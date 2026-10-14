<?php

// DSA › Linked list problems Q26–Q35 (C, C++, Java), numbered as supplied.
return [

'Q26. Remove a cycle from a linked list' => <<<'MD'
## Solution
Detect the cycle with Floyd's slow/fast pointers. Then find the node just before the cycle start and set its `next` to NULL.

## Step by step
1. Move `slow` one step and `fast` two steps until they meet (no meeting → no cycle, nothing to do).
2. Reset `slow = head`.
3. Special case: if the meeting point is the head (the whole list is a loop), walk `fast` around until `fast->next == head`, then cut.
4. Otherwise advance `slow` and `fast` one step at a time until `slow->next == fast->next`; that shared next node is the cycle start, so `fast` is the last node of the loop. Set `fast->next = NULL`.

## Example
`10 → 20 → 30 → 40 → 50 → back to 30` becomes `10 → 20 → 30 → 40 → 50 → NULL`.

## Complexity
- O(n) time, O(1) space.
MD,

'Q27. Find the nth node from the end' => <<<'MD'
## Solution
Two pointers with a gap of n: move `first` n steps ahead, then move both until `first` is NULL. `second` is then the nth node from the end. One pass, no length needed.

## Dry run (n = 2 on 10 → 20 → 30 → 40 → 50)
| Step | first | second |
|---|---|---|
| after the gap | 30 | 10 |
| move | 40 | 20 |
| move | 50 | 30 |
| move | NULL | 40 → answer |

## Edge cases
- `n <= 0` or `n` longer than the list → return NULL.

## Complexity
- O(n) time, O(1) space.
MD,

'Q28. Merge two sorted linked lists' => <<<'MD'
## Solution
Use a dummy head and a `tail` pointer. Repeatedly attach the smaller front node of the two lists; when one list ends, attach the rest of the other. No new nodes are created.

## Dry run
`1 → 3 → 5` and `2 → 4 → 6` → take 1, 2, 3, 4, 5, then attach 6 → `1 → 2 → 3 → 4 → 5 → 6`.

## Complexity
- O(n + m) time, O(1) extra space. Using `<=` keeps equal values in their original order (stable merge).
MD,

'Q29. Remove duplicates from a sorted linked list' => <<<'MD'
## Solution
In a sorted list duplicates are adjacent. Compare each node with its next: if equal, unlink (and free) the next node; otherwise move forward.

## Dry run
`1 → 1 → 2 → 3 → 3 → 3 → 4` → `1 → 2 → 3 → 4`.

## Note
For an unsorted list use a hash set of seen values (O(n) time, O(n) space).

## Complexity
- O(n) time, O(1) space.
MD,

'Q30. Check whether a linked list is a palindrome' => <<<'MD'
## Solution
Find the middle, reverse the second half, compare the two halves node by node, then reverse the second half back so the original list is unchanged.

## Step by step
1. Slow/fast pointers: stop when `fast->next` or `fast->next->next` is NULL; `slow` is the end of the first half.
2. Reverse the list after `slow`.
3. Compare from `head` and from the reversed half; any mismatch → not a palindrome.
4. Reverse the second half again and reattach it to `slow->next`.

## Example
`1 → 2 → 3 → 2 → 1` → Yes.

## Complexity
- O(n) time, O(1) space (a stack-based version needs O(n) space).
MD,

'Q31. Find the intersection of two linked lists' => <<<'MD'
## Solution
Two pointers walk both lists; when one reaches the end it continues from the head of the other list. Both travel `lenA + lenB` nodes, so they meet at the first shared node, or both become NULL together when the lists do not intersect.

Note: intersection means the same node in memory (pointer identity), not equal values.

## Example
A: `3 → 6 → 8 → 10`, B: `4 → 8 → 10`, where 8 and 10 are the same nodes → intersection at 8.

## Complexity
- O(m + n) time, O(1) space.
MD,

'Q32. Add two numbers represented by linked lists' => <<<'MD'
## Solution
Digits are stored in reverse order (units first). Add digit by digit with a carry, creating a new node for each digit of the sum; keep going while either list has digits or a carry remains.

## Dry run
`2 → 4 → 3` (342) + `5 → 6 → 4` (465):
| Digits | Sum | Output digit | Carry |
|---|---|---|---|
| 2 + 5 | 7 | 7 | 0 |
| 4 + 6 | 10 | 0 | 1 |
| 3 + 4 + 1 | 8 | 8 | 0 |

Result `7 → 0 → 8` (807).

## Complexity
- O(max(m, n)) time; O(max(m, n)) space for the result.
MD,

'Q33. Sort a linked list using merge sort' => <<<'MD'
## Solution
Merge sort suits linked lists: splitting needs no random access and merging needs no extra array.

## Step by step
1. Base case: empty or single node.
2. Find the middle with slow/fast pointers (`fast` starts at `head->next` so the left half is never empty) and cut the list in two.
3. Sort each half recursively.
4. Merge the two sorted halves (Q28).

## Example
`4 → 2 → 1 → 3` → `1 → 2 → 3 → 4`.

## Complexity
- O(n log n) time; O(log n) recursion stack.
MD,

'Q34. Rotate a linked list right by k positions' => <<<'MD'
## Solution
Find the length and tail, make the list circular, then cut it at the right place.

## Step by step
1. Walk to the tail counting `length`.
2. `k %= length` (rotating by the length gives the same list); if 0, return.
3. Link `tail->next = head` (circular).
4. Move `length − k` steps from the tail to reach the new tail; the node after it is the new head; cut the circle.

## Example
`1 → 2 → 3 → 4 → 5`, k = 2 → `4 → 5 → 1 → 2 → 3`.

## Complexity
- O(n) time, O(1) space.
MD,

'Q35. Reverse a linked list in groups of k' => <<<'MD'
## Solution
Check that k nodes remain; if not, leave the remaining group unchanged. Otherwise reverse those k nodes, then recursively process the rest and attach it after the reversed group (the old group head becomes its tail).

## Example
`1 → 2 → 3 → 4 → 5 → 6 → 7`, k = 3 → `3 → 2 → 1 → 6 → 5 → 4 → 7` (7 stays because only one node is left).

## Complexity
- O(n) time; O(n/k) recursion stack (an iterative version uses O(1)).
MD,

'Q26–Q30. Complete programs (C, C++, Java)' => <<<'MD'
## Expected output
```text
Q26 Cycle removed: 10 -> 20 -> 30 -> 40 -> 50 -> NULL
Q27 2nd from end: 40
Q28 Merged: 1 -> 2 -> 3 -> 4 -> 5 -> 6 -> NULL
Q29 Deduplicated: 1 -> 2 -> 3 -> 4 -> NULL
Q30 Palindrome: Yes
```

## C
```c
#include <stdio.h>
#include <stdlib.h>

typedef struct Node {
    int data;
    struct Node *next;
} Node;

Node* createNode(int value) {
    Node *n = malloc(sizeof(Node));
    if (!n) exit(EXIT_FAILURE);
    n->data = value;
    n->next = NULL;
    return n;
}

Node* insertEnd(Node *head, int value) {
    Node *n = createNode(value);
    if (!head) return n;

    Node *cur = head;
    while (cur->next) cur = cur->next;
    cur->next = n;
    return head;
}

void display(Node *head) {
    while (head) {
        printf("%d -> ", head->data);
        head = head->next;
    }
    printf("NULL\n");
}

// Q26: Remove a cycle using Floyd's algorithm
void removeCycle(Node *head) {
    Node *slow = head, *fast = head;

    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
        if (slow == fast) break;
    }

    if (!fast || !fast->next) return;

    slow = head;

    if (slow == fast) {
        while (fast->next != slow)
            fast = fast->next;
        fast->next = NULL;
        return;
    }

    while (slow->next != fast->next) {
        slow = slow->next;
        fast = fast->next;
    }
    fast->next = NULL;
}

// Q27: Return nth node from end; n is 1-based
Node* nthFromEnd(Node *head, int n) {
    if (n <= 0) return NULL;

    Node *first = head, *second = head;

    for (int i = 0; i < n; i++) {
        if (!first) return NULL;
        first = first->next;
    }

    while (first) {
        first = first->next;
        second = second->next;
    }
    return second;
}

// Q28: Merge two sorted lists
Node* mergeSorted(Node *a, Node *b) {
    Node dummy;
    dummy.next = NULL;
    Node *tail = &dummy;

    while (a && b) {
        if (a->data <= b->data) {
            tail->next = a;
            a = a->next;
        } else {
            tail->next = b;
            b = b->next;
        }
        tail = tail->next;
    }

    tail->next = a ? a : b;
    return dummy.next;
}

// Q29: Remove duplicates from sorted list
Node* removeDuplicates(Node *head) {
    Node *cur = head;

    while (cur && cur->next) {
        if (cur->data == cur->next->data) {
            Node *duplicate = cur->next;
            cur->next = duplicate->next;
            free(duplicate);
        } else {
            cur = cur->next;
        }
    }
    return head;
}

// Q30: Check palindrome without changing the original list
int isPalindrome(Node *head) {
    if (!head || !head->next) return 1;

    Node *slow = head, *fast = head;

    while (fast->next && fast->next->next) {
        slow = slow->next;
        fast = fast->next->next;
    }

    Node *prev = NULL, *cur = slow->next;

    while (cur) {
        Node *next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }

    Node *left = head, *right = prev;
    int palindrome = 1;

    while (right) {
        if (left->data != right->data) {
            palindrome = 0;
            break;
        }
        left = left->next;
        right = right->next;
    }

    // Restore the original list
    cur = prev;
    prev = NULL;
    while (cur) {
        Node *next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }
    slow->next = prev;

    return palindrome;
}

void freeList(Node *head) {
    while (head) {
        Node *next = head->next;
        free(head);
        head = next;
    }
}

int main(void) {
    Node *head = NULL;
    for (int x = 10; x <= 50; x += 10)
        head = insertEnd(head, x);

    // Q26: Create and remove a cycle
    Node *third = head->next->next;
    Node *last = head;
    while (last->next) last = last->next;
    last->next = third;
    removeCycle(head);
    printf("Q26 Cycle removed: ");
    display(head);

    // Q27
    Node *n = nthFromEnd(head, 2);
    printf("Q27 2nd from end: %d\n", n ? n->data : -1);

    // Q28
    Node *a = NULL, *b = NULL;
    a = insertEnd(a, 1);
    a = insertEnd(a, 3);
    a = insertEnd(a, 5);
    b = insertEnd(b, 2);
    b = insertEnd(b, 4);
    b = insertEnd(b, 6);
    Node *merged = mergeSorted(a, b);
    printf("Q28 Merged: ");
    display(merged);

    // Q29
    Node *duplicates = NULL;
    int values[] = {1, 1, 2, 3, 3, 3, 4};
    for (int i = 0; i < 7; i++)
        duplicates = insertEnd(duplicates, values[i]);
    duplicates = removeDuplicates(duplicates);
    printf("Q29 Deduplicated: ");
    display(duplicates);

    // Q30
    Node *pal = NULL;
    int p[] = {1, 2, 3, 2, 1};
    for (int i = 0; i < 5; i++)
        pal = insertEnd(pal, p[i]);
    printf("Q30 Palindrome: %s\n",
           isPalindrome(pal) ? "Yes" : "No");

    freeList(head);
    freeList(merged);
    freeList(duplicates);
    freeList(pal);
    return 0;
}
```

## C++
```cpp
#include <iostream>
using namespace std;

struct Node {
    int data;
    Node* next;
    Node(int value) : data(value), next(nullptr) {}
};

Node* insertEnd(Node* head, int value) {
    Node* n = new Node(value);
    if (!head) return n;

    Node* cur = head;
    while (cur->next) cur = cur->next;
    cur->next = n;
    return head;
}

void display(Node* head) {
    while (head) {
        cout << head->data << " -> ";
        head = head->next;
    }
    cout << "NULL\n";
}

// Q26
void removeCycle(Node* head) {
    Node *slow = head, *fast = head;

    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
        if (slow == fast) break;
    }

    if (!fast || !fast->next) return;

    slow = head;
    if (slow == fast) {
        while (fast->next != slow) fast = fast->next;
        fast->next = nullptr;
        return;
    }

    while (slow->next != fast->next) {
        slow = slow->next;
        fast = fast->next;
    }
    fast->next = nullptr;
}

// Q27: n is 1-based
Node* nthFromEnd(Node* head, int n) {
    if (n <= 0) return nullptr;

    Node *first = head, *second = head;
    for (int i = 0; i < n; i++) {
        if (!first) return nullptr;
        first = first->next;
    }

    while (first) {
        first = first->next;
        second = second->next;
    }
    return second;
}

// Q28
Node* mergeSorted(Node* a, Node* b) {
    Node dummy(0);
    Node* tail = &dummy;

    while (a && b) {
        if (a->data <= b->data) {
            tail->next = a;
            a = a->next;
        } else {
            tail->next = b;
            b = b->next;
        }
        tail = tail->next;
    }

    tail->next = a ? a : b;
    return dummy.next;
}

// Q29
Node* removeDuplicates(Node* head) {
    Node* cur = head;

    while (cur && cur->next) {
        if (cur->data == cur->next->data) {
            Node* duplicate = cur->next;
            cur->next = duplicate->next;
            delete duplicate;
        } else {
            cur = cur->next;
        }
    }
    return head;
}

// Q30: Restore the list after checking
bool isPalindrome(Node* head) {
    if (!head || !head->next) return true;

    Node *slow = head, *fast = head;
    while (fast->next && fast->next->next) {
        slow = slow->next;
        fast = fast->next->next;
    }

    Node *prev = nullptr, *cur = slow->next;
    while (cur) {
        Node* next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }

    Node *left = head, *right = prev;
    bool result = true;

    while (right) {
        if (left->data != right->data) {
            result = false;
            break;
        }
        left = left->next;
        right = right->next;
    }

    cur = prev;
    prev = nullptr;
    while (cur) {
        Node* next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }
    slow->next = prev;

    return result;
}

void freeList(Node* head) {
    while (head) {
        Node* next = head->next;
        delete head;
        head = next;
    }
}

int main() {
    Node* head = nullptr;
    for (int x = 10; x <= 50; x += 10)
        head = insertEnd(head, x);

    Node* third = head->next->next;
    Node* last = head;
    while (last->next) last = last->next;
    last->next = third;

    removeCycle(head);
    cout << "Q26 Cycle removed: ";
    display(head);

    Node* n = nthFromEnd(head, 2);
    cout << "Q27 2nd from end: " << (n ? n->data : -1) << '\n';

    Node *a = nullptr, *b = nullptr;
    a = insertEnd(a, 1);
    a = insertEnd(a, 3);
    a = insertEnd(a, 5);
    b = insertEnd(b, 2);
    b = insertEnd(b, 4);
    b = insertEnd(b, 6);

    Node* merged = mergeSorted(a, b);
    cout << "Q28 Merged: ";
    display(merged);

    Node* duplicates = nullptr;
    for (int x : {1, 1, 2, 3, 3, 3, 4})
        duplicates = insertEnd(duplicates, x);
    duplicates = removeDuplicates(duplicates);
    cout << "Q29 Deduplicated: ";
    display(duplicates);

    Node* pal = nullptr;
    for (int x : {1, 2, 3, 2, 1})
        pal = insertEnd(pal, x);
    cout << "Q30 Palindrome: "
         << (isPalindrome(pal) ? "Yes" : "No") << '\n';

    freeList(head);
    freeList(merged);
    freeList(duplicates);
    freeList(pal);
    return 0;
}
```

## Java
```java
public class LinkedList26To30 {

    static class Node {
        int data;
        Node next;
        Node(int value) { data = value; }
    }

    static Node insertEnd(Node head, int value) {
        Node n = new Node(value);
        if (head == null) return n;

        Node cur = head;
        while (cur.next != null) cur = cur.next;
        cur.next = n;
        return head;
    }

    static void display(Node head) {
        while (head != null) {
            System.out.print(head.data + " -> ");
            head = head.next;
        }
        System.out.println("NULL");
    }

    // Q26
    static void removeCycle(Node head) {
        Node slow = head, fast = head;

        while (fast != null && fast.next != null) {
            slow = slow.next;
            fast = fast.next.next;
            if (slow == fast) break;
        }

        if (fast == null || fast.next == null) return;

        slow = head;
        if (slow == fast) {
            while (fast.next != slow) fast = fast.next;
            fast.next = null;
            return;
        }

        while (slow.next != fast.next) {
            slow = slow.next;
            fast = fast.next;
        }
        fast.next = null;
    }

    // Q27: n is 1-based
    static Node nthFromEnd(Node head, int n) {
        if (n <= 0) return null;

        Node first = head, second = head;
        for (int i = 0; i < n; i++) {
            if (first == null) return null;
            first = first.next;
        }

        while (first != null) {
            first = first.next;
            second = second.next;
        }
        return second;
    }

    // Q28
    static Node mergeSorted(Node a, Node b) {
        Node dummy = new Node(0);
        Node tail = dummy;

        while (a != null && b != null) {
            if (a.data <= b.data) {
                tail.next = a;
                a = a.next;
            } else {
                tail.next = b;
                b = b.next;
            }
            tail = tail.next;
        }

        tail.next = (a != null) ? a : b;
        return dummy.next;
    }

    // Q29
    static Node removeDuplicates(Node head) {
        Node cur = head;

        while (cur != null && cur.next != null) {
            if (cur.data == cur.next.data)
                cur.next = cur.next.next;
            else
                cur = cur.next;
        }
        return head;
    }

    // Q30: Restore the list after checking
    static boolean isPalindrome(Node head) {
        if (head == null || head.next == null) return true;

        Node slow = head, fast = head;
        while (fast.next != null && fast.next.next != null) {
            slow = slow.next;
            fast = fast.next.next;
        }

        Node prev = null, cur = slow.next;
        while (cur != null) {
            Node next = cur.next;
            cur.next = prev;
            prev = cur;
            cur = next;
        }

        Node left = head, right = prev;
        boolean result = true;

        while (right != null) {
            if (left.data != right.data) {
                result = false;
                break;
            }
            left = left.next;
            right = right.next;
        }

        cur = prev;
        prev = null;
        while (cur != null) {
            Node next = cur.next;
            cur.next = prev;
            prev = cur;
            cur = next;
        }
        slow.next = prev;

        return result;
    }

    public static void main(String[] args) {
        Node head = null;
        for (int x = 10; x <= 50; x += 10)
            head = insertEnd(head, x);

        Node third = head.next.next;
        Node last = head;
        while (last.next != null) last = last.next;
        last.next = third;

        removeCycle(head);
        System.out.print("Q26 Cycle removed: ");
        display(head);

        Node n = nthFromEnd(head, 2);
        System.out.println("Q27 2nd from end: " +
                           (n == null ? -1 : n.data));

        Node a = null, b = null;
        a = insertEnd(a, 1);
        a = insertEnd(a, 3);
        a = insertEnd(a, 5);
        b = insertEnd(b, 2);
        b = insertEnd(b, 4);
        b = insertEnd(b, 6);

        Node merged = mergeSorted(a, b);
        System.out.print("Q28 Merged: ");
        display(merged);

        Node duplicates = null;
        for (int x : new int[]{1, 1, 2, 3, 3, 3, 4})
            duplicates = insertEnd(duplicates, x);
        duplicates = removeDuplicates(duplicates);
        System.out.print("Q29 Deduplicated: ");
        display(duplicates);

        Node pal = null;
        for (int x : new int[]{1, 2, 3, 2, 1})
            pal = insertEnd(pal, x);
        System.out.println("Q30 Palindrome: " +
                           (isPalindrome(pal) ? "Yes" : "No"));
    }
}
```
MD,

'Q31–Q35. Complete programs (C, C++, Java)' => <<<'MD'
## Expected output
```text
Q31 Intersection: 8
Q32 Sum digits: 7 -> 0 -> 8 -> NULL
Q33 Sorted: 1 -> 2 -> 3 -> 4 -> NULL
Q34 Rotated: 4 -> 5 -> 1 -> 2 -> 3 -> NULL
Q35 Groups of 3: 3 -> 2 -> 1 -> 6 -> 5 -> 4 -> 7 -> NULL
```

## C
```c
#include <stdio.h>
#include <stdlib.h>

typedef struct Node {
    int data;
    struct Node *next;
} Node;

Node* createNode(int value) {
    Node* n = malloc(sizeof(Node));
    if (!n) exit(EXIT_FAILURE);
    n->data = value;
    n->next = NULL;
    return n;
}

Node* insertEnd(Node* head, int value) {
    Node* n = createNode(value);
    if (!head) return n;

    Node* cur = head;
    while (cur->next) cur = cur->next;
    cur->next = n;
    return head;
}

void display(Node* head) {
    while (head) {
        printf("%d -> ", head->data);
        head = head->next;
    }
    printf("NULL\n");
}

// Q31: Find the shared intersection node by identity, not value.
Node* getIntersection(Node* a, Node* b) {
    Node *p = a, *q = b;

    while (p != q) {
        p = p ? p->next : b;
        q = q ? q->next : a;
    }
    return p;
}

// Q32: Add reverse-order digit lists.
Node* addNumbers(Node* a, Node* b) {
    Node dummy = {0, NULL};
    Node* tail = &dummy;
    int carry = 0;

    while (a || b || carry) {
        int sum = carry;
        if (a) { sum += a->data; a = a->next; }
        if (b) { sum += b->data; b = b->next; }

        carry = sum / 10;
        tail->next = createNode(sum % 10);
        tail = tail->next;
    }
    return dummy.next;
}

// Q33: Merge sort
Node* mergeLists(Node* a, Node* b) {
    Node dummy = {0, NULL};
    Node* tail = &dummy;

    while (a && b) {
        if (a->data <= b->data) {
            tail->next = a;
            a = a->next;
        } else {
            tail->next = b;
            b = b->next;
        }
        tail = tail->next;
    }

    tail->next = a ? a : b;
    return dummy.next;
}

Node* mergeSort(Node* head) {
    if (!head || !head->next) return head;

    Node *slow = head, *fast = head->next;
    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
    }

    Node* right = slow->next;
    slow->next = NULL;

    return mergeLists(mergeSort(head), mergeSort(right));
}

// Q34: Rotate right by k positions
Node* rotateRight(Node* head, int k) {
    if (!head || !head->next || k <= 0) return head;

    int length = 1;
    Node* tail = head;
    while (tail->next) {
        tail = tail->next;
        length++;
    }

    k %= length;
    if (k == 0) return head;

    tail->next = head;
    int steps = length - k;
    Node* newTail = tail;

    while (steps--) newTail = newTail->next;

    Node* newHead = newTail->next;
    newTail->next = NULL;
    return newHead;
}

// Q35: Reverse nodes in groups of k.
// An incomplete final group remains unchanged.
Node* reverseKGroup(Node* head, int k) {
    if (!head || k <= 1) return head;

    Node* check = head;
    for (int i = 0; i < k; i++) {
        if (!check) return head;
        check = check->next;
    }

    Node *prev = NULL, *cur = head;
    for (int i = 0; i < k; i++) {
        Node* next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }

    head->next = reverseKGroup(cur, k);
    return prev;
}

void freeList(Node* head) {
    while (head) {
        Node* next = head->next;
        free(head);
        head = next;
    }
}

int main(void) {
    // Q31
    Node* shared = NULL;
    shared = insertEnd(shared, 8);
    shared = insertEnd(shared, 10);

    Node* a = NULL;
    a = insertEnd(a, 3);
    a = insertEnd(a, 6);
    Node* atail = a;
    while (atail->next) atail = atail->next;
    atail->next = shared;

    Node* b = NULL;
    b = insertEnd(b, 4);
    Node* btail = b;
    while (btail->next) btail = btail->next;
    btail->next = shared;

    Node* intersection = getIntersection(a, b);
    printf("Q31 Intersection: %d\n",
           intersection ? intersection->data : -1);

    // The two lists share nodes. Break their links before freeing.
    atail->next = NULL;
    btail->next = NULL;
    freeList(a);
    freeList(b);
    freeList(shared);

    // Q32
    Node *num1 = NULL, *num2 = NULL;
    num1 = insertEnd(num1, 2);
    num1 = insertEnd(num1, 4);
    num1 = insertEnd(num1, 3);
    num2 = insertEnd(num2, 5);
    num2 = insertEnd(num2, 6);
    num2 = insertEnd(num2, 4);
    Node* sum = addNumbers(num1, num2);
    printf("Q32 Sum digits: ");
    display(sum);

    // Q33 (index loop: range-based for is C++ only)
    Node* unsorted = NULL;
    int values[] = {4, 2, 1, 3};
    for (int i = 0; i < 4; i++)
        unsorted = insertEnd(unsorted, values[i]);
    unsorted = mergeSort(unsorted);
    printf("Q33 Sorted: ");
    display(unsorted);

    // Q34
    Node* rotated = NULL;
    for (int x = 1; x <= 5; x++)
        rotated = insertEnd(rotated, x);
    rotated = rotateRight(rotated, 2);
    printf("Q34 Rotated: ");
    display(rotated);

    // Q35
    Node* grouped = NULL;
    for (int x = 1; x <= 7; x++)
        grouped = insertEnd(grouped, x);
    grouped = reverseKGroup(grouped, 3);
    printf("Q35 Groups of 3: ");
    display(grouped);

    freeList(num1);
    freeList(num2);
    freeList(sum);
    freeList(unsorted);
    freeList(rotated);
    freeList(grouped);
    return 0;
}
```

## C++
```cpp
#include <iostream>
using namespace std;

struct Node {
    int data;
    Node* next;
    Node(int value) : data(value), next(nullptr) {}
};

Node* insertEnd(Node* head, int value) {
    Node* n = new Node(value);
    if (!head) return n;

    Node* cur = head;
    while (cur->next) cur = cur->next;
    cur->next = n;
    return head;
}

void display(Node* head) {
    while (head) {
        cout << head->data << " -> ";
        head = head->next;
    }
    cout << "NULL\n";
}

// Q31
Node* getIntersection(Node* a, Node* b) {
    Node *p = a, *q = b;
    while (p != q) {
        p = p ? p->next : b;
        q = q ? q->next : a;
    }
    return p;
}

// Q32
Node* addNumbers(Node* a, Node* b) {
    Node dummy(0);
    Node* tail = &dummy;
    int carry = 0;

    while (a || b || carry) {
        int sum = carry;
        if (a) { sum += a->data; a = a->next; }
        if (b) { sum += b->data; b = b->next; }

        carry = sum / 10;
        tail->next = new Node(sum % 10);
        tail = tail->next;
    }
    return dummy.next;
}

// Q33
Node* mergeLists(Node* a, Node* b) {
    Node dummy(0);
    Node* tail = &dummy;

    while (a && b) {
        if (a->data <= b->data) {
            tail->next = a;
            a = a->next;
        } else {
            tail->next = b;
            b = b->next;
        }
        tail = tail->next;
    }

    tail->next = a ? a : b;
    return dummy.next;
}

Node* mergeSort(Node* head) {
    if (!head || !head->next) return head;

    Node *slow = head, *fast = head->next;
    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
    }

    Node* right = slow->next;
    slow->next = nullptr;
    return mergeLists(mergeSort(head), mergeSort(right));
}

// Q34
Node* rotateRight(Node* head, int k) {
    if (!head || !head->next || k <= 0) return head;

    int length = 1;
    Node* tail = head;
    while (tail->next) {
        tail = tail->next;
        length++;
    }

    k %= length;
    if (k == 0) return head;

    tail->next = head;
    int steps = length - k;
    Node* newTail = tail;
    while (steps--) newTail = newTail->next;

    Node* newHead = newTail->next;
    newTail->next = nullptr;
    return newHead;
}

// Q35: Leave incomplete final group unchanged.
Node* reverseKGroup(Node* head, int k) {
    if (!head || k <= 1) return head;

    Node* check = head;
    for (int i = 0; i < k; i++) {
        if (!check) return head;
        check = check->next;
    }

    Node *prev = nullptr, *cur = head;
    for (int i = 0; i < k; i++) {
        Node* next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }

    head->next = reverseKGroup(cur, k);
    return prev;
}

void freeList(Node* head) {
    while (head) {
        Node* next = head->next;
        delete head;
        head = next;
    }
}

int main() {
    // Q31: Both lists share the same actual nodes.
    Node* shared = nullptr;
    shared = insertEnd(shared, 8);
    shared = insertEnd(shared, 10);

    Node* a = nullptr;
    a = insertEnd(a, 3);
    a = insertEnd(a, 6);
    Node* atail = a;
    while (atail->next) atail = atail->next;
    atail->next = shared;

    Node* b = insertEnd(nullptr, 4);
    Node* btail = b;
    btail->next = shared;

    Node* intersection = getIntersection(a, b);
    cout << "Q31 Intersection: "
         << (intersection ? intersection->data : -1) << '\n';

    atail->next = nullptr;
    btail->next = nullptr;
    freeList(a);
    freeList(b);
    freeList(shared);

    // Q32
    Node *num1 = nullptr, *num2 = nullptr;
    for (int x : {2, 4, 3}) num1 = insertEnd(num1, x);
    for (int x : {5, 6, 4}) num2 = insertEnd(num2, x);
    Node* sum = addNumbers(num1, num2);
    cout << "Q32 Sum digits: ";
    display(sum);

    // Q33
    Node* unsorted = nullptr;
    for (int x : {4, 2, 1, 3}) unsorted = insertEnd(unsorted, x);
    unsorted = mergeSort(unsorted);
    cout << "Q33 Sorted: ";
    display(unsorted);

    // Q34
    Node* rotated = nullptr;
    for (int x = 1; x <= 5; x++) rotated = insertEnd(rotated, x);
    rotated = rotateRight(rotated, 2);
    cout << "Q34 Rotated: ";
    display(rotated);

    // Q35
    Node* grouped = nullptr;
    for (int x = 1; x <= 7; x++) grouped = insertEnd(grouped, x);
    grouped = reverseKGroup(grouped, 3);
    cout << "Q35 Groups of 3: ";
    display(grouped);

    freeList(num1);
    freeList(num2);
    freeList(sum);
    freeList(unsorted);
    freeList(rotated);
    freeList(grouped);
    return 0;
}
```

## Java
```java
public class LinkedList31To35 {

    static class Node {
        int data;
        Node next;
        Node(int value) { data = value; }
    }

    static Node insertEnd(Node head, int value) {
        Node n = new Node(value);
        if (head == null) return n;

        Node cur = head;
        while (cur.next != null) cur = cur.next;
        cur.next = n;
        return head;
    }

    static void display(Node head) {
        while (head != null) {
            System.out.print(head.data + " -> ");
            head = head.next;
        }
        System.out.println("NULL");
    }

    // Q31
    static Node getIntersection(Node a, Node b) {
        Node p = a, q = b;
        while (p != q) {
            p = (p == null) ? b : p.next;
            q = (q == null) ? a : q.next;
        }
        return p;
    }

    // Q32
    static Node addNumbers(Node a, Node b) {
        Node dummy = new Node(0);
        Node tail = dummy;
        int carry = 0;

        while (a != null || b != null || carry != 0) {
            int sum = carry;
            if (a != null) {
                sum += a.data;
                a = a.next;
            }
            if (b != null) {
                sum += b.data;
                b = b.next;
            }

            carry = sum / 10;
            tail.next = new Node(sum % 10);
            tail = tail.next;
        }
        return dummy.next;
    }

    // Q33
    static Node mergeLists(Node a, Node b) {
        Node dummy = new Node(0);
        Node tail = dummy;

        while (a != null && b != null) {
            if (a.data <= b.data) {
                tail.next = a;
                a = a.next;
            } else {
                tail.next = b;
                b = b.next;
            }
            tail = tail.next;
        }

        tail.next = (a != null) ? a : b;
        return dummy.next;
    }

    static Node mergeSort(Node head) {
        if (head == null || head.next == null) return head;

        Node slow = head, fast = head.next;
        while (fast != null && fast.next != null) {
            slow = slow.next;
            fast = fast.next.next;
        }

        Node right = slow.next;
        slow.next = null;
        return mergeLists(mergeSort(head), mergeSort(right));
    }

    // Q34: Rotate right by k
    static Node rotateRight(Node head, int k) {
        if (head == null || head.next == null || k <= 0) return head;

        int length = 1;
        Node tail = head;
        while (tail.next != null) {
            tail = tail.next;
            length++;
        }

        k %= length;
        if (k == 0) return head;

        tail.next = head;
        int steps = length - k;
        Node newTail = tail;

        while (steps-- > 0) newTail = newTail.next;

        Node newHead = newTail.next;
        newTail.next = null;
        return newHead;
    }

    // Q35: Incomplete final group stays unchanged.
    static Node reverseKGroup(Node head, int k) {
        if (head == null || k <= 1) return head;

        Node check = head;
        for (int i = 0; i < k; i++) {
            if (check == null) return head;
            check = check.next;
        }

        Node prev = null, cur = head;
        for (int i = 0; i < k; i++) {
            Node next = cur.next;
            cur.next = prev;
            prev = cur;
            cur = next;
        }

        head.next = reverseKGroup(cur, k);
        return prev;
    }

    public static void main(String[] args) {
        // Q31
        Node shared = null;
        shared = insertEnd(shared, 8);
        shared = insertEnd(shared, 10);

        Node a = null;
        a = insertEnd(a, 3);
        a = insertEnd(a, 6);
        Node atail = a;
        while (atail.next != null) atail = atail.next;
        atail.next = shared;

        Node b = new Node(4);
        b.next = shared;

        Node intersection = getIntersection(a, b);
        System.out.println("Q31 Intersection: " +
                           (intersection == null ? -1 : intersection.data));

        atail.next = null;
        b.next = null; // Disconnect B's own node from the shared nodes.

        // Q32
        Node num1 = null, num2 = null;
        for (int x : new int[]{2, 4, 3}) num1 = insertEnd(num1, x);
        for (int x : new int[]{5, 6, 4}) num2 = insertEnd(num2, x);
        Node sum = addNumbers(num1, num2);
        System.out.print("Q32 Sum digits: ");
        display(sum);

        // Q33
        Node unsorted = null;
        for (int x : new int[]{4, 2, 1, 3})
            unsorted = insertEnd(unsorted, x);
        unsorted = mergeSort(unsorted);
        System.out.print("Q33 Sorted: ");
        display(unsorted);

        // Q34
        Node rotated = null;
        for (int x = 1; x <= 5; x++) rotated = insertEnd(rotated, x);
        rotated = rotateRight(rotated, 2);
        System.out.print("Q34 Rotated: ");
        display(rotated);

        // Q35
        Node grouped = null;
        for (int x = 1; x <= 7; x++) grouped = insertEnd(grouped, x);
        grouped = reverseKGroup(grouped, 3);
        System.out.print("Q35 Groups of 3: ");
        display(grouped);
    }
}
```

Note: the C version builds the Q33 list with an index loop; a range-based `for (int x : …)` is C++ only and does not compile as C.
MD,

];
