<?php

namespace App\Console\Commands;

use App\Jobs\SendPortalEventEmailJob;
use App\Services\JobIngestionService;
use App\Services\Kafka\KafkaPublisher;
use Illuminate\Console\Command;

class ConsumeKafkaEvents extends Command
{
    protected $signature = 'kafka:consume {--once : Stop after one message or timeout}';
    protected $description = 'Consume Ascendia background events from Kafka';

    public function handle(KafkaPublisher $publisher, JobIngestionService $jobs): int
    {
        if (! $publisher->enabled()) {
            $this->error('Kafka is disabled or the rdkafka extension is unavailable.');
            return self::FAILURE;
        }

        $config = $publisher->configuration();
        $config->set('group.id', config('kafka.group_id'));
        $config->set('enable.auto.commit', 'false');
        $config->set('auto.offset.reset', 'earliest');
        $consumer = new \RdKafka\KafkaConsumer($config);
        $consumer->subscribe([config('kafka.topics.job_ingestion'), config('kafka.topics.email_dispatch')]);
        $this->info('Kafka consumer started.');

        while (true) {
            $message = $consumer->consume(1000);
            if ($message->err === RD_KAFKA_RESP_ERR__TIMED_OUT) {
                if ($this->option('once')) return self::SUCCESS;
                continue;
            }
            if ($message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                $this->error($message->errstr());
                continue;
            }

            try {
                $event = json_decode($message->payload, true, 512, JSON_THROW_ON_ERROR);
                if ($event['type'] === 'job.ingestion.requested') $jobs->ingest($event['data']['job']);
                if ($event['type'] === 'email.portal-event.requested') SendPortalEventEmailJob::dispatch($event['data']['notification_id']);
                $consumer->commit($message);
            } catch (\Throwable $exception) {
                report($exception);
                $this->error($exception->getMessage());
            }

            if ($this->option('once')) return self::SUCCESS;
        }
    }
}
