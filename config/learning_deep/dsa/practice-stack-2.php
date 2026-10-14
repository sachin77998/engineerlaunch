<?php

// DSA › Stack problems Q6–Q15 with full C, C++ and Java solutions.
return [

'Q6. Next Greater Element' => <<<'MD'
## Problem
For every element, find the first element to its right that is greater. If none exists, the answer is -1. For `[4, 5, 2, 25]` the answer is `[5, 25, 25, -1]`.

## Solution
Scan right to left; pop smaller or equal values and use the remaining top as the answer.

## Step by step
1. Keep a stack of candidates from the right side, decreasing from bottom to top.
2. For index i from n-1 down to 0: pop every value `<= a[i]` (they can never be the answer for anything further left, because `a[i]` is bigger and closer).
3. If the stack is empty the answer is -1, otherwise the answer is the top.
4. Push `a[i]`.

## Dry run for [4, 5, 2, 25]
| i | a[i] | Pops | Answer | Stack after |
|---|---|---|---|---|
| 3 | 25 | — | -1 | 25 |
| 2 | 2 | — | 25 | 25 2 |
| 1 | 5 | 2 | 25 | 25 5 |
| 0 | 4 | — | 5 | 25 5 4 |

## C
```c
#include <stdio.h>

void nextGreater(int a[], int n, int res[]) {
    int st[n], top = -1;
    for (int i = n - 1; i >= 0; i--) {
        while (top != -1 && st[top] <= a[i]) top--;
        res[i] = top == -1 ? -1 : st[top];
        st[++top] = a[i];
    }
}

int main(void) {
    int a[] = {4, 5, 2, 25}, n = 4, res[4];
    nextGreater(a, n, res);
    for (int i = 0; i < n; i++) printf("%d ", res[i]); // 5 25 25 -1
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <vector>
using namespace std;

vector<int> nextGreater(const vector<int>& a) {
    int n = a.size();
    vector<int> res(n);
    stack<int> st;
    for (int i = n - 1; i >= 0; i--) {
        while (!st.empty() && st.top() <= a[i]) st.pop();
        res[i] = st.empty() ? -1 : st.top();
        st.push(a[i]);
    }
    return res;
}

int main() {
    for (int x : nextGreater({4, 5, 2, 25})) cout << x << ' '; // 5 25 25 -1
}
```

## Java
```java
import java.util.*;

public class Main {
    static int[] nextGreater(int[] a) {
        int n = a.length;
        int[] res = new int[n];
        Deque<Integer> st = new ArrayDeque<>();
        for (int i = n - 1; i >= 0; i--) {
            while (!st.isEmpty() && st.peek() <= a[i]) st.pop();
            res[i] = st.isEmpty() ? -1 : st.peek();
            st.push(a[i]);
        }
        return res;
    }

    public static void main(String[] args) {
        System.out.println(Arrays.toString(nextGreater(new int[]{4, 5, 2, 25}))); // [5, 25, 25, -1]
    }
}
```

## Complexity
- Time: O(n): each element is pushed once and popped at most once.
- Space: O(n) for the stack.
MD,

'Q7. Previous Greater Element' => <<<'MD'
## Problem
For every element, find the nearest element to its left that is greater; -1 if none. For `[10, 4, 2, 20, 40, 12, 30]` the answer is `[-1, 10, 4, -1, -1, 40, 40]`.

## Solution
Scan left to right; remove smaller or equal elements from the monotonic stack.

## Step by step
1. The stack holds values seen so far in decreasing order (bottom → top).
2. For each `a[i]`, pop values `<= a[i]`.
3. The remaining top (if any) is the previous greater element; otherwise -1.
4. Push `a[i]`.

## Dry run (first five)
| a[i] | Pops | Answer | Stack after |
|---|---|---|---|
| 10 | — | -1 | 10 |
| 4 | — | 10 | 10 4 |
| 2 | — | 4 | 10 4 2 |
| 20 | 2, 4, 10 | -1 | 20 |
| 40 | 20 | -1 | 40 |

## C
```c
#include <stdio.h>

void previousGreater(int a[], int n, int res[]) {
    int st[n], top = -1;
    for (int i = 0; i < n; i++) {
        while (top != -1 && st[top] <= a[i]) top--;
        res[i] = top == -1 ? -1 : st[top];
        st[++top] = a[i];
    }
}

int main(void) {
    int a[] = {10, 4, 2, 20, 40, 12, 30}, n = 7, res[7];
    previousGreater(a, n, res);
    for (int i = 0; i < n; i++) printf("%d ", res[i]); // -1 10 4 -1 -1 40 40
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <vector>
using namespace std;

vector<int> previousGreater(const vector<int>& a) {
    vector<int> res;
    stack<int> st;
    for (int x : a) {
        while (!st.empty() && st.top() <= x) st.pop();
        res.push_back(st.empty() ? -1 : st.top());
        st.push(x);
    }
    return res;
}

int main() {
    for (int x : previousGreater({10, 4, 2, 20, 40, 12, 30})) cout << x << ' ';
}
```

## Java
```java
import java.util.*;

public class Main {
    static int[] previousGreater(int[] a) {
        int[] res = new int[a.length];
        Deque<Integer> st = new ArrayDeque<>();
        for (int i = 0; i < a.length; i++) {
            while (!st.isEmpty() && st.peek() <= a[i]) st.pop();
            res[i] = st.isEmpty() ? -1 : st.peek();
            st.push(a[i]);
        }
        return res;
    }

    public static void main(String[] args) {
        System.out.println(Arrays.toString(previousGreater(new int[]{10, 4, 2, 20, 40, 12, 30})));
    }
}
```

## Complexity
- Time: O(n). Space: O(n).
MD,

'Q8. Stock Span Problem' => <<<'MD'
## Problem
The span of a day's price is the number of consecutive days up to and including today with price `<=` today's price. For `[100, 80, 60, 70, 60, 75, 85]` the spans are `[1, 1, 1, 2, 1, 4, 6]`.

## Solution
Maintain indices in a decreasing-price stack; calculate the distance to the previous greater price.

## Step by step
1. Store indices, not prices, so the distance can be computed.
2. For day i, pop indices whose price is `<= price[i]`.
3. Span = `i + 1` if the stack is empty (all earlier prices are smaller), otherwise `i - top` (distance to the previous greater price).
4. Push i.

## Dry run
| Day | Price | Stack (indices) after | Span |
|---|---|---|---|
| 0 | 100 | 0 | 1 |
| 1 | 80 | 0 1 | 1 |
| 2 | 60 | 0 1 2 | 1 |
| 3 | 70 | 0 1 3 | 3 − 1 = 2 |
| 4 | 60 | 0 1 3 4 | 1 |
| 5 | 75 | 0 1 5 | 5 − 1 = 4 |
| 6 | 85 | 0 6 | 6 − 0 = 6 |

## C
```c
#include <stdio.h>

void stockSpan(int price[], int n, int span[]) {
    int st[n], top = -1;
    for (int i = 0; i < n; i++) {
        while (top != -1 && price[st[top]] <= price[i]) top--;
        span[i] = top == -1 ? i + 1 : i - st[top];
        st[++top] = i;
    }
}

int main(void) {
    int p[] = {100, 80, 60, 70, 60, 75, 85}, n = 7, s[7];
    stockSpan(p, n, s);
    for (int i = 0; i < n; i++) printf("%d ", s[i]); // 1 1 1 2 1 4 6
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <vector>
using namespace std;

vector<int> stockSpan(const vector<int>& price) {
    int n = price.size();
    vector<int> span(n);
    stack<int> st;
    for (int i = 0; i < n; i++) {
        while (!st.empty() && price[st.top()] <= price[i]) st.pop();
        span[i] = st.empty() ? i + 1 : i - st.top();
        st.push(i);
    }
    return span;
}

int main() {
    for (int x : stockSpan({100, 80, 60, 70, 60, 75, 85})) cout << x << ' ';
}
```

## Java
```java
import java.util.*;

public class Main {
    static int[] stockSpan(int[] price) {
        int n = price.length;
        int[] span = new int[n];
        Deque<Integer> st = new ArrayDeque<>();
        for (int i = 0; i < n; i++) {
            while (!st.isEmpty() && price[st.peek()] <= price[i]) st.pop();
            span[i] = st.isEmpty() ? i + 1 : i - st.peek();
            st.push(i);
        }
        return span;
    }

    public static void main(String[] args) {
        System.out.println(Arrays.toString(stockSpan(new int[]{100, 80, 60, 70, 60, 75, 85})));
    }
}
```

## Complexity
- Time: O(n). Space: O(n).
MD,

'Q9. Min Stack' => <<<'MD'
## Problem
Design a stack that supports push, pop, top and getMin, all in O(1).

## Solution
Store each value alongside the minimum so far, or maintain a second minimum stack.

## Step by step (two stacks)
1. `main` holds all values; `mins` holds the minimum at each depth.
2. push(x): push x to `main`; push `min(x, mins.top)` to `mins` (or x if empty).
3. pop(): pop both stacks, so `mins.top` is again the minimum of what remains.
4. getMin(): return `mins.top`.

## Dry run
| Operation | main | mins | getMin |
|---|---|---|---|
| push 5 | 5 | 5 | 5 |
| push 3 | 5 3 | 5 3 | 3 |
| push 7 | 5 3 7 | 5 3 3 | 3 |
| pop | 5 3 | 5 3 | 3 |
| pop | 5 | 5 | 5 |

## C
```c
#include <stdio.h>
#define MAX 100

int mainSt[MAX], minSt[MAX], top = -1;

void push(int x) {
    mainSt[++top] = x;
    minSt[top] = (top == 0 || x < minSt[top - 1]) ? x : minSt[top - 1];
}
void pop(void) { if (top >= 0) top--; }
int peek(void) { return mainSt[top]; }
int getMin(void) { return minSt[top]; }

int main(void) {
    push(5); push(3); push(7);
    printf("%d\n", getMin()); // 3
    pop(); pop();
    printf("%d\n", getMin()); // 5
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
using namespace std;

class MinStack {
    stack<int> st, mins;
public:
    void push(int x) {
        st.push(x);
        mins.push(mins.empty() ? x : min(x, mins.top()));
    }
    void pop() { st.pop(); mins.pop(); }
    int top() { return st.top(); }
    int getMin() { return mins.top(); }
};

int main() {
    MinStack s;
    s.push(5); s.push(3); s.push(7);
    cout << s.getMin() << '\n'; // 3
    s.pop(); s.pop();
    cout << s.getMin() << '\n'; // 5
}
```

## Java
```java
import java.util.*;

class MinStack {
    private final Deque<Integer> st = new ArrayDeque<>();
    private final Deque<Integer> mins = new ArrayDeque<>();

    void push(int x) {
        st.push(x);
        mins.push(mins.isEmpty() ? x : Math.min(x, mins.peek()));
    }
    void pop() { st.pop(); mins.pop(); }
    int top() { return st.peek(); }
    int getMin() { return mins.peek(); }
}

public class Main {
    public static void main(String[] args) {
        MinStack s = new MinStack();
        s.push(5); s.push(3); s.push(7);
        System.out.println(s.getMin()); // 3
        s.pop(); s.pop();
        System.out.println(s.getMin()); // 5
    }
}
```

## Complexity
- Time: O(1) per operation.
- Space: O(n) extra for the minimum stack. (An O(1)-extra-space variant stores `2x − min` when a new minimum arrives.)
MD,

'Q10. Sort a Stack' => <<<'MD'
## Problem
Sort a stack so the largest element is on top, using only another stack. `[34, 3, 31, 98, 92, 23]` (bottom → top) becomes `[3, 23, 31, 34, 92, 98]`.

## Solution
Use a second stack to insert each element in sorted order.

## Step by step
1. `sorted` keeps elements in ascending order bottom → top.
2. Pop `tmp` from the input.
3. While `sorted.top > tmp`, move that element back to the input stack (it will be re-inserted later).
4. Push `tmp` onto `sorted`. Repeat until the input is empty.

## Dry run (input top is 23)
| tmp | Moved back to input | sorted (bottom → top) |
|---|---|---|
| 23 | — | 23 |
| 92 | — | 23 92 |
| 98 | — | 23 92 98 |
| 31 | 98, 92 | 23 31 → then 92, 98 return |
| … | … | 3 23 31 34 92 98 |

## C
```c
#include <stdio.h>

int sortStack(int in[], int n, int out[]) {
    int inTop = n - 1, outTop = -1;
    while (inTop >= 0) {
        int tmp = in[inTop--];
        while (outTop >= 0 && out[outTop] > tmp) in[++inTop] = out[outTop--];
        out[++outTop] = tmp;
    }
    return outTop + 1;
}

int main(void) {
    int in[] = {34, 3, 31, 98, 92, 23}, out[6];
    int n = sortStack(in, 6, out);
    for (int i = 0; i < n; i++) printf("%d ", out[i]); // 3 23 31 34 92 98
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
using namespace std;

stack<int> sortStack(stack<int> in) {
    stack<int> sorted;
    while (!in.empty()) {
        int tmp = in.top(); in.pop();
        while (!sorted.empty() && sorted.top() > tmp) {
            in.push(sorted.top());
            sorted.pop();
        }
        sorted.push(tmp);
    }
    return sorted;
}

int main() {
    stack<int> s;
    for (int x : {34, 3, 31, 98, 92, 23}) s.push(x);
    stack<int> r = sortStack(s);
    while (!r.empty()) { cout << r.top() << ' '; r.pop(); } // 98 92 34 31 23 3
}
```

## Java
```java
import java.util.*;

public class Main {
    static Deque<Integer> sortStack(Deque<Integer> in) {
        Deque<Integer> sorted = new ArrayDeque<>();
        while (!in.isEmpty()) {
            int tmp = in.pop();
            while (!sorted.isEmpty() && sorted.peek() > tmp) in.push(sorted.pop());
            sorted.push(tmp);
        }
        return sorted;
    }

    public static void main(String[] args) {
        Deque<Integer> s = new ArrayDeque<>();
        for (int x : new int[]{34, 3, 31, 98, 92, 23}) s.push(x);
        System.out.println(sortStack(s)); // [98, 92, 34, 31, 23, 3] (top first)
    }
}
```

## Complexity
- Time: O(n²) in the worst case (each insertion may move all sorted elements).
- Space: O(n) for the second stack.
MD,

'Q11. Reverse a Stack Recursively' => <<<'MD'
## Problem
Reverse a stack using recursion only (no loops, no extra data structure apart from the call stack).

## Solution
Remove the top, reverse the remaining stack and insert the removed item at the bottom.

## Step by step
1. `reverse(st)`: if empty, return. Otherwise pop `x`, call `reverse(st)`, then `insertAtBottom(st, x)`.
2. `insertAtBottom(st, x)`: if empty, push x. Otherwise pop `y`, call `insertAtBottom(st, x)`, then push `y` back.

## Dry run for 1, 2, 3 pushed in order (3 on top)
| Step | Action | Stack (bottom → top) |
|---|---|---|
| 1 | reverse pops 3, then 2, then 1 (recursion unwinds from the innermost call) | empty |
| 2 | insertAtBottom(1) | 1 |
| 3 | insertAtBottom(2) | 2 1 |
| 4 | insertAtBottom(3) | 3 2 1, so 1 is now on top |

## C
```c
#include <stdio.h>
#define MAX 100

int st[MAX], top = -1;

void push(int x) { st[++top] = x; }
int pop(void) { return st[top--]; }
int isEmpty(void) { return top == -1; }

void insertAtBottom(int x) {
    if (isEmpty()) { push(x); return; }
    int y = pop();
    insertAtBottom(x);
    push(y);
}

void reverse(void) {
    if (isEmpty()) return;
    int x = pop();
    reverse();
    insertAtBottom(x);
}

int main(void) {
    push(1); push(2); push(3);
    reverse();
    while (!isEmpty()) printf("%d ", pop()); // 1 2 3
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
using namespace std;

void insertAtBottom(stack<int>& st, int x) {
    if (st.empty()) { st.push(x); return; }
    int y = st.top(); st.pop();
    insertAtBottom(st, x);
    st.push(y);
}

void reverseStack(stack<int>& st) {
    if (st.empty()) return;
    int x = st.top(); st.pop();
    reverseStack(st);
    insertAtBottom(st, x);
}

int main() {
    stack<int> st;
    for (int x : {1, 2, 3}) st.push(x);
    reverseStack(st);
    while (!st.empty()) { cout << st.top() << ' '; st.pop(); } // 1 2 3
}
```

## Java
```java
import java.util.*;

public class Main {
    static void insertAtBottom(Deque<Integer> st, int x) {
        if (st.isEmpty()) { st.push(x); return; }
        int y = st.pop();
        insertAtBottom(st, x);
        st.push(y);
    }

    static void reverse(Deque<Integer> st) {
        if (st.isEmpty()) return;
        int x = st.pop();
        reverse(st);
        insertAtBottom(st, x);
    }

    public static void main(String[] args) {
        Deque<Integer> st = new ArrayDeque<>();
        for (int x : new int[]{1, 2, 3}) st.push(x);
        reverse(st);
        System.out.println(st); // [1, 2, 3] (top first)
    }
}
```

## Complexity
- Time: O(n²): each of the n insertions at the bottom costs up to O(n).
- Space: O(n) recursion depth.
MD,

'Q12. Largest Rectangle in a Histogram' => <<<'MD'
## Problem
Bars of width 1 have heights `[2, 1, 5, 6, 2, 3]`. Find the largest rectangle area: here 10 (heights 5 and 6, width 2).

## Solution
Maintain increasing bar indices; when a shorter bar appears, calculate rectangle areas.

## Step by step
1. The stack holds indices of bars with increasing heights.
2. For each index i (and one extra step with height 0 at i = n to flush the stack): while the current height is less than the height at the top, pop index `h`.
3. The popped bar is the shortest in its rectangle. It extends right up to i − 1 and left to just after the new top: width = `i` if the stack is empty, otherwise `i - top - 1`.
4. Area = `height[h] × width`; keep the maximum. Then push i.

## Dry run (key pops)
| i | height | Popped | Width | Area |
|---|---|---|---|---|
| 1 | 1 | 0 (h=2) | 1 | 2 |
| 4 | 2 | 3 (h=6) | 4 − 2 − 1 = 1 | 6 |
| 4 | 2 | 2 (h=5) | 4 − 1 − 1 = 2 | 10 |
| 6 | 0 | 5, 4, 1 | … | 3, 8, 6 |

## C
```c
#include <stdio.h>

int largestRectangle(int h[], int n) {
    int st[n + 1], top = -1, best = 0;
    for (int i = 0; i <= n; i++) {
        int cur = i == n ? 0 : h[i];
        while (top != -1 && cur < h[st[top]]) {
            int height = h[st[top--]];
            int width = top == -1 ? i : i - st[top] - 1;
            if (height * width > best) best = height * width;
        }
        st[++top] = i;
    }
    return best;
}

int main(void) {
    int h[] = {2, 1, 5, 6, 2, 3};
    printf("%d\n", largestRectangle(h, 6)); // 10
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <vector>
using namespace std;

int largestRectangle(const vector<int>& h) {
    int n = h.size(), best = 0;
    stack<int> st;
    for (int i = 0; i <= n; i++) {
        int cur = i == n ? 0 : h[i];
        while (!st.empty() && cur < h[st.top()]) {
            int height = h[st.top()]; st.pop();
            int width = st.empty() ? i : i - st.top() - 1;
            best = max(best, height * width);
        }
        st.push(i);
    }
    return best;
}

int main() {
    cout << largestRectangle({2, 1, 5, 6, 2, 3}); // 10
}
```

## Java
```java
import java.util.*;

public class Main {
    static int largestRectangle(int[] h) {
        int n = h.length, best = 0;
        Deque<Integer> st = new ArrayDeque<>();
        for (int i = 0; i <= n; i++) {
            int cur = i == n ? 0 : h[i];
            while (!st.isEmpty() && cur < h[st.peek()]) {
                int height = h[st.pop()];
                int width = st.isEmpty() ? i : i - st.peek() - 1;
                best = Math.max(best, height * width);
            }
            st.push(i);
        }
        return best;
    }

    public static void main(String[] args) {
        System.out.println(largestRectangle(new int[]{2, 1, 5, 6, 2, 3})); // 10
    }
}
```

## Complexity
- Time: O(n): each index is pushed and popped once.
- Space: O(n).
MD,

'Q13. Trapping Rain Water Using a Stack' => <<<'MD'
## Problem
Given bar heights `[0, 1, 0, 2, 1, 0, 1, 3, 2, 1, 2, 1]`, compute the trapped water: 6 units.

## Solution
When a taller right boundary appears, calculate bounded water for popped bars.

## Step by step
1. The stack holds indices of bars with decreasing heights (potential left walls).
2. For each i: while `height[i] > height[top]`, pop the top as the `bottom` of a pit.
3. If the stack becomes empty there is no left wall, so stop. Otherwise left wall = new top.
4. Water over that pit = `(min(height[left], height[i]) − height[bottom]) × (i − left − 1)`.
5. Push i.

## Dry run (first water)
| i | height | Pop bottom | Left wall | Bounded height | Width | Water |
|---|---|---|---|---|---|---|
| 3 | 2 | index 2 (0) | index 1 (1) | min(1,2) − 0 = 1 | 1 | 1 |
| 3 | 2 | index 1 (1) | none | — | — | stop |
| 6 | 1 | index 5 (0) | index 4 (1) | 1 | 1 | 1 |
| 7 | 3 | index 6, 4 … | index 3 | … | … | total 6 |

## C
```c
#include <stdio.h>

int trap(int h[], int n) {
    int st[n], top = -1, water = 0;
    for (int i = 0; i < n; i++) {
        while (top != -1 && h[i] > h[st[top]]) {
            int bottom = st[top--];
            if (top == -1) break;
            int left = st[top];
            int bounded = (h[left] < h[i] ? h[left] : h[i]) - h[bottom];
            water += bounded * (i - left - 1);
        }
        st[++top] = i;
    }
    return water;
}

int main(void) {
    int h[] = {0, 1, 0, 2, 1, 0, 1, 3, 2, 1, 2, 1};
    printf("%d\n", trap(h, 12)); // 6
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <vector>
using namespace std;

int trap(const vector<int>& h) {
    stack<int> st;
    int water = 0;
    for (int i = 0; i < (int) h.size(); i++) {
        while (!st.empty() && h[i] > h[st.top()]) {
            int bottom = st.top(); st.pop();
            if (st.empty()) break;
            int left = st.top();
            int bounded = min(h[left], h[i]) - h[bottom];
            water += bounded * (i - left - 1);
        }
        st.push(i);
    }
    return water;
}

int main() {
    cout << trap({0, 1, 0, 2, 1, 0, 1, 3, 2, 1, 2, 1}); // 6
}
```

## Java
```java
import java.util.*;

public class Main {
    static int trap(int[] h) {
        Deque<Integer> st = new ArrayDeque<>();
        int water = 0;
        for (int i = 0; i < h.length; i++) {
            while (!st.isEmpty() && h[i] > h[st.peek()]) {
                int bottom = st.pop();
                if (st.isEmpty()) break;
                int left = st.peek();
                int bounded = Math.min(h[left], h[i]) - h[bottom];
                water += bounded * (i - left - 1);
            }
            st.push(i);
        }
        return water;
    }

    public static void main(String[] args) {
        System.out.println(trap(new int[]{0, 1, 0, 2, 1, 0, 1, 3, 2, 1, 2, 1})); // 6
    }
}
```

## Complexity
- Time: O(n). Space: O(n). (A two-pointer version needs only O(1) space.)
MD,

'Q14. Celebrity Problem' => <<<'MD'
## Problem
In a party of n people, `M[i][j] = 1` means i knows j. A celebrity is known by everyone and knows nobody. Find the celebrity, or return -1.

## Solution
Eliminate candidates using pairwise knowledge checks, then verify the remaining candidate.

## Step by step
1. Push everyone 0..n-1 onto a stack.
2. While more than one person is on the stack, pop `a` and `b`. If `a` knows `b`, then `a` cannot be the celebrity, so push `b`; otherwise `b` is not known by `a` and cannot be the celebrity, so push `a`.
3. The last person `c` is the only possible celebrity. Verify: for every `i != c`, `M[c][i]` must be 0 and `M[i][c]` must be 1.

## Example
`M = {{0,1,0},{0,0,0},{0,1,0}}`: person 1 is known by 0 and 2 and knows nobody, so the answer is 1.

## C
```c
#include <stdio.h>
#define N 3

int celebrity(int M[N][N], int n) {
    int st[N], top = -1;
    for (int i = 0; i < n; i++) st[++top] = i;
    while (top > 0) {
        int a = st[top--], b = st[top--];
        st[++top] = M[a][b] ? b : a;
    }
    int c = st[top];
    for (int i = 0; i < n; i++)
        if (i != c && (M[c][i] || !M[i][c])) return -1;
    return c;
}

int main(void) {
    int M[N][N] = {{0, 1, 0}, {0, 0, 0}, {0, 1, 0}};
    printf("%d\n", celebrity(M, N)); // 1
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <vector>
using namespace std;

int celebrity(const vector<vector<int>>& M) {
    int n = M.size();
    stack<int> st;
    for (int i = 0; i < n; i++) st.push(i);
    while (st.size() > 1) {
        int a = st.top(); st.pop();
        int b = st.top(); st.pop();
        st.push(M[a][b] ? b : a);
    }
    int c = st.top();
    for (int i = 0; i < n; i++)
        if (i != c && (M[c][i] || !M[i][c])) return -1;
    return c;
}

int main() {
    cout << celebrity({{0, 1, 0}, {0, 0, 0}, {0, 1, 0}}); // 1
}
```

## Java
```java
import java.util.*;

public class Main {
    static int celebrity(int[][] M) {
        int n = M.length;
        Deque<Integer> st = new ArrayDeque<>();
        for (int i = 0; i < n; i++) st.push(i);
        while (st.size() > 1) {
            int a = st.pop(), b = st.pop();
            st.push(M[a][b] == 1 ? b : a);
        }
        int c = st.pop();
        for (int i = 0; i < n; i++)
            if (i != c && (M[c][i] == 1 || M[i][c] == 0)) return -1;
        return c;
    }

    public static void main(String[] args) {
        System.out.println(celebrity(new int[][]{{0, 1, 0}, {0, 0, 0}, {0, 1, 0}})); // 1
    }
}
```

## Complexity
- Time: O(n): n − 1 eliminations plus a 2n verification.
- Space: O(n) for the stack (a two-pointer version uses O(1)).
MD,

'Q15. Decode an Encoded String' => <<<'MD'
## Problem
Decode `k[encoded]`, meaning `encoded` repeated k times, with nesting allowed. `3[a2[c]]` → `accaccacc`.

## Solution
Use stacks for repeat counts and partial strings when processing brackets such as `3[a2[c]]`.

## Step by step
1. Keep `cur` (string being built) and `k` (number being read), plus two stacks: counts and previous strings.
2. Digit: `k = k × 10 + digit` (counts can have several digits).
3. `[`: push `k` and `cur`, then reset both.
4. `]`: pop `count` and `prev`; set `cur = prev + cur repeated count times`.
5. Letter: append it to `cur`.

## Dry run for 3[a2[c]]
| Char | counts | strings | cur |
|---|---|---|---|
| 3 [ | 3 | "" | "" |
| a | 3 | "" | a |
| 2 [ | 3 2 | "" a | "" |
| c | 3 2 | "" a | c |
| ] | 3 | "" | a + cc = acc |
| ] | — | — | "" + acc × 3 = accaccacc |

## C
```c
#include <stdio.h>
#include <string.h>
#include <ctype.h>

#define MAX 1000

char *decode(const char *s, char *out) {
    int counts[MAX], ct = -1;
    char prev[MAX][MAX];
    int pt = -1;
    char cur[MAX] = "";
    int k = 0;

    for (int i = 0; s[i]; i++) {
        char c = s[i];
        if (isdigit((unsigned char) c)) {
            k = k * 10 + (c - '0');
        } else if (c == '[') {
            counts[++ct] = k;
            strcpy(prev[++pt], cur);
            cur[0] = '\0';
            k = 0;
        } else if (c == ']') {
            char built[MAX];
            strcpy(built, prev[pt--]);
            for (int r = counts[ct]; r > 0; r--) strcat(built, cur);
            ct--;
            strcpy(cur, built);
        } else {
            size_t len = strlen(cur);
            cur[len] = c;
            cur[len + 1] = '\0';
        }
    }
    strcpy(out, cur);
    return out;
}

int main(void) {
    char out[MAX];
    printf("%s\n", decode("3[a2[c]]", out)); // accaccacc
    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <stack>
#include <string>
using namespace std;

string decode(const string& s) {
    stack<int> counts;
    stack<string> prev;
    string cur;
    int k = 0;
    for (char c : s) {
        if (isdigit((unsigned char) c)) k = k * 10 + (c - '0');
        else if (c == '[') { counts.push(k); prev.push(cur); cur.clear(); k = 0; }
        else if (c == ']') {
            string built = prev.top(); prev.pop();
            for (int r = counts.top(); r > 0; r--) built += cur;
            counts.pop();
            cur = built;
        } else cur += c;
    }
    return cur;
}

int main() {
    cout << decode("3[a2[c]]"); // accaccacc
}
```

## Java
```java
import java.util.*;

public class Main {
    static String decode(String s) {
        Deque<Integer> counts = new ArrayDeque<>();
        Deque<StringBuilder> prev = new ArrayDeque<>();
        StringBuilder cur = new StringBuilder();
        int k = 0;
        for (char c : s.toCharArray()) {
            if (Character.isDigit(c)) k = k * 10 + (c - '0');
            else if (c == '[') { counts.push(k); prev.push(cur); cur = new StringBuilder(); k = 0; }
            else if (c == ']') {
                StringBuilder built = prev.pop();
                built.append(cur.toString().repeat(counts.pop()));
                cur = built;
            } else cur.append(c);
        }
        return cur.toString();
    }

    public static void main(String[] args) {
        System.out.println(decode("3[a2[c]]")); // accaccacc
    }
}
```

## Complexity
- Time: O(n + m), where m is the output size (every output character is written once per enclosing level).
- Space: O(n + m) for the stacks and the result.
MD,

];
