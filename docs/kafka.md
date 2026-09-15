# Kafka integration

Kafka is used only for asynchronous, high-volume work. Search, authentication, OTP verification, and normal page requests do not wait for Kafka.

## Integrated flows

- `ascendia.jobs.ingestion.v1`: bulk external job ingestion
- `ascendia.email.dispatch.v1`: portal-event email dispatch
- `ascendia.profile.audit.v1`: profile audit event stream
- `ascendia.news.events.v1`: news-fetch completion events

Job ingestion and email dispatch fall back to Laravel's database queue if Kafka is disabled or publishing fails. Consumers commit offsets only after successful handling. Job writes are idempotent by source and external job ID.

## Local broker

Start Docker Desktop, then run:

```bash
docker compose -f compose.kafka.yml up -d
docker compose -f compose.kafka.yml ps
```

The compose file uses the official Apache Kafka 4.3.1 JVM image in single-node KRaft mode. Run Laravel in its Docker image, which installs `librdkafka` and PHP `rdkafka`.

```dotenv
KAFKA_ENABLED=true
KAFKA_BROKERS=host.docker.internal:9092
```

```bash
php artisan kafka:consume
```

## Production

Use a managed Kafka cluster. Configure the broker and SASL variables on both the web service and a dedicated consumer worker. Start the consumer before enabling producers. Never expose a plaintext Kafka listener to the public internet.
