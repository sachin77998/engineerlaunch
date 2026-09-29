<?php

$lesson = function (
    string $title,
    string $summary,
    string $code,
    string $task,
    string $correct,
    string $realWorld = '',
    string $howItWorks = '',
    array $interviewPoints = []
): array {
    return [
        'title' => $title,

        'summary' => $summary,

        'real_world' => $realWorld,

        'how_it_works' => $howItWorks,

        'interview_points' => $interviewPoints,

        'code' => $code,

        'task' => $task,

        'options' => [
            $correct,
            'It is only a frontend feature and does not run inside the Spring container.',
            'It always requires XML configuration and an external application server.',
            'It disables dependency injection and database transactions.',
        ],

        'answer' => 0,

        'why' => $correct,
    ];
};

return [

    $lesson(
        'What is Spring Framework?',

        'Spring Framework provides the infrastructure for enterprise Java applications. It provides IoC, dependency injection, AOP, transactions, MVC, REST, security and data-access capabilities.',

        <<<'JAVA'
@Configuration
@ComponentScan("com.ascendia")
public class ApplicationConfig {

}
JAVA,

        'Create a Spring configuration class and register one service as a Spring bean.',

        'Spring is an enterprise Java framework centered around IoC and dependency injection.',

        'In a job portal, Spring can manage controllers, services, repositories, authentication, database transactions and other application components.',

        <<<'TEXT'
Application starts
        ↓
Spring creates ApplicationContext
        ↓
Component scanning
        ↓
Beans are discovered
        ↓
Dependencies are injected
        ↓
Application is ready
TEXT,

        [
            'Spring provides the IoC container.',
            'Spring supports dependency injection.',
            'Spring provides modules for web, data access, security and transactions.',
            'Spring is the foundation on which Spring Boot applications are built.',
        ]
    ),

    $lesson(
        'What is Spring Boot?',

        'Spring Boot builds on Spring and reduces application setup through auto-configuration, starter dependencies, embedded servers and production-ready conventions.',

        <<<'JAVA'
@SpringBootApplication
public class Application {

    public static void main(String[] args) {
        SpringApplication.run(Application.class, args);
    }
}
JAVA,

        'Generate a Spring Boot project with Spring Initializr and start it from the main method.',

        'Spring Boot simplifies Spring application development using conventions, auto-configuration, starter dependencies and embedded runtime support.',

        'A job portal can use Spring Boot to expose APIs such as /api/jobs, /api/companies and /api/applications without manually configuring an external application server.',

        <<<'TEXT'
main()
   ↓
SpringApplication.run()
   ↓
SpringApplication
   ↓
ApplicationContext
   ↓
Auto Configuration
   ↓
Component Scanning
   ↓
Spring Boot Application
TEXT,

        [
            'Spring Boot is built on Spring.',
            '@SpringBootApplication is commonly used as the main application annotation.',
            'Spring Boot supports embedded servers.',
            'Spring Boot reduces manual configuration.',
        ]
    ),

    $lesson(
        'Spring versus Spring Boot',

        'Spring provides the core framework and infrastructure. Spring Boot provides opinionated defaults, starter dependencies, auto-configuration, embedded servers and faster application bootstrap.',

        <<<'GRADLE'
implementation(
    "org.springframework.boot:spring-boot-starter-web"
)
GRADLE,

        'Create a traditional Spring MVC application and compare its configuration with a Spring Boot application.',

        'Spring provides the underlying framework, while Spring Boot simplifies configuration and application startup on top of Spring.',

        'For a large recruitment platform, Spring Boot can reduce the amount of infrastructure configuration required when creating services for jobs, candidates, companies and applications.',

        <<<'TEXT'
Spring
  ↓
Core container
  ↓
Manual configuration
  ↓
Application setup

Spring Boot
  ↓
Spring
  ↓
Auto configuration
  ↓
Starters
  ↓
Embedded server
  ↓
Application
TEXT,

        [
            'Spring is the underlying framework.',
            'Spring Boot uses Spring internally.',
            'Boot provides auto-configuration.',
            'Boot provides starter dependencies.',
            'Boot commonly runs with an embedded server.',
        ]
    ),

    $lesson(
        'What does @SpringBootApplication do?',

        '@SpringBootApplication combines @Configuration, @EnableAutoConfiguration and @ComponentScan.',

        <<<'JAVA'
@SpringBootApplication
public class JobsApi {

    public static void main(String[] args) {
        SpringApplication.run(
            JobsApi.class,
            args
        );
    }
}
JAVA,

        'Create a Spring Boot application and verify that controllers and services inside the application package are discovered.',

        '@SpringBootApplication combines configuration, auto-configuration and component scanning.',

        'When the recruitment application starts, Spring Boot can discover JobController, JobService and JobRepository automatically when they are located under the appropriate package hierarchy.',

        <<<'TEXT'
@SpringBootApplication
        ↓
@Configuration
        +
@EnableAutoConfiguration
        +
@ComponentScan
        ↓
Spring ApplicationContext
TEXT,

        [
            '@Configuration defines configuration.',
            '@EnableAutoConfiguration enables conditional Boot configuration.',
            '@ComponentScan discovers Spring components.',
            'The annotation is normally placed on the main application class.',
        ]
    ),

    $lesson(
        'What is auto-configuration?',

        'Spring Boot examines the classpath, configuration properties and existing beans and conditionally creates infrastructure required by the application.',

        <<<'JAVA'
@Bean
@ConditionalOnMissingBean
Clock applicationClock() {
    return Clock.systemUTC();
}
JAVA,

        'Add JDBC to a Spring Boot application and inspect which DataSource-related configuration is automatically created.',

        'Auto-configuration conditionally supplies Spring beans based on dependencies, properties and beans already defined by the application.',

        'If a recruitment application includes the appropriate database dependencies and configuration, Spring Boot can automatically configure the infrastructure required to connect to the database.',

        <<<'TEXT'
Dependencies
     ↓
Classpath inspection
     ↓
Application properties
     ↓
Existing beans
     ↓
Conditional configuration
     ↓
Required Spring infrastructure
TEXT,

        [
            'Auto-configuration is conditional.',
            'Classpath dependencies influence configuration.',
            'Application properties influence configuration.',
            'Existing user-defined beans can affect auto-configuration.',
        ]
    ),

    $lesson(
        'What are Spring Boot starters?',

        'Starters are curated dependency bundles for capabilities such as web applications, JPA, security and testing.',

        <<<'GRADLE'
implementation(
    "org.springframework.boot:spring-boot-starter-web"
)

implementation(
    "org.springframework.boot:spring-boot-starter-data-jpa"
)

testImplementation(
    "org.springframework.boot:spring-boot-starter-test"
)
GRADLE,

        'Replace individual web dependencies with spring-boot-starter-web and inspect the dependency tree.',

        'A Spring Boot starter is a managed dependency bundle designed for a specific application capability.',

        'A recruitment API can use starter-web for REST endpoints, starter-data-jpa for database persistence and starter-security for authentication and authorization.',

        <<<'TEXT'
starter-web
    ↓
Spring MVC
    +
HTTP support
    +
JSON support
    +
Embedded web server support

starter-data-jpa
    ↓
Spring Data JPA
    +
JPA
    +
Hibernate
TEXT,

        [
            'Starters simplify dependency management.',
            'Starters provide related dependencies for a capability.',
            'Spring Boot manages compatible dependency versions.',
            'Common starters include web, data-jpa, security and test.',
        ]
    ),

    $lesson(
        'What is an embedded server?',

        'A Spring Boot web application can package a web server such as Tomcat and run as an executable JAR without requiring a separately installed application server.',

        <<<'BASH'
./mvnw clean package

java -jar target/jobs-api.jar
BASH,

        'Build the application as an executable JAR and start it on port 8081.',

        'An embedded server is packaged with the Spring Boot application and starts when the executable application starts.',

        'A recruitment API can be packaged into a JAR and deployed to a server or container. The application starts its embedded HTTP server automatically.',

        <<<'TEXT'
jobs-api.jar
    ↓
Spring Boot
    ↓
Embedded Tomcat
    ↓
HTTP Port 8081
    ↓
REST Controllers
TEXT,

        [
            'Spring Boot commonly uses embedded Tomcat.',
            'An application can run as an executable JAR.',
            'A separate application-server installation is not required for the common embedded-server model.',
            'The server starts with the Spring Boot application.',
        ]
    ),

    $lesson(
        'What is Inversion of Control?',

        'Inversion of Control means the Spring container manages object creation, configuration and dependency relationships instead of application code manually constructing the dependency graph.',

        <<<'JAVA'
@Service
public class JobService {

    private final JobRepository jobs;

    public JobService(JobRepository jobs) {
        this.jobs = jobs;
    }
}
JAVA,

        'Remove a manual new JobRepository() call and allow Spring to supply the dependency.',

        'IoC transfers object creation and lifecycle management from application code to the Spring container.',

        'In a recruitment application, JobController does not need to manually create JobService. Spring creates the service and injects it into the controller.',

        <<<'TEXT'
Spring Container
       ↓
Creates JobRepository
       ↓
Creates JobService
       ↓
Injects JobRepository
       ↓
Creates JobController
       ↓
Injects JobService
TEXT,

        [
            'IoC is a core Spring concept.',
            'The Spring container manages objects.',
            'Dependencies are supplied by the container.',
            'IoC reduces manual object construction.',
        ]
    ),

    $lesson(
        'What is Dependency Injection?',

        'Dependency Injection supplies required collaborators to a class. Constructor injection makes required dependencies explicit and easier to test.',

        <<<'JAVA'
@RestController
public class JobController {

    private final JobService service;

    public JobController(JobService service) {
        this.service = service;
    }
}
JAVA,

        'Unit-test the controller by passing a mocked JobService into its constructor.',

        'Dependency Injection allows Spring to provide JobService to JobController instead of JobController creating JobService itself.',

        'When a candidate searches jobs, JobController can call JobService. JobService can use JobRepository. Each dependency is provided by Spring.',

        <<<'TEXT'
JobController
     |
     | JobService injected
     ↓
JobService
     |
     | JobRepository injected
     ↓
JobRepository
     |
     ↓
Database
TEXT,

        [
            'Constructor injection is preferred for required dependencies.',
            'Dependency Injection reduces tight coupling.',
            'Injected dependencies are easier to mock during testing.',
            'Spring manages the dependency graph.',
        ]
    ),

    $lesson(
        'What is a Spring Bean?',

        'A Spring bean is an object created and managed by the Spring ApplicationContext, including its dependencies, scope and lifecycle.',

        <<<'JAVA'
@Component
public class SalaryCalculator {

    public BigDecimal annual(
        BigDecimal monthly
    ) {
        return monthly.multiply(
            BigDecimal.valueOf(12)
        );
    }
}
JAVA,

        'Create a SalaryCalculator bean and retrieve it from a Spring application-context test.',

        'A Spring bean is an object whose creation and lifecycle are managed by the Spring IoC container.',

        'A JobService, JobRepository, EmailService or PaymentService can be a Spring bean and can receive other beans through dependency injection.',

        <<<'TEXT'
@Component
      ↓
Component Scan
      ↓
Bean Definition
      ↓
ApplicationContext
      ↓
Bean Instance
      ↓
Dependency Injection
TEXT,

        [
            'Beans are managed by ApplicationContext.',
            '@Component can register a class as a bean.',
            '@Service and @Repository are specialized stereotype annotations.',
            'Beans can have different scopes.',
        ]
    ),

    $lesson(
        '@Component, @Service and @Repository',

        '@Component is a generic stereotype. @Service communicates business logic. @Repository identifies persistence components and participates in persistence exception translation.',

        <<<'JAVA'
@Repository
public interface JobRepository
        extends JpaRepository<Job, Long> {
}

@Service
public class JobService {
}

@Component
public class JobSearchHelper {
}
JAVA,

        'Create the controller, service and repository layers for a small job-search use case.',

        'These annotations register Spring components while communicating their intended application layer.',

        'A job portal can organize its backend into Controller → Service → Repository. This makes responsibilities easier to maintain.',

        <<<'TEXT'
@RestController
      ↓
@Service
      ↓
@Repository
      ↓
Database
TEXT,

        [
            '@Component is the generic stereotype.',
            '@Service is normally used for business logic.',
            '@Repository is normally used for persistence.',
            'All three participate in component scanning.',
        ]
    ),

    $lesson(
        'What is @Controller?',

        '@Controller is used in Spring MVC when handler methods normally resolve views or templates. A method can also use @ResponseBody when it should return serialized data.',

        <<<'JAVA'
@Controller
public class HomeController {

    @GetMapping("/")
    public String home() {
        return "home";
    }
}
JAVA,

        'Create a controller that returns a Thymeleaf home view with one model attribute.',

        '@Controller marks a class as a Spring MVC controller where handler methods can return view names.',

        'A career portal can use @Controller for server-rendered pages such as the home page, job listing page or company profile page.',

        <<<'TEXT'
Browser
   ↓
@Controller
   ↓
@GetMapping
   ↓
Model
   ↓
View Template
   ↓
HTML
TEXT,

        [
            '@Controller is part of Spring MVC.',
            'It is commonly used for server-side rendered pages.',
            'A method can use @ResponseBody for response-body output.',
            '@RestController is commonly used for REST APIs.',
        ]
    ),

    $lesson(
        'What is @RestController?',

        '@RestController combines @Controller and @ResponseBody so handler return values are written directly to the HTTP response body.',

        <<<'JAVA'
@RestController
@RequestMapping("/api/jobs")
public class JobController {

    @GetMapping
    public List<JobDto> index() {
        return List.of();
    }
}
JAVA,

        'Create a GET endpoint and verify that the response is returned as JSON.',

        '@RestController is used for REST endpoints whose return values are normally serialized into HTTP responses.',

        'A job portal frontend can call /api/jobs and receive JSON containing job records.',

        <<<'TEXT'
React / Angular
       ↓
GET /api/jobs
       ↓
@RestController
       ↓
JobService
       ↓
JobRepository
       ↓
JSON Response
TEXT,

        [
            '@RestController combines @Controller and @ResponseBody.',
            'It is commonly used for REST APIs.',
            'Jackson commonly serializes Java objects to JSON.',
            'REST controllers normally return DTOs rather than database entities.',
        ]
    ),

    $lesson(
        'What is @RequestMapping?',

        '@RequestMapping defines request-matching conditions such as URI, HTTP method, headers, consumes and produces.',

        <<<'JAVA'
@RestController
@RequestMapping(
    value = "/api/v1/jobs",
    produces = MediaType.APPLICATION_JSON_VALUE
)
public class JobController {

}
JAVA,

        'Create a shared /api/v1/jobs prefix for multiple job endpoints.',

        '@RequestMapping establishes reusable request-matching conditions at class or method level.',

        'A job API can use /api/v1/jobs as the common endpoint and then expose /api/v1/jobs/{id}, /api/v1/jobs/search and other routes.',

        <<<'TEXT'
/api/v1/jobs
      ↓
-------------------------
|          |            |
GET /      GET /{id}    POST /
-------------------------
      ↓
JobController
TEXT,

        [
            '@RequestMapping can be used at class level.',
            '@RequestMapping can be used at method level.',
            'It can define paths and HTTP methods.',
            'It can define produces and consumes conditions.',
        ]
    ),

    $lesson(
        '@GetMapping versus @PostMapping',

        '@GetMapping handles GET requests used to retrieve resources. @PostMapping handles POST requests commonly used to create resources or trigger processing.',

        <<<'JAVA'
@GetMapping
public List<JobDto> all() {
    return service.all();
}

@PostMapping
public ResponseEntity<JobDto> create(
    @Valid @RequestBody CreateJobRequest body
) {
    JobDto job = service.create(body);

    return ResponseEntity
        .status(201)
        .body(job);
}
JAVA,

        'Implement GET and POST endpoints for a jobs collection.',

        '@GetMapping maps GET requests while @PostMapping maps POST requests.',

        'The frontend can use GET /api/jobs to search jobs and POST /api/jobs when an authorized recruiter creates a new job.',

        <<<'TEXT'
GET /api/jobs
     ↓
Read jobs

POST /api/jobs
     ↓
Validate request
     ↓
Create job
     ↓
Save database record
     ↓
Return 201
TEXT,

        [
            'GET is normally used to retrieve resources.',
            'POST is commonly used to create resources.',
            'GET should normally be safe.',
            'POST can change server state.',
        ]
    ),

    $lesson(
        'What is @PathVariable?',

        '@PathVariable binds a value from a URI template to a controller method parameter.',

        <<<'JAVA'
@GetMapping("/{id}")
public JobDto show(
    @PathVariable Long id
) {
    return service.find(id);
}
JAVA,

        'Create an endpoint for /api/jobs/42 and return 404 when the job does not exist.',

        '@PathVariable reads a value embedded inside the request URL.',

        'When a candidate opens /api/jobs/1250, Spring binds 1250 to the id parameter.',

        <<<'TEXT'
GET /api/jobs/1250
        ↓
{id}
        ↓
@PathVariable Long id
        ↓
service.find(1250)
        ↓
Database
TEXT,

        [
            '@PathVariable reads URI values.',
            'It is commonly used for resource identifiers.',
            'The variable name can be mapped explicitly.',
            'Missing resources should normally result in 404.',
        ]
    ),

    $lesson(
        'What is @RequestParam?',

        '@RequestParam binds query-string parameters or form parameters and can define defaults and optional values.',

        <<<'JAVA'
@GetMapping
public Page<JobDto> search(
    @RequestParam String skill,
    @RequestParam(defaultValue = "0")
    int page
) {
    return service.search(skill, page);
}
JAVA,

        'Support /api/jobs?skill=java&page=0.',

        '@RequestParam reads named request parameters from the URL query string.',

        'A candidate can search /api/jobs?skill=java&location=bangalore and Spring can bind those values to controller parameters.',

        <<<'TEXT'
/api/jobs
   ?
skill=java
   &
location=bangalore
   &
page=0
        ↓
@RequestParam
        ↓
Search Service
        ↓
Database
TEXT,

        [
            '@RequestParam reads query parameters.',
            'It can define default values.',
            'Parameters can be optional.',
            'It is useful for filtering and pagination.',
        ]
    ),

    $lesson(
        'What is @RequestBody?',

        '@RequestBody asks Spring HTTP message converters to deserialize the request body into a Java object.',

        <<<'JAVA'
public record CreateJobRequest(
    @NotBlank String title,
    @NotBlank String location
) {
}

@PostMapping
public JobDto create(
    @Valid @RequestBody CreateJobRequest request
) {
    return service.create(request);
}
JAVA,

        'Submit valid and invalid JSON to a POST endpoint and compare the responses.',

        '@RequestBody converts the HTTP request body into the Java request object expected by the controller.',

        'When an HR user posts a new job containing title, location, salary and experience, Spring converts the JSON request into a Java DTO.',

        <<<'TEXT'
JSON Request
     ↓
@RequestBody
     ↓
Jackson
     ↓
CreateJobRequest
     ↓
@Valid
     ↓
JobService
TEXT,

        [
            '@RequestBody reads the HTTP body.',
            'Jackson commonly performs JSON deserialization.',
            '@Valid can validate the resulting DTO.',
            'DTOs are preferable to exposing persistence entities directly.',
        ]
    ),

    $lesson(
        'What is @ResponseBody?',

        '@ResponseBody writes a controller method return value directly to the HTTP response body using Spring message converters.',

        <<<'JAVA'
@Controller
public class HealthController {

    @ResponseBody
    @GetMapping("/health-text")
    public Map<String, String> health() {

        return Map.of(
            "status",
            "UP"
        );
    }
}
JAVA,

        'Return a JSON health response from a regular @Controller.',

        '@ResponseBody tells Spring to serialize the method return value as the HTTP response body instead of treating it as a view name.',

        'A health endpoint can return application status as JSON for monitoring systems.',

        <<<'TEXT'
GET /health-text
       ↓
@Controller
       ↓
@ResponseBody
       ↓
Map
       ↓
JSON
       ↓
HTTP Response
TEXT,

        [
            '@ResponseBody writes data to the HTTP response body.',
            'It is commonly used for JSON responses.',
            '@RestController includes @ResponseBody behavior by default.',
            'Message converters handle serialization.',
        ]
    ),

    $lesson(
        'What is @Qualifier?',

        '@Qualifier selects a specific bean when multiple beans implement the same interface.',

        <<<'JAVA'
public interface PaymentGateway {
}

@Component("stripe")
public class StripeGateway
        implements PaymentGateway {
}

@Component("razorpay")
public class RazorpayGateway
        implements PaymentGateway {
}

@Service
public class BillingService {

    private final PaymentGateway gateway;

    public BillingService(
        @Qualifier("stripe")
        PaymentGateway gateway
    ) {
        this.gateway = gateway;
    }
}
JAVA,

        'Create two payment gateway beans and inject one specific implementation using @Qualifier.',

        '@Qualifier disambiguates multiple beans of the same dependency type.',

        'A recruitment platform could have multiple notification providers or payment providers and choose a particular implementation using a qualifier.',

        <<<'TEXT'
PaymentGateway
      ↓
------------------------
|                      |
StripeGateway      RazorpayGateway
|                      |
"stripe"            "razorpay"
          ↓
      @Qualifier
          ↓
    BillingService
TEXT,

        [
            '@Qualifier identifies a specific bean.',
            'It is useful when multiple beans have the same type.',
            'Bean names can be used with qualifiers.',
            '@Primary can provide a default candidate.',
        ]
    ),

    $lesson(
        'What are bean scopes?',

        'Bean scope controls the lifetime and visibility of Spring bean instances. Common scopes include singleton, prototype, request, session and application.',

        <<<'JAVA'
@RequestScope
@Component
public class RequestAuditContext {

}
JAVA,

        'Compare singleton and prototype beans by creating multiple container lookups.',

        'Bean scope determines how Spring creates and manages bean instances.',

        'A request-scoped bean can contain information specific to one HTTP request while a singleton service can be shared across requests.',

        <<<'TEXT'
Singleton
ApplicationContext
      ↓
One instance

Prototype
      ↓
New instance
per lookup

Request
      ↓
One instance
per HTTP request
TEXT,

        [
            'Singleton is the default scope.',
            'Prototype creates a new instance for each container lookup.',
            'Request scope is associated with an HTTP request.',
            'Scope should match the required lifecycle.',
        ]
    ),

    $lesson(
        'What is a singleton bean?',

        'Singleton is the default Spring bean scope. One bean instance exists per ApplicationContext.',

        <<<'JAVA'
@Service
public class JobSearchService {

    public List<Job> search(String keyword) {
        // Do not store request-specific state here.
        return List.of();
    }
}
JAVA,

        'Send concurrent requests and verify that request-specific state is not stored in a singleton service.',

        'A singleton bean has one instance per Spring ApplicationContext.',

        'A JobSearchService can safely be a singleton when it keeps request-specific data inside method-local variables instead of instance fields.',

        <<<'TEXT'
ApplicationContext
        ↓
JobSearchService
        ↓
ONE INSTANCE
        ↓
Request 1
Request 2
Request 3
Request 4
TEXT,

        [
            'Singleton is the default Spring scope.',
            'There is one instance per ApplicationContext.',
            'Singleton beans must be designed safely for concurrent requests.',
            'Request-specific mutable state should not be stored in singleton fields.',
        ]
    ),

    $lesson(
        'What is a prototype bean?',

        'A prototype bean is created each time the Spring container resolves it. Spring does not manage the complete destruction lifecycle of a prototype bean after creation.',

        <<<'JAVA'
@Scope(ConfigurableBeanFactory.SCOPE_PROTOTYPE)
@Component
public class ImportWorkspace {

}
JAVA,

        'Request two prototype instances and verify that they are different objects.',

        'Prototype scope creates a new bean instance whenever the container performs a lookup or creates the relevant injection instance.',

        'A temporary import-processing object can use prototype scope when independent object instances are required.',

        <<<'TEXT'
ApplicationContext
       ↓
getBean()
       ↓
ImportWorkspace #1

getBean()
       ↓
ImportWorkspace #2

#1 != #2
TEXT,

        [
            'Prototype creates new instances.',
            'It differs from the default singleton scope.',
            'Spring does not fully manage destruction of prototype beans.',
            'Use prototype only when a different lifecycle is actually required.',
        ]
    ),

    $lesson(
        'What is @Primary?',

        '@Primary marks the default candidate when multiple beans match an injection point.',

        <<<'JAVA'
@Primary
@Component
public class DefaultTaxCalculator
        implements TaxCalculator {

}
JAVA,

        'Register default and international tax calculators and inject each implementation intentionally.',

        '@Primary tells Spring which bean should be preferred when multiple candidates of the same type exist.',

        'A financial module may contain multiple calculation strategies while one implementation is selected as the default.',

        <<<'TEXT'
TaxCalculator
      ↓
-------------------------
|                       |
DefaultTaxCalculator   InternationalTaxCalculator
      ↓
   @Primary
      ↓
Default injection
TEXT,

        [
            '@Primary selects the preferred candidate.',
            'It is useful when multiple beans have the same type.',
            '@Qualifier can explicitly select another bean.',
            '@Primary does not remove other beans.',
        ]
    ),

    $lesson(
        'What is @Configuration?',

        '@Configuration declares a class containing Spring bean definitions.',

        <<<'JAVA'
@Configuration
public class ClientConfig {

    @Bean
    public Clock clock() {
        return Clock.systemUTC();
    }
}
JAVA,

        'Create a configuration class that exposes a Clock bean and inject it into a service.',

        '@Configuration is used to define Spring-managed bean configuration.',

        'A recruitment application can define third-party API clients, clocks, object mappers or custom service objects inside configuration classes.',

        <<<'TEXT'
@Configuration
      ↓
@Bean methods
      ↓
Bean Definitions
      ↓
ApplicationContext
      ↓
Injected into services
TEXT,

        [
            '@Configuration defines bean configuration.',
            '@Bean methods register returned objects.',
            'Configuration classes are managed by Spring.',
            'They are useful for third-party classes that cannot be annotated.',
        ]
    ),

    $lesson(
        'What is @Bean?',

        '@Bean registers the object returned by a configuration method as a Spring-managed bean.',

        <<<'JAVA'
@Configuration
public class AppConfig {

    @Bean
    public ObjectMapper objectMapper() {
        return JsonMapper.builder()
            .findAndAddModules()
            .build();
    }
}
JAVA,

        'Configure and inject a third-party ObjectMapper using a @Bean method.',

        '@Bean explicitly registers a method return value in the Spring container.',

        'If an external library class cannot be modified with @Component, a configuration class can expose it through @Bean.',

        <<<'TEXT'
@Configuration
      ↓
@Bean
      ↓
ObjectMapper created
      ↓
ApplicationContext
      ↓
Injected into application
TEXT,

        [
            '@Bean registers an object with Spring.',
            'It is commonly used for third-party classes.',
            'The returned object becomes a Spring bean.',
            'The bean can then be injected into other components.',
        ]
    ),

    $lesson(
        'What is component scanning?',

        'Component scanning discovers classes annotated with Spring stereotypes within configured packages and registers them as bean definitions.',

        <<<'JAVA'
@ComponentScan({
    "com.ascendia.jobs",
    "com.ascendia.shared"
})
public class ApplicationConfig {

}
JAVA,

        'Move a service outside the scanned package, observe the failure and correct the package configuration.',

        'Component scanning searches configured packages for Spring components and registers them with the container.',

        'A large recruitment application can organize components into packages such as jobs, candidates, companies, applications and authentication.',

        <<<'TEXT'
ComponentScan
      ↓
com.ascendia.jobs
      ↓
Controllers
Services
Repositories
      ↓
Spring Beans
TEXT,

        [
            'Component scanning discovers annotated classes.',
            'Package boundaries matter.',
            '@SpringBootApplication includes component scanning.',
            'Incorrect package placement can cause missing-bean errors.',
        ]
    ),

    $lesson(
        'What is the bean lifecycle?',

        'Spring creates a bean, injects dependencies, executes initialization callbacks, makes the bean available and eventually invokes destruction callbacks during context shutdown.',

        <<<'JAVA'
@PostConstruct
void initialize() {
}

@PreDestroy
void close() {
}
JAVA,

        'Log the lifecycle phases of a bean during application startup and shutdown.',

        'The Spring container coordinates bean creation, dependency injection, initialization and destruction.',

        'A service that creates an internal resource can initialize it after dependencies are available and release it during application shutdown.',

        <<<'TEXT'
Bean Definition
      ↓
Instantiation
      ↓
Dependency Injection
      ↓
Initialization
      ↓
Bean Ready
      ↓
Application Running
      ↓
Shutdown
      ↓
Destruction
TEXT,

        [
            'Spring manages bean creation.',
            'Dependencies are injected during lifecycle processing.',
            '@PostConstruct can perform initialization.',
            '@PreDestroy can perform cleanup.',
        ]
    ),

    $lesson(
        'What is @PostConstruct?',

        '@PostConstruct marks a method that runs after dependency injection and before normal bean use.',

        <<<'JAVA'
@PostConstruct
void validateConfiguration() {

    Assert.hasText(
        apiUrl,
        "apiUrl is required"
    );
}
JAVA,

        'Validate one required application property during bean initialization.',

        '@PostConstruct is useful for initialization or validation that requires injected dependencies and configuration.',

        'A job aggregation service can validate its configured external API URL when the application starts rather than waiting for the first user request.',

        <<<'TEXT'
Bean created
    ↓
Dependencies injected
    ↓
@PostConstruct
    ↓
Validation
    ↓
Bean ready
TEXT,

        [
            '@PostConstruct executes after dependency injection.',
            'It should normally perform short initialization work.',
            'Configuration validation can be performed there.',
            'Initialization failures can prevent proper application startup.',
        ]
    ),

    $lesson(
        'What is @PreDestroy?',

        '@PreDestroy marks cleanup logic that runs when a managed bean is destroyed during an orderly application shutdown.',

        <<<'JAVA'
@PreDestroy
void shutdown() {
    executor.shutdown();
}
JAVA,

        'Create a managed executor and shut it down when the Spring context closes.',

        '@PreDestroy is used for cleanup before a managed bean is destroyed.',

        'A notification service, scheduler or resource manager can release resources during application shutdown.',

        <<<'TEXT'
Application Running
       ↓
Shutdown signal
       ↓
Spring Context closes
       ↓
@PreDestroy
       ↓
Cleanup
       ↓
Bean destroyed
TEXT,

        [
            '@PreDestroy is used for cleanup.',
            'It runs during managed bean destruction.',
            'It is commonly used for resources that need orderly shutdown.',
            'Cleanup code should be reliable and focused.',
        ]
    ),

    $lesson(
        'How do you create a REST API?',

        'A maintainable REST API separates transport DTOs, validation, business logic and persistence while using resource-oriented routes and explicit HTTP responses.',

        <<<'JAVA'
@RestController
@RequestMapping("/api/v1/jobs")
public class JobController {

    @GetMapping
    public Page<JobDto> index(
        Pageable pageable
    ) {
        return service.index(pageable);
    }
}
JAVA,

        'Build a paginated jobs endpoint without returning JPA entities directly.',

        'A REST API exposes resources through HTTP endpoints and should separate controller, service, DTO and persistence responsibilities.',

        'A career portal can expose APIs for jobs, candidates, companies, applications and saved jobs to React or Angular clients.',

        <<<'TEXT'
Frontend
   ↓
REST Controller
   ↓
Request DTO
   ↓
Validation
   ↓
Service
   ↓
Repository
   ↓
Database
   ↓
Response DTO
   ↓
Frontend
TEXT,

        [
            'Use resource-oriented endpoints.',
            'Use DTOs at the API boundary.',
            'Validate incoming requests.',
            'Keep business logic inside services.',
            'Do not expose persistence entities unnecessarily.',
        ]
    ),

    $lesson(
        'What are HTTP methods?',

        'GET reads resources, POST creates resources or triggers processing, PUT replaces a resource, PATCH partially updates a resource and DELETE removes a resource.',

        <<<'HTTP'
GET    /api/jobs
POST   /api/jobs
PUT    /api/jobs/42
PATCH  /api/jobs/42
DELETE /api/jobs/42
HTTP,

        'Design CRUD routes for job applications and identify which operations should be idempotent.',

        'HTTP methods communicate the intended operation on a resource.',

        'A job portal can use GET for searching jobs, POST for applying, PATCH for changing application status and DELETE for removing a saved job.',

        <<<'TEXT'
GET
 ↓
Read

POST
 ↓
Create / Process

PUT
 ↓
Replace

PATCH
 ↓
Partial Update

DELETE
 ↓
Delete
TEXT,

        [
            'GET retrieves resources.',
            'POST commonly creates resources.',
            'PUT represents replacement.',
            'PATCH represents partial modification.',
            'DELETE removes a resource.',
        ]
    ),

    $lesson(
        'PUT versus PATCH',

        'PUT represents a complete replacement while PATCH applies a partial modification to an existing resource.',

        <<<'JAVA'
@PatchMapping("/{id}")
public JobDto updateStatus(
    @PathVariable Long id,
    @RequestBody UpdateStatusRequest request
) {
    return service.updateStatus(
        id,
        request
    );
}
JAVA,

        'Implement a PATCH endpoint that changes only the application status.',

        'PUT is used for replacement while PATCH is used when only selected fields need to change.',

        'When an HR recruiter changes an application from SHORTLISTED to INTERVIEW, PATCH can update only the status instead of sending the entire application object.',

        <<<'TEXT'
Existing Application
        ↓
PATCH
        ↓
status = INTERVIEW
        ↓
Updated Application

Other fields remain unchanged
TEXT,

        [
            'PUT represents replacement.',
            'PATCH represents partial modification.',
            'PATCH is useful for status updates.',
            'The API should clearly define which fields can be patched.',
        ]
    ),

    $lesson(
        'What is ResponseEntity?',

        'ResponseEntity provides explicit control over an HTTP response status, headers and body.',

        <<<'JAVA'
URI location =
    URI.create(
        "/api/v1/jobs/" + job.id()
    );

return ResponseEntity
    .created(location)
    .body(job);
JAVA,

        'Return 201 Created with a Location header after creating a job.',

        'ResponseEntity allows a controller to explicitly construct the HTTP response.',

        'When an HR user creates a new vacancy, the API can return 201 Created and provide the URL of the newly created job.',

        <<<'TEXT'
POST /api/jobs
      ↓
Create Job
      ↓
Database
      ↓
201 Created
      +
Location Header
      +
JSON Body
TEXT,

        [
            'ResponseEntity controls status.',
            'ResponseEntity can control headers.',
            'ResponseEntity can contain a response body.',
            'It is useful when an endpoint needs explicit HTTP behavior.',
        ]
    ),

    $lesson(
        'Which HTTP status codes should APIs use?',

        'Common API responses include 200 for successful requests, 201 for creation, 204 for success without a body, 400 for invalid requests, 401 for unauthenticated requests, 403 for forbidden requests, 404 for missing resources, 409 for conflicts and 500 for unexpected failures.',

        <<<'JAVA'
return ResponseEntity
    .noContent()
    .build();
JAVA,

        'Map five job-portal scenarios to appropriate HTTP status codes.',

        'HTTP status codes should describe the actual result of the request instead of returning 200 for every situation.',

        'A missing job should return 404, an invalid job request can return 400, an unauthenticated user can receive 401 and a duplicate application can return 409.',

        <<<'TEXT'
Request
  ↓
Controller
  ↓
Service
  ↓
Outcome
  ↓
-----------------------
200  Success
201  Created
204  No Content
400  Invalid Request
401  Unauthenticated
403  Forbidden
404  Not Found
409  Conflict
500  Server Error
TEXT,

        [
            '200 means successful request.',
            '201 means resource created.',
            '400 means invalid request.',
            '401 means authentication is required or invalid.',
            '403 means access is forbidden.',
            '404 means resource was not found.',
            '409 represents a conflict.',
        ]
    ),

    $lesson(
        'How do you validate API requests?',

        'Jakarta Bean Validation annotations define constraints while @Valid activates validation on a request DTO.',

        <<<'JAVA'
public record CreateUserRequest(

    @NotBlank
    String name,

    @Email
    String email

) {
}

@PostMapping
public UserDto create(
    @Valid
    @RequestBody
    CreateUserRequest request
) {
    return service.create(request);
}
JAVA,

        'Test blank names and malformed email addresses and inspect the validation response.',

        '@Valid triggers Bean Validation constraints on the request DTO.',

        'When an HR user creates a job, fields such as title, location and experience can be validated before business logic runs.',

        <<<'TEXT'
JSON
 ↓
@RequestBody
 ↓
DTO
 ↓
@Valid
 ↓
@NotBlank / @Email / @Min
 ↓
Valid?
 ↙       ↘
No        Yes
↓          ↓
400       Service
TEXT,

        [
            '@Valid activates validation.',
            'Bean Validation annotations define constraints.',
            'Validation belongs at the request boundary.',
            'Invalid requests should return a consistent 400 response.',
        ]
    ),

    $lesson(
        'What is global exception handling?',

        '@RestControllerAdvice centralizes exception-to-response mapping so REST controllers remain focused on request handling.',

        <<<'JAVA'
@RestControllerAdvice
public class ApiErrors {

    @ExceptionHandler(
        JobNotFoundException.class
    )
    ResponseEntity<ApiError> missing(
        JobNotFoundException ex
    ) {
        return ResponseEntity
            .status(404)
            .body(
                ApiError.of(
                    ex.getMessage()
                )
            );
    }
}
JAVA,

        'Handle validation, not-found and conflict errors through one centralized advice class.',

        '@RestControllerAdvice provides centralized exception handling across REST controllers.',

        'Instead of implementing identical try/catch logic in every job, company and candidate controller, the application can map common exceptions centrally.',

        <<<'TEXT'
Controller
    ↓
Service
    ↓
Exception
    ↓
@RestControllerAdvice
    ↓
@ExceptionHandler
    ↓
Standard JSON Error
TEXT,

        [
            '@RestControllerAdvice centralizes REST error handling.',
            '@ExceptionHandler maps exceptions.',
            'Controllers remain focused on request handling.',
            'Error responses should use a stable structure.',
        ]
    ),

    $lesson(
        'How do you create a custom exception?',

        'A domain-specific exception represents a meaningful application failure and can be mapped at the API boundary.',

        <<<'JAVA'
public class JobNotFoundException
        extends RuntimeException {

    public JobNotFoundException(Long id) {
        super(
            "Job " + id +
            " was not found"
        );
    }
}
JAVA,

        'Throw JobNotFoundException when a requested job does not exist.',

        'A custom exception gives a specific name and context to a business or domain failure.',

        'When a candidate opens a job that has been removed, the service can throw JobNotFoundException and the global exception handler can return 404.',

        <<<'TEXT'
GET /api/jobs/9999
       ↓
JobService
       ↓
Repository
       ↓
No Job
       ↓
JobNotFoundException
       ↓
@RestControllerAdvice
       ↓
404 JSON
TEXT,

        [
            'Custom exceptions represent meaningful application failures.',
            'They can extend RuntimeException.',
            'They should contain useful context.',
            'Global exception handling can map them to HTTP responses.',
        ]
    ),

    $lesson(
        'How should APIs return errors?',

        'A stable error contract can contain status, code, message, timestamp, path and optional field errors. Internal stack traces should not be exposed to clients.',

        <<<'JAVA'
public record ApiError(
    Instant timestamp,
    int status,
    String code,
    String message,
    String path
) {
}
JAVA,

        'Create one structured response for a missing job and another for invalid request fields.',

        'Consistent structured error responses make API clients easier to develop and prevent internal implementation details from leaking.',

        'The job portal frontend can display a clear message such as JOB_NOT_FOUND instead of receiving an unstructured server exception.',

        <<<'TEXT'
Exception
   ↓
Exception Handler
   ↓
ApiError
   ↓
{
  status,
  code,
  message,
  timestamp,
  path
}
   ↓
Frontend
TEXT,

        [
            'Use a stable error contract.',
            'Do not expose stack traces.',
            'Use machine-readable error codes.',
            'Include validation errors when appropriate.',
        ]
    ),

    $lesson(
        'What is API versioning?',

        'API versioning allows incompatible API contracts to coexist while clients migrate to a newer contract.',

        <<<'JAVA'
@RequestMapping("/api/v1/jobs")
public class JobV1Controller {

}
JAVA,

        'Introduce a v2 DTO without silently breaking existing v1 consumers.',

        'API versioning protects existing clients when breaking changes are introduced.',

        'A recruitment platform may keep /api/v1/jobs for an existing mobile application while introducing /api/v2/jobs for a newer frontend contract.',

        <<<'TEXT'
Mobile App
   ↓
/api/v1/jobs
   ↓
V1 Controller

New Web App
   ↓
/api/v2/jobs
   ↓
V2 Controller
TEXT,

        [
            'Versioning protects API consumers.',
            'URI versioning is one common strategy.',
            'Header and media-type versioning are alternatives.',
            'Breaking changes should not silently alter existing contracts.',
        ]
    ),

    $lesson(
        'What is JPA?',

        'Jakarta Persistence is a Java specification for mapping object state and relationships to relational database structures.',

        <<<'JAVA'
@Entity
public class Job {

    @Id
    @GeneratedValue
    private Long id;

    private String title;
}
JAVA,

        'Create a Job entity and persist it in an integration test.',

        'JPA defines persistence APIs and mappings. A provider such as Hibernate implements the persistence behavior.',

        'A job portal can map Job, Company, Candidate and Application Java objects to relational database tables.',

        <<<'TEXT'
Java Object
    ↓
JPA Mapping
    ↓
Entity
    ↓
JPA Provider
    ↓
SQL
    ↓
Database
TEXT,

        [
            'JPA is a specification.',
            '@Entity marks persistent classes.',
            '@Id defines entity identity.',
            'JPA providers implement the specification.',
        ]
    ),

    $lesson(
        'What is Hibernate?',

        'Hibernate is a widely used JPA implementation that provides ORM behavior, persistence context management, dirty checking, SQL generation and relationship loading.',

        <<<'PROPERTIES'
spring.jpa.show-sql=true
spring.jpa.properties.hibernate.format_sql=true
PROPERTIES,

        'Enable SQL logging locally and observe an entity insert.',

        'Hibernate commonly acts as the JPA provider in Spring Boot applications.',

        'When a Job entity is saved, Hibernate can generate the SQL required to persist the entity into the jobs table.',

        <<<'TEXT'
Job Java Object
      ↓
JPA
      ↓
Hibernate
      ↓
Generated SQL
      ↓
MySQL
TEXT,

        [
            'Hibernate is an ORM framework.',
            'Hibernate commonly implements JPA.',
            'Hibernate can generate SQL.',
            'Hibernate manages persistence context behavior.',
        ]
    ),

    $lesson(
        'What is an entity?',

        'An entity is a persistent class with an identity. It normally contains an @Id and represents persistent domain state.',

        <<<'JAVA'
@Entity
@Table(name = "jobs")
public class Job {

    @Id
    @GeneratedValue(
        strategy = GenerationType.IDENTITY
    )
    private Long id;

    @Column(nullable = false)
    private String title;
}
JAVA,

        'Add a non-null title and a unique external job identifier to the Job entity.',

        'An entity represents persistent domain data identified by an @Id.',

        'The Job entity can represent a vacancy stored in the portal database with information such as title, company, location, salary and experience.',

        <<<'TEXT'
Job
 |
 +-- id
 +-- title
 +-- company
 +-- location
 +-- salary
 +-- experience
 |
 ↓
jobs table
TEXT,

        [
            'An entity needs an identity.',
            '@Entity marks the persistent class.',
            '@Id identifies the entity.',
            'Entities should not be treated as unvalidated API payloads.',
        ]
    ),

    $lesson(
        'What is JpaRepository?',

        'JpaRepository provides CRUD operations, paging, sorting and query integration for a JPA entity.',

        <<<'JAVA'
public interface JobRepository
        extends JpaRepository<Job, Long> {

    Page<Job> findByStatus(
        String status,
        Pageable pageable
    );
}
JAVA,

        'Create a repository query for published jobs with pagination.',

        'JpaRepository provides typed persistence operations for an entity and its identifier type.',

        'The job portal can use JobRepository to retrieve published jobs, search jobs and save new vacancies without writing basic CRUD SQL manually.',

        <<<'TEXT'
JobController
      ↓
JobService
      ↓
JobRepository
      ↓
JpaRepository
      ↓
Hibernate
      ↓
MySQL
TEXT,

        [
            'JpaRepository provides CRUD methods.',
            'It supports paging and sorting.',
            'It supports derived query methods.',
            'Repositories should normally be called through services.',
        ]
    ),

    $lesson(
        'save() versus saveAndFlush()',

        'save schedules persistence through the persistence context while saveAndFlush additionally requests an immediate flush to the database.',

        <<<'JAVA'
Job saved =
    repository.save(job);

repository.flush();
JAVA,

        'Demonstrate normal transaction commit and identify a case where an early flush is required.',

        'save normally relies on transaction flush timing. saveAndFlush requests immediate synchronization with the database.',

        'During a recruitment transaction, normal saves can wait until the transaction is flushed or committed. An explicit flush can be useful when database synchronization is required earlier.',

        <<<'TEXT'
repository.save()
       ↓
Persistence Context
       ↓
Transaction Flush
       ↓
Database

saveAndFlush()
       ↓
Persistence Context
       ↓
Immediate Flush
       ↓
Database
TEXT,

        [
            'save does not necessarily immediately execute SQL.',
            'saveAndFlush explicitly requests a flush.',
            'Flush is different from transaction commit.',
            'Use early flush only when needed.',
        ]
    ),

    $lesson(
        'What is lazy loading?',

        'Lazy relationships are loaded when they are accessed instead of being retrieved immediately with the owning entity.',

        <<<'JAVA'
@OneToMany(
    mappedBy = "company",
    fetch = FetchType.LAZY
)
private List<Job> jobs =
    new ArrayList<>();
JAVA,

        'Load a company and fetch its jobs using an explicit service/query boundary rather than exposing a lazy entity graph directly.',

        'Lazy loading delays retrieval of related records until the relationship is accessed.',

        'A company page may contain thousands of jobs. Loading all jobs every time the company record is requested can be wasteful, so jobs can be loaded only when required.',

        <<<'TEXT'
Company
   ↓
Company loaded
   ↓
jobs relationship
   ↓
NOT loaded yet

When jobs are accessed
   ↓
Database query
   ↓
Jobs loaded
TEXT,

        [
            'LAZY delays related-data loading.',
            'Lazy access requires an appropriate persistence context or fetch strategy.',
            'Lazy loading can reduce unnecessary database work.',
            'DTO queries are often preferable for API responses.',
        ]
    ),

    $lesson(
        'What is eager loading?',

        'Eager loading requests related data as part of the entity load plan and can cause unnecessary data retrieval when relationships are large.',

        <<<'JAVA'
@EntityGraph(
    attributePaths = "company"
)
Page<Job> findByStatus(
    String status,
    Pageable pageable
);
JAVA,

        'Compare SQL generated by eager mapping and an EntityGraph query.',

        'Eager loading retrieves associated data as part of the load plan rather than waiting until the relationship is accessed.',

        'A job search result may need company information immediately, so an explicit fetch plan can retrieve the required company data without loading unrelated relationships.',

        <<<'TEXT'
Job Query
   ↓
Fetch Plan
   ↓
Job
 +
Company
   ↓
DTO
   ↓
API
TEXT,

        [
            'Eager loading can over-fetch data.',
            'Large relationships should not automatically be eager.',
            'EntityGraph can define query-specific fetching.',
            'Explicit fetch plans can improve API query behavior.',
        ]
    ),

    $lesson(
        'What is the N+1 query problem?',

        'N+1 occurs when one query loads a collection and additional queries are executed for a relationship for each returned row.',

        <<<'JAVA'
@Query("""
    select j
    from Job j
    join fetch j.company
    where j.status = :status
""")
List<Job> findPublishedWithCompany(
    String status
);
JAVA,

        'Measure database query count for 100 jobs before and after applying an appropriate fetch strategy.',

        'N+1 means one query retrieves the main records and additional queries retrieve related data repeatedly for individual rows.',

        'If a search returns 100 jobs and each job separately loads its company, the application can execute many unnecessary database queries.',

        <<<'TEXT'
1 query
   ↓
100 jobs
   ↓
Company query for job 1
Company query for job 2
Company query for job 3
...
Company query for job 100

= N+1 problem
TEXT,

        [
            'N+1 increases database round trips.',
            'Fetch joins can solve some N+1 scenarios.',
            'EntityGraph is another possible fetch strategy.',
            'Always inspect generated SQL and query counts.',
        ]
    ),

    $lesson(
        'What is cascade?',

        'Cascade propagates selected persistence operations from one entity to related entities.',

        <<<'JAVA'
@OneToMany(
    mappedBy = "job",
    cascade = {
        CascadeType.PERSIST,
        CascadeType.MERGE
    },
    orphanRemoval = true
)
private List<JobSkill> skills =
    new ArrayList<>();
JAVA,

        'Persist a job and its new skills without cascading deletion to shared records.',

        'Cascade controls which entity operations are propagated to associated entities.',

        'When a job contains its own JobSkill records, selected cascade operations can allow those records to be persisted with the job.',

        <<<'TEXT'
Job
 ↓
JobSkill
 ↓
----------------
PERSIST
MERGE
REMOVE
----------------
Only configured operations
are propagated
TEXT,

        [
            'Cascade controls operation propagation.',
            'CascadeType.ALL should be used carefully.',
            'REMOVE can delete related records.',
            'Cascade configuration should reflect ownership.',
        ]
    ),

    $lesson(
        'What is @Transactional?',

        '@Transactional defines a transaction boundary. Successful completion commits the transaction and matching failures can cause rollback according to transaction rules.',

        <<<'JAVA'
@Transactional
public void transfer(
    long from,
    long to,
    BigDecimal amount
) {

    debit(from, amount);

    credit(to, amount);
}
JAVA,

        'Write an integration test proving both database updates roll back when the credit operation fails.',

        '@Transactional allows multiple database operations to execute within one transaction boundary.',

        'In a recruitment platform, creating an application and updating a vacancy counter may need to be handled atomically so the database does not end up in an inconsistent state.',

        <<<'TEXT'
Begin Transaction
       ↓
Operation 1
       ↓
Operation 2
       ↓
Operation 3
       ↓
All successful?
   ↙           ↘
 YES           NO
 ↓              ↓
COMMIT        ROLLBACK
TEXT,

        [
            '@Transactional defines a transaction boundary.',
            'Transactions help maintain database consistency.',
            'Rollback behavior depends on exception and transaction configuration.',
            'Keep transaction boundaries around business operations.',
        ]
    ),

];
