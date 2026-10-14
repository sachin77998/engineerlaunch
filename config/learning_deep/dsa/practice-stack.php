<?php

// DSA › Stack problems with C, C++ and Java solutions (numbered as supplied).
return [

'Q1. Implement a stack using an array' => <<<'MD'
## Solution
A stack follows LIFO (Last In, First Out). `push()` inserts, `pop()` removes the top element, and `peek()` returns the top without removing it.

## Step by step
1. Keep an array `stack[MAX]` and an index `top`, starting at `-1` (empty stack).
2. push(x): if `top == MAX - 1` the stack is full (overflow); otherwise increment `top` and store `x` at `stack[top]`.
3. pop(): if `top == -1` the stack is empty (underflow); otherwise return `stack[top]` and decrement `top`.
4. peek(): return `stack[top]` without changing `top` (or -1 when empty).

## Dry run
| Operation | top after | Stack (bottom → top) | Returns |
|---|---|---|---|
| push(10) | 0 | 10 | — |
| push(20) | 1 | 10, 20 | — |
| pop() | 0 | 10 | 20 |
| peek() | 0 | 10 | 10 |

## C
```c
#include <stdio.h>
#define MAX 100

int stack[MAX], top = -1;

void push(int x) {
    if (top == MAX - 1) {
        printf("Stack Overflow\n");
        return;
    }
    stack[++top] = x;
}

int pop(void) {
    if (top == -1) {
        printf("Stack Underflow\n");
        return -1;
    }
    return stack[top--];
}

int peek(void) {
    return top == -1 ? -1 : stack[top];
}

int main(void) {
    push(10);
    push(20);
    printf("%d\n", pop());  // 20
    printf("%d\n", peek()); // 10
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
using namespace std;

int main() {
    stack<int> s;
    s.push(10);
    s.push(20);

    cout << s.top() << '\n'; // 20
    s.pop();
    cout << s.top() << '\n'; // 10
}
```

## Java
```java
import java.util.ArrayDeque;
import java.util.Deque;

public class Main {
    public static void main(String[] args) {
        Deque<Integer> stack = new ArrayDeque<>();
        stack.push(10);
        stack.push(20);

        System.out.println(stack.peek()); // 20
        System.out.println(stack.pop());  // 20
    }
}
```

## Complexity
- push, pop and peek: O(1) time each.
- Space: O(n) for the array.

Note: in Java prefer `ArrayDeque` over the legacy `Stack` class, which is synchronized and therefore slower.
MD,

'Q2. Reverse a string using a stack' => <<<'MD'
## Solution
Push every character onto the stack, then pop each character into the output. Popping reverses the insertion order.

## Step by step
1. Create an empty stack of characters.
2. Traverse the string from left to right and push each character.
3. Pop characters one by one and write them back from index 0; the last character pushed comes out first.

## Dry run for "HELLO"
| Step | Action | Stack (bottom → top) | Output so far |
|---|---|---|---|
| 1–5 | push H, E, L, L, O | H E L L O | — |
| 6 | pop O | H E L L | O |
| 7 | pop L | H E L | OL |
| 8 | pop L | H E | OLL |
| 9 | pop E | H | OLLE |
| 10 | pop H | (empty) | OLLEH |

## C
```c
#include <stdio.h>
#include <string.h>

void reverse(char s[]) {
    int n = strlen(s);
    char stack[n + 1];
    int top = -1;

    for (int i = 0; i < n; i++)
        stack[++top] = s[i];

    for (int i = 0; i < n; i++)
        s[i] = stack[top--];
}

int main(void) {
    char s[] = "HELLO";
    reverse(s);
    printf("%s\n", s); // OLLEH
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <string>
using namespace std;

int main() {
    string s = "HELLO";
    stack<char> st;

    for (char c : s) st.push(c);

    for (char &c : s) {
        c = st.top();
        st.pop();
    }

    cout << s << '\n'; // OLLEH
}
```

## Java
```java
import java.util.ArrayDeque;
import java.util.Deque;

public class Main {
    static String reverse(String s) {
        Deque<Character> stack = new ArrayDeque<>();
        for (char c : s.toCharArray()) stack.push(c);

        StringBuilder out = new StringBuilder(s.length());
        while (!stack.isEmpty()) out.append(stack.pop());
        return out.toString();
    }

    public static void main(String[] args) {
        System.out.println(reverse("HELLO")); // OLLEH
    }
}
```

## Complexity
- Time: O(n): each character is pushed once and popped once.
- Space: O(n) for the stack.

Note: without a stack the same result needs only O(1) extra space by swapping `s[i]` and `s[n-1-i]` from both ends; the stack version is asked to test LIFO understanding.
MD,

];
