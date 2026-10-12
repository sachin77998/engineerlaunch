<?php

// Spring Boot › REST API development and Spring Data JPA, in depth.
return [

'How do you create a REST API?' => <<<'MD'
## The layers
```text
HTTP request → @RestController (validation, mapping)
             → @Service (business rules, @Transactional)
             → Repository (Spring Data JPA)
             → Database
DTOs carry data in and out; entities stay inside the service/repository layers.
```

## Step 1: entity and repository
```java
@Entity
@Table(name = "jobs")
public class Job {
    @Id @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    private String title;
    private String location;
    private boolean open = true;
    // getters and setters
}

public interface JobRepository extends JpaRepository<Job, Long> {
    Page<Job> findByLocationIgnoreCaseAndOpenTrue(String location, Pageable pageable);
}
```

## Step 2: DTOs
```java
public record CreateJobRequest(@NotBlank @Size(max = 120) String title, @NotBlank String location) { }
public record JobResponse(Long id, String title, String location, boolean open) {
    static JobResponse from(Job j) { return new JobResponse(j.getId(), j.getTitle(), j.getLocation(), j.isOpen()); }
}
```

## Step 3: service
```java
@Service
public class JobService {
    private final JobRepository jobs;
    public JobService(JobRepository jobs) { this.jobs = jobs; }

    @Transactional
    public JobResponse create(CreateJobRequest req) {
        Job job = new Job();
        job.setTitle(req.title());
        job.setLocation(req.location());
        return JobResponse.from(jobs.save(job));
    }

    @Transactional(readOnly = true)
    public JobResponse get(Long id) {
        return jobs.findById(id).map(JobResponse::from)
                   .orElseThrow(() -> new JobNotFoundException(id));
    }
}
```

## Step 4: controller
```java
@RestController
@RequestMapping("/api/v1/jobs")
public class JobController {
    private final JobService service;
    public JobController(JobService service) { this.service = service; }

    @PostMapping
    public ResponseEntity<JobResponse> create(@Valid @RequestBody CreateJobRequest req) {
        JobResponse created = service.create(req);
        return ResponseEntity.created(URI.create("/api/v1/jobs/" + created.id())).body(created);
    }

    @GetMapping("/{id}")
    public JobResponse get(@PathVariable Long id) { return service.get(id); }
}
```

## Step 5: error handling
```java
@RestControllerAdvice
public class ApiErrors {
    @ExceptionHandler(JobNotFoundException.class)
    ResponseEntity<Map<String, Object>> notFound(JobNotFoundException e) {
        return ResponseEntity.status(404).body(Map.of("code", "JOB_NOT_FOUND", "message", e.getMessage()));
    }
}
```

## Trying it
```text
POST /api/v1/jobs {"title":"Java Developer","location":"Pune"}  → 201 Created, Location: /api/v1/jobs/1
GET  /api/v1/jobs/1                                          → 200 OK
GET  /api/v1/jobs/99                                         → 404 {"code":"JOB_NOT_FOUND",...}
```

## Checklist for a production API
- Correct HTTP methods and status codes, DTOs instead of entities, validation, central error handling.
- Pagination for lists, versioning (/v1), authentication (Spring Security + JWT).
- OpenAPI documentation (springdoc), tests (@WebMvcTest, @SpringBootTest).
MD,

'What are HTTP methods?' => <<<'MD'
## What they are
HTTP methods (verbs) tell the server what action the client wants on a resource. REST APIs map them to create, read, update and delete operations.

## The main methods
- GET – read a resource or collection. Safe and idempotent. No body.
- POST – create a resource or trigger an action (send OTP, pay). Not idempotent.
- PUT – replace a resource completely. Idempotent.
- PATCH – update part of a resource. Usually not guaranteed idempotent.
- DELETE – remove a resource. Idempotent.
- HEAD – like GET but headers only (check existence/size).
- OPTIONS – what methods are allowed; used by browsers for CORS preflight.

## Safe and idempotent
- Safe: does not change server state (GET, HEAD, OPTIONS).
- Idempotent: repeating the same request has the same effect as doing it once (GET, PUT, DELETE). Retrying a timed-out PUT is safe; retrying a POST may create duplicates.

## Mapping for a candidate profile API
```text
GET    /api/v1/candidates          list candidates
GET    /api/v1/candidates/42       get one
POST   /api/v1/candidates          create   → 201 Created
PUT    /api/v1/candidates/42       replace the whole profile
PATCH  /api/v1/candidates/42       change only phone/headline
DELETE /api/v1/candidates/42       delete   → 204 No Content
```

```java
@RestController
@RequestMapping("/api/v1/candidates")
class CandidateController {
    @GetMapping List<CandidateDto> all() { ... }
    @GetMapping("/{id}") CandidateDto one(@PathVariable Long id) { ... }
    @PostMapping ResponseEntity<CandidateDto> create(@Valid @RequestBody NewCandidate c) { ... }
    @PutMapping("/{id}") CandidateDto replace(@PathVariable Long id, @Valid @RequestBody NewCandidate c) { ... }
    @PatchMapping("/{id}") CandidateDto patch(@PathVariable Long id, @RequestBody Map<String, Object> changes) { ... }
    @DeleteMapping("/{id}") ResponseEntity<Void> delete(@PathVariable Long id) { ... }
}
```

## Making POST safe to retry (payments)
Clients send an Idempotency-Key header; the server stores the first result for that key and returns it for repeats, so a retried "pay" request never charges twice.

Note: actions that are not CRUD (approve, cancel, refund) are usually modelled as POST /orders/{id}/cancel.
MD,

'PUT versus PATCH' => <<<'MD'
## The difference
- PUT replaces the entire resource with the representation you send. Fields you omit are cleared (set to null/default). Idempotent.
- PATCH applies a partial change: only the fields you send are modified. Not guaranteed idempotent (e.g. "increment views").

## Example: a candidate profile
Current state:
```json
{"id": 42, "name": "Asha", "phone": "98100...", "city": "Pune", "headline": "Java Developer"}
```

PUT with only name and city:
```json
PUT /api/v1/candidates/42
{"name": "Asha Rao", "city": "Bengaluru"}
```
Result: phone and headline become null, because PUT replaces everything.

PATCH with only city:
```json
PATCH /api/v1/candidates/42
{"city": "Bengaluru"}
```
Result: only city changes; everything else stays.

## Implementing PATCH in Spring
```java
public record CandidatePatch(Optional<String> phone, Optional<String> city, Optional<String> headline) { }

@PatchMapping("/{id}")
@Transactional
public CandidateDto patch(@PathVariable Long id, @RequestBody CandidatePatch patch) {
    Candidate c = candidates.findById(id).orElseThrow();
    patch.phone().ifPresent(c::setPhone);
    patch.city().ifPresent(c::setCity);
    patch.headline().ifPresent(c::setHeadline);
    return CandidateDto.from(c);              // dirty checking saves the changes on commit
}
```

## Implementing PUT
```java
@PutMapping("/{id}")
@Transactional
public CandidateDto replace(@PathVariable Long id, @Valid @RequestBody CandidateRequest body) {
    Candidate c = candidates.findById(id).orElseThrow();
    c.setName(body.name()); c.setPhone(body.phone()); c.setCity(body.city()); c.setHeadline(body.headline());
    return CandidateDto.from(c);
}
```

## Which to use
- Forms that always submit the full object → PUT.
- Mobile apps editing one field, toggles, status changes → PATCH.

Note: JSON Merge Patch (application/merge-patch+json) and JSON Patch (application/json-patch+json, a list of operations) are standard PATCH formats for more complex needs.
MD,

'What is ResponseEntity?' => <<<'MD'
## What it is
ResponseEntity<T> represents the whole HTTP response: status code, headers and body. Returning it from a controller gives you full control instead of the default 200 OK.

```java
@PostMapping("/api/v1/applications")
public ResponseEntity<ApplicationDto> apply(@Valid @RequestBody ApplyRequest req) {
    ApplicationDto created = service.apply(req);
    return ResponseEntity
            .created(URI.create("/api/v1/applications/" + created.id()))   // 201 + Location header
            .header("X-Request-Id", MDC.get("requestId"))
            .body(created);
}

@GetMapping("/api/v1/applications/{id}")
public ResponseEntity<ApplicationDto> get(@PathVariable Long id) {
    return service.find(id)
            .map(ResponseEntity::ok)                    // 200 with body
            .orElse(ResponseEntity.notFound().build()); // 404 without body
}

@DeleteMapping("/api/v1/applications/{id}")
public ResponseEntity<Void> withdraw(@PathVariable Long id) {
    service.withdraw(id);
    return ResponseEntity.noContent().build();          // 204
}
```

## Useful builders
- ok(body), created(uri), accepted(), noContent(), badRequest(), notFound(), status(HttpStatus.CONFLICT).
- header(name, value), contentType(MediaType.APPLICATION_PDF), eTag(...), cacheControl(...).

## File download example
```java
@GetMapping("/api/v1/resumes/{id}/download")
public ResponseEntity<Resource> download(@PathVariable Long id) {
    Resource file = storage.load(id);
    return ResponseEntity.ok()
            .contentType(MediaType.APPLICATION_PDF)
            .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"resume.pdf\"")
            .body(file);
}
```

## When you do not need it
Returning a plain object gives 200 automatically; @ResponseStatus(HttpStatus.CREATED) on the method changes the default status. Use ResponseEntity when status or headers depend on the outcome.
MD,

'Which HTTP status codes should APIs use?' => <<<'MD'
## Families
- 2xx success, 3xx redirection, 4xx client error (fix the request), 5xx server error (retry may help).

## The codes you will use most
- 200 OK – successful GET/PUT/PATCH with a body.
- 201 Created – a POST created a resource; include a Location header.
- 202 Accepted – work queued for later (bulk import, report generation).
- 204 No Content – success without a body (DELETE).
- 400 Bad Request – malformed JSON, invalid parameters, validation failure (some APIs use 422 for validation).
- 401 Unauthorized – not authenticated (missing or expired token).
- 403 Forbidden – authenticated but not allowed.
- 404 Not Found – resource does not exist (or is hidden from this user).
- 405 Method Not Allowed – path exists, method does not.
- 409 Conflict – state conflict: duplicate email, applying twice, order already shipped.
- 415 Unsupported Media Type – wrong Content-Type.
- 422 Unprocessable Entity – well-formed but semantically invalid.
- 429 Too Many Requests – rate limit hit; include Retry-After.
- 500 Internal Server Error – unexpected bug; never expose stack traces.
- 502/503/504 – upstream service or gateway problems; clients may retry with backoff.

## Mapping exceptions to codes in Spring
```java
@RestControllerAdvice
public class ApiExceptionHandler {
    @ExceptionHandler(ResourceNotFoundException.class)
    ResponseEntity<ApiError> notFound(ResourceNotFoundException e) { return error(404, "NOT_FOUND", e.getMessage()); }

    @ExceptionHandler(DuplicateApplicationException.class)
    ResponseEntity<ApiError> conflict(DuplicateApplicationException e) { return error(409, "ALREADY_APPLIED", e.getMessage()); }

    @ExceptionHandler(MethodArgumentNotValidException.class)
    ResponseEntity<ApiError> invalid(MethodArgumentNotValidException e) { return error(400, "VALIDATION_FAILED", "Check the highlighted fields"); }

    private ResponseEntity<ApiError> error(int status, String code, String message) {
        return ResponseEntity.status(status).body(new ApiError(code, message, Instant.now()));
    }
}
```

Note: the status code and body must agree – never return 200 with {"error": ...}; clients, proxies and monitoring rely on the status.
MD,

'How do you validate API requests?' => <<<'MD'
## Bean Validation in Spring Boot
Add spring-boot-starter-validation, annotate DTO fields with constraints, and put @Valid on the @RequestBody (or @Validated on the controller for path/query parameters).

```java
public record RegisterCandidateRequest(
        @NotBlank @Size(max = 80) String name,
        @NotBlank @Email String email,
        @Pattern(regexp = "^[6-9]\\d{9}$", message = "must be a valid Indian mobile number") String phone,
        @Min(0) @Max(50) Integer experienceYears,
        @Past LocalDate dateOfBirth,
        @NotEmpty List<@NotBlank String> skills) { }

@PostMapping("/api/v1/candidates")
public ResponseEntity<CandidateDto> register(@Valid @RequestBody RegisterCandidateRequest req) { ... }
```

## Common constraints
@NotNull, @NotBlank, @NotEmpty, @Size, @Min/@Max, @Positive, @Email, @Pattern, @Past/@Future, @Digits, @DecimalMin. Use @Valid on nested objects and list elements.

## Returning field errors clearly
```java
@ExceptionHandler(MethodArgumentNotValidException.class)
ResponseEntity<Map<String, Object>> handle(MethodArgumentNotValidException ex) {
    Map<String, String> fields = new LinkedHashMap<>();
    ex.getBindingResult().getFieldErrors().forEach(e -> fields.put(e.getField(), e.getDefaultMessage()));
    return ResponseEntity.badRequest().body(Map.of("code", "VALIDATION_FAILED", "errors", fields));
}
```

```text
400 Bad Request
{"code":"VALIDATION_FAILED","errors":{"email":"must be a well-formed email address","phone":"must be a valid Indian mobile number"}}
```

## Validating path and query parameters
```java
@Validated
@RestController
class JobController {
    @GetMapping("/api/v1/jobs")
    List<JobDto> search(@RequestParam @Min(1) @Max(100) int size) { ... }   // ConstraintViolationException
}
```

## Custom validation
```java
@Target(FIELD) @Retention(RUNTIME) @Constraint(validatedBy = PanValidator.class)
public @interface ValidPan { String message() default "invalid PAN"; Class<?>[] groups() default {}; Class<? extends Payload>[] payload() default {}; }

public class PanValidator implements ConstraintValidator<ValidPan, String> {
    public boolean isValid(String v, ConstraintValidatorContext c) { return v == null || v.matches("[A-Z]{5}[0-9]{4}[A-Z]"); }
}
```

Note: validation of format belongs in DTOs; business rules (email already registered, job closed) belong in the service and usually return 409 or 422.
MD,

'What is global exception handling?' => <<<'MD'
## What it is
Instead of try/catch in every controller, a single @RestControllerAdvice class handles exceptions thrown from any controller and turns them into consistent HTTP responses.

```java
@RestControllerAdvice
public class GlobalExceptionHandler {

    private static final Logger log = LoggerFactory.getLogger(GlobalExceptionHandler.class);

    @ExceptionHandler(ResourceNotFoundException.class)
    public ResponseEntity<ApiError> notFound(ResourceNotFoundException ex) {
        return build(HttpStatus.NOT_FOUND, "NOT_FOUND", ex.getMessage());
    }

    @ExceptionHandler(MethodArgumentNotValidException.class)
    public ResponseEntity<ApiError> validation(MethodArgumentNotValidException ex) {
        String msg = ex.getBindingResult().getFieldErrors().stream()
                .map(e -> e.getField() + " " + e.getDefaultMessage()).collect(Collectors.joining("; "));
        return build(HttpStatus.BAD_REQUEST, "VALIDATION_FAILED", msg);
    }

    @ExceptionHandler(AccessDeniedException.class)
    public ResponseEntity<ApiError> forbidden(AccessDeniedException ex) {
        return build(HttpStatus.FORBIDDEN, "FORBIDDEN", "You cannot access this resource");
    }

    @ExceptionHandler(Exception.class)                          // safety net
    public ResponseEntity<ApiError> unexpected(Exception ex) {
        log.error("Unhandled error", ex);                       // full stack trace only in logs
        return build(HttpStatus.INTERNAL_SERVER_ERROR, "SERVER_ERROR", "Something went wrong");
    }

    private ResponseEntity<ApiError> build(HttpStatus status, String code, String message) {
        return ResponseEntity.status(status).body(new ApiError(code, message, Instant.now()));
    }
}

public record ApiError(String code, String message, Instant timestamp) { }
```

## How Spring chooses a handler
The most specific matching @ExceptionHandler wins (ResourceNotFoundException before Exception). Handlers can also live in a single controller for local rules.

## ProblemDetail (Spring 6 / Boot 3)
A standard error format (RFC 7807):
```java
@ExceptionHandler(ResourceNotFoundException.class)
ProblemDetail notFound(ResourceNotFoundException ex) {
    ProblemDetail pd = ProblemDetail.forStatusAndDetail(HttpStatus.NOT_FOUND, ex.getMessage());
    pd.setTitle("Job not found");
    return pd;
}
```

## Benefits
Consistent error shape for clients, no duplicated try/catch, no stack traces leaking to users, one place for logging and monitoring.
MD,

'How do you create a custom exception?' => <<<'MD'
## Why custom exceptions
They give business failures a name (JobClosedException, InsufficientBalanceException) so the service layer can signal them clearly and the global handler can map each to the right status code and error code.

## Step 1: define the exception
```java
public class JobClosedException extends RuntimeException {
    private final Long jobId;

    public JobClosedException(Long jobId) {
        super("Job " + jobId + " is no longer accepting applications");
        this.jobId = jobId;
    }

    public Long getJobId() { return jobId; }
}
```
Extend RuntimeException (unchecked) for business errors: no throws clauses everywhere, and @Transactional rolls back on unchecked exceptions by default.

## Step 2: throw it from the service
```java
@Transactional
public ApplicationDto apply(Long jobId, Long candidateId) {
    Job job = jobs.findById(jobId).orElseThrow(() -> new ResourceNotFoundException("Job", jobId));
    if (!job.isOpen()) throw new JobClosedException(jobId);
    if (applications.existsByJobIdAndCandidateId(jobId, candidateId)) throw new DuplicateApplicationException(jobId);
    return ApplicationDto.from(applications.save(new Application(job, candidateId)));
}
```

## Step 3: map it to HTTP
```java
@ExceptionHandler(JobClosedException.class)
ResponseEntity<ApiError> closed(JobClosedException ex) {
    return ResponseEntity.status(HttpStatus.CONFLICT)
            .body(new ApiError("JOB_CLOSED", ex.getMessage(), Instant.now()));
}
```
Or annotate the exception itself: @ResponseStatus(HttpStatus.CONFLICT).

## A small hierarchy keeps handlers simple
```java
public abstract class BusinessException extends RuntimeException {
    private final String code; private final HttpStatus status;
    protected BusinessException(String code, HttpStatus status, String message) { super(message); this.code = code; this.status = status; }
    public String code() { return code; } public HttpStatus status() { return status; }
}
public class JobClosedException extends BusinessException {
    public JobClosedException(Long id) { super("JOB_CLOSED", HttpStatus.CONFLICT, "Job " + id + " is closed"); }
}

@ExceptionHandler(BusinessException.class)
ResponseEntity<ApiError> business(BusinessException ex) {
    return ResponseEntity.status(ex.status()).body(new ApiError(ex.code(), ex.getMessage(), Instant.now()));
}
```

Note: keep the original cause when wrapping technical errors: new PaymentFailedException("Gateway timeout", cause).
MD,

'How should APIs return errors?' => <<<'MD'
## Principles
1. Use the correct HTTP status code.
2. Return a consistent JSON structure for every error.
3. Include a stable, machine-readable code that clients can switch on.
4. Give a human-readable message, plus field-level details for validation.
5. Add a trace/request id so support can find the logs.
6. Never expose stack traces, SQL or internal class names.

## A consistent error body
```json
{
  "code": "VALIDATION_FAILED",
  "message": "Some fields are invalid",
  "status": 400,
  "path": "/api/v1/candidates",
  "timestamp": "2026-10-12T10:20:30Z",
  "traceId": "8f2c1e7a9b",
  "errors": [
    {"field": "email", "message": "must be a well-formed email address"},
    {"field": "experienceYears", "message": "must be less than or equal to 50"}
  ]
}
```

## Error codes clients can rely on
- VALIDATION_FAILED (400), UNAUTHENTICATED (401), FORBIDDEN (403), NOT_FOUND (404), ALREADY_APPLIED (409), RATE_LIMITED (429), SERVER_ERROR (500).
A React or mobile app shows a friendly message per code and highlights fields from errors[].

## Implementation in Spring
```java
public record ApiError(String code, String message, int status, String path, Instant timestamp,
                       String traceId, List<FieldError> errors) {
    public record FieldError(String field, String message) { }
}
```
Build it in @RestControllerAdvice for every exception type (see global exception handling), taking the path from HttpServletRequest and the trace id from MDC.

## Payment API example
```json
{"code": "PAYMENT_DECLINED", "message": "The bank declined the card", "status": 402,
 "decline_reason": "insufficient_funds", "retryable": false}
```
Telling the client whether a retry makes sense prevents duplicate charges.

Note: Spring Boot 3's ProblemDetail follows RFC 7807 and is a good standard shape; extend it with your own properties.
MD,

'What is API versioning?' => <<<'MD'
## Why it is needed
Mobile apps and partner integrations cannot all update at the same moment. When you make a breaking change (renaming a field, changing a type, removing an endpoint), existing clients would fail. Versioning lets old and new contracts run side by side.

## Breaking vs non-breaking changes
- Non-breaking: adding a new optional field or endpoint – no new version needed.
- Breaking: removing or renaming fields, changing types or meanings, new required inputs – needs a new version.

## Strategies
1. URI path (most common, easy to test and route):
```java
@RestController @RequestMapping("/api/v1/jobs") class JobControllerV1 { ... }
@RestController @RequestMapping("/api/v2/jobs") class JobControllerV2 { ... }
```
2. Request parameter: /api/jobs?version=2.
3. Custom header: X-API-Version: 2
```java
@GetMapping(value = "/api/jobs", headers = "X-API-Version=2")
public List<JobV2> listV2() { ... }
```
4. Media type (content negotiation): Accept: application/vnd.ascendia.v2+json
```java
@GetMapping(value = "/api/jobs", produces = "application/vnd.ascendia.v2+json")
public List<JobV2> listV2() { ... }
```

## Example of a breaking change
v1 returns {"salary": "8-12 LPA"}; v2 returns {"salary": {"min": 800000, "max": 1200000, "currency": "INR"}}. Both versions read from the same service; only the DTO mapping differs.

## Lifecycle
- Announce deprecation (Deprecation and Sunset headers, docs, emails).
- Monitor traffic per version; retire v1 after clients migrate.
- Keep the number of live versions small (usually 2).

Note: share business logic between versions – only controllers and DTOs should be version-specific.
MD,

'What is JPA?' => <<<'MD'
## What it is
JPA (Jakarta Persistence API, formerly Java Persistence API) is the standard specification for object-relational mapping (ORM) in Java. It defines how Java objects (entities) map to database tables and how to save, query and update them without writing most SQL by hand. JPA is only a specification; Hibernate is the most used implementation, and Spring Data JPA builds repositories on top of it.

```text
Your code → Spring Data JPA (repositories) → JPA API (EntityManager) → Hibernate → JDBC → MySQL
```

## Key concepts
- Entity: a class mapped to a table (@Entity).
- EntityManager: the API to persist, find, merge and remove entities.
- Persistence context: the set of managed entities in the current transaction (a first-level cache). Changes to managed entities are saved automatically at commit ("dirty checking").
- JPQL: an object-oriented query language over entities, not tables.
- Relationships: @OneToMany, @ManyToOne, @ManyToMany, @OneToOne.

## Example
```java
@Entity
@Table(name = "candidates")
public class Candidate {
    @Id @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, length = 80)
    private String name;

    @Column(unique = true)
    private String email;

    @OneToMany(mappedBy = "candidate", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<Application> applications = new ArrayList<>();
}
```

```java
@PersistenceContext EntityManager em;

@Transactional
public void rename(Long id, String newName) {
    Candidate c = em.find(Candidate.class, id);   // SELECT ... WHERE id = ?
    c.setName(newName);                           // no save() call needed
}                                                 // UPDATE executed at commit (dirty checking)

List<Candidate> pune = em.createQuery(
        "SELECT c FROM Candidate c WHERE c.city = :city", Candidate.class)
    .setParameter("city", "Pune").getResultList();
```

## Benefits and trade-offs
- Less boilerplate, database portability, caching and change tracking.
- You must understand the generated SQL: lazy loading, N+1 queries and large persistence contexts can hurt performance.

Note: in interviews, distinguish JPA (specification), Hibernate (implementation) and Spring Data JPA (repository abstraction).
MD,

'What is Hibernate?' => <<<'MD'
## What it is
Hibernate ORM is the most widely used implementation of the JPA specification. It converts entity operations into SQL, manages the persistence context, caches entities, and handles relationships and transactions. Spring Boot uses Hibernate by default with spring-boot-starter-data-jpa.

## What Hibernate does for you
- Generates INSERT, UPDATE, DELETE and SELECT statements from entity mappings.
- Dirty checking: detects changed fields of managed entities and updates only those at commit.
- First-level cache: within one transaction, em.find(Job.class, 5) twice issues one query.
- Optional second-level cache (Ehcache, Redis) across transactions.
- Lazy loading of associations through proxies.
- Schema generation (ddl-auto) for development.
- Batch inserts/updates (hibernate.jdbc.batch_size).

## Seeing the SQL
```properties
spring.jpa.show-sql=true
spring.jpa.properties.hibernate.format_sql=true
logging.level.org.hibernate.orm.jdbc.bind=trace
```

```text
select j1_0.id, j1_0.title, j1_0.location from jobs j1_0 where j1_0.id=?
binding parameter (1:BIGINT) <- [5]
```

## Hibernate-specific features (beyond JPA)
- @BatchSize to load lazy collections in batches (fixes N+1).
- @Formula for computed columns, @Where/@SQLRestriction for soft deletes.
- Natural IDs, multi-tenancy, envers for auditing history.

## ddl-auto values
- create / create-drop: rebuild the schema (tests only).
- update: alters tables – convenient in development, risky in production.
- validate: only checks mappings – good for production.
- none: do nothing. Use Flyway or Liquibase migrations for real schema changes.

## Session vs EntityManager
Hibernate's native API is Session/SessionFactory; JPA's is EntityManager/EntityManagerFactory. In Spring you normally use EntityManager or Spring Data repositories, and Hibernate works underneath.

Note: always know which SQL a repository method generates – most JPA performance problems are invisible until you look at the logs.
MD,

'What is an entity?' => <<<'MD'
## What it is
An entity is a Java class whose instances are persisted in a database table. Each object corresponds to a row and each persistent field to a column. JPA tracks entities' lifecycle and changes.

```java
@Entity
@Table(name = "job_applications",
       uniqueConstraints = @UniqueConstraint(columnNames = {"job_id", "candidate_id"}))
public class JobApplication {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)      // MySQL AUTO_INCREMENT
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "job_id")
    private Job job;

    @Column(name = "candidate_id", nullable = false)
    private Long candidateId;

    @Enumerated(EnumType.STRING)                              // store "APPLIED", not 0
    @Column(nullable = false, length = 30)
    private ApplicationStatus status = ApplicationStatus.APPLIED;

    @CreationTimestamp
    private Instant appliedAt;

    @Version                                                  // optimistic locking
    private Integer version;

    protected JobApplication() { }                            // required no-arg constructor
    public JobApplication(Job job, Long candidateId) { this.job = job; this.candidateId = candidateId; }
}
```

## Requirements
- @Entity on the class and an @Id field.
- A no-argument constructor (public or protected).
- Not final, so Hibernate can create proxies for lazy loading.

## Entity lifecycle states
- Transient: created with new, not yet known to JPA.
- Managed: persisted or loaded inside an open persistence context; changes are tracked.
- Detached: the persistence context closed; changes are no longer tracked.
- Removed: scheduled for deletion.

## Best practices
- Use @Enumerated(EnumType.STRING); ordinal values break when enum order changes.
- Prefer LAZY for @ManyToOne/@OneToOne to avoid loading graphs you do not need.
- Implement equals/hashCode carefully (business key or id-based), never on lazy collections.
- Do not return entities from REST controllers; map to DTOs.

Note: @Version adds optimistic locking: if two users edit the same application, the second save fails with an OptimisticLockException instead of silently overwriting.
MD,

'What is JpaRepository?' => <<<'MD'
## What it is
JpaRepository<T, ID> is a Spring Data interface that gives you a full repository implementation at runtime: CRUD, paging, sorting and batch operations. You declare an interface; Spring generates the class.

```java
public interface JobRepository extends JpaRepository<Job, Long> { }
```

Inherited methods include save, saveAll, findById, findAll(Pageable), existsById, count, deleteById, flush, saveAndFlush.

## Derived query methods
Spring parses the method name and builds the query.

```java
public interface JobRepository extends JpaRepository<Job, Long> {
    List<Job> findByLocationIgnoreCase(String location);
    Page<Job> findByTitleContainingIgnoreCaseAndOpenTrue(String keyword, Pageable pageable);
    long countByCompanyId(Long companyId);
    boolean existsByCompanyIdAndTitle(Long companyId, String title);
    List<Job> findTop5ByOrderByPostedAtDesc();
}
```

## Custom JPQL and native queries
```java
@Query("SELECT j FROM Job j JOIN FETCH j.company WHERE j.open = true AND j.minSalary >= :salary")
List<Job> openJobsWithCompany(@Param("salary") int salary);

@Query(value = "SELECT location, COUNT(*) AS total FROM jobs GROUP BY location", nativeQuery = true)
List<Object[]> jobsPerCity();

@Modifying
@Query("UPDATE Job j SET j.open = false WHERE j.expiresAt < CURRENT_TIMESTAMP")
int closeExpired();
```

## Projections (fetch only what you need)
```java
public interface JobSummary { Long getId(); String getTitle(); String getLocation(); }
List<JobSummary> findByOpenTrue();
```

## Paging and sorting
```java
Page<Job> page = jobs.findAll(PageRequest.of(0, 20, Sort.by("postedAt").descending()));
page.getContent(); page.getTotalElements(); page.getTotalPages();
```

## Hierarchy
Repository → CrudRepository → PagingAndSortingRepository → JpaRepository (adds flush, batch deletes, List return types).

Note: @Modifying update queries bypass the persistence context; add @Transactional and clear the context (clearAutomatically = true) if managed entities might be stale.
MD,

'save() versus saveAndFlush()' => <<<'MD'
## The difference
- save(entity): makes the entity managed (persist for new, merge for detached). The SQL INSERT/UPDATE usually runs later, when the transaction commits or Hibernate flushes before a query.
- saveAndFlush(entity): saves and immediately flushes the persistence context, sending the SQL to the database right away (still inside the same transaction – not committed yet).

## What "flush" means
Flush synchronises pending changes from the persistence context to the database by executing SQL. Commit makes those changes permanent. Hibernate flushes automatically at commit and before queries that might see the pending data.

## Example
```java
@Transactional
public void register(Candidate c) {
    candidates.save(c);
    // no INSERT executed yet in many cases
    auditService.log("candidate created");
}   // INSERT happens here at commit

@Transactional
public void registerAndCheck(Candidate c) {
    try {
        candidates.saveAndFlush(c);          // INSERT now → unique constraint errors surface here
    } catch (DataIntegrityViolationException e) {
        throw new DuplicateEmailException(c.getEmail());
    }
    externalCrm.notify(c.getId());           // id and constraints are confirmed before calling out
}
```

## When to use saveAndFlush
- You need database-generated values or triggers immediately.
- You want constraint violations (duplicate email) to be thrown at a specific point so you can handle them.
- Native SQL or JDBC in the same transaction must see the change.

## When not to
In loops – flushing every row kills batching. Use saveAll and let Hibernate batch.

```properties
spring.jpa.properties.hibernate.jdbc.batch_size=50
spring.jpa.properties.hibernate.order_inserts=true
```

Note: with IDENTITY id generation, Hibernate must INSERT immediately on save to get the id, so save may already execute SQL; that is also why IDENTITY disables JDBC insert batching (SEQUENCE ids allow it).
MD,

'What is lazy loading?' => <<<'MD'
## What it is
With lazy loading, a related entity or collection is not fetched when the parent is loaded; JPA loads it only when your code first accesses it. Hibernate places a proxy (or an uninitialised collection) in the field and runs the extra query on access.

```java
@Entity
public class Company {
    @Id private Long id;
    private String name;

    @OneToMany(mappedBy = "company", fetch = FetchType.LAZY)   // LAZY is the default for collections
    private List<Job> jobs;
}
```

```java
@Transactional(readOnly = true)
public int openJobCount(Long companyId) {
    Company c = companies.findById(companyId).orElseThrow();   // SELECT company only
    return c.getJobs().size();                                 // now: SELECT jobs WHERE company_id = ?
}
```

## Defaults
- @OneToMany and @ManyToMany: LAZY.
- @ManyToOne and @OneToOne: EAGER by default – most teams change these to LAZY explicitly.

## LazyInitializationException
Accessing a lazy field after the transaction/persistence context has closed fails:

```java
Company c = companyService.find(5);     // transaction ended inside the service
c.getJobs().size();                     // LazyInitializationException: no Session
```

Fixes:
- Load what you need inside the transaction (map to a DTO there).
- Use JOIN FETCH or an @EntityGraph for that use case.
- Avoid relying on Open Session in View (spring.jpa.open-in-view=false is recommended), which keeps the session open during view rendering and hides N+1 problems.

## Benefits and risks
- Benefit: avoids loading large object graphs you do not need.
- Risk: the N+1 query problem when accessing lazy relations in a loop.

Note: lazy loading is a good default; decide per use case when to fetch eagerly with JOIN FETCH or entity graphs.
MD,

'What is eager loading?' => <<<'MD'
## What it is
Eager loading fetches related entities together with the parent, in the same query (a join) or immediately after. The data is ready even after the transaction ends.

## Two kinds of "eager"
1. Mapping-level FetchType.EAGER – the relation is always loaded, everywhere. Usually a bad idea for collections because every query pays the cost.
2. Query-level eager fetching for a specific use case – recommended.

```java
// Query-level: JOIN FETCH
@Query("SELECT DISTINCT c FROM Company c LEFT JOIN FETCH c.jobs WHERE c.city = :city")
List<Company> companiesWithJobs(@Param("city") String city);

// Query-level: entity graph
@EntityGraph(attributePaths = {"company", "skills"})
Page<Job> findByOpenTrue(Pageable pageable);
```

```text
select c.*, j.* from companies c left join jobs j on j.company_id = c.id where c.city = ?
```

## Comparing strategies
- Lazy everywhere + fetch joins where needed: best control.
- EAGER on @ManyToOne (default): convenient but can trigger extra selects you did not expect.
- EAGER on collections: can load huge graphs and causes Cartesian products when combined.

## Pitfalls
- JOIN FETCH on two collections at once multiplies rows (Cartesian product) – Hibernate throws MultipleBagFetchException for Lists; fetch one collection per query or use Set/batch fetching.
- JOIN FETCH with pagination on collections paginates in memory (HHH90003004 warning) – page the parents first, then fetch children by id.

## Batch fetching alternative
```properties
spring.jpa.properties.hibernate.default_batch_fetch_size=50
```
Lazy collections for up to 50 parents are loaded with one IN query instead of 50 queries.

Note: decide fetching per screen or endpoint, not globally on the entity.
MD,

'What is the N+1 query problem?' => <<<'MD'
## What it is
The N+1 problem happens when the application runs 1 query to load N parent rows, and then 1 extra query for each parent to load a lazy relation: N+1 queries in total. Each query is fast, but hundreds of round trips make the page slow.

```java
List<Job> jobs = jobRepository.findTop50ByOrderByPostedAtDesc();   // 1 query
for (Job job : jobs) {
    System.out.println(job.getCompany().getName());                // +1 query per job (lazy company)
}
```

```text
select * from jobs order by posted_at desc limit 50
select * from companies where id=?
select * from companies where id=?
... 50 times
```

## How to detect it
- Enable SQL logging (spring.jpa.show-sql=true) or use a datasource proxy / p6spy.
- Hibernate statistics (generate_statistics=true) show query counts per request.
- APM tools (New Relic, Datadog) show many identical queries per request.
- Tests asserting the number of queries.

## Fixes
1. JOIN FETCH:
```java
@Query("SELECT j FROM Job j JOIN FETCH j.company ORDER BY j.postedAt DESC")
List<Job> latestWithCompany(Pageable pageable);
```
2. @EntityGraph:
```java
@EntityGraph(attributePaths = "company")
List<Job> findTop50ByOrderByPostedAtDesc();
```
3. Batch fetching: hibernate.default_batch_fetch_size=50 turns N queries into about N/50 IN queries.
4. DTO projection – select only the columns you need in one query:
```java
@Query("SELECT new com.portal.JobRow(j.id, j.title, c.name) FROM Job j JOIN j.company c")
List<JobRow> jobRows();
```

## Result
50 jobs: 51 queries → 1 query (JOIN FETCH or projection) or 2 queries (batch fetching).

Note: the same problem exists in Laravel (Eloquent with()), Django (select_related) and any ORM – the fix is always "load relations in bulk".
MD,

'What is cascade?' => <<<'MD'
## What it is
Cascade makes an operation on a parent entity automatically apply to its related child entities. For example, saving a Resume also saves its Education and Experience entries; deleting it deletes them.

```java
@Entity
public class Resume {
    @Id @GeneratedValue private Long id;

    @OneToMany(mappedBy = "resume", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<Experience> experiences = new ArrayList<>();

    public void addExperience(Experience e) { experiences.add(e); e.setResume(this); }
    public void removeExperience(Experience e) { experiences.remove(e); e.setResume(null); }
}

@Entity
public class Experience {
    @Id @GeneratedValue private Long id;
    @ManyToOne(fetch = FetchType.LAZY) private Resume resume;
    private String company;
}
```

```java
Resume r = new Resume();
r.addExperience(new Experience("Infosys"));
r.addExperience(new Experience("TCS"));
resumeRepository.save(r);      // inserts the resume and both experiences (PERSIST cascades)
```

## Cascade types
- PERSIST – saving the parent saves new children.
- MERGE – merging the parent merges children.
- REMOVE – deleting the parent deletes children.
- REFRESH, DETACH – reload or detach children with the parent.
- ALL – all of the above.

## orphanRemoval
orphanRemoval = true deletes a child that is removed from the collection (r.removeExperience(e) → DELETE). Cascade REMOVE only acts when the parent itself is deleted.

## When to use cascade
- Use it for true composition: the child cannot exist without the parent (order → order lines, resume → experiences).
- Do not cascade to shared or independent entities: cascading REMOVE from Job to Company could delete a company used by other jobs.

## Database-level alternative
ON DELETE CASCADE foreign keys delete children in the database itself; JPA cascades run in the application and fire entity callbacks.

Note: cascade = REMOVE on a large collection loads and deletes children one by one; for bulk deletes use a JPQL DELETE query.
MD,

'What is @Transactional?' => <<<'MD'
## What it is
@Transactional makes a method run inside a database transaction: all its database operations commit together, or all roll back if an exception occurs (atomicity). Spring implements it with a proxy that opens the transaction before the method and commits or rolls back after it.

## Example: money transfer
```java
@Service
public class TransferService {

    private final AccountRepository accounts;
    private final LedgerRepository ledger;

    public TransferService(AccountRepository accounts, LedgerRepository ledger) {
        this.accounts = accounts; this.ledger = ledger;
    }

    @Transactional
    public void transfer(Long fromId, Long toId, BigDecimal amount) {
        Account from = accounts.findByIdForUpdate(fromId);    // SELECT ... FOR UPDATE
        Account to = accounts.findByIdForUpdate(toId);
        if (from.getBalance().compareTo(amount) < 0) throw new InsufficientBalanceException(fromId);
        from.debit(amount);
        to.credit(amount);
        ledger.save(new LedgerEntry(fromId, toId, amount));
    }   // commit; any RuntimeException above → full rollback
}
```

## How it works (proxy)
```text
caller → Spring proxy → begin transaction → TransferService.transfer() → commit / rollback
```

## Rollback rules
- Rolls back on unchecked exceptions (RuntimeException, Error) by default.
- Does not roll back on checked exceptions unless configured: @Transactional(rollbackFor = Exception.class).
- Catching an exception inside the method and not rethrowing it means the transaction commits.

## Important attributes
- readOnly = true – optimisations for queries (no dirty checking flush).
- propagation – REQUIRED (default: join or create), REQUIRES_NEW (always a new, independent transaction, e.g. audit logs), MANDATORY, NESTED, etc.
- isolation – READ_COMMITTED, REPEATABLE_READ (MySQL default), SERIALIZABLE.
- timeout – seconds before rollback.

## Common pitfalls
- Self-invocation: calling a @Transactional method from another method of the same class bypasses the proxy, so no transaction starts.
- Private methods are not proxied.
- Calling external APIs (payment gateway, email) inside a long transaction holds database locks; do remote calls outside, or use an outbox/event after commit.

```java
@TransactionalEventListener(phase = TransactionPhase.AFTER_COMMIT)
public void onTransferCompleted(TransferCompleted e) { smsService.sendDebitAlert(e); }
```

Note: put @Transactional on service methods (business operations), not on controllers or repositories.
MD,

];
