<?php

// DSA › Greedy and Dynamic Programming: in depth.
return [

'What are Greedy Algorithms and Dynamic Programming?' => <<<'MD'
## Greedy algorithms
A greedy algorithm builds a solution step by step, always taking the choice that looks best right now, and never revisits it. It is fast and simple, but it only gives the optimal answer when the problem has the greedy-choice property (a locally best choice is part of some globally best solution) and optimal substructure.

Example where greedy works: making change with Indian coins (1, 2, 5, 10, 20…): always take the largest coin that fits.
Example where greedy fails: coins {1, 3, 4} for amount 6 – greedy picks 4+1+1 (3 coins) but 3+3 (2 coins) is optimal. That needs DP.

## Dynamic programming (DP)
DP solves problems that have:
1. Optimal substructure – the best answer is built from best answers to subproblems.
2. Overlapping subproblems – the same subproblems repeat, so we store results instead of recomputing them.

Two styles:
- Top-down (memoisation): write the recursion and cache results in a map/array.
- Bottom-up (tabulation): fill a table from the smallest subproblems upward, often reducible to O(1) extra space.

```java
// Fibonacci: plain recursion O(2^n) → DP O(n)
static long fibMemo(int n, long[] memo) {
    if (n <= 1) return n;
    if (memo[n] != 0) return memo[n];
    return memo[n] = fibMemo(n - 1, memo) + fibMemo(n - 2, memo);
}

static long fibTable(int n) {
    if (n <= 1) return n;
    long prev = 0, cur = 1;
    for (int i = 2; i <= n; i++) { long next = prev + cur; prev = cur; cur = next; }
    return cur;
}
```

## A recipe for DP problems
1. Define the state: what does dp[i] (or dp[i][j]) mean in words?
2. Write the transition: how dp[i] depends on smaller states.
3. Set base cases.
4. Choose the order of computation and where the answer is.
5. Optimise space if only the previous row/values are needed.

## Greedy vs DP
- Greedy: one choice per step, no backtracking, usually O(n log n) after sorting.
- DP: considers all relevant choices via stored subproblems, usually O(n²) or O(n·W).
- Prove greedy correct (exchange argument) or find a counterexample; if it fails, try DP.

## Real-world use
- Greedy: Huffman compression, scheduling meetings, Dijkstra, minimum spanning trees.
- DP: diff tools (longest common subsequence), spell check (edit distance), route planning, inventory and budget allocation (knapsack).
MD,

'Activity Selection' => <<<'MD'
## Problem
Given activities with start and finish times, select the maximum number of non-overlapping activities (one person, one room).

```text
start  = [1, 3, 0, 5, 8, 5]
finish = [2, 4, 6, 7, 9, 9]
→ 4 activities: (1,2) (3,4) (5,7) (8,9)
```

## Greedy choice
Always pick the activity that finishes earliest; it leaves the most time for the rest.

```java
static int maxActivities(int[] start, int[] finish) {
    int n = start.length;
    Integer[] idx = new Integer[n];
    for (int i = 0; i < n; i++) idx[i] = i;
    Arrays.sort(idx, Comparator.comparingInt(i -> finish[i]));   // sort by finish time
    int count = 0, lastEnd = Integer.MIN_VALUE;
    for (int i : idx) {
        if (start[i] >= lastEnd) {          // compatible with the last chosen activity
            count++;
            lastEnd = finish[i];
        }
    }
    return count;
}
```

## Why greedy is correct (exchange argument)
Any optimal schedule can swap its first activity for the earliest-finishing one without causing overlaps, so choosing the earliest finish never hurts.

## Complexity
- Sorting O(n log n) + one pass O(n); O(n) space for indices.

## Variations
Minimum meeting rooms (sort starts and ends, or use a min-heap of end times), merging intervals, non-overlapping intervals to remove.
MD,

'Job Sequencing' => <<<'MD'
## Problem
Each job takes one unit of time, has a deadline and a profit. Schedule jobs (at most one per time slot, finishing by its deadline) to maximise total profit.

```text
jobs (id, deadline, profit): (a,4,20) (b,1,10) (c,1,40) (d,1,30)
→ schedule c (slot 1) and a (slot ≤ 4) → profit 60
```

## Greedy approach
1. Sort jobs by profit, highest first.
2. For each job, place it in the latest free slot on or before its deadline.

```java
static int jobSequencing(int[] deadline, int[] profit) {
    int n = profit.length, maxDeadline = 0;
    Integer[] idx = new Integer[n];
    for (int i = 0; i < n; i++) { idx[i] = i; maxDeadline = Math.max(maxDeadline, deadline[i]); }
    Arrays.sort(idx, (x, y) -> profit[y] - profit[x]);
    boolean[] used = new boolean[maxDeadline + 1];
    int total = 0;
    for (int j : idx) {
        for (int slot = deadline[j]; slot >= 1; slot--) {
            if (!used[slot]) { used[slot] = true; total += profit[j]; break; }
        }
    }
    return total;
}
```

## Complexity
- O(n log n) sorting + O(n · D) slot search, where D is the maximum deadline. A disjoint-set (union-find) of free slots reduces slot search to almost O(1).
MD,

'Fractional Knapsack' => <<<'MD'
## Problem
Items have weights and values; you may take fractions of items. Maximise the value that fits in a bag of capacity W.

```text
values = [60, 100, 120], weights = [10, 20, 30], W = 50
→ take items 1 and 2 fully (160) + 20/30 of item 3 (80) = 240
```

## Greedy choice
Sort by value per unit weight (value/weight) and take as much as possible of the best ratio first.

```java
static double fractionalKnapsack(int[] value, int[] weight, int capacity) {
    Integer[] idx = new Integer[value.length];
    for (int i = 0; i < idx.length; i++) idx[i] = i;
    Arrays.sort(idx, (a, b) -> Double.compare((double) value[b] / weight[b], (double) value[a] / weight[a]));
    double total = 0;
    for (int i : idx) {
        if (capacity == 0) break;
        int take = Math.min(weight[i], capacity);
        total += take * (double) value[i] / weight[i];
        capacity -= take;
    }
    return total;
}
```

## Complexity
- O(n log n) for sorting, O(1) extra space besides indices.

Note: greedy is optimal only for the fractional version. For 0/1 knapsack (take an item fully or not at all) greedy fails and DP is needed.
MD,

'Climbing Stairs' => <<<'MD'
## Problem
You can climb 1 or 2 steps at a time. In how many distinct ways can you reach step n?

```text
n = 3 → 3 ways: 1+1+1, 1+2, 2+1
```

## Recurrence
To reach step n you came from n−1 (one step) or n−2 (two steps): ways(n) = ways(n−1) + ways(n−2), with ways(1) = 1, ways(2) = 2. It is the Fibonacci sequence.

## Approach 1: plain recursion
O(2^n) because subproblems repeat.

## Approach 2: memoisation
```java
static int climb(int n, int[] memo) {
    if (n <= 2) return n;
    if (memo[n] != 0) return memo[n];
    return memo[n] = climb(n - 1, memo) + climb(n - 2, memo);
}
```

## Approach 3: bottom-up with O(1) space (optimal)
```java
static int climbStairs(int n) {
    if (n <= 2) return n;
    int oneBack = 2, twoBack = 1;
    for (int i = 3; i <= n; i++) {
        int current = oneBack + twoBack;
        twoBack = oneBack;
        oneBack = current;
    }
    return oneBack;
}
```

## Complexity
- Recursion O(2^n). Memo O(n) time and space. Iterative O(n) time, O(1) space.

## Variation
Steps of sizes {1, 2, 3}: ways(n) = ways(n−1) + ways(n−2) + ways(n−3).
MD,

'House Robber' => <<<'MD'
## Problem
Houses in a row contain money; you cannot rob two adjacent houses. Maximise the amount robbed.

```text
[2, 7, 9, 3, 1] → 12 (2 + 9 + 1)
```

## State and transition
dp[i] = best amount from the first i houses. For house i you either skip it (dp[i−1]) or rob it (dp[i−2] + money[i]).
dp[i] = max(dp[i−1], dp[i−2] + money[i]).

```java
static int rob(int[] money) {
    int prev2 = 0, prev1 = 0;            // dp[i-2], dp[i-1]
    for (int m : money) {
        int current = Math.max(prev1, prev2 + m);
        prev2 = prev1;
        prev1 = current;
    }
    return prev1;
}
```

## Dry run on [2, 7, 9, 3, 1]
- 2 → 2
- 7 → max(2, 0+7) = 7
- 9 → max(7, 2+9) = 11
- 3 → max(11, 7+3) = 11
- 1 → max(11, 11+1) = 12

## Complexity
- O(n) time, O(1) space. Brute-force recursion trying every subset is O(2^n).

## Variation: houses in a circle
Run the same algorithm twice: excluding the first house and excluding the last house; take the maximum.
MD,

'Coin Change' => <<<'MD'
## Problem
Given coin denominations and an amount, return the minimum number of coins to make the amount, or −1 if impossible (unlimited coins of each type).

```text
coins = [1, 2, 5], amount = 11 → 3 (5 + 5 + 1)
coins = [2], amount = 3 → -1
```

## Why greedy is not enough
For coins {1, 3, 4} and amount 6, greedy takes 4+1+1 (3 coins) but 3+3 uses 2.

## Bottom-up DP (optimal)
dp[a] = minimum coins for amount a. dp[0] = 0; dp[a] = min over coins c ≤ a of dp[a − c] + 1.

```java
static int coinChange(int[] coins, int amount) {
    int[] dp = new int[amount + 1];
    Arrays.fill(dp, amount + 1);        // "infinity"
    dp[0] = 0;
    for (int a = 1; a <= amount; a++) {
        for (int c : coins) {
            if (c <= a) dp[a] = Math.min(dp[a], dp[a - c] + 1);
        }
    }
    return dp[amount] > amount ? -1 : dp[amount];
}
```

## Dry run (coins 1, 2, 5)
dp[1]=1, dp[2]=1, dp[3]=2, dp[4]=2, dp[5]=1, dp[6]=2, … dp[10]=2, dp[11]=3.

## Related: number of ways to make the amount
```java
static long ways(int[] coins, int amount) {
    long[] dp = new long[amount + 1];
    dp[0] = 1;
    for (int c : coins)                  // coins outer loop → combinations, not permutations
        for (int a = c; a <= amount; a++) dp[a] += dp[a - c];
    return dp[amount];
}
```

## Complexity
- O(amount × number of coins) time, O(amount) space.
MD,

'Longest Increasing Subsequence' => <<<'MD'
## Problem
Find the length of the longest strictly increasing subsequence (elements in order, not necessarily contiguous).

```text
[10, 9, 2, 5, 3, 7, 101, 18] → 4 (2, 3, 7, 18)
```

## Approach 1: O(n²) DP
dp[i] = length of the LIS ending at index i = 1 + max(dp[j]) for j < i with a[j] < a[i].

```java
static int lisQuadratic(int[] a) {
    int[] dp = new int[a.length];
    int best = 0;
    for (int i = 0; i < a.length; i++) {
        dp[i] = 1;
        for (int j = 0; j < i; j++) if (a[j] < a[i]) dp[i] = Math.max(dp[i], dp[j] + 1);
        best = Math.max(best, dp[i]);
    }
    return best;
}
```

## Approach 2: patience sorting with binary search, O(n log n) (optimal)
tails[k] = smallest possible tail of an increasing subsequence of length k+1. For each number, replace the first tail ≥ it (or append).

```java
static int lis(int[] a) {
    int[] tails = new int[a.length];
    int size = 0;
    for (int x : a) {
        int lo = 0, hi = size;
        while (lo < hi) {
            int mid = (lo + hi) / 2;
            if (tails[mid] < x) lo = mid + 1; else hi = mid;
        }
        tails[lo] = x;
        if (lo == size) size++;
    }
    return size;
}
```

## Dry run of tails
10 → [10]; 9 → [9]; 2 → [2]; 5 → [2,5]; 3 → [2,3]; 7 → [2,3,7]; 101 → [2,3,7,101]; 18 → [2,3,7,18]. Length 4.

## Complexity
- DP O(n²) time, O(n) space. Binary search O(n log n) time, O(n) space.

Note: tails is not the actual subsequence; reconstructing it needs parent pointers.
MD,

'Longest Common Subsequence' => <<<'MD'
## Problem
Return the length of the longest subsequence common to two strings.

```text
"abcde", "ace" → 3 ("ace")
```

## State and transition
dp[i][j] = LCS of the first i characters of a and first j characters of b.
- If a[i−1] == b[j−1]: dp[i][j] = dp[i−1][j−1] + 1.
- Else: dp[i][j] = max(dp[i−1][j], dp[i][j−1]).

```java
static int lcs(String a, String b) {
    int[][] dp = new int[a.length() + 1][b.length() + 1];
    for (int i = 1; i <= a.length(); i++) {
        for (int j = 1; j <= b.length(); j++) {
            if (a.charAt(i - 1) == b.charAt(j - 1)) dp[i][j] = dp[i - 1][j - 1] + 1;
            else dp[i][j] = Math.max(dp[i - 1][j], dp[i][j - 1]);
        }
    }
    return dp[a.length()][b.length()];
}
```

## Table for "abcde" vs "ace"
```text
      ""  a  c  e
 ""    0  0  0  0
 a     0  1  1  1
 b     0  1  1  1
 c     0  1  2  2
 d     0  1  2  2
 e     0  1  2  3
```

## Complexity
- Brute force over all subsequences: O(2^n · m).
- DP: O(n·m) time, O(n·m) space (O(min(n, m)) with two rows).

## Real-world use
diff/git diff, DNA sequence comparison, plagiarism detection. Edit distance (Levenshtein) is a close relative.
MD,

'0/1 Knapsack' => <<<'MD'
## Problem
Each item has a weight and value and can be taken at most once. Maximise value without exceeding capacity W.

```text
values = [60, 100, 120], weights = [10, 20, 30], W = 50 → 220 (items 2 and 3)
```

## Why not greedy
The best value/weight ratio is item 1 (6/kg), but taking it leaves 40 capacity and only 160 more → 220 vs greedy's 160+60… greedy by ratio gives 60 + 100 = 160, then item 3 does not fit; the optimum skips item 1.

## State and transition
dp[i][w] = best value using the first i items with capacity w.
- Skip item i: dp[i−1][w].
- Take item i (if weight[i] ≤ w): dp[i−1][w − weight[i]] + value[i].

## Space-optimised 1D DP
Iterate capacity downwards so each item is used at most once.

```java
static int knapsack(int[] value, int[] weight, int capacity) {
    int[] dp = new int[capacity + 1];
    for (int i = 0; i < value.length; i++) {
        for (int w = capacity; w >= weight[i]; w--) {
            dp[w] = Math.max(dp[w], dp[w - weight[i]] + value[i]);
        }
    }
    return dp[capacity];
}
```

## Complexity
- Brute force (all subsets): O(2^n).
- DP: O(n · W) time, O(W) space. This is pseudo-polynomial: it depends on the numeric value of W.

## Variants
Unbounded knapsack (iterate w upwards), subset sum, partition into equal sums, target sum with + and − signs.

## Real-world use
Choosing projects within a budget, loading cargo, selecting ads for a fixed slot, portfolio selection.
MD,

];
