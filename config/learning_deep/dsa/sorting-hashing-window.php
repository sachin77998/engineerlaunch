<?php

// DSA › Sorting, Hashing & Two Pointer, Sliding Window & Prefix Sum: in depth.
return [

'What is Sorting and how to compare algorithms?' => <<<'MD'
## What it is
Sorting arranges elements in a defined order (ascending, descending or by a key such as salary or date). Sorted data makes searching (binary search), de-duplication, grouping and merging much faster.

## Comparing sorting algorithms
- Time complexity (best, average, worst).
- Space: in-place (O(1) extra) or not.
- Stability: a stable sort keeps equal elements in their original order (important when sorting records by a second key).
- Adaptivity: does it run faster on nearly sorted data?

## Summary table
- Bubble sort: O(n²) average/worst, O(n) best (with early exit), O(1) space, stable.
- Selection sort: O(n²) always, O(1) space, not stable, minimum number of swaps.
- Insertion sort: O(n²) worst, O(n) best on nearly sorted data, O(1) space, stable.
- Merge sort: O(n log n) always, O(n) space, stable.
- Quick sort: O(n log n) average, O(n²) worst, O(log n) stack space, not stable.
- Heap sort: O(n log n) always, O(1) space, not stable.
- Counting/radix sort: O(n + k) for small integer ranges, not comparison based.

## What Java uses
- Arrays.sort on primitives: dual-pivot quicksort.
- Arrays.sort on objects and Collections.sort: TimSort (merge + insertion sort), stable, O(n log n), very fast on partly sorted data.

```java
int[] scores = {72, 15, 90, 41};
Arrays.sort(scores);                                   // [15, 41, 72, 90]

List<Employee> staff = new ArrayList<>(...);
staff.sort(Comparator.comparing(Employee::department)
                     .thenComparing(Employee::salary, Comparator.reverseOrder()));
```

## Lower bound
Any comparison-based sort needs Ω(n log n) comparisons in the worst case, so merge, heap and quick sort are optimal in that model.

Note: in interviews, state stability and space alongside time complexity when you choose a sort.
MD,

'Bubble Sort' => <<<'MD'
## Idea
Repeatedly compare adjacent elements and swap them if they are in the wrong order. After each pass, the largest remaining element "bubbles" to the end.

```text
Pass 1 on [5, 1, 4, 2, 8]:
(5,1) swap → [1,5,4,2,8]
(5,4) swap → [1,4,5,2,8]
(5,2) swap → [1,4,2,5,8]
(5,8) ok   → 8 is in its final place
```

```java
static void bubbleSort(int[] a) {
    for (int pass = 0; pass < a.length - 1; pass++) {
        boolean swapped = false;
        for (int i = 0; i < a.length - 1 - pass; i++) {
            if (a[i] > a[i + 1]) {
                int t = a[i]; a[i] = a[i + 1]; a[i + 1] = t;
                swapped = true;
            }
        }
        if (!swapped) break;        // already sorted → stop early
    }
}
```

## Complexity
- Worst/average O(n²), best O(n) with the swapped flag, O(1) space, stable.

Note: used for teaching; real code uses library sorts.
MD,

'Selection Sort' => <<<'MD'
## Idea
Divide the array into a sorted prefix and an unsorted suffix. Repeatedly select the minimum of the unsorted part and swap it to the end of the sorted part.

```java
static void selectionSort(int[] a) {
    for (int i = 0; i < a.length - 1; i++) {
        int min = i;
        for (int j = i + 1; j < a.length; j++) if (a[j] < a[min]) min = j;
        int t = a[i]; a[i] = a[min]; a[min] = t;
    }
}
```

## Dry run on [64, 25, 12, 22, 11]
- i=0: min 11 → [11, 25, 12, 22, 64]
- i=1: min 12 → [11, 12, 25, 22, 64]
- i=2: min 22 → [11, 12, 22, 25, 64]
- i=3: already in place.

## Complexity
- O(n²) comparisons in every case, O(1) space, at most n−1 swaps.
- Not stable (a swap can jump an equal element over another).

Note: useful when writes are expensive (e.g. flash memory), because it performs the fewest swaps.
MD,

'Insertion Sort' => <<<'MD'
## Idea
Build the sorted array one element at a time, like sorting playing cards in your hand: take the next element and shift larger elements right until its correct position is found.

```java
static void insertionSort(int[] a) {
    for (int i = 1; i < a.length; i++) {
        int key = a[i], j = i - 1;
        while (j >= 0 && a[j] > key) {
            a[j + 1] = a[j];     // shift right
            j--;
        }
        a[j + 1] = key;
    }
}
```

## Dry run on [12, 11, 13, 5, 6]
- 11 → [11, 12, 13, 5, 6]
- 13 → no shift
- 5 → [5, 11, 12, 13, 6]
- 6 → [5, 6, 11, 12, 13]

## Complexity
- Worst/average O(n²), best O(n) for already sorted input, O(1) space, stable, adaptive.

Note: excellent for small or nearly sorted arrays; TimSort uses insertion sort for short runs.
MD,

'Merge Sort' => <<<'MD'
## Idea (divide and conquer)
1. Divide the array into two halves.
2. Recursively sort each half.
3. Merge the two sorted halves into one sorted array.

```text
[38, 27, 43, 3, 9, 82, 10]
split → [38, 27, 43, 3] [9, 82, 10]
       → [38, 27] [43, 3] [9, 82] [10]
sort+merge → [27, 38] [3, 43] [9, 82] [10]
           → [3, 27, 38, 43] [9, 10, 82]
           → [3, 9, 10, 27, 38, 43, 82]
```

```java
static void mergeSort(int[] a, int lo, int hi) {
    if (lo >= hi) return;
    int mid = lo + (hi - lo) / 2;
    mergeSort(a, lo, mid);
    mergeSort(a, mid + 1, hi);
    merge(a, lo, mid, hi);
}
static void merge(int[] a, int lo, int mid, int hi) {
    int[] tmp = new int[hi - lo + 1];
    int i = lo, j = mid + 1, k = 0;
    while (i <= mid && j <= hi) tmp[k++] = (a[i] <= a[j]) ? a[i++] : a[j++];   // <= keeps it stable
    while (i <= mid) tmp[k++] = a[i++];
    while (j <= hi) tmp[k++] = a[j++];
    System.arraycopy(tmp, 0, a, lo, tmp.length);
}
```

## Complexity
- Time O(n log n) in all cases: log n levels, O(n) merge work per level.
- Space O(n) for the temporary arrays. Stable.

## Uses
Sorting linked lists (no random access needed), external sorting of files too big for memory, counting inversions.
MD,

'Quick Sort' => <<<'MD'
## Idea
Pick a pivot, partition the array so smaller elements go left and larger go right, then recursively sort both sides. The pivot ends up in its final position.

```java
static void quickSort(int[] a, int lo, int hi) {
    if (lo >= hi) return;
    int p = partition(a, lo, hi);
    quickSort(a, lo, p - 1);
    quickSort(a, p + 1, hi);
}
// Lomuto partition with the last element as pivot
static int partition(int[] a, int lo, int hi) {
    int pivot = a[hi], i = lo;
    for (int j = lo; j < hi; j++) {
        if (a[j] < pivot) { swap(a, i, j); i++; }
    }
    swap(a, i, hi);
    return i;
}
static void swap(int[] a, int i, int j) { int t = a[i]; a[i] = a[j]; a[j] = t; }
```

## Dry run of one partition on [10, 80, 30, 90, 40, 50, 70], pivot 70
Elements < 70 move left: 10, 30, 40, 50 → pivot placed at index 4 → [10, 30, 40, 50, 70, 90, 80].

## Complexity
- Average O(n log n); worst O(n²) when the pivot is always the smallest/largest (e.g. sorted input with a last-element pivot).
- Space O(log n) recursion on average. In place, not stable.

## Avoiding the worst case
Random pivot, median-of-three pivot, or recursing into the smaller side first (limits stack depth to O(log n)).

Note: quicksort is usually fastest in practice because of good cache behaviour, which is why Java uses a quicksort variant for primitives.
MD,

'Heap Sort' => <<<'MD'
## Idea
1. Build a max-heap from the array (O(n)).
2. Repeatedly swap the root (largest) with the last element of the heap, shrink the heap by one and sift the new root down.

```java
static void heapSort(int[] a) {
    int n = a.length;
    for (int i = n / 2 - 1; i >= 0; i--) heapify(a, n, i);    // build max-heap
    for (int end = n - 1; end > 0; end--) {
        int t = a[0]; a[0] = a[end]; a[end] = t;               // move max to the end
        heapify(a, end, 0);
    }
}
static void heapify(int[] a, int size, int i) {
    while (true) {
        int largest = i, l = 2 * i + 1, r = 2 * i + 2;
        if (l < size && a[l] > a[largest]) largest = l;
        if (r < size && a[r] > a[largest]) largest = r;
        if (largest == i) return;
        int t = a[i]; a[i] = a[largest]; a[largest] = t;
        i = largest;
    }
}
```

## Complexity
- Time O(n log n) in all cases; space O(1) (in place); not stable.

Note: guarantees O(n log n) without extra memory, which is why it is the fallback in introsort when quicksort recursion gets too deep.
MD,

'Sort 0s, 1s and 2s' => <<<'MD'
## Problem
Sort an array containing only 0, 1 and 2 in a single pass without a library sort (Dutch National Flag problem).

```text
[2, 0, 2, 1, 1, 0] → [0, 0, 1, 1, 2, 2]
```

## Approach 1: counting
Count zeros, ones and twos, then overwrite the array. Two passes, O(n) time, O(1) space.

## Approach 2: three pointers (one pass, optimal)
- low: next position for 0; mid: current element; high: next position for 2.
- a[mid] == 0 → swap with low, low++, mid++.
- a[mid] == 1 → mid++.
- a[mid] == 2 → swap with high, high-- (do not advance mid; the swapped-in value is unchecked).

```java
static void sortColors(int[] a) {
    int low = 0, mid = 0, high = a.length - 1;
    while (mid <= high) {
        if (a[mid] == 0)      swap(a, low++, mid++);
        else if (a[mid] == 1) mid++;
        else                  swap(a, mid, high--);
    }
}
```

## Dry run on [2, 0, 1]
- mid 0 is 2 → swap with high → [1, 0, 2], high 1
- mid 0 is 1 → mid 1
- mid 1 is 0 → swap with low → [0, 1, 2]

## Complexity
- Counting: O(n), two passes. Dutch flag: O(n), one pass, O(1) space.
MD,

'Count Inversions' => <<<'MD'
## Problem
An inversion is a pair (i, j) with i < j and a[i] > a[j]. Count them; it measures how far the array is from sorted.

```text
[8, 4, 2, 1] → 6 inversions
[3, 1, 2]    → 2 ( (3,1) and (3,2) )
```

## Approach 1: check every pair
Two nested loops. O(n²).

## Approach 2: merge sort (optimal)
While merging two sorted halves, when an element from the right half is placed before remaining elements of the left half, it forms an inversion with each of them: add (mid − i + 1).

```java
static long countInversions(int[] a, int lo, int hi) {
    if (lo >= hi) return 0;
    int mid = lo + (hi - lo) / 2;
    long count = countInversions(a, lo, mid) + countInversions(a, mid + 1, hi);
    int[] tmp = new int[hi - lo + 1];
    int i = lo, j = mid + 1, k = 0;
    while (i <= mid && j <= hi) {
        if (a[i] <= a[j]) tmp[k++] = a[i++];
        else { tmp[k++] = a[j++]; count += mid - i + 1; }   // a[i..mid] all greater than a[j]
    }
    while (i <= mid) tmp[k++] = a[i++];
    while (j <= hi) tmp[k++] = a[j++];
    System.arraycopy(tmp, 0, a, lo, tmp.length);
    return count;
}
```

## Complexity
- Brute force O(n²). Merge sort O(n log n) time, O(n) space. A Fenwick tree also gives O(n log n).

## Real-world use
Ranking similarity (how differently two people ordered the same list) in recommendation systems.
MD,

'What is Hashing and the Two Pointer Technique?' => <<<'MD'
## Hashing
Hashing maps a key to an index in an array (bucket) using a hash function, giving O(1) average-time insert, lookup and delete. Java's HashMap and HashSet are hash tables.

```text
key "java" → hashCode() → index 3
buckets: [ ][ ][ ][ java→5 ][ ][ python→2 ][ ]
```

- Collisions (two keys in one bucket) are stored in a list or, in Java 8+, a balanced tree when a bucket grows large.
- When the table fills past its load factor (0.75), it resizes and rehashes.
- Worst case degrades to O(n) with a bad hash function; average stays O(1).

```java
Map<String, Integer> count = new HashMap<>();
for (String skill : List.of("java", "sql", "java")) count.merge(skill, 1, Integer::sum);
System.out.println(count);   // {java=2, sql=1}

Set<Integer> seen = new HashSet<>();
System.out.println(seen.add(5) + " " + seen.add(5));   // true false
```

Use hashing for counting frequencies, detecting duplicates, two-sum style lookups, grouping (anagrams) and caching.

Note: objects used as keys must implement equals() and hashCode() consistently.

## Two pointer technique
Use two indices that move through the data according to a rule, often turning O(n²) pair checks into O(n).
- Opposite ends: on a sorted array, move left or right depending on the current sum (pair sum, container with most water, palindrome check).
- Same direction (slow/fast): remove duplicates in place, partition arrays, linked-list middle and cycle detection.

```java
// Is there a pair with the target sum in a sorted array?
static boolean pairSum(int[] a, int target) {
    int l = 0, r = a.length - 1;
    while (l < r) {
        int s = a[l] + a[r];
        if (s == target) return true;
        if (s < target) l++; else r--;
    }
    return false;
}
```

## Complexity
- HashMap operations: O(1) average. Two pointers: O(n) time, O(1) space (after an O(n log n) sort if needed).
MD,

'Frequency of Elements' => <<<'MD'
## Problem
Count how many times each element appears.

```text
[10, 20, 20, 10, 10, 30] → 10:3, 20:2, 30:1
```

## Approach 1: nested loops
For each element, count its occurrences with a second loop (skipping elements already counted). O(n²).

## Approach 2: HashMap (optimal for general values)
```java
static Map<Integer, Integer> frequency(int[] a) {
    Map<Integer, Integer> freq = new LinkedHashMap<>();   // keeps first-seen order
    for (int x : a) freq.put(x, freq.getOrDefault(x, 0) + 1);
    return freq;
}
```

## Approach 3: counting array (small known range)
```java
int[] count = new int[101];          // values 0..100
for (int x : a) count[x]++;
```

## Approach 4: sort, then count runs
After sorting, equal values are adjacent; count consecutive runs. O(n log n), O(1) extra space.

## Complexity
- Nested loops O(n²). HashMap O(n) time, O(k) space for k distinct values. Counting array O(n + range).

## Real-world use
Counting skills across job postings, word counts, top error codes in logs.
MD,

'Two Sum' => <<<'MD'
## Problem
Return indices of the two numbers that add up to a target (exactly one solution exists; you may not use the same element twice).

```text
nums = [2, 7, 11, 15], target 9 → [0, 1]
```

## Approach 1: check every pair
O(n²) time, O(1) space.

## Approach 2: sort + two pointers
Sort (value, index) pairs and move pointers inward. O(n log n).

## Approach 3: HashMap of complements (optimal)
For each number, check whether target − number has been seen; if not, store the number with its index.

```java
static int[] twoSum(int[] nums, int target) {
    Map<Integer, Integer> seen = new HashMap<>();
    for (int i = 0; i < nums.length; i++) {
        Integer j = seen.get(target - nums[i]);
        if (j != null) return new int[]{j, i};
        seen.put(nums[i], i);
    }
    return new int[0];
}
```

## Dry run
- i=0 (2): need 7, not seen; store 2→0
- i=1 (7): need 2, seen at 0 → [0, 1]

## Complexity
- Pairs O(n²). Sort + pointers O(n log n). HashMap O(n) time, O(n) space.
MD,

'Longest Consecutive Sequence' => <<<'MD'
## Problem
Find the length of the longest run of consecutive integers in an unsorted array, in O(n).

```text
[100, 4, 200, 1, 3, 2] → 4  (1, 2, 3, 4)
```

## Approach 1: sort
Sort, then count consecutive runs (skipping duplicates). O(n log n).

## Approach 2: HashSet, start only at sequence beginnings (optimal)
A number x starts a sequence only if x − 1 is not in the set; from there count upward.

```java
static int longestConsecutive(int[] nums) {
    Set<Integer> set = new HashSet<>();
    for (int x : nums) set.add(x);
    int best = 0;
    for (int x : set) {
        if (!set.contains(x - 1)) {                 // x is the start of a run
            int length = 1;
            while (set.contains(x + length)) length++;
            best = Math.max(best, length);
        }
    }
    return best;
}
```

## Why it is O(n)
Each number is counted inside a while loop only once, as part of the run starting at its sequence beginning.

## Complexity
- Sorting O(n log n). HashSet O(n) time, O(n) space.
MD,

'Valid Anagram' => <<<'MD'
## Problem
Check whether t is an anagram of s (same letters with the same counts, any order).

```text
"listen", "silent" → true
"rat", "car"       → false
```

## Approach 1: sort both strings
Equal after sorting ⇔ anagrams. O(n log n).

```java
static boolean isAnagramSort(String s, String t) {
    char[] a = s.toCharArray(), b = t.toCharArray();
    Arrays.sort(a); Arrays.sort(b);
    return Arrays.equals(a, b);
}
```

## Approach 2: count characters (optimal)
```java
static boolean isAnagram(String s, String t) {
    if (s.length() != t.length()) return false;
    int[] count = new int[26];
    for (int i = 0; i < s.length(); i++) {
        count[s.charAt(i) - 'a']++;
        count[t.charAt(i) - 'a']--;
    }
    for (int c : count) if (c != 0) return false;
    return true;
}
```

## Complexity
- Sorting O(n log n). Counting O(n) time, O(1) space (26 letters; use a HashMap for Unicode).

## Follow-up: group anagrams
Use the sorted string (or the 26-count signature) as a HashMap key to group words.
MD,

'Remove Duplicates from Sorted Array' => <<<'MD'
## Problem
Remove duplicates in place from a sorted array so each value appears once; return the new length k (the first k elements hold the unique values).

```text
[0, 0, 1, 1, 1, 2, 2, 3, 3, 4] → k = 5, array starts [0, 1, 2, 3, 4]
```

## Approach 1: use a set
Copy unique values via a LinkedHashSet. O(n) time but O(n) extra space – not in place.

## Approach 2: slow and fast pointers (optimal)
slow marks the end of the unique prefix; fast scans. When a new value appears, write it after slow.

```java
static int removeDuplicates(int[] a) {
    if (a.length == 0) return 0;
    int slow = 0;
    for (int fast = 1; fast < a.length; fast++) {
        if (a[fast] != a[slow]) a[++slow] = a[fast];
    }
    return slow + 1;
}
```

## Dry run on [1, 1, 2, 3, 3]
- fast 1 (1) same → skip
- fast 2 (2) new → a[1] = 2, slow 1
- fast 3 (3) new → a[2] = 3, slow 2
- fast 4 (3) same → k = 3, array [1, 2, 3, …]

## Complexity
- Set: O(n) time, O(n) space. Two pointers: O(n) time, O(1) space.
MD,

'Container With Most Water' => <<<'MD'
## Problem
Given wall heights, choose two walls that, with the x-axis, hold the most water. Area = min(h[i], h[j]) × (j − i).

```text
[1, 8, 6, 2, 5, 4, 8, 3, 7] → 49 (walls 8 and 7, width 7)
```

## Approach 1: every pair
O(n²).

## Approach 2: two pointers from both ends (optimal)
Start with the widest container. The area is limited by the shorter wall, so moving the taller wall inward can never help; move the shorter one.

```java
static int maxArea(int[] h) {
    int l = 0, r = h.length - 1, best = 0;
    while (l < r) {
        best = Math.max(best, Math.min(h[l], h[r]) * (r - l));
        if (h[l] < h[r]) l++; else r--;
    }
    return best;
}
```

## Dry run highlights
- l=0 (1), r=8 (7): area 8 → move l
- l=1 (8), r=8 (7): area 49 → move r
- … no later area beats 49.

## Complexity
- Brute force O(n²). Two pointers O(n) time, O(1) space.
MD,

'Three Sum' => <<<'MD'
## Problem
Find all unique triplets that sum to zero.

```text
[-1, 0, 1, 2, -1, -4] → [[-1, -1, 2], [-1, 0, 1]]
```

## Approach 1: three nested loops
O(n³), plus a set to remove duplicate triplets.

## Approach 2: sort + fix one + two pointers (optimal)
Sort. For each index i (skipping duplicate values), find pairs in i+1..n−1 summing to −a[i] with two pointers, skipping duplicates as you move.

```java
static List<List<Integer>> threeSum(int[] a) {
    Arrays.sort(a);
    List<List<Integer>> res = new ArrayList<>();
    for (int i = 0; i < a.length - 2; i++) {
        if (i > 0 && a[i] == a[i - 1]) continue;          // skip duplicate first numbers
        int l = i + 1, r = a.length - 1;
        while (l < r) {
            int sum = a[i] + a[l] + a[r];
            if (sum == 0) {
                res.add(List.of(a[i], a[l], a[r]));
                while (l < r && a[l] == a[l + 1]) l++;
                while (l < r && a[r] == a[r - 1]) r--;
                l++; r--;
            } else if (sum < 0) l++;
            else r--;
        }
    }
    return res;
}
```

## Complexity
- Brute force O(n³). Sort + two pointers O(n²) time, O(1) extra space (besides output).

Note: if the smallest number a[i] is already > 0, you can stop early.
MD,

'What are Sliding Window and Prefix Sum?' => <<<'MD'
## Sliding window
A window is a contiguous range [left, right] of an array or string. Instead of recomputing every subarray from scratch, slide the window: add the element entering on the right and remove the one leaving on the left. This turns many O(n·k) or O(n²) problems into O(n).

- Fixed-size window: size k stays constant (maximum sum of k consecutive days of sales).
- Variable-size window: expand right until a condition breaks, then shrink left until it holds again (longest substring without repeats, smallest subarray with sum ≥ target).

```java
// Fixed window template
int windowSum = 0;
for (int right = 0; right < a.length; right++) {
    windowSum += a[right];                     // element enters
    if (right >= k) windowSum -= a[right - k]; // element leaves
    if (right >= k - 1) { /* use windowSum */ }
}
```

## Prefix sum
prefix[i] = a[0] + … + a[i−1]. Then the sum of any range [l, r] is prefix[r+1] − prefix[l] in O(1) after O(n) preprocessing.

```text
a      = [3, 1, 4, 1, 5]
prefix = [0, 3, 4, 8, 9, 14]
sum(1..3) = prefix[4] - prefix[1] = 9 - 3 = 6   (1 + 4 + 1)
```

```java
int[] prefix = new int[a.length + 1];
for (int i = 0; i < a.length; i++) prefix[i + 1] = prefix[i] + a[i];
int rangeSum = prefix[r + 1] - prefix[l];
```

## Prefix sum + HashMap
Storing counts of prefix sums in a HashMap answers "how many subarrays sum to k" in O(n), even with negative numbers (where a sliding window fails).

## When to use which
- All numbers non-negative and you need contiguous ranges → sliding window.
- Many range-sum queries or negative numbers → prefix sums (with a HashMap).

## Complexity
- Sliding window: O(n) time, O(1) or O(k) space. Prefix sums: O(n) build, O(1) per query, O(n) space.
MD,

'Maximum Sum Subarray of Size K' => <<<'MD'
## Problem
Find the maximum sum of any contiguous subarray of exactly k elements.

```text
[2, 1, 5, 1, 3, 2], k = 3 → 9 (5 + 1 + 3)
```

## Approach 1: sum every window
For each start, add k elements. O(n·k).

## Approach 2: fixed sliding window (optimal)
```java
static int maxSumK(int[] a, int k) {
    int windowSum = 0;
    for (int i = 0; i < k; i++) windowSum += a[i];
    int best = windowSum;
    for (int i = k; i < a.length; i++) {
        windowSum += a[i] - a[i - k];     // add new, remove old
        best = Math.max(best, windowSum);
    }
    return best;
}
```

## Dry run
- First window [2,1,5] = 8
- Slide: +1 −2 → 7; +3 −1 → 9; +2 −5 → 6 → best 9

## Complexity
- Brute force O(n·k). Sliding window O(n) time, O(1) space.
MD,

'Longest Substring Without Repeating Characters' => <<<'MD'
## Problem
Find the length of the longest substring with all distinct characters.

```text
"abcabcbb" → 3 ("abc")
"bbbbb"    → 1
"pwwkew"   → 3 ("wke")
```

## Approach 1: check every substring
O(n³) naive or O(n²) with a set per start.

## Approach 2: variable sliding window with last-seen positions (optimal)
Keep the window [left, right] free of repeats. When s[right] was seen inside the window, jump left past its previous position.

```java
static int lengthOfLongestSubstring(String s) {
    Map<Character, Integer> last = new HashMap<>();
    int left = 0, best = 0;
    for (int right = 0; right < s.length(); right++) {
        char c = s.charAt(right);
        if (last.containsKey(c) && last.get(c) >= left) left = last.get(c) + 1;
        last.put(c, right);
        best = Math.max(best, right - left + 1);
    }
    return best;
}
```

## Dry run on "abcabcbb"
- a b c → window "abc", best 3
- a repeats (at 0) → left 1; b repeats → left 2; c repeats → left 3 …
- best stays 3.

## Complexity
- O(n) time, O(min(n, alphabet)) space.
MD,

'Minimum Window Substring' => <<<'MD'
## Problem
Given strings s and t, find the smallest substring of s containing every character of t (with multiplicity).

```text
s = "ADOBECODEBANC", t = "ABC" → "BANC"
```

## Approach: variable window with counts (optimal)
1. Count the characters needed from t.
2. Expand right; when a needed character's count is satisfied, increase formed.
3. While all characters are satisfied, record the window and shrink from the left.

```java
static String minWindow(String s, String t) {
    int[] need = new int[128];
    for (char c : t.toCharArray()) need[c]++;
    int required = t.length(), left = 0, bestStart = 0, bestLen = Integer.MAX_VALUE;
    for (int right = 0; right < s.length(); right++) {
        if (need[s.charAt(right)]-- > 0) required--;       // a needed char entered
        while (required == 0) {
            if (right - left + 1 < bestLen) { bestLen = right - left + 1; bestStart = left; }
            if (++need[s.charAt(left++)] > 0) required++;   // a needed char left
        }
    }
    return bestLen == Integer.MAX_VALUE ? "" : s.substring(bestStart, bestStart + bestLen);
}
```

## Complexity
- O(|s| + |t|) time: each index enters and leaves the window once. O(1) space (128 counters).

Note: brute force over all substrings is O(n²) windows × O(n) checks = O(n³).
MD,

'Subarray Sum Equals K' => <<<'MD'
## Problem
Count contiguous subarrays whose sum equals k. Numbers may be negative.

```text
[1, 1, 1], k = 2 → 2
[1, 2, 3], k = 3 → 2  ([1,2] and [3])
```

## Approach 1: every subarray
Two loops with a running sum: O(n²).

## Approach 2: prefix sum + HashMap (optimal)
A subarray (j, i] sums to k when prefix[i] − prefix[j] = k, i.e. prefix[j] = prefix[i] − k. Count how often each prefix sum has occurred.

```java
static int subarraySum(int[] nums, int k) {
    Map<Integer, Integer> count = new HashMap<>();
    count.put(0, 1);                       // empty prefix
    int sum = 0, result = 0;
    for (int x : nums) {
        sum += x;
        result += count.getOrDefault(sum - k, 0);
        count.merge(sum, 1, Integer::sum);
    }
    return result;
}
```

## Dry run on [1, 2, 3], k = 3
- sum 1: need −2 → 0; store 1
- sum 3: need 0 → 1 (subarray [1,2]); store 3
- sum 6: need 3 → 1 (subarray [3]) → total 2

## Complexity
- O(n²) brute force. Prefix + HashMap O(n) time, O(n) space.

Note: a sliding window does not work here because negative numbers break the "shrink when too large" rule.
MD,

'Range Sum Query' => <<<'MD'
## Problem
Answer many queries sum(l, r) on a fixed array efficiently.

## Approach 1: loop per query
O(n) per query → O(n·q) total.

## Approach 2: prefix sums (optimal for a fixed array)
```java
class NumArray {
    private final int[] prefix;
    NumArray(int[] nums) {
        prefix = new int[nums.length + 1];
        for (int i = 0; i < nums.length; i++) prefix[i + 1] = prefix[i] + nums[i];
    }
    int sumRange(int l, int r) { return prefix[r + 1] - prefix[l]; }
}
```

## Example
nums = [-2, 0, 3, -5, 2, -1]; prefix = [0, -2, -2, 1, -4, -2, -3]
- sumRange(0, 2) = 1 − 0 = 1
- sumRange(2, 5) = −3 − (−2) = −1

## With updates
If values change between queries, use a Fenwick tree (Binary Indexed Tree) or a segment tree: O(log n) for both update and query.

## 2D version
prefix[i][j] over a matrix gives any sub-rectangle sum in O(1) using inclusion–exclusion.

## Complexity
- Prefix sums: O(n) build, O(1) per query, O(n) space.
MD,

'Longest Subarray With Given Sum' => <<<'MD'
## Problem
Find the length of the longest subarray whose sum equals k.

```text
[10, 5, 2, 7, 1, 9], k = 15 → 4 ([5, 2, 7, 1])
```

## Approach 1: all subarrays
O(n²).

## Approach 2: sliding window (only when all numbers are non-negative)
Expand right; while the sum exceeds k, shrink left; record the length when sum == k. O(n), O(1) space.

```java
static int longestNonNegative(int[] a, int k) {
    int left = 0, sum = 0, best = 0;
    for (int right = 0; right < a.length; right++) {
        sum += a[right];
        while (sum > k && left <= right) sum -= a[left++];
        if (sum == k) best = Math.max(best, right - left + 1);
    }
    return best;
}
```

## Approach 3: prefix sum + first-occurrence HashMap (works with negatives)
Store the first index where each prefix sum appears; if sum − k was seen at index j, the subarray (j, i] sums to k.

```java
static int longestSubarray(int[] a, int k) {
    Map<Integer, Integer> firstIndex = new HashMap<>();
    firstIndex.put(0, -1);
    int sum = 0, best = 0;
    for (int i = 0; i < a.length; i++) {
        sum += a[i];
        if (firstIndex.containsKey(sum - k)) best = Math.max(best, i - firstIndex.get(sum - k));
        firstIndex.putIfAbsent(sum, i);         // keep the earliest index for the longest span
    }
    return best;
}
```

## Complexity
- Brute force O(n²). Sliding window O(n)/O(1) for non-negative arrays. Prefix + HashMap O(n)/O(n) in general.
MD,

];
