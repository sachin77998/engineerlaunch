<?php

// DSA › Stack, Queue and Deque: concept lessons and problems, in depth.
return [

'What is a Stack?' => <<<'MD'
## What it is
A stack is a linear data structure that follows LIFO – Last In, First Out. The last element added is the first one removed, like a stack of plates: you put a plate on top and take the top plate first.

```text
push(10), push(20), push(30)

   top -> | 30 |
          | 20 |
          | 10 |
          +----+
pop() returns 30, then 20, then 10
```

## Core operations (all O(1))
- push(x): place x on top.
- pop(): remove and return the top element.
- peek() / top(): read the top element without removing it.
- isEmpty(): check whether the stack has elements.
- size(): number of elements.

## Implementation 1: using an array
```java
class ArrayStack {
    private final int[] items;
    private int top = -1;                       // index of the top element

    ArrayStack(int capacity) { items = new int[capacity]; }

    void push(int x) {
        if (top == items.length - 1) throw new RuntimeException("Stack overflow");
        items[++top] = x;
    }
    int pop() {
        if (top == -1) throw new RuntimeException("Stack underflow");
        return items[top--];
    }
    int peek() { if (top == -1) throw new RuntimeException("Empty"); return items[top]; }
    boolean isEmpty() { return top == -1; }
}
```

## Implementation 2: using a linked list (no fixed capacity)
Push and pop at the head, both O(1).

```java
class LinkedStack {
    private static class Node { int data; Node next; Node(int d, Node n) { data = d; next = n; } }
    private Node head;
    void push(int x) { head = new Node(x, head); }
    int pop() {
        if (head == null) throw new RuntimeException("Stack underflow");
        int value = head.data; head = head.next; return value;
    }
    int peek() { return head.data; }
    boolean isEmpty() { return head == null; }
}
```

## Using Java's built-in stack
```java
Deque<Integer> stack = new ArrayDeque<>();   // preferred over the old Stack class
stack.push(10); stack.push(20); stack.push(30);
System.out.println(stack.pop());   // 30
System.out.println(stack.peek());  // 20
```

```text
Output
30
20
```

## Complexity
- push, pop, peek, isEmpty: O(1) time.
- Space: O(n) for n elements.

## Applications
- Function calls: the call stack stores return addresses and local variables (recursion uses it).
- Undo in editors, back button in browsers.
- Expression evaluation and conversion (infix to postfix), syntax checking of brackets.
- Depth-first search and backtracking (maze solving).
- "Next greater element" style problems using a monotonic stack.

## Advantages and disadvantages
- Very simple and fast; all main operations are O(1).
- Natural for nested or reversible processes.
- Only the top element is accessible; searching is O(n).
- An array-based stack has a fixed capacity (overflow) unless it resizes.

Note: pop or peek on an empty stack is "underflow"; push on a full fixed-size stack is "overflow". Always check isEmpty first.
MD,

'What is a Queue and a Deque?' => <<<'MD'
## Queue
A queue is a linear data structure that follows FIFO – First In, First Out. Elements are added at the rear and removed from the front, like people in a ticket line.

```text
enqueue(10), enqueue(20), enqueue(30)

front -> [ 10 | 20 | 30 ] <- rear
dequeue() returns 10, then 20, then 30
```

## Queue operations (O(1))
- enqueue / offer(x): add at the rear.
- dequeue / poll(): remove from the front.
- peek(): read the front element.
- isEmpty(), size().

## Circular array queue
A plain array wastes space after dequeues, so front and rear wrap around using modulo.

```java
class CircularQueue {
    private final int[] items;
    private int front = 0, size = 0;

    CircularQueue(int capacity) { items = new int[capacity]; }

    boolean enqueue(int x) {
        if (size == items.length) return false;             // full
        items[(front + size) % items.length] = x;
        size++;
        return true;
    }
    int dequeue() {
        if (size == 0) throw new RuntimeException("Queue is empty");
        int value = items[front];
        front = (front + 1) % items.length;
        size--;
        return value;
    }
}
```

## Using Java's built-in queue
```java
Queue<String> tickets = new ArrayDeque<>();
tickets.offer("Asha"); tickets.offer("Ravi"); tickets.offer("Neha");
System.out.println(tickets.poll());   // Asha
System.out.println(tickets.peek());   // Ravi
```

## Deque (double-ended queue)
A deque allows insertion and removal at both ends, so it can act as a stack or a queue.
- addFirst / offerFirst, addLast / offerLast
- pollFirst, pollLast, peekFirst, peekLast

```java
Deque<Integer> dq = new ArrayDeque<>();
dq.offerLast(1); dq.offerLast(2); dq.offerFirst(0);   // 0, 1, 2
dq.pollLast();                                        // removes 2
System.out.println(dq);                               // [0, 1]
```

## Priority queue
Elements are removed in priority order (smallest first by default), not arrival order. It is backed by a heap: offer and poll are O(log n). See the Heap lesson.

## Complexity
- Queue and deque operations at the ends: O(1).
- Searching: O(n).

## Applications
- Queue: job and print queues, request handling in web servers, BFS traversal, message queues (Kafka, RabbitMQ) and CPU scheduling.
- Deque: sliding-window maximum, browser history (both ends), palindrome checks, work-stealing schedulers.
- Priority queue: Dijkstra's algorithm, task scheduling by priority, top-K problems.

Note: in Java prefer ArrayDeque for both stacks and queues; LinkedList also works but uses more memory per element.
MD,

'Valid Parentheses' => <<<'MD'
## Problem
Given a string of brackets ()[]{}, decide whether it is valid: every opening bracket is closed by the same type, in the correct order.

```text
"()[]{}"  -> true
"([{}])"  -> true
"(]"      -> false
"([)]"    -> false
"(("      -> false
```

## Idea
The most recently opened bracket must be closed first – exactly LIFO, so use a stack.

## Algorithm
1. For each character:
   - If it is an opening bracket, push it.
   - If it is a closing bracket, the stack must not be empty and its top must be the matching opener; pop it.
2. At the end the stack must be empty.

```java
static boolean isValid(String s) {
    Deque<Character> stack = new ArrayDeque<>();
    for (char c : s.toCharArray()) {
        if (c == '(' || c == '[' || c == '{') {
            stack.push(c);
        } else {
            if (stack.isEmpty()) return false;
            char open = stack.pop();
            if ((c == ')' && open != '(') || (c == ']' && open != '[') || (c == '}' && open != '{')) return false;
        }
    }
    return stack.isEmpty();
}
```

## Dry run on "([{}])"
- ( push → [(]
- [ push → [(, []
- { push → [(, [, {]
- } pops { ✓
- ] pops [ ✓
- ) pops ( ✓ → stack empty → true

## Brute force for comparison
Repeatedly remove "()", "[]" and "{}" until nothing changes; valid if the string becomes empty. Each pass is O(n) and up to n/2 passes are needed: O(n²) time.

## Complexity
- Stack solution: Time O(n), Space O(n).
- Replace-pairs solution: Time O(n²), Space O(n).

Note: an odd-length string can be rejected immediately.
MD,

'Next Greater Element' => <<<'MD'
## Problem
For each element of an array, find the first element to its right that is greater; return -1 if none exists.

```text
Input:  [4, 5, 2, 10, 8]
Output: [5, 10, 10, -1, -1]
```

## Approach 1: nested loops (brute force)
For each i, scan j = i+1..n-1 until arr[j] > arr[i].
- Time O(n²), Space O(1).

```java
static int[] nextGreaterBrute(int[] a) {
    int[] res = new int[a.length];
    for (int i = 0; i < a.length; i++) {
        res[i] = -1;
        for (int j = i + 1; j < a.length; j++) if (a[j] > a[i]) { res[i] = a[j]; break; }
    }
    return res;
}
```

## Approach 2: monotonic stack (optimal)
Traverse from right to left, keeping a stack of candidates in decreasing order. Pop every element that is not greater than the current one; the remaining top is the answer. Then push the current element.

```java
static int[] nextGreater(int[] a) {
    int[] res = new int[a.length];
    Deque<Integer> stack = new ArrayDeque<>();
    for (int i = a.length - 1; i >= 0; i--) {
        while (!stack.isEmpty() && stack.peek() <= a[i]) stack.pop();
        res[i] = stack.isEmpty() ? -1 : stack.peek();
        stack.push(a[i]);
    }
    return res;
}
```

## Dry run on [4, 5, 2, 10, 8]
- i=4 (8): stack empty → -1; push 8 → [8]
- i=3 (10): pop 8; empty → -1; push 10 → [10]
- i=2 (2): top 10 > 2 → 10; push 2 → [2, 10]
- i=1 (5): pop 2; top 10 → 10; push 5 → [5, 10]
- i=0 (4): top 5 → 5

## Complexity
- Brute force: Time O(n²), Space O(1).
- Monotonic stack: Time O(n) – every element is pushed and popped at most once – Space O(n).

## Variations using the same idea
Next smaller element, previous greater element, stock span, daily temperatures, and largest rectangle in a histogram.
MD,

'Min Stack' => <<<'MD'
## Problem
Design a stack that supports push, pop, top and getMin, all in O(1) time.

## Approach 1: scan for the minimum (brute force)
Use a normal stack and scan it on getMin. getMin becomes O(n).

## Approach 2: a second stack of minimums (optimal)
Alongside the main stack, keep a stack whose top is always the minimum of all elements currently in the main stack.

```java
class MinStack {
    private final Deque<Integer> stack = new ArrayDeque<>();
    private final Deque<Integer> mins = new ArrayDeque<>();

    void push(int x) {
        stack.push(x);
        if (mins.isEmpty() || x <= mins.peek()) mins.push(x);   // <= keeps duplicates
    }
    void pop() {
        int removed = stack.pop();
        if (removed == mins.peek()) mins.pop();
    }
    int top() { return stack.peek(); }
    int getMin() { return mins.peek(); }
}
```

## Dry run
- push 5 → stack [5], mins [5]
- push 3 → stack [3,5], mins [3,5]
- push 7 → stack [7,3,5], mins [3,5]
- getMin → 3
- pop (7) → mins unchanged → getMin 3
- pop (3) → mins pops 3 → getMin 5

## Approach 3: one stack storing pairs
Push (value, minimumSoFar) pairs; getMin reads the pair on top. Same complexity, simpler logic, slightly more memory.

## Complexity
- All operations O(1) time.
- Space O(n) (the min stack holds at most n values).

Note: use x <= mins.peek(), not <, otherwise popping a duplicate minimum would lose the minimum.
MD,

'Implement Queue Using Stacks' => <<<'MD'
## Problem
Implement a FIFO queue (push, pop, peek, empty) using only stack operations.

## Idea: two stacks
- in: receives every new element.
- out: serves pops and peeks. When out is empty, move everything from in to out, which reverses the order so the oldest element is on top.

```java
class MyQueue {
    private final Deque<Integer> in = new ArrayDeque<>();
    private final Deque<Integer> out = new ArrayDeque<>();

    void push(int x) { in.push(x); }

    int pop() { shift(); return out.pop(); }

    int peek() { shift(); return out.peek(); }

    boolean empty() { return in.isEmpty() && out.isEmpty(); }

    private void shift() {
        if (out.isEmpty()) while (!in.isEmpty()) out.push(in.pop());
    }
}
```

## Dry run
- push 1, push 2, push 3 → in [3,2,1], out []
- pop → out empty, move all: out [1,2,3] → pop returns 1
- push 4 → in [4]
- pop → out not empty → returns 2

## Complexity
- push: O(1).
- pop/peek: amortised O(1). A single call may move n elements, but each element moves from in to out only once in its lifetime.
- Space: O(n).

## The opposite problem: stack using queues
Push x, then rotate the queue size−1 times so x is at the front; pop is then a normal poll. Push becomes O(n), pop O(1).

Note: interviewers usually want the words "amortised O(1)" and the reason (each element is moved at most once).
MD,

'Sliding Window Maximum' => <<<'MD'
## Problem
Given an array and window size k, return the maximum of each window as it slides from left to right.

```text
nums = [1, 3, -1, -3, 5, 3, 6, 7], k = 3
windows: [1 3 -1] [3 -1 -3] [-1 -3 5] [-3 5 3] [5 3 6] [3 6 7]
output:  [3, 3, 5, 5, 6, 7]
```

## Approach 1: scan every window
For each of the n−k+1 windows, scan k elements. Time O(n·k), Space O(1).

## Approach 2: max-heap
Keep (value, index) pairs in a priority queue; discard tops whose index is outside the window. Time O(n log n), Space O(n).

## Approach 3: monotonic deque (optimal)
Store indices in a deque whose values are in decreasing order.
1. Remove the front index if it has left the window.
2. Remove from the back every index whose value is smaller than the current value (it can never be a maximum again).
3. Add the current index at the back.
4. Once the first window is complete, the front holds the maximum.

```java
static int[] maxSlidingWindow(int[] nums, int k) {
    int[] result = new int[nums.length - k + 1];
    Deque<Integer> dq = new ArrayDeque<>();
    for (int i = 0; i < nums.length; i++) {
        if (!dq.isEmpty() && dq.peekFirst() <= i - k) dq.pollFirst();
        while (!dq.isEmpty() && nums[dq.peekLast()] < nums[i]) dq.pollLast();
        dq.offerLast(i);
        if (i >= k - 1) result[i - k + 1] = nums[dq.peekFirst()];
    }
    return result;
}
```

## Complexity
- Brute force: O(n·k) time.
- Heap: O(n log n) time, O(n) space.
- Deque: O(n) time – each index enters and leaves once – O(k) space.

Note: store indices, not values, so you can tell when the front element has slid out of the window.
MD,

'Largest Rectangle in Histogram' => <<<'MD'
## Problem
Given bar heights of width 1, find the area of the largest rectangle that fits inside the histogram.

```text
heights = [2, 1, 5, 6, 2, 3]  → answer 10 (bars 5 and 6, height 5, width 2)
```

## Approach 1: expand from every bar
For each bar, extend left and right while neighbours are at least as tall; area = height × width. Time O(n²), Space O(1).

## Approach 2: monotonic increasing stack (optimal)
For every bar we need the nearest smaller bar on the left and on the right; the bar can extend between them. Keep indices of increasing heights on a stack. When the current bar is lower than the top, the top bar's rectangle ends here: its right boundary is i and its left boundary is the new top after popping.

```java
static int largestRectangle(int[] h) {
    Deque<Integer> stack = new ArrayDeque<>();
    int best = 0;
    for (int i = 0; i <= h.length; i++) {
        int current = (i == h.length) ? 0 : h[i];        // sentinel 0 flushes the stack
        while (!stack.isEmpty() && current < h[stack.peek()]) {
            int height = h[stack.pop()];
            int left = stack.isEmpty() ? -1 : stack.peek();
            best = Math.max(best, height * (i - left - 1));
        }
        stack.push(i);
    }
    return best;
}
```

## Dry run highlights on [2, 1, 5, 6, 2, 3]
- At i=4 (height 2): pop 6 → width 1 → area 6; pop 5 → width 2 → area 10.
- At the end the sentinel pops the rest: height 2 → width 4 → 8; height 1 → width 6 → 6.
- Maximum = 10.

## Complexity
- Brute force: O(n²) time.
- Stack: O(n) time, O(n) space.

Note: this is the building block for "maximal rectangle of 1s in a binary matrix": treat each row as a histogram.
MD,

];
