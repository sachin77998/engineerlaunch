<?php

// Java › Constructors, Access Modifiers and static, in depth.
return [

'What is a constructor?' => <<<'MD'
## What it is
A constructor is a special block of code that runs automatically when an object is created with `new`. Its job is to initialise the new object so it starts in a valid state.

## Rules that make it different from a method
- Its name is exactly the class name.
- It has no return type, not even `void`.
- It is called once per object, by `new` (or by `this()`/`super()` from another constructor).
- It cannot be `static`, `final` or `abstract`.
- It can have any access modifier: public, protected, package-private or private.

```java
public class Account {
    private final String accountNo;
    private final String holder;
    private double balance;

    public Account(String accountNo, String holder, double openingDeposit) {   // constructor
        if (openingDeposit < 1000) throw new IllegalArgumentException("Minimum opening deposit is ₹1000");
        this.accountNo = accountNo;
        this.holder = holder;
        this.balance = openingDeposit;
    }
}

Account acc = new Account("SB1001", "Kiran", 5000);
```

## What happens on new
1. Memory is allocated and fields get default values.
2. The constructor is called; its first line calls the parent constructor (`super()`).
3. Field initializers and instance blocks run.
4. The rest of the constructor body runs.
5. The reference to the fully initialised object is returned.

Note: if a method has the same name as the class but a return type (`public void Account() {}`), it is an ordinary method, not a constructor. The compiler allows it, which makes it a classic trick question.
MD,

'Why do we use a constructor?' => <<<'MD'
## Reasons
1. Guarantee a valid initial state: required values must be supplied at creation, so no half-built object exists.
2. Validation in one place: reject bad input before the object is used.
3. Immutability: `final` fields can only be assigned in the constructor (or at declaration), which allows immutable objects.
4. Dependency injection: an object receives its collaborators through the constructor (Spring's recommended style).
5. Convenience: one line creates and initialises the object.

## Without a constructor
```java
User u = new User();
u.setEmail("ravi@example.com");
// forgot u.setName(...) → object is incomplete and may cause NullPointerException later
```

## With a constructor
```java
public final class User {
    private final String name;
    private final String email;

    public User(String name, String email) {
        this.name = Objects.requireNonNull(name, "name");
        if (!email.contains("@")) throw new IllegalArgumentException("Invalid email");
        this.email = email;
    }
}

User u = new User("Ravi", "ravi@example.com");   // always complete and valid
```

## Constructor injection in Spring
```java
@Service
public class OrderService {
    private final PaymentGateway gateway;          // final: cannot be changed later

    public OrderService(PaymentGateway gateway) {  // Spring passes the bean in
        this.gateway = gateway;
    }
}
```
This makes dependencies explicit, supports immutability and makes unit testing easy: pass a mock in the test.
MD,

'Does a constructor have a return type?' => <<<'MD'
## Answer
No. A constructor has no declared return type, not even `void`. It implicitly produces the newly created object, but you never write `return value;` in it. A bare `return;` to exit early is allowed.

```java
public class Product {
    private String name;

    public Product(String name) {      // constructor: no return type
        if (name == null) return;      // allowed (exits early) but rarely good practice
        this.name = name;
    }

    public void Product() {            // NOT a constructor: it has a return type, so it is a method
        System.out.println("I am just a method named Product");
    }
}

Product p = new Product("Pen");   // calls the constructor
p.Product();                      // calls the method
```

## Why no return type?
`new` is what creates and returns the object. The constructor only initialises the memory that `new` has already allocated, so it has nothing to return. Adding a return type turns it into an ordinary method, and then the compiler supplies a default constructor instead (if no other constructor exists).
MD,

'Can a constructor be private?' => <<<'MD'
## Answer
Yes. A private constructor can only be called from inside the class itself. It is used to control or prevent object creation.

## Use 1: Singleton (exactly one instance)
```java
public class AppConfig {
    private static final AppConfig INSTANCE = new AppConfig();
    private AppConfig() { /* load settings */ }
    public static AppConfig getInstance() { return INSTANCE; }
}
```

## Use 2: Utility classes (no instance at all)
```java
public final class DateUtils {
    private DateUtils() { throw new AssertionError("No instances"); }
    public static boolean isWeekend(LocalDate d) { return d.getDayOfWeek().getValue() >= 6; }
}
```

## Use 3: Static factory methods with meaningful names
```java
public class Money {
    private final long paise;
    private Money(long paise) { this.paise = paise; }

    public static Money ofRupees(long rupees) { return new Money(rupees * 100); }
    public static Money ofPaise(long paise)   { return new Money(paise); }
}
Money price = Money.ofRupees(499);
```

## Use 4: Builder pattern
The class has a private constructor that takes the Builder; only `Builder.build()` can create it.

## Use 5: Constructor chaining
A private constructor holds common initialisation code that public constructors call with `this(...)`.

## Effects
- Other classes cannot write `new AppConfig()` (compile error).
- The class cannot be subclassed from outside, because a subclass constructor must call a super constructor it can access.
- Reflection can still call it with `setAccessible(true)`; an enum singleton is protected even against that.
MD,

'What is a default constructor?' => <<<'MD'
## What it is
If a class declares no constructor at all, the compiler adds a default constructor: public (same access as the class), no parameters, and its only action is calling `super()`.

```java
public class Student {
    String name;      // default null
    int age;          // default 0
}

// The compiler effectively generates:
// public Student() { super(); }

Student s = new Student();   // works
```

## Important: it disappears when you write any constructor
```java
public class Student {
    String name;
    public Student(String name) { this.name = name; }
}

Student s = new Student();   // compile error: constructor Student() is undefined
```
If you still need a no-argument constructor, write it yourself:
```java
public Student() { this("Unknown"); }
```

## Why frameworks care
JPA/Hibernate entities and many serialization libraries (Jackson by default) create objects through a no-argument constructor and then set fields. That is why entities usually declare a protected or public no-arg constructor.

## Default constructor vs no-arg constructor
- Default constructor: generated by the compiler, only when you write none.
- No-arg constructor: any constructor with no parameters, which you may write yourself with custom logic.
MD,

'What is a parameterized constructor?' => <<<'MD'
## What it is
A constructor that takes arguments, so each object can be initialised with different values at creation time.

```java
public class Job {
    private final String title;
    private final String company;
    private final double minSalaryLpa;

    public Job(String title, String company, double minSalaryLpa) {
        this.title = title;
        this.company = company;
        this.minSalaryLpa = minSalaryLpa;
    }

    @Override public String toString() { return title + " at " + company + " (" + minSalaryLpa + " LPA+)"; }
}

Job j1 = new Job("Java Developer", "TCS", 6.5);
Job j2 = new Job("Data Analyst", "Flipkart", 12);
System.out.println(j1);   // Java Developer at TCS (6.5 LPA+)
```

## this.field = parameter
When the parameter has the same name as the field, the parameter shadows the field. `this.title` refers to the field, `title` to the parameter. Forgetting `this.` (`title = title;`) assigns the parameter to itself and leaves the field null.

## Many parameters? Use a builder
A constructor with six or more parameters of similar types is error-prone (`new Job("TCS", "Java Developer", ...)` swaps silently). Use the Builder pattern or a record:
```java
public record JobPost(String title, String company, double minSalaryLpa) { }
JobPost p = new JobPost("Java Developer", "TCS", 6.5);   // canonical constructor generated
```
MD,

'What is constructor overloading?' => <<<'MD'
## What it is
Constructor overloading means a class has several constructors with different parameter lists (number, types or order of parameters). The compiler chooses one based on the arguments passed to `new`.

```java
public class Pizza {
    private final String size;
    private final String crust;
    private final List<String> toppings;

    public Pizza() {
        this("Medium");                                 // delegates
    }

    public Pizza(String size) {
        this(size, "Classic");
    }

    public Pizza(String size, String crust) {
        this(size, crust, List.of("Cheese"));
    }

    public Pizza(String size, String crust, List<String> toppings) {   // the one that does the work
        this.size = size;
        this.crust = crust;
        this.toppings = List.copyOf(toppings);
    }
}

new Pizza();                                     // Medium, Classic, [Cheese]
new Pizza("Large");                              // Large, Classic, [Cheese]
new Pizza("Small", "Thin", List.of("Paneer", "Onion"));
```

## Rules
- Parameter lists must differ; differing only by parameter names is not allowed.
- Access modifiers can differ between overloads.
- Resolution happens at compile time (static polymorphism).

## Good practice
Let the shorter constructors call the most complete one with `this(...)` (telescoping), so validation and assignment live in one place. Real examples: `new ArrayList<>()`, `new ArrayList<>(100)` and `new ArrayList<>(otherCollection)`.
MD,

'What is constructor chaining?' => <<<'MD'
## What it is
Constructor chaining is calling one constructor from another:
- `this(...)` calls another constructor of the same class.
- `super(...)` calls a constructor of the parent class.

It avoids duplicated initialisation code and makes sure parent parts of an object are initialised before child parts.

## Within the same class
```java
public class Employee {
    private final String name;
    private final String dept;
    private final double salary;

    public Employee(String name) { this(name, "Trainee"); }
    public Employee(String name, String dept) { this(name, dept, 25000); }
    public Employee(String name, String dept, double salary) {
        this.name = name; this.dept = dept; this.salary = salary;
    }
}
```

## Across the hierarchy
```java
class Vehicle {
    Vehicle(String reg) { System.out.println("Vehicle " + reg); }
}
class Car extends Vehicle {
    Car(String reg) {
        super(reg);                                  // must be first
        System.out.println("Car ready");
    }
}
class ElectricCar extends Car {
    ElectricCar(String reg) {
        super(reg);
        System.out.println("Battery checked");
    }
}
new ElectricCar("HR26AB1234");
```
```text
Vehicle HR26AB1234
Car ready
Battery checked
```
Construction always runs from the top of the hierarchy (Object) down to the actual class.

## Rules
- `this(...)` or `super(...)` must be the first statement of the constructor.
- A constructor cannot contain both.
- If you write neither, the compiler inserts `super()`. If the parent has no no-arg constructor, that is a compile error.
- Recursive chaining (`A()` calls `this(1)` which calls `this()`) is a compile error.
MD,

'What is this()?' => <<<'MD'
## What it is
`this()` (with parentheses) calls another constructor of the same class. It is used for constructor chaining.

```java
public class Rectangle {
    private final int width, height;

    public Rectangle() { this(1, 1); }            // calls Rectangle(int, int)
    public Rectangle(int side) { this(side, side); }
    public Rectangle(int width, int height) {
        if (width <= 0 || height <= 0) throw new IllegalArgumentException("Sides must be positive");
        this.width = width;
        this.height = height;
    }
}
```

## Rules
- Can only appear inside a constructor.
- Must be the first statement.
- Cannot be combined with `super()` in the same constructor (the chained constructor will call `super()` itself).

## this() vs this
| this() | this |
|---|---|
| Calls another constructor of the same class | Reference to the current object |
| Only in constructors, first line | In any instance method or constructor |
| Used for chaining | Used to access fields (`this.name = name`), pass the current object (`register(this)`), return it for fluent APIs (`return this;`) |

```java
public class QueryBuilder {
    private final StringBuilder sql = new StringBuilder("SELECT * FROM jobs");
    public QueryBuilder where(String cond) { sql.append(" WHERE ").append(cond); return this; }   // fluent
}
```
MD,

'What is super()?' => <<<'MD'
## What it is
`super()` calls a constructor of the immediate parent class. It makes sure the inherited part of the object is initialised before the child adds its own state.

```java
class Person {
    protected final String name;
    Person(String name) { this.name = name; }
}

class Employee extends Person {
    private final String empId;
    Employee(String name, String empId) {
        super(name);           // initialise the Person part first
        this.empId = empId;
    }
}
```

## Rules
- Must be the first statement of the constructor.
- If you do not write `super(...)` or `this(...)`, the compiler inserts `super()` (no arguments).
- If the parent has only parameterised constructors, you must call one explicitly, otherwise compile error: "constructor Person in class Person cannot be applied to given types".

## The super keyword without parentheses
`super.` accesses the parent's members:
```java
class Manager extends Employee {
    @Override
    public double salary() {
        return super.salary() + 20000;        // call the parent's version and extend it
    }
}
```

| super() | super. |
|---|---|
| Calls parent constructor | Calls parent method or accesses parent field |
| Only first line of a constructor | Anywhere in instance code |
MD,

'Can constructors be inherited?' => <<<'MD'
## Answer
No. Constructors are not inherited and cannot be overridden. A subclass must declare its own constructors, and each one calls a parent constructor with `super(...)`.

```java
class Vehicle {
    Vehicle(String regNo) { }
}

class Bike extends Vehicle {
    // No constructor declared: the compiler adds Bike() { super(); }
    // But Vehicle has no no-arg constructor → compile error.
}

class Car extends Vehicle {
    Car(String regNo) { super(regNo); }    // correct: declare and chain
}

new Car("DL3C1234");          // fine
// new Car();                 // error: Car has no no-arg constructor even though Vehicle might
```

## Why not inherited?
- A constructor's name is its class name; a parent constructor named `Vehicle` makes no sense for creating a `Car`.
- A subclass usually has extra fields that the parent constructor knows nothing about, so inheriting it would create partially initialised objects.

## Related facts
- Constructors cannot be overridden, so there is no runtime polymorphism for constructors.
- They can be overloaded within a class.
- Records and Lombok (`@AllArgsConstructor`, `@RequiredArgsConstructor`) generate constructors to reduce boilerplate.
MD,

'How are private constructors used in the Singleton pattern?' => <<<'MD'
## Idea
Singleton ensures a class has exactly one instance and gives a global access point to it. The private constructor stops other code from calling `new`; a static method or field hands out the single instance.

## 1. Eager initialisation
```java
public class ConfigManager {
    private static final ConfigManager INSTANCE = new ConfigManager();   // created when the class loads
    private ConfigManager() { }
    public static ConfigManager getInstance() { return INSTANCE; }
}
```
Simple and thread-safe (class loading is thread-safe), but the object is created even if never used.

## 2. Lazy with double-checked locking
```java
public class DbConnectionPool {
    private static volatile DbConnectionPool instance;    // volatile prevents seeing a half-built object
    private DbConnectionPool() { }

    public static DbConnectionPool getInstance() {
        if (instance == null) {                            // 1st check without locking (fast path)
            synchronized (DbConnectionPool.class) {
                if (instance == null) {                    // 2nd check inside the lock
                    instance = new DbConnectionPool();
                }
            }
        }
        return instance;
    }
}
```

## 3. Bill Pugh holder (lazy and thread-safe without locks)
```java
public class Logger {
    private Logger() { }
    private static class Holder { static final Logger INSTANCE = new Logger(); }
    public static Logger getInstance() { return Holder.INSTANCE; }   // Holder loads on first call
}
```

## 4. Enum singleton (recommended by Effective Java)
```java
public enum IdGenerator {
    INSTANCE;
    private final AtomicLong next = new AtomicLong();
    public long nextId() { return next.incrementAndGet(); }
}
IdGenerator.INSTANCE.nextId();
```
Safe against reflection and serialization attacks automatically.

## Breaking a singleton and the defences
| Attack | Defence |
|---|---|
| Reflection `setAccessible(true)` | Throw from the constructor if an instance exists, or use an enum |
| Serialization creates a new object | Implement `readResolve()` returning INSTANCE, or use an enum |
| Cloning | Do not implement Cloneable, or throw in clone() |

Note: in Spring, beans are singletons per container by default, so you rarely hand-write this pattern in Spring applications.
MD,

'What are access modifiers?' => <<<'MD'
## What they are
Access modifiers control where a class, constructor, method or field can be used from. They are Java's main tool for encapsulation: hide internal details, expose only what callers need.

## The four levels
| Modifier | Same class | Same package | Subclass (other package) | Everywhere |
|---|---|---|---|---|
| private | Yes | No | No | No |
| (none) package-private | Yes | Yes | No | No |
| protected | Yes | Yes | Yes (through inheritance) | No |
| public | Yes | Yes | Yes | Yes |

## Example
```java
package com.portal.payment;

public class PaymentService {                 // usable everywhere
    private String apiKey;                    // only inside this class
    int retryCount = 3;                       // package-private: classes in com.portal.payment
    protected void log(String msg) { }        // package + subclasses anywhere
    public boolean pay(double amount) {       // public API
        return charge(amount);
    }
    private boolean charge(double amount) { return true; }   // implementation detail
}
```

## Where each can be used
- Top-level classes: only public or package-private.
- Members (fields, methods, constructors, nested classes): all four.
- Interface methods are public by default; interface fields are `public static final`.

## Java 9 modules
With JPMS, even public classes are visible to other modules only if their package is exported in `module-info.java`, which adds a layer above these modifiers.

Rule of thumb: make everything as private as possible, and widen access only when there is a reason.
MD,

'What is public access?' => <<<'MD'
## Meaning
A `public` class, method, constructor or field is accessible from any class in any package (and, with modules, from any module that reads an exported package).

```java
package com.portal.util;

public class CurrencyFormatter {
    public static final String SYMBOL = "₹";

    public String format(double amount) {
        return SYMBOL + String.format("%,.2f", amount);
    }
}

// In another package
import com.portal.util.CurrencyFormatter;
String text = new CurrencyFormatter().format(125000);   // ₹1,25,000.00 style output (locale dependent)
```

## Rules
- A source file can contain at most one public top-level class, and the file name must match it.
- `public static void main(String[] args)` must be public so the JVM can call it from outside.
- Overriding methods cannot reduce visibility: if a parent method is public, the override must be public.

## When to use
- The class's API: methods other parts of the system are meant to call.
- Constants intended to be shared (`public static final`).

## When not to use
Avoid public mutable fields (`public double balance;`). Anyone can set it to an invalid value and you cannot add validation later without breaking callers. Expose behaviour through public methods instead.
MD,

'What is private access?' => <<<'MD'
## Meaning
A `private` member is accessible only inside the class (top-level class including its nested classes) where it is declared. It is the most restrictive access level.

```java
public class BankAccount {
    private double balance;                       // hidden state
    private final List<String> history = new ArrayList<>();

    public void withdraw(double amount) {
        validate(amount);
        balance -= amount;
        history.add("Withdraw " + amount);
    }

    public double getBalance() { return balance; }      // controlled read access

    private void validate(double amount) {              // helper not part of the public API
        if (amount <= 0 || amount > balance) throw new IllegalArgumentException("Invalid amount");
    }
}

BankAccount acc = new BankAccount();
// acc.balance = 1_000_000;   // compile error: balance has private access
```

## Rules
- Private methods are not inherited and cannot be overridden; a method with the same signature in a subclass is a new method.
- Private members of a nested class are accessible to the outer class and vice versa.
- Top-level classes cannot be private.
- Private constructors control instantiation (Singleton, utility classes, factories).
- Interfaces can have private methods since Java 9, to share code between default methods.

## Why use it
- Protects invariants (balance can never go negative).
- Lets you change the internal representation without breaking other code.
- Reduces what other developers need to understand.
MD,

'What is protected access?' => <<<'MD'
## Meaning
A `protected` member is accessible:
1. inside its own class,
2. from any class in the same package, and
3. from subclasses in other packages, through inheritance.

```java
package com.portal.base;
public class Notification {
    protected String recipient;
    protected void send(String message) { System.out.println("To " + recipient + ": " + message); }
}

package com.portal.sms;
import com.portal.base.Notification;

public class SmsNotification extends Notification {
    public void alert() {
        recipient = "9876543210";          // OK: inherited protected field
        send("Interview scheduled");      // OK: inherited protected method
    }

    void test(Notification other) {
        // other.send("x");                // compile error: not through inheritance (different package)
        SmsNotification same = new SmsNotification();
        same.send("ok");                   // OK: reference of the subclass type
    }
}
```

## The subtle rule
In a different package, a subclass can access a protected member only through references of its own type (or its subtypes), not through a plain parent-type reference.

## Typical uses
- Template Method pattern: the parent defines `protected abstract` steps that subclasses implement.
- Hooks that subclasses may override (`protected void onSave()`).
- `Object.clone()` and `Object.finalize()` are protected.

## Overriding
An override may keep `protected` or widen it to `public`, but cannot make it package-private or private.
MD,

'What is package-private access?' => <<<'MD'
## Meaning
When no access modifier is written, the member or class is package-private (also called default access). It is visible only to classes in the same package.

```java
package com.portal.jobs;

class JobRanker {                         // package-private class: helper not visible outside the package
    double score(Job job) { return job.getViews() * 0.3 + job.getApplies() * 0.7; }
}

public class JobSearchService {
    private final JobRanker ranker = new JobRanker();     // same package: OK
    public List<Job> search(String q) { /* uses ranker */ return List.of(); }
}

package com.portal.web;
// new JobRanker();     // compile error: JobRanker is not public in com.portal.jobs
```

## Key points
- Subclasses in other packages cannot access package-private members.
- Packages are not hierarchical: `com.portal.jobs.internal` cannot see package-private members of `com.portal.jobs`.
- Interface members are never package-private: methods default to public.

## Why use it
- Hide helper classes and methods that are implementation details of a package, while still sharing them inside the package.
- Unit tests placed in the same package (the usual Maven layout, `src/test/java/com/portal/jobs`) can access package-private methods, which is convenient for testing without making them public.
- Spring Boot 2.x+ test classes and JUnit 5 test methods are often package-private.
MD,

'Which modifier is most restrictive?' => <<<'MD'
## Answer
`private` is the most restrictive: the member is visible only inside its own class. `public` is the least restrictive.

```text
Most restrictive → least restrictive
private  <  package-private (no modifier)  <  protected  <  public
```

| Modifier | Class | Package | Subclass in other package | World |
|---|---|---|---|---|
| private | Yes | No | No | No |
| package-private | Yes | Yes | No | No |
| protected | Yes | Yes | Yes | No |
| public | Yes | Yes | Yes | Yes |

## Why the order matters: overriding
An overriding method cannot be more restrictive than the method it overrides.
```java
class Parent { protected void run() { } }
class Child extends Parent {
    @Override public void run() { }          // OK: widening protected → public
    // @Override void run() { }              // error: attempting to assign weaker access privileges
}
```

## Interview tip
"Protected is more restrictive than package-private" is a common wrong answer. Protected includes everything package-private allows, plus subclass access from other packages, so it is less restrictive.
MD,

'Can a method or constructor be private?' => <<<'MD'
## Answer
Yes, both can be private.

## Private methods
Used as internal helpers that are not part of the class's API.
```java
public class InvoiceService {
    public Invoice create(Order order) {
        double tax = calculateGst(order.getAmount());
        return new Invoice(order.getId(), order.getAmount() + tax);
    }

    private double calculateGst(double amount) {     // hidden helper
        return amount * 0.18;
    }
}
```
Characteristics:
- Not inherited, so they cannot be overridden (a same-named method in a subclass is independent).
- Resolved at compile time (static binding), and the JIT can inline them easily.
- Since Java 9 interfaces can have private methods shared by default methods.

## Private constructors
Used to control object creation:
```java
public final class Validators {
    private Validators() { }                         // no instances of a utility class
    public static boolean isPan(String s) { return s.matches("[A-Z]{5}[0-9]{4}[A-Z]"); }
}

public class Connection {
    private Connection(String url) { }
    public static Connection open(String url) { return new Connection(url); }   // static factory
}
```
Uses: Singleton, utility classes, static factory methods, builders and enums (enum constructors are always private).

Side effect: a class whose only constructors are private cannot be subclassed by other classes (except its own nested classes).
MD,

'What is the difference between private and protected?' => <<<'MD'
## Comparison
| Feature | private | protected |
|---|---|---|
| Same class | Yes | Yes |
| Same package, other class | No | Yes |
| Subclass in another package | No | Yes, through inheritance |
| Inherited by subclasses | No (exists in the object but not accessible) | Yes |
| Can be overridden (methods) | No | Yes |
| Can be used on top-level class | No | No |
| Purpose | Hide implementation completely | Share with subclasses (extension points) |

## Example
```java
public class Report {
    private String rawData;                         // only Report can touch it
    protected String title = "Monthly report";      // subclasses can customise it

    protected String format() { return title.toUpperCase(); }   // hook for subclasses
    private void loadData() { rawData = "…"; }                   // internal step
}

public class SalesReport extends Report {
    public SalesReport() {
        title = "Sales report";        // OK: protected
        // rawData = "x";              // error: private
    }

    @Override
    protected String format() {        // OK: overriding a protected method
        return "[SALES] " + super.format();
    }
}
```

## Guideline
Start with private. Make something protected only when you are deliberately designing the class for extension, because every protected member becomes a contract that subclasses may depend on.
MD,

'Why should fields usually be private?' => <<<'MD'
## Short answer
Private fields enforce encapsulation: the object controls its own state, so it can guarantee its rules (invariants), and its internal representation can change without breaking other code.

## Problem with public fields
```java
public class Account {
    public double balance;
}
Account a = new Account();
a.balance = -50000;          // invalid state, nothing can stop it
```

## Private fields with controlled access
```java
public class Account {
    private double balance;

    public double getBalance() { return balance; }

    public void deposit(double amount) {
        if (amount <= 0) throw new IllegalArgumentException("Deposit must be positive");
        balance += amount;
    }

    public void withdraw(double amount) {
        if (amount > balance) throw new IllegalStateException("Insufficient funds");
        balance -= amount;
    }
}
```

## Benefits
1. Validation: every change goes through methods that check rules.
2. Flexibility: you can change `double balance` to `BigDecimal` or `long paise` internally; callers using `getBalance()` are unaffected.
3. Read-only or write-only: provide only a getter, or only a setter.
4. Thread safety: synchronise inside the methods that change state.
5. Debugging: put a breakpoint or log in one setter instead of searching all assignments.
6. Lazy loading and derived values: a getter can compute or load values on demand.
7. Immutability: private final fields with no setters produce immutable objects that are safe to share.

## Exceptions
- `public static final` constants (`MAX_SIZE`) are fine.
- Records expose components through accessor methods, with fields still private and final.
MD,

'What is the static keyword?' => <<<'MD'
## What it is
`static` marks a member as belonging to the class itself rather than to individual objects. There is one copy shared by all instances, and it can be used without creating an object.

## Where static can be used
| Usage | Meaning |
|---|---|
| Static variable | One shared copy for the class |
| Static method | Called on the class; no `this` |
| Static block | Runs once when the class is initialised |
| Static nested class | Nested class that does not need an outer instance |
| Static import | Use static members without the class name (`import static java.lang.Math.*;`) |

## Example
```java
public class Order {
    private static int totalOrders = 0;                 // shared counter
    private static final double GST = 0.18;             // shared constant
    private final int orderNo;

    static { System.out.println("Order class loaded"); }   // static block

    public Order() { orderNo = ++totalOrders; }

    public static int getTotalOrders() { return totalOrders; }    // static method
    public static double withGst(double amount) { return amount * (1 + GST); }
}

new Order(); new Order();
System.out.println(Order.getTotalOrders());   // 2
System.out.println(Order.withGst(1000));      // 1180.0
```

## Rules
- Static methods cannot use `this` or `super` and cannot access instance members directly.
- Static methods are not overridden (they are hidden).
- Top-level classes cannot be static; only nested classes can.
- Local variables cannot be static.

## When to use
Constants, counters, utility/helper functions (`Math.sqrt`, `Collections.sort`), factory methods (`List.of`). Avoid mutable static state in multi-threaded or testable code: it is effectively a global variable.
MD,

'What is a static variable?' => <<<'MD'
## What it is
A static variable (class variable) is declared with `static`. Only one copy exists per class (per class loader), shared by every object, created when the class is initialised.

```java
public class Visitor {
    static int count = 0;          // shared
    String name;                   // per object

    Visitor(String name) {
        this.name = name;
        count++;
    }
}

new Visitor("A"); new Visitor("B"); new Visitor("C");
System.out.println(Visitor.count);   // 3
```

```text
Class area: Visitor.count = 3
Heap: Visitor{name="A"}  Visitor{name="B"}  Visitor{name="C"}
```

## Characteristics
- Initialised once, when the class is first used; default values apply (0, null, false).
- Lives as long as the class is loaded, usually the whole application.
- Accessed with the class name: `Visitor.count` (accessing via an object compiles but is misleading).
- Stored with the class's data (in the heap alongside the Class object since Java 8; metadata in Metaspace).

## Constants
```java
public static final int MAX_LOGIN_ATTEMPTS = 3;     // UPPER_SNAKE_CASE by convention
```

## Thread safety
`count++` is not atomic: two threads can read the same value and lose an update. Use `AtomicInteger` or synchronisation:
```java
private static final AtomicInteger count = new AtomicInteger();
count.incrementAndGet();
```

## Pitfalls
- Static collections that keep growing cause memory leaks.
- Static mutable state makes unit tests depend on each other.
MD,

'What is a static method?' => <<<'MD'
## What it is
A static method belongs to the class and is called without an object: `ClassName.method()`. It has no `this` reference, so it works only with its parameters and static data.

```java
public final class SalaryUtils {
    private SalaryUtils() { }

    public static double monthlyInHand(double ctcLpa, double pfPercent) {
        double monthly = ctcLpa * 100000 / 12;
        return monthly - monthly * pfPercent / 100;
    }
}

double inHand = SalaryUtils.monthlyInHand(12, 6);   // no object needed
```

## Examples from the JDK
`Math.max()`, `Integer.parseInt()`, `String.valueOf()`, `Arrays.sort()`, `Collections.unmodifiableList()`, `List.of()`, `LocalDate.now()`.

## Why is main static?
The JVM must call `main` before any object exists, so it must be static.

## Rules
- Can access static variables and call other static methods directly.
- Cannot access instance variables or instance methods directly (needs an object reference).
- Cannot use `this` or `super`.
- Can be overloaded; cannot be overridden (a same-signature static method in a subclass hides it).
- Can be called through an object reference, but it still uses the declared type, which is confusing.

## When to use
- Pure functions that depend only on inputs (calculations, validation, formatting).
- Factory methods (`Money.ofRupees(500)`).
- Utility classes.

Avoid static methods for business logic that needs collaborators (repositories, services); those are hard to mock in tests. Prefer instance methods on injected beans.
MD,

'Can a static method access instance data directly?' => <<<'MD'
## Answer
No. A static method runs without any object, so there is no `this` to say whose instance variable is meant. Accessing an instance field or method directly is a compile error.

```java
public class Student {
    private String name = "Asha";              // instance variable
    private static String school = "DPS";     // static variable

    public static void print() {
        System.out.println(school);           // OK: static
        // System.out.println(name);          // error: non-static variable name cannot be referenced from a static context
        // display();                         // error: non-static method
    }

    public void display() { System.out.println(name); }
}
```

## The way around: use an object reference
```java
public static void print(Student s) {
    System.out.println(s.name);               // OK: accessed through a reference
    s.display();
}

public static void main(String[] args) {
    Student s = new Student();                // main is static, so create an object first
    s.display();
}
```

## The reverse is fine
Instance methods can access static members directly, because static data does not depend on any particular object.

| From \ To | Static member | Instance member |
|---|---|---|
| Static method | Directly | Only via an object reference |
| Instance method | Directly | Directly (`this` is implicit) |
MD,

'Can a static method be overridden?' => <<<'MD'
## Answer
No. Static methods are bound at compile time to the declared (reference) type, not the runtime object type. If a subclass declares a static method with the same signature, it hides the parent's method; it does not override it.

```java
class Parent {
    static void greet() { System.out.println("Parent static"); }
    void show()         { System.out.println("Parent instance"); }
}

class Child extends Parent {
    static void greet() { System.out.println("Child static"); }     // hides Parent.greet()
    @Override void show() { System.out.println("Child instance"); } // overrides
}

Parent p = new Child();
p.greet();    // Parent static   ← decided by reference type (compile time)
p.show();     // Child instance  ← decided by object type (run time)
```

## Proof
Adding `@Override` to `Child.greet()` gives a compile error: "method does not override or implement a method from a supertype".

## Related rules
- A static method cannot hide an instance method, and an instance method cannot override a static one (compile errors).
- The hiding method must not have weaker access.
- Overriding = runtime polymorphism; hiding = compile-time resolution.

## Why the design
Overriding relies on the actual object, but static methods do not need an object at all, so there is nothing to choose from at run time.
MD,

'What is method hiding?' => <<<'MD'
## What it is
Method hiding happens when a subclass defines a static method with the same signature as a static method in its parent. The subclass method hides the parent one. Which method runs depends on the reference type used at compile time, not on the actual object.

```java
class Logger {
    static String level() { return "INFO"; }
}

class DebugLogger extends Logger {
    static String level() { return "DEBUG"; }    // hides Logger.level()
}

Logger a = new DebugLogger();
DebugLogger b = new DebugLogger();

System.out.println(a.level());            // INFO   (reference type Logger)
System.out.println(b.level());            // DEBUG  (reference type DebugLogger)
System.out.println(Logger.level());       // INFO
System.out.println(DebugLogger.level());  // DEBUG
```

## Hiding vs overriding
| | Method hiding | Method overriding |
|---|---|---|
| Methods involved | Static | Instance |
| Resolved | Compile time, by reference type | Run time, by object type |
| Polymorphism | No | Yes |
| @Override allowed | No | Yes |

## Field hiding
Fields are never overridden either: a subclass field with the same name hides the parent field, and access is resolved by reference type.
```java
class A { String name = "A"; }
class B extends A { String name = "B"; }
A obj = new B();
System.out.println(obj.name);   // A
```
Tip: always call static methods with the class name (`DebugLogger.level()`) to avoid confusion.
MD,

'Can a static method be overloaded?' => <<<'MD'
## Answer
Yes. Overloading only requires the same method name with different parameter lists, and that works for static methods just as for instance methods. The compiler picks the version from the argument types.

```java
public final class Calculator {
    public static int add(int a, int b)            { return a + b; }
    public static double add(double a, double b)   { return a + b; }
    public static int add(int a, int b, int c)     { return a + b + c; }
    public static int add(int... numbers)          { return Arrays.stream(numbers).sum(); }
}

Calculator.add(2, 3);          // int version
Calculator.add(2.5, 3.1);      // double version
Calculator.add(1, 2, 3);       // three-arg version
Calculator.add(1, 2, 3, 4);    // varargs
```

## Even main can be overloaded
```java
public static void main(String[] args) { main(5); }
public static void main(int times) { System.out.println("Overloaded main " + times); }
```
The JVM only calls `main(String[])` as the entry point.

## Rules
- Changing only the return type is not overloading (compile error).
- A static and an instance method cannot share the same signature, but they can be overloads with different parameters.
- JDK examples: `String.valueOf(int)`, `String.valueOf(double)`, `String.valueOf(char[])`; `Math.abs(int/long/float/double)`.
MD,

'What is a static block?' => <<<'MD'
## What it is
A static block (static initializer) is a block of code marked `static { }` inside a class. It runs once, when the class is initialised, before any object is created or static method is called. It is used for complex initialisation of static fields.

```java
public class StateCodes {
    private static final Map<String, String> CODES;

    static {
        Map<String, String> m = new HashMap<>();
        m.put("HR", "Haryana");
        m.put("KA", "Karnataka");
        m.put("MH", "Maharashtra");
        CODES = Collections.unmodifiableMap(m);
        System.out.println("State codes loaded");
    }

    public static String name(String code) { return CODES.getOrDefault(code, "Unknown"); }
}

System.out.println(StateCodes.name("HR"));
```
```text
State codes loaded
Haryana
```

## Characteristics
- A class can have several static blocks; they run in source order, together with static field initializers.
- Can only access static members directly.
- Cannot throw checked exceptions; wrap them (an exception here causes `ExceptionInInitializerError`, and later uses throw `NoClassDefFoundError`).
- Classic use: loading a JDBC driver (`static { Class.forName("com.mysql.cj.jdbc.Driver"); }`), loading native libraries (`System.loadLibrary`), building lookup tables.

## Static block vs instance block vs constructor
| | Static block | Instance block | Constructor |
|---|---|---|---|
| Runs | Once per class | Every object, before the constructor body | Every object |
| Accesses | Static members | Everything | Everything |
MD,

'When is a static block executed?' => <<<'MD'
## Answer
When the class is initialised, which happens once, on the first active use of the class:
- creating the first object (`new Order()`),
- calling a static method,
- reading or writing a static field that is not a compile-time constant,
- `Class.forName("com.app.Order")`,
- initialising a subclass (the parent is initialised first),
- the class containing `main` when the program starts.

It does not run when you:
- only declare a variable of that type (`Order o;`),
- access a compile-time constant (`static final int MAX = 10;` is inlined by the compiler),
- create an array of that type (`new Order[10]`),
- use `Order.class`.

## Order of execution
```java
class Parent {
    static { System.out.println("1. Parent static"); }
    { System.out.println("3. Parent instance block"); }
    Parent() { System.out.println("4. Parent constructor"); }
}

class Child extends Parent {
    static { System.out.println("2. Child static"); }
    { System.out.println("5. Child instance block"); }
    Child() { System.out.println("6. Child constructor"); }
}

public class Main {
    static { System.out.println("0. Main static (before main runs)"); }
    public static void main(String[] args) {
        new Child();
        new Child();   // static blocks do not run again
    }
}
```
```text
0. Main static (before main runs)
1. Parent static
2. Child static
3. Parent instance block
4. Parent constructor
5. Child instance block
6. Child constructor
3. Parent instance block
4. Parent constructor
5. Child instance block
6. Child constructor
```
Class initialisation is thread-safe: the JVM guarantees a static block runs exactly once even if many threads use the class at the same time.
MD,

'What is a static nested class?' => <<<'MD'
## What it is
A static nested class is a class declared `static` inside another class. It is associated with the outer class, not with an outer object, so it can be created without an instance of the outer class.

```java
public class Pizza {
    private final String size;
    private final List<String> toppings;

    private Pizza(Builder b) { this.size = b.size; this.toppings = b.toppings; }

    public static class Builder {                 // static nested class
        private String size = "Medium";
        private final List<String> toppings = new ArrayList<>();

        public Builder size(String size) { this.size = size; return this; }
        public Builder topping(String t) { toppings.add(t); return this; }
        public Pizza build() { return new Pizza(this); }   // can call Pizza's private constructor
    }
}

Pizza p = new Pizza.Builder().size("Large").topping("Paneer").topping("Olives").build();
```

## Characteristics
- Can access the outer class's static members directly, including private ones.
- Cannot access the outer class's instance members without an object reference.
- Does not hold a hidden reference to an outer object, so it uses less memory and avoids leaks.
- Can have any access modifier.

## Static nested vs inner class
| | Static nested | Inner (non-static) |
|---|---|---|
| Needs outer object | No: `new Outer.Nested()` | Yes: `outer.new Inner()` |
| Access to outer instance fields | No (only via a reference) | Yes |
| Hidden outer reference | No | Yes |

## Real examples
`Map.Entry`, `AbstractMap.SimpleEntry`, builders, and node classes of linked lists and trees (`static class Node<T>`).

Guideline from Effective Java: if a nested class does not need access to an outer instance, make it static.
MD,

];
