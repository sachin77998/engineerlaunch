<?php

// Java › Class and Object, in depth.
return [

'Input and Output in Java' => <<<'MD'
## What it is
Input is data a program reads (keyboard, file, network); output is data it writes (console, file, socket). Java models both as streams: a sequence of bytes or characters flowing in one direction.

- Byte streams (`InputStream`, `OutputStream`) handle raw bytes: images, PDFs, network data.
- Character streams (`Reader`, `Writer`) handle text and take care of character encoding (UTF-8).
- `System.in` is the keyboard input stream, `System.out` the console output stream, `System.err` the error stream.

## Reading from the keyboard with Scanner
```java
import java.util.Scanner;

public class Greeting {
    public static void main(String[] args) {
        Scanner sc = new Scanner(System.in);
        System.out.print("Enter your name: ");
        String name = sc.nextLine();          // whole line
        System.out.print("Enter your age: ");
        int age = sc.nextInt();               // next integer token
        System.out.printf("Hello %s, next year you will be %d%n", name, age + 1);
        sc.close();
    }
}
```

Note: after `nextInt()` the newline is still in the buffer, so a following `nextLine()` returns an empty string. Call `sc.nextLine()` once to consume it.

## Fast input for competitive programming
```java
BufferedReader br = new BufferedReader(new InputStreamReader(System.in));
int n = Integer.parseInt(br.readLine().trim());
StringTokenizer st = new StringTokenizer(br.readLine());
int[] a = new int[n];
for (int i = 0; i < n; i++) a[i] = Integer.parseInt(st.nextToken());
```
`BufferedReader` reads large chunks at once and is many times faster than `Scanner` for big inputs.

## Output
```java
System.out.println("Total: " + 42);          // prints and adds a newline
System.out.print("No newline");
System.out.printf("%-10s|%8.2f%n", "Laptop", 54999.5);   // formatted: left-aligned text, 2 decimals
```

## Reading and writing files (java.nio)
```java
Path path = Path.of("jobs.txt");
Files.writeString(path, "Java Developer\nSpring Boot Engineer\n");
List<String> lines = Files.readAllLines(path);
lines.forEach(System.out::println);

try (BufferedWriter w = Files.newBufferedWriter(Path.of("out.csv"))) {
    w.write("id,title");
    w.newLine();
}
```

## Summary
- Scanner: easy keyboard input; BufferedReader: fast input.
- println/printf for output; `%d` integer, `%s` string, `%.2f` decimal, `%n` newline.
- Use `Files` and try-with-resources for files so streams are always closed.
MD,

'What is a class in Java?' => <<<'MD'
## What it is
A class is a blueprint or template that describes what an object will contain (fields/state) and what it can do (methods/behaviour). The class itself is just a definition; objects are the real instances created from it.

Analogy: the architect's plan of a flat is the class. The 200 flats built from that plan in a society are objects. Every flat has the same structure (rooms, doors) but different values (owner name, paint colour).

## Structure of a class
```java
public class Car {                       // class declaration
    // 1. Fields (state)
    private String brand;
    private String model;
    private int speed;

    // 2. Constructor (initialises a new object)
    public Car(String brand, String model) {
        this.brand = brand;
        this.model = model;
    }

    // 3. Methods (behaviour)
    public void accelerate(int amount) {
        speed += amount;
    }

    public String describe() {
        return brand + " " + model + " at " + speed + " km/h";
    }
}
```

## Using the class
```java
Car baleno = new Car("Maruti", "Baleno");
Car creta  = new Car("Hyundai", "Creta");
baleno.accelerate(60);
System.out.println(baleno.describe());   // Maruti Baleno at 60 km/h
System.out.println(creta.describe());    // Hyundai Creta at 0 km/h
```

## What a class can contain
- Fields (instance and static variables)
- Constructors
- Methods (instance and static)
- Blocks (instance initializer `{ }`, static block `static { }`)
- Nested classes, interfaces, enums and records

## Rules
- A `.java` file can have only one public top-level class, and the file name must match it (`Car.java`).
- Class names use PascalCase by convention.
- Every class implicitly extends `java.lang.Object`.
MD,

'Why do we need a class?' => <<<'MD'
## The problem without classes
Imagine storing 1,000 employees using separate variables or parallel arrays:

```java
String[] names = new String[1000];
double[] salaries = new double[1000];
String[] departments = new String[1000];
// Easy to get out of sync: names[5] must always match salaries[5]
```
Data and the logic that works on it are scattered, nothing stops invalid values (negative salary), and every new attribute means another array.

## The class solution
```java
public class Employee {
    private final String name;
    private double salary;
    private String department;

    public Employee(String name, double salary, String department) {
        if (salary < 0) throw new IllegalArgumentException("Salary cannot be negative");
        this.name = name;
        this.salary = salary;
        this.department = department;
    }

    public void giveRaise(double percent) {
        salary += salary * percent / 100;
    }

    public double getSalary() { return salary; }
}

List<Employee> staff = new ArrayList<>();
staff.add(new Employee("Asha", 60000, "IT"));
```

## Why classes are needed
1. Grouping: related data and behaviour live together (an Employee knows how to give itself a raise).
2. Reusability: write the blueprint once, create thousands of objects.
3. Encapsulation: private fields plus validation keep objects in a valid state.
4. Abstraction: callers use `giveRaise(10)` without knowing how it is calculated.
5. Inheritance and polymorphism: `Manager extends Employee` reuses code and can override behaviour.
6. Modelling the real world: Order, Customer, Invoice map naturally to business concepts, which makes large codebases understandable.
7. Java requires it: every method and variable must live inside a class (or interface/enum/record).
MD,

'What is the difference between a class and an object?' => <<<'MD'
## Short answer
A class is the blueprint; an object is a real thing built from that blueprint, occupying memory and holding its own values.

| Class | Object |
|---|---|
| Logical template / definition | Physical instance in memory |
| Declared once with the `class` keyword | Created many times with `new` |
| No memory for instance fields when declared | Heap memory allocated for its fields |
| Example: `Student` | Example: `rahul`, `priya` |
| Loaded once by the class loader | Each has its own state |

## Example
```java
class Student {               // class: one definition
    String name;
    int rollNo;
}

Student rahul = new Student();   // object 1
rahul.name = "Rahul"; rahul.rollNo = 1;

Student priya = new Student();   // object 2
priya.name = "Priya"; priya.rollNo = 2;
```

```text
Method area: class Student (fields: name, rollNo)
Heap:  [Student@1a2b: name="Rahul", rollNo=1]   ← rahul
       [Student@3c4d: name="Priya", rollNo=2]   ← priya
```

Analogy: "Smartphone model iPhone 16" is the class; the phone in your pocket with your photos and IMEI number is an object.
MD,

'Can a Java class exist without creating an object?' => <<<'MD'
## Answer
Yes. A class is loaded and usable without any instance being created. You can use its static members, and some classes are designed never to be instantiated.

## 1. Using static members
```java
public class MathUtils {
    public static final double GST = 0.18;

    public static double withGst(double amount) {
        return amount * (1 + GST);
    }
}

System.out.println(MathUtils.withGst(1000));   // 1180.0 — no object created
```
The JDK does the same: `Math.max(3, 7)`, `Integer.parseInt("42")`, `Collections.sort(list)`.

## 2. Utility classes that forbid objects
```java
public final class StringUtils {
    private StringUtils() { }          // private constructor: no one can call new StringUtils()
    public static boolean isBlank(String s) { return s == null || s.isBlank(); }
}
```

## 3. Abstract classes and interfaces
An abstract class cannot be instantiated directly, but it exists, can have static methods and is used through subclasses.

## 4. The main class
`public static void main(String[] args)` runs without creating an object of the class that contains it; that is why `main` is static.

## When does the class load?
The JVM loads and initialises a class the first time it is actively used: creating an object, calling a static method, or accessing a non-constant static field. Static blocks run at that moment, even if no object is ever created.
MD,

'What are instance variables?' => <<<'MD'
## What they are
Instance variables are non-static fields declared inside a class but outside any method. Every object gets its own separate copy, stored inside that object on the heap.

```java
public class BankAccount {
    private String holder;        // instance variable
    private double balance;       // instance variable

    public BankAccount(String holder) { this.holder = holder; }
    public void deposit(double amt) { balance += amt; }
}

BankAccount a = new BankAccount("Ravi");
BankAccount b = new BankAccount("Meena");
a.deposit(500);
// a.balance = 500.0, b.balance = 0.0 — separate copies
```

## Characteristics
- Created when the object is created with `new`, destroyed when the object is garbage collected.
- Get default values if not initialised: `0` for numbers, `false` for boolean, `'\u0000'` for char, `null` for references. (Local variables get no default and must be assigned before use.)
- Can have any access modifier; usually `private` (encapsulation).
- Accessed through an object reference (`a.balance`) or `this.balance` inside the class.
- Cannot be used directly from a static method, because a static method has no `this` object.

| | Instance variable | Static (class) variable | Local variable |
|---|---|---|---|
| Declared | In class, no `static` | In class, with `static` | Inside a method/block |
| Copies | One per object | One per class | One per method call |
| Memory | Heap (inside object) | Method area / metaspace | Stack |
| Default value | Yes | Yes | No |
MD,

'What are instance methods?' => <<<'MD'
## What they are
Instance methods are non-static methods that operate on a particular object. They are called on an object reference and have access to that object's instance variables through the implicit `this` reference.

```java
public class Cart {
    private final List<Double> prices = new ArrayList<>();

    public void add(double price) {          // instance method: changes this cart's state
        prices.add(price);
    }

    public double total() {                  // instance method: reads this cart's state
        return prices.stream().mapToDouble(Double::doubleValue).sum();
    }
}

Cart myCart = new Cart();
myCart.add(499);
myCart.add(1299);
System.out.println(myCart.total());   // 1798.0
```

## Key points
- Need an object: `myCart.total()`; calling `Cart.total()` does not compile.
- Can access both instance and static members of the class.
- Support overriding: a subclass can replace an instance method, and the JVM chooses the version at run time based on the actual object (dynamic dispatch). This is the basis of polymorphism.
- Behind the scenes the object is passed as a hidden parameter `this`.

## Types by purpose
- Accessors (getters): return state without changing it.
- Mutators (setters or business actions): change state, ideally with validation.
- Behaviour methods: `order.place()`, `account.withdraw(500)`.
MD,

'What are class variables?' => <<<'MD'
## What they are
Class variables are fields declared with `static`. There is exactly one copy per class, shared by all objects of that class, and it exists even if no object is created.

```java
public class Employee {
    private static int count = 0;              // class variable: shared
    public static final String COMPANY = "Infosys";  // constant
    private final int id;                       // instance variable

    public Employee() {
        count++;
        id = count;                             // each employee gets the next id
    }

    public static int getCount() { return count; }
}

new Employee(); new Employee(); new Employee();
System.out.println(Employee.getCount());   // 3
```

```text
Class Employee (metaspace/static area): count = 3, COMPANY = "Infosys"
Heap: Employee{id=1}  Employee{id=2}  Employee{id=3}
```

## When to use
- Counters and id generators shared across objects.
- Constants: `static final` (e.g. `MAX_RETRIES = 3`).
- Shared caches or configuration (with care for thread safety).

## Cautions
- Mutable static state is shared by all threads; protect it (`AtomicInteger`, synchronization).
- Access through the class name (`Employee.count`), not an object, to make it clear the value is shared.
MD,

'What is a nested class?' => <<<'MD'
## What it is
A nested class is a class declared inside another class. It groups classes that are only used together, increases encapsulation and can make code more readable.

## Four kinds
```java
public class Outer {
    private int x = 10;
    private static int y = 20;

    static class StaticNested {               // 1. static nested class
        void show() { System.out.println(y); }        // can access only static members of Outer directly
    }

    class Inner {                             // 2. inner (non-static) class
        void show() { System.out.println(x + y); }    // can access everything, including private x
    }

    void method() {
        class Local {                         // 3. local class (inside a method)
            void show() { System.out.println(x); }
        }
        new Local().show();

        Runnable r = new Runnable() {         // 4. anonymous class
            public void run() { System.out.println("anonymous " + x); }
        };
        r.run();
    }
}
```

## Creating objects
```java
Outer.StaticNested a = new Outer.StaticNested();   // no Outer object needed
Outer outer = new Outer();
Outer.Inner b = outer.new Inner();                 // needs an Outer object
```

| Kind | Needs outer object | Access to outer instance fields |
|---|---|---|
| Static nested | No | No (only via an explicit reference) |
| Inner | Yes | Yes |
| Local | Yes (in instance methods) | Yes, plus effectively final locals |
| Anonymous | Same as local | Same as local |

Real use: `Map.Entry` is a nested interface of `Map`; a `Builder` is usually a static nested class (`Pizza.Builder`).
MD,

'What is an inner class?' => <<<'MD'
## What it is
An inner class is a non-static nested class. Each inner-class object is tied to an object of the outer class and can access all its members, including private ones.

```java
public class Bank {
    private final String name = "SBI";
    private double interestRate = 6.5;

    public class Account {                        // inner class
        private double balance;

        public Account(double balance) { this.balance = balance; }

        public double yearlyInterest() {
            return balance * interestRate / 100;  // uses the outer object's private field
        }

        public String bankName() {
            return Bank.this.name;                // explicit reference to the outer object
        }
    }
}

Bank sbi = new Bank();
Bank.Account acc = sbi.new Account(100000);
System.out.println(acc.yearlyInterest());   // 6500.0
```

## How it works
The compiler adds a hidden field in the inner object pointing to its outer object (`Bank.this`). That is why an outer object is required to create it.

## Points to remember
- Before Java 16 an inner class could not declare static members (except constants); since Java 16 it can.
- Because it holds a reference to the outer object, an inner object keeps the outer object alive. In Android/long-lived code this can cause memory leaks; prefer a static nested class if the outer instance is not needed.
- Use when the inner object logically cannot exist without the outer one, e.g. an iterator over a custom collection.
MD,

'What is an anonymous class?' => <<<'MD'
## What it is
An anonymous class is a class without a name, declared and instantiated in a single expression. It either extends a class or implements an interface, and is used for one-off implementations.

```java
interface Discount {
    double apply(double price);
}

Discount festive = new Discount() {          // anonymous class implementing Discount
    @Override
    public double apply(double price) {
        return price * 0.8;                  // 20% off
    }
};
System.out.println(festive.apply(1000));     // 800.0
```

## Extending a class anonymously
```java
Thread t = new Thread() {
    @Override public void run() { System.out.println("Running in " + getName()); }
};
t.start();
```

## Anonymous class vs lambda
Since Java 8, functional interfaces (one abstract method) are usually written as lambdas:
```java
Discount festive = price -> price * 0.8;
Comparator<String> byLength = (a, b) -> a.length() - b.length();
```

| Anonymous class | Lambda |
|---|---|
| Can implement interfaces with many methods or extend classes | Only functional interfaces |
| Can have fields and state | No own state |
| `this` refers to the anonymous object | `this` refers to the enclosing object |
| Generates a separate .class file (`Outer$1.class`) | Uses invokedynamic, no extra class file |

## Rules
- Cannot have a constructor (no name), but can use an instance initializer block.
- Can access effectively final local variables of the enclosing method.
MD,

'Can a top-level class be private?' => <<<'MD'
## Answer
No. A top-level class (one not nested inside another class) can only be `public` or package-private (no modifier). `private` and `protected` give a compile error:

```java
private class Helper { }    // error: modifier private not allowed here
```

## Why
`private` means "visible only inside the enclosing class". A top-level class has no enclosing class, so private would make it usable by nobody, which is meaningless. `protected` relates to subclasses of an enclosing class, which also does not apply.

## What you can do instead
```java
// Payment.java
public class Payment {
    private static class Validator { }      // nested classes CAN be private
}

class InternalHelper { }                    // package-private: visible only inside the same package
```

| Modifier | Top-level class | Nested class |
|---|---|---|
| public | Yes | Yes |
| (default) package-private | Yes | Yes |
| protected | No | Yes |
| private | No | Yes |
MD,

'What is an abstract class?' => <<<'MD'
## What it is
An abstract class is a class declared with `abstract` that cannot be instantiated. It is meant to be extended. It can contain abstract methods (no body) that subclasses must implement, as well as normal methods, fields and constructors shared by all subclasses.

## Example
```java
public abstract class Payment {
    protected final double amount;

    protected Payment(double amount) { this.amount = amount; }

    public abstract boolean pay();                 // each payment type implements this

    public void printReceipt() {                    // shared, concrete behaviour
        System.out.println("Paid ₹" + amount + " via " + getClass().getSimpleName());
    }
}

public class UpiPayment extends Payment {
    private final String upiId;
    public UpiPayment(double amount, String upiId) { super(amount); this.upiId = upiId; }

    @Override public boolean pay() {
        System.out.println("Collect request sent to " + upiId);
        return true;
    }
}

Payment p = new UpiPayment(499, "ravi@okicici");
if (p.pay()) p.printReceipt();
// new Payment(100);   // compile error: Payment is abstract
```

## Rules
- A class with at least one abstract method must be declared abstract.
- An abstract class may have zero abstract methods (just to prevent instantiation).
- It can have constructors (called through `super()` from subclasses), static methods, final methods and any fields.
- A concrete subclass must implement all inherited abstract methods, or itself be abstract.
- `abstract` cannot be combined with `final`, `private` or `static` on a method.

## When to use
Use an abstract class when related classes share state and code (a template), for example the Template Method pattern: the abstract class defines the algorithm steps and subclasses fill in specific steps. Use an interface when unrelated classes only need to share a capability.
MD,

'What is an object in Java?' => <<<'MD'
## What it is
An object is an instance of a class: a real entity in memory with its own state (field values), behaviour (methods) and identity (its unique place in memory).

Example: a `Laptop` class defines brand, RAM and price. The Dell laptop on your desk with 16 GB RAM costing ₹65,000 is one object; your friend's HP is another.

```java
public class Laptop {
    String brand;
    int ramGb;
    double price;

    void boot() { System.out.println(brand + " is starting with " + ramGb + " GB RAM"); }
}

Laptop mine = new Laptop();
mine.brand = "Dell"; mine.ramGb = 16; mine.price = 65000;
mine.boot();   // Dell is starting with 16 GB RAM
```

## Three properties of every object
| Property | Meaning | In the example |
|---|---|---|
| State | Current values of its fields | brand = Dell, ramGb = 16 |
| Behaviour | What it can do | boot() |
| Identity | Distinguishes it from other objects even with equal state | its memory location; `mine == other` compares identity |

## Every object inherits from Object
Methods available on every object: `toString()`, `equals()`, `hashCode()`, `getClass()`, `wait()/notify()/notifyAll()` and `clone()` (protected).
MD,

'How do you create an object?' => <<<'MD'
## 1. The new keyword (most common)
```java
Student s = new Student("Anu", 21);
```
Steps: the class is loaded if needed → memory is allocated on the heap → fields get default values → initializers and the constructor run → the reference is returned and stored in `s`.

## 2. Factory and static methods
```java
List<String> list = List.of("a", "b");
LocalDate today = LocalDate.now();
Integer n = Integer.valueOf(42);           // may return a cached object
```

## 3. Cloning
```java
class Point implements Cloneable {
    int x, y;
    @Override public Point clone() throws CloneNotSupportedException { return (Point) super.clone(); }
}
Point copy = original.clone();    // new object, constructor not called
```

## 4. Reflection
```java
Student s = Student.class.getDeclaredConstructor(String.class, int.class).newInstance("Anu", 21);
```
Frameworks such as Spring and Hibernate create your objects this way.

## 5. Deserialization
```java
try (ObjectInputStream in = new ObjectInputStream(new FileInputStream("student.ser"))) {
    Student s = (Student) in.readObject();   // constructor of Student not called
}
```
JSON libraries (Jackson) also create objects from text.

## 6. Builder pattern
```java
Order order = Order.builder().id(101).customer("Ravi").amount(2499).build();
```

| Way | Constructor called? |
|---|---|
| new, factory method, reflection, builder | Yes |
| clone() | No |
| Deserialization | No (for the Serializable class itself) |
MD,

'Where is an object stored in Java memory?' => <<<'MD'
## Short answer
Objects live on the heap. References (variables that point to objects) that are local variables live on the stack of the thread executing the method.

```java
public void createOrder() {
    int qty = 2;                              // primitive local → stack
    Order order = new Order("Phone", qty);    // reference 'order' → stack; Order object → heap
}
```

```text
Stack (thread main)              Heap
┌───────────────────────┐        ┌────────────────────────────┐
│ createOrder() frame   │        │ Order@5e2d                  │
│   qty   = 2           │        │   item = ──► String "Phone" │
│   order = ────────────┼──────► │   qty  = 2                  │
└───────────────────────┘        └────────────────────────────┘
```

## JVM memory areas
| Area | Stores | Shared? |
|---|---|---|
| Heap | All objects and arrays, including instance variables | Shared by all threads; garbage collected |
| Stack (one per thread) | Method frames: local primitives and references, return addresses | Private per thread |
| Metaspace (method area) | Class metadata, method bytecode, static variables (static field values live with the Class object) | Shared |
| PC register | Current instruction per thread | Private |
| Native method stack | Native (C/C++) calls | Private |

## Heap generations
- Young generation (Eden + two Survivor spaces): new objects; most die young and are collected quickly (minor GC).
- Old generation: objects that survived several collections.

## Escape analysis
The JIT compiler can avoid heap allocation for an object that never leaves a method (scalar replacement), but conceptually objects belong to the heap.

## Errors
- `OutOfMemoryError: Java heap space` – the heap is full.
- `StackOverflowError` – a thread's stack is exhausted, usually by infinite recursion.
MD,

'What is the difference between an object and a reference?' => <<<'MD'
## Short answer
An object is the actual data on the heap. A reference is a variable that holds the address (handle) of that object, like a TV remote that controls a TV.

```java
Car c1 = new Car("Swift");   // new Car(...) creates the object; c1 is a reference to it
Car c2 = c1;                 // copies the reference, NOT the object
c2.setModel("Dzire");
System.out.println(c1.getModel());   // Dzire — both remotes control the same TV
```

| Object | Reference |
|---|---|
| Lives on the heap | Variable on the stack (local) or inside another object (field) |
| Holds the state | Holds the address of an object, or null |
| Created with `new` | Declared with a type: `Car c1;` |
| Garbage collected when unreachable | Disappears when it goes out of scope |
| One object can have many references | One reference points to at most one object at a time |

## Java is pass-by-value
Java passes a copy of the reference to methods. The method can change the object's state through it, but reassigning the parameter does not affect the caller.

```java
static void rename(Car car) {
    car.setModel("Baleno");   // changes the shared object: visible to the caller
    car = new Car("Alto");    // only the local copy now points elsewhere
}

Car mine = new Car("Swift");
rename(mine);
System.out.println(mine.getModel());   // Baleno
```
MD,

'Can multiple references point to the same object?' => <<<'MD'
## Answer
Yes. Assigning one reference to another copies the address, so both refer to the same object. A change made through either reference is visible through the other. This is called aliasing.

```java
List<String> cart = new ArrayList<>();
List<String> sameCart = cart;           // alias
sameCart.add("Shoes");
System.out.println(cart);               // [Shoes]
System.out.println(cart == sameCart);   // true — same object
```

```text
cart ─────┐
          ├──► ArrayList ["Shoes"]
sameCart ─┘
```

## Where it matters
- Methods receive a copy of the reference, so they can modify the caller's object.
- Collections store references: adding the same object twice stores two references to one object.
- Returning an internal mutable field exposes it to outside modification:

```java
public class Order {
    private final List<String> items = new ArrayList<>();
    public List<String> getItems() { return items; }                    // caller can modify our list!
    public List<String> getItemsSafe() { return List.copyOf(items); }   // defensive copy
}
```

## Garbage collection
An object becomes eligible for garbage collection only when no live reference points to it. With two references, setting one to `null` does not free the object.

## Need an independent copy?
Create a copy: `new ArrayList<>(cart)`, a copy constructor, or a deep copy (see shallow vs deep copy).
MD,

'What happens when an object reference is null?' => <<<'MD'
## Meaning
`null` means the reference points to no object. Using it to access a field or call a method throws `NullPointerException` (NPE) at run time.

```java
String city = null;
System.out.println(city.length());    // NullPointerException
```

Since Java 14, helpful NPE messages show exactly what was null:
```text
Cannot invoke "String.length()" because "city" is null
```

## Common causes
- Fields of reference type are null by default if not initialised.
- Methods returning null (`map.get(key)` for a missing key).
- Unboxing a null wrapper: `Integer qty = null; int q = qty;` throws NPE.
- Uninitialised array elements: `String[] names = new String[3]; names[0].trim();`.

## What is allowed with null
```java
String s = null;
System.out.println(s);              // prints "null"
System.out.println("x" + s);        // "xnull"
if (s == null) { }                   // comparison is fine
s instanceof String                  // false, no exception
```

## How to avoid NPEs
```java
// 1. Check
if (customer != null && customer.getAddress() != null) { ... }

// 2. Fail fast with a clear message
this.name = Objects.requireNonNull(name, "name must not be null");

// 3. Optional for values that may be absent
Optional<User> user = repository.findByEmail(email);
String display = user.map(User::getName).orElse("Guest");

// 4. Put the known non-null value first
"ADMIN".equals(role);                // safe even if role is null

// 5. Return empty collections instead of null
return List.of();
```
MD,

'What is object initialization?' => <<<'MD'
## What it is
Object initialization is the process of giving an object's fields their starting values when it is created. Java runs initialization in a fixed order.

## Order when you call new
1. Memory is allocated and every field gets its default value (0, false, null).
2. The constructor is invoked; its first statement calls `super(...)` (explicitly or implicitly), so the parent class initialises first.
3. Field initializers and instance initializer blocks run, top to bottom in source order.
4. The rest of the constructor body runs.

```java
class Base {
    Base() { System.out.println("1. Base constructor"); }
}

class Product extends Base {
    private String name = initName();                      // field initializer
    { System.out.println("3. instance initializer block"); }

    Product() {
        super();                                           // implicit if omitted
        System.out.println("4. Product constructor body");
    }

    private String initName() { System.out.println("2. field initializer"); return "Laptop"; }
}

new Product();
```
```text
1. Base constructor
2. field initializer
3. instance initializer block
4. Product constructor body
```

## Static initialization (once per class)
Static fields and `static { }` blocks run once, when the class is first loaded, before any object is created.

## Ways to initialise fields
- At declaration: `private int stock = 0;`
- In an initializer block (shared by all constructors).
- In the constructor (most common, especially with parameters).
- Lazily, on first use: `if (cache == null) cache = load();`

Note: never call overridable methods from a constructor. The subclass version runs before the subclass's fields are initialised and may see null values.
MD,

'What is the lifecycle of an object?' => <<<'MD'
## Stages
1. Class loading: before the first object, the class is loaded, linked and statically initialised (once).
2. Creation: `new` allocates heap memory (usually in the Eden space) and fields get default values.
3. Initialization: initializers and the constructor run; the object is now usable.
4. In use (reachable): one or more live references point to it; methods are called on it.
5. Unreachable: no live reference remains (variable out of scope, set to null, reassigned).
6. Garbage collected: the GC detects it is unreachable and reclaims its memory. Cleanup actions registered with `Cleaner` may run.

```java
public void process() {
    Invoice inv = new Invoice(101);   // 2–3 created and initialised
    inv.calculateTax();              // 4 in use
    inv = new Invoice(102);          // 5 the first Invoice is now unreachable
}                                    // 5 the second becomes unreachable when the method returns
                                     // 6 GC reclaims both at some later time
```

## Generational journey
```text
new object → Eden → (survives minor GC) → Survivor S0/S1 → (survives several GCs) → Old generation → major GC
```

## Notes
- You cannot force GC; `System.gc()` is only a hint.
- `finalize()` is deprecated (Java 9) and removed for future use: it was unpredictable and slow. Use try-with-resources for files, sockets and DB connections, and `java.lang.ref.Cleaner` as a safety net.
MD,

'What is garbage collection?' => <<<'MD'
## What it is
Garbage collection (GC) is the JVM's automatic memory management: it finds objects that are no longer reachable from the program and frees their heap memory. Programmers do not call `free()` or `delete` as in C/C++.

## How the JVM decides what is garbage
GC starts from GC roots and marks everything reachable. Anything not marked is garbage.
- GC roots: local variables on thread stacks, static fields, active threads, JNI references.

```java
Customer a = new Customer("A");
Customer b = new Customer("B");
a = b;      // the object "A" is now unreachable → eligible for GC
b = null;   // "B" is still reachable through a
```
Circular references are not a problem: two objects pointing to each other but unreachable from roots are both collected.

## Basic algorithm: mark, sweep, compact
1. Mark: traverse from roots and mark live objects.
2. Sweep: reclaim memory of unmarked objects.
3. Compact: move live objects together to avoid fragmentation.

## Generational collection
Most objects die young, so the heap is split:
- Young generation (Eden, Survivor spaces): minor GCs are frequent and fast.
- Old generation: long-lived objects; major/full GCs are less frequent and more expensive.

## Collectors
| Collector | Characteristics | Typical use |
|---|---|---|
| Serial | Single thread, stop-the-world | Small apps, containers with 1 CPU |
| Parallel | Many threads, maximises throughput | Batch jobs |
| G1 (default since Java 9) | Region-based, predictable pause targets (`-XX:MaxGCPauseMillis`) | Most server applications |
| ZGC / Shenandoah | Mostly concurrent, pauses of a few milliseconds even on huge heaps | Low-latency services |

## Memory leaks still happen
An object that is still referenced is never collected, e.g. a static `Map` cache that only grows, unremoved listeners, or ThreadLocals in thread pools. Diagnose with heap dumps (`jmap`, VisualVM, Eclipse MAT).

## Useful flags
```text
-Xms512m -Xmx2g            initial and maximum heap
-XX:+UseG1GC               choose collector
-Xlog:gc*                  GC logging (Java 9+)
```
MD,

'What is shallow copy?' => <<<'MD'
## What it is
A shallow copy creates a new object and copies the field values of the original. For primitive fields that copies the values; for reference fields it copies only the references, so the original and the copy share the same nested objects.

```java
class Address { String city; Address(String c) { city = c; } }

class Employee implements Cloneable {
    String name;
    Address address;

    Employee(String name, Address address) { this.name = name; this.address = address; }

    @Override
    protected Employee clone() throws CloneNotSupportedException {
        return (Employee) super.clone();      // Object.clone() is a shallow copy
    }
}

Employee e1 = new Employee("Asha", new Address("Pune"));
Employee e2 = e1.clone();
e2.address.city = "Noida";
System.out.println(e1.address.city);    // Noida — the Address object is shared!
System.out.println(e1 == e2);           // false — different Employee objects
System.out.println(e1.address == e2.address);  // true
```

```text
e1 ──► Employee{name, address ─┐
                               ├──► Address{city="Noida"}
e2 ──► Employee{name, address ─┘
```

## Other shallow copies
```java
List<Employee> copy = new ArrayList<>(original);  // new list, same Employee objects
int[] arr2 = arr1.clone();                          // fine for primitives
Employee[] staff2 = staff.clone();                  // array copied, elements shared
```

## When shallow copy is fine
- All fields are primitives or immutable objects (String, Integer, LocalDate).
- Sharing nested objects is intentional.
Otherwise use a deep copy.
MD,

'What is deep copy?' => <<<'MD'
## What it is
A deep copy creates a new object and also recursively copies every mutable object it refers to, so the copy is fully independent of the original.

## 1. Copy constructors (preferred)
```java
class Address {
    String city;
    Address(String city) { this.city = city; }
    Address(Address other) { this.city = other.city; }            // copy constructor
}

class Employee {
    String name;
    Address address;
    List<String> skills;

    Employee(Employee other) {
        this.name = other.name;                                   // String is immutable: sharing is safe
        this.address = new Address(other.address);                // new nested object
        this.skills = new ArrayList<>(other.skills);              // new list
    }
}

Employee e2 = new Employee(e1);
e2.address.city = "Noida";      // e1's address is unchanged
```

## 2. Deep clone()
```java
@Override
protected Employee clone() throws CloneNotSupportedException {
    Employee copy = (Employee) super.clone();
    copy.address = new Address(this.address);
    copy.skills = new ArrayList<>(this.skills);
    return copy;
}
```

## 3. Serialization (simple but slow)
```java
ObjectMapper mapper = new ObjectMapper();
Employee copy = mapper.readValue(mapper.writeValueAsString(e1), Employee.class);
```

## Shallow vs deep
| | Shallow copy | Deep copy |
|---|---|---|
| Top-level object | New | New |
| Nested mutable objects | Shared | Copied |
| Speed and memory | Fast, little memory | Slower, more memory |
| Independence | Changes to nested objects affect both | Fully independent |

Note: immutable objects (String, records with immutable fields, `List.of`) never need copying. Making classes immutable is often simpler than deep copying.
MD,

'What is the difference between == and equals()?' => <<<'MD'
## Short answer
- `==` compares references: are these the same object in memory? For primitives it compares values.
- `equals()` compares logical content, as defined by the class. The default `Object.equals()` behaves like `==`; classes such as String, Integer and List override it to compare content.

```java
String a = new String("java");
String b = new String("java");
String c = a;

System.out.println(a == b);        // false — two different objects
System.out.println(a.equals(b));   // true  — same characters
System.out.println(a == c);        // true  — same object

String x = "java";
String y = "java";
System.out.println(x == y);        // true — both literals come from the String pool (don't rely on this)
```

## Wrapper caching trap
```java
Integer i1 = 127, i2 = 127;
System.out.println(i1 == i2);      // true  (cached -128..127)
Integer j1 = 128, j2 = 128;
System.out.println(j1 == j2);      // false (different objects)
System.out.println(j1.equals(j2)); // true
```

## Overriding equals in your class
```java
public class Employee {
    private final String empId;
    private final String name;

    @Override
    public boolean equals(Object o) {
        if (this == o) return true;
        if (!(o instanceof Employee other)) return false;
        return empId.equals(other.empId);
    }

    @Override
    public int hashCode() { return Objects.hash(empId); }
}
```

## The equals–hashCode contract
- If `a.equals(b)` is true, `a.hashCode() == b.hashCode()` must also be true.
- Override both together, or HashMap/HashSet will not find your objects.
- equals must be reflexive, symmetric, transitive, consistent, and `x.equals(null)` must return false.

Rule: use `==` for primitives, enums and identity checks; use `equals()` (or `Objects.equals(a, b)` for null safety) for content.
MD,

];
