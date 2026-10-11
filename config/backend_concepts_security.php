<?php

// Backend concepts, part 1: identity and request handling. Rendered by learning.sql-case-studies (sections/blocks).
$code = fn (string $caption, string $language, string $source) => ['type' => 'code', 'caption' => $caption, 'language' => $language, 'source' => trim($source)];
$table = fn (string $caption, array $columns, array $rows) => ['type' => 'table', 'caption' => $caption, 'columns' => $columns, 'rows' => $rows];
$text = fn (string ...$paragraphs) => ['type' => 'text', 'paragraphs' => $paragraphs];
$flow = fn (string $caption, array $steps) => ['type' => 'flow', 'caption' => $caption, 'steps' => $steps];
$ask = fn (array $items) => ['type' => 'questions', 'items' => $items];

return [
'tokens-authentication-authorization' => [
    'title' => 'Tokens, Authentication, Authorization & Roles',
    'summary' => 'How a system knows who is calling (authentication), what they may do (authorization), how tokens carry identity, and how roles and permissions are modelled. Examples from net banking, an e-commerce store and a payment gateway in Laravel, Spring Boot, React and MySQL.',
    'example_label' => 'Running examples: a bank customer transferring money, a shopper placing an order, a merchant calling a payment API',
    'sections' => [
        ['id' => 'concept', 'title' => 'Authentication vs authorization', 'blocks' => [
            $text(
                'Authentication answers "who are you?". The caller proves identity with something they know (password, PIN), something they have (OTP on a phone, hardware key) or something they are (fingerprint). Once proven, the server issues a session or a token so the caller does not send the password on every request.',
                'Authorization answers "what are you allowed to do?". It runs after authentication, on every request, and decides whether this identity may perform this action on this record.',
                'The two fail differently. A failed authentication returns 401 Unauthorized (log in again). A failed authorization returns 403 Forbidden (you are known but not allowed), or 404 when you do not want to reveal that the record exists.'
            ),
            $table('The same request seen by both checks', ['Step', 'Net banking', 'E-commerce', 'Payment gateway API'], [
                ['Authentication', 'Customer ID + password + OTP', 'Email + password, or Google login', 'Merchant API key / OAuth client credentials'],
                ['Proof carried afterwards', 'Session cookie (web) or access token (mobile app)', 'Session cookie', 'Bearer token or HMAC-signed request'],
                ['Authorization question', 'Is account 1234 owned by this customer and is the amount within the daily limit?', 'Is order 187 placed by this shopper?', 'Does this key have the refunds:write scope and belong to the merchant that owns payment pay_9X?'],
                ['Failure response', '403 + "Limit exceeded"', '404 (hide other people\'s orders)', '403 + error code INSUFFICIENT_SCOPE'],
            ]),
            $flow('Every protected request', ['Read credential (cookie / Authorization header)', 'Authenticate: resolve the user or client', 'Authorize: role, permission, ownership, limits', 'Run business logic', 'Audit-log sensitive actions']),
        ]],
        ['id' => 'tokens', 'title' => 'What a token is and the main token types', 'blocks' => [
            $text(
                'A token is a string that represents a proven identity for a limited time. The client stores it and sends it with each request, usually as "Authorization: Bearer <token>". The server checks it instead of asking for the password again.',
                'There are two families. Opaque tokens are random strings; the server looks them up in a database or cache to find the user (Laravel Sanctum personal access tokens, OAuth tokens stored by Passport). Self-contained tokens, mainly JWT (JSON Web Token), carry the user id, roles and expiry inside the token itself, signed by the server, so any service holding the key can verify them without a database lookup.'
            ),
            $table('Choosing a token type', ['Type', 'How it is verified', 'Revocation', 'Typical use'], [
                ['Session id cookie', 'Lookup in session store', 'Delete the session', 'Server-rendered web apps, Laravel Blade, admin panels'],
                ['Opaque token (Sanctum)', 'Hash lookup in personal_access_tokens', 'Delete the row, instant', 'Mobile apps and SPAs talking to one Laravel API'],
                ['JWT access token', 'Verify signature + expiry, no DB', 'Hard: valid until it expires, so keep it short (5–15 min)', 'Microservices: API gateway issues, many services verify'],
                ['Refresh token', 'DB lookup, rotated on each use', 'Delete or mark used', 'Getting a new short access token without re-login'],
                ['API key', 'Hash lookup', 'Rotate the key', 'Server-to-server: merchant calling a payment gateway'],
            ]),
            $code('Anatomy of a JWT (header.payload.signature, each part base64url-encoded)', 'json', <<<'TXT'
// header
{ "alg": "RS256", "typ": "JWT", "kid": "2026-key-1" }
// payload (claims) - readable by anyone, so never put secrets here
{ "sub": "customer-42", "roles": ["CUSTOMER"], "scope": "accounts:read transfers:write",
  "iat": 1791700000, "exp": 1791700900, "iss": "https://auth.bank.example" }
// signature = RSA-SHA256(header + "." + payload, private key of the auth server)
TXT),
            $text('The payload is only encoded, not encrypted. Anyone can decode it, but nobody can change it without breaking the signature. That is why a JWT may contain a user id and roles but never a password, card number or Aadhaar number.'),
        ]],
        ['id' => 'laravel', 'title' => 'Laravel: login, tokens and protecting routes', 'blocks' => [
            $code('Sanctum token login for a mobile banking app', 'php', <<<'TXT'
// routes/api.php
Route::post('/login', LoginController::class)->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/transfers', [TransferController::class, 'store'])->middleware(['ability:transfers:write', 'otp.verified']);
});

// LoginController
public function __invoke(Request $request)
{
    $data = $request->validate(['customer_id' => 'required', 'password' => 'required', 'device' => 'required|max:100']);
    $user = User::where('customer_id', $data['customer_id'])->first();

    if (! $user || ! Hash::check($data['password'], $user->password)) {
        throw ValidationException::withMessages(['customer_id' => 'Invalid credentials.']);   // same message for both cases
    }

    $token = $user->createToken($data['device'], ['accounts:read', 'transfers:write'], now()->addMinutes(30));
    return ['token' => $token->plainTextToken, 'expires_in' => 1800];
}
TXT),
            $text(
                'Sanctum stores only a SHA-256 hash of the token in personal_access_tokens, so a leaked database does not expose working tokens. Abilities (scopes) limit what a token can do: a read-only token issued to a budgeting app cannot move money even though it belongs to the same customer.',
                'Web pages in the same Laravel app do not need tokens at all: the auth middleware uses the session cookie. Use tokens only for clients that are not your own Blade pages: mobile apps, SPAs on another domain, partners.'
            ),
            $code('Ownership check with a policy (authorization)', 'php', <<<'TXT'
class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $account->customer_id === $user->customer_id;
    }
}

public function show(Account $account)
{
    $this->authorize('view', $account);   // 403 if it is someone else's account
    return new AccountResource($account->load('recentTransactions'));
}
TXT),
        ]],
        ['id' => 'spring', 'title' => 'Spring Boot: Spring Security with JWT', 'blocks' => [
            $code('Resource server that validates JWTs and checks roles', 'java', <<<'TXT'
@Configuration
@EnableMethodSecurity
public class SecurityConfig {
    @Bean
    SecurityFilterChain api(HttpSecurity http) throws Exception {
        return http
            .csrf(csrf -> csrf.disable())                         // stateless API, no cookies
            .sessionManagement(s -> s.sessionCreationPolicy(SessionCreationPolicy.STATELESS))
            .authorizeHttpRequests(auth -> auth
                .requestMatchers("/actuator/health", "/api/login").permitAll()
                .requestMatchers(HttpMethod.POST, "/api/loans/*/approve").hasRole("BRANCH_MANAGER")
                .anyRequest().authenticated())
            .oauth2ResourceServer(o -> o.jwt(Customizer.withDefaults()))   // verifies signature + expiry
            .build();
    }
}

@RestController
@RequestMapping("/api/accounts")
class AccountController {
    @GetMapping("/{id}")
    @PreAuthorize("@accountGuard.owns(authentication, #id)")      // ownership, not just role
    public AccountDto get(@PathVariable Long id) { return service.find(id); }
}
TXT),
            $text('Spring Security is a chain of servlet filters. The JWT filter authenticates and stores an Authentication object in the SecurityContext; @PreAuthorize then authorizes at method level. The idea is the same as Laravel\'s auth middleware followed by a policy.'),
        ]],
        ['id' => 'react', 'title' => 'React: storing and sending tokens safely', 'blocks' => [
            $code('API client with an in-memory access token and refresh on 401', 'js', <<<'TXT'
let accessToken = null;                       // kept in memory, not localStorage

export async function api(path, options = {}) {
  const send = () => fetch(`/api${path}`, {
    ...options,
    credentials: 'include',                   // sends the HttpOnly refresh cookie
    headers: { 'Content-Type': 'application/json', ...(accessToken && { Authorization: `Bearer ${accessToken}` }) },
  });
  let res = await send();
  if (res.status === 401) {                   // access token expired: rotate once, then retry
    const r = await fetch('/api/auth/refresh', { method: 'POST', credentials: 'include' });
    if (!r.ok) { window.location.assign('/login'); return; }
    accessToken = (await r.json()).access_token;
    res = await send();
  }
  return res;
}

// Route guard: UX only, the server still authorizes every call
function RequireRole({ role, children }) {
  const { user } = useAuth();
  return user?.roles.includes(role) ? children : <Navigate to="/forbidden" />;
}
TXT),
            $text('Anything in localStorage can be read by any script on the page, so one XSS bug leaks the token. Keeping the short-lived access token in memory and the refresh token in an HttpOnly, Secure, SameSite cookie limits that damage. Hiding a button in React is never security; the API must check again.'),
        ]],
        ['id' => 'roles', 'title' => 'Roles and permissions (RBAC) with the MySQL design', 'blocks' => [
            $text(
                'Role-Based Access Control groups permissions into roles and assigns roles to users. Code checks permissions ("loans.approve"), not role names, so a new role such as "Deputy Manager" needs no code change, only rows in the database.',
                'Real systems add conditions roles cannot express: a branch manager may approve loans only for their own branch and only up to Rs 25 lakh. Those rules live in policies (attribute-based checks) on top of RBAC.'
            ),
            ['type' => 'schema', 'name' => 'roles / permissions / role_user / permission_role', 'columns' => [['roles.id, roles.name', 'CUSTOMER, TELLER, BRANCH_MANAGER, AUDITOR'], ['permissions.id, permissions.name', 'accounts.view, transfers.create, loans.approve, reports.export'], ['permission_role(role_id, permission_id)', 'many-to-many, composite PK'], ['role_user(user_id, role_id, branch_id NULL)', 'role scoped to a branch when needed']], 'note' => 'Packages such as spatie/laravel-permission create exactly these tables.'],
            $code('Checking permissions plus a limit', 'php', <<<'TXT'
// LoanPolicy
public function approve(User $user, Loan $loan): Response
{
    if (! $user->hasPermissionTo('loans.approve')) return Response::deny('Not allowed to approve loans.');
    if ($loan->branch_id !== $user->branch_id) return Response::denyAsNotFound();
    if ($loan->amount > $user->approval_limit) return Response::deny('Above your approval limit; escalate to regional office.');
    return Response::allow();
}
TXT),
            $table('Roles in the three example systems', ['System', 'Roles', 'Rule that roles alone cannot express'], [
                ['Banking', 'Customer, Teller, Branch Manager, Auditor (read-only)', 'Maker-checker: the person who creates a payment cannot approve it'],
                ['E-commerce', 'Shopper, Seller, Support agent, Admin', 'A seller edits only their own products'],
                ['Payment gateway', 'Merchant owner, Developer, Finance, Read-only', 'Refunds above Rs 50,000 need the owner role and 2FA'],
            ]),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['Session or JWT for a banking web app?', 'Sessions (or opaque tokens): they can be revoked instantly on logout, password change or fraud alerts. JWTs suit distributed services where a central lookup is expensive, but they need very short expiry plus refresh-token rotation.'],
                ['How do you log out a JWT user?', 'Delete the refresh token so no new access token can be issued, and keep access tokens short. For immediate revocation keep a deny-list of token ids (jti) in Redis until they expire.'],
                ['What is IDOR?', 'Insecure Direct Object Reference: an authenticated user changes /orders/187 to /orders/188 and sees another person\'s order. Fix it with ownership checks (policies or scoped queries) on every record.'],
                ['Where should the role check happen in a React + Laravel app?', 'In Laravel (middleware, policies). React checks only decide what to show.'],
            ]),
        ]],
    ],
],

'middleware-filters' => [
    'title' => 'Middleware, Filters & Interceptors',
    'summary' => 'Middleware is code that wraps every request before and after the controller. This lesson shows the request pipeline, writing middleware in Laravel, filters and interceptors in Spring Boot, the React and Kafka equivalents, and real banking, e-commerce and payment middleware.',
    'example_label' => 'Running examples: request id logging, rate limiting transfers, idempotent payments, maintenance windows',
    'sections' => [
        ['id' => 'pipeline', 'title' => 'The request pipeline', 'blocks' => [
            $text(
                'A web request does not go straight to a controller. It passes through a stack of layers, each of which can inspect or change the request, stop it with its own response, or let it continue. On the way back the same layers can change the response. The pattern is called a pipeline or "onion".',
                'Middleware is the right place for cross-cutting concerns: rules that apply to many routes and have nothing to do with a particular business action. Examples: authentication, CSRF, rate limits, CORS, security headers, request ids, locale, maintenance mode.'
            ),
            $flow('Laravel request through middleware', ['Global: TrustProxies, maintenance check, trim strings', 'Group web: cookies, session, CSRF', 'Route: auth, throttle, can:', 'Controller', 'Response back out through the same layers']),
            $table('What belongs where', ['Concern', 'Middleware?', 'Why'], [
                ['Is the user logged in?', 'Yes', 'Same for every protected route'],
                ['Max 5 transfers per minute', 'Yes (throttle)', 'Generic, keyed by user'],
                ['Can this user cancel order 187?', 'No: policy', 'Needs the specific record'],
                ['Calculate GST on an order', 'No: service', 'Business logic'],
                ['Add X-Request-Id to logs and response', 'Yes', 'Applies to every request'],
            ]),
        ]],
        ['id' => 'laravel', 'title' => 'Laravel middleware in practice', 'blocks' => [
            $code('Request id + timing middleware (observability for every request)', 'php', <<<'TXT'
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->header('X-Request-Id') ?: (string) Str::uuid();
        Log::withContext(['request_id' => $id, 'user_id' => $request->user()?->id]);   // every log line now carries it
        $started = microtime(true);

        $response = $next($request);                     // controller runs here

        $response->headers->set('X-Request-Id', $id);
        $response->headers->set('Server-Timing', 'app;dur=' . round((microtime(true) - $started) * 1000));
        return $response;
    }
}
TXT),
            $code('Rate limiting money transfers per customer', 'php', <<<'TXT'
// AppServiceProvider::boot()
RateLimiter::for('transfers', fn (Request $r) => [
    Limit::perMinute(5)->by($r->user()->id),
    Limit::perDay(50)->by($r->user()->id)->response(fn () => response()->json(['message' => 'Daily transfer attempts exceeded.'], 429)),
]);

Route::post('/transfers', [TransferController::class, 'store'])->middleware(['auth:sanctum', 'throttle:transfers']);
TXT),
            $code('Payment idempotency: a retried request must not charge twice', 'php', <<<'TXT'
class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key') ?? abort(400, 'Idempotency-Key header required');
        $cacheKey = "idem:{$request->user()->id}:{$key}";

        return Cache::lock($cacheKey . ':lock', 10)->block(5, function () use ($cacheKey, $request, $next) {
            if ($saved = Cache::get($cacheKey)) {
                return response($saved['body'], $saved['status'])->header('Idempotent-Replay', 'true');
            }
            $response = $next($request);
            Cache::put($cacheKey, ['body' => $response->getContent(), 'status' => $response->getStatusCode()], now()->addDay());
            return $response;
        });
    }
}
TXT),
            $text('The lock matters: a mobile app on a bad network may fire the same request twice within milliseconds. Without the lock both copies pass the cache check before either has saved a result.'),
        ]],
        ['id' => 'spring', 'title' => 'Spring Boot: filters, interceptors and AOP', 'blocks' => [
            $text('Spring has three layers that play the role of middleware. Servlet filters wrap the whole request, including Spring Security. HandlerInterceptors run around controller methods and know which handler was chosen. AOP aspects (@Around) wrap any bean method, including services, which is how @Transactional and @Cacheable work.'),
            $code('A filter and an interceptor', 'java', <<<'TXT'
@Component
@Order(1)
class RequestIdFilter extends OncePerRequestFilter {
    @Override
    protected void doFilterInternal(HttpServletRequest req, HttpServletResponse res, FilterChain chain)
            throws ServletException, IOException {
        String id = Optional.ofNullable(req.getHeader("X-Request-Id")).orElse(UUID.randomUUID().toString());
        MDC.put("requestId", id);                 // appears in every log line
        res.setHeader("X-Request-Id", id);
        try { chain.doFilter(req, res); } finally { MDC.clear(); }
    }
}

@Component
class MaintenanceInterceptor implements HandlerInterceptor {
    @Override
    public boolean preHandle(HttpServletRequest req, HttpServletResponse res, Object handler) throws IOException {
        if (flags.isOff("upi") && req.getRequestURI().startsWith("/api/upi")) {
            res.sendError(503, "UPI is under maintenance until 02:00");
            return false;                         // stop: controller never runs
        }
        return true;
    }
}
TXT),
        ]],
        ['id' => 'react-kafka', 'title' => 'The same idea in React and Kafka', 'blocks' => [
            $text(
                'React has no server middleware, but the pattern appears in two places. Route guards wrap pages (redirect to login if there is no user). HTTP interceptors (an axios interceptor or a fetch wrapper) add the token and request id to every call and handle 401 centrally.',
                'Kafka clients have ProducerInterceptor and ConsumerInterceptor. Teams use them to stamp every message with a trace id and the producing service, or to count messages for monitoring, without touching each producer call.'
            ),
            $code('axios interceptors', 'js', <<<'TXT'
api.interceptors.request.use(config => {
  config.headers['X-Request-Id'] = crypto.randomUUID();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
api.interceptors.response.use(r => r, error => {
  if (error.response?.status === 401) window.location.assign('/login');
  return Promise.reject(error);
});
TXT),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['Middleware vs policy?', 'Middleware is generic and runs before the controller without knowing the specific record; a policy decides about one model instance (this order, this account).'],
                ['What is terminable middleware?', 'A terminate() method that runs after the response is sent to the browser, used for metrics or logging that should not slow the user.'],
                ['Filter vs interceptor in Spring?', 'Filters are servlet-level and run for every request, including static and security; interceptors run inside Spring MVC around a resolved handler.'],
                ['Why does order matter?', 'Session must start before authentication and authentication before authorization; a rate limiter keyed by user must run after auth.'],
            ]),
        ]],
    ],
],

'sessions-cookies' => [
    'title' => 'Sessions & Cookies',
    'summary' => 'How stateless HTTP remembers a logged-in user: cookies on the browser, sessions on the server, the security flags that protect them, session storage when you have many servers, and how Spring Boot and React handle the same problem.',
    'example_label' => 'Running examples: guest cart that survives login, banking idle timeout, remember-me',
    'sections' => [
        ['id' => 'basics', 'title' => 'Cookies and sessions explained', 'blocks' => [
            $text(
                'HTTP is stateless: the server forgets you after each response. A cookie is a small name=value pair the server asks the browser to store (Set-Cookie header); the browser sends it back on every later request to that site (Cookie header).',
                'A session keeps the data on the server and puts only a random id in the cookie. The browser holds laravel_session=Abc; the server holds Abc => {user_id: 42, cart_id: 9}. Users cannot read or edit server-side data, and large data never travels with each request.'
            ),
            $code('What travels over the wire', 'http', <<<'TXT'
POST /login HTTP/1.1                      (credentials checked)

HTTP/1.1 302 Found
Set-Cookie: laravel_session=eyJpdiI6...; Path=/; Secure; HttpOnly; SameSite=Lax; Max-Age=7200

GET /orders HTTP/1.1
Cookie: laravel_session=eyJpdiI6...       (browser sends it automatically)
TXT),
            $table('Cookie security flags', ['Flag', 'Effect', 'Protects against'], [
                ['Secure', 'Sent only over HTTPS', 'Theft on public Wi-Fi'],
                ['HttpOnly', 'JavaScript cannot read it', 'XSS stealing the session'],
                ['SameSite=Lax/Strict', 'Not sent on cross-site POSTs (Strict: no cross-site at all)', 'CSRF'],
                ['Max-Age / Expires', 'When the browser deletes it', 'Sessions living forever on shared PCs'],
                ['Domain / Path', 'Which hosts and paths receive it', 'Leaking to other subdomains'],
            ]),
        ]],
        ['id' => 'laravel', 'title' => 'Sessions in Laravel', 'blocks' => [
            $code('Guest cart merged at login, then session id regenerated', 'php', <<<'TXT'
// Adding to cart as a guest
$cart = session('cart', []);
$cart[$product->id] = ($cart[$product->id] ?? 0) + 1;
session(['cart' => $cart]);

// After successful login
public function authenticated(Request $request, User $user)
{
    foreach (session()->pull('cart', []) as $productId => $qty) {
        $user->cartItems()->updateOrCreate(['product_id' => $productId], ['quantity' => DB::raw("quantity + {$qty}")]);
    }
    $request->session()->regenerate();      // new id: blocks session fixation attacks
}

// Logout
Auth::logout();
$request->session()->invalidate();
$request->session()->regenerateToken();     // new CSRF token
TXT),
            $code('Banking settings in config/session.php (via .env)', 'bash', <<<'TXT'
SESSION_DRIVER=redis          # shared by every app server
SESSION_LIFETIME=10           # minutes idle before logout
SESSION_EXPIRE_ON_CLOSE=true
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
TXT),
            $text('Flash data (session()->flash("status", "Order placed")) lives for exactly one more request, which is how "saved successfully" messages survive a redirect. Remember-me uses a separate long-lived cookie holding a token that is checked against users.remember_token; turn it off for banking.'),
        ]],
        ['id' => 'scale', 'title' => 'Sessions with many servers', 'blocks' => [
            $text('With the default file driver each server keeps its own session files, so a load balancer that sends the next request to another server logs the user out. Fix it with a shared store (Redis, database, Memcached) or with sticky sessions. Shared storage is preferred because sticky sessions break when a server is removed during a deployment.'),
            $table('Session drivers', ['Driver', 'Speed', 'Multi-server', 'Notes'], [
                ['file', 'Good', 'No', 'Default; fine for one server or shared hosting'],
                ['cookie', 'Good', 'Yes', 'Whole session in an encrypted cookie, 4 KB max, cannot be revoked server-side'],
                ['database', 'OK', 'Yes', 'Easy to list and delete a user\'s sessions ("log out all devices")'],
                ['redis', 'Excellent', 'Yes', 'Usual production choice'],
            ]),
            $code('Spring Boot: the same with Spring Session + Redis', 'java', <<<'TXT'
// build.gradle: implementation 'org.springframework.session:spring-session-data-redis'
// application.yml
// spring.session.store-type: redis
// server.servlet.session.timeout: 10m
// server.servlet.session.cookie: { http-only: true, secure: true, same-site: strict }

@GetMapping("/cart")
public Cart cart(HttpSession session) {
    return (Cart) Optional.ofNullable(session.getAttribute("cart")).orElseGet(Cart::new);
}
TXT),
        ]],
        ['id' => 'react', 'title' => 'React SPA: cookies vs storage', 'blocks' => [
            $text('A React app on the same site as the Laravel API can simply use the session cookie (Sanctum SPA mode): call /sanctum/csrf-cookie once, then log in, and every fetch with credentials: "include" is authenticated. This is safer than tokens in localStorage because the cookie is HttpOnly.'),
            $code('Sanctum SPA login from React', 'js', <<<'TXT'
await fetch('/sanctum/csrf-cookie', { credentials: 'include' });   // sets XSRF-TOKEN cookie
await fetch('/login', {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(getCookie('XSRF-TOKEN')) },
  body: JSON.stringify({ email, password }),
});
const me = await (await fetch('/api/user', { credentials: 'include' })).json();
TXT),
            $table('Where to keep client state', ['Data', 'Where'], [
                ['Session / auth', 'HttpOnly cookie set by the server'],
                ['Theme, language', 'localStorage'],
                ['Form draft', 'sessionStorage or React state'],
                ['Card number, password', 'Nowhere on the client; send once over HTTPS'],
            ]),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['Session vs cookie?', 'A cookie is stored in the browser; a session is stored on the server and identified by a cookie. Cookies can be read or edited by the user unless encrypted/signed; sessions cannot.'],
                ['What is session fixation?', 'An attacker gets the victim to use a session id the attacker knows, then waits for the login. Regenerating the id at login defeats it.'],
                ['Why is the user logged out randomly behind a load balancer?', 'File sessions on separate servers; move to Redis or database sessions.'],
                ['How do you implement "log out from all devices"?', 'Use database/Redis sessions with user_id and delete them, delete all API tokens, and rotate remember_token.'],
            ]),
        ]],
    ],
],

'exceptions' => [
    'title' => 'Exception Handling',
    'summary' => 'What exceptions are, checked vs unchecked, designing domain exceptions, central handlers in Laravel and Spring Boot, database rollbacks, error boundaries in React, retries and dead-letter topics in Kafka, and what to log.',
    'example_label' => 'Running examples: insufficient balance, payment gateway timeout, out-of-stock at checkout',
    'sections' => [
        ['id' => 'concept', 'title' => 'What an exception is and how to classify it', 'blocks' => [
            $text(
                'An exception is an object that represents a failure and interrupts normal flow. It travels up the call stack until code that knows how to handle it catches it. If nothing catches it, the framework\'s global handler turns it into an error response.',
                'Classify every failure before deciding how to handle it. Business rule violations (insufficient balance, coupon expired) are expected: return a clear 4xx message, do not wake anyone up. Transient technical failures (gateway timeout, deadlock) may succeed if retried. Bugs and infrastructure failures (null pointer, database down) need logs, alerts and a generic 5xx message.'
            ),
            $table('Failure types', ['Example', 'Type', 'Response', 'Log/alert', 'Retry?'], [
                ['Balance Rs 500, transfer Rs 2,000', 'Business', '422 INSUFFICIENT_BALANCE', 'Info', 'No'],
                ['Product went out of stock during checkout', 'Business / concurrency', '409 OUT_OF_STOCK', 'Info', 'No'],
                ['Razorpay timed out', 'Transient', '503 or queue a retry', 'Warning', 'Yes, with backoff and idempotency key'],
                ['MySQL deadlock 1213', 'Transient', 'Retry the transaction', 'Warning', 'Yes, 2–3 times'],
                ['Undefined index in code', 'Bug', '500 generic', 'Error + alert', 'No'],
            ]),
        ]],
        ['id' => 'laravel', 'title' => 'Laravel: domain exceptions, transactions and the handler', 'blocks' => [
            $code('Transfer with rollback on any failure', 'php', <<<'TXT'
class TransferService
{
    public function transfer(Account $from, Account $to, int $amountPaise): Transfer
    {
        return DB::transaction(function () use ($from, $to, $amountPaise) {
            // lock both rows in a fixed order to avoid deadlocks
            [$a, $b] = $from->id < $to->id ? [$from, $to] : [$to, $from];
            Account::whereIn('id', [$a->id, $b->id])->lockForUpdate()->get();
            $from->refresh();

            if ($from->balance_paise < $amountPaise) {
                throw new InsufficientBalanceException($from, $amountPaise);   // transaction rolls back
            }
            $from->decrement('balance_paise', $amountPaise);
            $to->increment('balance_paise', $amountPaise);
            return Transfer::create(['from_id' => $from->id, 'to_id' => $to->id, 'amount_paise' => $amountPaise]);
        }, attempts: 3);                                                   // retries deadlocks
    }
}
TXT),
            $code('Domain exception that renders itself', 'php', <<<'TXT'
class InsufficientBalanceException extends DomainException
{
    public function __construct(public Account $account, public int $requested)
    {
        parent::__construct('Insufficient balance.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'code' => 'INSUFFICIENT_BALANCE', 'message' => $this->getMessage()], 422);
    }

    public function report(): bool { return false; }       // expected; do not send to Sentry
}

// app/Exceptions/Handler.php (Laravel 9/10)
public function register(): void
{
    $this->reportable(fn (GatewayTimeoutException $e) => Log::channel('payments')->warning('gateway timeout', ['order' => $e->orderId]));
    $this->renderable(fn (ModelNotFoundException $e, $request) => $request->expectsJson()
        ? response()->json(['success' => false, 'code' => 'NOT_FOUND', 'message' => 'Record not found.'], 404) : null);
}
TXT),
            $text('Catch only where you can recover or add meaning. Wrapping every controller in try { } catch (Exception $e) { return "error"; } hides bugs and loses stack traces. Converting a vendor exception into your own (GatewayTimeoutException with previous: $e) is good: the rest of the app no longer depends on Guzzle or a payment SDK.'),
        ]],
        ['id' => 'spring', 'title' => 'Spring Boot: checked vs unchecked and @RestControllerAdvice', 'blocks' => [
            $text('Java has checked exceptions (must be declared or caught, such as IOException) and unchecked ones (RuntimeException and subclasses). Business exceptions are usually unchecked. @Transactional rolls back on unchecked exceptions by default but NOT on checked ones unless you add rollbackFor, a classic interview trap.'),
            $code('Global handler', 'java', <<<'TXT'
public class InsufficientBalanceException extends RuntimeException {
    public InsufficientBalanceException(String accountNo) { super("Insufficient balance in " + accountNo); }
}

@RestControllerAdvice
class ApiExceptionHandler {
    @ExceptionHandler(InsufficientBalanceException.class)
    ResponseEntity<ApiError> balance(InsufficientBalanceException e) {
        return ResponseEntity.unprocessableEntity().body(new ApiError("INSUFFICIENT_BALANCE", e.getMessage()));
    }

    @ExceptionHandler(MethodArgumentNotValidException.class)
    ResponseEntity<ApiError> validation(MethodArgumentNotValidException e) {
        return ResponseEntity.badRequest().body(ApiError.fromFieldErrors(e.getBindingResult()));
    }

    @ExceptionHandler(Exception.class)
    ResponseEntity<ApiError> unexpected(Exception e) {
        log.error("Unhandled", e);                               // full stack trace in logs only
        return ResponseEntity.internalServerError().body(new ApiError("SERVER_ERROR", "Something went wrong"));
    }
}

@Transactional(rollbackFor = Exception.class)                  // roll back on checked exceptions too
public void transfer(...) { ... }
TXT),
        ]],
        ['id' => 'react-kafka', 'title' => 'React error boundaries and Kafka retries', 'blocks' => [
            $code('React: show a fallback instead of a white screen', 'jsx', <<<'TXT'
class ErrorBoundary extends React.Component {
  state = { failed: false };
  static getDerivedStateFromError() { return { failed: true }; }
  componentDidCatch(error, info) { reportToSentry(error, info); }
  render() { return this.state.failed ? <p>Could not load your orders. <a href="">Retry</a></p> : this.props.children; }
}

// API errors are not render errors: handle them where you call fetch
const res = await api('/orders', { method: 'POST', body });
if (res.status === 422) setErrors((await res.json()).errors);
else if (res.status === 409) toast('Item went out of stock');
TXT),
            $text(
                'Kafka consumers cannot return an error to a caller, so failures are handled with retries and a dead-letter topic (DLT). A payment-events consumer retries a message a few times with backoff; if it still fails (for example the message is malformed) it is published to payments.DLT, alerting the team, so the partition is not blocked forever.',
                'Because a consumer may process the same message twice after a retry or rebalance, every handler must be idempotent: check the event id in a processed_events table before applying it.'
            ),
            $code('Spring Kafka retry + dead-letter topic', 'java', <<<'TXT'
@Bean
DefaultErrorHandler errorHandler(KafkaTemplate<String, Object> template) {
    var recoverer = new DeadLetterPublishingRecoverer(template);         // sends to <topic>.DLT
    var handler = new DefaultErrorHandler(recoverer, new ExponentialBackOff(1000, 2.0));
    handler.addNotRetryableExceptions(InvalidPaymentEventException.class);  // bad data: no point retrying
    return handler;
}
TXT),
        ]],
        ['id' => 'practice', 'title' => 'Interview questions', 'blocks' => [
            $ask([
                ['What should a 500 response contain in production?', 'A generic message and a reference id. Stack traces, SQL and file paths go to logs only (APP_DEBUG=false).'],
                ['Does @Transactional roll back on IOException?', 'No, checked exceptions do not trigger rollback by default; use rollbackFor.'],
                ['How do you avoid double charging when retrying a gateway call?', 'Send an idempotency key the gateway honours, and check payment status before retrying.'],
                ['report() vs render() in Laravel?', 'report decides logging/alerting; render decides the HTTP response.'],
            ]),
        ]],
    ],
],
];
