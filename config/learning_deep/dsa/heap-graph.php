<?php

// DSA › Heap and Graph: concept lessons and problems, in depth.
return [

'What is a Heap?' => <<<'MD'
## What it is
A heap is a complete binary tree that satisfies the heap property:
- Min-heap: every parent is smaller than or equal to its children, so the smallest element is at the root.
- Max-heap: every parent is greater than or equal to its children, so the largest element is at the root.

"Complete" means every level is full except possibly the last, which is filled from the left. Because of this shape, a heap is stored compactly in an array with no pointers.

```text
Min-heap                    Array: [1, 3, 5, 7, 9, 8]
          1                 index:  0  1  2  3  4  5
        /   \
       3     5              For index i:
      / \   /                 parent = (i - 1) / 2
     7   9 8                  left   = 2i + 1
                              right  = 2i + 2
```

## Core operations
- peek (min or max): O(1) – it is at index 0.
- insert: add at the end, then "sift up" (swap with the parent while the property is violated): O(log n).
- extract (remove root): move the last element to the root, then "sift down" (swap with the smaller child): O(log n).
- build a heap from n elements: O(n) using bottom-up heapify.

## Min-heap implementation
```java
class MinHeap {
    private final List<Integer> a = new ArrayList<>();

    void insert(int x) {
        a.add(x);
        int i = a.size() - 1;
        while (i > 0 && a.get((i - 1) / 2) > a.get(i)) {      // sift up
            Collections.swap(a, i, (i - 1) / 2);
            i = (i - 1) / 2;
        }
    }

    int extractMin() {
        int min = a.get(0);
        int last = a.remove(a.size() - 1);
        if (!a.isEmpty()) {
            a.set(0, last);
            int i = 0;
            while (true) {                                      // sift down
                int l = 2 * i + 1, r = 2 * i + 2, smallest = i;
                if (l < a.size() && a.get(l) < a.get(smallest)) smallest = l;
                if (r < a.size() && a.get(r) < a.get(smallest)) smallest = r;
                if (smallest == i) break;
                Collections.swap(a, i, smallest);
                i = smallest;
            }
        }
        return min;
    }
}
```

## Using Java's PriorityQueue
```java
PriorityQueue<Integer> minHeap = new PriorityQueue<>();
PriorityQueue<Integer> maxHeap = new PriorityQueue<>(Comparator.reverseOrder());
for (int x : new int[]{5, 1, 8, 3}) { minHeap.offer(x); maxHeap.offer(x); }
System.out.println(minHeap.poll() + " " + maxHeap.poll());   // 1 8
```

## Complexity summary
- Get min/max: O(1). Insert: O(log n). Extract: O(log n). Build: O(n).
- Search for an arbitrary value: O(n) – a heap is not sorted, only partially ordered.

## Applications
- Priority queues: task scheduling, hospital triage, event simulation.
- Dijkstra's and Prim's algorithms.
- Top-K and K-th largest problems, running median (two heaps).
- Heap sort (O(n log n), in place).
- Merging K sorted lists or files.

Note: heaps are not BSTs. A BST orders left < parent < right; a heap only orders parent vs children.
MD,

'What is a Graph?' => <<<'MD'
## What it is
A graph is a set of vertices (nodes) connected by edges. Graphs model relationships: cities and roads, users and friendships, web pages and links, tasks and dependencies.

```text
     A ----- B
     |     / |
     |   /   |
     C ----- D ----- E
```

## Key terms
- Directed vs undirected: in a directed graph an edge A → B goes one way (Twitter follows); undirected edges go both ways (Facebook friends).
- Weighted vs unweighted: edges may carry a cost (distance, time, price).
- Degree: number of edges at a vertex (in-degree / out-degree for directed graphs).
- Path, cycle, connected component.
- DAG: directed acyclic graph (task dependencies, course prerequisites).

## Representations
1. Adjacency matrix: an n × n grid where m[u][v] = 1 (or the weight) if an edge exists.
   - Edge check O(1); space O(V²) – good for dense graphs.
2. Adjacency list: for each vertex, a list of its neighbours.
   - Space O(V + E); iterating neighbours is fast – best for sparse real-world graphs.

```java
int V = 5;
List<List<Integer>> adj = new ArrayList<>();
for (int i = 0; i < V; i++) adj.add(new ArrayList<>());

void addEdge(int u, int v) {      // undirected
    adj.get(u).add(v);
    adj.get(v).add(u);
}
```

## The two fundamental traversals
- Breadth-first search (BFS): explores level by level using a queue; finds shortest paths in unweighted graphs.
- Depth-first search (DFS): goes as deep as possible using recursion or a stack; used for cycle detection, topological sort and connected components.

## Classic algorithms and their complexity
- BFS / DFS: O(V + E).
- Dijkstra (non-negative weights, with a heap): O((V + E) log V).
- Bellman-Ford (negative weights): O(V · E).
- Topological sort (Kahn's or DFS): O(V + E).
- Minimum spanning tree (Prim / Kruskal): O(E log V).
- Union-Find for connectivity: almost O(1) per operation.

## Real-world applications
- Google Maps routing (shortest paths), ride-sharing ETAs.
- Social networks: friend suggestions are vertices two hops away.
- Build tools and package managers: dependency order is a topological sort.
- Network routing, recommendation engines, fraud rings in payment graphs.

Note: always keep a visited set; without it, any cycle makes traversal run forever.
MD,

'Kth Largest Element' => <<<'MD'
## Problem
Find the k-th largest element in an unsorted array.

```text
nums = [3, 2, 1, 5, 6, 4], k = 2  → 5
```

## Approach 1: sort
Sort and return nums[n − k]. Time O(n log n), Space O(1) to O(n) depending on the sort.

## Approach 2: min-heap of size k (common optimal answer)
Keep the k largest elements seen so far in a min-heap. Its root is the k-th largest.

```java
static int kthLargest(int[] nums, int k) {
    PriorityQueue<Integer> heap = new PriorityQueue<>();
    for (int x : nums) {
        heap.offer(x);
        if (heap.size() > k) heap.poll();   // drop the smallest of the k+1
    }
    return heap.peek();
}
```

## Dry run (k = 2)
3 → [3]; 2 → [2,3]; 1 → poll 1 → [2,3]; 5 → poll 2 → [3,5]; 6 → poll 3 → [5,6]; 4 → poll 4 → [5,6]. Root = 5.

## Approach 3: Quickselect
Partition like quicksort but recurse only into the side that contains index n − k. Average O(n), worst O(n²) (randomise the pivot).

```java
static int quickselect(int[] a, int k) {
    int target = a.length - k, lo = 0, hi = a.length - 1;
    Random rnd = new Random();
    while (true) {
        int p = partition(a, lo, hi, lo + rnd.nextInt(hi - lo + 1));
        if (p == target) return a[p];
        if (p < target) lo = p + 1; else hi = p - 1;
    }
}
static int partition(int[] a, int lo, int hi, int pivotIndex) {
    int pivot = a[pivotIndex]; swap(a, pivotIndex, hi);
    int store = lo;
    for (int i = lo; i < hi; i++) if (a[i] < pivot) swap(a, i, store++);
    swap(a, store, hi);
    return store;
}
static void swap(int[] a, int i, int j) { int t = a[i]; a[i] = a[j]; a[j] = t; }
```

## Complexity
- Sort: O(n log n).
- Heap: O(n log k) time, O(k) space – great for streams.
- Quickselect: O(n) average, O(1) extra space.
MD,

'Top K Frequent Elements' => <<<'MD'
## Problem
Return the k most frequent elements of an array.

```text
nums = [1,1,1,2,2,3], k = 2 → [1, 2]
```

## Step 1 (all approaches): count frequencies
```java
Map<Integer, Integer> freq = new HashMap<>();
for (int x : nums) freq.merge(x, 1, Integer::sum);
```

## Approach 1: sort by frequency
Sort the distinct elements by count descending and take k. Time O(n log n).

## Approach 2: min-heap of size k
```java
static int[] topK(int[] nums, int k) {
    Map<Integer, Integer> freq = new HashMap<>();
    for (int x : nums) freq.merge(x, 1, Integer::sum);
    PriorityQueue<Integer> heap = new PriorityQueue<>(Comparator.comparingInt(freq::get));
    for (int key : freq.keySet()) {
        heap.offer(key);
        if (heap.size() > k) heap.poll();     // remove the least frequent
    }
    return heap.stream().mapToInt(Integer::intValue).toArray();
}
```
Time O(n log k), Space O(n).

## Approach 3: bucket sort by frequency (optimal)
A count can be at most n, so make buckets 0..n where bucket[c] holds the elements appearing c times; read buckets from high to low.

```java
static List<Integer> topKBuckets(int[] nums, int k) {
    Map<Integer, Integer> freq = new HashMap<>();
    for (int x : nums) freq.merge(x, 1, Integer::sum);
    List<Integer>[] buckets = new List[nums.length + 1];
    freq.forEach((value, count) -> {
        if (buckets[count] == null) buckets[count] = new ArrayList<>();
        buckets[count].add(value);
    });
    List<Integer> result = new ArrayList<>();
    for (int c = nums.length; c >= 0 && result.size() < k; c--) {
        if (buckets[c] != null) result.addAll(buckets[c]);
    }
    return result.subList(0, k);
}
```

## Complexity
- Sorting: O(n log n). Heap: O(n log k). Buckets: O(n) time, O(n) space.

## Real-world use
Trending hashtags, most-searched jobs, top error codes in logs.
MD,

'Merge K Sorted Lists' => <<<'MD'
## Problem
Merge k sorted linked lists into one sorted list.

```text
[1->4->5, 1->3->4, 2->6] → 1->1->2->3->4->4->5->6
```

## Approach 1: merge one by one
Merge list 1 with list 2, the result with list 3, and so on. With N total nodes, time O(k · N).

## Approach 2: min-heap of list heads (optimal and common)
Put the head of every list into a min-heap. Repeatedly take the smallest node, append it, and push its next.

```java
static Node mergeK(List<Node> lists) {
    PriorityQueue<Node> heap = new PriorityQueue<>(Comparator.comparingInt(n -> n.data));
    for (Node head : lists) if (head != null) heap.offer(head);
    Node dummy = new Node(0), tail = dummy;
    while (!heap.isEmpty()) {
        Node smallest = heap.poll();
        tail.next = smallest;
        tail = smallest;
        if (smallest.next != null) heap.offer(smallest.next);
    }
    return dummy.next;
}
```

## Approach 3: divide and conquer
Merge lists in pairs (1+2, 3+4, …), then pairs of results, like merge sort. Also O(N log k), O(1) extra space with the iterative pairwise merge.

## Complexity
- One by one: O(k · N).
- Heap: O(N log k) time, O(k) space.
- Divide and conquer: O(N log k) time.

## Real-world use
External sorting of huge files, merging sorted results from database shards, combining time-ordered event streams.
MD,

'Breadth First Search' => <<<'MD'
## What it is
BFS visits a graph level by level: first the start vertex, then all vertices one edge away, then two edges away, and so on. It uses a queue, and in an unweighted graph it finds the shortest path (fewest edges) from the start to every vertex.

## Algorithm
1. Mark the start visited and enqueue it.
2. While the queue is not empty: dequeue u, process it, and enqueue every unvisited neighbour (marking it visited when enqueued).

```java
static List<Integer> bfs(List<List<Integer>> adj, int start) {
    boolean[] visited = new boolean[adj.size()];
    List<Integer> order = new ArrayList<>();
    Queue<Integer> queue = new ArrayDeque<>();
    visited[start] = true;
    queue.offer(start);
    while (!queue.isEmpty()) {
        int u = queue.poll();
        order.add(u);
        for (int v : adj.get(u)) {
            if (!visited[v]) { visited[v] = true; queue.offer(v); }
        }
    }
    return order;
}
```

## Shortest distances in an unweighted graph
```java
static int[] distances(List<List<Integer>> adj, int src) {
    int[] dist = new int[adj.size()];
    Arrays.fill(dist, -1);
    dist[src] = 0;
    Queue<Integer> q = new ArrayDeque<>(List.of(src));
    while (!q.isEmpty()) {
        int u = q.poll();
        for (int v : adj.get(u)) if (dist[v] == -1) { dist[v] = dist[u] + 1; q.offer(v); }
    }
    return dist;
}
```

## Dry run
Edges: 0–1, 0–2, 1–3, 2–4. From 0: queue [0] → visit 0, add 1, 2 → visit 1, add 3 → visit 2, add 4 → visit 3 → visit 4. Order: 0 1 2 3 4; distances 0,1,1,2,2.

## Complexity
- Time O(V + E) with an adjacency list (each vertex and edge handled once).
- Space O(V) for the queue and visited array.

## Applications
Shortest path in unweighted graphs or grids (maze, word ladder), "people you may know" (friends of friends), web crawling by depth, checking bipartite graphs.

Note: mark a vertex visited when you enqueue it, not when you dequeue it; otherwise the same vertex can be enqueued many times.
MD,

'Depth First Search' => <<<'MD'
## What it is
DFS explores as far as possible along one path before backtracking. It uses recursion (the call stack) or an explicit stack.

## Recursive DFS
```java
static void dfs(List<List<Integer>> adj, int u, boolean[] visited, List<Integer> order) {
    visited[u] = true;
    order.add(u);
    for (int v : adj.get(u)) {
        if (!visited[v]) dfs(adj, v, visited, order);
    }
}
```

## Iterative DFS with a stack
```java
static List<Integer> dfsIterative(List<List<Integer>> adj, int start) {
    boolean[] visited = new boolean[adj.size()];
    List<Integer> order = new ArrayList<>();
    Deque<Integer> stack = new ArrayDeque<>(List.of(start));
    while (!stack.isEmpty()) {
        int u = stack.pop();
        if (visited[u]) continue;
        visited[u] = true;
        order.add(u);
        for (int i = adj.get(u).size() - 1; i >= 0; i--) {   // reverse to mimic recursion order
            int v = adj.get(u).get(i);
            if (!visited[v]) stack.push(v);
        }
    }
    return order;
}
```

## Counting connected components
```java
static int components(List<List<Integer>> adj) {
    boolean[] visited = new boolean[adj.size()];
    int count = 0;
    for (int u = 0; u < adj.size(); u++) {
        if (!visited[u]) { dfs(adj, u, visited, new ArrayList<>()); count++; }
    }
    return count;
}
```

## Dry run
Edges 0–1, 0–2, 1–3, 2–4 starting at 0: visit 0 → 1 → 3 (dead end, back) → 2 → 4. Order: 0 1 3 2 4.

## Complexity
- Time O(V + E); Space O(V) for visited plus the recursion depth.

## Applications
Cycle detection, topological sort, connected components, number of islands in a grid, path finding in mazes, solving puzzles with backtracking.

Note: very deep graphs can overflow the recursion stack; use the iterative version for large inputs.
MD,

'Detect Cycle in Graph' => <<<'MD'
## Undirected graph: DFS with parent
During DFS, reaching an already visited vertex that is not the parent of the current vertex means a cycle.

```java
static boolean hasCycleUndirected(List<List<Integer>> adj) {
    boolean[] visited = new boolean[adj.size()];
    for (int u = 0; u < adj.size(); u++) {
        if (!visited[u] && dfsCycle(adj, u, -1, visited)) return true;
    }
    return false;
}
static boolean dfsCycle(List<List<Integer>> adj, int u, int parent, boolean[] visited) {
    visited[u] = true;
    for (int v : adj.get(u)) {
        if (!visited[v]) { if (dfsCycle(adj, v, u, visited)) return true; }
        else if (v != parent) return true;
    }
    return false;
}
```

Alternative: Union-Find – for each edge (u, v), if u and v already have the same root, the edge closes a cycle.

## Directed graph: DFS with three colours
A cycle exists if DFS reaches a vertex that is still on the current recursion path (a "back edge").
- 0 = unvisited, 1 = on the current path, 2 = finished.

```java
static boolean hasCycleDirected(List<List<Integer>> adj) {
    int[] state = new int[adj.size()];
    for (int u = 0; u < adj.size(); u++) if (state[u] == 0 && dfsDirected(adj, u, state)) return true;
    return false;
}
static boolean dfsDirected(List<List<Integer>> adj, int u, int[] state) {
    state[u] = 1;
    for (int v : adj.get(u)) {
        if (state[v] == 1) return true;                         // back edge
        if (state[v] == 0 && dfsDirected(adj, v, state)) return true;
    }
    state[u] = 2;
    return false;
}
```

Alternative for directed graphs: Kahn's topological sort – if not all vertices can be processed, a cycle exists.

## Complexity
- DFS methods: O(V + E) time, O(V) space.
- Union-Find: O(E · α(V)) ≈ O(E).

## Real-world use
Detecting circular dependencies in build systems or microservice calls, deadlock detection in operating systems and databases (wait-for graphs).
MD,

'Dijkstra\'s Algorithm' => <<<'MD'
## Problem
Find the shortest distance from a source vertex to every other vertex in a weighted graph with non-negative edge weights.

## Idea
Greedy: always finalise the unvisited vertex with the smallest known distance, then relax its edges (if going through it gives a shorter path to a neighbour, update the neighbour). A min-heap gives the next closest vertex quickly.

```java
static int[] dijkstra(List<List<int[]>> adj, int src) {   // adj.get(u) holds {v, weight}
    int[] dist = new int[adj.size()];
    Arrays.fill(dist, Integer.MAX_VALUE);
    dist[src] = 0;
    PriorityQueue<int[]> pq = new PriorityQueue<>(Comparator.comparingInt(a -> a[1]));
    pq.offer(new int[]{src, 0});
    while (!pq.isEmpty()) {
        int[] top = pq.poll();
        int u = top[0], d = top[1];
        if (d > dist[u]) continue;                 // stale entry
        for (int[] edge : adj.get(u)) {
            int v = edge[0], w = edge[1];
            if (dist[u] + w < dist[v]) {
                dist[v] = dist[u] + w;
                pq.offer(new int[]{v, dist[v]});
            }
        }
    }
    return dist;
}
```

## Dry run
Edges: A–B 4, A–C 1, C–B 2, B–D 1, C–D 5. From A:
- A=0 → C=1, B=4.
- Pop C(1) → B via C = 3 (better than 4), D = 6.
- Pop B(3) → D via B = 4.
- Pop D(4). Final: A 0, C 1, B 3, D 4.

## Complexity
- With a binary heap: O((V + E) log V) time, O(V + E) space.
- With a simple array (dense graphs): O(V²).

## Limitations
Negative edge weights break the greedy choice; use Bellman-Ford (O(V·E)) instead. For all pairs, use Floyd-Warshall (O(V³)).

## Real-world use
GPS navigation, network routing protocols (OSPF), cheapest flight or delivery cost.
MD,

'Topological Sort' => <<<'MD'
## Problem
Order the vertices of a directed acyclic graph (DAG) so that for every edge u → v, u comes before v.

```text
Courses: Java → Spring Boot → Microservices, Java → Data Structures
Valid order: Java, Data Structures, Spring Boot, Microservices
```

## Approach 1: Kahn's algorithm (BFS on in-degrees)
1. Compute the in-degree of each vertex.
2. Enqueue vertices with in-degree 0 (no prerequisites).
3. Pop a vertex, add it to the order, decrease in-degrees of its neighbours; enqueue any that drop to 0.
4. If the order has fewer than V vertices, the graph has a cycle.

```java
static List<Integer> topoKahn(List<List<Integer>> adj) {
    int n = adj.size();
    int[] indegree = new int[n];
    for (List<Integer> edges : adj) for (int v : edges) indegree[v]++;
    Queue<Integer> q = new ArrayDeque<>();
    for (int u = 0; u < n; u++) if (indegree[u] == 0) q.offer(u);
    List<Integer> order = new ArrayList<>();
    while (!q.isEmpty()) {
        int u = q.poll();
        order.add(u);
        for (int v : adj.get(u)) if (--indegree[v] == 0) q.offer(v);
    }
    if (order.size() != n) throw new IllegalStateException("Graph has a cycle");
    return order;
}
```

## Approach 2: DFS finishing order
Run DFS; when a vertex finishes (all descendants done), push it onto a stack. Reversing the finishing order gives a topological order.

```java
static Deque<Integer> topoDfs(List<List<Integer>> adj) {
    boolean[] visited = new boolean[adj.size()];
    Deque<Integer> stack = new ArrayDeque<>();
    for (int u = 0; u < adj.size(); u++) if (!visited[u]) visit(adj, u, visited, stack);
    return stack;   // pop order is topological
}
static void visit(List<List<Integer>> adj, int u, boolean[] visited, Deque<Integer> stack) {
    visited[u] = true;
    for (int v : adj.get(u)) if (!visited[v]) visit(adj, v, visited, stack);
    stack.push(u);
}
```

## Complexity
- Both: O(V + E) time, O(V) space.

## Real-world use
Build systems (Maven, Gradle, make), package installation order (npm, Composer), course schedules, spreadsheet formula evaluation, CI pipeline stages.
MD,

];
