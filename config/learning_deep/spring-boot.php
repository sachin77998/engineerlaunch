<?php

// In-depth explanations for the Spring Boot track, keyed by lesson title (replaces the one-line summaries).
return [

'What is Spring Framework?' => <<<'MD'
## What it is
Spring Framework is an open-source framework for building Java applications. Its core idea is that you write plain Java classes for your business logic, and Spring takes care of the "plumbing": creating objects, connecting them together, managing database transactions, handling web requests, security and much more.

Before Spring, enterprise Java (EJB 2.x) forced developers to extend framework classes, write long XML files and deploy to heavy application servers just to call a database. Spring replaced that with simple classes ("POJOs" – Plain Old Java Objects) that are easy to test.

## The core building blocks
- IoC container: creates and manages your objects (called beans) and their lifetime.
- Dependency Injection (DI): supplies each object with the objects it needs, instead of the object creating them with new.
- AOP (Aspect-Oriented Programming): adds cross-cutting behaviour such as logging, security checks and transactions around your methods without changing them.
- Data access: JdbcTemplate, transaction management and integration with JPA/Hibernate.
- Spring MVC: the web layer for building websites and REST APIs.
- Testing support: start parts of the application in tests and replace real dependencies with mocks.

## Real-world example
A banking application needs AccountService to talk to AccountRepository, send SMS through NotificationService, and wrap every money transfer in a database transaction. With Spring, AccountService just declares what it needs; Spring builds the repository, the notification service and the transaction proxy, wires them together and hands back a ready-to-use service.

```java
@Service
public class AccountService {

    private final AccountRepository accounts;
    private final NotificationService notifications;

    // Spring supplies both objects; this class never calls "new"
    public AccountService(AccountRepository accounts, NotificationService notifications) {
        this.accounts = accounts;
        this.notifications = notifications;
    }

    @Transactional   // Spring opens and commits (or rolls back) the transaction
    public void transfer(long fromId, long toId, BigDecimal amount) {
        Account from = accounts.findById(fromId).orElseThrow();
        Account to = accounts.findById(toId).orElseThrow();
        from.debit(amount);
        to.credit(amount);
        notifications.sendDebitAlert(from, amount);
    }
}
```

## How it works at runtime
1. The application starts and creates the Spring container (ApplicationContext).
2. Spring reads your configuration (annotations or Java @Configuration classes) and finds the classes it should manage.
3. It creates each bean, resolving and injecting its dependencies first.
4. Where needed it wraps beans in proxies (for @Transactional, security, caching).
5. Your code calls the beans; Spring keeps managing their lifecycle until shutdown.

## Spring modules you will hear about
- Spring Core / Context – the container.
- Spring MVC / WebFlux – web applications (blocking / reactive).
- Spring Data JPA – repositories without writing SQL for common queries.
- Spring Security – authentication and authorization.
- Spring Boot – opinionated setup on top of all of the above.

Note: Spring is the framework; Spring Boot is a tool built on top of it that removes most of the setup work.

## Interview follow-ups
- Why is Spring called lightweight? Because your classes do not have to extend framework classes, and it runs in a plain JVM or an embedded server rather than a heavy application server.
- What problem does Spring solve? Tight coupling and boilerplate: object creation, wiring, transactions and configuration are handled by the framework.
- Is Spring only for web applications? No. It is used for batch jobs, messaging consumers, desktop and command-line applications too.
MD,

'What is Spring Boot?' => <<<'MD'
## What it is
Spring Boot is a project built on top of the Spring Framework that lets you create a production-ready Spring application with almost no setup. It follows "convention over configuration": it makes sensible default choices for you, and you only configure what is different in your application.

With plain Spring you had to choose and version every library, write configuration for the web server, the database connection pool, JSON conversion, logging and so on, then package a WAR file and deploy it into Tomcat. Spring Boot does all of this automatically.

## What Spring Boot gives you
- Starters: one dependency (for example spring-boot-starter-web) brings in a tested set of compatible libraries.
- Auto-configuration: Boot looks at the libraries on your classpath and configures beans for you (a DataSource when it sees a database driver, Jackson for JSON, Tomcat for the web).
- Embedded server: Tomcat, Jetty or Undertow runs inside your application, so you start it with java -jar.
- Externalised configuration: application.properties / application.yml, environment variables and profiles (dev, test, prod).
- Actuator: health checks, metrics and info endpoints for production monitoring.
- Spring Boot DevTools: automatic restart during development.

## A complete application in one class
```java
@SpringBootApplication
public class JobPortalApplication {
    public static void main(String[] args) {
        SpringApplication.run(JobPortalApplication.class, args);
    }
}

@RestController
@RequestMapping("/api/jobs")
class JobController {
    @GetMapping
    List<String> jobs() {
        return List.of("Java Developer - Pune", "Spring Boot Engineer - Bengaluru");
    }
}
```

```text
$ mvn spring-boot:run
Tomcat started on port 8080 (http) with context path ''
Started JobPortalApplication in 2.1 seconds

$ curl http://localhost:8080/api/jobs
["Java Developer - Pune","Spring Boot Engineer - Bengaluru"]
```

## What happens when it starts
1. SpringApplication.run creates the ApplicationContext.
2. @SpringBootApplication triggers component scanning and auto-configuration.
3. Auto-configuration sees spring-boot-starter-web, so it creates a DispatcherServlet, Jackson ObjectMapper and an embedded Tomcat.
4. Your @RestController is registered and Tomcat starts listening on port 8080.

## Configuration example
```properties
server.port=9090
spring.datasource.url=jdbc:mysql://localhost:3306/jobs
spring.datasource.username=portal
spring.jpa.hibernate.ddl-auto=validate
```

Note: Spring Boot does not replace Spring. Every Spring feature is still there; Boot only automates the setup and lets you override any default.

## Interview follow-ups
- How do you change the server port? server.port in application.properties or the SERVER_PORT environment variable.
- How do you run different settings in production? Profiles: application-prod.properties activated with spring.profiles.active=prod.
- How do you package it? mvn package produces an executable "fat JAR" containing your code, all libraries and the embedded server.
MD,

'Spring versus Spring Boot' => <<<'MD'
## The short difference
Spring Framework provides the features (dependency injection, MVC, transactions, security). Spring Boot provides the setup: it chooses dependencies, configures them automatically and packages everything into a runnable application.

## Side-by-side comparison
- Setup: Spring needs you to declare and version each library; Boot uses starters with managed versions.
- Configuration: Spring needs XML or Java config for the DispatcherServlet, DataSource, view resolver and more; Boot auto-configures them from the classpath.
- Server: a classic Spring web app is packaged as a WAR and deployed to an external Tomcat; a Boot app contains an embedded server and runs with java -jar.
- Production features: with Spring you add monitoring yourself; Boot provides Actuator health and metrics endpoints.
- Learning curve: Spring requires knowing every piece of infrastructure; Boot lets beginners start quickly and learn the details later.

## The same web application, both ways
Classic Spring MVC (simplified): you configure the servlet yourself.

```java
public class WebInitializer extends AbstractAnnotationConfigDispatcherServletInitializer {
    protected Class<?>[] getRootConfigClasses() { return new Class[]{ DataConfig.class }; }
    protected Class<?>[] getServletConfigClasses() { return new Class[]{ WebConfig.class }; }
    protected String[] getServletMappings() { return new String[]{"/"}; }
}

@Configuration
@EnableWebMvc
@ComponentScan("com.portal")
public class WebConfig implements WebMvcConfigurer { /* view resolvers, converters ... */ }

@Configuration
@EnableTransactionManagement
public class DataConfig {
    @Bean DataSource dataSource() { /* create and tune a connection pool by hand */ }
    @Bean LocalContainerEntityManagerFactoryBean entityManagerFactory() { /* ... */ }
    @Bean PlatformTransactionManager transactionManager() { /* ... */ }
}
```

Spring Boot: the same infrastructure comes from starters and three properties.

```java
@SpringBootApplication
public class PortalApplication {
    public static void main(String[] args) { SpringApplication.run(PortalApplication.class, args); }
}
```

```properties
spring.datasource.url=jdbc:mysql://localhost:3306/portal
spring.datasource.username=portal
spring.datasource.password=secret
```

## When would you still configure things yourself?
Whenever the default is not right: a second database, a custom JSON date format, special security rules. You declare your own @Bean and Boot's auto-configuration steps back (most auto-configurations are conditional on @ConditionalOnMissingBean).

Note: in interviews, avoid saying "Spring Boot is better than Spring". The accurate answer is that Boot is Spring with automated setup and production defaults.

## Interview follow-ups
- Can a Spring Boot app be deployed as a WAR? Yes, extend SpringBootServletInitializer and set packaging to war.
- Which one do new projects use? Almost always Spring Boot, because it produces the same Spring application with far less configuration.
MD,

'What does @SpringBootApplication do?' => <<<'MD'
## What it is
@SpringBootApplication is a convenience annotation placed on the main class of a Spring Boot application. It switches on the three things almost every Boot application needs, so you do not have to write them separately.

It is equivalent to:

```java
@SpringBootConfiguration      // a specialised @Configuration
@EnableAutoConfiguration      // let Boot configure beans from the classpath
@ComponentScan                // find @Component, @Service, @Repository, @Controller classes
public class PortalApplication { }
```

## The three parts explained
1. @SpringBootConfiguration (a @Configuration): marks the class as a source of bean definitions, so you may declare @Bean methods inside it.
2. @EnableAutoConfiguration: Boot reads the list of auto-configuration classes shipped in its JARs (META-INF/spring/...AutoConfiguration.imports) and applies those whose conditions match. Example: if a MySQL driver and spring-boot-starter-data-jpa are on the classpath, a DataSource, EntityManagerFactory and TransactionManager are created.
3. @ComponentScan: scans the package of the main class and all sub-packages for your components and registers them as beans.

## Example and what happens at startup
```java
package com.ascendia.portal;

@SpringBootApplication
public class PortalApplication {
    public static void main(String[] args) {
        ApplicationContext context = SpringApplication.run(PortalApplication.class, args);
        System.out.println("Beans created: " + context.getBeanDefinitionCount());
    }
}

package com.ascendia.portal.jobs;      // sub-package: found by component scan
@Service
public class JobService { }

package com.other.billing;             // outside the base package: NOT found
@Service
public class InvoiceService { }
```

```text
Started PortalApplication in 1.9 seconds
Beans created: 214
```

JobService becomes a bean; InvoiceService does not, because it lives outside com.ascendia.portal.

## Customising it
```java
@SpringBootApplication(
    scanBasePackages = {"com.ascendia.portal", "com.other.billing"},   // scan more packages
    exclude = { DataSourceAutoConfiguration.class }                    // skip one auto-configuration
)
public class PortalApplication { }
```

## Common mistakes
- Putting the main class in a sub-package (com.ascendia.portal.app): classes in com.ascendia.portal.jobs are then not scanned. Keep the main class in the root package.
- Adding @EnableWebMvc to a Boot app: it switches off Boot's MVC auto-configuration and can break JSON or static resources.
- Expecting a bean from another module to be found automatically when its package is not under the base package.

Note: the main method only calls SpringApplication.run; all the work is done by the annotation and the container.

## Interview follow-ups
- How do you see which auto-configurations were applied? Start with --debug to print the conditions evaluation report.
- How do you disable one auto-configuration? exclude on the annotation or spring.autoconfigure.exclude in properties.
MD,

'What is auto-configuration?' => <<<'MD'
## What it is
Auto-configuration is Spring Boot's mechanism for creating and configuring beans automatically, based on what it finds in your application: the libraries on the classpath, the beans you already defined, and your properties.

Think of it as a set of "if this, then configure that" rules written by the Spring team:
- If a database driver and Spring Data JPA are on the classpath and no DataSource is defined, create a HikariCP DataSource from spring.datasource.* properties.
- If spring-webmvc is present, create a DispatcherServlet and an embedded Tomcat.
- If Jackson is present, create an ObjectMapper for JSON.

## How it works internally
1. @EnableAutoConfiguration (inside @SpringBootApplication) loads the list of candidate auto-configuration classes from META-INF/spring/org.springframework.boot.autoconfigure.AutoConfiguration.imports in each Boot JAR.
2. Each candidate is an ordinary @Configuration class guarded by @Conditional annotations.
3. Spring evaluates the conditions; only matching configurations contribute beans.
4. Your own beans are processed first, so @ConditionalOnMissingBean lets Boot back off when you define your own.

## What a real auto-configuration looks like (simplified)
```java
@AutoConfiguration
@ConditionalOnClass(DataSource.class)                  // only if JDBC classes exist
@EnableConfigurationProperties(DataSourceProperties.class)
public class DataSourceAutoConfiguration {

    @Bean
    @ConditionalOnMissingBean(DataSource.class)        // back off if you defined one
    public DataSource dataSource(DataSourceProperties properties) {
        return properties.initializeDataSourceBuilder().build();
    }
}
```

## Common conditions
- @ConditionalOnClass / @ConditionalOnMissingClass – depends on the classpath.
- @ConditionalOnBean / @ConditionalOnMissingBean – depends on other beans.
- @ConditionalOnProperty – depends on a setting, e.g. feature.payments.enabled=true.
- @ConditionalOnWebApplication – only in web applications.

## Overriding the default (payment example)
Boot would create one DataSource. A payments service needs a tuned pool, so it defines its own and Boot steps back:

```java
@Configuration
public class PaymentDbConfig {
    @Bean
    public DataSource dataSource() {
        HikariDataSource ds = new HikariDataSource();
        ds.setJdbcUrl("jdbc:mysql://payments-db:3306/payments");
        ds.setMaximumPoolSize(40);
        return ds;
    }
}
```

## Seeing what was configured
```text
$ java -jar portal.jar --debug
============================
CONDITIONS EVALUATION REPORT
============================
Positive matches:
   DataSourceAutoConfiguration matched: @ConditionalOnClass found required classes 'javax.sql.DataSource'
Negative matches:
   MongoAutoConfiguration: @ConditionalOnClass did not find required class 'com.mongodb.client.MongoClient'
```

Note: auto-configuration never removes your control. Anything it creates can be replaced by your own bean or tuned through properties.

## Interview follow-ups
- How do you exclude one? @SpringBootApplication(exclude = X.class) or spring.autoconfigure.exclude.
- Can you write your own? Yes: a @AutoConfiguration class with conditions, registered in the AutoConfiguration.imports file of your library (this is how custom starters work).
MD,

'What are Spring Boot starters?' => <<<'MD'
## What they are
Spring Boot starters are pre-configured dependency packages. Each starter is a single Maven/Gradle dependency that pulls in every library needed for one feature, in versions that are tested to work together. You no longer hunt for compatible versions of Hibernate, Jackson, Tomcat and so on.

- They provide ready-made dependency bundles for a feature (web, data, security, testing).
- The Spring Boot parent / BOM manages compatible versions, so you omit version numbers.
- They reduce the size and mistakes of your pom.xml or build.gradle.

## Example: one starter instead of many dependencies
```xml
<dependencies>
    <dependency>
        <groupId>org.springframework.boot</groupId>
        <artifactId>spring-boot-starter-data-jpa</artifactId>
    </dependency>
</dependencies>
```

This single dependency brings Spring Data JPA, Hibernate, the JDBC layer, the HikariCP connection pool and transaction management. Earlier, developers had to add and version each of these by hand. Combined with auto-configuration, adding the starter plus a database URL is enough to start using repositories.

## Naming convention
- Official starters: spring-boot-starter-* where * is the technology (web, data-jpa, security, test).
- The prefix spring-boot is reserved for official starters.
- Third-party/custom starters start with the project name, e.g. ascendia-audit-spring-boot-starter.

## Most used application starters
- spring-boot-starter – core: auto-configuration, logging, YAML support.
- spring-boot-starter-web – Spring MVC, REST, JSON (Jackson) and embedded Tomcat.
- spring-boot-starter-webflux – reactive web applications.
- spring-boot-starter-data-jpa – Spring Data JPA with Hibernate.
- spring-boot-starter-jdbc – JDBC with the HikariCP pool.
- spring-boot-starter-data-redis / data-mongodb / data-elasticsearch – other data stores.
- spring-boot-starter-security – Spring Security; oauth2-client and oauth2-resource-server for OAuth2/JWT.
- spring-boot-starter-validation – Bean Validation (@NotNull, @Email) with Hibernate Validator.
- spring-boot-starter-mail, -cache, -batch, -quartz, -amqp (RabbitMQ), -websocket.
- spring-boot-starter-test – JUnit 5, Mockito, AssertJ, Spring Test.

## Production and technical starters
- spring-boot-starter-actuator – health, metrics and monitoring endpoints.
- spring-boot-starter-tomcat (default), -jetty or -undertow – choose the embedded server.
- spring-boot-starter-logging (Logback, default) or -log4j2.

## Swapping a technical starter
```xml
<dependency>
    <groupId>org.springframework.boot</groupId>
    <artifactId>spring-boot-starter-web</artifactId>
    <exclusions>
        <exclusion>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-tomcat</artifactId>
        </exclusion>
    </exclusions>
</dependency>
<dependency>
    <groupId>org.springframework.boot</groupId>
    <artifactId>spring-boot-starter-jetty</artifactId>
</dependency>
```

## Advantages
- Less configuration time and fewer dependency conflicts.
- Tested, production-ready combinations of libraries.
- No need to remember artifact names and versions.

Note: a starter only adds libraries. Auto-configuration is what turns those libraries into configured beans.

## Interview follow-ups
- How do you see what a starter brings in? mvn dependency:tree or the Maven tab in your IDE.
- How would you build a custom starter? Create an autoconfigure module with @AutoConfiguration classes plus a starter module that depends on it and on the required libraries.
MD,

'What is an embedded server?' => <<<'MD'
## What it is
An embedded server is a web server (Tomcat, Jetty or Undertow) that runs inside your application instead of your application running inside a separately installed server. Your Spring Boot JAR contains the server, so starting the application starts the server.

## Old way versus embedded way
Traditional deployment:
1. Install Tomcat on the server.
2. Build a WAR file.
3. Copy the WAR into Tomcat's webapps folder.
4. Configure ports and settings in Tomcat's own files.

Spring Boot:
1. mvn package builds one executable JAR.
2. java -jar portal.jar starts the application and its server on the configured port.

```text
$ java -jar portal.jar
Tomcat initialized with port 8080 (http)
Tomcat started on port 8080 (http) with context path ''
Started PortalApplication in 2.4 seconds
```

## Why it matters
- Same runtime everywhere: the laptop, the test server and production run identical server versions.
- Perfect for Docker and Kubernetes: one container runs one java -jar process.
- Each microservice can run its own server on its own port.
- No separate server installation to patch and maintain.

## Configuring the embedded server
```properties
server.port=8081
server.servlet.context-path=/portal
server.tomcat.threads.max=200
server.compression.enabled=true
server.ssl.enabled=true
server.ssl.key-store=classpath:keystore.p12
```

## Choosing a different server
- Tomcat – default, most widely used.
- Jetty – lightweight, popular for long-lived connections.
- Undertow – high-throughput, non-blocking core.
- Reactor Netty – used with WebFlux for reactive applications.
Switch by excluding spring-boot-starter-tomcat and adding spring-boot-starter-jetty or -undertow.

## Real-world example
A payment company runs 30 microservices. Each is a Docker image built FROM a JRE with CMD java -jar service.jar. The orders service listens on 8081, payments on 8082. Kubernetes restarts any instance whose /actuator/health check fails. None of this needs a shared application server.

Note: you can still deploy a Boot app as a WAR to an external Tomcat if your organisation requires it, by extending SpringBootServletInitializer.

## Interview follow-ups
- How do you run on a random port in tests? server.port=0 or @SpringBootTest(webEnvironment = RANDOM_PORT).
- What thread handles each request in Tomcat? A worker thread from Tomcat's pool (server.tomcat.threads.max).
MD,

'What is Inversion of Control?' => <<<'MD'
## What it is
Inversion of Control (IoC) is a design principle where the control of creating and connecting objects moves from your code to a container. Normally a class creates the objects it needs with new; with IoC, the Spring IoC container creates them, wires them together and manages their lifecycle. The control is "inverted": your code no longer controls object creation, the framework does.

The Spring IoC container:
- creates, configures and manages application objects (beans);
- injects required dependencies into beans (Dependency Injection is how IoC is achieved);
- manages bean creation, initialization and destruction;
- supports XML, Java-based and annotation-based configuration;
- separates object creation from business logic, making code flexible and testable.

## Types of IoC containers
1. BeanFactory – the basic container providing dependency injection and lifecycle management. Beans are created lazily, when first requested.
2. ApplicationContext – extends BeanFactory and adds event publishing, internationalization, annotation support and eager creation of singletons. Spring Boot always uses an ApplicationContext.

## Example: a mobile phone and its SIM
Interface and two implementations:

```java
public interface Sim {
    void calling();
    void data();
}

public class Airtel implements Sim {
    public void calling() { System.out.println("Airtel Calling"); }
    public void data()    { System.out.println("Airtel Data"); }
}

public class Jio implements Sim {
    public void calling() { System.out.println("Jio Calling"); }
    public void data()    { System.out.println("Jio Data"); }
}
```

## Without IoC: tight coupling
```java
public class Mobile {
    public static void main(String[] args) {
        Sim sim = new Jio();      // Mobile decides and creates the dependency
        sim.calling();
        sim.data();
    }
}
```

```text
Jio Calling
Jio Data
```

Mobile is tightly coupled to Jio. Switching to Airtel means editing and recompiling Mobile.

## With IoC: Java-based configuration
```java
@Configuration
public class AppConfig {
    @Bean
    public Sim sim() {
        return new Jio();         // change to new Airtel() here only
    }
}

public class Mobile {
    public static void main(String[] args) {
        ApplicationContext context = new AnnotationConfigApplicationContext(AppConfig.class);
        Sim sim = context.getBean(Sim.class);   // the container created it
        sim.calling();
        sim.data();
    }
}
```

The same can be expressed in XML (<bean id="sim" class="com.example.ioc.Jio"/>), which older applications use.

## With IoC: annotations (how Spring Boot apps do it)
```java
@Component
public class Jio implements Sim { /* ... */ }

@Component
public class Mobile {
    private final Sim sim;

    public Mobile(Sim sim) {      // the container injects the Sim
        this.sim = sim;
    }

    public void useSim() {
        sim.calling();
        sim.data();
    }
}
```

## When two implementations exist
If both Airtel and Jio are @Component, Spring throws NoUniqueBeanDefinitionException because it cannot choose. Fix it with:
- @Primary on the default implementation;
- @Qualifier("jio") at the injection point;
- explicit bean names, e.g. @Component("jioBean") with @Qualifier("jioBean").

## How the container builds the graph (job portal)
1. Spring scans and finds JobRepository, JobService and JobController.
2. It creates JobRepository first (it has no dependencies).
3. It creates JobService and injects JobRepository.
4. It creates JobController and injects JobService.
5. It keeps these singletons and closes them at shutdown.

## Benefits
- Loose coupling: classes depend on interfaces, not concrete classes.
- Easy testing: pass a mock Sim or a fake repository in unit tests.
- Central configuration: change an implementation in one place.
- Lifecycle management: initialization and cleanup hooks are handled for you.

Note: IoC is the principle; Dependency Injection is the technique Spring uses to implement it.

## Quiz
Which of these is a feature of Spring's IoC? Dependency Injection. (IoC does not manage thread pools, database connections by itself, or inheritance.)
MD,

'What is Dependency Injection?' => <<<'MD'
## What it is
Dependency Injection (DI) is the technique Spring uses to implement Inversion of Control. A dependency is any object your class needs to do its work (a repository, a service, an HTTP client). Instead of the class creating that object, the container "injects" it from outside, usually through the constructor.

## The problem DI solves
```java
// Without DI: OrderService is welded to one payment gateway
public class OrderService {
    private final PaymentGateway gateway = new RazorpayGateway();   // hard-coded
}
```
You cannot switch to Stripe, you cannot test without calling Razorpay, and every OrderService creates its own gateway.

```java
// With DI: OrderService asks for any PaymentGateway
@Service
public class OrderService {
    private final PaymentGateway gateway;

    public OrderService(PaymentGateway gateway) {   // injected by Spring
        this.gateway = gateway;
    }

    public PaymentResult checkout(Order order) {
        return gateway.charge(order.total(), order.idempotencyKey());
    }
}
```

## Three ways to inject
1. Constructor injection (recommended):
```java
@Service
public class JobService {
    private final JobRepository jobs;
    public JobService(JobRepository jobs) { this.jobs = jobs; }   // @Autowired optional with one constructor
}
```
Dependencies are final, required, and the class is easy to construct in a test.

2. Setter injection (for optional dependencies):
```java
@Service
public class ReportService {
    private MailSender mail;
    @Autowired(required = false)
    public void setMail(MailSender mail) { this.mail = mail; }
}
```

3. Field injection (works, but avoid in production code):
```java
@Service
public class JobService {
    @Autowired private JobRepository jobs;   // hidden dependency, hard to test, cannot be final
}
```

## How Spring resolves what to inject
1. By type: find beans assignable to the parameter type (PaymentGateway).
2. If exactly one exists, inject it.
3. If several exist, use @Primary or @Qualifier, or match the parameter name to a bean name.
4. If none exists and the dependency is required, startup fails with NoSuchBeanDefinitionException – a helpful early error.

## Testing benefit
```java
class OrderServiceTest {
    @Test
    void chargesTheOrderTotal() {
        PaymentGateway fake = (amount, key) -> PaymentResult.success("pay_123");
        OrderService service = new OrderService(fake);            // no Spring needed
        assertEquals("pay_123", service.checkout(sampleOrder()).paymentId());
    }
}
```

## Common mistakes
- Circular dependencies (A needs B, B needs A) – usually a design problem; split responsibilities or use events.
- Calling new on a class that has injected dependencies: the object is not a Spring bean, so its fields stay null.
- Injecting too many dependencies into one class – a sign it does too much.

Note: prefer constructor injection; it makes dependencies explicit and lets fields be final.

## Interview follow-ups
- IoC vs DI? IoC is the principle (the container is in control); DI is the mechanism (dependencies are supplied).
- Why constructor injection? Immutability, required dependencies, no reflection needed in tests, and circular dependencies are detected immediately.
MD,

'What is a Spring Bean?' => <<<'MD'
## What it is
A Spring Bean is any object that is created, configured and managed by the Spring IoC container. Your JobService becomes a bean when Spring creates it; the same class created with new is just an ordinary object that Spring knows nothing about.

## Ways to declare a bean
1. Stereotype annotations on your own classes (found by component scanning):
```java
@Component  public class PdfGenerator { }
@Service    public class JobService { }
@Repository public class JobRepositoryImpl { }
@RestController public class JobController { }
```

2. @Bean methods inside a @Configuration class (for third-party classes you cannot annotate):
```java
@Configuration
public class HttpConfig {
    @Bean
    public RestClient razorpayClient() {
        return RestClient.builder().baseUrl("https://api.razorpay.com/v1").build();
    }
}
```

3. XML (<bean id="..." class="..."/>) in older applications.

## What the container does with a bean
1. Instantiates it (calls the constructor).
2. Injects its dependencies.
3. Calls initialization callbacks (@PostConstruct).
4. Wraps it in a proxy if needed (@Transactional, @Cacheable, security).
5. Hands it out wherever it is injected.
6. Calls destruction callbacks (@PreDestroy) on shutdown, for singletons.

## Bean names and scope
- The default name is the class name with a lower-case first letter (jobService) or the @Bean method name (razorpayClient).
- The default scope is singleton: one shared instance per container. Other scopes include prototype (new instance per injection), request and session (web).

```java
@Service
@Scope("prototype")
public class ResumeParser { }   // a new parser every time one is requested
```

## Seeing your beans
```java
ApplicationContext ctx = SpringApplication.run(PortalApplication.class, args);
System.out.println(ctx.getBean(JobService.class));
System.out.println(Arrays.toString(ctx.getBeanNamesForType(JobService.class)));
```

```text
com.ascendia.portal.jobs.JobService@5b1d2887
[jobService]
```

## Bean versus ordinary object
```java
JobService manual = new JobService(null);   // not a bean: no injection, no @Transactional proxy
@Autowired JobService managed;              // a bean: dependencies injected, proxies applied
```

Note: singleton beans are shared across all requests, so do not store request-specific data (like the current user) in their fields.

## Interview follow-ups
- @Component vs @Bean? @Component goes on your class and is discovered by scanning; @Bean is a factory method you write, useful for library classes or conditional creation.
- Are singleton beans thread-safe? Spring does not make them thread-safe; keep them stateless or protect shared state.
MD,

];
