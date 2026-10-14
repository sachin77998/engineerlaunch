<?php

// DSA › Linked list problems Q16–Q25 (C, C++, Java), numbered as supplied.
return [

'Q16. Create and display a linked list' => <<<'MD'
## Solution
A singly linked list is a chain of nodes; each node stores `data` and a pointer `next` to the following node. The last node points to NULL. To display it, start at `head` and follow `next` until NULL.

## Step by step
1. Node: `data` + `next`.
2. Build: start with `head = NULL` and append 10, 20, 30 (Q18 shows how appending works).
3. Display: `while (head != NULL) { print head->data; head = head->next; }`, then print `NULL`.

## Output
```text
Q16: 10 -> 20 -> 30 -> NULL
```

## Complexity
- Display: O(n) time, O(1) extra space.

The full C, C++ and Java programs for Q16–Q20 are in "Q16–Q20. Complete programs".
MD,

'Q17. Insert at the beginning' => <<<'MD'
## Solution
Create a node, point its `next` to the current head, and return it as the new head.

## Step by step
1. `node = createNode(value)`.
2. `node->next = head`.
3. Return `node` (the caller stores it in `head`).

## Dry run
`10 -> 20 -> 30`, insert 5 → `5 -> 10 -> 20 -> 30`.

## Complexity
- O(1) time: no traversal is needed.
MD,

'Q18. Insert at the end' => <<<'MD'
## Solution
If the list is empty the new node is the head. Otherwise walk to the last node (whose `next` is NULL) and link the new node after it.

## Step by step
1. `node = createNode(value)`.
2. If `head == NULL`, return `node`.
3. `current = head`; while `current->next != NULL`, move forward.
4. `current->next = node`; return `head`.

## Dry run
`5 -> 10 -> 20 -> 30`, insert 40 → walk to 30 → `5 -> 10 -> 20 -> 30 -> 40`.

## Complexity
- O(n) time to reach the tail (O(1) if a tail pointer is maintained).
MD,

'Q19. Delete the first node containing a value' => <<<'MD'
## Solution
If the head holds the value, the second node becomes the head. Otherwise stop at the node just before the match and bypass the match.

## Step by step
1. Empty list: return NULL.
2. `head->data == value`: save `head->next`, free the head, return the saved node.
3. Move `current` while `current->next != NULL` and `current->next->data != value`.
4. If a match was found (`current->next != NULL`): `temp = current->next; current->next = temp->next; free(temp)`.
5. Return `head`.

## Dry run
`5 -> 10 -> 20 -> 30 -> 40`, delete 20: `current` stops at 10, link 10 → 30 → `5 -> 10 -> 30 -> 40`.

## Complexity
- O(n) time, O(1) space. In C free the node; in C++ `delete` it; Java's garbage collector reclaims it.
MD,

'Q20. Search for a value' => <<<'MD'
## Solution
Walk the list with a position counter; return the zero-based position of the first match, or -1.

## Dry run
`5 -> 10 -> 30 -> 40`: 30 is found at position 2; 99 is not found → -1.

## Complexity
- O(n) time, O(1) space.
MD,

'Q16–Q20. Complete programs (C, C++, Java)' => <<<'MD'
## Expected output
```text
Q16: 10 -> 20 -> 30 -> NULL
Q17: 5 -> 10 -> 20 -> 30 -> NULL
Q18: 5 -> 10 -> 20 -> 30 -> 40 -> NULL
Q19: 5 -> 10 -> 30 -> 40 -> NULL
Q20: Position of 30 = 2
Position of 99 = -1
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
    Node *node = (Node*)malloc(sizeof(Node));
    if (node == NULL) {
        printf("Memory allocation failed\n");
        exit(1);
    }
    node->data = value;
    node->next = NULL;
    return node;
}

// Q16: Create and display a linked list
void display(Node *head) {
    while (head != NULL) {
        printf("%d -> ", head->data);
        head = head->next;
    }
    printf("NULL\n");
}

// Q17: Insert at the beginning
Node* insertBeginning(Node *head, int value) {
    Node *node = createNode(value);
    node->next = head;
    return node;
}

// Q18: Insert at the end
Node* insertEnd(Node *head, int value) {
    Node *node = createNode(value);

    if (head == NULL) return node;

    Node *current = head;
    while (current->next != NULL)
        current = current->next;

    current->next = node;
    return head;
}

// Q19: Delete the first node containing a value
Node* deleteValue(Node *head, int value) {
    if (head == NULL) return NULL;

    if (head->data == value) {
        Node *temp = head->next;
        free(head);
        return temp;
    }

    Node *current = head;
    while (current->next != NULL &&
           current->next->data != value) {
        current = current->next;
    }

    if (current->next != NULL) {
        Node *temp = current->next;
        current->next = temp->next;
        free(temp);
    }

    return head;
}

// Q20: Search for a value; return its zero-based position
int search(Node *head, int value) {
    int position = 0;

    while (head != NULL) {
        if (head->data == value) return position;
        head = head->next;
        position++;
    }

    return -1;
}

void freeList(Node *head) {
    while (head != NULL) {
        Node *temp = head;
        head = head->next;
        free(temp);
    }
}

int main(void) {
    Node *head = NULL;

    // Q16: Build a list
    head = insertEnd(head, 10);
    head = insertEnd(head, 20);
    head = insertEnd(head, 30);
    printf("Q16: ");
    display(head);

    // Q17
    head = insertBeginning(head, 5);
    printf("Q17: ");
    display(head);

    // Q18
    head = insertEnd(head, 40);
    printf("Q18: ");
    display(head);

    // Q19
    head = deleteValue(head, 20);
    printf("Q19: ");
    display(head);

    // Q20
    printf("Q20: Position of 30 = %d\n", search(head, 30));
    printf("Position of 99 = %d\n", search(head, 99));

    freeList(head);
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

// Q16: Display linked list
void display(Node* head) {
    while (head != nullptr) {
        cout << head->data << " -> ";
        head = head->next;
    }
    cout << "NULL\n";
}

// Q17: Insert at beginning
Node* insertBeginning(Node* head, int value) {
    Node* node = new Node(value);
    node->next = head;
    return node;
}

// Q18: Insert at end
Node* insertEnd(Node* head, int value) {
    Node* node = new Node(value);

    if (head == nullptr) return node;

    Node* current = head;
    while (current->next != nullptr)
        current = current->next;

    current->next = node;
    return head;
}

// Q19: Delete first occurrence
Node* deleteValue(Node* head, int value) {
    if (head == nullptr) return nullptr;

    if (head->data == value) {
        Node* temp = head->next;
        delete head;
        return temp;
    }

    Node* current = head;
    while (current->next != nullptr &&
           current->next->data != value) {
        current = current->next;
    }

    if (current->next != nullptr) {
        Node* temp = current->next;
        current->next = temp->next;
        delete temp;
    }

    return head;
}

// Q20: Search; return zero-based position or -1
int search(Node* head, int value) {
    int position = 0;

    while (head != nullptr) {
        if (head->data == value) return position;
        head = head->next;
        position++;
    }

    return -1;
}

void freeList(Node* head) {
    while (head != nullptr) {
        Node* temp = head;
        head = head->next;
        delete temp;
    }
}

int main() {
    Node* head = nullptr;

    head = insertEnd(head, 10);
    head = insertEnd(head, 20);
    head = insertEnd(head, 30);
    cout << "Q16: ";
    display(head);

    head = insertBeginning(head, 5);
    cout << "Q17: ";
    display(head);

    head = insertEnd(head, 40);
    cout << "Q18: ";
    display(head);

    head = deleteValue(head, 20);
    cout << "Q19: ";
    display(head);

    cout << "Q20: Position of 30 = " << search(head, 30) << '\n';
    cout << "Position of 99 = " << search(head, 99) << '\n';

    freeList(head);
    return 0;
}
```

## Java
```java
public class LinkedListQuestions {

    static class Node {
        int data;
        Node next;

        Node(int value) {
            data = value;
            next = null;
        }
    }

    // Q16: Display linked list
    static void display(Node head) {
        while (head != null) {
            System.out.print(head.data + " -> ");
            head = head.next;
        }
        System.out.println("NULL");
    }

    // Q17: Insert at beginning
    static Node insertBeginning(Node head, int value) {
        Node node = new Node(value);
        node.next = head;
        return node;
    }

    // Q18: Insert at end
    static Node insertEnd(Node head, int value) {
        Node node = new Node(value);

        if (head == null) return node;

        Node current = head;
        while (current.next != null)
            current = current.next;

        current.next = node;
        return head;
    }

    // Q19: Delete first occurrence
    static Node deleteValue(Node head, int value) {
        if (head == null) return null;

        if (head.data == value)
            return head.next;

        Node current = head;
        while (current.next != null &&
               current.next.data != value) {
            current = current.next;
        }

        if (current.next != null)
            current.next = current.next.next;

        return head;
    }

    // Q20: Search; return zero-based position or -1
    static int search(Node head, int value) {
        int position = 0;

        while (head != null) {
            if (head.data == value) return position;
            head = head.next;
            position++;
        }

        return -1;
    }

    public static void main(String[] args) {
        Node head = null;

        head = insertEnd(head, 10);
        head = insertEnd(head, 20);
        head = insertEnd(head, 30);
        System.out.print("Q16: ");
        display(head);

        head = insertBeginning(head, 5);
        System.out.print("Q17: ");
        display(head);

        head = insertEnd(head, 40);
        System.out.print("Q18: ");
        display(head);

        head = deleteValue(head, 20);
        System.out.print("Q19: ");
        display(head);

        System.out.println("Q20: Position of 30 = " + search(head, 30));
        System.out.println("Position of 99 = " + search(head, 99));
    }
}
```
MD,

'Q21. Find the middle node' => <<<'MD'
## Solution
Slow and fast pointers: `slow` moves one node, `fast` moves two. When `fast` reaches the end, `slow` is at the middle (the second middle for an even length).

## Dry run for 10 → 20 → 30 → 40 → 50
| Step | slow | fast |
|---|---|---|
| start | 10 | 10 |
| 1 | 20 | 30 |
| 2 | 30 | 50 (fast->next is NULL → stop) |

Middle = 30.

## Complexity
- O(n) time, O(1) space: one pass, no length count needed.
MD,

'Q22. Reverse a linked list iteratively' => <<<'MD'
## Solution
Walk the list once, turning each `next` pointer backwards. Keep three pointers: `prev`, `cur` and `next`.

## Step by step
1. `prev = NULL`, `cur = head`.
2. While `cur`: save `next = cur->next`; set `cur->next = prev`; move `prev = cur`, `cur = next`.
3. `prev` is the new head.

## Dry run (first two steps)
| cur | Action | Reversed part |
|---|---|---|
| 10 | 10 → NULL | 10 |
| 20 | 20 → 10 | 20 → 10 |
| … | … | 50 → 40 → 30 → 20 → 10 |

## Complexity
- O(n) time, O(1) space.
MD,

'Q23. Reverse a linked list recursively' => <<<'MD'
## Solution
Reverse the rest of the list first, then hook the current node after its old successor.

## Step by step
1. Base case: empty list or single node → return it.
2. `newHead = reverseRecursive(head->next)` (the rest is now reversed and ends at `head->next`).
3. `head->next->next = head` (old successor now points back to head); `head->next = NULL`.
4. Return `newHead` unchanged up the chain.

## Complexity
- O(n) time; O(n) space for the recursion stack (the iterative version uses O(1)).
MD,

'Q24. Detect a cycle (Floyd\'s algorithm)' => <<<'MD'
## Solution
Move `slow` one step and `fast` two steps. If there is a cycle, `fast` laps `slow` and they meet; if `fast` reaches NULL, there is no cycle.

## Why they must meet
Inside the cycle the gap between `fast` and `slow` shrinks by one node every step, so it reaches zero within one lap.

## Example
`10 → 20 → 30 → 40 → 50 → back to 30`: the pointers meet inside the loop → "Yes".

## Complexity
- O(n) time, O(1) space (a hash set of visited nodes also works but needs O(n) space).
MD,

'Q25. Find the starting node of a cycle' => <<<'MD'
## Solution
After `slow` and `fast` meet, move `slow` back to `head` and advance both one step at a time; they meet again exactly at the start of the cycle.

## Why it works
Let `a` = distance from head to the cycle start, `b` = distance from the start to the meeting point, `c` = cycle length. `fast` travelled twice as far as `slow`: `2(a + b) = a + b + k·c`, so `a = k·c − b`. Walking `a` steps from the head and `a` steps from the meeting point therefore lands both pointers on the cycle start.

## Example
`10 → 20 → 30 → 40 → 50 → 30`: the cycle starts at 30.

## Complexity
- O(n) time, O(1) space.
MD,

'Q21–Q25. Complete programs (C, C++, Java)' => <<<'MD'
## Complexity
| Question | Time | Extra space |
|---|---|---|
| 21. Find middle | O(n) | O(1) |
| 22. Reverse iteratively | O(n) | O(1) |
| 23. Reverse recursively | O(n) | O(n) call stack |
| 24. Detect cycle | O(n) | O(1) |
| 25. Find cycle start | O(n) | O(1) |

## Expected output
```text
Original: 10 -> 20 -> 30 -> 40 -> 50 -> NULL
Q21 Middle: 30
Q22 Reversed iteratively: 50 -> 40 -> 30 -> 20 -> 10 -> NULL
Q23 Reversed recursively: 10 -> 20 -> 30 -> 40 -> 50 -> NULL
Q24 Has cycle: Yes
Q25 Cycle starts at: 30
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

// Q21. Find middle node (second middle for even length)
Node* findMiddle(Node *head) {
    Node *slow = head, *fast = head;
    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
    }
    return slow;
}

// Q22. Reverse iteratively
Node* reverseIterative(Node *head) {
    Node *prev = NULL, *cur = head;

    while (cur) {
        Node *next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }
    return prev;
}

// Q23. Reverse recursively
Node* reverseRecursive(Node *head) {
    if (!head || !head->next) return head;

    Node *newHead = reverseRecursive(head->next);
    head->next->next = head;
    head->next = NULL;
    return newHead;
}

// Q24. Detect cycle using Floyd's algorithm
int hasCycle(Node *head) {
    Node *slow = head, *fast = head;

    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
        if (slow == fast) return 1;
    }
    return 0;
}

// Q25. Find the starting node of a cycle
Node* cycleStart(Node *head) {
    Node *slow = head, *fast = head;

    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;

        if (slow == fast) {
            slow = head;
            while (slow != fast) {
                slow = slow->next;
                fast = fast->next;
            }
            return slow;
        }
    }
    return NULL;
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
    for (int i = 10; i <= 50; i += 10)
        head = insertEnd(head, i);

    printf("Original: ");
    display(head);

    Node *mid = findMiddle(head);
    printf("Q21 Middle: %d\n", mid ? mid->data : -1);

    Node *reversed = reverseIterative(head);
    printf("Q22 Reversed iteratively: ");
    display(reversed);

    head = reverseRecursive(reversed);
    printf("Q23 Reversed recursively: ");
    display(head);

    // Q24 and Q25: create a cycle from the last node to node 30.
    Node *third = head->next->next;
    Node *last = head;
    while (last->next) last = last->next;
    last->next = third;

    printf("Q24 Has cycle: %s\n",
           hasCycle(head) ? "Yes" : "No");

    Node *start = cycleStart(head);
    printf("Q25 Cycle starts at: %d\n",
           start ? start->data : -1);

    // Break the cycle before freeing the list.
    last->next = NULL;
    freeList(head);
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

// Q21
Node* findMiddle(Node* head) {
    Node *slow = head, *fast = head;
    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
    }
    return slow;
}

// Q22
Node* reverseIterative(Node* head) {
    Node *prev = nullptr, *cur = head;

    while (cur) {
        Node* next = cur->next;
        cur->next = prev;
        prev = cur;
        cur = next;
    }
    return prev;
}

// Q23
Node* reverseRecursive(Node* head) {
    if (!head || !head->next) return head;

    Node* newHead = reverseRecursive(head->next);
    head->next->next = head;
    head->next = nullptr;
    return newHead;
}

// Q24
bool hasCycle(Node* head) {
    Node *slow = head, *fast = head;

    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;
        if (slow == fast) return true;
    }
    return false;
}

// Q25
Node* cycleStart(Node* head) {
    Node *slow = head, *fast = head;

    while (fast && fast->next) {
        slow = slow->next;
        fast = fast->next->next;

        if (slow == fast) {
            slow = head;
            while (slow != fast) {
                slow = slow->next;
                fast = fast->next;
            }
            return slow;
        }
    }
    return nullptr;
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
    for (int i = 10; i <= 50; i += 10)
        head = insertEnd(head, i);

    cout << "Original: ";
    display(head);

    cout << "Q21 Middle: " << findMiddle(head)->data << '\n';

    Node* reversed = reverseIterative(head);
    cout << "Q22 Reversed iteratively: ";
    display(reversed);

    head = reverseRecursive(reversed);
    cout << "Q23 Reversed recursively: ";
    display(head);

    Node* third = head->next->next;
    Node* last = head;
    while (last->next) last = last->next;
    last->next = third;

    cout << "Q24 Has cycle: "
         << (hasCycle(head) ? "Yes" : "No") << '\n';

    Node* start = cycleStart(head);
    cout << "Q25 Cycle starts at: "
         << (start ? start->data : -1) << '\n';

    last->next = nullptr;
    freeList(head);
    return 0;
}
```

## Java
```java
public class LinkedList21To25 {

    static class Node {
        int data;
        Node next;

        Node(int value) {
            data = value;
        }
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

    // Q21
    static Node findMiddle(Node head) {
        Node slow = head, fast = head;

        while (fast != null && fast.next != null) {
            slow = slow.next;
            fast = fast.next.next;
        }
        return slow;
    }

    // Q22
    static Node reverseIterative(Node head) {
        Node prev = null, cur = head;

        while (cur != null) {
            Node next = cur.next;
            cur.next = prev;
            prev = cur;
            cur = next;
        }
        return prev;
    }

    // Q23
    static Node reverseRecursive(Node head) {
        if (head == null || head.next == null) return head;

        Node newHead = reverseRecursive(head.next);
        head.next.next = head;
        head.next = null;
        return newHead;
    }

    // Q24
    static boolean hasCycle(Node head) {
        Node slow = head, fast = head;

        while (fast != null && fast.next != null) {
            slow = slow.next;
            fast = fast.next.next;
            if (slow == fast) return true;
        }
        return false;
    }

    // Q25
    static Node cycleStart(Node head) {
        Node slow = head, fast = head;

        while (fast != null && fast.next != null) {
            slow = slow.next;
            fast = fast.next.next;

            if (slow == fast) {
                slow = head;
                while (slow != fast) {
                    slow = slow.next;
                    fast = fast.next;
                }
                return slow;
            }
        }
        return null;
    }

    public static void main(String[] args) {
        Node head = null;
        for (int i = 10; i <= 50; i += 10)
            head = insertEnd(head, i);

        System.out.print("Original: ");
        display(head);

        System.out.println("Q21 Middle: " + findMiddle(head).data);

        Node reversed = reverseIterative(head);
        System.out.print("Q22 Reversed iteratively: ");
        display(reversed);

        head = reverseRecursive(reversed);
        System.out.print("Q23 Reversed recursively: ");
        display(head);

        Node third = head.next.next;
        Node last = head;
        while (last.next != null) last = last.next;
        last.next = third;

        System.out.println("Q24 Has cycle: " +
                           (hasCycle(head) ? "Yes" : "No"));

        Node start = cycleStart(head);
        System.out.println("Q25 Cycle starts at: " +
                           (start == null ? -1 : start.data));

        // Break cycle so the list can be traversed safely afterward.
        last.next = null;
    }
}
```
MD,

];
