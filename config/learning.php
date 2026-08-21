<?php

return [
    'java' => ['title' => 'Java & Spring Engineering', 'description' => 'Object-oriented Java through production APIs and batch processing.', 'modules' => [
        'class-and-object' => ['Class and Object', 'A class defines state and behavior; an object is a runtime instance with its own identity and values.', ['Encapsulation and access modifiers', 'Instance versus static members', 'Composition over inheritance']],
        'constructors' => ['Constructors', 'Constructors establish valid initial object state and may be overloaded or chained.', ['Default and parameterized constructors', 'this() and super()', 'Immutable object construction']],
        'inheritance' => ['Inheritance', 'Inheritance models an is-a relationship and enables controlled specialization through overriding.', ['extends and super', 'Method overriding', 'Avoid fragile deep hierarchies']],
        'interfaces' => ['Interfaces', 'Interfaces define behavioral contracts without coupling callers to an implementation.', ['Default methods', 'Dependency inversion', 'Interface segregation']],
        'abstraction' => ['Abstraction', 'Abstraction exposes essential behavior while hiding changeable implementation details.', ['Abstract classes', 'Ports and adapters', 'Choosing interface versus abstract class']],
        'streams' => ['Collections & Stream API', 'Streams express lazy data transformations; collections own in-memory data.', ['map, filter and reduce', 'Collectors and grouping', 'Parallel stream trade-offs']],
        'multithreading' => ['Multithreading', 'Concurrency coordinates multiple tasks while protecting shared state and system capacity.', ['Executors and futures', 'Locks and atomic operations', 'Thread safety and deadlocks']],
        'spring-api' => ['Spring Boot API Development', 'A production API separates transport validation, use-case logic, persistence and external integrations.', ['Controller and request DTO', 'Service and repository boundaries', 'Exception handling and tests']],
        'spring-batch' => ['Spring Batch', 'Spring Batch processes restartable, observable workloads using jobs, steps, readers, processors and writers.', ['Chunk processing', 'Checkpoints and retries', 'Partitioning and scheduling']],
        'microservices' => ['Microservices', 'Microservices trade local autonomy for distributed-system complexity and operational overhead.', ['Service boundaries', 'Idempotency and resilience', 'Events and observability']],
    ]],
    'laravel' => ['title' => 'PHP & Laravel Production Development', 'description' => 'From request validation to queues, authorization, documents and deployment.', 'modules' => [
        'php' => ['Modern PHP', 'Modern PHP uses strict types, objects, exceptions, Composer packages and framework-independent domain logic.', ['Types and nullability', 'OOP and SOLID', 'Exceptions and testing']],
        'request-lifecycle' => ['Laravel API Request Lifecycle', 'Define the route, validate through a Form Request, invoke a controller and service, persist through models, then transform the response.', ['routes/api.php endpoint', 'Form Request validation and authorization', 'Controller → service → model/resource']],
        'migrations' => ['Migrations', 'Migrations version database structure so deployments can apply and reverse schema changes predictably.', ['up and down methods', 'Indexes and foreign keys', 'Zero-downtime changes']],
        'composer' => ['Composer', 'Composer resolves PHP dependencies, generates autoload metadata and runs project lifecycle scripts.', ['composer.json constraints', 'Lock-file reproducibility', 'PSR-4 autoloading']],
        'service-providers' => ['Service Providers', 'Service providers register and boot container bindings, events, configuration and package integrations.', ['register versus boot', 'Singleton and scoped bindings', 'Deferred integrations']],
        'eloquent-loading' => ['Eager and Lazy Loading', 'Eager loading fetches known relationships in bounded queries; lazy loading may create an N+1 query problem.', ['with and load', 'withCount and constrained relationships', 'Prevent lazy loading in development']],
        'large-data' => ['Processing 10,000+ Records', 'Use cursor, lazy, chunkById or queued batches instead of loading every model into memory.', ['chunkById for updates', 'cursor/lazy for streaming', 'Database-side aggregation']],
        'events' => ['Events and Listeners', 'Events announce completed facts while listeners perform decoupled reactions such as notifications or audit logging.', ['Synchronous versus queued listeners', 'Transactional timing', 'Idempotent consumers']],
        'roles' => ['Roles and Permissions', 'Authorization maps authenticated people to explicit permissions, enforced through policies and gates.', ['Admin, compliance and sales permissions', 'Policies for model actions', 'Least privilege and audit trails']],
        'queues-cron' => ['Queues, Scheduler and Cron', 'Queues move slow work out of web requests; Laravel Scheduler defines timing and system cron invokes it.', ['Retries and failed jobs', 'withoutOverlapping locks', 'Supervisor/systemd workers']],
        'files-messaging' => ['PDF, Excel, Email and OTP', 'Document generation and messaging belong in dedicated services and queued jobs with secure temporary storage.', ['PDF templates and streamed downloads', 'Chunked spreadsheet exports', 'OTP expiry, throttling and hashing']],
        'ajax' => ['AJAX and JSON APIs', 'Browser code sends asynchronous HTTP requests, handles validation responses and updates UI state safely.', ['CSRF and authentication', '422 validation errors', 'Pagination and cancellation']],
        'deployment' => ['Laravel Deployment', 'A release installs locked dependencies, builds assets, migrates safely, warms caches and restarts workers.', ['Environment secrets', 'Atomic releases and rollback', 'Health checks and monitoring']],
    ]],
    'architecture' => ['title' => 'System Design', 'description' => 'Design maintainable components and reliable distributed platforms.', 'modules' => [
        'low-level-design' => ['Low-Level Design', 'LLD defines classes, interfaces, invariants, interactions and extensibility inside a component.', ['SOLID principles', 'Design patterns', 'Sequence and class diagrams']],
        'high-level-design' => ['High-Level Design', 'HLD chooses service boundaries, data stores, communication paths, scaling and failure strategies.', ['Capacity estimates', 'Caching and partitioning', 'Availability and consistency']],
        'data-handling' => ['Data Handling', 'Reliable data systems validate input, preserve invariants and define ownership, retention and access controls.', ['Transactions and isolation', 'Indexes and query plans', 'Encryption and audit logs']],
    ]],
    'dsa' => ['title' => 'Data Structures & Algorithms', 'description' => 'Complexity-aware problem solving for interviews and production.', 'modules' => [
        'binary-search-tree' => ['Binary Search Tree', 'A binary search tree maintains an ordering invariant: every key in a node’s left subtree is smaller and every key in its right subtree is larger. Balanced trees support search, insertion and deletion in logarithmic time; a skewed tree can degrade to linear time.', ['Search by comparing and choosing one subtree', 'Insert while preserving the ordering invariant', 'Delete leaf, one-child and two-child nodes', 'Inorder traversal produces sorted keys', 'AVL and red-black trees control height']],
        'foundations' => ['DSA Foundations', 'Select structures based on access, ordering, mutation and memory requirements.', ['Big-O analysis', 'Arrays, lists, stacks and queues', 'Hash maps, trees, heaps and graphs']],
        'problem-solving' => ['Interview Problem Solving', 'Clarify constraints, establish a correct baseline, optimize deliberately and test edge cases.', ['Brute force first', 'State invariants', 'Explain time and space complexity']],
    ]],
    'ai-data' => ['title' => 'Machine Learning & Data Science', 'description' => 'Data preparation, modeling, evaluation and responsible deployment.', 'modules' => [
        'data-science' => ['Data Science Workflow', 'Turn a business question into measurable analysis using trustworthy data and reproducible methods.', ['Cleaning and exploratory analysis', 'Statistics and experimentation', 'SQL, Python and visualization']],
        'machine-learning' => ['Machine Learning', 'ML learns patterns from examples and must be evaluated on unseen data against a meaningful baseline.', ['Supervised and unsupervised learning', 'Feature engineering', 'Cross-validation and metrics']],
        'ml-production' => ['Production ML', 'Production ML adds versioned data, repeatable training, monitored inference and safe rollback.', ['Data and model drift', 'Feature stores and pipelines', 'Responsible AI and observability']],
    ]],
];
