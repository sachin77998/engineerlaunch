<?php

// DSA › Array and String, Searching: concept lessons and problems, in depth.
return [

'What is an Array and a String?' => <<<'MD'
## Array
An array stores elements of the same type in contiguous (adjacent) memory locations, accessed by an index starting at 0. Because the address of element i is base + i × size, reading any element takes O(1).

```text
index:   0    1    2    3    4
value: [ 12 | 45 | 7  | 23 | 56 ]
address: 1000 1004 1008 1012 1016   (4-byte ints)
```

```java
int[] marks = {12, 45, 7, 23, 56};
System.out.println(marks[2]);        // 7  (O(1) access)
marks[2] = 70;                       // O(1) update
int sum = 0;
for (int m : marks) sum += m;        // O(n) traversal
System.out.println(sum);             // 206
```

## Operation complexity
- Access/update by index: O(1).
- Search unsorted: O(n). Search sorted (binary search): O(log n).
- Insert/delete at the end of a dynamic array (ArrayList): amortised O(1).
- Insert/delete at the start or middle: O(n), because elements shift.

## Static vs dynamic arrays
Java arrays have a fixed size. ArrayList grows by allocating a larger array (about 1.5×) and copying, which is why appends are amortised O(1).

## String
A string is a sequence of characters. In Java, String is immutable: every "modification" creates a new object. Use StringBuilder when building strings in a loop.

```java
String s = "ascendia";
System.out.println(s.length());          // 8
System.out.println(s.charAt(0));         // a
System.out.println(s.substring(0, 3));   // asc
System.out.println(s.indexOf("end"));    // 2

StringBuilder sb = new StringBuilder();
for (int i = 0; i < 3; i++) sb.append(i).append(',');
System.out.println(sb);                  // 0,1,2,
```

Note: s1 == s2 compares references; always compare string content with equals().

## Common techniques for array/string problems
- Two pointers (from both ends or slow/fast).
- Prefix sums for range totals.
- Sliding window for subarrays/substrings.
- Hashing (HashMap/HashSet) for counts and lookups.
- Sorting first to simplify the problem.

## Advantages and disadvantages
- O(1) random access, cache-friendly, low memory overhead.
- Fixed size (static arrays) and costly insertion/deletion in the middle.
MD,

'Check if Array is Sorted' => <<<'MD'
## Problem
Return true if the array is sorted in non-decreasing order.

```text
[1, 2, 2, 5, 9] → true
[1, 4, 3, 5]    → false
```

## Approach: compare neighbours (optimal)
If any element is smaller than the one before it, the array is not sorted.

```java
static boolean isSorted(int[] a) {
    for (int i = 1; i < a.length; i++) {
        if (a[i] < a[i - 1]) return false;
    }
    return true;
}
```

## Brute force for comparison
Copy and sort the array, then compare with the original: O(n log n) time, O(n) space – unnecessary work.

## Recursive version
```java
static boolean isSortedRec(int[] a, int n) {
    if (n <= 1) return true;
    return a[n - 1] >= a[n - 2] && isSortedRec(a, n - 1);
}
```

## Complexity
- Neighbour check: Time O(n), Space O(1).
- Recursive: Time O(n), Space O(n) call stack.

Note: empty and single-element arrays are sorted; for strictly increasing use <= instead of <.
MD,

'Reverse Array' => <<<'MD'
## Problem
Reverse the elements of an array in place.

```text
[1, 2, 3, 4, 5] → [5, 4, 3, 2, 1]
```

## Approach 1: extra array
Copy elements into a new array from the end. Time O(n), Space O(n).

## Approach 2: two pointers (optimal)
Swap the first and last elements, move inwards, stop when the pointers meet.

```java
static void reverse(int[] a) {
    int left = 0, right = a.length - 1;
    while (left < right) {
        int temp = a[left];
        a[left++] = a[right];
        a[right--] = temp;
    }
}
```

## Dry run
[1,2,3,4,5]: swap 1↔5 → [5,2,3,4,1]; swap 2↔4 → [5,4,3,2,1]; left == right → stop.

## Complexity
- Extra array: O(n) time, O(n) space.
- Two pointers: O(n) time, O(1) space.

Note: the same idea reverses a string (on a char array), a range of an array (used in array rotation) or a linked list's values.
MD,

'Rotate Array' => <<<'MD'
## Problem
Rotate an array to the right by k steps.

```text
[1, 2, 3, 4, 5, 6, 7], k = 3 → [5, 6, 7, 1, 2, 3, 4]
```

## Approach 1: rotate one step k times
Move the last element to the front k times. Time O(n·k).

## Approach 2: extra array
Place a[i] at index (i + k) % n in a new array. Time O(n), Space O(n).

```java
static void rotateExtra(int[] a, int k) {
    int n = a.length;
    int[] res = new int[n];
    for (int i = 0; i < n; i++) res[(i + k) % n] = a[i];
    System.arraycopy(res, 0, a, 0, n);
}
```

## Approach 3: reversal algorithm (optimal)
1. Reverse the whole array.
2. Reverse the first k elements.
3. Reverse the remaining n − k elements.

```java
static void rotate(int[] a, int k) {
    int n = a.length;
    k %= n;                              // k may be larger than n
    reverse(a, 0, n - 1);
    reverse(a, 0, k - 1);
    reverse(a, k, n - 1);
}
static void reverse(int[] a, int l, int r) {
    while (l < r) { int t = a[l]; a[l++] = a[r]; a[r--] = t; }
}
```

## Dry run (k = 3)
- Reverse all: [7,6,5,4,3,2,1]
- Reverse first 3: [5,6,7,4,3,2,1]
- Reverse rest: [5,6,7,1,2,3,4] ✓

## Complexity
- One step at a time: O(n·k). Extra array: O(n) time, O(n) space. Reversal: O(n) time, O(1) space.
MD,

'Generate All Subarrays' => <<<'MD'
## Problem
Print every contiguous subarray of an array.

```text
[1, 2, 3] → [1] [1,2] [1,2,3] [2] [2,3] [3]
```

## Counting them
For n elements there are n(n+1)/2 subarrays (choose a start i and an end j ≥ i). For n = 3 that is 6.

## Approach: two loops for start and end
```java
static void printSubarrays(int[] a) {
    for (int start = 0; start < a.length; start++) {
        StringBuilder sb = new StringBuilder("[");
        for (int end = start; end < a.length; end++) {
            sb.append(end > start ? "," : "").append(a[end]);
            System.out.println(sb + "]");
        }
    }
}
```

```text
Output
[1]
[1,2]
[1,2,3]
[2]
[2,3]
[3]
```

## Recursive version
```java
static void subarrays(int[] a, int start, int end) {
    if (end == a.length) return;
    if (start > end) { subarrays(a, 0, end + 1); return; }
    System.out.println(Arrays.toString(Arrays.copyOfRange(a, start, end + 1)));
    subarrays(a, start + 1, end);
}
```

## Complexity
- Generating all subarrays: O(n²) of them; printing each costs up to O(n), so O(n³) total output work; O(1) extra space besides the output.

Note: subarray (contiguous) ≠ subsequence (keeps order, may skip elements, 2^n of them) ≠ subset (order irrelevant). Most "maximum subarray" problems avoid enumeration using Kadane's algorithm or sliding windows.
MD,

'Arrange Array by Sign' => <<<'MD'
## Problem
Given an array with equal numbers of positive and negative integers, rearrange it so signs alternate, starting with a positive, keeping the relative order within each sign.

```text
[3, 1, -2, -5, 2, -4] → [3, -2, 1, -5, 2, -4]
```

## Approach 1: separate lists, then merge
Collect positives and negatives in order, then fill even indices with positives and odd indices with negatives. Two passes.

## Approach 2: single pass with index pointers (optimal)
```java
static int[] rearrange(int[] a) {
    int[] res = new int[a.length];
    int pos = 0, neg = 1;
    for (int x : a) {
        if (x > 0) { res[pos] = x; pos += 2; }
        else       { res[neg] = x; neg += 2; }
    }
    return res;
}
```

## Dry run
3 → res[0]; 1 → res[2]; −2 → res[1]; −5 → res[3]; 2 → res[4]; −4 → res[5] → [3, −2, 1, −5, 2, −4].

## Complexity
- Time O(n), Space O(n) for the result.

## Variant: unequal counts
Alternate while both lists have elements, then append the leftovers in order.

Note: an in-place O(1)-space version exists but loses the relative order unless you use O(n²) rotations.
MD,

'Best Time to Buy and Sell Stock' => <<<'MD'
## Problem
Given daily prices, choose one day to buy and a later day to sell to maximise profit. Return 0 if no profit is possible.

```text
prices = [7, 1, 5, 3, 6, 4] → buy at 1, sell at 6 → profit 5
prices = [7, 6, 4, 3, 1]    → 0
```

## Approach 1: try every pair (brute force)
```java
static int maxProfitBrute(int[] p) {
    int best = 0;
    for (int i = 0; i < p.length; i++)
        for (int j = i + 1; j < p.length; j++) best = Math.max(best, p[j] - p[i]);
    return best;
}
```
Time O(n²).

## Approach 2: track the minimum so far (optimal)
For each day, the best sale today uses the cheapest price seen before today.

```java
static int maxProfit(int[] prices) {
    int minPrice = Integer.MAX_VALUE, best = 0;
    for (int price : prices) {
        minPrice = Math.min(minPrice, price);
        best = Math.max(best, price - minPrice);
    }
    return best;
}
```

## Dry run on [7, 1, 5, 3, 6, 4]
- 7: min 7, best 0
- 1: min 1, best 0
- 5: best 4
- 3: best 4
- 6: best 5
- 4: best 5 → answer 5

## Complexity
- Brute force: O(n²) time. Optimal: O(n) time, O(1) space.

## Follow-ups
- Unlimited transactions: add every positive day-to-day increase (greedy).
- At most two/k transactions or with cooldown/fees: dynamic programming.
MD,

'Leaders in an Array' => <<<'MD'
## Problem
An element is a leader if it is greater than or equal to every element to its right. The last element is always a leader.

```text
[16, 17, 4, 3, 5, 2] → leaders 17, 5, 2
```

## Approach 1: check every element (brute force)
For each i, scan all j > i. Time O(n²).

## Approach 2: scan from the right with a running maximum (optimal)
```java
static List<Integer> leaders(int[] a) {
    List<Integer> result = new ArrayList<>();
    int maxRight = Integer.MIN_VALUE;
    for (int i = a.length - 1; i >= 0; i--) {
        if (a[i] >= maxRight) {
            result.add(a[i]);
            maxRight = a[i];
        }
    }
    Collections.reverse(result);   // original left-to-right order
    return result;
}
```

## Dry run
From the right: 2 (leader, max 2) → 5 (leader, max 5) → 3 → 4 → 17 (leader, max 17) → 16. Reversed: [17, 5, 2].

## Complexity
- Brute force: O(n²). Right-to-left scan: O(n) time, O(1) extra space besides the output.
MD,

'Check Same Strings' => <<<'MD'
## Problem
Check whether two strings are equal (same characters in the same order). Often asked together with "ignore case" and "same characters in any order (anagram)" variants.

## Exact equality
```java
static boolean same(String a, String b) {
    if (a == null || b == null) return a == b;
    if (a.length() != b.length()) return false;
    for (int i = 0; i < a.length(); i++) {
        if (a.charAt(i) != b.charAt(i)) return false;
    }
    return true;           // equivalent to a.equals(b)
}
```

## Why not ==?
```java
String x = "java";
String y = new String("java");
System.out.println(x == y);        // false – different objects
System.out.println(x.equals(y));   // true – same content
```

## Ignoring case
a.equalsIgnoreCase(b), or compare Character.toLowerCase of each pair.

## Same characters, any order (anagram)
Count characters with an int[26] (or a HashMap for Unicode): increment for a, decrement for b, then check all zeros.

```java
static boolean sameChars(String a, String b) {
    if (a.length() != b.length()) return false;
    int[] count = new int[26];
    for (int i = 0; i < a.length(); i++) { count[a.charAt(i) - 'a']++; count[b.charAt(i) - 'a']--; }
    for (int c : count) if (c != 0) return false;
    return true;
}
```

## Complexity
- Equality: O(n) time, O(1) space.
- Anagram by counting: O(n) time, O(1) space (26 counters). Sorting both strings instead costs O(n log n).
MD,

'Reverse a String' => <<<'MD'
## Problem
Reverse a string: "ascendia" → "aidnecsa".

## Approach 1: StringBuilder
```java
String reversed = new StringBuilder("ascendia").reverse().toString();
```

## Approach 2: two pointers on a char array (in place)
```java
static String reverse(String s) {
    char[] c = s.toCharArray();
    for (int l = 0, r = c.length - 1; l < r; l++, r--) {
        char t = c[l]; c[l] = c[r]; c[r] = t;
    }
    return new String(c);
}
```

## Approach 3: recursion
```java
static String reverseRec(String s) {
    if (s.length() <= 1) return s;
    return reverseRec(s.substring(1)) + s.charAt(0);
}
```
Easy to write but O(n²) because each substring and concatenation copies characters.

## Avoid this
```java
String r = "";
for (char ch : s.toCharArray()) r = ch + r;   // creates a new String each time → O(n²)
```

## Complexity
- StringBuilder / two pointers: O(n) time, O(n) space for the new string.
- Recursive substring version: O(n²) time.

## Variation: reverse the words of a sentence
"I love Java" → "Java love I": split on spaces, reverse the array of words, join.
MD,

'Longest Common Prefix' => <<<'MD'
## Problem
Find the longest prefix shared by all strings in an array.

```text
["flower", "flow", "flight"] → "fl"
["dog", "racecar", "car"]    → ""
```

## Approach 1: vertical scanning (optimal and simple)
Compare characters column by column; stop at the first mismatch or the end of any string.

```java
static String longestCommonPrefix(String[] strs) {
    if (strs.length == 0) return "";
    for (int i = 0; i < strs[0].length(); i++) {
        char c = strs[0].charAt(i);
        for (int j = 1; j < strs.length; j++) {
            if (i == strs[j].length() || strs[j].charAt(i) != c) return strs[0].substring(0, i);
        }
    }
    return strs[0];
}
```

## Approach 2: sort and compare first and last
After sorting, the common prefix of the whole array equals the common prefix of the first and last strings.

```java
static String lcpSorted(String[] strs) {
    Arrays.sort(strs);
    String a = strs[0], b = strs[strs.length - 1];
    int i = 0;
    while (i < a.length() && i < b.length() && a.charAt(i) == b.charAt(i)) i++;
    return a.substring(0, i);
}
```

## Complexity
- Vertical scanning: O(S) where S is the total number of characters, O(1) extra space.
- Sorting: O(n · m log n) for n strings of length m.
- A trie also works (useful when many queries share the same word list).
MD,

'What is Searching and Binary Search?' => <<<'MD'
## Searching
Searching means finding whether (and where) a target value exists in a collection.
- Linear search checks elements one by one: works on any array, O(n).
- Binary search repeatedly halves a sorted range: O(log n).
- Hashing (HashMap/HashSet) finds keys in O(1) average time.

## Binary search intuition
Looking up a word in a dictionary, you open the middle; if your word comes earlier, you discard the second half, and so on. Each step halves the search space, so 1,000,000 elements need at most about 20 comparisons (2^20 ≈ 10^6).

```text
sorted: [2, 5, 8, 12, 16, 23, 38, 56, 72, 91], target 23
low=0, high=9 → mid=4 (16) < 23 → low=5
low=5, high=9 → mid=7 (56) > 23 → high=6
low=5, high=6 → mid=5 (23) found at index 5
```

```java
static int binarySearch(int[] a, int target) {
    int low = 0, high = a.length - 1;
    while (low <= high) {
        int mid = low + (high - low) / 2;   // avoids int overflow of (low + high)
        if (a[mid] == target) return mid;
        if (a[mid] < target) low = mid + 1; else high = mid - 1;
    }
    return -1;
}
```

## Requirements and pitfalls
- The data must be sorted (or the condition must be monotonic).
- Use mid = low + (high − low) / 2 to avoid overflow.
- Decide carefully between low <= high and low < high and how you move the bounds, or you will loop forever.

## Binary search on the answer
Many problems are not about arrays at all: find the smallest capacity, speed or value x for which a yes/no check becomes true (e.g. "minimum days to ship packages"). If the check is monotonic, binary search over the range of possible answers.

## Complexity
- Linear search: O(n) time, O(1) space.
- Binary search: O(log n) time, O(1) space iterative (O(log n) recursive).
MD,

'Linear Search' => <<<'MD'
## Problem
Find the index of a target in an array (sorted or not); return −1 if absent.

## Algorithm
Check each element from left to right.

```java
static int linearSearch(int[] a, int target) {
    for (int i = 0; i < a.length; i++) {
        if (a[i] == target) return i;
    }
    return -1;
}
```

```text
a = [10, 50, 30, 70, 80], target 30 → index 2
target 25 → -1
```

## Sentinel optimisation
Place the target at the end so the loop does not need an i < n check on each iteration; restore the last element afterwards. Saves one comparison per step.

## Complexity
- Best case O(1) (first element), worst and average O(n).
- Space O(1).

## When linear search is the right choice
- Unsorted data searched only once (sorting first would cost O(n log n)).
- Very small arrays.
- Linked lists, where binary search is not possible without random access.

Note: for repeated lookups, build a HashSet/HashMap once (O(n)) and then search in O(1).
MD,

'Binary Search' => <<<'MD'
## Problem
Given a sorted array and a target, return the target's index or −1.

## Iterative (preferred)
```java
static int search(int[] a, int target) {
    int lo = 0, hi = a.length - 1;
    while (lo <= hi) {
        int mid = lo + (hi - lo) / 2;
        if (a[mid] == target) return mid;
        else if (a[mid] < target) lo = mid + 1;
        else hi = mid - 1;
    }
    return -1;
}
```

## Recursive
```java
static int searchRec(int[] a, int target, int lo, int hi) {
    if (lo > hi) return -1;
    int mid = lo + (hi - lo) / 2;
    if (a[mid] == target) return mid;
    return a[mid] < target ? searchRec(a, target, mid + 1, hi) : searchRec(a, target, lo, mid - 1);
}
```

## Dry run
a = [1, 3, 5, 7, 9, 11], target 9: mid=2 (5) → lo=3; mid=4 (9) → found at 4.

## Lower bound (first index with a[i] >= target)
```java
static int lowerBound(int[] a, int target) {
    int lo = 0, hi = a.length;           // hi is exclusive
    while (lo < hi) {
        int mid = lo + (hi - lo) / 2;
        if (a[mid] < target) lo = mid + 1; else hi = mid;
    }
    return lo;                            // insertion position
}
```

## Complexity
- Time O(log n). Space O(1) iterative, O(log n) recursive.

Note: Java provides Arrays.binarySearch and Collections.binarySearch; they return −(insertionPoint) − 1 when the key is absent.
MD,

'First and Last Occurrence' => <<<'MD'
## Problem
In a sorted array with duplicates, find the first and last index of a target.

```text
[5, 7, 7, 8, 8, 10], target 8 → [3, 4]
target 6 → [-1, -1]
```

## Approach 1: linear scan
Find the first match from the left and the first from the right. O(n).

## Approach 2: two binary searches (optimal)
When a[mid] equals the target, record it and keep searching left (for the first) or right (for the last).

```java
static int[] searchRange(int[] a, int target) {
    return new int[]{ find(a, target, true), find(a, target, false) };
}
static int find(int[] a, int target, boolean first) {
    int lo = 0, hi = a.length - 1, ans = -1;
    while (lo <= hi) {
        int mid = lo + (hi - lo) / 2;
        if (a[mid] == target) {
            ans = mid;
            if (first) hi = mid - 1; else lo = mid + 1;
        } else if (a[mid] < target) lo = mid + 1;
        else hi = mid - 1;
    }
    return ans;
}
```

## Count of occurrences
last − first + 1 (when found) – also O(log n).

## Complexity
- Linear: O(n). Two binary searches: O(log n) time, O(1) space.
MD,

'Search in Rotated Sorted Array' => <<<'MD'
## Problem
A sorted array of distinct values was rotated at an unknown pivot (e.g. [0,1,2,4,5,6,7] → [4,5,6,7,0,1,2]). Find the index of a target in O(log n).

## Key observation
After choosing mid, at least one half (lo..mid or mid..hi) is still sorted. Check whether the target lies inside the sorted half; if yes search there, otherwise search the other half.

```java
static int searchRotated(int[] a, int target) {
    int lo = 0, hi = a.length - 1;
    while (lo <= hi) {
        int mid = lo + (hi - lo) / 2;
        if (a[mid] == target) return mid;
        if (a[lo] <= a[mid]) {                                  // left half sorted
            if (target >= a[lo] && target < a[mid]) hi = mid - 1; else lo = mid + 1;
        } else {                                                // right half sorted
            if (target > a[mid] && target <= a[hi]) lo = mid + 1; else hi = mid - 1;
        }
    }
    return -1;
}
```

## Dry run: [4,5,6,7,0,1,2], target 0
- mid=3 (7); left half [4..7] sorted, 0 not in it → lo=4.
- mid=5 (1); left half [0..1] sorted, 0 in it → hi=4.
- mid=4 (0) → found at 4.

## Complexity
- Time O(log n), Space O(1). Linear search would be O(n).

Note: with duplicates (e.g. [1,0,1,1,1]) you cannot always tell which half is sorted; shrink both ends when a[lo] == a[mid] == a[hi], giving O(n) worst case.
MD,

'Find Peak Element' => <<<'MD'
## Problem
A peak is an element greater than its neighbours (treat out-of-range neighbours as −∞). Return the index of any peak.

```text
[1, 2, 3, 1]          → 2 (value 3)
[1, 2, 1, 3, 5, 6, 4] → 1 or 5
```

## Approach 1: linear scan
Return the first i where a[i] > a[i+1] (or the last index). O(n).

## Approach 2: binary search on the slope (optimal)
If a[mid] < a[mid+1], the values rise to the right, so a peak exists on the right; otherwise a peak exists at mid or to its left.

```java
static int findPeak(int[] a) {
    int lo = 0, hi = a.length - 1;
    while (lo < hi) {
        int mid = lo + (hi - lo) / 2;
        if (a[mid] < a[mid + 1]) lo = mid + 1;   // climb uphill
        else hi = mid;
    }
    return lo;
}
```

## Why it works
Going uphill must eventually reach a peak because the end of the array acts as −∞. Each step keeps a peak inside [lo, hi].

## Complexity
- Linear: O(n). Binary search: O(log n) time, O(1) space.
MD,

'Square Root Using Binary Search' => <<<'MD'
## Problem
Return the integer square root of n (floor of √n) without using Math.sqrt.

```text
n = 8  → 2   (2² = 4 ≤ 8 < 9 = 3²)
n = 49 → 7
```

## Approach 1: linear
Increase i while (i+1)² ≤ n. O(√n).

## Approach 2: binary search on the answer (optimal)
The answer lies in [0, n] and mid² ≤ n is monotonic, so binary search for the largest such mid.

```java
static int mySqrt(int n) {
    long lo = 0, hi = n, ans = 0;
    while (lo <= hi) {
        long mid = lo + (hi - lo) / 2;
        if (mid * mid <= n) { ans = mid; lo = mid + 1; }   // mid works, try larger
        else hi = mid - 1;
    }
    return (int) ans;
}
```

## Dry run (n = 8)
- lo 0, hi 8 → mid 4 (16 > 8) → hi 3
- mid 1 (1 ≤ 8) → ans 1, lo 2
- mid 2 (4 ≤ 8) → ans 2, lo 3
- mid 3 (9 > 8) → hi 2 → stop. Answer 2.

## Complexity
- Linear: O(√n). Binary search: O(log n) time, O(1) space. Newton's method converges even faster.

Note: use long for mid × mid; with int it overflows for n near Integer.MAX_VALUE.
MD,

'Median of Two Sorted Arrays' => <<<'MD'
## Problem
Given two sorted arrays of sizes m and n, find the median of their combined elements in O(log(min(m, n))).

```text
[1, 3] and [2]    → 2.0
[1, 2] and [3, 4] → 2.5
```

## Approach 1: merge (simple)
Merge like merge sort and pick the middle. O(m + n) time and space (O(1) space if you only count to the middle).

## Approach 2: binary search on the partition (optimal)
Partition both arrays so the left halves together contain half of all elements and every left element ≤ every right element. Binary search the cut position i in the smaller array; the cut in the other array is j = (m + n + 1)/2 − i.

```java
static double findMedian(int[] a, int[] b) {
    if (a.length > b.length) return findMedian(b, a);          // search the smaller array
    int m = a.length, n = b.length, half = (m + n + 1) / 2;
    int lo = 0, hi = m;
    while (lo <= hi) {
        int i = (lo + hi) / 2, j = half - i;
        int aLeft  = i == 0 ? Integer.MIN_VALUE : a[i - 1];
        int aRight = i == m ? Integer.MAX_VALUE : a[i];
        int bLeft  = j == 0 ? Integer.MIN_VALUE : b[j - 1];
        int bRight = j == n ? Integer.MAX_VALUE : b[j];
        if (aLeft <= bRight && bLeft <= aRight) {               // correct partition
            if ((m + n) % 2 == 1) return Math.max(aLeft, bLeft);
            return (Math.max(aLeft, bLeft) + Math.min(aRight, bRight)) / 2.0;
        } else if (aLeft > bRight) hi = i - 1;                  // too many from a
        else lo = i + 1;                                        // too few from a
    }
    throw new IllegalArgumentException("Input arrays are not sorted");
}
```

## Complexity
- Merge: O(m + n). Partition binary search: O(log(min(m, n))) time, O(1) space.

Note: this is a classic "hard" interview question; explain the partition condition before coding.
MD,

];
