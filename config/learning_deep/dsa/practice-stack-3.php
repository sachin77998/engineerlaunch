<?php

// DSA › Stack problems: Q6–Q15 together in one runnable program per language.
return [

'Q6–Q15. All solutions in one program (C, C++, Java)' => <<<'MD'
## Complexity summary
| Question | Time | Auxiliary space |
|---|---|---|
| Q6 Next Greater Element | O(n) | O(n) |
| Q7 Previous Greater Element | O(n) | O(n) |
| Q8 Stock Span | O(n) | O(n) |
| Q9 Min Stack | O(1) per operation | O(n) total |
| Q10 Sort Stack | O(n²) | O(n) |
| Q11 Reverse Stack Recursively | O(n²) | O(n) call stack |
| Q12 Largest Rectangle | O(n) | O(n) |
| Q13 Trapping Rain Water | O(n) | O(n) |
| Q14 Celebrity | O(n) | O(1) auxiliary |
| Q15 Decode String | O(L) for ordinary bounded repetitions; output construction affects the actual cost | O(L) |

## Language implementation guide
- C: arrays for both the input and the stack, with an integer `top`.
- C++: `std::stack<int>` or `std::vector<int>` as the stack.
- Java: `Deque<Integer>` backed by `ArrayDeque<Integer>`.

Note: Q14 here finds the candidate in one pass without a stack (if the candidate knows i, i becomes the candidate), so it needs only O(1) extra space; the stack version in the Q14 answer gives the same result.

## Expected output (all three programs)
```text
Q6 Next Greater: 5 10 10 -1 -1
Q7 Previous Greater: -1 -1 5 -1 10
Q8 Stock Span: 1 1 1 2 1 4 6
Q9 Minimum: 2
Q10 Sorted stack (top first): 1 2 3 4
Q11 Reversed stack (top first): 1 2 3 4
Q12 Largest Rectangle: 10
Q13 Trapped Water: 6
Q14 Celebrity Index: 2
Q15 Decoded String: accaccacc
```

## C
```c
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

#define MAX 10000
#define PEOPLE 100   /* celebrity matrix size: a MAX x MAX int matrix would need about 400 MB */

/* Q6. Next Greater Element */
void nextGreater(int a[], int n, int result[]) {
    int st[MAX], top = -1;

    for (int i = n - 1; i >= 0; i--) {
        while (top >= 0 && st[top] <= a[i])
            top--;

        result[i] = (top == -1) ? -1 : st[top];
        st[++top] = a[i];
    }
}

/* Q7. Previous Greater Element */
void previousGreater(int a[], int n, int result[]) {
    int st[MAX], top = -1;

    for (int i = 0; i < n; i++) {
        while (top >= 0 && st[top] <= a[i])
            top--;

        result[i] = (top == -1) ? -1 : st[top];
        st[++top] = a[i];
    }
}

/* Q8. Stock Span */
void stockSpan(int prices[], int n, int span[]) {
    int st[MAX], top = -1;

    for (int i = 0; i < n; i++) {
        while (top >= 0 && prices[st[top]] <= prices[i])
            top--;

        span[i] = (top == -1) ? i + 1 : i - st[top];
        st[++top] = i;
    }
}

/* Q9. Min Stack */
typedef struct {
    int value[MAX];
    int minimum[MAX];
    int top;
} MinStack;

void minStackInit(MinStack *s) {
    s->top = -1;
}

int minStackPush(MinStack *s, int x) {
    if (s->top == MAX - 1) return 0;

    s->top++;
    s->value[s->top] = x;

    if (s->top == 0 || x < s->minimum[s->top - 1])
        s->minimum[s->top] = x;
    else
        s->minimum[s->top] = s->minimum[s->top - 1];

    return 1;
}

int minStackPop(MinStack *s, int *out) {
    if (s->top < 0) return 0;
    *out = s->value[s->top--];
    return 1;
}

int minStackTop(MinStack *s, int *out) {
    if (s->top < 0) return 0;
    *out = s->value[s->top];
    return 1;
}

int minStackGetMin(MinStack *s, int *out) {
    if (s->top < 0) return 0;
    *out = s->minimum[s->top];
    return 1;
}

/* Q10. Sort a stack in ascending order, smallest on top */
void sortStack(int a[], int *top) {
    int temp[MAX], tempTop = -1;

    while (*top >= 0) {
        int x = a[(*top)--];

        while (tempTop >= 0 && temp[tempTop] > x)
            a[++(*top)] = temp[tempTop--];

        temp[++tempTop] = x;
    }

    while (tempTop >= 0)
        a[++(*top)] = temp[tempTop--];
}

/* Q11. Reverse a stack recursively */
void insertAtBottom(int a[], int *top, int x) {
    if (*top == -1) {
        a[++(*top)] = x;
        return;
    }

    int saved = a[(*top)--];
    insertAtBottom(a, top, x);
    a[++(*top)] = saved;
}

void reverseStackRecursive(int a[], int *top) {
    if (*top == -1) return;

    int saved = a[(*top)--];
    reverseStackRecursive(a, top);
    insertAtBottom(a, top, saved);
}

/* Q12. Largest Rectangle in Histogram */
long long largestRectangle(int h[], int n) {
    int st[MAX], top = -1;
    long long best = 0;

    for (int i = 0; i <= n; i++) {
        int current = (i == n) ? 0 : h[i];

        while (top >= 0 && h[st[top]] > current) {
            int height = h[st[top--]];
            int width = (top == -1) ? i : i - st[top] - 1;
            long long area = (long long)height * width;

            if (area > best) best = area;
        }

        if (i < n) st[++top] = i;
    }

    return best;
}

/* Q13. Trapping Rain Water using a stack */
long long trappedWater(int h[], int n) {
    int st[MAX], top = -1;
    long long water = 0;

    for (int i = 0; i < n; i++) {
        while (top >= 0 && h[i] > h[st[top]]) {
            int bottom = st[top--];

            if (top < 0) break;

            int width = i - st[top] - 1;
            int boundedHeight = h[i] < h[st[top]]
                              ? h[i] - h[bottom]
                              : h[st[top]] - h[bottom];

            water += (long long)width * boundedHeight;
        }

        st[++top] = i;
    }

    return water;
}

/* Q14. Celebrity Problem
   knows[a][b] == 1 means person a knows person b.
   Returns celebrity index, or -1 if none exists.
*/
int celebrity(int n, int knows[PEOPLE][PEOPLE]) {
    if (n <= 0) return -1;

    int candidate = 0;

    for (int i = 1; i < n; i++) {
        if (knows[candidate][i] == 1)
            candidate = i;
    }

    for (int i = 0; i < n; i++) {
        if (i == candidate) continue;

        if (knows[candidate][i] == 1 ||
            knows[i][candidate] == 0)
            return -1;
    }

    return candidate;
}

/* Q15. Decode encoded string, e.g. 3[a2[c]] -> accaccacc */
#define DECODE_MAX 10000
#define NEST_MAX 1000

int decodeString(const char *input, char *output, size_t outputSize) {
    int counts[NEST_MAX];
    static char prefixes[NEST_MAX][DECODE_MAX];   /* static: about 10 MB would overflow the call stack */
    static char inner[DECODE_MAX];
    int top = -1;
    int number = 0;
    size_t currentLen = 0;

    output[0] = '\0';

    for (size_t i = 0; input[i] != '\0'; i++) {
        char ch = input[i];

        if (isdigit((unsigned char)ch)) {
            if (number > (DECODE_MAX - 1) / 10)
                return 0;
            number = number * 10 + (ch - '0');
        } else if (ch == '[') {
            if (top + 1 >= NEST_MAX)
                return 0;

            top++;
            counts[top] = number;
            strcpy(prefixes[top], output);
            number = 0;
            output[0] = '\0';
            currentLen = 0;
        } else if (ch == ']') {
            if (top < 0) return 0;

            strcpy(inner, output);

            size_t prefixLen = strlen(prefixes[top]);
            size_t innerLen = strlen(inner);
            int repeat = counts[top];

            if (prefixLen + innerLen * (size_t)repeat >= outputSize)
                return 0;

            strcpy(output, prefixes[top]);
            currentLen = prefixLen;

            for (int r = 0; r < repeat; r++) {
                memcpy(output + currentLen, inner, innerLen);
                currentLen += innerLen;
                output[currentLen] = '\0';
            }

            top--;
        } else {
            if (currentLen + 1 >= outputSize)
                return 0;

            output[currentLen++] = ch;
            output[currentLen] = '\0';
        }
    }

    return top == -1;
}

void printArray(const char *label, int a[], int n) {
    printf("%s", label);
    for (int i = 0; i < n; i++)
        printf("%d ", a[i]);
    printf("\n");
}

/* Stacks are stored bottom-to-top in the array, so print from the end for "top first". */
void printStackTopFirst(const char *label, int a[], int top) {
    printf("%s", label);
    for (int i = top; i >= 0; i--)
        printf("%d ", a[i]);
    printf("\n");
}

int main(void) {
    int a[] = {4, 5, 2, 10, 8};
    int n = (int)(sizeof(a) / sizeof(a[0]));
    int result[MAX];

    nextGreater(a, n, result);
    printArray("Q6 Next Greater: ", result, n);

    previousGreater(a, n, result);
    printArray("Q7 Previous Greater: ", result, n);

    int prices[] = {100, 80, 60, 70, 60, 75, 85};
    int pn = (int)(sizeof(prices) / sizeof(prices[0]));
    int span[MAX];

    stockSpan(prices, pn, span);
    printArray("Q8 Stock Span: ", span, pn);

    static MinStack ms;
    int value;
    minStackInit(&ms);
    minStackPush(&ms, 5);
    minStackPush(&ms, 2);
    minStackPush(&ms, 8);

    if (minStackGetMin(&ms, &value))
        printf("Q9 Minimum: %d\n", value);

    int s[] = {3, 1, 4, 2};          /* pushed in this order: 2 is on top */
    int top = 3;
    sortStack(s, &top);
    printStackTopFirst("Q10 Sorted stack (top first): ", s, top);

    int r[] = {1, 2, 3, 4};          /* 4 is on top */
    top = 3;
    reverseStackRecursive(r, &top);
    printStackTopFirst("Q11 Reversed stack (top first): ", r, top);

    int hist[] = {2, 1, 5, 6, 2, 3};
    int hn = (int)(sizeof(hist) / sizeof(hist[0]));
    printf("Q12 Largest Rectangle: %lld\n", largestRectangle(hist, hn));

    int bars[] = {0, 1, 0, 2, 1, 0, 1, 3, 2, 1, 2, 1};
    int bn = (int)(sizeof(bars) / sizeof(bars[0]));
    printf("Q13 Trapped Water: %lld\n", trappedWater(bars, bn));

    static int knows[PEOPLE][PEOPLE] = {
        {0, 1, 1},
        {0, 0, 1},
        {0, 0, 0}
    };
    printf("Q14 Celebrity Index: %d\n", celebrity(3, knows));

    static char decoded[DECODE_MAX];
    if (decodeString("3[a2[c]]", decoded, sizeof(decoded)))
        printf("Q15 Decoded String: %s\n", decoded);
    else
        printf("Q15 Decode failed: invalid input or output too large\n");

    return 0;
}
```

## C++
```cpp
#include <iostream>
#include <vector>
#include <stack>
#include <string>
#include <algorithm>
#include <cctype>
#include <stdexcept>
using namespace std;

// Q6. Next Greater Element to the right
vector<int> nextGreater(vector<int> a) {
    int n = a.size();
    vector<int> ans(n, -1);
    stack<int> st;

    for (int i = n - 1; i >= 0; --i) {
        while (!st.empty() && st.top() <= a[i])
            st.pop();
        if (!st.empty()) ans[i] = st.top();
        st.push(a[i]);
    }
    return ans;
}

// Q7. Previous Greater Element
vector<int> previousGreater(vector<int> a) {
    vector<int> ans;
    stack<int> st;

    for (int x : a) {
        while (!st.empty() && st.top() <= x)
            st.pop();
        ans.push_back(st.empty() ? -1 : st.top());
        st.push(x);
    }
    return ans;
}

// Q8. Stock Span
vector<int> stockSpan(vector<int> prices) {
    int n = prices.size();
    vector<int> span(n);
    stack<int> st; // indices

    for (int i = 0; i < n; ++i) {
        while (!st.empty() && prices[st.top()] <= prices[i])
            st.pop();
        span[i] = st.empty() ? i + 1 : i - st.top();
        st.push(i);
    }
    return span;
}

// Q9. Min Stack
class MinStack {
    stack<pair<int, int>> st; // {value, minimum so far}
public:
    void push(int x) {
        int mn = st.empty() ? x : min(x, st.top().second);
        st.push({x, mn});
    }
    void pop() {
        if (!st.empty()) st.pop();
    }
    int top() {
        if (st.empty()) throw runtime_error("Stack is empty");
        return st.top().first;
    }
    int getMin() {
        if (st.empty()) throw runtime_error("Stack is empty");
        return st.top().second;
    }
    bool empty() { return st.empty(); }
};

// Q10. Sort a stack in ascending order (smallest on top)
void sortStack(stack<int>& st) {
    stack<int> temp;
    while (!st.empty()) {
        int x = st.top();
        st.pop();
        while (!temp.empty() && temp.top() > x) {
            st.push(temp.top());
            temp.pop();
        }
        temp.push(x);
    }
    while (!temp.empty()) {
        st.push(temp.top());
        temp.pop();
    }
}

// Q11. Reverse stack recursively
void insertAtBottom(stack<int>& st, int x) {
    if (st.empty()) {
        st.push(x);
        return;
    }
    int top = st.top();
    st.pop();
    insertAtBottom(st, x);
    st.push(top);
}
void reverseStack(stack<int>& st) {
    if (st.empty()) return;
    int x = st.top();
    st.pop();
    reverseStack(st);
    insertAtBottom(st, x);
}

// Q12. Largest Rectangle in Histogram
long long largestRectangle(vector<int> h) {
    stack<int> st;
    long long best = 0;
    int n = h.size();

    for (int i = 0; i <= n; ++i) {
        int current = (i == n ? 0 : h[i]);
        while (!st.empty() && h[st.top()] > current) {
            int height = h[st.top()];
            st.pop();
            int width = st.empty() ? i : i - st.top() - 1;
            best = max(best, 1LL * height * width);
        }
        st.push(i);
    }
    return best;
}

// Q13. Trapping Rain Water using a stack
long long trappedWater(vector<int> h) {
    stack<int> st;
    long long water = 0;

    for (int i = 0; i < (int)h.size(); ++i) {
        while (!st.empty() && h[i] > h[st.top()]) {
            int bottom = st.top();
            st.pop();
            if (st.empty()) break;

            int width = i - st.top() - 1;
            int boundedHeight = min(h[i], h[st.top()]) - h[bottom];
            water += 1LL * width * boundedHeight;
        }
        st.push(i);
    }
    return water;
}

// Q14. Celebrity Problem
// knows[a][b] == 1 means person a knows person b.
int celebrity(vector<vector<int>> knows) {
    int n = knows.size();
    if (n == 0) return -1;

    int candidate = 0;
    for (int i = 1; i < n; ++i) {
        if (knows[candidate][i] == 1)
            candidate = i;
    }

    for (int i = 0; i < n; ++i) {
        if (i == candidate) continue;
        if (knows[candidate][i] == 1 || knows[i][candidate] == 0)
            return -1;
    }
    return candidate;
}

// Q15. Decode strings such as "3[a2[c]]" -> "accaccacc"
string decodeString(const string& s) {
    stack<int> counts;
    stack<string> previous;
    string current;
    int number = 0;

    for (char ch : s) {
        if (isdigit((unsigned char)ch)) {
            number = number * 10 + (ch - '0');
        } else if (ch == '[') {
            counts.push(number);
            previous.push(current);
            number = 0;
            current.clear();
        } else if (ch == ']') {
            int repeat = counts.top();
            counts.pop();
            string prefix = previous.top();
            previous.pop();

            while (repeat-- > 0) prefix += current;
            current = prefix;
        } else {
            current += ch;
        }
    }
    return current;
}

template<class T>
void printVector(const vector<T>& v) {
    for (const auto& x : v) cout << x << " ";
    cout << '\n';
}

int main() {
    vector<int> a = {4, 5, 2, 10, 8};

    cout << "Q6 Next greater: ";
    printVector(nextGreater(a));

    cout << "Q7 Previous greater: ";
    printVector(previousGreater(a));

    cout << "Q8 Stock span: ";
    printVector(stockSpan({100, 80, 60, 70, 60, 75, 85}));

    MinStack ms;
    ms.push(5);
    ms.push(2);
    ms.push(8);
    cout << "Q9 Minimum: " << ms.getMin() << '\n';

    stack<int> s;
    for (int x : {3, 1, 4, 2}) s.push(x);
    sortStack(s);
    cout << "Q10 Sorted stack, top first: ";
    while (!s.empty()) { cout << s.top() << " "; s.pop(); }
    cout << '\n';

    stack<int> r;
    for (int x : {1, 2, 3, 4}) r.push(x);
    reverseStack(r);
    cout << "Q11 Reversed stack, top first: ";
    while (!r.empty()) { cout << r.top() << " "; r.pop(); }
    cout << '\n';

    cout << "Q12 Largest rectangle: "
         << largestRectangle({2, 1, 5, 6, 2, 3}) << '\n';
    cout << "Q13 Trapped water: "
         << trappedWater({0, 1, 0, 2, 1, 0, 1, 3, 2, 1, 2, 1}) << '\n';

    vector<vector<int>> matrix = {
        {0, 1, 1},
        {0, 0, 1},
        {0, 0, 0}
    };
    cout << "Q14 Celebrity index: " << celebrity(matrix) << '\n';
    cout << "Q15 Decoded string: " << decodeString("3[a2[c]]") << '\n';

    return 0;
}
```

## Java
```java
import java.util.*;

public class StackQuestions {

    // Q6. Next Greater Element
    static int[] nextGreater(int[] a) {
        int n = a.length;
        int[] ans = new int[n];
        Arrays.fill(ans, -1);
        Deque<Integer> st = new ArrayDeque<>();

        for (int i = n - 1; i >= 0; i--) {
            while (!st.isEmpty() && st.peek() <= a[i]) st.pop();
            if (!st.isEmpty()) ans[i] = st.peek();
            st.push(a[i]);
        }
        return ans;
    }

    // Q7. Previous Greater Element
    static int[] previousGreater(int[] a) {
        int[] ans = new int[a.length];
        Deque<Integer> st = new ArrayDeque<>();

        for (int i = 0; i < a.length; i++) {
            while (!st.isEmpty() && st.peek() <= a[i]) st.pop();
            ans[i] = st.isEmpty() ? -1 : st.peek();
            st.push(a[i]);
        }
        return ans;
    }

    // Q8. Stock Span
    static int[] stockSpan(int[] prices) {
        int n = prices.length;
        int[] span = new int[n];
        Deque<Integer> st = new ArrayDeque<>();

        for (int i = 0; i < n; i++) {
            while (!st.isEmpty() && prices[st.peek()] <= prices[i])
                st.pop();
            span[i] = st.isEmpty() ? i + 1 : i - st.peek();
            st.push(i);
        }
        return span;
    }

    // Q9. Min Stack
    static class MinStack {
        Deque<int[]> st = new ArrayDeque<>();

        void push(int x) {
            int min = st.isEmpty() ? x : Math.min(x, st.peek()[1]);
            st.push(new int[]{x, min});
        }

        void pop() {
            if (st.isEmpty()) throw new NoSuchElementException("Empty stack");
            st.pop();
        }

        int top() {
            if (st.isEmpty()) throw new NoSuchElementException("Empty stack");
            return st.peek()[0];
        }

        int getMin() {
            if (st.isEmpty()) throw new NoSuchElementException("Empty stack");
            return st.peek()[1];
        }
    }

    // Q10. Sort stack: smallest element becomes the top
    static void sortStack(Deque<Integer> st) {
        Deque<Integer> temp = new ArrayDeque<>();

        while (!st.isEmpty()) {
            int x = st.pop();
            while (!temp.isEmpty() && temp.peek() > x)
                st.push(temp.pop());
            temp.push(x);
        }
        while (!temp.isEmpty()) st.push(temp.pop());
    }

    // Q11. Reverse stack recursively
    static void insertAtBottom(Deque<Integer> st, int x) {
        if (st.isEmpty()) {
            st.push(x);
            return;
        }
        int top = st.pop();
        insertAtBottom(st, x);
        st.push(top);
    }

    static void reverseStack(Deque<Integer> st) {
        if (st.isEmpty()) return;
        int x = st.pop();
        reverseStack(st);
        insertAtBottom(st, x);
    }

    // Q12. Largest Rectangle in Histogram
    static long largestRectangle(int[] h) {
        Deque<Integer> st = new ArrayDeque<>();
        long best = 0;
        int n = h.length;

        for (int i = 0; i <= n; i++) {
            int current = (i == n) ? 0 : h[i];

            while (!st.isEmpty() && h[st.peek()] > current) {
                int height = h[st.pop()];
                int width = st.isEmpty() ? i : i - st.peek() - 1;
                best = Math.max(best, (long) height * width);
            }
            if (i < n) st.push(i);
        }
        return best;
    }

    // Q13. Trapping Rain Water
    static long trappedWater(int[] h) {
        Deque<Integer> st = new ArrayDeque<>();
        long water = 0;

        for (int i = 0; i < h.length; i++) {
            while (!st.isEmpty() && h[i] > h[st.peek()]) {
                int bottom = st.pop();
                if (st.isEmpty()) break;

                int width = i - st.peek() - 1;
                int height = Math.min(h[i], h[st.peek()]) - h[bottom];
                water += (long) width * height;
            }
            st.push(i);
        }
        return water;
    }

    // Q14. Celebrity Problem: 1 means person i knows person j
    static int celebrity(int[][] knows) {
        int n = knows.length;
        if (n == 0) return -1;

        int candidate = 0;
        for (int i = 1; i < n; i++)
            if (knows[candidate][i] == 1) candidate = i;

        for (int i = 0; i < n; i++) {
            if (i == candidate) continue;
            if (knows[candidate][i] == 1 || knows[i][candidate] == 0)
                return -1;
        }
        return candidate;
    }

    // Q15. Decode "3[a2[c]]" into "accaccacc"
    static String decodeString(String s) {
        Deque<Integer> counts = new ArrayDeque<>();
        Deque<StringBuilder> prefixes = new ArrayDeque<>();
        StringBuilder current = new StringBuilder();
        int number = 0;

        for (char ch : s.toCharArray()) {
            if (Character.isDigit(ch)) {
                number = number * 10 + ch - '0';
            } else if (ch == '[') {
                counts.push(number);
                prefixes.push(current);
                current = new StringBuilder();
                number = 0;
            } else if (ch == ']') {
                int repeat = counts.pop();
                StringBuilder prefix = prefixes.pop();
                for (int i = 0; i < repeat; i++) prefix.append(current);
                current = prefix;
            } else {
                current.append(ch);
            }
        }
        return current.toString();
    }

    public static void main(String[] args) {
        int[] a = {4, 5, 2, 10, 8};
        System.out.println("Q6: " + Arrays.toString(nextGreater(a)));
        System.out.println("Q7: " + Arrays.toString(previousGreater(a)));
        System.out.println("Q8: " + Arrays.toString(
            stockSpan(new int[]{100, 80, 60, 70, 60, 75, 85})));

        MinStack ms = new MinStack();
        ms.push(5);
        ms.push(2);
        ms.push(8);
        System.out.println("Q9 minimum: " + ms.getMin());

        Deque<Integer> st = new ArrayDeque<>();
        for (int x : new int[]{3, 1, 4, 2}) st.push(x);
        sortStack(st);
        System.out.println("Q10 sorted stack, top first: " + st);

        Deque<Integer> reversed = new ArrayDeque<>();
        for (int x : new int[]{1, 2, 3, 4}) reversed.push(x);
        reverseStack(reversed);
        System.out.println("Q11 reversed stack, top first: " + reversed);

        System.out.println("Q12: " + largestRectangle(new int[]{2, 1, 5, 6, 2, 3}));
        System.out.println("Q13: " + trappedWater(
            new int[]{0, 1, 0, 2, 1, 0, 1, 3, 2, 1, 2, 1}));

        int[][] matrix = {{0,1,1}, {0,0,1}, {0,0,0}};
        System.out.println("Q14 celebrity index: " + celebrity(matrix));
        System.out.println("Q15 decoded: " + decodeString("3[a2[c]]"));
    }
}
```

## Notes on the C version
- The celebrity matrix uses a small fixed size (`PEOPLE`): a `MAX × MAX` (10000 × 10000) int matrix needs about 400 MB and many compilers or systems reject it.
- The decode buffers are `static`: 1000 × 10000 characters (about 10 MB) declared inside the function would overflow the default call stack.
- Stacks live bottom-to-top in the array, so Q10 and Q11 are printed from the end to show the top first.
MD,

];
