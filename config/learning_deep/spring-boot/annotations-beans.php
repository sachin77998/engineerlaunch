<?php

// Spring Boot › Annotations and Bean Lifecycle, in depth.
return [

'@Component, @Service and @Repository' => <<<'MD'
## What they are
All three are stereotype annotations: they mark a class as a Spring bean so component scanning registers it in the IoC container. @Service and @Repository are specialisations of @Component that also describe the class's role in a layered architecture.

## The layers of a typical application
```text
Controller (@RestController)   – receives HTTP requests
      ↓
Service (@Service)             – business rules, transactions
      ↓
Repository (@Repository)       – database access
      ↓
Database
```

## Each annotation
- @Component: a generic Spring-managed class (a PDF generator, an SMS client, a utility).
- @Service: holds business logic. Functionally identical to @Component today, but it documents intent and is a natural target for AOP (logging, transactions).
- @Repository: data-access classes. Spring adds persistence exception translation: low-level exceptions such as SQLException or Hibernate exceptions become Spring's unchecked DataAccessException hierarchy, so the service layer does not depend on JDBC or JPA specifics.

## Example: job portal
```java
@Component
public class SlugGenerator {
    public String slug(String title) { return title.toLowerCase().replaceAll("[^a-z0-9]+", "-"); }
}

@Repository
public class JdbcJobRepository {
    private final JdbcTemplate jdbc;
    public JdbcJobRepository(JdbcTemplate jdbc) { this.jdbc = jdbc; }

    public List<String> titles() {
        return jdbc.queryForList("SELECT title FROM jobs", String.class);
    }
}

@Service
public class JobService {
    private final JdbcJobRepository jobs;
    private final SlugGenerator slugs;

    public JobService(JdbcJobRepository jobs, SlugGenerator slugs) {
        this.jobs = jobs;
        this.slugs = slugs;
    }

    public List<String> jobSlugs() {
        return jobs.titles().stream().map(slugs::slug).toList();
    }
}
```

## With Spring Data JPA
You usually write only an interface; Spring generates the implementation, so the interface does not need @Repository.

```java
public interface JobRepository extends JpaRepository<Job, Long> { }
```

## Common mistakes
- Putting business logic in controllers or repositories instead of services.
- Annotating a class outside the scanned packages and wondering why it is not injected.
- Using @Component on everything – it works, but loses the readability and exception translation benefits.

Note: @Controller and @RestController are also stereotypes of @Component, used for the web layer.

## Interview follow-ups
- Can you use @Component instead of @Service? Yes, behaviour is the same; @Service communicates the layer.
- What does @Repository add? Exception translation into DataAccessException.
MD,

'What is @Controller?' => <<<'MD'
## What it is
@Controller marks a class as a Spring MVC web controller: a bean whose methods handle HTTP requests. In a classic server-rendered application, controller methods return a view name (a Thymeleaf or JSP template) and put data into a Model.

## How a request flows
```text
Browser → DispatcherServlet → @Controller method → returns "jobs/list"
        → ViewResolver finds templates/jobs/list.html → HTML response
```

## Example: rendering a page
```java
@Controller
@RequestMapping("/jobs")
public class JobPageController {

    private final JobService jobs;

    public JobPageController(JobService jobs) { this.jobs = jobs; }

    @GetMapping
    public String list(Model model) {
        model.addAttribute("jobs", jobs.findOpenJobs());
        return "jobs/list";                     // view name, not text
    }

    @GetMapping("/{id}")
    public String show(@PathVariable Long id, Model model) {
        model.addAttribute("job", jobs.find(id));
        return "jobs/show";
    }
}
```

```html
<!-- src/main/resources/templates/jobs/list.html (Thymeleaf) -->
<ul>
  <li th:each="job : ${jobs}" th:text="${job.title}">Job title</li>
</ul>
```

## Returning data instead of a view
Adding @ResponseBody to a method makes Spring write the return value (JSON/text) directly to the response instead of resolving a view.

```java
@GetMapping("/count")
@ResponseBody
public long count() { return jobs.count(); }
```

## Form handling (POST-redirect-GET)
```java
@PostMapping
public String create(@Valid @ModelAttribute JobForm form, BindingResult errors) {
    if (errors.hasErrors()) return "jobs/new";
    jobs.create(form);
    return "redirect:/jobs";                 // avoids double submission on refresh
}
```

Note: use @Controller for server-rendered pages; for JSON APIs use @RestController.

## Interview follow-ups
- What is the DispatcherServlet? The front controller that receives every request and routes it to the right controller method.
- What does "redirect:" do? Sends an HTTP 302 so the browser makes a new GET request.
MD,

'What is @RestController?' => <<<'MD'
## What it is
@RestController is a convenience annotation equal to @Controller + @ResponseBody. Every method's return value is serialised directly into the HTTP response body – typically as JSON by Jackson – instead of being treated as a view name. It is the standard choice for REST APIs consumed by React, mobile apps or other services.

## Example: jobs API
```java
@RestController
@RequestMapping("/api/v1/jobs")
public class JobApiController {

    private final JobService jobs;
    public JobApiController(JobService jobs) { this.jobs = jobs; }

    @GetMapping
    public List<JobDto> list(@RequestParam(defaultValue = "0") int page) {
        return jobs.page(page);
    }

    @GetMapping("/{id}")
    public JobDto get(@PathVariable Long id) {
        return jobs.find(id);
    }

    @PostMapping
    public ResponseEntity<JobDto> create(@Valid @RequestBody CreateJobRequest request) {
        JobDto created = jobs.create(request);
        return ResponseEntity.created(URI.create("/api/v1/jobs/" + created.id())).body(created);
    }
}

public record JobDto(Long id, String title, String company, String location) { }
```

```text
GET /api/v1/jobs/7
HTTP/1.1 200 OK
Content-Type: application/json

{"id":7,"title":"Java Developer","company":"Ascendia","location":"Pune"}
```

## How the conversion works
1. Spring finds the method for the URL and HTTP method.
2. @RequestBody JSON is converted to Java objects by HttpMessageConverters (Jackson).
3. The method returns an object.
4. Jackson writes it as JSON, using the Accept header to pick the format.

## Best practices
- Return DTOs (records), not JPA entities – avoids leaking internal fields and lazy-loading errors.
- Use ResponseEntity when you need specific status codes or headers.
- Version the API in the path (/api/v1).
- Validate input with @Valid and handle errors centrally with @RestControllerAdvice.

## @Controller vs @RestController
- @Controller: returns view names; add @ResponseBody per method for data.
- @RestController: every method returns data; no views.
MD,

'What is @RequestMapping?' => <<<'MD'
## What it is
@RequestMapping maps HTTP requests to controller classes or methods. It can match on path, HTTP method, request parameters, headers, and content types.

## At class and method level
A class-level mapping sets a common prefix; method-level mappings add to it.

```java
@RestController
@RequestMapping("/api/v1/applications")
public class ApplicationController {

    @RequestMapping(method = RequestMethod.GET)                       // GET /api/v1/applications
    public List<ApplicationDto> all() { ... }

    @RequestMapping(value = "/{id}", method = RequestMethod.GET)      // GET /api/v1/applications/5
    public ApplicationDto one(@PathVariable Long id) { ... }

    @RequestMapping(method = RequestMethod.POST,
                    consumes = "application/json", produces = "application/json")
    public ApplicationDto apply(@RequestBody ApplyRequest req) { ... }
}
```

## Shortcut annotations (preferred)
- @GetMapping = @RequestMapping(method = GET)
- @PostMapping, @PutMapping, @PatchMapping, @DeleteMapping

```java
@GetMapping("/{id}")
public ApplicationDto one(@PathVariable Long id) { ... }
```

## Advanced matching
```java
@GetMapping(value = "/export", params = "format=csv")       // only when ?format=csv
public ResponseEntity<byte[]> exportCsv() { ... }

@GetMapping(value = "/export", headers = "X-Admin=true")    // only with this header
public ResponseEntity<byte[]> exportAdmin() { ... }

@PostMapping(value = "/resume", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
public void upload(@RequestParam MultipartFile file) { ... }
```

## Path patterns
- /files/{name} – one segment.
- /files/** – any depth.
- /jobs/{id:\\d+} – regular expression constraint (digits only).

Note: without a method attribute, @RequestMapping matches every HTTP method, which is rarely what you want on an API.

## Interview follow-ups
- What happens when two methods match the same URL and method? Startup fails with an "ambiguous mapping" error.
- What returns 405? A path that exists but not for the requested HTTP method.
MD,

'@GetMapping versus @PostMapping' => <<<'MD'
## The difference
Both are shortcuts of @RequestMapping for specific HTTP methods, and they follow HTTP semantics:
- @GetMapping handles GET: read data. Safe (no changes on the server) and idempotent (repeating it gives the same result). Parameters travel in the URL and can be cached and bookmarked.
- @PostMapping handles POST: create a resource or trigger an action. Not idempotent (sending twice may create two records). Data travels in the request body.

## Example: job applications
```java
@RestController
@RequestMapping("/api/v1/jobs/{jobId}/applications")
public class JobApplicationController {

    @GetMapping                                   // list applications for a job
    public List<ApplicationDto> list(@PathVariable Long jobId,
                                     @RequestParam(defaultValue = "applied") String status) {
        return service.list(jobId, status);
    }

    @PostMapping                                  // apply to the job
    public ResponseEntity<ApplicationDto> apply(@PathVariable Long jobId,
                                                @Valid @RequestBody ApplyRequest request) {
        ApplicationDto created = service.apply(jobId, request);
        return ResponseEntity.status(HttpStatus.CREATED).body(created);
    }
}
```

```text
GET  /api/v1/jobs/7/applications?status=shortlisted   → 200 OK, list
POST /api/v1/jobs/7/applications  {"resumeId": 12}    → 201 Created
```

## Side-by-side
- Purpose: GET reads; POST creates/acts.
- Data: GET uses path and query parameters; POST uses a JSON or form body.
- Idempotent: GET yes; POST no.
- Cacheable/bookmarkable: GET yes; POST no.
- Security: never put passwords or tokens in GET URLs (they appear in logs and browser history).

## Other mappings
- @PutMapping: replace a resource (idempotent).
- @PatchMapping: partially update.
- @DeleteMapping: remove (idempotent).

Note: a GET request must never change data – crawlers and browser prefetching may call it automatically.
MD,

'What is @PathVariable?' => <<<'MD'
## What it is
@PathVariable binds a value from the URL path (a URI template variable) to a method parameter. It identifies which resource is being addressed.

```java
@GetMapping("/api/v1/companies/{companyId}/jobs/{jobId}")
public JobDto job(@PathVariable Long companyId, @PathVariable Long jobId) {
    return jobs.findForCompany(companyId, jobId);
}
```

```text
GET /api/v1/companies/12/jobs/345 → companyId = 12, jobId = 345
```

## Naming and type conversion
- If the parameter name differs: @PathVariable("id") Long jobId.
- Spring converts the text to the parameter type (Long, UUID, enums). Invalid input such as /jobs/abc for a Long produces 400 Bad Request (MethodArgumentTypeMismatchException).

```java
@GetMapping("/orders/{orderNumber}")
public OrderDto byNumber(@PathVariable("orderNumber") String number) { ... }

@GetMapping("/jobs/{id:\\d+}")              // regex: only digits match this endpoint
public JobDto byId(@PathVariable Long id) { ... }
```

## Optional path variables and maps
```java
@GetMapping({"/reports", "/reports/{year}"})
public Report report(@PathVariable(required = false) Integer year) { ... }

@GetMapping("/a/{x}/b/{y}")
public String all(@PathVariable Map<String, String> vars) { return vars.toString(); }   // {x=..., y=...}
```

## @PathVariable vs @RequestParam
- Path variable: identifies a resource (/jobs/345).
- Request parameter: filters, sorts or pages a collection (/jobs?city=Pune&page=2).

Note: never trust a path variable for authorization – check that the logged-in user may access company 12 before returning its data.
MD,

'What is @RequestParam?' => <<<'MD'
## What it is
@RequestParam binds query-string parameters (or form fields) to method parameters. It is used for optional inputs like filters, search terms, sorting and pagination.

```java
@GetMapping("/api/v1/jobs")
public Page<JobDto> search(
        @RequestParam(required = false) String q,
        @RequestParam(defaultValue = "Pune") String city,
        @RequestParam(defaultValue = "0") int page,
        @RequestParam(defaultValue = "20") int size) {
    return jobs.search(q, city, PageRequest.of(page, size));
}
```

```text
GET /api/v1/jobs?q=java&city=Noida&page=1
→ q = "java", city = "Noida", page = 1, size = 20 (default)
```

## Behaviour
- Required by default: a missing parameter without defaultValue gives 400 Bad Request.
- defaultValue makes it optional.
- Type conversion is automatic (int, LocalDate with @DateTimeFormat, enums).
- Multiple values: ?skill=java&skill=sql → @RequestParam List<String> skill.
- All parameters: @RequestParam Map<String, String> params.

```java
@GetMapping("/applications")
public List<ApplicationDto> filter(
        @RequestParam List<String> status,
        @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate from) { ... }
// /applications?status=applied&status=shortlisted&from=2026-10-01
```

## Uploading files
```java
@PostMapping(value = "/resume", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
public void upload(@RequestParam("file") MultipartFile file) { ... }
```

## Tip: group many filters into an object
```java
public record JobFilter(String q, String city, Integer minSalary) { }

@GetMapping("/api/v1/jobs")
public List<JobDto> search(JobFilter filter) { ... }   // bound from query parameters automatically
```

Note: validate numeric limits (size ≤ 100) so a client cannot request a million rows.
MD,

'What is @RequestBody?' => <<<'MD'
## What it is
@RequestBody tells Spring to read the HTTP request body and convert it into a Java object using an HttpMessageConverter – Jackson for JSON. It is used for POST, PUT and PATCH requests that send structured data.

```java
public record CreateJobRequest(
        @NotBlank String title,
        @NotNull Long companyId,
        @Min(0) Integer minSalary,
        List<@NotBlank String> skills) { }

@PostMapping("/api/v1/jobs")
public ResponseEntity<JobDto> create(@Valid @RequestBody CreateJobRequest request) {
    return ResponseEntity.status(HttpStatus.CREATED).body(jobs.create(request));
}
```

```text
POST /api/v1/jobs
Content-Type: application/json

{"title":"Spring Boot Developer","companyId":3,"minSalary":800000,"skills":["Java","Spring"]}
```

## What happens step by step
1. The Content-Type header (application/json) selects the Jackson converter.
2. Jackson creates the CreateJobRequest object from the JSON.
3. @Valid runs Bean Validation; violations throw MethodArgumentNotValidException → 400 with field errors.
4. Your method runs with a fully populated object.

## Common errors
- 415 Unsupported Media Type: the client did not send Content-Type: application/json.
- 400 Bad Request: malformed JSON or a type mismatch ("abc" for an Integer).
- Unknown JSON fields are ignored by default in Spring Boot (configurable with spring.jackson.deserialization.fail-on-unknown-properties).

## Security tip: never bind directly to entities
```java
// Risky: a client could send {"id": 9, "verified": true}
@PostMapping public Job create(@RequestBody Job job) { ... }
```
Use a request DTO that contains only the fields a client may set (protection against mass assignment).

Note: only one @RequestBody is allowed per method, because the body stream can be read once.
MD,

'What is @ResponseBody?' => <<<'MD'
## What it is
@ResponseBody tells Spring to write a method's return value directly into the HTTP response body (converted to JSON, XML or text) instead of treating it as a view name. @RestController applies it to every method automatically.

```java
@Controller
public class HealthController {

    @GetMapping("/status")
    @ResponseBody
    public Map<String, Object> status() {
        return Map.of("status", "UP", "time", Instant.now().toString());
    }
}
```

```text
GET /status → {"status":"UP","time":"2026-10-12T10:15:30Z"}
```

## How the format is chosen
Content negotiation: Spring looks at the client's Accept header and the available HttpMessageConverters.
- Objects → JSON via Jackson (default in Boot).
- String → text/plain.
- byte[] / Resource → files and downloads.
- XML if jackson-dataformat-xml is on the classpath and the client asks for application/xml.

## Mixing views and data in one @Controller
```java
@Controller
public class JobController {
    @GetMapping("/jobs")
    public String page(Model model) { return "jobs/list"; }          // HTML view

    @GetMapping("/jobs/suggest")
    @ResponseBody
    public List<String> suggest(@RequestParam String q) { return service.suggest(q); }   // JSON
}
```

## Controlling status and headers
@ResponseBody returns 200 by default. Use @ResponseStatus(HttpStatus.CREATED) or return ResponseEntity for full control.

Note: a String returned from a @RestController method is sent as text, not as a view name – a common surprise when moving code between controller types.
MD,

'What is @Qualifier?' => <<<'MD'
## The problem
When more than one bean of the same type exists, Spring cannot decide which to inject and fails with NoUniqueBeanDefinitionException.

```java
public interface PaymentGateway { String charge(long paise); }

@Component("razorpay")
public class RazorpayGateway implements PaymentGateway { public String charge(long p) { return "rzp_" + p; } }

@Component("stripe")
public class StripeGateway implements PaymentGateway { public String charge(long p) { return "stripe_" + p; } }

@Service
public class CheckoutService {
    public CheckoutService(PaymentGateway gateway) { }   // ✗ two candidates
}
```

```text
NoUniqueBeanDefinitionException: expected single matching bean but found 2: razorpay,stripe
```

## The fix: @Qualifier names the bean you want
```java
@Service
public class DomesticCheckoutService {
    private final PaymentGateway gateway;
    public DomesticCheckoutService(@Qualifier("razorpay") PaymentGateway gateway) {
        this.gateway = gateway;
    }
}

@Service
public class InternationalCheckoutService {
    private final PaymentGateway gateway;
    public InternationalCheckoutService(@Qualifier("stripe") PaymentGateway gateway) {
        this.gateway = gateway;
    }
}
```

## Other ways to resolve ambiguity
- @Primary on the default implementation (used when no qualifier is given).
- Matching the parameter name to the bean name (PaymentGateway razorpay).
- Injecting all of them: List<PaymentGateway> or Map<String, PaymentGateway> (bean name → bean), useful for a strategy pattern:

```java
@Service
public class PaymentRouter {
    private final Map<String, PaymentGateway> gateways;
    public PaymentRouter(Map<String, PaymentGateway> gateways) { this.gateways = gateways; }
    public String pay(String provider, long paise) { return gateways.get(provider).charge(paise); }
}
```

## @Qualifier vs @Primary
- @Primary: set once on a bean; it wins by default everywhere.
- @Qualifier: chosen at each injection point; it always wins over @Primary.

Note: custom qualifier annotations (e.g. @Domestic) avoid typo-prone string names.
MD,

'What are bean scopes?' => <<<'MD'
## What it is
A bean's scope controls how many instances Spring creates and how long they live.

## The scopes
- singleton (default): one instance per Spring container, shared everywhere.
- prototype: a new instance every time the bean is requested or injected.
- request: one instance per HTTP request (web apps).
- session: one instance per HTTP session.
- application: one instance per ServletContext.
- websocket: one instance per WebSocket session.

```java
@Service                           // singleton by default
public class JobService { }

@Component
@Scope("prototype")
public class ResumeParser { }     // new parser each time

@Component
@RequestScope
public class RequestContext {     // per HTTP request
    private String requestId = UUID.randomUUID().toString();
    public String id() { return requestId; }
}
```

## Showing the difference
```java
ApplicationContext ctx = SpringApplication.run(App.class, args);
System.out.println(ctx.getBean(JobService.class) == ctx.getBean(JobService.class));     // true
System.out.println(ctx.getBean(ResumeParser.class) == ctx.getBean(ResumeParser.class)); // false
```

## Injecting a shorter-lived bean into a singleton
A singleton is created once, so injecting a prototype or request bean directly freezes one instance forever. Solutions:
- Inject ObjectProvider<ResumeParser> and call getObject() each time.
- Use a scoped proxy: @Scope(value = "request", proxyMode = ScopedProxyMode.TARGET_CLASS) (Spring injects a proxy that finds the current request's instance).

```java
@Service
public class ImportService {
    private final ObjectProvider<ResumeParser> parsers;
    public ImportService(ObjectProvider<ResumeParser> parsers) { this.parsers = parsers; }
    public void importAll(List<File> files) {
        files.forEach(f -> parsers.getObject().parse(f));   // fresh parser per file
    }
}
```

## Lifecycle differences
- Singleton: created at startup (eagerly), destroyed at shutdown (@PreDestroy runs).
- Prototype: created on demand, Spring does not track it afterwards – @PreDestroy is not called.

Note: most services, repositories and controllers should be stateless singletons.
MD,

'What is a singleton bean?' => <<<'MD'
## What it is
A singleton bean is the default scope: Spring creates exactly one instance per application context and injects that same instance everywhere it is needed.

```java
@Service
public class ExchangeRateService {
    public BigDecimal usdToInr() { return new BigDecimal("83.20"); }
}

@RestController class A { A(ExchangeRateService s) { System.out.println(s); } }
@RestController class B { B(ExchangeRateService s) { System.out.println(s); } }
```

```text
com.portal.ExchangeRateService@4a1c0752
com.portal.ExchangeRateService@4a1c0752   ← same object
```

## Spring singleton vs GoF Singleton pattern
- GoF Singleton: one instance per class per JVM (ClassLoader), enforced with a private constructor.
- Spring singleton: one instance per container per bean definition; you can still create others with new or define two beans of the same class.

## Thread safety
A web server handles requests on many threads at once, and all of them use the same singleton. Spring does not make it thread-safe for you.

```java
@Service
public class CounterService {
    private int count = 0;                 // ✗ shared mutable state → race conditions
    public int next() { return ++count; }
}

@Service
public class SafeCounterService {
    private final AtomicInteger count = new AtomicInteger();   // ✓ thread-safe
    public int next() { return count.incrementAndGet(); }
}
```

Best practice: keep singletons stateless – only final dependencies and configuration as fields; per-request data lives in method parameters and local variables.

## Eager vs lazy creation
Singletons are created at startup so configuration errors fail fast. @Lazy delays creation until first use (useful for heavy, rarely used beans).

Note: storing the current user, request data or a running total in a singleton's fields is one of the most common production bugs in Spring applications.
MD,

'What is a prototype bean?' => <<<'MD'
## What it is
A prototype-scoped bean gets a new instance every time it is requested from the container or injected. Use it for stateful, short-lived objects.

```java
@Component
@Scope(ConfigurableBeanFactory.SCOPE_PROTOTYPE)
public class ReportBuilder {
    private final List<String> rows = new ArrayList<>();   // state belongs to one report
    public ReportBuilder add(String row) { rows.add(row); return this; }
    public String build() { return String.join("\n", rows); }
}
```

```java
ReportBuilder a = ctx.getBean(ReportBuilder.class);
ReportBuilder b = ctx.getBean(ReportBuilder.class);
System.out.println(a == b);   // false
```

## The singleton-injection pitfall
```java
@Service
public class ReportService {
    private final ReportBuilder builder;          // ✗ injected once → shared by every call
    public ReportService(ReportBuilder builder) { this.builder = builder; }
}
```
Fix with a provider (or @Lookup method):

```java
@Service
public class ReportService {
    private final ObjectProvider<ReportBuilder> builders;
    public ReportService(ObjectProvider<ReportBuilder> builders) { this.builders = builders; }

    public String monthly(List<String> rows) {
        ReportBuilder builder = builders.getObject();   // new instance each call
        rows.forEach(builder::add);
        return builder.build();
    }
}
```

## Lifecycle
- Spring creates and injects the bean and runs @PostConstruct.
- After that, the caller owns it: Spring does not call @PreDestroy for prototypes. Release resources yourself.

## When to use
Builders, parsers or clients holding per-task state, or objects that are not thread-safe. Often a plain new inside a method is simpler – use prototype scope when the object itself needs injected dependencies.
MD,

'What is @Primary?' => <<<'MD'
## What it is
@Primary marks one bean as the default choice when several beans of the same type qualify for injection and no @Qualifier is specified.

```java
public interface NotificationSender { void send(String to, String message); }

@Component
@Primary
public class EmailSender implements NotificationSender {
    public void send(String to, String msg) { System.out.println("Email to " + to); }
}

@Component("sms")
public class SmsSender implements NotificationSender {
    public void send(String to, String msg) { System.out.println("SMS to " + to); }
}

@Service
public class ApplicationAlerts {
    private final NotificationSender sender;              // EmailSender (primary)
    public ApplicationAlerts(NotificationSender sender) { this.sender = sender; }
}

@Service
public class OtpService {
    private final NotificationSender sender;              // SmsSender (explicit)
    public OtpService(@Qualifier("sms") NotificationSender sender) { this.sender = sender; }
}
```

## On @Bean methods
```java
@Configuration
public class DataSourceConfig {
    @Bean @Primary
    DataSource portalDataSource() { ... }        // used by JPA by default

    @Bean
    DataSource reportingDataSource() { ... }     // injected with @Qualifier("reportingDataSource")
}
```

## Rules
- Only one @Primary per type, otherwise ambiguity returns.
- @Qualifier at an injection point overrides @Primary.

Note: @Primary is ideal for "one normal implementation plus special cases"; when every consumer must choose deliberately, prefer @Qualifier everywhere.
MD,

'What is @Configuration?' => <<<'MD'
## What it is
@Configuration marks a class as a source of bean definitions. Its @Bean methods create and configure objects – usually third-party classes you cannot annotate with @Component – and register them in the container.

```java
@Configuration
public class HttpClientConfig {

    @Bean
    public RestClient razorpayClient(@Value("${razorpay.base-url}") String baseUrl) {
        return RestClient.builder()
                .baseUrl(baseUrl)
                .defaultHeader("Accept", "application/json")
                .build();
    }

    @Bean
    public ObjectMapper objectMapper() {
        return new ObjectMapper().registerModule(new JavaTimeModule())
                                 .disable(SerializationFeature.WRITE_DATES_AS_TIMESTAMPS);
    }
}
```

## Full mode: proxying guarantees singletons
@Configuration classes are proxied with CGLIB, so calling one @Bean method from another returns the container's single instance instead of creating a new object.

```java
@Configuration
public class AppConfig {
    @Bean public JobRepository jobRepository() { return new JobRepository(); }
    @Bean public JobService jobService() { return new JobService(jobRepository()); }     // same instance
    @Bean public ReportService reportService() { return new ReportService(jobRepository()); }
}
```

With @Configuration(proxyBeanMethods = false) (lite mode), those inner calls would create new objects – faster startup, but inject dependencies as method parameters instead.

## Organising configuration
- One configuration class per concern: security, data source, messaging, web.
- @Import(OtherConfig.class) combines them.
- @Profile("prod") activates a configuration only in some environments.
- @ConditionalOnProperty enables features by property.

```java
@Configuration
@Profile("dev")
public class DevDataConfig {
    @Bean CommandLineRunner seed(JobRepository jobs) { return args -> jobs.save(new Job("Sample Java job")); }
}
```

Note: @SpringBootApplication itself is a @Configuration, so small apps can declare @Bean methods in the main class.
MD,

'What is @Bean?' => <<<'MD'
## What it is
@Bean marks a method inside a @Configuration (or @Component) class whose return value Spring registers as a bean. You control exactly how the object is created.

```java
@Configuration
public class MailConfig {

    @Bean(name = "transactionalMailer", initMethod = "connect", destroyMethod = "close")
    public SmtpClient smtpClient(MailProperties props) {      // dependencies are method parameters
        SmtpClient client = new SmtpClient(props.host(), props.port());
        client.setTimeout(Duration.ofSeconds(10));
        return client;
    }
}
```

## Details
- The bean name defaults to the method name (smtpClient); override with @Bean(name = ...).
- Method parameters are resolved from the container (like constructor injection).
- initMethod and destroyMethod hook into the lifecycle (Spring also auto-detects close()/shutdown()).
- Combine with @Scope, @Primary, @Lazy, @Profile and @Conditional*.

## @Bean vs @Component
- @Component: you own the class; Spring discovers it by scanning.
- @Bean: for classes you cannot modify (libraries: RestClient, ObjectMapper, DataSource, KafkaTemplate customisations), or when creation needs logic.

```java
@Bean
@ConditionalOnProperty(name = "payments.provider", havingValue = "stripe")
public PaymentGateway stripeGateway(StripeProperties p) { return new StripeGateway(p.apiKey()); }

@Bean
@ConditionalOnProperty(name = "payments.provider", havingValue = "razorpay", matchIfMissing = true)
public PaymentGateway razorpayGateway(RazorpayProperties p) { return new RazorpayGateway(p.key(), p.secret()); }
```

Note: overriding a Boot default is usually just declaring your own @Bean of that type – auto-configuration backs off.
MD,

'What is component scanning?' => <<<'MD'
## What it is
Component scanning is how Spring automatically finds classes annotated with @Component, @Service, @Repository, @Controller, @RestController and @Configuration, and registers them as beans – without listing each one manually.

## How it works
1. @ComponentScan (included in @SpringBootApplication) defines base packages – by default the package of the annotated class.
2. Spring scans those packages and sub-packages on the classpath.
3. Every class with a stereotype annotation becomes a bean definition.

```text
com.ascendia.portal                ← @SpringBootApplication here
├── jobs/JobService.java           ✓ scanned
├── companies/CompanyService.java  ✓ scanned
└── web/JobController.java         ✓ scanned
com.ascendia.shared/AuditLogger    ✗ outside the base package
```

## Customising the scan
```java
@SpringBootApplication(scanBasePackages = {"com.ascendia.portal", "com.ascendia.shared"})
public class PortalApplication { }

@Configuration
@ComponentScan(basePackages = "com.ascendia.plugins",
               excludeFilters = @ComponentScan.Filter(type = FilterType.REGEX, pattern = ".*Legacy.*"))
public class PluginConfig { }
```

## Common problems
- "No qualifying bean of type X": X is outside the scanned packages or missing an annotation.
- Main class placed in a sub-package, shrinking the scan area.
- Scanning too broadly (e.g. the root "com") slows startup and picks up unintended beans.

## Alternatives
- @Import(SomeConfig.class) to bring specific configurations in.
- Auto-configuration for library code – libraries should not rely on the application scanning them.

Note: Spring Data repositories are found by a separate scan enabled by auto-configuration (@EnableJpaRepositories), also defaulting to the main package.
MD,

'What is the bean lifecycle?' => <<<'MD'
## The phases
1. Instantiation – Spring calls the constructor.
2. Populate properties – dependencies are injected (constructor, setters, fields).
3. Aware callbacks – BeanNameAware, ApplicationContextAware, etc. if implemented.
4. BeanPostProcessor "before initialization".
5. Initialization – @PostConstruct, then InitializingBean.afterPropertiesSet(), then a custom initMethod.
6. BeanPostProcessor "after initialization" – proxies for @Transactional, @Async, @Cacheable are created here.
7. Bean is ready and used by the application.
8. Destruction (singletons, on shutdown) – @PreDestroy, then DisposableBean.destroy(), then destroyMethod.

```text
constructor → inject dependencies → @PostConstruct → (proxy) → in use → @PreDestroy
```

## Example
```java
@Component
public class ExchangeRateCache implements InitializingBean, DisposableBean {

    private final RateClient client;
    private Map<String, BigDecimal> rates = Map.of();

    public ExchangeRateCache(RateClient client) {          // 1–2
        this.client = client;
        System.out.println("1. constructor");
    }

    @PostConstruct
    void warmUp() {                                        // 5
        rates = client.fetchAll();
        System.out.println("2. @PostConstruct – loaded " + rates.size() + " rates");
    }

    @Override public void afterPropertiesSet() { System.out.println("3. afterPropertiesSet"); }

    @PreDestroy
    void flush() { System.out.println("4. @PreDestroy"); }   // 8

    @Override public void destroy() { System.out.println("5. destroy"); }
}
```

```text
1. constructor
2. @PostConstruct – loaded 160 rates
3. afterPropertiesSet
... application runs ...
4. @PreDestroy
5. destroy
```

## Why it matters
- Do initialisation that needs injected dependencies in @PostConstruct, not the constructor's field defaults.
- @Transactional does not apply inside @PostConstruct, because the proxy is created after initialization.
- Prototype beans never get destruction callbacks.

Note: for work that should run after the whole application is ready (cache warm-up, scheduled jobs), prefer ApplicationRunner or @EventListener(ApplicationReadyEvent.class).
MD,

'What is @PostConstruct?' => <<<'MD'
## What it is
@PostConstruct marks a method that Spring calls once, after the bean is created and all dependencies have been injected, but before the bean is used. It is the place for initialisation logic.

```java
@Service
public class SkillDictionary {

    private final SkillRepository skills;
    private Set<String> known = Set.of();

    public SkillDictionary(SkillRepository skills) { this.skills = skills; }

    @PostConstruct
    void load() {
        known = skills.findAllNames().stream().map(String::toLowerCase).collect(Collectors.toSet());
        log.info("Loaded {} skills", known.size());
    }

    public boolean isKnown(String skill) { return known.contains(skill.toLowerCase()); }
}
```

## Rules
- The method has no parameters, returns void and may have any visibility.
- It runs exactly once per bean instance.
- If it throws, the bean fails to create and application startup fails – useful for validating configuration early.

## Typical uses
- Loading reference data or warming a cache.
- Validating required configuration (missing API key → fail fast).
- Opening a connection or registering a listener.

## Caveats
- Proxies are not applied yet, so @Transactional/@Async on the same bean are not active inside it.
- Long work here delays startup; consider ApplicationReadyEvent or a background job for heavy loading.
- In Spring Boot 3 the annotation is jakarta.annotation.PostConstruct (previously javax).

Note: why not the constructor? In the constructor, field and setter injection have not happened yet; @PostConstruct is guaranteed to see every dependency.
MD,

'What is @PreDestroy?' => <<<'MD'
## What it is
@PreDestroy marks a method that Spring calls just before a bean is removed from the container – normally during application shutdown. Use it to release resources cleanly.

```java
@Component
public class ResumeIndexWriter {

    private final ExecutorService pool = Executors.newFixedThreadPool(4);
    private final BufferedWriter log;

    public ResumeIndexWriter() throws IOException {
        log = Files.newBufferedWriter(Path.of("index.log"));
    }

    @PreDestroy
    void shutdown() throws Exception {
        pool.shutdown();                                       // stop accepting tasks
        if (!pool.awaitTermination(20, TimeUnit.SECONDS)) pool.shutdownNow();
        log.flush();
        log.close();
        System.out.println("Index writer closed cleanly");
    }
}
```

## When it runs
- On a normal shutdown: context.close(), Ctrl+C, SIGTERM from Docker/Kubernetes (Spring registers a JVM shutdown hook).
- Not on a hard kill (kill -9) or a JVM crash.
- Only for singletons; prototype beans are not tracked after creation.

## Typical uses
- Closing thread pools, file handles, sockets and clients.
- Flushing buffered metrics or logs.
- Deregistering from a service registry.

## Graceful shutdown of the web server
```properties
server.shutdown=graceful
spring.lifecycle.timeout-per-shutdown-phase=30s
```
With these, in-flight HTTP requests finish before beans are destroyed.

Note: libraries such as HikariCP and Kafka clients already close themselves through their own destroy methods; you only need @PreDestroy for resources you create.
MD,

];
