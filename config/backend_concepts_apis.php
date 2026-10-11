<?php

// Backend concepts, part 2: APIs, framework internals and packaging. Rendered by learning.sql-case-studies.
$code = fn (string $caption, string $language, string $source) => ['type' => 'code', 'caption' => $caption, 'language' => $language, 'source' => trim($source)];
$table = fn (string $caption, array $columns, array $rows) => ['type' => 'table', 'caption' => $caption, 'columns' => $columns, 'rows' => $rows];
$text = fn (string ...$paragraphs) => ['type' => 'text', 'paragraphs' => $paragraphs];
$flow = fn (string $caption, array $steps) => ['type' => 'flow', 'caption' => $caption, 'steps' => $steps];
$ask = fn (array $items) => ['type' => 'questions', 'items' => $items];

return [
'rest-graphql-curl-swagger' => [
    'title' => 'REST APIs, GraphQL, cURL & Swagger',
    'summary' => 'Designing REST APIs properly (resources, verbs, status codes, pagination, versioning), when GraphQL is a better fit, testing any API from the terminal with cURL, and documenting it with OpenAPI/Swagger, all with e-commerce, banking and payment endpoints in Laravel, Spring Boot and React.',
    'example_label' => 'Running examples: product catalogue, order placement, payment refunds, account statements',
    'sections' => [
        ['id' => 'rest', 'title' => 'REST: resources, verbs and status codes', 'blocks' => [
            $text(
                'REST (Representational State Transfer) models an API as resources (nouns) addressed by URLs, manipulated with standard HTTP methods (verbs). The server is stateless: each request carries everything needed, including authentication. Responses are usually JSON.',
                'Good REST design makes an API predictable: once a developer knows GET /orders, they can guess GET /orders/{id}, POST /orders and so on.'
            ),
            $table('E-commerce order API', ['Method + URL', 'Meaning', 'Success code', 'Idempotent?'], [
                ['GET /api/v1/products?category=mobiles&sort=-price&page=2', 'List with filter, sort, pagination', '200', 'Yes'],
                ['GET /api/v1/orders/ORD-187', 'One order', '200 / 404', 'Yes'],
                ['POST /api/v1/orders', 'Place an order', '201 + Location header', 'No (use Idempotency-Key)'],
                ['PATCH /api/v1/orders/ORD-187', 'Change delivery address', '200', 'Usually'],
                ['POST /api/v1/orders/ORD-187/cancel', 'Domain action (not CRUD)', '200 / 409 if shipped', 'Should be'],
                ['DELETE /api/v1/cart/items/55', 'Remove from cart', '204', 'Yes'],
            ]),
            $text('Rules of thumb: plural nouns, no verbs in URLs except for domain actions, nested URLs only one level deep (/orders/187/items), version in the URL or a header (/v1), consistent JSON envelope, and never return a 200 with "error" in the body.'),
        ]],
        ['id' => 'laravel-rest', 'title' => 'Laravel REST endpoint with resources and pagination', 'blocks' => [
            $code('Controller, API Resource and cursor pagination', 'php', <<<'TXT'
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    Route::post('orders/{order}/cancel', CancelOrderController::class);
});

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with('items.product:id,name,price')            // avoid N+1
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->cursorPaginate(20);                           // fast on large tables, stable while new rows arrive
        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request, OrderService $service)
    {
        $order = $service->place($request->user(), $request->validated());
        return (new OrderResource($order))->response()->setStatusCode(201)
            ->header('Location', route('orders.show', $order));
    }
}

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->number,                          // public id, not the auto-increment
            'status' => $this->status,
            'total' => ['amount' => $this->total_paise / 100, 'currency' => 'INR'],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'placed_at' => $this->created_at->toIso8601String(),
        ];
    }
}
TXT),
        ]],
        ['id' => 'spring-rest', 'title' => 'The same endpoint in Spring Boot', 'blocks' => [
            $code('Controller with validation and a DTO', 'java', <<<'TXT'
@RestController
@RequestMapping("/api/v1/orders")
@RequiredArgsConstructor
class OrderController {
    private final OrderService service;

    @GetMapping
    Page<OrderDto> list(@AuthenticationPrincipal Jwt jwt, @RequestParam(required = false) String status, Pageable pageable) {
        return service.list(jwt.getSubject(), status, pageable).map(OrderDto::from);
    }

    @PostMapping
    ResponseEntity<OrderDto> place(@Valid @RequestBody PlaceOrderRequest body, @RequestHeader("Idempotency-Key") String key) {
        Order order = service.place(body, key);
        return ResponseEntity.created(URI.create("/api/v1/orders/" + order.getNumber())).body(OrderDto.from(order));
    }
}

record PlaceOrderRequest(@NotNull Long addressId, @NotEmpty @Size(max = 50) List<@Valid Line> items) {
    record Line(@NotNull Long productId, @Min(1) @Max(10) int quantity) {}
}
TXT),
        ]],
        ['id' => 'graphql', 'title' => 'GraphQL: one endpoint, client chooses the fields', 'blocks' => [
            $text(
                'GraphQL exposes a single endpoint (POST /graphql) and a typed schema. The client sends a query describing exactly the fields it needs, possibly across several related objects, and gets back JSON in the same shape.',
                'It solves two REST problems. Over-fetching: a mobile order list needs 4 fields but /orders returns 40. Under-fetching: one screen needs order + items + product images + delivery status, which is 4 REST calls. With GraphQL it is one request with only those fields.',
                'Costs: caching by URL no longer works (everything is POST /graphql), a client can ask for very deep or expensive queries (limit depth and complexity), and N+1 queries appear in resolvers unless you batch them (DataLoader, Lighthouse @hasMany batching).'
            ),
            $code('Schema and a query for an order-tracking screen', 'graphql', <<<'TXT'
type Order { id: ID!  status: String!  total: Float!  items: [OrderItem!]!  shipment: Shipment }
type OrderItem { quantity: Int!  product: Product! }
type Product { name: String!  imageUrl: String }
type Query { order(id: ID!): Order }
type Mutation { cancelOrder(id: ID!, reason: String): Order }

query TrackOrder {
  order(id: "ORD-187") {
    status
    items { quantity product { name imageUrl } }
    shipment { courier trackingNumber eta }
  }
}
TXT),
            $code('Laravel with Lighthouse (schema-first)', 'graphql', <<<'TXT'
# graphql/schema.graphql
type Query {
  order(id: ID! @eq(key: "number")): Order @find @guard @can(ability: "view", find: "number")
}
type Order { number: ID!  status: String!  items: [OrderItem!]! @hasMany }
TXT),
            $code('Spring for GraphQL', 'java', <<<'TXT'
@Controller
class OrderGraphController {
    @QueryMapping
    Order order(@Argument String id) { return orders.findByNumber(id); }

    @BatchMapping                                   // loads items for many orders in one query (no N+1)
    Map<Order, List<OrderItem>> items(List<Order> orders) { return items.findByOrders(orders); }
}
TXT),
            $code('React with Apollo Client', 'jsx', <<<'TXT'
const TRACK = gql`query($id: ID!) { order(id: $id) { status shipment { courier eta } } }`;

function Tracking({ id }) {
  const { data, loading, error } = useQuery(TRACK, { variables: { id } });
  if (loading) return <Spinner />;
  if (error) return <p>Could not load tracking.</p>;
  return <p>{data.order.status} with {data.order.shipment?.courier}, arriving {data.order.shipment?.eta}</p>;
}
TXT),
            $table('REST or GraphQL?', ['Situation', 'Prefer'], [
                ['Public payment API used by thousands of merchants', 'REST (simple, cacheable, easy to document and version)'],
                ['Mobile app screens combining many related objects', 'GraphQL'],
                ['File upload, webhooks, simple CRUD', 'REST'],
                ['Many teams\' services behind one client-facing API', 'GraphQL gateway / federation'],
            ]),
        ]],
        ['id' => 'curl', 'title' => 'cURL: calling and debugging APIs from the terminal', 'blocks' => [
            $text('cURL is a command-line HTTP client available on every server. Engineers use it to test endpoints, reproduce bugs ("send me the curl"), check headers and TLS, and call webhooks. Postman and browser DevTools can export any request as a cURL command.'),
            $code('Everyday cURL commands', 'bash', <<<'TXT'
# GET with a token, show response headers (-i)
curl -i https://api.shop.test/api/v1/orders/ORD-187 -H "Authorization: Bearer 12|abc..." -H "Accept: application/json"

# POST JSON (place an order) with an idempotency key
curl -X POST https://api.shop.test/api/v1/orders \
  -H "Authorization: Bearer 12|abc..." -H "Content-Type: application/json" -H "Idempotency-Key: 7f9c-1" \
  -d '{"address_id": 3, "items": [{"product_id": 10, "quantity": 2}]}'

# Upload a file (multipart)
curl -X POST https://bank.test/api/kyc -H "Authorization: Bearer ..." -F "pan=@/home/me/pan.jpg"

# Debug: timing breakdown, verbose TLS handshake, follow redirects
curl -o /dev/null -s -w "dns=%{time_namelookup} connect=%{time_connect} tls=%{time_appconnect} total=%{time_total}\n" https://bank.test
curl -v https://bank.test
curl -L http://bank.test          # follows the 301 to https
TXT),
            $code('Calling a payment gateway from PHP (Laravel HTTP client is a cURL wrapper)', 'php', <<<'TXT'
$response = Http::withBasicAuth(config('services.razorpay.key'), config('services.razorpay.secret'))
    ->timeout(10)->retry(2, 500, throw: false)
    ->post('https://api.razorpay.com/v1/payments/' . $paymentId . '/refund', ['amount' => 50000]);

if ($response->failed()) {
    throw new RefundFailedException($response->json('error.description'));
}

// Raw PHP cURL, what the wrapper does underneath
$ch = curl_init('https://api.example.com/v1/orders');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json']]);
$body = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
TXT),
            $text('Always set a timeout when calling another service. Without one, a slow payment gateway keeps your PHP-FPM worker busy until all workers are stuck and the whole site goes down.'),
        ]],
        ['id' => 'swagger', 'title' => 'Swagger / OpenAPI: documenting the API', 'blocks' => [
            $text(
                'OpenAPI is a standard YAML/JSON description of an API: endpoints, parameters, request and response schemas, auth and error codes. Swagger UI renders it as an interactive page where developers can try requests. Other tools generate client SDKs, mock servers and contract tests from the same file.',
                'Two approaches: code-first (annotations in controllers generate the spec; springdoc-openapi, L5-Swagger, Scramble) or design-first (write the spec, review it with consumers, then implement). Payment companies usually go design-first because merchants build against the spec before the API exists.'
            ),
            $code('OpenAPI snippet for a refund endpoint', 'yaml', <<<'TXT'
openapi: 3.0.3
info: { title: Payments API, version: "1.0" }
paths:
  /v1/payments/{paymentId}/refunds:
    post:
      summary: Refund a captured payment (full or partial)
      security: [{ bearerAuth: [] }]
      parameters:
        - { name: paymentId, in: path, required: true, schema: { type: string, example: pay_9X2 } }
        - { name: Idempotency-Key, in: header, required: true, schema: { type: string } }
      requestBody:
        content:
          application/json:
            schema: { type: object, required: [amount], properties: { amount: { type: integer, description: "in paise" } } }
      responses:
        "201": { description: Refund created }
        "409": { description: Payment already fully refunded }
        "422": { description: Amount exceeds refundable balance }
components:
  securitySchemes: { bearerAuth: { type: http, scheme: bearer } }
TXT),
            $code('Spring Boot: springdoc generates /swagger-ui.html from annotations', 'java', <<<'TXT'
// implementation 'org.springdoc:springdoc-openapi-starter-webmvc-ui:2.6.0'
@Operation(summary = "Refund a captured payment")
@ApiResponse(responseCode = "201", description = "Refund created")
@ApiResponse(responseCode = "409", description = "Already refunded")
@PostMapping("/v1/payments/{paymentId}/refunds")
RefundDto refund(@PathVariable String paymentId, @Valid @RequestBody RefundRequest body) { ... }
TXT),
            $code('Laravel: L5-Swagger annotations (or Scramble, which infers docs from Form Requests and Resources)', 'php', <<<'TXT'
// composer require darkaonline/l5-swagger  ->  php artisan l5-swagger:generate  ->  /api/documentation
/**
 * @OA\Post(path="/api/v1/orders", summary="Place an order", security={{"sanctum":{}}},
 *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PlaceOrder")),
 *   @OA\Response(response=201, description="Created"),
 *   @OA\Response(response=422, description="Validation failed"))
 */
public function store(StoreOrderRequest $request) { ... }
TXT),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['PUT vs PATCH?', 'PUT replaces the whole resource and is idempotent; PATCH changes some fields.'],
                ['How do you version a REST API?', 'URL prefix (/v1) is most common and easy to route; headers are cleaner but harder to test. Never break v1; add v2 and deprecate with a timeline.'],
                ['How do you stop expensive GraphQL queries?', 'Depth and complexity limits, pagination on lists, persisted queries, timeouts and DataLoader batching.'],
                ['Offset vs cursor pagination?', 'OFFSET scans and discards rows, so page 5,000 is slow and rows shift when new data arrives; cursor (WHERE id < last_id) uses the index and is stable.'],
            ]),
        ]],
    ],
],

'service-container-providers-annotations' => [
    'title' => 'Service Container, Service Providers, Dependency Injection & Annotations',
    'summary' => 'How Laravel builds objects for you (the service container), where you register and configure services (service providers), how this matches Spring Boot\'s IoC container and annotations, and why it matters: swapping a payment gateway, testing with fakes, and keeping controllers small.',
    'example_label' => 'Running example: a PaymentGateway interface with Razorpay, Stripe and fake implementations',
    'sections' => [
        ['id' => 'di', 'title' => 'Dependency injection: the problem it solves', 'blocks' => [
            $text(
                'If a class creates its own dependencies (new RazorpayClient(...) inside CheckoutService) it is welded to them: you cannot switch to Stripe for international cards, and tests would hit the real gateway. Dependency injection means the class asks for what it needs, usually an interface, in its constructor, and something else supplies it.',
                'That "something else" is an IoC (Inversion of Control) container. Laravel calls it the service container; Spring calls it the ApplicationContext. It knows how to build every service and its dependencies, recursively.'
            ),
            $code('Without and with injection', 'php', <<<'TXT'
// Tightly coupled
class CheckoutService {
    public function pay(Order $order) {
        $client = new RazorpayClient(env('RZP_KEY'), env('RZP_SECRET'));   // hard to test, hard to change
        return $client->charge($order->total_paise);
    }
}

// Injected
interface PaymentGateway { public function charge(Order $order, string $idempotencyKey): PaymentResult; }

class CheckoutService {
    public function __construct(private PaymentGateway $gateway, private OrderRepository $orders) {}
    public function pay(Order $order, string $key): PaymentResult { return $this->gateway->charge($order, $key); }
}
TXT),
        ]],
        ['id' => 'container', 'title' => 'The Laravel service container', 'blocks' => [
            $text(
                'When Laravel calls a controller, job, listener, command or middleware, it reads the type hints of the constructor and method parameters and asks the container for each one. Concrete classes are built automatically by reflection (auto-wiring). Interfaces need a binding that says which implementation to use.',
                'Binding types: bind() creates a new instance every time; singleton() creates one and reuses it for the request (or the whole worker process under Octane and queue workers, so do not keep per-user state in singletons); scoped() is a singleton reset per request/job; instance() registers an existing object; when()->needs()->give() supplies different implementations to different consumers.'
            ),
            $code('Bindings', 'php', <<<'TXT'
$this->app->singleton(PaymentGateway::class, fn ($app) => new RazorpayGateway(
    new RazorpayClient(config('services.razorpay.key'), config('services.razorpay.secret')),
    $app->make(LoggerInterface::class),
));

// International orders use Stripe, domestic use Razorpay
$this->app->when(InternationalCheckoutService::class)->needs(PaymentGateway::class)->give(StripeGateway::class);

// Resolving manually (rarely needed; prefer constructor injection)
$gateway = app(PaymentGateway::class);
$gateway = $this->app->make(PaymentGateway::class);
TXT),
            $code('Tests swap the real gateway for a fake', 'php', <<<'TXT'
public function test_order_is_marked_paid_when_charge_succeeds(): void
{
    $this->app->instance(PaymentGateway::class, new FakeGateway(succeeds: true));

    $this->postJson('/api/v1/orders/ORD-187/pay', [], ['Idempotency-Key' => 'k1'])->assertOk();

    $this->assertSame('paid', Order::firstWhere('number', 'ORD-187')->status);
}
TXT),
        ]],
        ['id' => 'providers', 'title' => 'Service providers: where the app is wired together', 'blocks' => [
            $text(
                'A service provider is a class that registers bindings and boots features. Every Laravel feature (database, queue, mail, routing) is loaded by a provider. Your own providers are listed in config/app.php (Laravel 9/10) or bootstrap/providers.php (11+).',
                'register() runs first for all providers and must only bind things into the container: do not use other services there because they may not be registered yet. boot() runs after every provider is registered and is where you use services: define gates, rate limiters, macros, view composers, event listeners, model observers and Blade directives.',
                'Deferred providers (implementing DeferrableProvider) are only loaded when one of their services is actually requested, which saves time on every request.'
            ),
            $code('PaymentServiceProvider', 'php', <<<'TXT'
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');
        $this->app->singleton(PaymentGateway::class, fn ($app) => match (config('payments.default')) {
            'stripe' => $app->make(StripeGateway::class),
            'fake' => new FakeGateway(),
            default => $app->make(RazorpayGateway::class),
        });
    }

    public function boot(): void
    {
        RateLimiter::for('payments', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?: $r->ip()));
        Event::listen(PaymentCaptured::class, [SendPaymentReceipt::class, 'handle']);
        Payment::observe(PaymentAuditObserver::class);
    }
}
TXT),
            $flow('Laravel boot order', ['public/index.php', 'Create application (container)', 'register() on all providers', 'boot() on all providers', 'Handle request through middleware and router']),
        ]],
        ['id' => 'spring', 'title' => 'Spring Boot: the same ideas with annotations', 'blocks' => [
            $text(
                'Annotations are metadata attached to classes, methods or fields with @Name. They do nothing by themselves; a framework reads them (at startup via reflection, or at compile time) and changes behaviour. Spring Boot is driven almost entirely by annotations; PHP 8 has native attributes (#[Route], #[AsCommand]) and older PHP libraries used docblock annotations (/** @OA\Get */).',
                'In Spring, @Component/@Service/@Repository/@Controller mark a class as a bean the container should create. Constructor injection supplies dependencies. @Configuration classes with @Bean methods play the role of Laravel service providers. @Primary and @Qualifier choose between several implementations, like when()->needs()->give().'
            ),
            $code('Spring beans for the payment gateway', 'java', <<<'TXT'
public interface PaymentGateway { PaymentResult charge(Order order, String idempotencyKey); }

@Service @Primary
class RazorpayGateway implements PaymentGateway { ... }

@Service("stripeGateway")
class StripeGateway implements PaymentGateway { ... }

@Service
class InternationalCheckoutService {
    private final PaymentGateway gateway;
    InternationalCheckoutService(@Qualifier("stripeGateway") PaymentGateway gateway) { this.gateway = gateway; }
}

@Configuration                                   // like a Laravel service provider
class HttpClientsConfig {
    @Bean
    RestClient razorpayClient(@Value("${razorpay.base-url}") String url) {
        return RestClient.builder().baseUrl(url).build();
    }
}
TXT),
            $table('Annotation cheat-sheet', ['Annotation', 'Effect', 'Laravel equivalent'], [
                ['@RestController, @GetMapping', 'HTTP endpoint', 'routes/api.php + controller'],
                ['@Service, @Component', 'Register a bean', 'Container auto-wiring / bind()'],
                ['@Configuration + @Bean', 'Manual bean creation', 'ServiceProvider::register()'],
                ['@Transactional', 'Wrap method in a DB transaction', 'DB::transaction()'],
                ['@Valid, @NotNull', 'Validation', 'Form Request rules'],
                ['@PreAuthorize', 'Authorization', 'Policies / can middleware'],
                ['@Scheduled', 'Run on a schedule', 'Console Kernel schedule'],
                ['@Async', 'Run in another thread', 'ShouldQueue jobs'],
                ['@KafkaListener', 'Consume a Kafka topic', 'Queue worker / Kafka consumer command'],
                ['@Cacheable', 'Cache method result', 'Cache::remember()'],
            ]),
        ]],
        ['id' => 'react', 'title' => 'React: dependency injection with context', 'blocks' => [
            $text('React has no container, but Context gives the same benefit: components ask for a service (an API client, the current user) without creating it, and tests or storybooks provide a fake.'),
            $code('Injecting an API client', 'jsx', <<<'TXT'
const ApiContext = createContext(null);
export const useApi = () => useContext(ApiContext);

root.render(<ApiContext.Provider value={createApiClient('/api')}><App /></ApiContext.Provider>);
// test: render(<ApiContext.Provider value={fakeApi}><OrderList /></ApiContext.Provider>)

function OrderList() {
  const api = useApi();
  const [orders, setOrders] = useState([]);
  useEffect(() => { api.get('/orders').then(setOrders); }, [api]);
  return orders.map(o => <OrderRow key={o.id} order={o} />);
}
TXT),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['register() vs boot()?', 'register only binds into the container; boot runs after all providers are registered and may use any service.'],
                ['bind vs singleton?', 'bind returns a new instance each time; singleton returns the same instance. Do not store request-specific data in singletons under Octane or queue workers.'],
                ['What is a facade?', 'A static-looking proxy (Cache::get) that resolves the real object from the container, so it is still swappable in tests (Cache::shouldReceive).'],
                ['How do annotations work at runtime?', 'The framework reads them with reflection at startup and builds proxies or registrations; the annotation itself contains no logic.'],
            ]),
        ]],
    ],
],

'docker' => [
    'title' => 'Docker for Laravel, Spring Boot, React, MySQL & Kafka',
    'summary' => 'What containers are, images vs containers, writing Dockerfiles for Laravel and Spring Boot, a docker-compose stack with MySQL, Redis and Kafka for local development, and production practices.',
    'example_label' => 'Running example: an e-commerce stack (Laravel API, queue worker, React front end, MySQL, Redis, Kafka)',
    'sections' => [
        ['id' => 'concept', 'title' => 'Containers explained', 'blocks' => [
            $text(
                'A container packages an application with its runtime (PHP 8.3 + extensions, or Java 21), libraries and configuration, and runs it isolated from other processes, sharing the host\'s kernel. "Works on my machine" problems disappear because every developer, CI and production run the same image.',
                'An image is the read-only template, built from a Dockerfile, layer by layer. A container is a running instance of an image. A registry (Docker Hub, AWS ECR, GitHub Container Registry) stores images. docker compose runs several containers together for development; Kubernetes or ECS run them in production.'
            ),
            $table('Virtual machine vs container', ['', 'Virtual machine', 'Container'], [
                ['Includes', 'Full guest OS', 'App + libraries only'],
                ['Start time', 'Minutes', 'Seconds'],
                ['Size', 'GBs', 'MBs'],
                ['Isolation', 'Strong (hypervisor)', 'Process-level (namespaces, cgroups)'],
            ]),
        ]],
        ['id' => 'laravel', 'title' => 'Dockerfile for a Laravel API', 'blocks' => [
            $code('Multi-stage build: dependencies installed in one stage, slim runtime in the next', 'dockerfile', <<<'TXT'
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction

FROM php:8.3-fpm-alpine
RUN docker-php-ext-install pdo_mysql opcache bcmath && apk add --no-cache icu-dev && docker-php-ext-install intl
WORKDIR /var/www
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
TXT),
            $text('Copying composer.json before the rest of the code lets Docker reuse the cached vendor layer when only application code changes, so builds take seconds instead of minutes. Never COPY .env into an image: secrets are injected at runtime as environment variables. Add .env, node_modules, vendor and storage/logs to .dockerignore.'),
        ]],
        ['id' => 'spring-react', 'title' => 'Spring Boot and React images', 'blocks' => [
            $code('Spring Boot', 'dockerfile', <<<'TXT'
FROM eclipse-temurin:21-jdk AS build
WORKDIR /src
COPY . .
RUN ./gradlew bootJar --no-daemon

FROM eclipse-temurin:21-jre-alpine
COPY --from=build /src/build/libs/payments.jar /app.jar
USER 1000
EXPOSE 8080
CMD ["java", "-XX:MaxRAMPercentage=75", "-jar", "/app.jar"]
TXT),
            $code('React: build static files, serve with nginx', 'dockerfile', <<<'TXT'
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:1.27-alpine
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
TXT),
        ]],
        ['id' => 'compose', 'title' => 'docker-compose: the whole stack locally', 'blocks' => [
            $code('docker-compose.yml', 'yaml', <<<'TXT'
services:
  api:
    build: .
    env_file: .env
    depends_on: [mysql, redis, kafka]
  web:
    image: nginx:1.27-alpine
    ports: ["8080:80"]
    volumes: ["./docker/nginx.conf:/etc/nginx/conf.d/default.conf", "./public:/var/www/public"]
  worker:
    build: .
    command: php artisan queue:work redis --queue=payments,emails,default --tries=3
    depends_on: [api]
  scheduler:
    build: .
    command: sh -c "while true; do php artisan schedule:run; sleep 60; done"
  mysql:
    image: mysql:8.0
    environment: { MYSQL_DATABASE: shop, MYSQL_ROOT_PASSWORD: secret }
    volumes: ["mysql-data:/var/lib/mysql"]          # data survives container restarts
  redis:
    image: redis:7-alpine
  kafka:
    image: bitnami/kafka:3.7
    environment:
      KAFKA_CFG_NODE_ID: 1
      KAFKA_CFG_PROCESS_ROLES: broker,controller
      KAFKA_CFG_LISTENERS: PLAINTEXT://:9092,CONTROLLER://:9093
      KAFKA_CFG_CONTROLLER_QUORUM_VOTERS: 1@kafka:9093
      KAFKA_CFG_CONTROLLER_LISTENER_NAMES: CONTROLLER
volumes:
  mysql-data:
TXT),
            $code('Daily commands', 'bash', <<<'TXT'
docker compose up -d --build          # build and start everything
docker compose ps                     # what is running
docker compose logs -f worker         # follow queue worker logs
docker compose exec api php artisan migrate
docker compose exec mysql mysql -uroot -psecret shop
docker compose down                   # stop (add -v to delete volumes, i.e. the database)
TXT),
            $text('Inside the compose network, services reach each other by service name: DB_HOST=mysql, REDIS_HOST=redis, KAFKA_BROKERS=kafka:9092. "localhost" inside a container means that container itself, the most common beginner mistake.'),
        ]],
        ['id' => 'production', 'title' => 'Production practices', 'blocks' => [
            $table('Checklist', ['Practice', 'Why'], [
                ['One process per container (api, worker, scheduler separate)', 'Scale queue workers independently of web traffic during a sale'],
                ['Pin image versions (mysql:8.0.36, not latest)', 'Reproducible builds'],
                ['Run as non-root user', 'Limits damage if the app is compromised'],
                ['Secrets from the platform (Kubernetes secrets, AWS Secrets Manager)', 'Never baked into images'],
                ['Health checks (/up in Laravel 11, /actuator/health in Spring)', 'Orchestrator restarts unhealthy containers'],
                ['Logs to stdout/stderr', 'Collected centrally (CloudWatch, ELK)'],
                ['Managed MySQL/Kafka in production', 'Backups, failover and upgrades handled for you'],
            ]),
            $text('Note for shared hosting (cPanel/GoDaddy): Docker is not available there, so this project deploys with Git + cron. Docker becomes relevant when you move to a VPS, AWS, or Kubernetes.'),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['Image vs container?', 'An image is the immutable template; a container is a running instance with its own writable layer.'],
                ['Why multi-stage builds?', 'Build tools (Composer, JDK, Node) stay in the build stage, so the final image is small and has less attack surface.'],
                ['How do containers talk to each other in compose?', 'Over a shared network using service names as hostnames.'],
                ['Where does MySQL data live?', 'In a named volume; containers are disposable, volumes persist.'],
            ]),
        ]],
    ],
],
];
