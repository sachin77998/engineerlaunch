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
    public static void main(String[] args) {
        String s = "HELLO";
        Deque<Character> st = new ArrayDeque<>();

        for (char c : s.toCharArray()) st.push(c);

        StringBuilder result = new StringBuilder();
        while (!st.isEmpty()) result.append(st.pop());

        System.out.println(result); // OLLEH
    }
}
```

## Complexity
- Time: O(n): each character is pushed once and popped once.
- Space: O(n) extra space for the stack.

Note: without a stack the same result needs only O(1) extra space by swapping `s[i]` and `s[n-1-i]` from both ends; the stack version is asked to test LIFO understanding.
MD,

'Q3. Check balanced parentheses' => <<<'MD'
## Problem
Determine whether `({[]})` is balanced but `([)]` is not.

## Solution
Push opening brackets. For each closing bracket, verify that it matches the most recently opened bracket.

## Step by step
1. Scan the string from left to right.
2. Opening bracket `(`, `[` or `{`: push it.
3. Closing bracket: if the stack is empty there is nothing to close, so the string is unbalanced. Otherwise pop the top and check it is the matching opener (`)` ↔ `(`, `]` ↔ `[`, `}` ↔ `{`); a mismatch means unbalanced.
4. Other characters are ignored.
5. At the end the string is balanced only if the stack is empty (no opener left unclosed).

## Dry run
| Input | Step | Stack | Result |
|---|---|---|---|
| ({[]}) | push ( { [ | ( { [ | — |
| | ] matches [ , } matches { , ) matches ( | empty | balanced |
| ([)] | push ( [ | ( [ | — |
| | ) but top is [ | — | not balanced |

## C
```c
#include <stdio.h>
#include <string.h>
#include <stdbool.h>

bool balanced(const char *s) {
    int n = strlen(s);
    char stack[n + 1];
    int top = -1;

    for (int i = 0; i < n; i++) {
        char c = s[i];
        if (c == '(' || c == '[' || c == '{') {
            stack[++top] = c;
        } else if (c == ')' || c == ']' || c == '}') {
            if (top == -1) return false;
            char t = stack[top--];
            if ((c == ')' && t != '(') ||
                (c == ']' && t != '[') ||
                (c == '}' && t != '{'))
                return false;
        }
    }
    return top == -1;
}

int main(void) {
    printf("%d\n", balanced("({[]})")); // 1
    printf("%d\n", balanced("([)]"));   // 0
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <string>
using namespace std;

bool balanced(string s) {
    stack<char> st;

    for (char c : s) {
        if (c == '(' || c == '[' || c == '{') {
            st.push(c);
        } else if (c == ')' || c == ']' || c == '}') {
            if (st.empty()) return false;

            char t = st.top();
            st.pop();

            if ((c == ')' && t != '(') ||
                (c == ']' && t != '[') ||
                (c == '}' && t != '{'))
                return false;
        }
    }

    return st.empty();
}

int main() {
    cout << balanced("({[]})") << '\n'; // 1
    cout << balanced("([)]") << '\n';   // 0
}
```

## Java
```java
import java.util.ArrayDeque;
import java.util.Deque;

public class Main {
    static boolean balanced(String s) {
        Deque<Character> st = new ArrayDeque<>();

        for (char c : s.toCharArray()) {
            if (c == '(' || c == '[' || c == '{') {
                st.push(c);
            } else if (c == ')' || c == ']' || c == '}') {
                if (st.isEmpty()) return false;
                char t = st.pop();
                if ((c == ')' && t != '(') ||
                    (c == ']' && t != '[') ||
                    (c == '}' && t != '{'))
                    return false;
            }
        }
        return st.isEmpty();
    }

    public static void main(String[] args) {
        System.out.println(balanced("({[]})")); // true
        System.out.println(balanced("([)]"));   // false
    }
}
```

## Complexity
- Time: O(n), one pass.
- Space: O(n) in the worst case (all openers).
MD,

'Q4. Evaluate a postfix expression' => <<<'MD'
## Problem
Evaluate `2 3 4 * +`, which equals 2 + (3 × 4) = 14.

## Solution
Push operands. When an operator appears, pop the right operand first, then the left operand; apply the operator and push the result.

## Step by step
1. Read the tokens from left to right.
2. Number: push it.
3. Operator: pop `b` (right operand), then pop `a` (left operand), compute `a op b` and push the result. The order matters for `-` and `/`: `8 2 -` is 8 − 2.
4. At the end the single value left on the stack is the answer.

## Dry run
| Token | Action | Stack (bottom → top) |
|---|---|---|
| 2 | push | 2 |
| 3 | push | 2 3 |
| 4 | push | 2 3 4 |
| * | b=4, a=3 → push 12 | 2 12 |
| + | b=12, a=2 → push 14 | 14 |

## C
```c
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

int evaluatePostfix(char *expr) {
    int stack[100], top = -1;
    char *token = strtok(expr, " ");

    while (token != NULL) {
        if (strlen(token) == 1 && strchr("+-*/", token[0])) {
            int b = stack[top--];
            int a = stack[top--];
            switch (token[0]) {
                case '+': stack[++top] = a + b; break;
                case '-': stack[++top] = a - b; break;
                case '*': stack[++top] = a * b; break;
                case '/': stack[++top] = a / b; break;
            }
        } else {
            stack[++top] = (int) strtol(token, NULL, 10);
        }
        token = strtok(NULL, " ");
    }
    return stack[top];
}

int main(void) {
    char expr[] = "2 3 4 * +";
    printf("%d\n", evaluatePostfix(expr)); // 14
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <vector>
using namespace std;

int evaluatePostfix(vector<string> tokens) {
    stack<int> st;

    for (const string &t : tokens) {
        if (t == "+" || t == "-" || t == "*" || t == "/") {
            int b = st.top(); st.pop();
            int a = st.top(); st.pop();

            if (t == "+") st.push(a + b);
            else if (t == "-") st.push(a - b);
            else if (t == "*") st.push(a * b);
            else st.push(a / b);
        } else {
            st.push(stoi(t));
        }
    }

    return st.top();
}

int main() {
    cout << evaluatePostfix({"2", "3", "4", "*", "+"});
}
```

## Java
```java
import java.util.ArrayDeque;
import java.util.Deque;

public class Main {
    static int evaluatePostfix(String expr) {
        Deque<Integer> st = new ArrayDeque<>();

        for (String t : expr.trim().split("\\s+")) {
            switch (t) {
                case "+", "-", "*", "/" -> {
                    int b = st.pop();
                    int a = st.pop();
                    st.push(switch (t) {
                        case "+" -> a + b;
                        case "-" -> a - b;
                        case "*" -> a * b;
                        default -> a / b;
                    });
                }
                default -> st.push(Integer.parseInt(t));
            }
        }
        return st.pop();
    }

    public static void main(String[] args) {
        System.out.println(evaluatePostfix("2 3 4 * +")); // 14
    }
}
```

## Complexity
- Time: O(n), each token handled once.
- Space: O(n) for the stack.

Note: division here uses integer arithmetic and assumes valid input (no division by zero, enough operands).
MD,

'Q5. Convert infix expression to postfix' => <<<'MD'
## Problem
Convert `A+B*C` to `ABC*+`.

## Solution
Output operands immediately. Push operators onto a stack, respecting precedence and parentheses. Multiplication has higher precedence than addition.

## Step by step (shunting-yard)
1. Operand (letter or digit): append it to the output.
2. `(`: push it.
3. `)`: pop operators to the output until `(`, then discard the `(`.
4. Operator: while the top of the stack is an operator with greater or equal precedence, pop it to the output; then push the new operator.
5. At the end, pop every remaining operator to the output.

Precedence: `^` (3) > `*` `/` (2) > `+` `-` (1).

## Dry run for A+B*C
| Char | Action | Stack | Output |
|---|---|---|---|
| A | output | — | A |
| + | push | + | A |
| B | output | + | AB |
| * | top + has lower precedence → push | + * | AB |
| C | output | + * | ABC |
| end | pop * then + | — | ABC*+ |

## C
```c
#include <stdio.h>
#include <ctype.h>

int precedence(char c) {
    if (c == '+' || c == '-') return 1;
    if (c == '*' || c == '/') return 2;
    if (c == '^') return 3;
    return 0;
}

void toPostfix(const char *s, char *out) {
    char stack[100];
    int top = -1, k = 0;

    for (int i = 0; s[i]; i++) {
        char c = s[i];
        if (c == ' ') continue;
        if (isalnum((unsigned char) c)) out[k++] = c;
        else if (c == '(') stack[++top] = c;
        else if (c == ')') {
            while (top != -1 && stack[top] != '(') out[k++] = stack[top--];
            if (top != -1) top--;               // discard '('
        } else {
            while (top != -1 && stack[top] != '(' && precedence(stack[top]) >= precedence(c))
                out[k++] = stack[top--];
            stack[++top] = c;
        }
    }
    while (top != -1) out[k++] = stack[top--];
    out[k] = '\0';
}

int main(void) {
    char out[100];
    toPostfix("A+B*C", out);
    printf("%s\n", out); // ABC*+
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <string>
using namespace std;

int precedence(char c) {
    if (c == '+' || c == '-') return 1;
    if (c == '*' || c == '/') return 2;
    if (c == '^') return 3;
    return 0;
}

string toPostfix(string s) {
    stack<char> st;
    string out;

    for (char c : s) {
        if (c == ' ') continue;

        if (isalnum((unsigned char)c)) out += c;
        else if (c == '(') st.push(c);
        else if (c == ')') {
            while (!st.empty() && st.top() != '(') {
                out += st.top();
                st.pop();
            }
            if (!st.empty()) st.pop();
        } else {
            while (!st.empty() && st.top() != '(' &&
                   precedence(st.top()) >= precedence(c)) {
                out += st.top();
                st.pop();
            }
            st.push(c);
        }
    }

    while (!st.empty()) {
        out += st.top();
        st.pop();
    }
    return out;
}

int main() {
    cout << toPostfix("A+B*C"); // ABC*+
}
```

## Java
```java
import java.util.ArrayDeque;
import java.util.Deque;

public class Main {
    static int precedence(char c) {
        return switch (c) {
            case '+', '-' -> 1;
            case '*', '/' -> 2;
            case '^' -> 3;
            default -> 0;
        };
    }

    static String toPostfix(String s) {
        Deque<Character> st = new ArrayDeque<>();
        StringBuilder out = new StringBuilder();

        for (char c : s.toCharArray()) {
            if (c == ' ') continue;
            if (Character.isLetterOrDigit(c)) out.append(c);
            else if (c == '(') st.push(c);
            else if (c == ')') {
                while (!st.isEmpty() && st.peek() != '(') out.append(st.pop());
                if (!st.isEmpty()) st.pop();
            } else {
                while (!st.isEmpty() && st.peek() != '(' && precedence(st.peek()) >= precedence(c))
                    out.append(st.pop());
                st.push(c);
            }
        }
        while (!st.isEmpty()) out.append(st.pop());
        return out.toString();
    }

    public static void main(String[] args) {
        System.out.println(toPostfix("A+B*C")); // ABC*+
    }
}
```

## Complexity
- Time: O(n): every character is output once and each operator is pushed and popped at most once.
- Space: O(n) for the stack and the output.

Note: `^` (power) is right-associative, so `A^B^C` should give `ABC^^`. To support that, pop only when the top has strictly greater precedence for `^`: change the condition to `precedence(top) > precedence(c) || (precedence(top) == precedence(c) && c != '^')`.
MD,

];
