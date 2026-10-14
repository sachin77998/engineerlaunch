<?php

// Java › Arrays and Strings, Collections and Stream API, in depth.
return [

'How are arrays created and initialized?' => <<<'MD'
## What an array is
An array is a fixed-size, ordered container of elements of one type, stored in contiguous memory. Arrays are objects in Java: they live on the heap and have a `length` field. Indexing starts at 0, and access by index is O(1).

## Creating arrays
```java
int[] marks = new int[5];                 // {0, 0, 0, 0, 0} default values
String[] cities = new String[3];          // {null, null, null}
int[] primes = {2, 3, 5, 7, 11};          // declare + initialise
double[] salaries = new double[]{45000.0, 52000.5};
```
Default values: `0` for numbers, `false` for boolean, `'\u0000'` for char, `null` for objects.

## Reading and looping
```java
System.out.println(primes[0]);            // 2
System.out.println(primes.length);        // 5 (a field, not a method)

for (int i = 0; i < primes.length; i++) System.out.print(primes[i] + " ");
for (int p : primes) System.out.print(p + " ");           // enhanced for
```
`primes[5]` throws `ArrayIndexOutOfBoundsException`.

## Useful java.util.Arrays methods
```java
int[] a = {5, 2, 9, 1};
Arrays.sort(a);                            // [1, 2, 5, 9]  dual-pivot quicksort for primitives
int pos = Arrays.binarySearch(a, 5);       // 2 (array must be sorted)
int[] copy = Arrays.copyOf(a, 6);          // [1, 2, 5, 9, 0, 0]
Arrays.fill(a, -1);                        // all -1
System.out.println(Arrays.toString(copy)); // printing an array directly shows [I@1b6d3586
boolean same = Arrays.equals(a, copy);
```

## Arrays are references
```java
int[] x = {1, 2, 3};
int[] y = x;      // same array
y[0] = 99;        // x[0] is also 99
int[] z = x.clone();  // independent copy (shallow for object arrays)
```

## Reading an array from input
```java
Scanner sc = new Scanner(System.in);
int n = sc.nextInt();
int[] arr = new int[n];
for (int i = 0; i < n; i++) arr[i] = sc.nextInt();
```

## Summary
Arrays have fixed size, O(1) index access, default values and a `length` field; use `java.util.Arrays` for sorting, searching, copying and printing.
MD,

'What is the difference between an array and ArrayList?' => <<<'MD'
## Comparison
| | Array | ArrayList |
|---|---|---|
| Size | fixed when created | grows automatically |
| Element types | primitives and objects | objects only (`Integer`, not `int`; autoboxing) |
| Length | `arr.length` | `list.size()` |
| Access | `arr[i]` | `list.get(i)` |
| Add/remove | not possible (create a new array) | `add`, `remove`, `addAll` |
| Generics / type safety | built in | `ArrayList<String>` |
| Utility methods | `Arrays.*` | `Collections.*`, streams, `contains`, `indexOf` |
| Performance | slightly faster, less memory | small overhead, boxing for numbers |

## How ArrayList grows
Internally an `ArrayList` holds an `Object[]`. When it is full, it creates a new array about **1.5 times** larger and copies the elements. So `add` is **amortised O(1)**; inserting or removing in the middle is O(n) because elements shift.

```java
List<String> skills = new ArrayList<>();
skills.add("Java");
skills.add("Spring Boot");
skills.add(0, "SQL");          // shifts others right
skills.remove("Java");
System.out.println(skills);    // [SQL, Spring Boot]

List<Integer> big = new ArrayList<>(10_000);   // pre-size to avoid repeated resizing
```

## Converting
```java
String[] arr = {"a", "b"};
List<String> fixed = Arrays.asList(arr);          // fixed-size view: add() throws
List<String> list  = new ArrayList<>(Arrays.asList(arr));   // modifiable
List<String> immutable = List.of("a", "b");       // Java 9+, unmodifiable
String[] back = list.toArray(new String[0]);
int[] nums = list2.stream().mapToInt(Integer::intValue).toArray();
```

## Common pitfall: removing while looping
```java
for (String s : skills) if (s.isBlank()) skills.remove(s);   // ConcurrentModificationException
skills.removeIf(String::isBlank);                             // correct
```

## When to use which
- Array: fixed size known in advance, primitives, performance-critical code (DSA problems).
- ArrayList: everyday business code where the number of items changes.

## Summary
Arrays are fixed-size and support primitives; `ArrayList` is a resizable, generic list backed by an array, with amortised O(1) appends and O(n) middle inserts.
MD,

'How do multidimensional arrays work?' => <<<'MD'
## Concept
Java has no true matrix type: a 2D array is an **array of arrays**. Each row is a separate array object, so rows can even have different lengths (jagged arrays).

```java
int[][] grid = new int[3][4];          // 3 rows, 4 columns, all 0
grid[1][2] = 7;
System.out.println(grid.length);       // 3 (rows)
System.out.println(grid[0].length);    // 4 (columns)

int[][] marks = {
    {78, 85, 90},
    {66, 72, 81},
};
```

## Looping
```java
for (int r = 0; r < marks.length; r++) {
    for (int c = 0; c < marks[r].length; c++) {
        System.out.print(marks[r][c] + " ");
    }
    System.out.println();
}
for (int[] row : marks) System.out.println(Arrays.toString(row));
System.out.println(Arrays.deepToString(marks));    // [[78, 85, 90], [66, 72, 81]]
```

## Jagged arrays
```java
int[][] triangle = new int[4][];
for (int i = 0; i < 4; i++) {
    triangle[i] = new int[i + 1];
    triangle[i][0] = triangle[i][i] = 1;
    for (int j = 1; j < i; j++) triangle[i][j] = triangle[i - 1][j - 1] + triangle[i - 1][j];
}
// Pascal's triangle: [1] [1,1] [1,2,1] [1,3,3,1]
```

## Example: matrix multiplication
```java
static int[][] multiply(int[][] a, int[][] b) {
    int n = a.length, m = b[0].length, k = b.length;
    int[][] c = new int[n][m];
    for (int i = 0; i < n; i++)
        for (int j = 0; j < m; j++)
            for (int x = 0; x < k; x++)
                c[i][j] += a[i][x] * b[x][j];
    return c;
}
```
Time complexity O(n·m·k). Accessing row by row (`c[i][j]` with `j` in the inner loop) is cache-friendly because each row is contiguous.

## Copying
`grid.clone()` copies only the outer array; rows are still shared. Deep copy:
```java
int[][] copy = new int[grid.length][];
for (int i = 0; i < grid.length; i++) copy[i] = grid[i].clone();
```

## Summary
A 2D array is an array of row arrays; use `arr.length` for rows and `arr[i].length` for columns, `Arrays.deepToString` for printing, and copy each row for a deep copy.
MD,

'Why is String immutable?' => <<<'MD'
## What immutable means
Once a `String` object is created its characters can never change. Methods that look like they modify it (`toUpperCase`, `replace`, `concat`, `substring`) return a **new** String.

```java
String s = "java";
s.toUpperCase();            // result discarded
System.out.println(s);      // java
s = s.toUpperCase();        // s now points to a new object "JAVA"
```
The class is `final`, its internal `byte[] value` is `private final`, and no method changes it.

## Why Java designed it this way
1. **String pool:** literals are shared. `String a = "hi"; String b = "hi";` point to one object. If strings were mutable, changing `a` would silently change `b`.
2. **Security:** strings hold file paths, URLs, database usernames and class names. After validation (`if (path.startsWith("/safe"))`), nobody can change the value before it is used.
3. **Thread safety:** immutable objects can be shared between threads without synchronisation.
4. **Hash caching:** `hashCode()` is computed once and cached, which makes `String` an efficient and reliable `HashMap` key. A key whose hash changed after insertion could never be found again.
5. **Class loading:** class names passed as strings cannot be altered.

## Cost: creating many objects
```java
String result = "";
for (int i = 0; i < 10000; i++) result += i;   // creates ~10,000 temporary Strings: O(n^2)

StringBuilder sb = new StringBuilder();
for (int i = 0; i < 10000; i++) sb.append(i); // one growing buffer: O(n)
String result2 = sb.toString();
```
(The compiler optimises `a + b + c` in a single expression, but not concatenation inside loops.)

## Passwords
Because a String stays in memory until garbage-collected and cannot be wiped, APIs use `char[]` for passwords (e.g. `JPasswordField.getPassword()`), which can be overwritten with zeros after use.

## Summary
String is immutable for pooling, security, thread safety and hash caching; use `StringBuilder` when building strings in loops.
MD,

'What is the String pool?' => <<<'MD'
## What it is
The String pool (string constant pool, or intern pool) is a special area in the heap where Java keeps **one shared copy of each distinct string literal**. Since Java 7 it lives in the main heap (earlier in PermGen), so it is garbage-collected like other objects.

```java
String a = "Pune";
String b = "Pune";
String c = new String("Pune");

System.out.println(a == b);        // true: same pooled object
System.out.println(a == c);        // false: new String creates a separate heap object
System.out.println(a.equals(c));   // true: same characters
System.out.println(a == c.intern()); // true: intern() returns the pooled copy
```

## How it works
- When the class loads, literals like `"Pune"` are added to the pool if not already there.
- `new String("Pune")` always creates a new object on the heap (and the literal `"Pune"` is in the pool too), so up to **two objects** may exist.
- `intern()` looks up the pool: returns the existing pooled string, or adds this one.
- Compile-time constants are pooled: `"Pu" + "ne" == "Pune"` is `true`; but `s1 + s2` with variables at runtime creates a new object (unless both are `final` constants).

## Why it exists
Programs repeat the same strings (status codes, city names, JSON keys). Sharing one copy saves memory, and immutability makes sharing safe.

## Interview takeaways
- Always compare strings with `equals()` (or `equalsIgnoreCase()`), never `==`. `==` compares references and only works by accident for pooled literals.
- `"constant".equals(variable)` avoids `NullPointerException` when the variable is null; or use `Objects.equals(a, b)`.
- Interning large numbers of runtime strings can save memory (e.g. repeated values parsed from a big file), but the JVM's G1 GC also offers `-XX:+UseStringDeduplication`.

## Summary
The String pool stores one shared instance per literal; `new String` bypasses it, `intern()` uses it, and `equals()` is the correct way to compare contents.
MD,

'What is the difference between String, StringBuilder and StringBuffer?' => <<<'MD'
## Comparison
| | String | StringBuilder | StringBuffer |
|---|---|---|---|
| Mutable | no | yes | yes |
| Thread-safe | yes (immutable) | no | yes (synchronized methods) |
| Speed for repeated changes | slow (new object each time) | fastest | slower than StringBuilder |
| Introduced | Java 1.0 | Java 5 | Java 1.0 |
| Use for | fixed text, keys, constants | building text in one thread | legacy code shared across threads |

## StringBuilder in action
```java
StringBuilder sb = new StringBuilder("Java");
sb.append(" Developer")          // Java Developer
  .insert(0, "Senior ")          // Senior Java Developer
  .replace(0, 6, "Lead")         // Lead Java Developer
  .reverse();                    // repoleveD avaJ daeL
sb.reverse();
sb.setCharAt(0, 'l');
sb.deleteCharAt(sb.length() - 1);
System.out.println(sb.length() + " " + sb);
String result = sb.toString();
```
Internally it keeps a resizable character buffer (default capacity 16, grows to `(old * 2) + 2`). Give a capacity if you know the size: `new StringBuilder(1000)`.

## Performance example
```java
// Building a CSV of 1 lakh rows
StringBuilder csv = new StringBuilder();
for (Job j : jobs) {
    csv.append(j.getId()).append(',').append(j.getTitle()).append('\n');
}
```
Using `String +=` here would copy the entire growing text on each iteration (O(n^2)).

## StringBuffer today
Its methods are `synchronized`, so each call takes a lock even in single-threaded code. Sharing one mutable buffer between threads is rare; usually each thread builds its own `StringBuilder`. Use `StringBuffer` only when an old API requires it.

## Other options
- `String.join(", ", list)` and `Collectors.joining(", ")` for joining.
- `String.format` / `"%s".formatted(x)` for templates.
- Text blocks (`"""`, Java 15) for multi-line literals.

## Summary
Use String for values that do not change, StringBuilder for building or modifying text (almost always), and StringBuffer only when a mutable buffer really must be shared between threads.
MD,

'How do equals() and compareTo() differ?' => <<<'MD'
## equals()
Answers **"are these two objects equal in content?"** and returns `boolean`. It is defined in `Object` (default: same reference, like `==`) and overridden by `String`, wrappers, collections and your own classes.

```java
"Java".equals("Java");             // true
"Java".equals("java");             // false
"Java".equalsIgnoreCase("java");   // true
```

## compareTo()
Answers **"how do they order?"** and returns an `int`: negative if this comes before the other, 0 if equal in ordering, positive if after. It comes from the `Comparable<T>` interface and defines the **natural order** used by `Collections.sort`, `TreeMap` and `TreeSet`.

```java
"apple".compareTo("banana");   // negative ('a' < 'b')
"b".compareTo("a");            // positive
"Java".compareTo("Java");      // 0
"Java".compareTo("java");      // negative: 'J' (74) < 'j' (106)
"Java".compareToIgnoreCase("java");   // 0
```
For strings, it compares character by character (Unicode values); if one string is a prefix of the other, the shorter one is smaller (`"app".compareTo("apple")` = -2, the length difference).

## Consistency between them
It is strongly recommended that `a.compareTo(b) == 0` exactly when `a.equals(b)`. `BigDecimal` is a known exception: `new BigDecimal("2.0").equals(new BigDecimal("2.00"))` is false (different scale) but `compareTo` returns 0. In a `HashSet` both values are kept; in a `TreeSet` only one.

## Implementing both in your class
```java
public class Employee implements Comparable<Employee> {
    private final String empId;
    private final double salary;

    @Override public boolean equals(Object o) {
        if (this == o) return true;
        if (!(o instanceof Employee e)) return false;
        return empId.equals(e.empId);
    }
    @Override public int hashCode() { return empId.hashCode(); }   // always override with equals

    @Override public int compareTo(Employee other) {
        return Double.compare(this.salary, other.salary);          // never subtract doubles
    }
}
```

## Summary
`equals()` checks equality and returns boolean; `compareTo()` defines ordering and returns negative, zero or positive. Keep them consistent, and always override `hashCode()` with `equals()`.
MD,

'What is the Java Collections Framework?' => <<<'MD'
## What it is
The Java Collections Framework (JCF, `java.util`) is a unified set of **interfaces**, **implementations** and **algorithms** for storing and processing groups of objects. Code is written against interfaces (`List`, `Map`) so implementations can be swapped without changing the rest of the program.

## Hierarchy
```text
Iterable
 └─ Collection
     ├─ List      (ordered, duplicates, index access)   ArrayList, LinkedList, CopyOnWriteArrayList
     ├─ Set       (no duplicates)                        HashSet, LinkedHashSet, TreeSet
     └─ Queue     (processing order)                     ArrayDeque, PriorityQueue, LinkedList
         └─ Deque (both ends)                            ArrayDeque, LinkedList

Map (key → value, not a Collection)                       HashMap, LinkedHashMap, TreeMap, ConcurrentHashMap
```

## Choosing an implementation
| Need | Use | Key operations |
|---|---|---|
| list with fast index access | `ArrayList` | get O(1), add at end amortised O(1) |
| unique items, fast lookup | `HashSet` | add/contains O(1) average |
| unique items, insertion order | `LinkedHashSet` | O(1) |
| sorted unique items | `TreeSet` | O(log n), red-black tree |
| key-value lookup | `HashMap` | get/put O(1) average |
| sorted keys, range queries | `TreeMap` | O(log n), `floorKey`, `subMap` |
| LRU cache | `LinkedHashMap` with access order | O(1) |
| stack or queue | `ArrayDeque` | push/pop/offer/poll O(1) |
| always take the smallest/highest priority | `PriorityQueue` | offer/poll O(log n) |
| shared between threads | `ConcurrentHashMap`, `CopyOnWriteArrayList`, `BlockingQueue` | |

## Algorithms in Collections
```java
List<Integer> scores = new ArrayList<>(List.of(70, 95, 82));
Collections.sort(scores);
Collections.reverse(scores);
Collections.max(scores);
Collections.unmodifiableList(scores);
Collections.frequency(scores, 82);
```

## Why it matters
- Program to interfaces: `List<Job> jobs = new ArrayList<>();`.
- Generics give compile-time type safety.
- Iterators are **fail-fast**: modifying a collection while iterating throws `ConcurrentModificationException` (use `Iterator.remove` or `removeIf`).

## Summary
The JCF provides interfaces (List, Set, Queue, Map), ready implementations with known complexity, and utility algorithms; choose the implementation from your access pattern.
MD,

'What is the difference between List, Set and Map?' => <<<'MD'
## Comparison
| | List | Set | Map |
|---|---|---|---|
| Stores | elements | unique elements | key → value pairs |
| Duplicates | allowed | not allowed | keys unique, values may repeat |
| Order | insertion order, index-based | depends: Hash (none), Linked (insertion), Tree (sorted) | same as Set for keys |
| Access | `get(index)` | `contains(x)` | `get(key)` |
| Null | allowed | HashSet: one null; TreeSet: none | HashMap: one null key; TreeMap: no null key |
| Main classes | ArrayList, LinkedList | HashSet, LinkedHashSet, TreeSet | HashMap, LinkedHashMap, TreeMap |
| Extends Collection | yes | yes | no |

## Examples
```java
List<String> applied = new ArrayList<>(List.of("TCS", "Infosys", "TCS"));
System.out.println(applied);          // [TCS, Infosys, TCS] duplicates kept
System.out.println(applied.get(1));   // Infosys

Set<String> companies = new HashSet<>(applied);
System.out.println(companies);        // [Infosys, TCS] duplicates removed
System.out.println(companies.contains("TCS"));   // true, O(1)

Map<String, Integer> applicationsPerCompany = new HashMap<>();
for (String c : applied) applicationsPerCompany.merge(c, 1, Integer::sum);
System.out.println(applicationsPerCompany);      // {TCS=2, Infosys=1}
System.out.println(applicationsPerCompany.getOrDefault("Wipro", 0));   // 0
```

## Iterating a Map
```java
for (Map.Entry<String, Integer> e : applicationsPerCompany.entrySet()) {
    System.out.println(e.getKey() + " -> " + e.getValue());
}
applicationsPerCompany.forEach((k, v) -> System.out.println(k + "=" + v));
```

## How a Set guarantees uniqueness
`HashSet` is backed by a `HashMap` (elements are keys). It uses `hashCode()` then `equals()`; if you put your own objects in a Set or use them as Map keys, override both consistently.

## Choosing
- Ordered collection, duplicates allowed, positional access: **List**.
- "Have I seen this?" or remove duplicates: **Set**.
- Look something up by an identifier, count occurrences, group: **Map**.

## Summary
List keeps an ordered sequence with duplicates, Set keeps unique elements, Map associates unique keys with values; pick by whether you need order, uniqueness or lookup by key.
MD,

'ArrayList versus LinkedList?' => <<<'MD'
## Internal structure
- **ArrayList:** a resizable array. Elements sit next to each other in memory.
- **LinkedList:** a doubly linked list. Each element is a node object with `prev` and `next` pointers. It implements both `List` and `Deque`.

## Complexity
| Operation | ArrayList | LinkedList |
|---|---|---|
| `get(i)` | O(1) | O(n) (walks from the nearer end) |
| add at end | amortised O(1) | O(1) |
| add/remove at start | O(n) (shifts) | O(1) |
| add/remove in middle (by index) | O(n) shift | O(n) to find + O(1) relink |
| remove via iterator at current position | O(n) | O(1) |
| memory per element | reference only | node: reference + 2 pointers + object header (~3–4× more) |

## Reality in benchmarks
ArrayList is faster in almost all real programs, even for many middle inserts of moderate size: copying a contiguous array (`System.arraycopy`) is very fast and CPU-cache-friendly, while walking linked nodes scattered across the heap causes cache misses.

```java
List<Integer> a = new ArrayList<>();
List<Integer> l = new LinkedList<>();
for (int i = 0; i < 1_000_000; i++) { a.add(i); l.add(i); }
a.get(500_000);   // instant
l.get(500_000);   // walks 500,000 nodes
```
A classic mistake: `for (int i = 0; i < linked.size(); i++) linked.get(i)` is O(n²).

## When LinkedList makes sense
- You need a queue or deque and many removals from both ends; but `ArrayDeque` is usually better still.
- You remove elements while iterating with a `ListIterator` over very large lists.

## Summary
ArrayList gives O(1) random access and is the default choice; LinkedList offers O(1) insertions at the ends or at an iterator position but has O(n) access and higher memory use, so it is rarely the better option.
MD,

'HashMap versus ConcurrentHashMap?' => <<<'MD'
## HashMap internals
- An array of **buckets**; `index = hash(key) & (n - 1)`.
- Collisions go into a linked list in the bucket; since Java 8 a bucket with more than 8 entries (and table size ≥ 64) becomes a **red-black tree**, so worst-case lookup is O(log n) instead of O(n).
- Default capacity 16, **load factor 0.75**: when size exceeds capacity × 0.75 the table doubles and entries are rehashed.
- Allows one `null` key and `null` values.
- **Not thread-safe:** concurrent `put`s can lose updates, and resizing during concurrent access can corrupt the structure.

## ConcurrentHashMap internals (Java 8+)
- Same bucket array, but writes lock only **one bucket** (CAS for an empty bucket, `synchronized` on the first node otherwise). Many threads can write to different buckets at once.
- Reads take **no lock** (volatile reads).
- Resizing is done cooperatively by several threads.
- **No null keys or values**: `get` returning null must mean "absent", not "mapped to null".
- Iterators are **weakly consistent**: no `ConcurrentModificationException`; they may or may not show concurrent updates.

## Comparison
| | HashMap | ConcurrentHashMap | Collections.synchronizedMap / Hashtable |
|---|---|---|---|
| Thread-safe | no | yes | yes |
| Locking | none | per bucket, lock-free reads | whole map for every call |
| Null keys/values | allowed | not allowed | synchronizedMap allows, Hashtable not |
| Scalability | best single-threaded | high | poor under contention |

## Atomic compound operations
```java
ConcurrentHashMap<String, Integer> views = new ConcurrentHashMap<>();

// Wrong even on ConcurrentHashMap: check-then-act race
if (!views.containsKey(jobId)) views.put(jobId, 0);
views.put(jobId, views.get(jobId) + 1);

// Correct: atomic per key
views.merge(jobId, 1, Integer::sum);
views.computeIfAbsent(city, c -> new ConcurrentLinkedQueue<>()).add(job);
```
For high-contention counters, `ConcurrentHashMap<String, LongAdder>` is faster.

## Summary
Use HashMap in single-threaded code; use ConcurrentHashMap when threads share a map, and use its atomic methods (`merge`, `compute`, `putIfAbsent`) instead of check-then-act.
MD,

'Comparable versus Comparator?' => <<<'MD'
## Comparable: natural order inside the class
```java
public class Job implements Comparable<Job> {
    String title; int salary; LocalDate postedOn;

    @Override
    public int compareTo(Job other) {
        return Integer.compare(this.salary, other.salary);   // natural order: by salary
    }
}
Collections.sort(jobs);          // uses compareTo
new TreeSet<Job>(jobs);          // also uses compareTo
```
One natural order per class, written in the class itself (`java.lang.Comparable`, method `compareTo(T)`).

## Comparator: any order, outside the class
```java
Comparator<Job> byNewest = Comparator.comparing(Job::getPostedOn).reversed();
Comparator<Job> byTitleThenSalary = Comparator.comparing(Job::getTitle)
        .thenComparing(Job::getSalary, Comparator.reverseOrder());

jobs.sort(byNewest);
jobs.sort(byTitleThenSalary);
jobs.sort(Comparator.comparing(Job::getCity, Comparator.nullsLast(Comparator.naturalOrder())));
```
`java.util.Comparator`, method `compare(T a, T b)`. Create as many orderings as you need, even for classes you cannot modify (e.g. third-party types).

## Comparison
| | Comparable | Comparator |
|---|---|---|
| Package | `java.lang` | `java.util` |
| Method | `compareTo(other)` | `compare(a, b)` |
| Where | inside the class | separate object / lambda |
| Number of orders | one natural order | unlimited |
| Used by | `Collections.sort(list)`, TreeMap keys by default | `list.sort(cmp)`, `new TreeMap<>(cmp)`, `stream.sorted(cmp)` |

## Pitfall: subtraction
```java
return this.salary - other.salary;   // can overflow for large or negative ints
return Integer.compare(this.salary, other.salary);   // safe
```

## Summary
Comparable defines one natural ordering inside the class; Comparator defines any number of external orderings, built fluently with `comparing`, `thenComparing` and `reversed`.
MD,

'What is the Stream API?' => <<<'MD'
## What it is
The Stream API (Java 8, `java.util.stream`) processes sequences of elements in a **declarative, functional** style: you describe *what* to compute (filter, transform, group) instead of writing loops. A stream is not a data structure; it is a pipeline over a source (collection, array, file lines, generator).

```java
List<String> topJavaCompanies = jobs.stream()
        .filter(j -> j.getSkills().contains("Java"))
        .filter(j -> j.getSalary() >= 1_000_000)
        .sorted(Comparator.comparing(Job::getSalary).reversed())
        .map(Job::getCompany)
        .distinct()
        .limit(5)
        .toList();
```
The same with loops would need a temporary list, a sort, a set for uniqueness and a counter.

## Properties
- **Lazy:** intermediate operations do nothing until a terminal operation runs; elements flow one by one through the whole pipeline, and `limit` can stop early.
- **Single use:** a stream cannot be reused after a terminal operation (`IllegalStateException`).
- **Does not modify the source.**
- **Can be parallel:** `parallelStream()` splits work across the ForkJoin pool.

## Creating streams
```java
list.stream();
Arrays.stream(new int[]{1, 2, 3});           // IntStream
Stream.of("a", "b");
IntStream.rangeClosed(1, 10);
Files.lines(Path.of("jobs.csv"));            // close it (try-with-resources)
Stream.iterate(1, x -> x * 2).limit(10);
```

## Powerful collectors
```java
Map<String, List<Job>> byCity = jobs.stream().collect(Collectors.groupingBy(Job::getCity));
Map<String, Long> countByCity = jobs.stream().collect(Collectors.groupingBy(Job::getCity, Collectors.counting()));
Map<String, Double> avgSalary = jobs.stream().collect(Collectors.groupingBy(Job::getCity, Collectors.averagingInt(Job::getSalary)));
Map<Boolean, List<Job>> remoteSplit = jobs.stream().collect(Collectors.partitioningBy(Job::isRemote));
String titles = jobs.stream().map(Job::getTitle).collect(Collectors.joining(", "));
IntSummaryStatistics stats = jobs.stream().mapToInt(Job::getSalary).summaryStatistics();
```

## Summary
Streams describe data processing as lazy pipelines of intermediate operations ending in one terminal operation, producing concise, readable code for filtering, mapping, grouping and aggregating.
MD,

'What are intermediate and terminal operations?' => <<<'MD'
## Intermediate operations
Return a **new stream** and are **lazy**: they only describe a step.
| Operation | Purpose |
|---|---|
| `filter(predicate)` | keep matching elements |
| `map(fn)` / `mapToInt` | transform each element |
| `flatMap(fn)` | turn each element into a stream and flatten |
| `distinct()` | remove duplicates (uses equals/hashCode) |
| `sorted()` / `sorted(cmp)` | sort (stateful: needs all elements) |
| `limit(n)` / `skip(n)` | take or drop elements (short-circuit) |
| `peek(action)` | look at elements, for debugging |
| `takeWhile` / `dropWhile` (Java 9) | stop or start at a condition |

## Terminal operations
Trigger execution and produce a result or side effect; the stream is consumed.
| Operation | Result |
|---|---|
| `collect(...)`, `toList()` | collection or map |
| `forEach(action)` | side effect |
| `reduce(...)` | single combined value |
| `count()`, `min()`, `max()`, `sum()`, `average()` | aggregates |
| `anyMatch`, `allMatch`, `noneMatch` | boolean (short-circuit) |
| `findFirst`, `findAny` | `Optional` (short-circuit) |
| `toArray()` | array |

## Laziness demonstrated
```java
Stream<String> s = Stream.of("java", "php", "go", "kotlin")
        .filter(x -> { System.out.println("filter " + x); return x.length() > 2; })
        .map(x -> { System.out.println("map " + x); return x.toUpperCase(); });
System.out.println("nothing printed yet");
s.findFirst();
// filter java
// map java       (stops: findFirst needs only one)
```
Elements pass **vertically** through the whole pipeline one at a time, not stage by stage.

## flatMap example
```java
List<List<String>> skillsPerCandidate = List.of(List.of("Java", "SQL"), List.of("React", "Java"));
Set<String> allSkills = skillsPerCandidate.stream()
        .flatMap(List::stream)
        .collect(Collectors.toCollection(TreeSet::new));   // [Java, React, SQL]
```

## Rules
- Without a terminal operation nothing runs.
- Put `filter` before `map` and `sorted` to process fewer elements.
- Avoid side effects in intermediate operations (except `peek` for debugging).

## Summary
Intermediate operations are lazy and return streams; a single terminal operation runs the pipeline, element by element, and short-circuiting operations can stop early.
MD,

'What do map(), filter() and reduce() do?' => <<<'MD'
## filter(): keep what matches
```java
List<Job> remoteJobs = jobs.stream().filter(Job::isRemote).toList();
List<Integer> evens = nums.stream().filter(n -> n % 2 == 0).toList();
```
Takes a `Predicate<T>` (returns boolean). Output has the same type, possibly fewer elements.

## map(): transform each element
```java
List<String> titles = jobs.stream().map(Job::getTitle).toList();
List<Integer> squares = nums.stream().map(n -> n * n).toList();
int totalSalary = jobs.stream().mapToInt(Job::getSalary).sum();   // primitive stream, no boxing
```
Takes a `Function<T, R>`. Same number of elements, possibly a different type.

## reduce(): combine everything into one value
```java
int sum = nums.stream().reduce(0, Integer::sum);                 // identity + accumulator
Optional<Integer> max = nums.stream().reduce(Integer::max);      // no identity → Optional
int product = nums.stream().reduce(1, (a, b) -> a * b);

// reduce to a different type: identity, accumulator, combiner (used in parallel)
int totalChars = words.stream().reduce(0, (acc, w) -> acc + w.length(), Integer::sum);
```
- **Identity:** the starting value and neutral element (`0` for sum, `1` for product).
- **Accumulator:** combines the running result with the next element.
- The function must be **associative** so parallel streams give correct results.

## Together: total salary of senior Java jobs in Bengaluru
```java
long total = jobs.stream()
        .filter(j -> j.getCity().equals("Bengaluru"))
        .filter(j -> j.getExperience() >= 5)
        .map(Job::getSalary)
        .reduce(0L, Long::sum, Long::sum);
```

## Specialised alternatives to reduce
For common cases prefer clearer methods: `sum()`, `max()`, `count()`, `Collectors.summingInt`, `Collectors.joining`. Use `collect` (mutable reduction) to build collections; using `reduce` to build a list creates many intermediate copies.

## Summary
`filter` selects elements with a predicate, `map` transforms each element, and `reduce` folds all elements into one result using an identity and an associative accumulator.
MD,

'When should parallel streams be avoided?' => <<<'MD'
## What parallel streams do
`list.parallelStream()` or `.parallel()` splits the source into chunks processed on the **common ForkJoinPool** (threads = CPU cores − 1) and then combines the results. It can speed up CPU-heavy work on large data, but often makes code slower or wrong.

## Avoid them when
1. **The data is small.** Splitting, scheduling and merging cost more than the work. Below roughly 10,000 simple elements, sequential is usually faster.
2. **The work is I/O-bound** (HTTP calls, database queries, file reads). Threads in the common pool block, starving every other parallel stream and `CompletableFuture` in the JVM. Use a dedicated executor, virtual threads (Java 21) or async clients instead.
3. **The source splits poorly.** `ArrayList`, arrays and `IntStream.range` split well; `LinkedList`, `Stream.iterate` and `Files.lines` split badly.
4. **Operations have shared mutable state.**
```java
List<Integer> out = new ArrayList<>();
nums.parallelStream().forEach(out::add);     // race condition: lost elements or exceptions
List<Integer> safe = nums.parallelStream().toList();   // correct: let the collector combine
```
5. **Order matters and you use order-sensitive operations** (`findFirst`, `limit`, `sorted`, `forEachOrdered`): they force extra coordination. `forEach` on a parallel stream prints in unpredictable order.
6. **Inside a web server request.** Every request competing for the same small common pool hurts throughput and latency under load.
7. **Boxing-heavy pipelines:** use `mapToInt`/`IntStream` or the gain disappears.

## When they can help
- Large in-memory data (millions of elements), CPU-bound, independent work per element (for example computing scores or hashes), on a machine with spare cores, in a batch job.
- Always **measure** with JMH or realistic timings.

```java
long count = LongStream.rangeClosed(1, 50_000_000).parallel().filter(Primes::isPrime).count();
```

## Summary
Avoid parallel streams for small data, I/O, poorly splitting sources, shared mutable state, order-dependent logic and request-handling code; use them only for large, CPU-bound, independent work after measuring.
MD,

];
