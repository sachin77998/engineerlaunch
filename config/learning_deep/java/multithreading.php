<?php

// Java › Multithreading, in depth.
return [

'What is a process?' => <<<'MD'
## What it is
A process is a running instance of a program. When you start a Java application, the operating system creates a process for the JVM with its own:
- memory address space (code, heap, stacks),
- open files and network sockets,
- environment, security context and process id (PID).

Processes are isolated from each other: one process cannot read another's memory directly. They communicate through inter-process communication (pipes, sockets, files, shared memory, message queues).

## Example
Running the job portal API and a separate report generator gives two JVM processes:

```text
$ jps
4120 PortalApplication      ← process 1 (its own heap)
4388 ReportGenerator        ← process 2 (its own heap)
```

## Process vs thread (preview)
- A process can contain many threads.
- Threads inside one process share the same heap memory; processes do not share memory.
- Creating a process is expensive (new address space); creating a thread is cheaper.
- A crash in one process does not kill another; an uncaught error in a thread can corrupt shared state of its process.

## Starting another process from Java
```java
Process p = new ProcessBuilder("java", "-version").redirectErrorStream(true).start();
try (BufferedReader r = new BufferedReader(new InputStreamReader(p.getInputStream()))) {
    r.lines().forEach(System.out::println);
}
System.out.println("Exit code: " + p.waitFor());
```

Note: microservices run as separate processes (often in separate containers) so a failure or memory leak in one service does not take down the others.
MD,

'What is a thread?' => <<<'MD'
## What it is
A thread is a lightweight, independent path of execution inside a process. Every Java program starts with one thread – the main thread – and can create more. All threads of a process share the heap (objects, static fields) but each has its own stack (local variables, method calls) and program counter.

```text
Process (JVM)
├── Heap (shared): objects, static data
├── Thread "main"        → own stack
├── Thread "worker-1"    → own stack
└── Thread "worker-2"    → own stack
```

## Seeing threads
```java
public class ThreadInfo {
    public static void main(String[] args) {
        Thread current = Thread.currentThread();
        System.out.println(current.getName() + " priority " + current.getPriority());

        Thread worker = new Thread(() ->
            System.out.println("Running in " + Thread.currentThread().getName()), "worker-1");
        worker.start();
    }
}
```

```text
Output
main priority 5
Running in worker-1
```

## Thread lifecycle (states)
- NEW – created, start() not called yet.
- RUNNABLE – running or ready to run.
- BLOCKED – waiting to acquire a monitor lock.
- WAITING / TIMED_WAITING – waiting for another thread or a timeout (join, wait, sleep).
- TERMINATED – run() finished.

```text
NEW --start()--> RUNNABLE --(lock busy)--> BLOCKED --(lock free)--> RUNNABLE
                     |--sleep()/wait()--> TIMED_WAITING / WAITING --> RUNNABLE
                     |--run() ends--> TERMINATED
```

## Daemon threads
Background threads (garbage collector, monitoring) that do not keep the JVM alive. setDaemon(true) before start().

## Virtual threads (Java 21)
Very cheap threads managed by the JVM rather than the OS, suitable for millions of blocking tasks (HTTP calls, database queries):

```java
try (var executor = Executors.newVirtualThreadPerTaskExecutor()) {
    for (int i = 0; i < 10_000; i++) executor.submit(() -> callPaymentApi());
}
```

Note: call start(), never run() directly – run() executes on the current thread and creates no new thread.
MD,

'What is multithreading?' => <<<'MD'
## What it is
Multithreading in Java is a feature that lets a program run multiple threads at the same time, so tasks execute concurrently (and truly in parallel on multi-core CPUs) and the CPU is used more efficiently. A thread is a lightweight, independent unit of execution inside a process.
- Threads allow parallel execution of tasks.
- A process can have multiple threads.
- Each thread runs independently but shares the same memory.

Example: imagine a restaurant kitchen. Several chefs (threads) prepare different dishes at the same time. Service is faster and every stove (CPU core) is in use.

```text
          Main program (restaurant manager)
                       |
          starts multiple threads (chefs)
     ┌──────────┬──────────┬──────────┐
 Thread 1    Thread 2   Thread 3   Thread 4
 (Chef A)    (Chef B)   (Chef C)   (Chef D)
  run()       run()      run()      run()
     └──────────┴──── concurrent execution ┘
```

## Way 1: extending the Thread class
Create a class that extends Thread and override run() with the task. Create objects and call start(), which creates a new thread and calls run() on it.

```java
class CookingTask extends Thread {
    private final String task;

    CookingTask(String task) { this.task = task; }

    @Override
    public void run() {
        System.out.println(task + " is being prepared by " + Thread.currentThread().getName());
    }
}

public class Restaurant {
    public static void main(String[] args) {
        Thread t1 = new CookingTask("Pasta");
        Thread t2 = new CookingTask("Salad");
        Thread t3 = new CookingTask("Dessert");
        Thread t4 = new CookingTask("Rice");
        t1.start(); t2.start(); t3.start(); t4.start();
    }
}
```

```text
Output (one possible order)
Rice is being prepared by Thread-3
Pasta is being prepared by Thread-0
Dessert is being prepared by Thread-2
Salad is being prepared by Thread-1
```

Explanation:
- We created four threads (t1–t4) from the CookingTask class; each represents a dish.
- start() creates a new thread with its own call stack and internally invokes run(), so the threads run concurrently with main and with each other.
- Execution order is not fixed: after start(), the JVM and operating system decide when each thread runs, so output may differ on every run.

## Way 2: implementing Runnable
Create a class that implements java.lang.Runnable, define run(), pass an instance to a Thread constructor and call start().

```java
class CookingJob implements Runnable {
    private final String task;
    CookingJob(String task) { this.task = task; }

    @Override
    public void run() {
        System.out.println(task + " is being prepared by " + Thread.currentThread().getName());
    }
}

public class RestaurantRunnable {
    public static void main(String[] args) {
        Thread t1 = new Thread(new CookingJob("Soup"));
        Thread t2 = new Thread(new CookingJob("Pizza"));
        Thread t3 = new Thread(new CookingJob("Burger"));
        t1.start(); t2.start(); t3.start();
    }
}
```

```text
Output (one possible order)
Burger is being prepared by Thread-2
Pizza is being prepared by Thread-1
Soup is being prepared by Thread-0
```

With Java 8 lambdas: new Thread(() -> System.out.println("Tea ready")).start();

## Which way to use
- extends Thread: only if your class does not extend any other class.
- implements Runnable: preferred – your class can still extend another class (Java has no multiple inheritance), and the task is separated from the thread that runs it.
- In real applications use an ExecutorService (thread pool) rather than creating threads by hand.

## Waiting for threads to finish
```java
t1.join();   // main waits until t1 completes
```

## Advantages
- Improved performance: multiple tasks run simultaneously.
- Efficient CPU utilisation: cores stay busy.
- Responsiveness: GUIs and servers stay responsive while background work runs.
- Resource sharing: threads share memory, avoiding duplication.
- Better user experience: downloads, animations and real-time updates run smoothly.

## Challenges
- Race conditions when threads modify shared data (use synchronization or atomic classes).
- Deadlocks, starvation, harder debugging and testing.
- Too many threads add context-switching overhead.

Note: a web server like Tomcat is multithreaded – each incoming HTTP request is handled by a thread from a pool.
MD,

'What is the difference between Thread and Runnable?' => <<<'MD'
## The difference
- Thread is a class that represents a thread of execution (it also implements Runnable).
- Runnable is a functional interface with one method, void run(), that represents a task.

So Runnable describes what to do; Thread is the worker that does it.

## Side by side
```java
// Extending Thread: task and thread are the same object
class EmailSender extends Thread {
    @Override public void run() { System.out.println("Sending email"); }
}
new EmailSender().start();

// Implementing Runnable: task is separate from the thread
class SmsSender implements Runnable {
    @Override public void run() { System.out.println("Sending SMS"); }
}
new Thread(new SmsSender()).start();

// Lambda Runnable
Runnable push = () -> System.out.println("Sending push notification");
new Thread(push).start();
```

## Comparison
- Inheritance: extending Thread uses up your single superclass; implementing Runnable leaves you free to extend another class.
- Separation of concerns: Runnable separates the task from the execution mechanism, so the same task can run in a Thread, an ExecutorService or a scheduler.
- Reuse: one Runnable instance can be submitted many times or shared by several threads.
- Thread pools accept Runnable/Callable, not Thread subclasses.
- Extending Thread is only worthwhile if you need to customise thread behaviour itself (rare).

## Same task, many workers
```java
Runnable counter = new Runnable() {
    private final AtomicInteger count = new AtomicInteger();
    public void run() { System.out.println(Thread.currentThread().getName() + " -> " + count.incrementAndGet()); }
};
new Thread(counter, "A").start();
new Thread(counter, "B").start();   // both threads share the same Runnable state
```

## Limitation of Runnable
run() cannot return a value or throw checked exceptions. When you need a result, use Callable with an ExecutorService.

Note: interview answer – "prefer Runnable (or Callable) and run it with an ExecutorService".
MD,

'What is Callable?' => <<<'MD'
## What it is
Callable<V> is a functional interface like Runnable, but its call() method returns a result of type V and may throw checked exceptions. It is submitted to an ExecutorService, which returns a Future<V> holding the eventual result.

```java
@FunctionalInterface
public interface Callable<V> {
    V call() throws Exception;
}
```

## Runnable vs Callable
- Return value: run() returns void; call() returns V.
- Exceptions: run() cannot throw checked exceptions; call() can.
- Usage: Runnable with Thread or executor; Callable with ExecutorService.submit or invokeAll.

## Example: fetch prices from three suppliers in parallel
```java
ExecutorService pool = Executors.newFixedThreadPool(3);

Callable<Integer> supplierA = () -> { Thread.sleep(300); return 1200; };
Callable<Integer> supplierB = () -> { Thread.sleep(500); return 1150; };
Callable<Integer> supplierC = () -> { Thread.sleep(200); return 1300; };

List<Future<Integer>> quotes = pool.invokeAll(List.of(supplierA, supplierB, supplierC));
int best = Integer.MAX_VALUE;
for (Future<Integer> q : quotes) best = Math.min(best, q.get());   // get() waits for each result
System.out.println("Best price: " + best);
pool.shutdown();
```

```text
Output (after about 500 ms instead of 1000 ms sequentially)
Best price: 1150
```

## Exception handling
If call() throws, future.get() throws an ExecutionException wrapping the original cause:

```java
try {
    future.get();
} catch (ExecutionException e) {
    System.out.println("Task failed: " + e.getCause().getMessage());
}
```

Note: for composing many asynchronous steps (call API → transform → save), CompletableFuture is more convenient than raw Callable/Future.
MD,

'What is Future?' => <<<'MD'
## What it is
A Future<V> represents the result of an asynchronous computation that may not have finished yet. You get one when you submit a Callable (or Runnable) to an ExecutorService.

## Methods
- get() – wait (block) until the result is ready, then return it.
- get(timeout, unit) – wait at most the given time, else TimeoutException.
- isDone() – has the task finished (normally, with an exception, or cancelled)?
- cancel(mayInterruptIfRunning) – try to cancel the task.
- isCancelled().

## Example: generate a report without blocking the request thread
```java
ExecutorService pool = Executors.newFixedThreadPool(4);

Future<byte[]> report = pool.submit(() -> buildSalaryReport());   // runs in the background

doOtherWork();                                                      // main thread keeps going

try {
    byte[] pdf = report.get(10, TimeUnit.SECONDS);                  // wait up to 10 s
    save(pdf);
} catch (TimeoutException e) {
    report.cancel(true);                                            // interrupt the worker
    System.out.println("Report took too long");
} catch (ExecutionException e) {
    System.out.println("Report failed: " + e.getCause());
}
```

## Limitations of Future
- get() blocks; there is no callback when the result arrives.
- You cannot easily chain steps or combine several futures.
- You cannot complete it manually.

## CompletableFuture (Java 8+) solves these
```java
CompletableFuture<Job> job = CompletableFuture.supplyAsync(() -> jobApi.fetch(5));
CompletableFuture<Company> company = CompletableFuture.supplyAsync(() -> companyApi.fetch(9));

job.thenCombine(company, (j, c) -> j.title() + " at " + c.name())
   .thenAccept(System.out::println)
   .exceptionally(ex -> { System.out.println("Failed: " + ex.getMessage()); return null; });
```

Note: always use a timeout with get() in servers; an unbounded wait can hang request threads.
MD,

'What is ExecutorService?' => <<<'MD'
## What it is
ExecutorService is a high-level API (java.util.concurrent) that manages a pool of worker threads. You submit tasks; the pool reuses its threads to run them, so you do not create and destroy a thread per task.

## Why not new Thread() for every task?
- Creating threads is expensive and unbounded creation can exhaust memory.
- A pool limits concurrency (e.g. 10 parallel database calls) and queues extra tasks.
- It provides results (Future), shutdown control and scheduling.

## Creating pools
```java
ExecutorService fixed = Executors.newFixedThreadPool(4);          // 4 threads, unbounded queue
ExecutorService cached = Executors.newCachedThreadPool();         // grows as needed (careful)
ScheduledExecutorService scheduler = Executors.newScheduledThreadPool(2);
ExecutorService virtual = Executors.newVirtualThreadPerTaskExecutor();   // Java 21
```

## Example: send 100 emails with 5 workers
```java
ExecutorService pool = Executors.newFixedThreadPool(5);
for (int i = 1; i <= 100; i++) {
    int id = i;
    pool.submit(() -> System.out.println("Email " + id + " sent by " + Thread.currentThread().getName()));
}
pool.shutdown();                                   // stop accepting new tasks
pool.awaitTermination(1, TimeUnit.MINUTES);       // wait for queued tasks
```

```text
Output (excerpt)
Email 1 sent by pool-1-thread-1
Email 2 sent by pool-1-thread-2
Email 6 sent by pool-1-thread-1   ← threads are reused
```

## Production configuration: ThreadPoolExecutor
```java
ExecutorService pool = new ThreadPoolExecutor(
        5, 10,                                   // core and max threads
        60, TimeUnit.SECONDS,                    // idle time before extra threads die
        new ArrayBlockingQueue<>(500),           // bounded queue
        new ThreadPoolExecutor.CallerRunsPolicy()// back-pressure when full
);
```

## Scheduling
```java
scheduler.scheduleAtFixedRate(() -> refreshExchangeRates(), 0, 15, TimeUnit.MINUTES);
```

## Shutdown
- shutdown(): finish queued tasks, accept no new ones.
- shutdownNow(): interrupt running tasks and return the queued ones.
Always shut pools down, otherwise their non-daemon threads keep the JVM alive.

Note: in Spring, configure a ThreadPoolTaskExecutor bean and use @Async instead of creating pools manually.
MD,

'What is synchronization?' => <<<'MD'
## What it is
Synchronization controls access to shared data so that only one thread at a time executes a critical section. It prevents race conditions and also guarantees visibility: changes made inside a synchronized block are visible to the next thread that acquires the same lock.

Every Java object has an intrinsic lock (monitor). A thread must acquire it to enter a synchronized method or block on that object.

## Without synchronization: lost updates
```java
class Ticketing {
    private int sold = 0;
    void sell() { sold++; }          // read, add, write – not atomic
    int sold() { return sold; }
}
```
Two threads each selling 10,000 tickets often print fewer than 20,000.

## Synchronized method
```java
class Ticketing {
    private int sold = 0;
    synchronized void sell() { sold++; }      // lock on "this"
    synchronized int sold() { return sold; }
}
```

## Synchronized block (lock only what is needed)
```java
class BankAccount {
    private final Object lock = new Object();
    private long balance;

    void withdraw(long amount) {
        validate(amount);                     // no lock needed
        synchronized (lock) {                 // critical section
            if (balance < amount) throw new IllegalStateException("Insufficient balance");
            balance -= amount;
        }
    }
}
```

## Static synchronized
static synchronized methods lock the Class object, protecting static data.

## Demonstration
```java
Ticketing t = new Ticketing();
Thread a = new Thread(() -> { for (int i = 0; i < 10_000; i++) t.sell(); });
Thread b = new Thread(() -> { for (int i = 0; i < 10_000; i++) t.sell(); });
a.start(); b.start(); a.join(); b.join();
System.out.println(t.sold());   // always 20000 with synchronized
```

## Alternatives
- AtomicInteger/AtomicLong for single counters (lock-free).
- ReentrantLock: tryLock with timeout, fair locks, multiple conditions.
- Concurrent collections (ConcurrentHashMap, BlockingQueue).
- Immutable objects need no locking at all.

## Costs
Locks serialise threads (less parallelism) and can cause deadlock if acquired in inconsistent order. Keep critical sections small.
MD,

'What is a race condition?' => <<<'MD'
## What it is
A race condition occurs when the result of a program depends on the unpredictable timing of threads accessing shared data, and at least one thread writes. The classic case is a read-modify-write that is not atomic.

## Example: two withdrawals from one account
Balance = ₹1,000. Two ATM threads withdraw ₹800 at the same moment:

```text
Thread A: read balance = 1000
Thread B: read balance = 1000
Thread A: 1000 >= 800 → write 200
Thread B: 1000 >= 800 → write 200
Result: ₹1,600 paid out, balance 200   ← money lost
```

```java
class Account {
    private int balance = 1000;
    void withdraw(int amount) {
        if (balance >= amount) {          // check
            sleepBriefly();               // widens the window
            balance -= amount;            // act
        }
    }
}
```

## Another example: count++
count++ is three steps (read, increment, write). Two threads incrementing 1,000 times each can end with less than 2,000.

## Fixes
1. synchronized or a Lock around the check-and-act:
```java
synchronized void withdraw(int amount) {
    if (balance >= amount) balance -= amount;
}
```
2. Atomic classes:
```java
AtomicInteger count = new AtomicInteger();
count.incrementAndGet();
balance.updateAndGet(b -> b >= amount ? b - amount : b);   // atomic compare-and-set loop
```
3. Thread-confinement or immutability – do not share mutable state.
4. In databases: transactions with row locks (SELECT ... FOR UPDATE) or optimistic locking (@Version).

## Detecting race conditions
They are intermittent and may disappear under a debugger. Use stress tests, tools such as jcstress, and code review of shared mutable state.

Note: volatile does not fix check-then-act races; it only guarantees visibility, not atomicity.
MD,

'What is deadlock?' => <<<'MD'
## What it is
A deadlock is a situation where two or more threads wait forever, each holding a lock the other needs. None can proceed.

## Example: two transfers in opposite directions
```java
class Account { final Object lock = new Object(); long balance; }

void transfer(Account from, Account to, long amount) {
    synchronized (from.lock) {
        synchronized (to.lock) {
            from.balance -= amount;
            to.balance += amount;
        }
    }
}
// Thread 1: transfer(A, B) locks A, waits for B
// Thread 2: transfer(B, A) locks B, waits for A  → deadlock
```

```text
Thread 1 holds A ──waits for──► B
Thread 2 holds B ──waits for──► A
```

## Four conditions (all required)
1. Mutual exclusion – a resource is held by one thread.
2. Hold and wait – a thread holds one lock while waiting for another.
3. No preemption – locks cannot be taken away.
4. Circular wait – a cycle of threads waiting on each other.
Break any one to prevent deadlock.

## Prevention
1. Consistent lock ordering (break circular wait):
```java
void transfer(Account from, Account to, long amount) {
    Account first = from.id < to.id ? from : to;
    Account second = first == from ? to : from;
    synchronized (first.lock) {
        synchronized (second.lock) { /* move money */ }
    }
}
```
2. Timeouts with ReentrantLock.tryLock – give up and retry instead of waiting forever.
3. Hold fewer locks and for less time; avoid calling external code while holding a lock.

## Detecting deadlocks
- jstack <pid> or a thread dump prints "Found one Java-level deadlock" with the threads and locks involved.
- ThreadMXBean.findDeadlockedThreads() programmatically.

Note: databases detect deadlocks themselves (MySQL error 1213) and roll back one transaction; your code should retry it.
MD,

'What is volatile?' => <<<'MD'
## What it is
volatile is a field modifier that guarantees visibility and ordering: every read sees the most recent write by any thread, and writes are not reordered with surrounding operations in a way that breaks this. Without it, a thread may keep using a cached (stale) value.

## Classic use: a stop flag
```java
class Worker implements Runnable {
    private volatile boolean running = true;     // without volatile the loop may never stop

    public void run() {
        while (running) {
            processNextMessage();
        }
        System.out.println("Worker stopped");
    }

    public void stop() { running = false; }      // called from another thread
}
```

## What volatile does NOT do
It does not make compound operations atomic:

```java
private volatile int count = 0;
count++;          // still a race: read, add, write
```
Use AtomicInteger or synchronized for that.

## volatile vs synchronized vs atomic
- volatile: visibility only, no locking, very cheap. One writer / many readers of a simple flag or reference.
- synchronized: mutual exclusion + visibility for multi-step updates.
- AtomicInteger etc.: lock-free atomic updates of a single value + visibility.

## Double-checked locking needs volatile
```java
class ConfigHolder {
    private static volatile Config instance;
    static Config get() {
        if (instance == null) {
            synchronized (ConfigHolder.class) {
                if (instance == null) instance = new Config();   // volatile prevents seeing a half-built object
            }
        }
        return instance;
    }
}
```

Note: the Java Memory Model's "happens-before" rule: a write to a volatile field happens-before every later read of that field.
MD,

'What is the difference between wait() and sleep()?' => <<<'MD'
## The difference
- Thread.sleep(ms) pauses the current thread for a time. It does not release any lock it holds. Static method of Thread.
- obj.wait() pauses the current thread until another thread calls notify()/notifyAll() on the same object (or a timeout). It must be called while holding obj's lock and it releases that lock while waiting. Method of Object.

## Comparison
- Class: sleep – Thread; wait – Object.
- Lock: sleep keeps locks; wait releases the monitor of obj.
- Wake-up: sleep after the time; wait on notify/notifyAll/timeout/interrupt.
- Calling context: sleep anywhere; wait only inside synchronized(obj), otherwise IllegalMonitorStateException.
- Purpose: sleep for delays; wait for coordination between threads.

## Producer–consumer with wait/notify
```java
class OrderQueue {
    private final Queue<String> orders = new LinkedList<>();
    private final int capacity = 5;

    public synchronized void put(String order) throws InterruptedException {
        while (orders.size() == capacity) wait();     // releases the lock while full
        orders.add(order);
        notifyAll();                                  // wake consumers
    }

    public synchronized String take() throws InterruptedException {
        while (orders.isEmpty()) wait();              // releases the lock while empty
        String order = orders.poll();
        notifyAll();                                  // wake producers
        return order;
    }
}
```
Always call wait() in a while loop that rechecks the condition, because of spurious wake-ups.

## sleep example
```java
for (int attempt = 1; attempt <= 3; attempt++) {
    if (callPaymentGateway()) break;
    Thread.sleep(1000L * attempt);    // back off before retrying
}
```

Note: modern code uses BlockingQueue, CountDownLatch or CompletableFuture instead of raw wait/notify.
MD,

'What is ConcurrentHashMap?' => <<<'MD'
## What it is
ConcurrentHashMap is a thread-safe, high-performance implementation of Map designed for many threads reading and writing at once.

## Why not HashMap or Hashtable?
- HashMap is not thread-safe: concurrent writes can lose entries or corrupt the structure.
- Hashtable and Collections.synchronizedMap lock the whole map for every operation, so threads queue up.
- ConcurrentHashMap allows concurrent reads without locking and locks only a single bucket (bin) during writes (Java 8+ uses CAS operations and per-bin synchronization).

## Example: counting job views from many request threads
```java
ConcurrentHashMap<Long, LongAdder> views = new ConcurrentHashMap<>();

void recordView(long jobId) {
    views.computeIfAbsent(jobId, id -> new LongAdder()).increment();
}

long viewsFor(long jobId) {
    LongAdder counter = views.get(jobId);
    return counter == null ? 0 : counter.sum();
}
```

## Atomic compound operations
```java
map.putIfAbsent("java", 0);
map.merge("java", 1, Integer::sum);                    // atomic increment
map.compute("java", (k, v) -> v == null ? 1 : v + 1);
map.computeIfAbsent(key, k -> loadFromDatabase(k));   // loads once per key
```
Avoid check-then-act sequences like if (!map.containsKey(k)) map.put(k, v) – use the atomic methods above.

## Characteristics
- No null keys or values (null would be ambiguous with "absent").
- Iterators are weakly consistent: they never throw ConcurrentModificationException and may or may not see concurrent updates.
- size() is an estimate while other threads are modifying the map.

## Complexity
- get/put: O(1) average, like HashMap.

Note: a typical use is an in-memory cache or rate-limit counter shared by all request threads in a Spring Boot application.
MD,

'What is the difference between Runnable and Iterable?' => <<<'MD'
## They solve unrelated problems
- Runnable (java.lang) represents a task to execute, usually in another thread. One method: void run().
- Iterable<T> (java.lang) represents something you can loop over. One method: Iterator<T> iterator(). It enables the for-each loop.

## Runnable: a unit of work
```java
Runnable sendReminder = () -> System.out.println("Reminder sent at " + LocalTime.now());
new Thread(sendReminder).start();
executor.submit(sendReminder);
```

## Iterable: a sequence of elements
```java
class JobFeed implements Iterable<String> {
    private final List<String> titles = List.of("Java Developer", "QA Engineer", "Data Analyst");

    @Override
    public Iterator<String> iterator() { return titles.iterator(); }
}

for (String title : new JobFeed()) {      // works because JobFeed is Iterable
    System.out.println(title);
}
```

```text
Output
Java Developer
QA Engineer
Data Analyst
```

## Side by side
- Purpose: Runnable – execution of a task; Iterable – traversal of elements.
- Method: run() returns void; iterator() returns an Iterator.
- Typical users: Thread, ExecutorService, schedulers vs for-each loops, streams (Iterable.spliterator), collections.
- Both are functional-style interfaces (Runnable is annotated @FunctionalInterface; Iterable has one abstract method and default methods such as forEach).

## Where they meet
You can iterate a collection of tasks and run each one:
```java
List<Runnable> tasks = List.of(this::sendEmail, this::sendSms);
tasks.forEach(executor::submit);         // Iterable.forEach submitting Runnables
```

Note: this question checks that you understand interfaces by their contract: Runnable is about concurrency, Iterable is about collections.
MD,

];
