<?php

// DSA › Linked List: concept lessons and problems, in depth (rendered by LearningPresentation).
return [

'What is a Singly Linked List?' => <<<'MD'
## What it is
A singly linked list is a linear data structure made of nodes. Each node contains a data field and a reference (pointer) to the next node. The first node is called the head, and the next of the last node is null, which marks the end of the list. Unlike an array, the nodes are not stored next to each other in memory; they are connected through these references, which makes insertion and deletion efficient.

```text
head -> [ 1 | • ] -> [ 2 | • ] -> [ 3 | • ] -> [ 4 | • ] -> [ 5 | • ] -> null
         data next
```

## Understanding the node structure
Each node has two parts: the data and a pointer to the next node. This lets nodes be linked together dynamically into a chain.

```java
// Definition of a node in a singly linked list
class Node {
    int data;      // the value stored in the node
    Node next;     // reference to the next node (null for the last node)

    Node(int data) {
        this.data = data;
        this.next = null;
    }
}
```

## Creating a linked list of size 3 to understand how it works
Create the first node:
- Allocate memory for the first node and store data in it.
- Mark this node as head.

Create the second node:
- Allocate memory for the second node and store data in it.
- Link the first node's next to this new node.

Create the third node:
- Allocate memory for the third node and store data in it.
- Link the second node's next to this node.
- Its next stays null, so the next of the last node is null.

```java
public class Main {
    public static void main(String[] args) {
        Node head = new Node(10);          // first node (head of the list)
        head.next = new Node(20);          // link the second node
        head.next.next = new Node(30);     // link the third node

        // Traverse and print
        Node temp = head;
        while (temp != null) {
            System.out.print(temp.data + " -> ");
            temp = temp.next;
        }
        System.out.println("null");
    }
}
```

```text
Output
10 -> 20 -> 30 -> null
```

## Common operations and their complexity
- Traversal (visit every node): O(n) time, O(1) space.
- Search for a key: O(n) – no index, so you walk from the head.
- Insert at the beginning: O(1) – new node points to the old head.
- Insert at the end: O(n) with only a head; O(1) if you keep a tail pointer.
- Insert at a given position: O(n) to reach the position, then O(1) to relink.
- Delete from the beginning: O(1) – move head to head.next.
- Delete from the end or a position: O(n) – you need the previous node.
- Access the k-th element: O(n) (arrays do this in O(1)).
- Reverse: O(n) time, O(1) space.

```java
// Traversal and search
static boolean search(Node head, int key) {
    for (Node cur = head; cur != null; cur = cur.next) {
        if (cur.data == key) return true;
    }
    return false;
}

// Length of the list
static int length(Node head) {
    int count = 0;
    for (Node cur = head; cur != null; cur = cur.next) count++;
    return count;
}
```

## Linked list versus array
- Size: an array has a fixed size (or must be copied to grow); a linked list grows one node at a time.
- Access: array[i] is O(1); the i-th node of a list is O(n).
- Insert/delete at the front: array O(n) (shift elements); list O(1).
- Memory: arrays are contiguous and cache-friendly; list nodes are scattered and each stores an extra pointer.

## Advantages
- Dynamic size: no fixed limit like arrays.
- Efficient insertion and deletion at the beginning (and at the end with a tail pointer). Insertion in the middle is also O(1) if you already have a reference to the node after which you insert.
- Used to build other data structures: stacks, queues, adjacency lists of graphs, hash-table buckets.

## Disadvantages
- Extra memory for storing a pointer in every node.
- No direct or random access; you must traverse.
- Cache-unfriendly, because nodes are not stored in contiguous memory.

## Applications
- Undo/redo history in editors and drawing tools.
- Music playlists and image viewers (next item).
- Implementing stacks, queues and graph adjacency lists.
- Polynomial arithmetic and sparse matrices.
- Memory management (free lists) in operating systems.

Note: always handle the empty list (head == null) and the single-node list; most linked-list bugs come from these edge cases.
MD,

'Insertion and Deletion in a Linked List' => <<<'MD'
## Insertion at the beginning – O(1)
1. Create the new node.
2. Point its next to the current head.
3. Make the new node the head.

```java
static Node insertAtBeginning(Node head, int value) {
    Node node = new Node(value);
    node.next = head;
    return node;                 // new head
}
```

## Insertion at the end – O(n)
1. Create the new node.
2. If the list is empty, it becomes the head.
3. Otherwise walk to the last node (whose next is null) and link it.

```java
static Node insertAtEnd(Node head, int value) {
    Node node = new Node(value);
    if (head == null) return node;
    Node cur = head;
    while (cur.next != null) cur = cur.next;
    cur.next = node;
    return head;
}
```

## Insertion at a position (1-based) – O(n)
Walk to the node before the position, then relink: new.next = prev.next; prev.next = new.

```java
static Node insertAt(Node head, int position, int value) {
    if (position == 1) return insertAtBeginning(head, value);
    Node prev = head;
    for (int i = 1; i < position - 1 && prev != null; i++) prev = prev.next;
    if (prev == null) throw new IndexOutOfBoundsException("position too large");
    Node node = new Node(value);
    node.next = prev.next;
    prev.next = node;
    return head;
}
```

## Deletion from the beginning – O(1)
```java
static Node deleteFirst(Node head) {
    return head == null ? null : head.next;   // old head is garbage-collected
}
```

## Deletion from the end – O(n)
Find the second-last node and set its next to null.

```java
static Node deleteLast(Node head) {
    if (head == null || head.next == null) return null;
    Node cur = head;
    while (cur.next.next != null) cur = cur.next;
    cur.next = null;
    return head;
}
```

## Deletion by value – O(n)
```java
static Node deleteValue(Node head, int key) {
    if (head == null) return null;
    if (head.data == key) return head.next;
    Node cur = head;
    while (cur.next != null && cur.next.data != key) cur = cur.next;
    if (cur.next != null) cur.next = cur.next.next;   // skip the node
    return head;
}
```

## Dry run
List 10 -> 20 -> 30, insertAt(position 2, value 15):
- prev stops at 10 (position 1).
- node(15).next = 20; 10.next = 15.
- Result: 10 -> 15 -> 20 -> 30.

## Complexity summary
- Insert/delete at head: O(1) time, O(1) space.
- Insert/delete at tail: O(n) time (O(1) insert with a tail pointer), O(1) space.
- Insert/delete at position or by value: O(n) time, O(1) space.

Note: a dummy (sentinel) node before the head removes the special case for position 1 and makes deletion code shorter.
MD,

'Doubly and Circular Linked Lists' => <<<'MD'
## Doubly linked list
A doubly linked list (DLL) stores two references in each node: prev to the previous node and next to the next node. This allows traversal in both directions and deletion of a known node in O(1).

```text
null <- [ • | 10 | • ] <-> [ • | 20 | • ] <-> [ • | 30 | • ] <-> [ • | 40 | • ] -> null
          prev data next
```

```java
class DNode {
    int data;
    DNode prev;    // reference to the previous node
    DNode next;    // reference to the next node

    DNode(int data) { this.data = data; }
}
```

## Creating a doubly linked list with 4 nodes
1. Create the head node; its prev and next are null.
2. Create the next node: head.next = new node; head.next.prev = head.
3. Repeat for further nodes, setting both next and prev.
4. The last node's next stays null; keep a tail pointer if you append often.

```java
public class DllDemo {
    public static void main(String[] args) {
        DNode head = new DNode(10);
        head.next = new DNode(20);           head.next.prev = head;
        head.next.next = new DNode(30);      head.next.next.prev = head.next;
        head.next.next.next = new DNode(40); head.next.next.next.prev = head.next.next;

        DNode tail = null;
        for (DNode cur = head; cur != null; cur = cur.next) {      // forward
            System.out.print(cur.data + (cur.next != null ? " <-> " : "\n"));
            tail = cur;
        }
        for (DNode cur = tail; cur != null; cur = cur.prev) {      // backward
            System.out.print(cur.data + (cur.prev != null ? " <-> " : "\n"));
        }
    }
}
```

```text
Output
10 <-> 20 <-> 30 <-> 40
40 <-> 30 <-> 20 <-> 10
```

## Deleting a given node in O(1)
```java
static DNode delete(DNode head, DNode node) {
    if (node.prev != null) node.prev.next = node.next; else head = node.next;
    if (node.next != null) node.next.prev = node.prev;
    return head;
}
```

## Advantages and disadvantages of a DLL
- Bidirectional traversal (forward with next, backward with prev).
- O(1) deletion when you hold a reference to the node.
- Efficient insertion at both ends (with head and tail).
- Ideal for deques, browser back/forward history, undo/redo and LRU caches.
- Extra memory for the prev pointer in every node.
- More pointer updates per operation, so more chances of broken links.
- Not cache-friendly, like any linked list.

## Circular linked list
In a circular list the last node points back to the first instead of null. You can start anywhere and visit every node; keep a pointer to the last node so both ends are O(1).

```text
   [ 1 ] -> [ 2 ] -> [ 3 ] -> [ 4 ]
     ^                          |
     +--------------------------+
```

```java
static void printCircular(Node last) {
    if (last == null) return;
    Node cur = last.next;          // first node
    do {
        System.out.print(cur.data + " ");
        cur = cur.next;
    } while (cur != last.next);    // stop when we are back at the start
}
```

Uses: round-robin CPU scheduling, multiplayer turn order, circular buffers and repeating playlists.

## Comparison
- Singly: one pointer, forward only, least memory.
- Doubly: two pointers, both directions, O(1) delete of a known node.
- Circular: no null end, natural for repeating cycles; can be singly or doubly linked.

Note: in circular lists, loops must stop when you return to the starting node, otherwise they never end.
MD,

'Reverse a Linked List' => <<<'MD'
## Problem
Given the head of a singly linked list, reverse the list and return the new head.

```text
Input:  1 -> 2 -> 3 -> 4 -> 5 -> null
Output: 5 -> 4 -> 3 -> 2 -> 1 -> null
```

## Approach 1: copy values into an array (brute force)
Store the values in a list, then write them back in reverse order.
- Time O(n), Space O(n) – extra storage for every value.

```java
static Node reverseWithArray(Node head) {
    List<Integer> values = new ArrayList<>();
    for (Node c = head; c != null; c = c.next) values.add(c.data);
    int i = values.size() - 1;
    for (Node c = head; c != null; c = c.next) c.data = values.get(i--);
    return head;
}
```

## Approach 2: iterative pointer reversal (optimal)
Walk the list once and turn each next pointer around. Keep three references: prev (already reversed part), cur (node being processed) and next (the rest).

```java
static Node reverse(Node head) {
    Node prev = null, cur = head;
    while (cur != null) {
        Node next = cur.next;   // 1. remember the rest
        cur.next = prev;        // 2. reverse the link
        prev = cur;             // 3. move prev forward
        cur = next;             // 4. move cur forward
    }
    return prev;                // new head
}
```

## Dry run on 1 -> 2 -> 3
- Start: prev = null, cur = 1.
- cur 1: next = 2, 1.next = null, prev = 1, cur = 2.
- cur 2: next = 3, 2.next = 1, prev = 2, cur = 3.
- cur 3: next = null, 3.next = 2, prev = 3, cur = null.
- Return 3: list is 3 -> 2 -> 1 -> null.

## Approach 3: recursion
```java
static Node reverseRecursive(Node head) {
    if (head == null || head.next == null) return head;
    Node newHead = reverseRecursive(head.next);
    head.next.next = head;   // the node after head points back to head
    head.next = null;
    return newHead;
}
```

## Complexity
- Array copy: Time O(n), Space O(n).
- Iterative: Time O(n), Space O(1) – the answer interviewers expect.
- Recursive: Time O(n), Space O(n) for the call stack (risk of stack overflow on very long lists).

Note: the most common bug is overwriting cur.next before saving it; always store next first.
MD,

'Find Middle Node' => <<<'MD'
## Problem
Return the middle node of a singly linked list. If there are two middle nodes, return the second one.

```text
1 -> 2 -> 3 -> 4 -> 5        middle = 3
1 -> 2 -> 3 -> 4 -> 5 -> 6   middle = 4
```

## Approach 1: count, then walk (two passes)
- First pass counts n; second pass moves n/2 steps.
- Time O(n), Space O(1), but two traversals.

```java
static Node middleByCount(Node head) {
    int n = 0;
    for (Node c = head; c != null; c = c.next) n++;
    Node cur = head;
    for (int i = 0; i < n / 2; i++) cur = cur.next;
    return cur;
}
```

## Approach 2: slow and fast pointers (one pass, optimal)
Move slow by one step and fast by two. When fast reaches the end, slow is in the middle, because it has travelled half the distance.

```java
static Node middle(Node head) {
    Node slow = head, fast = head;
    while (fast != null && fast.next != null) {
        slow = slow.next;
        fast = fast.next.next;
    }
    return slow;
}
```

## Dry run on 1 -> 2 -> 3 -> 4 -> 5
- slow 1, fast 1
- slow 2, fast 3
- slow 3, fast 5 (fast.next is null) → stop; middle = 3.

## Complexity
- Counting: Time O(n) (two passes), Space O(1).
- Slow/fast: Time O(n) (one pass), Space O(1).

Note: to return the first middle for even lengths, loop while fast.next != null && fast.next.next != null.

## Where this pattern is used
Splitting a list for merge sort, checking whether a list is a palindrome (reverse the second half), and cycle detection.
MD,

'Detect a Cycle' => <<<'MD'
## Problem
Determine whether a linked list contains a cycle, i.e. some node's next points back to an earlier node so traversal never reaches null. Follow-up: return the node where the cycle starts.

```text
1 -> 2 -> 3 -> 4 -> 5
          ^         |
          +---------+      cycle starts at 3
```

## Approach 1: hash set of visited nodes
Store every visited node; seeing a node twice means a cycle.
- Time O(n), Space O(n).

```java
static boolean hasCycleSet(Node head) {
    Set<Node> seen = new HashSet<>();
    for (Node c = head; c != null; c = c.next) {
        if (!seen.add(c)) return true;
    }
    return false;
}
```

## Approach 2: Floyd's tortoise and hare (optimal)
Slow moves 1 step, fast moves 2. In a cycle, fast gains one node per step on slow, so they must meet; without a cycle, fast reaches null.

```java
static boolean hasCycle(Node head) {
    Node slow = head, fast = head;
    while (fast != null && fast.next != null) {
        slow = slow.next;
        fast = fast.next.next;
        if (slow == fast) return true;
    }
    return false;
}
```

## Finding the start of the cycle
After slow and fast meet, move one pointer back to the head and advance both one step at a time; they meet at the cycle's first node. (If the distance from head to the cycle start is a and the meeting point is b steps into the cycle of length c, then a ≡ c − b mod c.)

```java
static Node cycleStart(Node head) {
    Node slow = head, fast = head;
    while (fast != null && fast.next != null) {
        slow = slow.next; fast = fast.next.next;
        if (slow == fast) {
            Node p = head;
            while (p != slow) { p = p.next; slow = slow.next; }
            return p;
        }
    }
    return null;
}
```

## Complexity
- Hash set: Time O(n), Space O(n).
- Floyd: Time O(n), Space O(1).

Note: compare node references (==), not node values; two different nodes may hold the same value.
MD,

'Merge Two Sorted Lists' => <<<'MD'
## Problem
Merge two sorted linked lists into one sorted list by splicing their nodes together.

```text
l1: 1 -> 3 -> 5
l2: 2 -> 4 -> 6
out: 1 -> 2 -> 3 -> 4 -> 5 -> 6
```

## Approach 1: collect values and sort (brute force)
Copy both lists into an array, sort, rebuild. Time O((n+m) log(n+m)), Space O(n+m). It ignores that the inputs are already sorted.

## Approach 2: iterative merge with a dummy node (optimal)
Keep a tail pointer; repeatedly attach the smaller head and advance that list. Attach the leftover list at the end.

```java
static Node merge(Node l1, Node l2) {
    Node dummy = new Node(0), tail = dummy;
    while (l1 != null && l2 != null) {
        if (l1.data <= l2.data) { tail.next = l1; l1 = l1.next; }
        else                    { tail.next = l2; l2 = l2.next; }
        tail = tail.next;
    }
    tail.next = (l1 != null) ? l1 : l2;   // one list may still have nodes
    return dummy.next;
}
```

## Dry run
- Compare 1 and 2 → take 1. Compare 3 and 2 → take 2. Compare 3 and 4 → take 3.
- Compare 5 and 4 → take 4. Compare 5 and 6 → take 5. l1 empty → attach 6.

## Recursive version
```java
static Node mergeRecursive(Node a, Node b) {
    if (a == null) return b;
    if (b == null) return a;
    if (a.data <= b.data) { a.next = mergeRecursive(a.next, b); return a; }
    b.next = mergeRecursive(a, b.next);
    return b;
}
```

## Complexity
- Sort-based: Time O((n+m) log(n+m)), Space O(n+m).
- Iterative merge: Time O(n+m), Space O(1).
- Recursive merge: Time O(n+m), Space O(n+m) call stack.

Note: using <= keeps the merge stable (equal values from l1 come first). This merge step is the heart of merge sort on linked lists.
MD,

'Remove Nth Node from End' => <<<'MD'
## Problem
Remove the n-th node from the end of the list and return the head.

```text
Input: 1 -> 2 -> 3 -> 4 -> 5, n = 2
Output: 1 -> 2 -> 3 -> 5
```

## Approach 1: two passes
Count the length L; the node to remove is at position L − n + 1 from the start. Walk to the node before it and unlink. Time O(n), Space O(1), two traversals.

```java
static Node removeNthTwoPass(Node head, int n) {
    int length = 0;
    for (Node c = head; c != null; c = c.next) length++;
    Node dummy = new Node(0); dummy.next = head;
    Node prev = dummy;
    for (int i = 0; i < length - n; i++) prev = prev.next;
    prev.next = prev.next.next;
    return dummy.next;
}
```

## Approach 2: one pass with two pointers (optimal)
Move fast n steps ahead. Then move fast and slow together until fast reaches the last node; slow is just before the node to delete. A dummy node handles removal of the head.

```java
static Node removeNth(Node head, int n) {
    Node dummy = new Node(0); dummy.next = head;
    Node fast = dummy, slow = dummy;
    for (int i = 0; i < n; i++) fast = fast.next;   // gap of n nodes
    while (fast.next != null) { fast = fast.next; slow = slow.next; }
    slow.next = slow.next.next;                      // unlink the target
    return dummy.next;
}
```

## Dry run (n = 2)
- After the head start: fast at 2, slow at dummy.
- Move together: (3, 1), (4, 2), (5, 3) → fast.next is null.
- slow is 3; remove 4 → 1 -> 2 -> 3 -> 5.

## Complexity
- Two pass: Time O(n), Space O(1).
- One pass: Time O(n), Space O(1).

Note: without a dummy node, removing the first node (n equals the length) needs a special case.
MD,

'Clone List With Random Pointer' => <<<'MD'
## Problem
Each node has next and random pointers (random may point to any node or null). Create a deep copy: new nodes with the same values whose next and random point to the corresponding new nodes.

## Approach 1: hash map from original to copy
1. First pass: create a copy of every node and store original → copy.
2. Second pass: copy.next = map(original.next), copy.random = map(original.random).
- Time O(n), Space O(n).

```java
class RNode { int val; RNode next, random; RNode(int v) { val = v; } }

static RNode copyWithMap(RNode head) {
    Map<RNode, RNode> map = new HashMap<>();
    for (RNode c = head; c != null; c = c.next) map.put(c, new RNode(c.val));
    for (RNode c = head; c != null; c = c.next) {
        map.get(c).next = map.get(c.next);
        map.get(c).random = map.get(c.random);
    }
    return map.get(head);
}
```

## Approach 2: interleave copies in the same list (O(1) extra space)
1. Insert each copy right after its original: A -> A' -> B -> B' -> ...
2. Set randoms: original.next.random = original.random.next.
3. Separate the two lists.

```java
static RNode copyInterleaved(RNode head) {
    if (head == null) return null;
    for (RNode c = head; c != null; c = c.next.next) {          // step 1
        RNode copy = new RNode(c.val);
        copy.next = c.next; c.next = copy;
    }
    for (RNode c = head; c != null; c = c.next.next) {          // step 2
        if (c.random != null) c.next.random = c.random.next;
    }
    RNode newHead = head.next;
    for (RNode c = head; c != null; c = c.next) {               // step 3
        RNode copy = c.next;
        c.next = copy.next;
        copy.next = (copy.next != null) ? copy.next.next : null;
    }
    return newHead;
}
```

## Complexity
- Hash map: Time O(n), Space O(n).
- Interleaving: Time O(n), Space O(1) extra (besides the output).

Note: the original list must be restored in step 3; interviewers check that you did not leave it modified.
MD,

'LRU Cache' => <<<'MD'
## Problem
Design a Least Recently Used cache with a fixed capacity supporting get(key) and put(key, value), both in O(1). When the cache is full, put evicts the least recently used entry.

```text
capacity = 2
put(1,1); put(2,2); get(1) → 1    (order: 2, 1)
put(3,3)          → evicts key 2  (order: 1, 3)
get(2)            → -1
```

## Why a hash map plus a doubly linked list
- A HashMap gives O(1) lookup from key to node.
- A doubly linked list keeps usage order: most recently used at the head, least recently used at the tail. A DLL can remove any known node and add at the head in O(1).

## Implementation
```java
class LRUCache {
    private class Node { int key, value; Node prev, next; Node(int k, int v) { key = k; value = v; } }

    private final int capacity;
    private final Map<Integer, Node> map = new HashMap<>();
    private final Node head = new Node(0, 0), tail = new Node(0, 0);   // sentinels

    LRUCache(int capacity) {
        this.capacity = capacity;
        head.next = tail; tail.prev = head;
    }

    public int get(int key) {
        Node node = map.get(key);
        if (node == null) return -1;
        moveToFront(node);
        return node.value;
    }

    public void put(int key, int value) {
        Node node = map.get(key);
        if (node != null) { node.value = value; moveToFront(node); return; }
        if (map.size() == capacity) {                 // evict least recently used
            Node lru = tail.prev;
            remove(lru);
            map.remove(lru.key);
        }
        Node fresh = new Node(key, value);
        addFront(fresh);
        map.put(key, fresh);
    }

    private void remove(Node n) { n.prev.next = n.next; n.next.prev = n.prev; }
    private void addFront(Node n) { n.next = head.next; n.prev = head; head.next.prev = n; head.next = n; }
    private void moveToFront(Node n) { remove(n); addFront(n); }
}
```

## Shortcut in Java
```java
class LRU<K, V> extends LinkedHashMap<K, V> {
    private final int capacity;
    LRU(int capacity) { super(16, 0.75f, true); this.capacity = capacity; }   // accessOrder = true
    protected boolean removeEldestEntry(Map.Entry<K, V> eldest) { return size() > capacity; }
}
```

## Complexity
- get and put: O(1) time.
- Space: O(capacity).

## Real-world use
Database buffer pools, CDN and browser caches, and in-memory caches such as Redis (allkeys-lru eviction policy).

Note: sentinel head and tail nodes remove all null checks when adding or removing at the ends.
MD,

];
