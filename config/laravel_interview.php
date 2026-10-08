<?php

// Laravel 5-year interview series, part 1 (Q1–Q25). Answers use ``` fences for code and request-flow diagrams.
$q = [];

$q[] = ['question' => 'Q1. What is Laravel?', 'answer' => <<<'TXT'
Laravel is a PHP web application framework based on the MVC architecture and a service container.

It provides built-in functionality for routing, controllers, middleware, authentication, authorization, database access, Eloquent ORM, validation, Blade templates, queues, events, jobs, notifications, caching, API development, testing and task scheduling.

Laravel follows conventions that let developers build applications faster while keeping a structured architecture.

Basic request flow for GET /products:

```
Browser
   ↓
Web Server
   ↓
public/index.php
   ↓
Laravel Application
   ↓
HTTP Kernel / Middleware
   ↓
Router
   ↓
Controller
   ↓
Service
   ↓
Repository / Model
   ↓
Database
   ↓
View / JSON Response
   ↓
Browser
```

Strong 5-year answer: "Laravel is a modern PHP framework based on MVC and a service-container-driven architecture. It provides components for routing, middleware, authentication, validation, ORM through Eloquent, queues, events, caching and testing. In production I use Laravel not only for CRUD but also for API development, background processing, caching, database optimization and scalable application architecture."
TXT];

$q[] = ['question' => 'Q2. Explain Laravel MVC architecture.', 'answer' => <<<'TXT'
MVC means Model, View, Controller.

Model: responsible for data and database interaction.

```
class Product extends Model
{
    protected $fillable = ['name', 'price', 'category_id'];
}
```

View: responsible for presentation.

```
<h1>{{ $product->name }}</h1>
<p>Price: {{ $product->price }}</p>
```

Controller: coordinates the request.

```
class ProductController extends Controller
{
    public function show(int $id)
    {
        $product = Product::findOrFail($id);

        return view('products.show', compact('product'));
    }
}
```

```
Request → Route → Controller → Model → Database → Model → Controller → View → Response
```

Important interview point: don't say "the model contains all business logic". In a small application it might, but in a large production application responsibilities are separated:

```
Controller
   ↓
Service
   ↓
Repository / Query
   ↓
Model
```
TXT];

$q[] = ['question' => 'Q3. What is the Laravel service container?', 'answer' => <<<'TXT'
A very important 5-year question. The service container manages class dependencies and performs dependency injection.

```
class PaymentService
{
    public function process()
    {
        //
    }
}

class OrderController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}
}
```

Laravel automatically resolves PaymentService from the container when it builds the controller.

Why it is useful: dependency injection, loose coupling, testability, interface-to-implementation binding and centralized dependency management.
TXT];

$q[] = ['question' => 'Q4. What is dependency injection in Laravel?', 'answer' => <<<'TXT'
Dependency injection means a class receives its dependencies instead of creating them internally.

Bad approach: the controller is tightly coupled to PaymentService.

```
class OrderController
{
    public function createOrder()
    {
        $payment = new PaymentService();
        $payment->process();
    }
}
```

Better approach: constructor injection, resolved by the service container.

```
class OrderController
{
    public function __construct(private PaymentService $paymentService) {}

    public function createOrder()
    {
        $this->paymentService->process();
    }
}
```

Interface example:

```
interface PaymentGateway
{
    public function pay(float $amount);
}

class RazorpayGateway implements PaymentGateway
{
    public function pay(float $amount)
    {
        // Razorpay implementation
    }
}

// In a service provider's register():
$this->app->bind(PaymentGateway::class, RazorpayGateway::class);

class PaymentService
{
    public function __construct(private PaymentGateway $gateway) {}
}
```

The service doesn't care whether payment goes through Razorpay, Stripe or another gateway. That is loose coupling, and in tests you can bind a fake gateway.
TXT];

$q[] = ['question' => 'Q5. What is a Laravel service provider?', 'answer' => <<<'TXT'
Service providers are the central place for bootstrapping application services. They have two main methods: register() and boot().

register(): registers services into the container.

```
public function register(): void
{
    $this->app->singleton(PaymentService::class, fn ($app) => new PaymentService());
}
```

boot(): runs after all providers have been registered.

```
public function boot(): void
{
    View::share('appName', config('app.name'));
}
```

Difference: register() is for container bindings and should avoid resolving other services; boot() initializes services and can safely use anything already registered.
TXT];

$q[] = ['question' => 'Q6. What is the difference between register() and boot()?', 'answer' => <<<'TXT'
register() is used for container bindings such as $this->app->bind(...) and $this->app->singleton(...).

```
public function register(): void
{
    $this->app->singleton(PaymentService::class, fn () => new PaymentService());
}
```

boot() is used once every provider has been registered.

```
public function boot(): void
{
    // Catch N+1 queries during development without breaking production.
    Model::preventLazyLoading(! app()->isProduction());
}
```

Interview answer: "register() is primarily used to register bindings and services in the container. boot() runs after all service providers have been registered and is used for initialization logic such as event registration, view sharing, model configuration and macros."
TXT];

$q[] = ['question' => 'Q7. What is middleware in Laravel?', 'answer' => <<<'TXT'
Middleware filters HTTP requests before they reach the controller and can also process the response afterwards.

```
class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->is_admin) {
            abort(403);
        }

        return $next($request);
    }
}
```

```
Request → Middleware → Controller → Response → Middleware → Browser
```

Common middleware: auth, guest, verified, throttle and CSRF verification.
TXT];

$q[] = ['question' => 'Q8. What is the difference between middleware and authorization?', 'answer' => <<<'TXT'
Middleware generally controls whether a request should proceed at all. Authorization decides whether a user may perform a particular action on a particular resource.

Middleware asks: is the user authenticated? Authorization asks: can this authenticated user delete this invoice?

```
$this->authorize('delete', $invoice);
```

This normally uses a Policy (InvoicePolicy::delete). Use middleware for broad route rules and policies for record-level decisions.
TXT];

$q[] = ['question' => 'Q9. What is a Laravel Facade?', 'answer' => <<<'TXT'
A Facade provides a convenient static-looking interface to a service registered in the service container.

```
Cache::put('name', 'Sachin', 3600);
DB::table('users')->get();
Log::info('Order created');
Storage::put('file.txt', $data);
```

Although the call looks static, Laravel resolves the underlying object from the container. Facades are not traditional static classes; they are proxies to container-resolved objects, which is why many of them can be faked in tests, for example Mail::fake() and Queue::fake().
TXT];

$q[] = ['question' => 'Q10. Facade vs dependency injection?', 'answer' => <<<'TXT'
Facade:

```
Cache::get('products');
```

Dependency injection:

```
public function __construct(private CacheManager $cache) {}

$this->cache->get('products');
```

Facades are convenient, short and Laravel-friendly. Dependency injection makes dependencies explicit, is easier to understand from the constructor, is excellent for testing and suits highly decoupled architecture.

Interview answer: "I use facades for simple framework operations, but for important domain services I generally prefer dependency injection because dependencies are explicit and easier to mock and test."
TXT];

$q[] = ['question' => 'Q11. What is the Laravel request lifecycle?', 'answer' => <<<'TXT'
One of the most important questions for a 5-year developer. For GET /api/products:

```
Web Server
    ↓
public/index.php
    ↓
Application Bootstrap
    ↓
Service Providers
    ↓
HTTP Kernel / Middleware Pipeline
    ↓
Router
    ↓
Route Middleware
    ↓
Controller
    ↓
Service
    ↓
Database
    ↓
Response
    ↓
Middleware
    ↓
HTTP Response
```

public/index.php is the entry point; it loads bootstrap/app.php, creates the application and sends the request through the HTTP kernel.

```
$app = require_once __DIR__.'/../bootstrap/app.php';
```

Interview tip: don't just say "the route calls the controller". Walk through web server, index.php, bootstrap, service providers, middleware, router, controller, business logic, database and response.
TXT];

$q[] = ['question' => 'Q12. routes/web.php vs routes/api.php?', 'answer' => <<<'TXT'
web.php is for browser routes and runs the web middleware group: sessions, cookies and CSRF protection.

```
Route::get('/products', fn () => view('products'));
```

api.php is for stateless HTTP APIs. Its routes are prefixed with /api and usually authenticate with tokens (for example Sanctum) instead of sessions. In Laravel 11+ the file is added with php artisan install:api.

```
Route::get('/products', [ProductController::class, 'index']);
```

Typical API routes:

```
GET    /api/products
POST   /api/products
PUT    /api/products/{id}
DELETE /api/products/{id}
```
TXT];

$q[] = ['question' => 'Q13. What is Artisan?', 'answer' => <<<'TXT'
Artisan is Laravel's command-line interface.

```
php artisan migrate
php artisan make:model Product
php artisan make:controller ProductController
php artisan make:migration create_products_table
php artisan queue:work
php artisan optimize
php artisan route:list
```

Custom command:

```
php artisan make:command ImportCompanies

class ImportCompanies extends Command
{
    protected $signature = 'companies:import';

    public function handle(): int
    {
        // import logic
        return self::SUCCESS;
    }
}

php artisan companies:import
```
TXT];

$q[] = ['question' => 'Q14. What is Eloquent ORM?', 'answer' => <<<'TXT'
Eloquent is Laravel's ORM (Object Relational Mapping). Instead of SQL like SELECT * FROM products WHERE id = 10 you write:

```
$product = Product::find(10);

Product::create(['name' => 'Samsung A35', 'price' => 30000]);

$product->update(['price' => 28000]);

$product->delete();
```
TXT];

$q[] = ['question' => 'Q15. Eloquent vs Query Builder?', 'answer' => <<<'TXT'
```
// Eloquent
Product::where('price', '>', 10000)->get();

// Query Builder
DB::table('products')->where('price', '>', 10000)->get();
```

Eloquent advantages: models, relationships, attribute casting, accessors, mutators, events, scopes and convenient syntax.

Query Builder advantages: lightweight, good for complex queries and reporting, and no model hydration overhead.

Interview answer: "I prefer Eloquent when relationships and model behavior matter. For heavy reporting, aggregations or highly optimized queries, Query Builder or raw SQL can be more appropriate."
TXT];

$q[] = ['question' => 'Q16. What is mass assignment?', 'answer' => <<<'TXT'
Passing a whole request array into create() or update().

```
User::create($request->all());
```

A malicious user might submit:

```
{ "name": "Sachin", "email": "test@test.com", "is_admin": true }
```

If is_admin is mass assignable, the user could escalate privileges. Protect models with $fillable (allow-list) or $guarded (block-list):

```
protected $fillable = ['name', 'email'];
```

Best practice: use an explicit $fillable on important models and pass only validated data, for example User::create($request->validated()).
TXT];

$q[] = ['question' => 'Q17. $fillable vs $guarded?', 'answer' => <<<'TXT'
$fillable lists the fields that can be mass assigned. $guarded lists the fields that cannot.

```
protected $fillable = ['name', 'email', 'phone'];   // allow-list

protected $guarded = ['is_admin'];                  // block-list
```

For security-sensitive applications explicit allow-listing ($fillable) is preferable: a new column is protected by default until you deliberately allow it.
TXT];

$q[] = ['question' => 'Q18. What are Laravel migrations?', 'answer' => <<<'TXT'
Migrations are version-controlled database schema definitions.

```
php artisan make:migration create_products_table

Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('price', 10, 2);
    $table->timestamps();
});

php artisan migrate
php artisan migrate:rollback
php artisan migrate:refresh
php artisan migrate:fresh
```

rollback undoes the latest migration batch. refresh rolls back every migration and runs them again. fresh drops all tables and migrates from scratch.

Warning: refresh and fresh delete data. Never run them against production or a shared database; use new forward-only migrations there.
TXT];

$q[] = ['question' => 'Q19. What are Laravel seeders?', 'answer' => <<<'TXT'
Seeders insert initial or test data. Migrations define structure; seeders define data.

```
php artisan make:seeder ProductSeeder

Product::create(['name' => 'Samsung A35', 'price' => 30000]);

php artisan db:seed
php artisan db:seed --class=ProductSeeder
```

Production tip: write seeders with updateOrCreate() so running them twice does not create duplicates.
TXT];

$q[] = ['question' => 'Q20. What are Laravel factories?', 'answer' => <<<'TXT'
Factories generate fake data for tests and development.

```
php artisan make:factory ProductFactory

public function definition(): array
{
    return [
        'name' => fake()->words(3, true),
        'price' => fake()->randomFloat(2, 100, 50000),
    ];
}

Product::factory()->count(100)->create();
```

Useful for testing, local development, load testing and database seeding.
TXT];

$q[] = ['question' => 'Q21. What are Laravel model relationships?', 'answer' => <<<'TXT'
```
// One-to-one: $user->profile
public function profile() { return $this->hasOne(Profile::class); }

// One-to-many: $user->orders
public function orders() { return $this->hasMany(Order::class); }

// Many-to-many: $user->roles
public function roles() { return $this->belongsToMany(Role::class); }

// Belongs-to: $order->user
public function user() { return $this->belongsTo(User::class); }
```

Laravel also supports hasManyThrough, hasOneThrough and polymorphic relationships (morphTo, morphMany, morphToMany).
TXT];

$q[] = ['question' => 'Q22. What is eager loading?', 'answer' => <<<'TXT'
```
$orders = Order::all();

foreach ($orders as $order) {
    echo $order->user->name;   // one extra query per order
}
```

This creates the N+1 query problem. Eager load instead:

```
$orders = Order::with('user')->get();
```

```
Without eager loading: 1 query for orders + 100 queries for users = 101 queries
With eager loading:    1 query for orders + 1 query for users    = 2 queries
```

This is extremely important in production Laravel applications.
TXT];

$q[] = ['question' => 'Q23. What is lazy loading?', 'answer' => <<<'TXT'
Lazy loading means a relationship is queried only when it is first accessed.

```
$order = Order::find(1);

echo $order->user->name;   // the user query runs here
```

Advantage: simple and convenient for a single record. Disadvantage: inside loops it silently produces N+1 queries. For lists, prefer Order::with('user')->get(), and enable Model::preventLazyLoading() in development to catch mistakes.
TXT];

$q[] = ['question' => 'Q24. What is the N+1 query problem?', 'answer' => <<<'TXT'
Suppose there are 1,000 products:

```
$products = Product::all();

foreach ($products as $product) {
    echo $product->category->name;
}
```

```
1 product query + 1,000 category queries = 1,001 queries
```

Solution:

```
$products = Product::with('category')->get();   // about 2 queries
```

How to identify it: Laravel Debugbar, query logging, Telescope, application performance monitoring, database monitoring, and Model::preventLazyLoading() during development.
TXT];

$q[] = ['question' => 'Q25. How would you optimize a slow Laravel application?', 'answer' => <<<'TXT'
A very common 5-year scenario. Don't immediately say "use Redis"; first identify the bottleneck.

Step 1, database: check slow queries, missing indexes, N+1 queries, large SELECT *, unnecessary joins and poor pagination.

```
// Only one row is needed:
User::where('email', $email)->first();

// Add an index:
$table->index('email');
```

Step 2, Eloquent: eager load and select only required columns.

```
Order::with(['user', 'items.product'])->get();

User::select(['id', 'name', 'email'])->get();
```

Step 3, cache frequently requested data (Redis in distributed, high-traffic setups).

```
$products = Cache::remember('products', 3600, fn () => Product::latest()->get());
```

Step 4, queues: don't do slow work during the HTTP request.

```
User places order → save order → return response
                                   ↓ queue: email, invoice, SMS, notifications

SendOrderConfirmation::dispatch($order);
```

Step 5, pagination and chunking instead of Product::all() on millions of rows.

```
Product::paginate(50);

Product::chunkById(1000, function ($products) {
    //
});
```

Step 6, HTTP and API: pagination, caching, compression, CDN, HTTP caching and lean JSON responses.

Step 7, infrastructure for high traffic:

```
Load Balancer
      ↓
Laravel Server 1 / Server 2 / Server 3
      ↓
Redis
      ↓
Database (with read replicas where appropriate)
```

Use PHP OPcache, Redis, queue workers under Supervisor or systemd, Horizon where applicable, database indexes, a CDN and horizontal scaling.

Strong 5-year answer: "I don't optimize Laravel blindly. First I identify whether the bottleneck is CPU, database, memory, network or external services. For database issues I inspect slow queries, indexes and N+1 problems. For repeated reads I add caching, usually Redis in a distributed environment. For expensive work I use queues. I also optimize pagination, eager loading, selected columns and API payloads, and at infrastructure level I use OPcache, multiple application instances behind a load balancer and appropriate database scaling."
TXT];

// Existing practice questions in other PHP & Laravel topics now show the matching answer.
$byNumber = fn (int $n) => $q[$n - 1]['answer'];
$answers = [
    'Explain the Laravel MVC request lifecycle.' => $byNumber(11),
    'What belongs in routes/web.php?' => $byNumber(12),
    'What belongs in routes/api.php?' => $byNumber(12),
    'Where should business logic live?' => $byNumber(2),
    'What is an Eloquent model?' => $byNumber(14),
    'What is dependency injection?' => $byNumber(4),
    'How does Laravel resolve dependencies from its service container?' => $byNumber(3),
    'What is constructor injection?' => $byNumber(4),
    'When should you bind an interface to an implementation?' => $byNumber(4),
    'What is a Laravel migration?' => $byNumber(18),
    'How do you roll back a migration?' => $byNumber(18),
    'How does route middleware enforce a role?' => $byNumber(7),
    'Authentication versus authorization?' => $byNumber(8),
    'When should you use a policy instead of middleware?' => $byNumber(8),
];

return ['questions' => $q, 'answers' => $answers];
