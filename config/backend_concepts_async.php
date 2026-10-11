<?php

// Backend concepts, part 3: background work, scheduling, performance and events. Rendered by learning.sql-case-studies.
$code = fn (string $caption, string $language, string $source) => ['type' => 'code', 'caption' => $caption, 'language' => $language, 'source' => trim($source)];
$table = fn (string $caption, array $columns, array $rows) => ['type' => 'table', 'caption' => $caption, 'columns' => $columns, 'rows' => $rows];
$text = fn (string ...$paragraphs) => ['type' => 'text', 'paragraphs' => $paragraphs];
$flow = fn (string $caption, array $steps) => ['type' => 'flow', 'caption' => $caption, 'steps' => $steps];
$ask = fn (array $items) => ['type' => 'questions', 'items' => $items];

return [
'queues-batches-cron-scheduling' => [
    'title' => 'Queues, Batches, Cron Jobs & Job Scheduling',
    'summary' => 'Moving slow work out of the request (queues), processing thousands of items as one tracked unit (batches), running work at fixed times (cron and the Laravel scheduler, Spring @Scheduled), and making all of it safe to retry, with banking, e-commerce and payment examples.',
    'example_label' => 'Running examples: order confirmation emails, nightly interest posting, monthly statements, payment reconciliation',
    'sections' => [
        ['id' => 'why', 'title' => 'Why background jobs exist', 'blocks' => [
            $text(
                'A web request should finish in well under a second. Sending an email (1–3 s), generating a PDF invoice, calling a slow partner API or resizing images would make the user wait and tie up a PHP worker. Instead the request puts a small message ("send receipt for order 187") on a queue and returns immediately; a separate worker process picks it up and does the work.',
                'Queues also absorb spikes. During a flash sale 50,000 orders arrive in ten minutes; the order is saved instantly and the confirmation emails drain over the next minutes without crashing the mail server.'
            ),
            $flow('Checkout with a queue', ['User clicks Pay', 'Save order + payment (fast DB transaction)', 'Dispatch SendOrderConfirmation, ReserveStock, NotifyWarehouse', 'Return 201 to the user', 'Workers process jobs in the background']),
            $table('Sync vs async', ['Do it in the request', 'Do it in a job'], [
                ['Validate input, check stock, charge the card (user needs the result)', 'Emails, SMS, push notifications'],
                ['Save the order', 'PDF invoices and statements'],
                ['Return order id', 'Webhooks to partners, search index updates, analytics'],
            ]),
        ]],
        ['id' => 'laravel-queues', 'title' => 'Laravel queues: jobs, workers, retries', 'blocks' => [
            $code('A job that is safe to retry', 'php', <<<'TXT'
class SendPaymentReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 60, 300];       // seconds between attempts
    public int $timeout = 30;

    public function __construct(public Payment $payment) {}   // only the id is serialized; reloaded in handle()

    public function handle(Mailer $mail): void
    {
        if ($this->payment->receipt_sent_at) return;           // idempotent: a retry must not email twice
        $mail->to($this->payment->user)->send(new PaymentReceiptMail($this->payment));
        $this->payment->update(['receipt_sent_at' => now()]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('Receipt failed', ['payment' => $this->payment->id, 'error' => $e->getMessage()]);
    }
}

// Dispatch only after the DB transaction commits, otherwise the worker may not find the payment yet
SendPaymentReceipt::dispatch($payment)->onQueue('emails')->afterCommit();
TXT),
            $code('Running workers', 'bash', <<<'TXT'
php artisan queue:work redis --queue=payments,emails,default --tries=3 --max-time=3600
php artisan queue:failed            # list failed jobs
php artisan queue:retry all         # retry them after fixing the bug
php artisan queue:restart           # workers reload code after a deployment
TXT),
            $text(
                'Drivers: database (simple, works on shared hosting, which is what this portal uses), Redis (fast, most common), SQS, or Kafka/RabbitMQ via packages. In production a process manager (Supervisor, systemd, a Kubernetes Deployment) keeps workers running. On cPanel without Supervisor, a cron entry runs queue:work --stop-when-empty every minute.',
                'Queue priority comes from the order in --queue=payments,emails,default: the worker always empties "payments" first.'
            ),
        ]],
        ['id' => 'batches', 'title' => 'Batches: many jobs tracked as one', 'blocks' => [
            $text('A batch groups many jobs and gives you progress, a single "all done" callback and failure handling. Typical uses: generate monthly statements for 2 million customers, import a 500,000-row product CSV in 1,000-row chunks, send a festive-offer email to every customer segment.'),
            $code('Monthly bank statements as a batch', 'php', <<<'TXT'
// php artisan queue:batches-table && php artisan migrate   (creates job_batches)
$jobs = Account::query()->where('status', 'active')->select('id')
    ->lazyById(1000)->chunk(500)
    ->map(fn ($ids) => new GenerateStatements($ids->pluck('id')->all(), '2026-09'));

$batch = Bus::batch($jobs->all())
    ->name('Statements 2026-09')
    ->allowFailures()                                       // one bad account must not stop 2M others
    ->then(fn (Batch $b) => Notification::route('mail', 'ops@bank.test')->notify(new StatementsReady($b->id)))
    ->catch(fn (Batch $b, Throwable $e) => Log::warning('statement chunk failed', ['batch' => $b->id]))
    ->finally(fn (Batch $b) => Cache::forget('statements:running'))
    ->onQueue('statements')
    ->dispatch();

// Progress for an admin screen
$batch = Bus::findBatch($id);
echo "{$batch->processedJobs()}/{$batch->totalJobs} ({$batch->progress()}%), failed: {$batch->failedJobs}";
TXT),
            $text('Chains are the sequential cousin: Bus::chain([new CaptureFunds($order), new GenerateInvoice($order), new NotifyWarehouse($order)])->dispatch() runs each job only after the previous one succeeded. Use a chain when order matters, a batch when the items are independent.'),
        ]],
        ['id' => 'cron', 'title' => 'Cron and the Laravel scheduler', 'blocks' => [
            $text(
                'Cron is the Unix service that runs commands at set times. Each line has five time fields (minute, hour, day of month, month, day of week) and a command. Managing dozens of cron lines on every server is error-prone, so Laravel needs only ONE cron entry that runs schedule:run every minute, and the schedule itself lives in code, under version control.',
                'On GoDaddy cPanel: Cron Jobs > Common settings "Once per minute" > command: /opt/alt/php83/usr/bin/php /home/USER/career-portal/naukri-com/artisan schedule:run >> /dev/null 2>&1'
            ),
            $code('Cron syntax', 'bash', <<<'TXT'
# ┌ minute (0-59)  ┌ hour (0-23)  ┌ day of month  ┌ month  ┌ day of week (0=Sun)
  *              *              *              *       *      php /var/www/artisan schedule:run   # every minute
  30             2              *              *       *      backup.sh                          # 02:30 daily
  0              */6            *              *       *      import-feeds.sh                    # every 6 hours
  0              9              1              *       *      statements.sh                      # 09:00 on the 1st
TXT),
            $code('app/Console/Kernel.php schedule for a bank and a shop', 'php', <<<'TXT'
protected function schedule(Schedule $schedule): void
{
    // Banking
    $schedule->command('interest:accrue')->dailyAt('00:05')->timezone('Asia/Kolkata')
        ->withoutOverlapping()->onOneServer();                  // never run twice, even with 5 servers
    $schedule->command('statements:generate --month=previous')->monthlyOn(1, '01:00');
    $schedule->command('loans:mark-overdue')->dailyAt('06:00')->environments(['production']);

    // Payments
    $schedule->command('payments:reconcile')->hourly()->runInBackground()
        ->onFailure(fn () => Notification::route('slack', config('alerts.slack'))->notify(new ReconcileFailed()));
    $schedule->command('payments:expire-pending')->everyFiveMinutes();   // cancel unpaid orders after 15 min

    // E-commerce
    $schedule->command('carts:send-abandoned-reminders')->hourlyAt(15);
    $schedule->job(new RebuildSearchIndex())->weeklyOn(0, '03:00');     // queued job on schedule
    $schedule->command('queue:prune-batches --hours=48')->daily();
}
TXT),
            $text(
                'withoutOverlapping() puts a lock in the cache so a slow run is not started again a minute later; onOneServer() makes sure only one of several servers runs it (requires a shared cache such as Redis). Long tasks should dispatch jobs instead of doing all the work inside the scheduled command.',
                'php artisan schedule:list shows every task and its next run; php artisan schedule:test runs one now.'
            ),
        ]],
        ['id' => 'spring', 'title' => 'Spring Boot: @Async, @Scheduled and ShedLock', 'blocks' => [
            $code('Scheduled interest posting with a distributed lock', 'java', <<<'TXT'
@SpringBootApplication
@EnableScheduling
@EnableAsync
public class BankApplication { }

@Component
class InterestJobs {
    @Scheduled(cron = "0 5 0 * * *", zone = "Asia/Kolkata")         // sec min hour day month weekday
    @SchedulerLock(name = "accrueInterest", lockAtMostFor = "PT2H")  // ShedLock: one instance only
    public void accrueInterest() {
        accounts.streamActiveSavings().forEach(interestService::accrueForToday);
    }

    @Scheduled(fixedDelay = 300_000)                                 // 5 min after the previous run ends
    public void expirePendingPayments() { payments.expireOlderThan(Duration.ofMinutes(15)); }
}

@Service
class ReceiptService {
    @Async                                                            // runs on a thread pool, caller does not wait
    public CompletableFuture<Void> send(Payment p) { mailer.send(p); return CompletableFuture.completedFuture(null); }
}
TXT),
            $text('@Async keeps work in the same JVM; if the instance restarts, in-flight work is lost. For anything important (money, emails customers rely on) use a durable queue or Kafka topic instead. Spring Batch is the framework for large chunked jobs (read-process-write with restartability), the counterpart of Laravel batches for bulk statement or settlement files.'),
        ]],
        ['id' => 'kafka-react', 'title' => 'Kafka as a queue, and showing progress in React', 'blocks' => [
            $text('A Kafka topic can replace a job queue when many independent consumers need the same event (see the Kafka events lesson). Each consumer group gets every message; inside one group partitions spread the work across instances, like queue workers.'),
            $code('React: poll batch progress for an admin screen', 'jsx', <<<'TXT'
function BatchProgress({ id }) {
  const [batch, setBatch] = useState(null);
  useEffect(() => {
    const timer = setInterval(async () => {
      const b = await (await fetch(`/api/admin/batches/${id}`)).json();
      setBatch(b);
      if (b.finished_at) clearInterval(timer);
    }, 3000);
    return () => clearInterval(timer);
  }, [id]);
  if (!batch) return <Spinner />;
  return <progress max="100" value={batch.progress}>{batch.progress}%</progress>;
}
TXT),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['Why must jobs be idempotent?', 'Workers can crash after doing the work but before acknowledging it, so the job runs again. Check a flag or unique key before acting.'],
                ['Why use afterCommit when dispatching?', 'Otherwise the worker can start before the transaction commits and not find the new rows, or the transaction rolls back and the job acts on data that never existed.'],
                ['How do you stop a scheduled task running on every server?', 'onOneServer() in Laravel (shared cache lock) or ShedLock in Spring.'],
                ['Queue vs cron?', 'Cron triggers work at a time; a queue processes work as it arrives. Scheduled commands often just dispatch queued jobs.'],
            ]),
        ]],
    ],
],

'performance-eager-loading-large-requests' => [
    'title' => 'Slow Pages, Large Requests & Eager Loading (N+1)',
    'summary' => 'Finding why a page or API is slow, fixing N+1 queries with eager loading, indexes, pagination, chunking large datasets, caching, handling big uploads and exports, and the same techniques in Spring Boot (JPA fetch joins), MySQL and React.',
    'example_label' => 'Running examples: order history page, transaction statement export, product listing during a sale',
    'sections' => [
        ['id' => 'measure', 'title' => 'Measure first', 'blocks' => [
            $text(
                'Most slow pages are slow for one of a few reasons: too many queries (N+1), a query without an index, too much data loaded into memory, slow external calls in the request, or heavy work that should be queued. Guessing wastes time; measure.',
                'Tools: Laravel Debugbar or Telescope (query count and time per request), DB::listen or the slow query log in MySQL, EXPLAIN on suspicious queries, Spring Boot Actuator metrics and Hibernate statistics, browser DevTools Network tab (time to first byte vs download vs rendering), and APM tools such as New Relic or Datadog in production.'
            ),
            $code('Quick checks', 'php', <<<'TXT'
// Fail loudly in development when a relation is lazy-loaded (Laravel 9+)
Model::preventLazyLoading(! app()->isProduction());

// Log slow queries
DB::listen(function ($query) {
    if ($query->time > 200) Log::warning('slow query', ['sql' => $query->sql, 'ms' => $query->time]);
});
TXT),
            $code('MySQL', 'sql', <<<'TXT'
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 0.5;

EXPLAIN SELECT * FROM transactions WHERE account_id = 42 ORDER BY created_at DESC LIMIT 20;
-- type=ALL and rows=4000000 means a full table scan: add an index
CREATE INDEX idx_tx_account_created ON transactions (account_id, created_at);
TXT),
        ]],
        ['id' => 'n-plus-one', 'title' => 'The N+1 problem and eager loading', 'blocks' => [
            $text(
                'Lazy loading fetches a relation the first time you access it. In a loop this produces one query for the list plus one query per row: N+1. Fifty orders with their customer and items becomes 101 queries; each is fast, but the round trips add up to seconds.',
                'Eager loading fetches the relation for all parent rows in one extra query using WHERE id IN (...). Fifty orders with customers and items becomes 3 queries regardless of the number of rows.'
            ),
            $code('Order history page', 'php', <<<'TXT'
// N+1: 1 + 50 (customer) + 50 (items) = 101 queries
$orders = Order::latest()->take(50)->get();
foreach ($orders as $order) {
    echo $order->customer->name . ' ' . $order->items->count();
}

// Eager loading: 3 queries
$orders = Order::with(['customer:id,name', 'items'])->latest()->take(50)->get();

// Only need a count? Do not load the rows at all
$orders = Order::with('customer:id,name')->withCount('items')->withSum('items as total_qty', 'quantity')->latest()->paginate(50);

// Nested and constrained eager loading
$accounts = Account::with([
    'transactions' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))->latest()->limit(5),
    'transactions.merchant:id,name',
])->get();

// Already have the models? Load afterwards
$orders->load('payments');
$order->loadMissing('items.product');
TXT),
            $code('Generated SQL with eager loading', 'sql', <<<'TXT'
SELECT * FROM orders ORDER BY created_at DESC LIMIT 50;
SELECT id, name FROM customers WHERE id IN (3, 9, 14, ...);
SELECT * FROM order_items WHERE order_id IN (187, 186, 185, ...);
TXT),
            $code('Spring Boot / JPA: the same problem and fixes', 'java', <<<'TXT'
// Lazy @OneToMany + loop = N+1. Fix with a fetch join or an entity graph:
@Query("select o from Order o join fetch o.customer where o.createdAt > :since")
List<Order> recentWithCustomer(@Param("since") Instant since);

@EntityGraph(attributePaths = {"customer", "items"})
Page<Order> findByStatus(String status, Pageable pageable);

// Or batch lazy loads globally: spring.jpa.properties.hibernate.default_batch_fetch_size=100
// Never return entities directly as JSON: serialisation triggers lazy loads; map to DTOs.
TXT),
            $text('Over-eager loading is the opposite mistake: with("everything") on a list page that shows two columns loads megabytes of unused data. Load what the screen uses, and select only needed columns.'),
        ]],
        ['id' => 'large-data', 'title' => 'Large result sets: pagination, chunking, streaming', 'blocks' => [
            $text('Loading 2 million transactions with ->get() builds 2 million PHP objects and exhausts memory. Paginate for screens; chunk or stream for processing and exports.'),
            $code('Techniques for big tables', 'php', <<<'TXT'
// Screens: paginate (cursor pagination stays fast on deep pages)
$tx = $account->transactions()->latest('id')->cursorPaginate(25);

// Processing: chunkById walks the primary key, safe even while updating rows
Transaction::where('status', 'pending')->chunkById(1000, function ($rows) {
    foreach ($rows as $tx) $tx->settle();
});

// Low memory iteration: lazy() / lazyById() / cursor()
foreach (Customer::where('segment', 'gold')->lazyById(2000) as $customer) {
    SendFestiveOffer::dispatch($customer->id);
}

// Bulk writes: one query instead of thousands
DB::table('price_updates')->upsert($rows, ['sku'], ['price', 'updated_at']);
Product::whereIn('id', $ids)->update(['is_featured' => true]);

// Exports: stream a CSV instead of building it in memory
return response()->streamDownload(function () use ($account) {
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Description', 'Debit', 'Credit', 'Balance']);
    $account->transactions()->orderBy('id')->lazyById(2000)->each(fn ($t) => fputcsv($out, [$t->created_at, $t->narration, $t->debit, $t->credit, $t->balance]));
    fclose($out);
}, "statement-{$account->number}.csv");
TXT),
            $text('For very large exports (a year of transactions for a corporate account) do not stream inside the request at all: dispatch a job that writes the file to storage, then email a signed download link. The same applies to large imports: accept the upload, store it, and process it in a queued batch of chunks.'),
        ]],
        ['id' => 'large-requests', 'title' => 'Large requests: uploads, payloads and timeouts', 'blocks' => [
            $table('Limits to align', ['Layer', 'Setting', 'Example'], [
                ['Nginx / Apache', 'client_max_body_size / LimitRequestBody', '20M'],
                ['PHP', 'upload_max_filesize, post_max_size, max_execution_time, memory_limit', '20M, 25M, 60, 256M'],
                ['Laravel validation', "'file' => 'max:20480'", 'kilobytes'],
                ['Spring Boot', 'spring.servlet.multipart.max-file-size', '20MB'],
                ['Load balancer / CDN', 'idle timeout', '60 s'],
            ]),
            $text(
                'For files larger than a few MB, upload directly from the browser to object storage (S3 pre-signed URL) so the bytes never pass through PHP, then send only the file key to the API. For bulk API payloads (a merchant posting 10,000 refunds), accept the request, validate the shape, store it, return 202 Accepted with a job id, and process it asynchronously; the client polls or receives a webhook.',
                'Protect the server from oversized JSON: limit array sizes in validation (items => max:50), limit request body size at the web server, and rate-limit endpoints.'
            ),
        ]],
        ['id' => 'caching', 'title' => 'Caching and other quick wins', 'blocks' => [
            $code('Cache expensive, rarely changing data', 'php', <<<'TXT'
// Category tree on every product page: cache for an hour, clear when an admin edits categories
$tree = Cache::remember('categories:tree:v1', now()->addHour(), fn () => Category::with('children')->whereNull('parent_id')->get());

// Per-user dashboard totals: short TTL
$summary = Cache::remember("dashboard:{$user->id}", 60, fn () => $reports->summaryFor($user));

// Invalidate on change
Category::saved(fn () => Cache::forget('categories:tree:v1'));
TXT),
            $table('Quick wins checklist', ['Area', 'Action'], [
                ['Laravel boot', 'php artisan config:cache route:cache view:cache event:cache; OPcache on'],
                ['Database', 'Composite indexes matching WHERE + ORDER BY; avoid SELECT *; avoid functions on indexed columns'],
                ['External calls', 'Timeouts, queue them, cache responses where allowed'],
                ['Responses', 'Gzip/Brotli, CDN for images and JS, HTTP caching headers'],
                ['Search', 'Full-text index or a search engine (Meilisearch, Elasticsearch) instead of LIKE %term%'],
            ]),
            $code('React: do not render 10,000 rows', 'jsx', <<<'TXT'
// Paginate or virtualise long lists, debounce search, and lazy-load heavy screens
const Reports = React.lazy(() => import('./Reports'));          // separate JS chunk
const debounced = useMemo(() => debounce(q => setQuery(q), 300), []);
<FixedSizeList height={600} itemCount={rows.length} itemSize={48}>{Row}</FixedSizeList>   // react-window
TXT),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['What is N+1 and how do you detect it?', 'One query for a list plus one per row for a relation. Detect with Debugbar/Telescope query counts or preventLazyLoading; fix with with()/withCount() or JPA fetch joins.'],
                ['chunk() vs chunkById() vs lazy()?', 'chunk uses OFFSET and can skip rows if you update the filtered column; chunkById walks the key and is safe; lazy streams models one by one with low memory.'],
                ['A report times out after 30 seconds. What do you do?', 'Find the slow part (EXPLAIN, profiling), add indexes or pre-aggregate, and move generation to a queued job with a download link.'],
                ['Why is OFFSET pagination slow on page 10,000?', 'MySQL reads and discards all previous rows; use cursor/keyset pagination.'],
            ]),
        ]],
    ],
],

'events-kafka' => [
    'title' => 'Events & Kafka in Banking, E-commerce and Payments',
    'summary' => 'Event-driven design from Laravel events and listeners up to Kafka topics: what an event is, producers and consumers, partitions and ordering, consumer groups, delivery guarantees, the outbox pattern, and complete flows for an order, a payment and a bank transfer.',
    'example_label' => 'Running examples: OrderPlaced, PaymentCaptured, FundsTransferred events consumed by many services',
    'sections' => [
        ['id' => 'events', 'title' => 'Events: announcing what happened', 'blocks' => [
            $text(
                'An event is a fact about the past: OrderPlaced, PaymentCaptured, FundsTransferred. The code that produces it does not know or care who reacts. Listeners subscribe and do their part: send email, reduce stock, award loyalty points, update analytics.',
                'This removes coupling. Without events, OrderService calls Mailer, Inventory, Loyalty, Analytics and Warehouse directly, so adding a new reaction means editing checkout code. With events, you add a listener and checkout never changes.'
            ),
            $code('Laravel events and queued listeners', 'php', <<<'TXT'
class OrderPlaced
{
    use Dispatchable, SerializesModels;
    public function __construct(public Order $order) {}
}

// EventServiceProvider
protected $listen = [
    OrderPlaced::class => [SendOrderConfirmation::class, ReserveStock::class, AwardLoyaltyPoints::class],
];

class AwardLoyaltyPoints implements ShouldQueue          // runs on a worker, not in the checkout request
{
    public function handle(OrderPlaced $event): void
    {
        $event->order->customer->increment('points', intdiv($event->order->total_paise, 10000));
    }
}

// In OrderService, after the transaction commits
DB::afterCommit(fn () => OrderPlaced::dispatch($order));
TXT),
            $text('Laravel events live inside one application. When several separately deployed services (orders, payments, notifications, fraud, analytics) must react, events need a broker that stores them durably and lets each service read at its own pace. That is Kafka.'),
        ]],
        ['id' => 'kafka', 'title' => 'Kafka building blocks', 'blocks' => [
            $table('Core terms', ['Term', 'Meaning', 'Payment example'], [
                ['Topic', 'Named, append-only log of events', 'payments.captured'],
                ['Partition', 'A topic is split into partitions for parallelism; order is guaranteed only within one partition', '12 partitions'],
                ['Key', 'Decides the partition; same key, same partition, same order', 'merchant_id or account_id'],
                ['Producer', 'Writes events', 'Payment service'],
                ['Consumer group', 'A set of instances sharing the work; each partition goes to one instance in the group', 'settlement-service, fraud-service, notification-service'],
                ['Offset', 'Position of a consumer in a partition, committed after processing', 'settlement at offset 99120'],
                ['Retention', 'How long events are kept, independent of consumption', '7 days, or compacted forever'],
            ]),
            $flow('One event, many independent consumers', ['Payment service produces PaymentCaptured(key = merchant_id)', 'Topic payments.captured', 'Settlement group: credits merchant ledger', 'Notification group: SMS + email', 'Fraud group: scores the payment', 'Analytics group: dashboards']),
            $text(
                'Unlike a job queue where a message is deleted once handled, Kafka keeps events for the retention period. A new service (say, a cashback engine) can start reading from the beginning and catch up. Each consumer group tracks its own offset, so a slow analytics service never delays settlement.',
                'Ordering: all events for account 42 must be applied in order (debit before the balance check of the next transfer). Using account_id as the key sends them to the same partition, where order is preserved.'
            ),
        ]],
        ['id' => 'spring', 'title' => 'Producing and consuming in Spring Boot', 'blocks' => [
            $code('Producer and idempotent consumer', 'java', <<<'TXT'
// application.yml: spring.kafka.producer.acks=all, enable.idempotence=true (no duplicates from producer retries)
@Service
@RequiredArgsConstructor
class PaymentEvents {
    private final KafkaTemplate<String, PaymentCaptured> kafka;
    void captured(Payment p) {
        kafka.send("payments.captured", p.getMerchantId(), new PaymentCaptured(p.getId(), p.getMerchantId(), p.getAmountPaise(), Instant.now()));
    }
}

@Component
class SettlementConsumer {
    @KafkaListener(topics = "payments.captured", groupId = "settlement-service", concurrency = "4")
    @Transactional
    public void on(PaymentCaptured e) {
        if (processed.existsById(e.paymentId())) return;            // at-least-once delivery => dedupe
        ledger.creditMerchant(e.merchantId(), e.amountPaise());
        processed.save(new ProcessedEvent(e.paymentId()));
    }
}
TXT),
        ]],
        ['id' => 'laravel', 'title' => 'Kafka from Laravel', 'blocks' => [
            $code('Publishing with a Kafka client package (mateusjunges/laravel-kafka)', 'php', <<<'TXT'
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

Kafka::publish('kafka:9092')->onTopic('orders.placed')
    ->withMessage(new Message(key: (string) $order->customer_id, body: [
        'event_id' => (string) Str::uuid(), 'order_id' => $order->number, 'total_paise' => $order->total_paise, 'occurred_at' => now()->toIso8601String(),
    ]))->send();

// Consumer as an artisan command run by Supervisor
Kafka::consumer(['orders.placed'], 'warehouse-service')
    ->withHandler(function (ConsumerMessage $message) {
        $body = $message->getBody();
        if (ProcessedEvent::where('event_id', $body['event_id'])->exists()) return;
        DB::transaction(function () use ($body) {
            Shipment::create(['order_number' => $body['order_id'], 'status' => 'picking']);
            ProcessedEvent::create(['event_id' => $body['event_id']]);
        });
    })->build()->consume();
TXT),
            $text('This portal has a KafkaPublisher that stays off unless KAFKA_ENABLED=true, because shared hosting has no Kafka broker. The same events are handled by Laravel listeners and queues there.'),
        ]],
        ['id' => 'outbox', 'title' => 'Delivery guarantees and the outbox pattern', 'blocks' => [
            $text(
                'The dual-write problem: you commit the payment row to MySQL, then publish to Kafka, and the process crashes in between. The database says "captured" but no one ever settles the merchant. Publishing first is worse: the event exists but the transaction may roll back.',
                'The transactional outbox fixes it: in the same database transaction, insert the event into an outbox table. A separate relay (a scheduled job, or Debezium reading the MySQL binlog) publishes outbox rows to Kafka and marks them sent. If the relay crashes it publishes again, so consumers must be idempotent: at-least-once delivery plus dedupe gives effectively-once processing.'
            ),
            $code('Outbox in Laravel + MySQL', 'php', <<<'TXT'
DB::transaction(function () use ($payment) {
    $payment->update(['status' => 'captured']);
    DB::table('outbox')->insert([
        'id' => (string) Str::uuid(), 'topic' => 'payments.captured', 'key' => (string) $payment->merchant_id,
        'payload' => json_encode(['payment_id' => $payment->id, 'amount_paise' => $payment->amount_paise]),
        'created_at' => now(),
    ]);
});

// Scheduled every minute: relay unsent rows
DB::table('outbox')->whereNull('sent_at')->orderBy('created_at')->limit(500)->get()->each(function ($row) use ($kafka) {
    $kafka->publish($row->topic, $row->key, $row->payload);
    DB::table('outbox')->where('id', $row->id)->update(['sent_at' => now()]);
});
TXT),
            $table('Delivery semantics', ['Guarantee', 'Meaning', 'How'], [
                ['At most once', 'May lose events, never duplicates', 'Commit offset before processing'],
                ['At least once', 'Never loses, may duplicate', 'Commit offset after processing (default choice)'],
                ['Exactly once (effectively)', 'Each event affects state once', 'At least once + idempotent consumer, or Kafka transactions within Kafka'],
            ]),
        ]],
        ['id' => 'flows', 'title' => 'End-to-end flows', 'blocks' => [
            $flow('E-commerce order', ['OrderPlaced', 'Inventory reserves stock', 'Payment captures', 'PaymentCaptured', 'Warehouse picks and ships', 'OrderShipped', 'Notification sends tracking SMS']),
            $flow('Bank transfer (NEFT/IMPS)', ['TransferRequested', 'Fraud scoring (may hold)', 'Core banking debits sender', 'FundsDebited', 'Switch sends to beneficiary bank', 'FundsTransferred or TransferFailed (auto-reversal)', 'Customer alert + statement entry']),
            $flow('Payment gateway refund', ['RefundRequested (merchant API)', 'Check refundable balance', 'Call card network / UPI', 'RefundProcessed', 'Merchant webhook', 'Settlement adjusts next payout']),
            $text('A failure part-way (stock reserved but payment failed) is undone with compensating events (StockReleased) rather than a distributed transaction. This is the saga pattern; each step has a defined compensation.'),
        ]],
        ['id' => 'react', 'title' => 'Showing events live in React', 'blocks' => [
            $text('Browsers do not connect to Kafka. A backend service consumes the topic and pushes updates to the browser over WebSockets (Laravel Reverb or Pusher, Spring WebSocket/STOMP) or Server-Sent Events. Typical uses: live order tracking, "payment received" on a merchant dashboard.'),
            $code('Listening for a payment update with Laravel Echo', 'jsx', <<<'TXT'
useEffect(() => {
  const channel = window.Echo.private(`merchant.${merchantId}`)
    .listen('.payment.captured', e => setPayments(list => [e.payment, ...list]));
  return () => window.Echo.leave(`merchant.${merchantId}`);
}, [merchantId]);
TXT),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['How do you keep events for one account in order?', 'Use account_id as the message key so all its events go to one partition, and process each partition sequentially.'],
                ['What happens if there are more consumers than partitions?', 'Extra consumers in the group sit idle; partitions are the unit of parallelism.'],
                ['Kafka vs a job queue?', 'A queue delivers each job to one worker and deletes it; Kafka keeps an ordered log that many groups read independently and can replay.'],
                ['What is the outbox pattern for?', 'Atomically saving state and the event to publish, avoiding lost or phantom events when the DB and Kafka cannot share a transaction.'],
            ]),
        ]],
    ],
],
];
