<?php

// Java › Exceptions and OOP, in depth.
return [

'What is exception handling?' => <<<'MD'
## What it is
An exception is an event that disrupts the normal flow of a program at run time: a missing file, a network failure, dividing by zero, a null reference. Exception handling is the mechanism to detect such events, respond to them and keep the program running (or fail cleanly) instead of crashing.

## Without handling
```java
int[] marks = {80, 90};
System.out.println(marks[5]);       // ArrayIndexOutOfBoundsException → program stops
System.out.println("Never printed");
```

## With handling
```java
try {
    int amount = Integer.parseInt(input);          // may throw NumberFormatException
    double perHead = amount / members;             // may throw ArithmeticException
    System.out.println(perHead);
} catch (NumberFormatException e) {
    System.out.println("Please enter digits only");
} catch (ArithmeticException e) {
    System.out.println("Group must have at least one member");
} finally {
    System.out.println("Split calculation finished");   // always runs
}
```

## The keywords
| Keyword | Purpose |
|---|---|
| try | Wraps code that might throw |
| catch | Handles a specific exception type |
| finally | Runs whether or not an exception occurred (cleanup) |
| throw | Throws an exception object |
| throws | Declares exceptions a method may pass to its caller |

## How it works
1. An exception object is created with the type, message and stack trace.
2. The JVM looks for a matching catch in the current method, then in its caller, up the call stack.
3. If none is found, the thread terminates and the stack trace is printed.

## Multi-catch (Java 7+)
```java
catch (IOException | SQLException e) { log.error("Data access failed", e); }
```

## Best practices
- Catch specific exceptions, not `Exception` or `Throwable` blindly.
- Never swallow exceptions with an empty catch block.
- Log with the exception object to keep the stack trace.
- Use try-with-resources for anything that must be closed.
- In Spring REST APIs, map exceptions to HTTP responses centrally with `@RestControllerAdvice`.
MD,

'What is the difference between Error and Exception?' => <<<'MD'
## Short answer
Both extend `Throwable`. An Exception represents a condition an application can reasonably catch and recover from. An Error represents a serious problem in the JVM or environment that applications normally should not try to handle.

| | Exception | Error |
|---|---|---|
| Package / parent | `java.lang.Exception` | `java.lang.Error` |
| Cause | Application logic or external resources | JVM or system resources |
| Recoverable | Usually yes | Usually no |
| Should you catch it | Yes, where you can handle it | Generally no |
| Checked? | Checked (except RuntimeException subclasses) | Always unchecked |
| Examples | IOException, SQLException, NullPointerException, IllegalArgumentException | OutOfMemoryError, StackOverflowError, NoClassDefFoundError, ExceptionInInitializerError |

## Examples
```java
// Exception: recoverable
try {
    Files.readString(Path.of("config.json"));
} catch (IOException e) {
    useDefaultConfig();
}

// Error: not recoverable in normal code
static int depth(int n) { return depth(n + 1); }   // StackOverflowError: fix the code, don't catch it
List<byte[]> leak = new ArrayList<>();
while (true) leak.add(new byte[10_000_000]);       // OutOfMemoryError: fix the leak or increase -Xmx
```

## Practical notes
- Catching `Throwable` or `Error` hides fatal conditions; the JVM may be in an inconsistent state.
- Exceptions to that rule: top-level frameworks may catch Throwable to log it before the thread dies.
- `AssertionError` (from `assert`) is also an Error.
MD,

'What is Throwable?' => <<<'MD'
## What it is
`java.lang.Throwable` is the root class of everything that can be thrown with `throw` and caught with `catch`. Only objects of Throwable or its subclasses can be thrown.

## Hierarchy
```text
Object
└── Throwable
    ├── Error                        (serious, unchecked)
    │   ├── OutOfMemoryError
    │   ├── StackOverflowError
    │   └── NoClassDefFoundError
    └── Exception                    (checked unless RuntimeException)
        ├── IOException
        │   └── FileNotFoundException
        ├── SQLException
        ├── InterruptedException
        └── RuntimeException         (unchecked)
            ├── NullPointerException
            ├── ArithmeticException
            ├── IllegalArgumentException
            │   └── NumberFormatException
            ├── IllegalStateException
            ├── IndexOutOfBoundsException
            └── ClassCastException
```

## Useful methods
| Method | Returns |
|---|---|
| `getMessage()` | The detail message |
| `getCause()` | The wrapped original exception, if any |
| `printStackTrace()` | Prints type, message and call stack to stderr |
| `getStackTrace()` | Array of stack frames |
| `addSuppressed()` / `getSuppressed()` | Exceptions suppressed during try-with-resources closing |
| `toString()` | Class name + message |

```java
try {
    repository.save(order);
} catch (DataAccessException e) {
    throw new OrderFailedException("Could not save order " + order.getId(), e);   // keep the cause
}
```
Exception chaining with a cause preserves the original stack trace ("Caused by: …" in logs), which is essential for debugging.
MD,

'What are checked and unchecked exceptions?' => <<<'MD'
## Checked exceptions
Subclasses of `Exception` that are not `RuntimeException`. The compiler checks them: a method must either catch them or declare them with `throws`. They represent foreseeable problems outside the program's control.

```java
public String readResume(Path path) throws IOException {   // must declare or catch
    return Files.readString(path);
}
```
Examples: IOException, FileNotFoundException, SQLException, InterruptedException, ClassNotFoundException.

## Unchecked exceptions
`RuntimeException` and its subclasses (plus `Error`). The compiler does not force you to handle them. They usually indicate programming bugs or invalid arguments.

```java
public void setAge(int age) {
    if (age < 18) throw new IllegalArgumentException("Candidate must be 18+");   // no throws needed
}
```
Examples: NullPointerException, ArrayIndexOutOfBoundsException, ArithmeticException, IllegalArgumentException, IllegalStateException, NumberFormatException, ClassCastException.

## Comparison
| | Checked | Unchecked |
|---|---|---|
| Compile-time enforcement | Yes | No |
| Parent | Exception (not RuntimeException) | RuntimeException / Error |
| Typical cause | External: files, network, DB | Programming error, bad input |
| Handling | Catch or declare | Prevent with checks; catch where meaningful |

## Which should a custom exception be?
- Checked: if the caller can reasonably recover (e.g. `InsufficientBalanceException` the UI can show).
- Unchecked: for programming errors or when recovery is not possible at that level. Modern frameworks (Spring, Hibernate) mostly use unchecked exceptions, e.g. Spring wraps SQLException into unchecked `DataAccessException`, because lambdas and streams do not work well with checked exceptions.
MD,

'What is the difference between throw and throws?' => <<<'MD'
## Short answer
- `throw` actually throws one exception object, inside a method body.
- `throws` is part of a method signature and declares the exception types the method may pass on to its caller.

```java
public class WalletService {

    public void withdraw(double amount) throws InsufficientBalanceException {   // throws: declaration
        if (amount <= 0) {
            throw new IllegalArgumentException("Amount must be positive");        // throw: action
        }
        if (amount > balance) {
            throw new InsufficientBalanceException("Balance is only ₹" + balance);
        }
        balance -= amount;
    }
}

// Caller must handle the checked exception
try {
    wallet.withdraw(5000);
} catch (InsufficientBalanceException e) {
    showMessage(e.getMessage());
}
```

| throw | throws |
|---|---|
| Statement inside a method or block | Clause in a method/constructor signature |
| Followed by an exception instance: `throw new X()` | Followed by class names: `throws X, Y` |
| Throws exactly one exception at a time | Can list several types |
| Transfers control immediately | Only informs the compiler and callers |
| Works with checked and unchecked | Required for checked exceptions not caught inside |

## Rethrowing
```java
catch (SQLException e) {
    log.error("Query failed", e);
    throw new ReportException("Report generation failed", e);   // wrap and rethrow
}
```

## Overriding rule
An overriding method may declare the same checked exceptions, narrower ones or none, but not new or broader checked exceptions.
MD,

'Can try exist without catch?' => <<<'MD'
## Answer
Yes, a `try` block needs either at least one `catch` or a `finally` (or both). And try-with-resources can stand alone.

## try–finally
Used when you do not handle the exception here, but must run cleanup code; the exception continues to the caller after finally runs.
```java
lock.lock();
try {
    updateInventory();
} finally {
    lock.unlock();            // always released, even if updateInventory throws
}
```

## try-with-resources alone
```java
try (BufferedReader reader = Files.newBufferedReader(path)) {
    return reader.readLine();
}                              // reader closed automatically; exceptions propagate (method declares throws IOException)
```

## Not allowed
```java
try {
    doWork();
}                              // compile error: 'try' without 'catch', 'finally' or resource declarations
```

## When does finally not run?
- `System.exit()` is called inside try or catch.
- The JVM crashes or the process is killed.
- An infinite loop or deadlock in try.
Otherwise it runs even when try executes `return`.

## Trick: return in finally
```java
static int test() {
    try { return 1; }
    finally { return 2; }      // returns 2 and swallows any exception from try — avoid this
}
```
MD,

'What is try-with-resources?' => <<<'MD'
## What it is
Try-with-resources (Java 7) declares one or more resources in parentheses after `try`. Each resource is closed automatically at the end of the block, in reverse order of creation, whether the block completes normally or throws. A resource is any object implementing `AutoCloseable`.

## Before Java 7
```java
BufferedReader reader = null;
try {
    reader = new BufferedReader(new FileReader("jobs.csv"));
    System.out.println(reader.readLine());
} catch (IOException e) {
    e.printStackTrace();
} finally {
    if (reader != null) {
        try { reader.close(); } catch (IOException ignored) { }   // noisy and easy to get wrong
    }
}
```

## With try-with-resources
```java
try (BufferedReader reader = Files.newBufferedReader(Path.of("jobs.csv"))) {
    System.out.println(reader.readLine());
} catch (IOException e) {
    log.error("Could not read jobs.csv", e);
}
```

## Several resources (JDBC)
```java
String sql = "SELECT title FROM jobs WHERE city = ?";
try (Connection con = dataSource.getConnection();
     PreparedStatement ps = con.prepareStatement(sql)) {
    ps.setString(1, "Pune");
    try (ResultSet rs = ps.executeQuery()) {
        while (rs.next()) System.out.println(rs.getString("title"));
    }
}   // closes rs, then ps, then con
```

## Suppressed exceptions
If the block throws and close() also throws, the exception from the block is kept as the main one and the close exception is attached as suppressed (`e.getSuppressed()`), so no information is lost. In the old finally style, the close exception would hide the real one.

## Java 9 improvement
An effectively final variable declared earlier can be used directly:
```java
BufferedReader reader = Files.newBufferedReader(path);
try (reader) { … }
```
MD,

'What is AutoCloseable?' => <<<'MD'
## What it is
`java.lang.AutoCloseable` is an interface with a single method, `void close() throws Exception`. Any class implementing it can be used in a try-with-resources statement, which calls `close()` automatically.

## Closeable vs AutoCloseable
| | AutoCloseable | Closeable |
|---|---|---|
| Package | java.lang (Java 7) | java.io (Java 5) |
| close() throws | Exception | IOException |
| Idempotent close required | Recommended | Yes (calling close twice has no effect) |
| Relationship | Parent | `Closeable extends AutoCloseable` |

Implemented by streams, readers/writers, JDBC Connection/Statement/ResultSet, Scanner, sockets, ExecutorService (Java 19+), and many more.

## Your own resource
```java
public class AuditSession implements AutoCloseable {
    private final String user;

    public AuditSession(String user) {
        this.user = user;
        System.out.println("Audit started for " + user);
    }

    public void record(String action) { System.out.println(user + " did " + action); }

    @Override
    public void close() {                       // narrower: throws nothing
        System.out.println("Audit closed for " + user);
    }
}

try (AuditSession audit = new AuditSession("admin")) {
    audit.record("deleted job 42");
}
```
```text
Audit started for admin
admin did deleted job 42
Audit closed for admin
```

Tip: declare `close()` with the narrowest exception possible (or none), so callers do not need to catch a generic Exception.
MD,

'How do you create a custom exception?' => <<<'MD'
## Steps
1. Extend `Exception` (checked) or `RuntimeException` (unchecked).
2. Give it a meaningful name ending in Exception.
3. Provide constructors for a message and for a message plus cause.
4. Optionally add fields with extra context.

```java
public class InsufficientBalanceException extends RuntimeException {
    private final double balance;
    private final double requested;

    public InsufficientBalanceException(double balance, double requested) {
        super(String.format("Requested ₹%.2f but balance is ₹%.2f", requested, balance));
        this.balance = balance;
        this.requested = requested;
    }

    public InsufficientBalanceException(String message, Throwable cause) {
        super(message, cause);
        this.balance = 0;
        this.requested = 0;
    }

    public double getShortfall() { return requested - balance; }
}
```

## Throwing and handling
```java
public void withdraw(double amount) {
    if (amount > balance) throw new InsufficientBalanceException(balance, amount);
    balance -= amount;
}

try {
    account.withdraw(10000);
} catch (InsufficientBalanceException e) {
    System.out.println(e.getMessage() + ". Add ₹" + e.getShortfall());
}
```

## In a Spring Boot API
```java
@ResponseStatus(HttpStatus.NOT_FOUND)
public class JobNotFoundException extends RuntimeException {
    public JobNotFoundException(long id) { super("Job " + id + " not found"); }
}

@RestControllerAdvice
public class ApiErrors {
    @ExceptionHandler(InsufficientBalanceException.class)
    ResponseEntity<Map<String, String>> balance(InsufficientBalanceException e) {
        return ResponseEntity.unprocessableEntity().body(Map.of("error", e.getMessage()));
    }
}
```

## Good practice
- Create custom exceptions for business rules, not for every small case.
- Always keep the cause when wrapping.
- Group related ones under a base class (`PaymentException` → `CardDeclinedException`, `UpiTimeoutException`).
MD,

'What is exception propagation?' => <<<'MD'
## What it is
When an exception is thrown and not caught in the current method, the method stops and the exception is passed (propagated) to its caller, then to that method's caller, and so on down the call stack, until some method catches it. If none does, the thread ends and the JVM prints the stack trace.

```java
public class Checkout {
    void validateCard(String card) {
        if (card.length() != 16) throw new IllegalArgumentException("Invalid card number");   // thrown here
    }
    void pay(String card)        { validateCard(card); }        // not caught → propagates
    void placeOrder(String card) { pay(card); }                 // not caught → propagates

    public static void main(String[] args) {
        try {
            new Checkout().placeOrder("1234");
        } catch (IllegalArgumentException e) {                   // caught here
            System.out.println("Order failed: " + e.getMessage());
        }
    }
}
```

```text
Call stack when thrown          Propagation
main                            ▲ caught in main
 └ placeOrder                   │
    └ pay                       │
       └ validateCard  ── throw ┘
```

## Checked vs unchecked
- Unchecked exceptions propagate automatically.
- Checked exceptions propagate only if each method on the way declares them with `throws`; otherwise compilation fails.

## Reading a stack trace
```text
java.lang.IllegalArgumentException: Invalid card number
    at Checkout.validateCard(Checkout.java:3)    ← where it was thrown
    at Checkout.pay(Checkout.java:5)
    at Checkout.placeOrder(Checkout.java:6)
    at Checkout.main(Checkout.java:10)           ← where the chain started
```

## Design guideline
Catch an exception at the level that can actually do something about it (retry, show a message, return an HTTP error). Lower layers should let it propagate or wrap it with context.
MD,

'What is abstraction?' => <<<'MD'
## What it is
Abstraction means showing only the essential features of something and hiding the implementation details. The caller knows what an object does, not how it does it.

Analogy: when you drive a car you use the steering, accelerator and brake. You do not need to know about fuel injection or the ABS algorithm. When you pay with UPI you tap "Pay"; the bank-to-bank NPCI messaging is hidden.

## Achieved in Java with
1. Abstract classes (0–100% abstraction)
2. Interfaces (full abstraction of behaviour)

```java
public interface NotificationSender {          // WHAT: send a notification
    void send(String to, String message);
}

public class EmailSender implements NotificationSender {   // HOW: SMTP
    public void send(String to, String message) { /* SMTP connection, MIME, retries… */ }
}

public class SmsSender implements NotificationSender {     // HOW: SMS gateway API
    public void send(String to, String message) { /* HTTP call to gateway */ }
}

public class InterviewScheduler {
    private final NotificationSender sender;                 // depends on the abstraction only
    public InterviewScheduler(NotificationSender sender) { this.sender = sender; }

    public void schedule(String candidate) {
        sender.send(candidate, "Your interview is on Monday 10 AM");
    }
}
```
`InterviewScheduler` works with email, SMS or WhatsApp senders without changing a line.

## Benefits
- Reduces complexity for callers.
- Implementations can change or be swapped (loose coupling).
- Enables testing with fake/mock implementations.

## Abstraction vs encapsulation
| Abstraction | Encapsulation |
|---|---|
| Hides complexity (design level) | Hides data (implementation level) |
| Focuses on what an object does | Focuses on how state is protected |
| Interfaces, abstract classes | private fields + public methods |
MD,

'What is encapsulation?' => <<<'MD'
## What it is
Encapsulation is bundling data (fields) and the methods that operate on it into one class, and restricting direct access to the data. Fields are private; controlled access is given through public methods that enforce the rules.

Analogy: an ATM. You cannot open the cash box directly; you interact through a PIN and menu, and the machine enforces limits.

```java
public class Candidate {
    private String email;
    private int experienceYears;
    private final List<String> skills = new ArrayList<>();

    public String getEmail() { return email; }

    public void setEmail(String email) {
        if (email == null || !email.matches("^[\\w.+-]+@[\\w-]+\\.[\\w.]+$"))
            throw new IllegalArgumentException("Invalid email");
        this.email = email.toLowerCase();
    }

    public void setExperienceYears(int years) {
        if (years < 0 || years > 50) throw new IllegalArgumentException("Experience must be 0–50");
        this.experienceYears = years;
    }

    public void addSkill(String skill) { skills.add(skill.trim()); }

    public List<String> getSkills() { return List.copyOf(skills); }   // read-only copy, internals stay protected
}
```

## Benefits
1. Data integrity: invalid values are rejected in one place.
2. Flexibility: internal representation can change without affecting callers.
3. Control: read-only (getter only) or write-only properties.
4. Easier maintenance and debugging.
5. Security: sensitive data (passwords) never exposed directly.

## Levels of encapsulation
- Field level: private fields.
- Class level: package-private helper classes.
- Module level: only exported packages are visible (Java 9 modules).

Note: getters and setters for every field is not automatically good encapsulation. Prefer meaningful behaviour (`account.withdraw(500)`) over exposing raw setters (`account.setBalance(...)`).
MD,

'What is inheritance?' => <<<'MD'
## What it is
Inheritance lets a class (subclass/child) acquire the fields and methods of another class (superclass/parent) using `extends`. The child reuses the parent's code and can add new members or override behaviour. It models an IS-A relationship: a Car IS-A Vehicle.

```java
public class Employee {
    protected final String name;
    protected final double baseSalary;

    public Employee(String name, double baseSalary) { this.name = name; this.baseSalary = baseSalary; }

    public double monthlyPay() { return baseSalary; }
    public String badge() { return "EMP-" + name; }
}

public class Manager extends Employee {
    private final double teamBonus;

    public Manager(String name, double baseSalary, double teamBonus) {
        super(name, baseSalary);
        this.teamBonus = teamBonus;
    }

    @Override
    public double monthlyPay() { return super.monthlyPay() + teamBonus; }   // extends behaviour

    public void approveLeave(String employee) { System.out.println(name + " approved leave for " + employee); }
}

Manager m = new Manager("Priya", 150000, 25000);
System.out.println(m.monthlyPay());   // 175000.0
System.out.println(m.badge());        // EMP-Priya (inherited)
```

## Types
| Type | Example | Supported with classes? |
|---|---|---|
| Single | B extends A | Yes |
| Multilevel | C extends B extends A | Yes |
| Hierarchical | B and C both extend A | Yes |
| Multiple | C extends A, B | No (diamond problem); possible with interfaces |
| Hybrid | Combination | Only through interfaces |

## What is not inherited
- Constructors (called with `super()` instead).
- Private members are not accessible (they exist in the object but the child cannot use them directly).
- Static members are accessible but belong to the parent class (hidden, not overridden).

## Why no multiple inheritance of classes?
If two parents had the same method, which one would the child get? (diamond problem). Java avoids this for classes; for interfaces with conflicting default methods, the class must override and choose.

## Cautions
Inheritance couples the child tightly to the parent. Use it only for a true IS-A relationship; otherwise prefer composition.
MD,

'What is polymorphism?' => <<<'MD'
## What it is
Polymorphism ("many forms") means the same method call can behave differently depending on the object or the arguments. One interface, many implementations.

Analogy: a "pay" button in an app. The same action works with UPI, credit card, net banking or wallet, each doing it differently.

## 1. Compile-time polymorphism (static): method overloading
The compiler chooses the method from the argument types.
```java
class Printer {
    void print(int n)      { System.out.println("int " + n); }
    void print(String s)   { System.out.println("text " + s); }
    void print(double d)   { System.out.println("decimal " + d); }
}
```

## 2. Run-time polymorphism (dynamic): method overriding
The JVM chooses the method from the actual object type at run time (dynamic method dispatch).
```java
abstract class Payment {
    abstract double fee(double amount);
}
class UpiPayment extends Payment  { double fee(double a) { return 0; } }
class CardPayment extends Payment { double fee(double a) { return a * 0.018; } }
class EmiPayment extends Payment  { double fee(double a) { return a * 0.03 + 199; } }

List<Payment> methods = List.of(new UpiPayment(), new CardPayment(), new EmiPayment());
for (Payment p : methods) {
    System.out.println(p.getClass().getSimpleName() + " fee: " + p.fee(10000));
}
```
```text
UpiPayment fee: 0.0
CardPayment fee: 180.0
EmiPayment fee: 499.0
```
The loop is written once against `Payment`; adding a new payment type needs no change to it (open/closed principle).

## Upcasting and downcasting
```java
Payment p = new CardPayment();          // upcasting: automatic
if (p instanceof CardPayment card) {    // pattern matching (Java 16)
    // use card-specific methods
}
```

| | Compile-time | Run-time |
|---|---|---|
| Mechanism | Overloading | Overriding |
| Decided by | Compiler, using reference/argument types | JVM, using the actual object |
| Also called | Static binding | Dynamic binding / late binding |

Note: fields, static methods and private methods are not polymorphic; only overridable instance methods are.
MD,

'What is method overloading?' => <<<'MD'
## What it is
Method overloading is defining several methods with the same name in the same class (or inherited), but with different parameter lists. The compiler picks the right one based on the arguments, so it is compile-time polymorphism.

```java
public class SearchService {
    public List<Job> search(String keyword) { return search(keyword, "All India"); }
    public List<Job> search(String keyword, String city) { return search(keyword, city, 0); }
    public List<Job> search(String keyword, String city, int minExperience) { /* query */ return List.of(); }
    public List<Job> search(List<String> skills) { /* match by skills */ return List.of(); }
}
```

## Valid ways to overload
- Different number of parameters: `add(int a, int b)` and `add(int a, int b, int c)`
- Different types: `add(int, int)` and `add(double, double)`
- Different order of types: `log(String, int)` and `log(int, String)`

## Not overloading
- Only the return type differs: `int total()` vs `double total()` is a compile error.
- Only parameter names differ.
- Only access modifiers or thrown exceptions differ.

## How the compiler resolves a call (in order)
1. Exact match.
2. Widening primitive conversion (int → long → float → double).
3. Autoboxing/unboxing (int → Integer).
4. Varargs.

```java
void show(long x)    { System.out.println("long"); }
void show(Integer x) { System.out.println("Integer"); }
void show(int... x)  { System.out.println("varargs"); }

show(5);   // prints "long": widening beats boxing, boxing beats varargs
```

## Ambiguity
```java
void f(Integer a, int b) { }
void f(int a, Integer b) { }
f(1, 2);   // compile error: ambiguous
```
JDK examples: `println` (int, double, String, Object…), `Math.max`, `String.valueOf`, `Arrays.sort`.
MD,

'What is method overriding?' => <<<'MD'
## What it is
Method overriding is when a subclass provides its own implementation of an instance method already defined in its parent, with the same name, parameters and a compatible return type. The version that runs is chosen at run time from the actual object type (runtime polymorphism).

```java
class Notification {
    void send(String msg) { System.out.println("Generic: " + msg); }
}

class EmailNotification extends Notification {
    @Override
    void send(String msg) { System.out.println("Email: " + msg); }
}

class WhatsAppNotification extends Notification {
    @Override
    void send(String msg) { System.out.println("WhatsApp: " + msg); }
}

Notification n = new WhatsAppNotification();
n.send("Offer letter released");   // WhatsApp: Offer letter released
```

## Rules
1. Same method name and parameter list.
2. Return type: same, or a subtype (covariant return).
3. Access cannot be more restrictive (protected → public is OK, public → protected is not).
4. Cannot throw new or broader checked exceptions (fewer, narrower or unchecked are fine).
5. `final`, `static` and `private` methods cannot be overridden.
6. Constructors cannot be overridden.
7. Use `@Override`: the compiler then catches typos such as `sendd` or wrong parameters.

## Calling the parent version
```java
@Override
void send(String msg) {
    super.send(msg);            // keep parent behaviour
    auditLog.record(msg);       // and add more
}
```

## Overloading vs overriding
| | Overloading | Overriding |
|---|---|---|
| Where | Same class (or inherited) | Parent and child |
| Parameters | Must differ | Must be the same |
| Return type | Can differ | Same or covariant |
| Binding | Compile time | Run time |
| static/private/final methods | Can be overloaded | Cannot be overridden |

Commonly overridden: `toString()`, `equals()`, `hashCode()`, `compareTo()`, `run()`.
MD,

'What is an interface?' => <<<'MD'
## What it is
An interface is a reference type that defines a contract: a set of methods a class promises to provide. A class `implements` the interface and must give bodies for its abstract methods. Interfaces enable abstraction, loose coupling and multiple inheritance of type.

```java
public interface Shippable {
    double MAX_WEIGHT_KG = 30;                       // implicitly public static final

    double weightKg();                               // abstract (implicitly public abstract)

    default boolean needsHeavyCourier() {            // default method (Java 8)
        return weightKg() > MAX_WEIGHT_KG;
    }

    static Shippable of(double kg) { return () -> kg; }   // static method (Java 8)

    private void log(String s) { System.out.println(s); } // private method (Java 9)
}

public class Refrigerator implements Shippable, Comparable<Refrigerator> {   // a class can implement many
    private final double kg;
    public Refrigerator(double kg) { this.kg = kg; }
    public double weightKg() { return kg; }
    public int compareTo(Refrigerator o) { return Double.compare(kg, o.kg); }
}

Shippable item = new Refrigerator(55);
System.out.println(item.needsHeavyCourier());   // true
```

## What an interface can contain
| Member | Since | Notes |
|---|---|---|
| Abstract methods | Java 1 | Public by default |
| Constants | Java 1 | public static final |
| Default methods | Java 8 | Body provided; implementing classes may override |
| Static methods | Java 8 | Called with the interface name |
| Private methods | Java 9 | Share code between default methods |

It cannot have instance fields or constructors.

## Special kinds
- Functional interface: exactly one abstract method, usable with lambdas (`Runnable`, `Comparator`, `Function`, `Predicate`). Mark with `@FunctionalInterface`.
- Marker interface: no methods, just tags a class (`Serializable`, `Cloneable`).
- Sealed interface (Java 17): restricts which classes may implement it.

## Why interfaces matter
Spring injects beans by interface (`PaymentGateway`), so implementations can be swapped (Razorpay, Stripe, a mock in tests) without changing the code that uses them.
MD,

'What is the difference between an abstract class and an interface?' => <<<'MD'
## Comparison
| Feature | Abstract class | Interface |
|---|---|---|
| Keyword | `abstract class`, used with `extends` | `interface`, used with `implements` |
| Multiple inheritance | A class extends only one | A class implements many |
| Methods | Abstract and concrete | Abstract, default, static, private |
| Fields | Any: instance, static, final or not | Only public static final constants |
| Constructors | Yes (called by subclasses) | No |
| Access modifiers on methods | Any | Public (private allowed for helpers) |
| State | Can hold state | Cannot hold instance state |
| Relationship | IS-A with shared base implementation | CAN-DO capability / contract |
| Speed of adding methods | Adding a concrete method is safe | Adding an abstract method breaks implementers; default methods avoid that |

## When to use which
Abstract class: closely related classes that share state and code.
```java
public abstract class BaseRepository<T> {
    protected final DataSource dataSource;                  // shared state
    protected BaseRepository(DataSource ds) { this.dataSource = ds; }
    public abstract T findById(long id);
    protected Connection connection() throws SQLException { return dataSource.getConnection(); }   // shared code
}
```

Interface: a capability that unrelated classes can have, or an API you want to keep swappable.
```java
public interface Exportable { byte[] toPdf(); }
class Invoice implements Exportable { … }
class Resume implements Exportable { … }       // Invoice and Resume are unrelated
```

## Both together
A common design: an interface for the contract, an abstract class with a skeletal implementation, and concrete classes. The JDK does this with `List` → `AbstractList` → `ArrayList`.

Since Java 8 the gap has narrowed (default methods), but interfaces still cannot hold instance state or constructors.
MD,

'What is an IS-A relationship?' => <<<'MD'
## What it is
IS-A is the relationship expressed by inheritance (`extends`) or interface implementation (`implements`). If B extends A, then B IS-A A, and a B object can be used anywhere an A is expected.

```java
class Vehicle { void start() { System.out.println("Starting"); } }
class Car extends Vehicle { }                    // Car IS-A Vehicle
class ElectricCar extends Car implements Chargeable { public void charge() { } }   // ElectricCar IS-A Car, Vehicle and Chargeable

Vehicle v = new ElectricCar();                   // valid because of IS-A
System.out.println(v instanceof Car);            // true
System.out.println(v instanceof Chargeable);     // true
```

## Test for IS-A: Liskov Substitution Principle
A subclass should be usable wherever the parent is used without breaking expectations.
- A Savings Account IS-A Bank Account: good inheritance.
- A Square IS-A Rectangle? Mathematically yes, but if `Rectangle.setWidth()` changes only width, a Square breaks that expectation. Classic example of where IS-A in English does not mean inheritance in code.

## Characteristics
- Expressed with `extends` and `implements`.
- Tight coupling: changes in the parent affect all children.
- Enables polymorphism.
- Checked with `instanceof`.

## IS-A vs HAS-A preview
| IS-A | HAS-A |
|---|---|
| Inheritance | Composition / aggregation |
| `class Car extends Vehicle` | `class Car { private Engine engine; }` |
| Fixed at compile time | Can change at run time |
| Tight coupling | Loose coupling |
MD,

'What is a HAS-A relationship?' => <<<'MD'
## What it is
HAS-A means one class contains a reference to another class as a field: a Car HAS-A Engine, an Order HAS-A list of Items. It is implemented with composition or aggregation, not inheritance.

```java
public class Engine {
    private final int cc;
    public Engine(int cc) { this.cc = cc; }
    public void start() { System.out.println(cc + "cc engine started"); }
}

public class Car {
    private final Engine engine;                 // Car HAS-A Engine
    private final String model;

    public Car(String model, Engine engine) {
        this.model = model;
        this.engine = engine;
    }

    public void drive() {
        engine.start();                          // delegation
        System.out.println(model + " is moving");
    }
}

new Car("Creta", new Engine(1497)).drive();
```

## Two forms
| | Composition (strong HAS-A) | Aggregation (weak HAS-A) |
|---|---|---|
| Lifetime | Part cannot exist without the whole | Part exists independently |
| Ownership | Whole creates and owns the part | Whole only refers to the part |
| Example | House HAS rooms; delete house, rooms go too | Department HAS employees; employees exist if the department closes |
| Code hint | Part created inside the class | Part passed in from outside and shared |

```java
class House {
    private final List<Room> rooms = List.of(new Room("Hall"), new Room("Kitchen"));   // composition
}
class Department {
    private final List<Employee> employees;                                            // aggregation
    Department(List<Employee> employees) { this.employees = employees; }
}
```

## Benefits
- Loose coupling: the Engine can be replaced (petrol, diesel, electric) through an interface.
- Reuse without inheriting unwanted behaviour.
- Easier testing: inject a mock part.
This is exactly how Spring dependency injection works: an OrderService HAS-A PaymentGateway.
MD,

'When should composition be preferred?' => <<<'MD'
## Principle
"Favour composition over inheritance" (Gang of Four, Effective Java). Use inheritance only for a genuine, stable IS-A relationship where the subclass truly is a specialised version of the parent. Use composition in most other cases.

## Problems with inheritance
1. Tight coupling: changes in the parent can break subclasses (fragile base class).
2. Exposes everything: the child inherits all public methods, even unsuitable ones.
3. Single inheritance: a class can only extend one class.
4. Fixed at compile time: behaviour cannot be swapped at run time.

## Classic example: counting additions
```java
// Inheritance: broken
class CountingSet<E> extends HashSet<E> {
    int added = 0;
    @Override public boolean add(E e) { added++; return super.add(e); }
    @Override public boolean addAll(Collection<? extends E> c) { added += c.size(); return super.addAll(c); }
}
CountingSet<String> s = new CountingSet<>();
s.addAll(List.of("a", "b", "c"));
System.out.println(s.added);   // 6, not 3: HashSet.addAll internally calls add()
```

```java
// Composition: correct and independent of HashSet's internals
class CountingSet<E> {
    private final Set<E> set = new HashSet<>();
    private int added = 0;
    public boolean add(E e) { added++; return set.add(e); }
    public boolean addAll(Collection<? extends E> c) { added += c.size(); return set.addAll(c); }
    public int added() { return added; }
}
```

## Swapping behaviour at run time (Strategy pattern)
```java
interface DiscountStrategy { double apply(double price); }

class Checkout {
    private DiscountStrategy discount;                       // HAS-A strategy
    Checkout(DiscountStrategy d) { this.discount = d; }
    void setDiscount(DiscountStrategy d) { this.discount = d; }   // change at run time
    double total(double price) { return discount.apply(price); }
}

Checkout c = new Checkout(p -> p);           // no discount
c.setDiscount(p -> p * 0.9);                 // festive sale: 10% off
```
With inheritance you would need `FestiveCheckout`, `ClearanceCheckout`, `PrimeFestiveCheckout`… a class explosion.

## Prefer composition when
- The relationship is "uses" or "has", not "is".
- You need behaviour from several sources.
- Behaviour should change at run time.
- You do not control the parent class (library classes).
- You want easy unit testing with mocks.

## Inheritance is fine when
- There is a true IS-A relationship that satisfies Liskov substitution.
- The parent is designed and documented for extension (abstract base classes, template methods).
MD,

];
