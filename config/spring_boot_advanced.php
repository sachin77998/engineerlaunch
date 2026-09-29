<?php

$lesson = function (
    string $title,
    string $summary,
    string $code,
    string $task,
    string $correct
): array {
    return [
        'title' => $title,
        'summary' => $summary,
        'code' => $code,
        'task' => $task,
        'options' => [
            $correct,
            'It is only a browser-side feature.',
            'It requires disabling the Spring container.',
            'It stores all application data in source code.'
        ],
        'answer' => 0,
        'why' => $correct
    ];
};

return [

    $lesson(
        'What is Spring Security?',
        <<<'TEXT'
        Solution:

        Spring Security is responsible for protecting Spring Boot applications.

        Suppose our job portal has these users:

        1. JOB_SEEKER
        2. RECRUITER
        3. HR
        4. ADMIN

        A job seeker can:

        - Search jobs
        - View companies
        - Apply for jobs
        - View his own applications

        A recruiter can:

        - Create jobs
        - View candidates for his jobs
        - Shortlist candidates

        HR can:

        - Manage recruitment
        - Update interview status
        - View recruitment reports

        ADMIN can:

        - Manage users
        - Manage companies
        - Manage jobs
        - Manage system configuration

        Spring Security handles two different problems.

        Authentication:

        "Who are you?"

        Example:

        Sachin logs in using:

        email = sachin@example.com
        password = ********

        The application verifies the credentials and creates an authenticated identity.

        Authorization:

        "What are you allowed to do?"

        After Sachin is authenticated, Spring checks his role.

        If Sachin is JOB_SEEKER:

        GET /api/jobs
        GET /api/applications
        POST /api/jobs/10/apply

        may be allowed.

        But:

        DELETE /api/jobs/10

        may return 403 Forbidden.

        The important difference is:

        401 = authentication is missing or invalid.

        403 = authentication exists, but the user does not have permission.

        Example:

        Banking system:

        Customer logs in successfully.

        Authentication:
        Customer = ACCOUNT_1001

        Authorization:

        Customer can:
        - View own balance
        - Transfer money
        - Download statement

        Customer cannot:
        - Change another customer's balance
        - Access bank employee administration
        - Approve another customer's loan

        Spring Security should therefore be applied at the HTTP and service/method boundaries, not only in the frontend.
        TEXT,
        <<<'CODE'
        @Bean
        SecurityFilterChain securityFilterChain(HttpSecurity http) throws Exception {

            return http
                .csrf(csrf -> csrf.disable())
                .authorizeHttpRequests(auth -> auth

                    .requestMatchers(
                        "/api/auth/login",
                        "/api/auth/register",
                        "/api/jobs",
                        "/api/companies/**"
                    ).permitAll()

                    .requestMatchers("/api/admin/**")
                    .hasRole("ADMIN")

                    .requestMatchers("/api/hr/**")
                    .hasAnyRole("HR", "ADMIN")

                    .requestMatchers("/api/recruiter/**")
                    .hasAnyRole("RECRUITER", "HR", "ADMIN")

                    .anyRequest()
                    .authenticated()
                )
                .build();
        }
CODE,
        'Build authentication and authorization for JOB_SEEKER, RECRUITER, HR and ADMIN.',
        'Spring Security protects application resources by authenticating users and authorizing their permitted operations.'
    ),

    $lesson(
        'Authentication versus Authorization',
        <<<'TEXT'
        Solution:

        Authentication answers:

        "Who is this user?"

        Authorization answers:

        "What can this user do?"

        Consider an Indian banking application.

        Customer A logs in.

        The authentication process verifies:

        - Customer ID
        - Password
        - OTP or another authentication factor
        - Account/session/token validity

        After authentication:

        customer_id = 1001

        Now authorization begins.

        Customer 1001 requests:

        GET /api/accounts/1001

        The server checks whether account 1001 belongs to the authenticated customer.

        If yes:

        200 OK

        If the customer tries:

        GET /api/accounts/2000

        the server must not simply trust the account ID received from the URL.

        The authenticated identity and ownership must be checked.

        The same principle applies to a job portal.

        A recruiter may request:

        GET /api/jobs/500/applications

        The system must verify that recruiter 20 owns job 500 or has explicit permission to access it.

        Authentication without authorization would allow a logged-in user to access data belonging to another user.

        Therefore:

        Authentication = identity.

        Authorization = permission.
        TEXT,
        <<<'CODE'
        @GetMapping("/jobs/{jobId}/applications")
        @PreAuthorize("hasAnyRole('RECRUITER','HR','ADMIN')")
        public List<ApplicationDto> applications(
                @PathVariable Long jobId,
                Authentication authentication) {

            return applicationService
                .getApplicationsForAuthorizedRecruiter(
                    jobId,
                    authentication.getName()
                );
        }
CODE,
        'Implement ownership validation so one recruiter cannot access another recruiter’s candidate applications.',
        'Authentication identifies the caller; authorization determines whether that authenticated caller can perform the requested operation.'
    ),

    $lesson(
        'How does JWT authentication work?',
        <<<'TEXT'
        Solution:

        JWT authentication is commonly used when a frontend such as React or Angular communicates with a Spring Boot REST API.

        Example:

        The user submits:

        POST /api/auth/login

        Request:

        email = sachin@example.com
        password = password123

        Spring Security verifies the credentials.

        If valid, the backend creates a JWT.

        The token contains information such as:

        - subject/user ID
        - roles
        - issuer
        - issued time
        - expiration time

        The frontend then sends:

        Authorization: Bearer <token>

        on subsequent API requests.

        Example:

        GET /api/applications

        The request reaches the Spring Security filter.

        The filter:

        1. Reads Authorization header.
        2. Extracts Bearer token.
        3. Verifies the JWT signature.
        4. Checks expiration.
        5. Checks issuer/audience when configured.
        6. Extracts user identity and authorities.
        7. Creates Authentication.
        8. Places it into SecurityContext.
        9. Allows authorization rules to execute.

        If the signature is invalid:

        401 Unauthorized.

        If the token is expired:

        401 Unauthorized.

        If the token is valid but the role is insufficient:

        403 Forbidden.

        Never trust a JWT merely because it can be decoded. The payload is readable. The signature must be verified.
        TEXT,
        <<<'CODE'
        @Component
        public class JwtAuthenticationFilter
                extends OncePerRequestFilter {

            private final JwtService jwtService;

            public JwtAuthenticationFilter(JwtService jwtService) {
                this.jwtService = jwtService;
            }

            @Override
            protected void doFilterInternal(
                    HttpServletRequest request,
                    HttpServletResponse response,
                    FilterChain filterChain)
                    throws ServletException, IOException {

                String header = request.getHeader("Authorization");

                if (header == null || !header.startsWith("Bearer ")) {
                    filterChain.doFilter(request, response);
                    return;
                }

                String token = header.substring(7);

                if (jwtService.isValid(token)) {

                    String username =
                        jwtService.extractUsername(token);

                    var authentication =
                        new UsernamePasswordAuthenticationToken(
                            username,
                            null,
                            jwtService.extractAuthorities(token)
                        );

                    SecurityContextHolder
                        .getContext()
                        .setAuthentication(authentication);
                }

                filterChain.doFilter(request, response);
            }
        }
CODE,
        'Trace login -> JWT creation -> Authorization header -> JWT validation -> SecurityContext -> controller.',
        'JWT authentication works by validating a signed token on each protected request and creating an authenticated security context.'
    ),

    $lesson(
        'What is PasswordEncoder and why is BCrypt used?',
        <<<'TEXT'
        Solution:

        Passwords must never be stored directly in a database.

        Bad:

        users.password = "Sachin@123"

        If the database is compromised, the original passwords are immediately exposed.

        Instead, the application stores a password hash.

        Example:

        password entered:

        Sachin@123

        database:

        $2a$10$....

        During login:

        1. User enters password.
        2. Spring Security loads stored hash.
        3. PasswordEncoder compares the supplied password with the stored hash.
        4. If it matches, authentication succeeds.
        5. The original password is never recovered from the hash.

        BCrypt includes a salt.

        Therefore two users having the same password should not necessarily have the same stored hash.

        Example:

        User A password:

        Welcome@123

        User B password:

        Welcome@123

        Their BCrypt hashes can still be different because different salts are used.

        Never use MD5 or SHA-1 directly for password storage.
        TEXT,
        <<<'CODE'
        @Bean
        PasswordEncoder passwordEncoder() {
            return new BCryptPasswordEncoder(12);
        }

        @Service
        public class UserService {

            private final PasswordEncoder passwordEncoder;

            public UserService(PasswordEncoder passwordEncoder) {
                this.passwordEncoder = passwordEncoder;
            }

            public String hashPassword(String password) {

                return passwordEncoder.encode(password);
            }

            public boolean verifyPassword(
                    String rawPassword,
                    String storedHash) {

                return passwordEncoder.matches(
                    rawPassword,
                    storedHash
                );
            }
        }
CODE,
        'Create a registration API that stores only a BCrypt hash and a login API that verifies the password using matches().',
        'PasswordEncoder securely hashes passwords and verifies supplied passwords without storing the original password.'
    ),

    $lesson(
        'What is OAuth2 and OpenID Connect?',
        <<<'TEXT'
        Solution:

        OAuth2 is used when an application wants delegated access to another system.

        Example:

        A user clicks:

        "Login with Google"

        The job portal does not need to collect the user's Google password.

        The browser is redirected to the identity provider.

        The provider authenticates the user.

        After successful authentication, the provider redirects back to the application with an authorization result.

        OpenID Connect adds an identity layer on top of OAuth2.

        For an enterprise application, the same concept can be used with:

        - Google
        - Microsoft
        - GitHub
        - Okta
        - Keycloak
        - enterprise identity providers

        Example:

        A company has 10,000 employees.

        Instead of creating separate passwords in every internal Spring Boot application, employees can authenticate through the organization's identity provider.

        The Spring Boot application receives verified identity information and maps it to application roles.

        OAuth2 answers delegated authorization.

        OpenID Connect provides standardized authentication/identity information.
        TEXT,
        <<<'CODE'
        spring:
          security:
            oauth2:
              client:
                registration:
                  google:
                    client-id: ${GOOGLE_CLIENT_ID}
                    client-secret: ${GOOGLE_CLIENT_SECRET}
                    scope:
                      - openid
                      - profile
                      - email
CODE,
        'Implement Google login and map the returned email to an existing JOB_SEEKER or HR account.',
        'OAuth2 provides delegated authorization, while OpenID Connect adds an identity layer commonly used for login.'
    ),

    $lesson(
        'What is CORS and why does it matter in React + Spring Boot?',
        <<<'TEXT'
        Solution:

        Suppose the frontend runs on:

        https://jobs.example.com

        and Spring Boot runs on:

        https://api.example.com

        The browser treats these as different origins.

        The frontend may call:

        GET https://api.example.com/api/jobs

        The browser checks whether the API allows the frontend origin.

        Spring Boot can explicitly allow:

        https://jobs.example.com

        but reject:

        https://unknown-site.com

        CORS is enforced by browsers.

        It should not be used as the only API security mechanism.

        A common mistake is:

        allowedOrigins("*")

        for production APIs.

        Instead, maintain an explicit allow-list.

        For authenticated browser requests, credentials and CORS configuration must also be handled consistently.
        TEXT,
        <<<'CODE'
        @Configuration
        public class CorsConfig {

            @Bean
            CorsConfigurationSource corsConfigurationSource() {

                CorsConfiguration config =
                    new CorsConfiguration();

                config.setAllowedOrigins(
                    List.of("https://jobs.example.com")
                );

                config.setAllowedMethods(
                    List.of(
                        "GET",
                        "POST",
                        "PUT",
                        "PATCH",
                        "DELETE",
                        "OPTIONS"
                    )
                );

                config.setAllowedHeaders(
                    List.of("Authorization", "Content-Type")
                );

                config.setAllowCredentials(true);

                UrlBasedCorsConfigurationSource source =
                    new UrlBasedCorsConfigurationSource();

                source.registerCorsConfiguration(
                    "/**",
                    config
                );

                return source;
            }
        }
CODE,
        'Allow the production React frontend while rejecting requests from an unauthorized browser origin.',
        'CORS controls which browser origins are allowed to make cross-origin requests to the Spring Boot API.'
    ),

    $lesson(
        'What is role-based authorization?',
        <<<'TEXT'
        Solution:

        Suppose the portal contains these roles:

        JOB_SEEKER
        RECRUITER
        HR
        ADMIN

        Permissions:

        JOB_SEEKER:
        - Search jobs
        - Apply
        - View own applications

        RECRUITER:
        - Create jobs
        - View candidates
        - Shortlist candidates

        HR:
        - Manage recruitment
        - Schedule interviews
        - View recruitment reports

        ADMIN:
        - Manage everything permitted by the application policy

        Example:

        A recruiter sends:

        PUT /api/applications/100/status

        with:

        status = SHORTLISTED

        The backend must check:

        1. Is the recruiter authenticated?
        2. Does the recruiter have recruiter permission?
        3. Does application 100 belong to a job managed by this recruiter?
        4. Is the status transition valid?

        Checking only the role is insufficient when resource ownership matters.

        The database and service layer must enforce ownership rules.
        TEXT,
        <<<'CODE'
        @PreAuthorize("hasAnyRole('RECRUITER','HR','ADMIN')")
        @PutMapping("/applications/{id}/status")
        public ApplicationDto updateStatus(
                @PathVariable Long id,
                @RequestBody UpdateStatusRequest request,
                Authentication authentication) {

            return applicationService.updateStatus(
                id,
                request,
                authentication.getName()
            );
        }
CODE,
        'Prevent recruiter A from updating an application belonging to recruiter B.',
        'Role-based authorization grants permissions through roles, while service-level ownership checks protect individual resources.'
    ),

    $lesson(
        'What is microservices architecture?',
        <<<'TEXT'
        Solution:

        Suppose a large job portal contains:

        - User management
        - Company management
        - Job management
        - Applications
        - Interviews
        - Notifications
        - Payments
        - Search
        - Analytics

        Instead of putting everything into one independently deployable service, business capabilities can be separated.

        Example:

        Identity Service
        Company Service
        Job Service
        Application Service
        Interview Service
        Notification Service
        Payment Service
        Analytics Service

        Suppose 5 million users search jobs every day.

        The Job Search functionality may need much more capacity than the interview service.

        With independently deployable services, job search can be scaled separately.

        However, microservices introduce additional problems:

        - Network failures
        - Distributed tracing
        - Service discovery
        - Data consistency
        - Message delivery
        - Deployment complexity
        - Monitoring
        - Authentication between services

        Therefore microservices are not simply "many Spring Boot projects".

        They require clear ownership and communication boundaries.
        TEXT,
        <<<'CODE'
        Client
           |
        API Gateway
           |
           +---- Identity Service
           |
           +---- Company Service
           |
           +---- Job Service
           |
           +---- Application Service
           |
           +---- Interview Service
           |
           +---- Notification Service
           |
           +---- Analytics Service
CODE,
        'Define service boundaries for the job portal and identify which service owns application status.',
        'Microservices divide a system into independently deployable business capabilities with explicit ownership and communication boundaries.'
    ),

    $lesson(
        'What is an API Gateway?',
        <<<'TEXT'
        Solution:

        The API Gateway is normally the entry point between clients and backend services.

        Instead of React directly calling:

        user-service
        job-service
        application-service
        company-service

        the frontend can call:

        api.example.com

        The gateway routes requests.

        Example:

        GET /api/jobs
                |
                v
        Job Service

        POST /api/applications
                |
                v
        Application Service

        GET /api/companies/100
                |
                v
        Company Service

        The gateway can also participate in:

        - Authentication integration
        - Rate limiting
        - Routing
        - Request correlation
        - Logging
        - TLS termination
        - Header forwarding

        Business rules such as "whether candidate can apply to this job" should remain inside the Application Service.

        The gateway should not become a giant business-logic service.
        TEXT,
        <<<'CODE'
        spring:
          cloud:
            gateway:
              routes:

                - id: job-service
                  uri: lb://JOB-SERVICE
                  predicates:
                    - Path=/api/jobs/**

                - id: application-service
                  uri: lb://APPLICATION-SERVICE
                  predicates:
                    - Path=/api/applications/**

                - id: company-service
                  uri: lb://COMPANY-SERVICE
                  predicates:
                    - Path=/api/companies/**
CODE,
        'Route jobs, applications and company requests to separate Spring Boot services.',
        'An API Gateway provides a centralized entry point for routing and cross-cutting edge concerns.'
    ),

    $lesson(
        'What is service discovery?',
        <<<'TEXT'
        Solution:

        Imagine Job Service has three running instances:

        job-service-1
        job-service-2
        job-service-3

        The IP address of each instance can change.

        A client should not permanently store:

        http://10.10.20.31:8080

        Instead, it can call:

        http://job-service/api/jobs

        The platform resolves the logical service name to available instances.

        In Kubernetes, Services and DNS commonly provide this capability.

        The important idea is:

        Client knows the service name.

        Infrastructure knows where healthy instances currently exist.

        When one instance fails, traffic can be sent to another healthy instance.
        TEXT,
        <<<'CODE'
        @RestController
        @RequestMapping("/api/jobs")
        public class JobController {

            @GetMapping
            public List<JobDto> jobs() {
                return jobService.findJobs();
            }
        }

        // Internal logical destination:
        //
        // http://job-service/api/jobs
CODE,
        'Run three Job Service instances and route requests through the logical service name.',
        'Service discovery allows applications to locate changing service instances without hard-coding individual server addresses.'
    ),

    $lesson(
        'What is OpenFeign?',
        <<<'TEXT'
        Solution:

        Suppose Application Service needs company information.

        Without Feign, developers repeatedly write HTTP client code.

        With OpenFeign, the remote API can be represented as a Java interface.

        Example:

        Application Service:

        GET /applications/100

        needs:

        GET /companies/50

        The Feign client performs the HTTP call.

        However, Feign does not remove network failure.

        Production configuration still needs:

        - Connect timeout
        - Read timeout
        - Error handling
        - Retry policy where safe
        - Circuit breaker where appropriate
        - Authentication between services
        TEXT,
        <<<'CODE'
        @FeignClient(name = "company-service")
        public interface CompanyClient {

            @GetMapping("/api/companies/{id}")
            CompanyDto findCompany(
                @PathVariable("id") Long id
            );
        }

        @Service
        public class ApplicationService {

            private final CompanyClient companyClient;

            public ApplicationService(
                CompanyClient companyClient
            ) {
                this.companyClient = companyClient;
            }

            public CompanyDto getCompany(Long companyId) {

                return companyClient.findCompany(companyId);
            }
        }
CODE,
        'Create a Feign client from Application Service to Company Service and configure timeouts.',
        'OpenFeign provides a declarative interface for calling HTTP services while resilience and timeout policies remain necessary.'
    ),

    $lesson(
        'What is a circuit breaker?',
        <<<'TEXT'
        Solution:

        Suppose Application Service calls Company Service.

        Normally:

        Application Service
              |
              v
        Company Service
              |
              v
        Database

        Now Company Service becomes unavailable.

        If Application Service continues making thousands of requests:

        Request 1 -> timeout
        Request 2 -> timeout
        Request 3 -> timeout
        ...
        Request 100000 -> timeout

        Threads can become exhausted.

        This can cause failure to spread to other services.

        A circuit breaker changes this behavior.

        Closed:

        Requests are allowed.

        If failures exceed a threshold:

        Open:

        Requests fail fast without repeatedly calling the unhealthy service.

        After a waiting period:

        Half-open:

        A limited request is allowed to test recovery.

        If successful:

        Closed.

        If unsuccessful:

        Open again.
        TEXT,
        <<<'CODE'
        @CircuitBreaker(
            name = "companyService",
            fallbackMethod = "companyFallback"
        )
        public CompanyDto getCompany(Long id) {

            return companyClient.findCompany(id);
        }

        public CompanyDto companyFallback(
                Long id,
                Throwable exception) {

            return CompanyDto.unavailable(id);
        }
CODE,
        'Simulate Company Service timeouts and observe CLOSED -> OPEN -> HALF_OPEN -> CLOSED.',
        'A circuit breaker prevents repeated calls to an unhealthy dependency and helps contain cascading failures.'
    ),

    $lesson(
        'What is Kafka and why use it with Spring Boot?',
        <<<'TEXT'
        Solution:

        Suppose a candidate applies for a job.

        The Application Service performs the core database transaction.

        After the application is successfully created, several things may need to happen:

        1. Send email.
        2. Notify recruiter.
        3. Update analytics.
        4. Create audit record.
        5. Update recommendation data.
        6. Update search-related statistics.

        The application API should not necessarily wait for all of these operations.

        An event can be published:

        ApplicationSubmitted

        Consumers can independently process it.

        Example:

        Application Service
                |
                v
        Kafka
                |
                +---- Notification Service
                |
                +---- Analytics Service
                |
                +---- Audit Service
                |
                +---- Recommendation Service

        If Notification Service temporarily fails, Kafka can retain the event until the consumer can process it according to the configured retention/processing strategy.

        Each consumer should be idempotent.
        TEXT,
        <<<'CODE'
        @Service
        public class ApplicationEventPublisher {

            private final KafkaTemplate<String, ApplicationSubmittedEvent> kafka;

            public ApplicationEventPublisher(
                KafkaTemplate<String, ApplicationSubmittedEvent> kafka
            ) {
                this.kafka = kafka;
            }

            public void publish(
                ApplicationSubmittedEvent event
            ) {

                kafka.send(
                    "application-submitted",
                    event.applicationId().toString(),
                    event
                );
            }
        }

        @KafkaListener(
            topics = "application-submitted",
            groupId = "notification-service"
        )
        public void consume(
            ApplicationSubmittedEvent event
        ) {

            notificationService
                .sendApplicationConfirmation(event);
        }
CODE,
        'Create ApplicationSubmitted and ApplicationStatusChanged events and process them with separate consumer groups.',
        'Kafka allows Spring Boot services to exchange durable asynchronous events without tightly coupling every downstream operation to the original request.'
    ),

    $lesson(
        'What is a Kafka partition and consumer group?',
        <<<'TEXT'
        Solution:

        Suppose:

        application-submitted

        has 3 partitions.

        Partition 0
        Partition 1
        Partition 2

        A consumer group contains:

        Consumer A
        Consumer B
        Consumer C

        The partitions can be distributed among consumers.

        The important rule is:

        One partition is processed by only one consumer within the same consumer group at a time.

        If another service uses another consumer group:

        notification-service
        analytics-service

        both groups receive their own processing view of the topic.

        This is useful for a job portal.

        The same ApplicationSubmitted event may need to be consumed by:

        Notification Service
        Analytics Service
        Audit Service

        They should not be forced into one consumer group because each represents an independent business function.

        Partition keys also matter.

        If applicationId is the key, events for the same application can be routed to the same partition, preserving order within that partition.
        TEXT,
        <<<'CODE'
        spring:
          kafka:
            consumer:
              group-id: notification-service

            producer:
              key-serializer: org.apache.kafka.common.serialization.StringSerializer
              value-serializer: org.springframework.kafka.support.serializer.JsonSerializer

            consumer:
              key-deserializer: org.apache.kafka.common.serialization.StringDeserializer
              value-deserializer: org.springframework.kafka.support.serializer.JsonDeserializer
CODE,
        'Create separate consumer groups for notification, analytics and audit processing.',
        'Kafka partitions provide parallelism and ordering within a partition, while consumer groups distribute processing among consumers.'
    ),

    $lesson(
        'How do you make Kafka consumers idempotent?',
        <<<'TEXT'
        Solution:

        Suppose Kafka delivers:

        eventId = EVT-1001

        The Notification Service processes it and sends an email.

        Now the same event is delivered again.

        If the service blindly processes it:

        Email 1
        Email 2

        The candidate receives duplicate notifications.

        The consumer should maintain a processed-events table.

        Example:

        processed_events

        id
        event_id
        consumer_name
        processed_at

        Before processing:

        SELECT event_id
        FROM processed_events
        WHERE event_id = 'EVT-1001'
        AND consumer_name = 'notification-service';

        If it exists:

        Do not process again.

        If it does not exist:

        1. Process event.
        2. Record event ID.
        3. Commit transaction.

        A unique constraint should protect against concurrent duplicate processing.

        Idempotency is essential because distributed messaging systems can involve redelivery.
        TEXT,
        <<<'CODE'
        CREATE TABLE processed_events (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            event_id VARCHAR(100) NOT NULL,
            consumer_name VARCHAR(100) NOT NULL,
            processed_at TIMESTAMP NOT NULL,
            UNIQUE(event_id, consumer_name)
        );

        @KafkaListener(
            topics = "application-submitted",
            groupId = "notification-service"
        )
        @Transactional
        public void consume(ApplicationSubmittedEvent event) {

            if (processedEventRepository
                    .existsByEventIdAndConsumerName(
                        event.eventId(),
                        "notification-service"
                    )) {

                return;
            }

            notificationService.send(event);

            processedEventRepository.save(
                new ProcessedEvent(
                    event.eventId(),
                    "notification-service"
                )
            );
        }
CODE,
        'Send the same Kafka event three times and verify that the business operation occurs only once.',
        'Kafka consumers should use idempotency so redelivery does not create duplicate business effects.'
    ),

    $lesson(
        'What is Redis and where is it used in Spring Boot?',
        <<<'TEXT'
        Solution:

        Redis is commonly used for very fast temporary or frequently accessed data.

        Examples:

        1. Job search cache.
        2. Company profile cache.
        3. Session storage.
        4. OTP expiration.
        5. Rate limiting.
        6. Counters.
        7. Distributed locks.
        8. Temporary tokens.

        Example:

        Suppose 100,000 users search:

        "Laravel developer in Punjab"

        Without caching, every request may execute database/search queries.

        With Redis:

        First request:

        Redis MISS
              |
              v
        Database/Search
              |
              v
        Redis SET

        Next requests:

        Request
          |
          v
        Redis HIT
          |
          v
        Response

        The database remains authoritative.

        Do not treat Redis cache as the permanent source of truth unless the architecture explicitly requires Redis as a durable data store.
        TEXT,
        <<<'CODE'
        spring:
          data:
            redis:
              host: localhost
              port: 6379

          cache:
            type: redis

        @Cacheable(
            value = "companies",
            key = "#companyId"
        )
        public CompanyDto getCompany(Long companyId) {

            return companyRepository
                .findById(companyId)
                .map(companyMapper::toDto)
                .orElseThrow();
        }
CODE,
        'Cache a company profile for repeated requests and verify that the database is not queried on every request.',
        'Redis provides fast storage commonly used by Spring Boot applications for caching, counters, sessions and temporary distributed state.'
    ),

    $lesson(
        'What is cache invalidation?',
        <<<'TEXT'
        Solution:

        Suppose:

        Company 100

        has:

        employeeCount = 5000

        The value is cached.

        Redis:

        company:100 -> employeeCount 5000

        Now the database is updated:

        employeeCount = 5200

        If Redis still contains 5000, users receive stale data.

        Therefore the application needs an invalidation strategy.

        After updating:

        Database -> 5200
        Redis key deleted

        Next read:

        Redis MISS
        Database -> 5200
        Redis -> cache 5200

        For frequently changing data, use a shorter TTL or event-driven invalidation.

        Never assume that adding a cache automatically makes an application correct.
        TEXT,
        <<<'CODE'
        @CacheEvict(
            value = "companies",
            key = "#companyId"
        )
        @Transactional
        public CompanyDto updateCompany(
                Long companyId,
                UpdateCompanyRequest request
        ) {

            Company company =
                companyRepository
                    .findById(companyId)
                    .orElseThrow();

            company.setName(request.name());
            company.setEmployeeCount(
                request.employeeCount()
            );

            Company saved =
                companyRepository.save(company);

            return mapper.toDto(saved);
        }
CODE,
        'Update a company and verify that the next GET reads the new database value.',
        'Cache invalidation removes stale cached data after the authoritative database value changes.'
    ),

    $lesson(
        'What is @Async and when should it not be used?',
        <<<'TEXT'
        Solution:

        @Async executes work using a Spring-managed executor instead of blocking the calling thread.

        Example:

        A user applies for a job.

        The main operation:

        1. Validate candidate.
        2. Validate job.
        3. Insert application.
        4. Commit transaction.

        Sending an email can happen separately.

        Without asynchronous execution:

        Application request
            |
            +-- Save application
            |
            +-- Send email
            |
            +-- Send notification
            |
        Response

        The user waits for email processing.

        With asynchronous execution:

        Application request
            |
            +-- Save application
            |
        Response

        Background worker:
            |
            +-- Send email
            +-- Send notification

        However, @Async is local to the application process.

        If the process crashes, the in-memory task may be lost.

        For durable cross-service business events, Kafka or another durable messaging system is generally more appropriate.

        Also remember that @Async works through Spring proxies. Calling an @Async method through self-invocation in the same class does not activate asynchronous interception.
        TEXT,
        <<<'CODE'
        @Configuration
        @EnableAsync
        public class AsyncConfig {

            @Bean("mailExecutor")
            public Executor mailExecutor() {

                ThreadPoolTaskExecutor executor =
                    new ThreadPoolTaskExecutor();

                executor.setCorePoolSize(5);
                executor.setMaxPoolSize(20);
                executor.setQueueCapacity(100);
                executor.setThreadNamePrefix("mail-");

                executor.initialize();

                return executor;
            }
        }

        @Async("mailExecutor")
        public CompletableFuture<Void> sendApplicationEmail(
                Long applicationId) {

            emailService.send(applicationId);

            return CompletableFuture.completedFuture(null);
        }
CODE,
        'Configure a bounded executor and process email notifications asynchronously.',
        '@Async is useful for local background work, while durable cross-service business events should normally use messaging infrastructure.'
    ),

    $lesson(
        'What is Spring Batch?',
        <<<'TEXT'
        Solution:

        Suppose a university has 2,000 employees.

        At the end of every month, salary must be calculated.

        Another company may have:

        100,000 employees.

        Processing everything as one huge operation can create memory, transaction and recovery problems.

        Spring Batch breaks large processing into controlled steps.

        Example:

        Read employee
            |
        Calculate salary
            |
        Validate salary
            |
        Write payroll item
            |
        Commit chunk

        Suppose chunk size = 200.

        Employees:

        1-200
        201-400
        401-600
        ...

        If employees 1-200 complete successfully and processing employee 401-600 fails, the job can restart according to its configured restart/retry strategy instead of blindly recalculating everything.

        Typical structure:

        Job
          |
        Step
          |
        ItemReader
          |
        ItemProcessor
          |
        ItemWriter

        For payroll:

        Reader:
        employees

        Processor:
        basic + HRA + allowance - deduction

        Writer:
        payroll_items
        TEXT,
        <<<'CODE'
        @Bean
        public Step payrollStep(
                JobRepository jobRepository,
                PlatformTransactionManager transactionManager) {

            return new StepBuilder(
                    "payrollStep",
                    jobRepository
                )
                .<Employee, PayrollItem>chunk(
                    200,
                    transactionManager
                )
                .reader(employeeReader())
                .processor(employee ->
                    payrollCalculator.calculate(employee)
                )
                .writer(payrollWriter())
                .build();
        }
CODE,
        'Process 2,000 employee salaries in chunks of 200 and make the job restartable after a failure.',
        'Spring Batch provides structured processing, chunk transactions, restartability and controlled handling of large datasets.'
    ),

    $lesson(
        'What is transaction management in Spring Boot?',
        <<<'TEXT'
        Solution:

        Consider a banking transfer.

        Account A:

        balance = 10000

        Account B:

        balance = 2000

        Customer transfers:

        5000

        The operation has two important database changes:

        Account A:
        10000 -> 5000

        Account B:
        2000 -> 7000

        Both must succeed together.

        If Account A is debited but Account B is not credited, the database becomes inconsistent.

        A transaction groups the database changes.

        If everything succeeds:

        COMMIT

        If a database operation fails:

        ROLLBACK

        Important:

        A local database transaction cannot automatically roll back an external bank transaction that already succeeded.

        Example:

        Database transaction
            |
            +-- debit record
            |
            +-- external bank API
                     |
                     +-- SUCCESS
            |
            +-- database failure

        A database ROLLBACK cannot undo the external bank's successful operation.

        That situation requires idempotency, status reconciliation, callback handling and potentially a separate reversal/refund process.
        TEXT,
        <<<'CODE'
        @Service
        public class TransferService {

            private final AccountRepository accountRepository;
            private final TransferRepository transferRepository;

            @Transactional
            public void transfer(
                    Long fromAccount,
                    Long toAccount,
                    BigDecimal amount) {

                Account source =
                    accountRepository
                        .findByIdForUpdate(fromAccount)
                        .orElseThrow();

                Account target =
                    accountRepository
                        .findByIdForUpdate(toAccount)
                        .orElseThrow();

                if (source.getBalance().compareTo(amount) < 0) {
                    throw new InsufficientFundsException();
                }

                source.debit(amount);
                target.credit(amount);

                accountRepository.save(source);
                accountRepository.save(target);

                transferRepository.save(
                    Transfer.completed(
                        fromAccount,
                        toAccount,
                        amount
                    )
                );
            }
        }
CODE,
        'Implement an Account A -> Account B transfer and demonstrate rollback when the second database operation fails.',
        'Spring transactions provide atomicity for participating database operations, while external side effects require separate failure and reconciliation handling.'
    ),

    $lesson(
        'How do you handle two simultaneous withdrawals?',
        <<<'TEXT'
        Solution:

        Suppose bank account balance:

        Rs 5,000

        At the same time two requests arrive.

        Request A:

        withdraw Rs 5,000

        Request B:

        withdraw Rs 10,000

        Without concurrency protection, both requests may read:

        balance = 5000

        Both may incorrectly believe enough money exists.

        This is a race condition.

        The database must serialize the balance decision.

        One approach is pessimistic locking.

        Request A locks the account row.

        Request B waits.

        Request A checks:

        5000 >= 5000

        Yes.

        New balance:

        0

        Request A commits.

        Request B obtains the lock.

        It now reads:

        balance = 0

        Then:

        0 < 10000

        Therefore:

        INSUFFICIENT_FUNDS

        Only one operation succeeds.

        The exact concurrency mechanism should match the database and transaction design. The critical rule is that the balance check and balance update must occur under the same protected transaction.
        TEXT,
        <<<'CODE'
        @Lock(LockModeType.PESSIMISTIC_WRITE)
        @Query("""
            SELECT a
            FROM Account a
            WHERE a.id = :id
        """)
        Optional<Account> findByIdForUpdate(
            @Param("id") Long id
        );

        @Transactional
        public WithdrawalResult withdraw(
                Long accountId,
                BigDecimal amount) {

            Account account =
                repository.findByIdForUpdate(accountId)
                    .orElseThrow();

            if (account.getBalance()
                    .compareTo(amount) < 0) {

                return WithdrawalResult.failed(
                    "INSUFFICIENT_FUNDS"
                );
            }

            account.debit(amount);

            repository.save(account);

            return WithdrawalResult.success();
        }
CODE,
        'Execute two concurrent withdrawal requests against a Rs 5,000 account and verify that the balance never becomes negative.',
        'Concurrent financial operations must protect the account row so the balance check and update cannot race with another withdrawal.'
    ),

    $lesson(
        'What is distributed tracing?',
        <<<'TEXT'
        Solution:

        Suppose one request travels through:

        React
          |
        Gateway
          |
        Application Service
          |
        Job Service
          |
        Company Service
          |
        Kafka
          |
        Notification Service

        If the user reports:

        "My application is taking 8 seconds."

        Without tracing, it is difficult to know where the time was spent.

        Distributed tracing assigns a trace ID.

        Example:

        traceId = 8f91ab

        The trace follows the request.

        Gateway:
        200 ms

        Application Service:
        500 ms

        Job Service:
        100 ms

        Company Service:
        6 seconds

        Now the bottleneck becomes visible.

        Trace IDs and span IDs allow operations teams to reconstruct the request path across services.
        TEXT,
        <<<'CODE'
        management:
          tracing:
            sampling:
              probability: 0.1

        logging:
          pattern:
            correlation: "[traceId=${spring.application.name},spanId=%X{traceId}]"
CODE,
        'Trace one job application request across Gateway, Application Service and Notification Service.',
        'Distributed tracing correlates a request across multiple services and shows where time and failures occur.'
    ),

    $lesson(
        'What is Spring Boot Actuator?',
        <<<'TEXT'
        Solution:

        A production application needs operational information.

        Examples:

        Is the application alive?

        Is the database reachable?

        How many requests are being processed?

        What is the response time?

        Are JVM resources exhausted?

        Spring Boot Actuator exposes management endpoints.

        Common examples:

        /actuator/health

        /actuator/info

        /actuator/metrics

        /actuator/prometheus

        Health should be divided conceptually into:

        Liveness:

        Should this process be restarted?

        Readiness:

        Can this instance receive traffic?

        Sensitive management information should not be exposed publicly without authentication and network controls.
        TEXT,
        <<<'CODE'
        management:
          endpoints:
            web:
              exposure:
                include:
                  - health
                  - info
                  - prometheus

          endpoint:
            health:
              probes:
                enabled: true
CODE,
        'Expose health and Prometheus metrics while keeping sensitive management endpoints protected.',
        'Spring Boot Actuator provides production health, metrics and management capabilities.'
    ),

    $lesson(
        'How do you handle millions of API requests?',
        <<<'TEXT'
        Solution:

        Suppose a job portal receives:

        1,000,000 requests per day.

        The first question should not be:

        "Which technology should we add?"

        First identify the bottleneck.

        Possible bottlenecks:

        - Database CPU
        - Slow SQL
        - Missing indexes
        - Too many database connections
        - Large responses
        - Expensive search
        - External API latency
        - Application CPU
        - Memory pressure

        Then scale the appropriate layer.

        Example architecture:

        Users
          |
        CDN
          |
        Load Balancer
          |
        API Gateway
          |
        Multiple Spring Boot instances
          |
        Redis
          |
        Database / Search

        Slow operations:

        Spring Boot
          |
        Kafka / Queue
          |
        Background workers

        For job search:

        Use proper indexes or a dedicated search engine.

        For repeated reads:

        Use Redis.

        For large result sets:

        Use pagination.

        For expensive background operations:

        Use queues/Kafka.

        For abusive clients:

        Use rate limiting.

        Scaling should be measured through metrics rather than adding infrastructure blindly.
        TEXT,
        <<<'CODE'
        Client
           |
        Load Balancer
           |
        +-----------------------+
        | Spring Boot Instance 1|
        | Spring Boot Instance 2|
        | Spring Boot Instance 3|
        | Spring Boot Instance 4|
        +-----------------------+
                  |
              Redis Cache
                  |
           +------+------+
           |             |
        Database      Search
           |
        Read replicas
CODE,
        'Measure an API bottleneck and apply caching, indexing, horizontal scaling and asynchronous processing where required.',
        'High-scale Spring Boot systems combine measured horizontal scaling, efficient databases, caching, asynchronous processing and controlled traffic.'
    ),

    $lesson(
        'How do you prevent duplicate API requests?',
        <<<'TEXT'
        Solution:

        Suppose a candidate clicks:

        Apply Now

        The browser sends:

        POST /api/jobs/500/applications

        Network delay occurs.

        The user clicks again.

        Two requests arrive:

        Request A
        Request B

        Without protection:

        application #100
        application #101

        Both may be created.

        The business rule says:

        One candidate can apply to one job only once.

        Use multiple protection layers.

        Application request:

        Idempotency-Key: APPLY-USER-100-JOB-500

        Database:

        UNIQUE(candidate_id, job_id)

        The service transaction checks and creates the application.

        The database constraint is the final protection against concurrent requests.

        Application-level checks alone are not enough because two requests can pass the check simultaneously.
        TEXT,
        <<<'CODE'
        @PostMapping("/jobs/{jobId}/applications")
        public ApplicationDto apply(
                @PathVariable Long jobId,
                @RequestHeader("Idempotency-Key")
                String idempotencyKey,
                Authentication authentication) {

            return applicationService.apply(
                jobId,
                authentication.getName(),
                idempotencyKey
            );
        }
        
        CREATE UNIQUE INDEX
        uk_candidate_job
        ON applications(candidate_id, job_id);
CODE,
        'Send the same application request concurrently and prove only one application is created.',
        'Idempotency keys and database uniqueness constraints prevent repeated requests from creating duplicate business operations.'
    ),

    $lesson(
        'How do you design a scalable job search system?',
        <<<'TEXT'
        Solution:

        A relational database can remain the authoritative source for jobs.

        But searching millions of job documents with:

        title
        skills
        company
        location
        experience
        salary
        industry
        employment type

        can become expensive.

        A search index such as Elasticsearch or OpenSearch can provide:

        - Full-text search
        - Skill matching
        - Location filters
        - Salary filters
        - Facets
        - Relevance
        - Pagination

        Architecture:

        Admin/Recruiter
              |
        Job Service
              |
        PostgreSQL
              |
        Outbox/Event
              |
        Kafka
              |
        Search Index
              |
        Search API

        The important rule is:

        Search index is not automatically the business source of truth.

        PostgreSQL can remain authoritative.

        When a job changes:

        Database transaction records the job change.

        An event is published.

        Search indexing consumes the event.

        If indexing fails, the event can be retried/reconciled instead of losing the business record.
        TEXT,
        <<<'CODE'
        Job Service
            |
            +---- PostgreSQL
            |
            +---- Outbox Event
                      |
                      v
                    Kafka
                      |
                      v
               Search Indexer
                      |
                      v
              Elasticsearch/OpenSearch

        GET /api/search/jobs?q=spring+boot&location=pune
CODE,
        'Create a job search index containing title, skills, location, experience and salary filters.',
        'A scalable job search architecture keeps authoritative business data in the database and uses a dedicated search index for fast search and filtering.'
    ),

    $lesson(
        'How do you design a production job application system?',
        <<<'TEXT'
        Solution:

        Consider:

        POST /jobs/500/applications

        The application service should perform:

        1. Authenticate candidate.
        2. Load job.
        3. Verify job is open.
        4. Verify candidate eligibility.
        5. Check duplicate application.
        6. Create application.
        7. Create initial status.
        8. Create audit record.
        9. Commit transaction.
        10. Publish ApplicationSubmitted event.
        11. Notification service sends email.
        12. Analytics service processes event.

        Database example:

        applications

        id
        candidate_id
        job_id
        status
        applied_at

        application_status_history

        id
        application_id
        old_status
        new_status
        changed_by
        changed_at

        The status history is important because the current status alone does not explain how the application reached that state.

        Example:

        APPLIED
            |
        SCREENING
            |
        SHORTLISTED
            |
        INTERVIEW
            |
        SELECTED

        Every transition should be validated.

        A candidate should not normally jump from APPLIED directly to an arbitrary invalid state unless the business rules permit it.
        TEXT,
        <<<'CODE'
        @Transactional
        public ApplicationDto apply(
                Long candidateId,
                Long jobId
        ) {

            Job job = jobRepository
                .findById(jobId)
                .orElseThrow();

            if (!job.isOpen()) {
                throw new JobClosedException();
            }

            if (applicationRepository
                .existsByCandidateIdAndJobId(
                    candidateId,
                    jobId
                )) {

                throw new DuplicateApplicationException();
            }

            Application application =
                Application.create(
                    candidateId,
                    jobId,
                    ApplicationStatus.APPLIED
                );

            applicationRepository.save(application);

            statusHistoryRepository.save(
                ApplicationStatusHistory.initial(
                    application.getId(),
                    ApplicationStatus.APPLIED
                )
            );

            return mapper.toDto(application);
        }
CODE,
        'Implement duplicate prevention, status history and valid status transitions for job applications.',
        'A production application system combines transactional persistence, eligibility checks, duplicate protection, status history and asynchronous downstream events.'
    ),

    $lesson(
        'How do you handle external payment or banking failure?',
        <<<'TEXT'
        Solution:

        Suppose a university student has:

        Semester Fee = Rs 80,000

        The student pays Rs 30,000.

        The database records:

        invoice:
        gross = 80000
        paid = 30000
        balance = 50000
        status = PARTIALLY_PAID

        Now the payment gateway returns:

        SUCCESS

        but the browser closes before the frontend receives the response.

        The system must not assume:

        "Browser closed = payment failed."

        Instead, the backend should use the persisted payment intent and gateway reference.

        Payment lifecycle:

        INITIATED
            |
        PENDING
            |
        SUCCESS

        or:

        INITIATED
            |
        PENDING
            |
        CONFIRMED_FAILED

        A webhook/callback should be authenticated and verified.

        Duplicate callbacks should be idempotent.

        If the same gateway reference arrives three times, the student should receive only one financial credit.

        If the gateway confirms Rs 30,000 but the local database update fails, a database rollback cannot undo the gateway transaction.

        The payment remains an external financial fact that must be reconciled.
        TEXT,
        <<<'CODE'
        @Transactional
        public void confirmPayment(
                String gatewayReference,
                BigDecimal amount
        ) {

            Payment payment =
                paymentRepository
                    .findByGatewayReferenceForUpdate(
                        gatewayReference
                    )
                    .orElseThrow();

            if (payment.isSuccessful()) {
                return;
            }

            if (payment.getAmount()
                .compareTo(amount) != 0) {

                throw new PaymentAmountMismatchException();
            }

            payment.markSuccessful();

            paymentRepository.save(payment);

            FeeInvoice invoice =
                invoiceRepository
                    .findByIdForUpdate(
                        payment.getInvoiceId()
                    )
                    .orElseThrow();

            invoice.addPayment(amount);

            invoiceRepository.save(invoice);
        }
CODE,
        'Implement an idempotent payment callback where the same gateway reference can be delivered multiple times.',
        'External payment success must be persisted idempotently and reconciled separately from local database rollback.'
    ),

    $lesson(
        'How do you design a 10,000-employee salary batch?',
        <<<'TEXT'
        Solution:

        Suppose Infosys-like corporate payroll contains:

        10,000 employees

        Average net salary:

        Rs 75,000

        Total:

        10,000 × 75,000
        = Rs 750,000,000
        = Rs 75 crore

        The system should not treat this as one giant payment row.

        Create:

        salary_batches

        id
        company_id
        batch_reference
        salary_month
        employee_count
        total_amount
        status

        salary_batch_items

        id
        salary_batch_id
        employee_id
        beneficiary_account
        amount
        status
        payment_reference
        failure_code

        Lifecycle:

        DRAFT
          |
        VALIDATED
          |
        APPROVED
          |
        SUBMITTED
          |
        PROCESSING
          |
        COMPLETED

        But processing can have mixed results.

        Example:

        9970 SUCCESS
        20 FAILED
        10 PENDING

        The batch cannot be declared fully successful merely because most employees succeeded.

        The system must retain individual payment outcomes.

        The final batch state can be:

        PARTIALLY_COMPLETED

        while the 10 pending items remain unresolved.

        An external bank timeout should not automatically mean FAILED.

        It may mean UNKNOWN/PENDING until the bank confirms the result.
        TEXT,
        <<<'CODE'
        public enum SalaryBatchStatus {
            DRAFT,
            VALIDATED,
            APPROVED,
            SUBMITTED,
            PROCESSING,
            COMPLETED,
            PARTIALLY_COMPLETED,
            FAILED,
            CANCELLED
        }

        public enum SalaryItemStatus {
            PENDING,
            PROCESSING,
            SUCCESS,
            FAILED,
            UNKNOWN
        }

        public record SalaryBatchResult(
            int total,
            int success,
            int failed,
            int pending,
            int unknown
        ) {}
CODE,
        'Process 10,000 salary payments individually while maintaining one batch-level lifecycle.',
        'A large salary batch requires batch-level state plus individual payment state so partial success, failure and pending outcomes are never hidden.'
    ),

    $lesson(
        'What is retry and why can blindly retrying payments be dangerous?',
        <<<'TEXT'
        Solution:

        Retries are useful for transient failures.

        Example:

        Database connection temporarily fails.

        Retry may succeed.

        But financial operations are different.

        Suppose:

        POST /bank/transfer

        Rs 50,000

        The request reaches the bank.

        The bank successfully transfers the money.

        Before the response reaches our application, the network times out.

        Our application sees:

        TIMEOUT

        If it blindly retries:

        POST /bank/transfer
        Rs 50,000

        the bank might transfer another Rs 50,000.

        The customer could lose Rs 100,000 instead of Rs 50,000.

        Therefore payment requests need idempotency.

        Example:

        businessReference = TRANSFER-2026-00001

        The same reference must represent the same business transaction across retries.

        If the bank supports querying status:

        1. Query original reference.
        2. Confirm outcome.
        3. Retry only when safe.

        Never convert UNKNOWN into FAILED merely because a network timeout occurred.
        TEXT,
        <<<'CODE'
        public TransferResult transfer(
                String businessReference,
                BigDecimal amount
        ) {

            Transfer existing =
                transferRepository
                    .findByReference(businessReference)
                    .orElse(null);

            if (existing != null) {
                return existing.toResult();
            }

            Transfer transfer =
                transferRepository.createPending(
                    businessReference,
                    amount
                );

            bankClient.submit(
                businessReference,
                amount
            );

            return transfer.toResult();
        }
CODE,
        'Simulate a successful bank transaction followed by a network timeout and verify that the retry does not create a second transfer.',
        'Retries must distinguish safe transient operations from uncertain financial operations and use idempotency for the latter.'
    ),

    $lesson(
        'What is Spring Batch versus Kafka?',
        <<<'TEXT'
        Solution:

        Spring Batch and Kafka solve different problems.

        Spring Batch:

        Good for controlled large-scale processing.

        Example:

        Every night:

        Process 5 million employee records.

        Read -> process -> write in chunks.

        Kafka:

        Good for event streaming and asynchronous communication.

        Example:

        Candidate submits application.

        ApplicationSubmitted event is consumed by:

        Notification
        Analytics
        Audit
        Recommendation

        A system can use both.

        Example:

        Kafka receives:

        EmployeeSalaryApproved

        A Spring Batch process can later process a large reconciliation file.

        Do not select a technology merely because the data volume is large.

        The workflow determines the appropriate tool.
        TEXT,
        <<<'CODE'
        Kafka:

        ApplicationSubmitted
                |
                +--> Notification
                +--> Analytics
                +--> Audit

        Spring Batch:

        Payroll File
              |
        ItemReader
              |
        ItemProcessor
              |
        ItemWriter
              |
        Payroll Database
CODE,
        'Identify which parts of a job portal should use Kafka and which should use Spring Batch.',
        'Kafka is primarily for durable event streaming and asynchronous integration, while Spring Batch is structured for restartable bulk processing.'
    ),

    $lesson(
        'What is @SpringBootTest and when should it be used?',
        <<<'TEXT'
        Solution:

        Testing should happen at different levels.

        Unit test:

        Tests one class.

        Example:

        JobService

        Integration test:

        Tests several real Spring components.

        Example:

        Controller
        Service
        Repository
        Database

        @SpringBootTest:

        Loads the Spring Boot application context.

        This is useful when we want to verify that the actual application wiring works.

        Example:

        POST /api/jobs

        Request enters:

        Controller
          |
        Service
          |
        Repository
          |
        Database

        A broad integration test can verify the complete flow.

        However, using @SpringBootTest for every small test can make the test suite slow.

        Use unit tests for isolated business logic and integration tests for important interactions.
        TEXT,
        <<<'CODE'
        @SpringBootTest
        @ActiveProfiles("test")
        class JobApplicationFlowTest {

            @Autowired
            private MockMvc mockMvc;

            @Test
            void candidateCanApplyForOpenJob()
                    throws Exception {

                mockMvc.perform(
                    post("/api/jobs/100/applications")
                        .contentType("application/json")
                        .content("""
                            {
                              "candidateId": 50
                            }
                        """)
                )
                .andExpect(status().isCreated());
            }
        }
CODE,
        'Create one complete application-flow integration test and separate it from unit tests.',
        '@SpringBootTest loads the Spring Boot application context and is useful for broad integration testing.'
    ),

    $lesson(
        'What is MockMvc?',
        <<<'TEXT'
        Solution:

        MockMvc allows testing Spring MVC endpoints without starting a real external web server.

        Suppose:

        GET /api/jobs

        The test can verify:

        - HTTP status
        - JSON structure
        - headers
        - authentication
        - validation
        - controller behavior

        Example:

        Expected:

        200 OK

        and:

        content = array

        For validation:

        POST /api/jobs

        with missing title.

        Expected:

        400 Bad Request

        For unauthorized access:

        DELETE /api/jobs/100

        Expected:

        401 or 403 depending on authentication state and authorization rules.

        MockMvc is therefore useful for testing the HTTP/controller layer.
        TEXT,
        <<<'CODE'
        mockMvc.perform(
                get("/api/jobs")
                    .param("location", "Ludhiana")
                    .param("page", "0")
        )
        .andExpect(status().isOk())
        .andExpect(
            jsonPath("$.content").isArray()
        );

        mockMvc.perform(
                post("/api/jobs")
                    .contentType("application/json")
                    .content("""
                        {
                            "title": ""
                        }
                    """)
        )
        .andExpect(status().isBadRequest());
CODE,
        'Test successful job search, invalid job creation and unauthorized access using MockMvc.',
        'MockMvc tests Spring MVC request handling, validation, security and JSON responses without requiring an external HTTP server.'
    ),

    $lesson(
        'How do you manage production configuration and secrets?',
        <<<'TEXT'
        Solution:

        Never put production passwords or API secrets directly into Git.

        Bad:

        spring.datasource.password=MyRealPassword123

        Better:

        spring.datasource.password=${DB_PASSWORD}

        The production environment supplies:

        DB_PASSWORD

        Other examples:

        JWT_SECRET
        DATABASE_URL
        REDIS_PASSWORD
        KAFKA_USERNAME
        KAFKA_PASSWORD
        PAYMENT_API_KEY

        Different environments can use:

        application.yml
        application-dev.yml
        application-test.yml
        application-prod.yml

        The code remains the same.

        Only environment-specific configuration changes.

        Secrets should also be rotated and access should be limited according to least privilege.
        TEXT,
        <<<'CODE'
        spring:
          datasource:
            url: ${DATABASE_URL}
            username: ${DATABASE_USERNAME}
            password: ${DATABASE_PASSWORD}

        security:
          jwt:
            secret: ${JWT_SECRET}

        payment:
          api-key: ${PAYMENT_API_KEY}
CODE,
        'Remove hard-coded production credentials and load database, JWT and payment secrets through environment configuration.',
        'Production configuration should be externalized and secrets should be injected through controlled secret management rather than committed to source code.'
    ),

    $lesson(
        'How do you Dockerize a Spring Boot application?',
        <<<'TEXT'
        Solution:

        A Spring Boot application can be packaged as a JAR.

        Example:

        target/job-portal.jar

        A Docker image contains:

        - Java runtime
        - Application JAR
        - Required runtime configuration

        The container should not normally run as root.

        Environment variables can provide configuration.

        Example:

        DATABASE_URL
        DATABASE_USERNAME
        DATABASE_PASSWORD

        The image can then be deployed consistently across environments.

        Production images should use a maintained runtime, minimize unnecessary packages and keep dependency versions controlled.
        TEXT,
        <<<'CODE'
        FROM eclipse-temurin:21-jre

        WORKDIR /app

        RUN useradd --system --create-home appuser

        COPY target/job-portal.jar app.jar

        USER appuser

        EXPOSE 8080

        ENTRYPOINT ["java", "-jar", "app.jar"]
CODE,
        'Build the Spring Boot JAR, create a non-root Docker image and pass database configuration through environment variables.',
        'Docker packages the Spring Boot application and runtime into a reproducible deployment unit.'
    ),

    $lesson(
        'How do you deploy Spring Boot on Kubernetes?',
        <<<'TEXT'
        Solution:

        A Kubernetes deployment commonly contains:

        Deployment:
        Manages application replicas.

        Pod:
        Runs the Spring Boot container.

        Service:
        Provides stable internal networking.

        Ingress:
        Provides HTTP routing from outside the cluster.

        Example:

        Deployment
            |
            +-- Pod 1
            +-- Pod 2
            +-- Pod 3
                  |
               Service
                  |
               Ingress

        Readiness probe:

        Can this instance receive traffic?

        Liveness probe:

        Is the process healthy enough to continue running?

        Resource requests and limits help Kubernetes schedule and control workloads.

        For production applications, configuration and secrets should be externalized rather than baked into the image.
        TEXT,
        <<<'CODE'
        apiVersion: apps/v1
        kind: Deployment
        metadata:
          name: job-service
        spec:
          replicas: 3

          selector:
            matchLabels:
              app: job-service

          template:
            metadata:
              labels:
                app: job-service

            spec:
              containers:
                - name: job-service
                  image: job-service:1.0.0
                  ports:
                    - containerPort: 8080

                  readinessProbe:
                    httpGet:
                      path: /actuator/health/readiness
                      port: 8080

                  livenessProbe:
                    httpGet:
                      path: /actuator/health/liveness
                      port: 8080

                  resources:
                    requests:
                      cpu: "250m"
                      memory: "512Mi"
                    limits:
                      cpu: "1"
                      memory: "1Gi"
CODE,
        'Deploy three Spring Boot replicas with readiness and liveness probes.',
        'Kubernetes manages Spring Boot container replicas and provides service discovery, health checks and controlled deployment.'
    ),

    $lesson(
        'How do you design a complete production Spring Boot architecture?',
        <<<'TEXT'
        Solution:

        Consider the complete career platform.

        Users:

        Job seekers
        Recruiters
        HR
        Administrators

        Core services:

        Identity Service
        Company Service
        Job Service
        Application Service
        Interview Service
        Notification Service
        Payment Service
        Learning Service
        Analytics Service

        Request flow:

        React/Angular
              |
              v
        Load Balancer
              |
              v
        API Gateway
              |
              +---- Security
              |
              +---- Job Service
              |
              +---- Application Service
              |
              +---- Company Service
              |
              +---- Learning Service

        Application events:

        Application Service
              |
              v
        Kafka
              |
              +---- Notification
              +---- Analytics
              +---- Audit
              +---- Recommendation

        Storage:

        PostgreSQL/MySQL
              |
              +---- authoritative business data

        Redis
              |
              +---- cache
              +---- rate limits
              +---- temporary state

        OpenSearch/Elasticsearch
              |
              +---- job search

        Observability:

        Spring Boot Actuator
              |
        Prometheus
              |
        Grafana

        Deployment:

        Docker
              |
        Kubernetes

        Important production principles:

        1. Each service owns its business data.
        2. Database transactions protect local consistency.
        3. Kafka handles asynchronous events.
        4. Idempotency protects repeated requests.
        5. Redis accelerates repeated reads.
        6. Search engine handles complex search.
        7. Distributed tracing follows requests.
        8. Metrics identify bottlenecks.
        9. Retries are bounded.
        10. Financial operations are reconciled.
        11. Sensitive APIs are authenticated and authorized.
        12. Failures are represented explicitly instead of silently ignored.
        TEXT,
        <<<'CODE'
        Client
           |
           v
        Load Balancer
           |
           v
        API Gateway
           |
           +-------------------+
           |                   |
        Security            Routing
           |                   |
           +---------+---------+
                     |
           +---------+----------+----------+
           |         |          |          |
        Job       Company   Application  Learning
        Service   Service    Service     Service
           |         |          |
           |         |          +------+
           |         |                 |
           +---------+-------------- Kafka
                     |                 |
                 PostgreSQL      +-----+------+
                     |           |     |      |
                   Redis      Email Audit Analytics
                     |
              Search Index

        Observability:
        Actuator -> Prometheus -> Grafana

        Deployment:
        Docker -> Kubernetes
CODE,
        'Design the complete architecture for the career portal and identify the failure boundary of every major component.',
        'A production Spring Boot platform combines secure APIs, domain services, transactional databases, Kafka, Redis, search, observability and container orchestration.'
    ),

];
