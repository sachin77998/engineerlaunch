<?php

// DSA › Binary Tree and BST: concept lesson and problems, in depth.
return [

'What is a Binary Tree and a BST?' => <<<'MD'
## Binary tree
A tree is a hierarchical data structure of nodes connected by edges, with one root and no cycles. In a binary tree, every node has at most two children: left and right.

```text
            1          <- root (level 0)
          /   \
         2     3       <- level 1
        / \     \
       4   5     6     <- leaves (no children)
```

## Terminology
- Root: the top node. Leaf: a node with no children.
- Parent/child/sibling relationships.
- Depth of a node: edges from the root to it. Height of the tree: edges on the longest root-to-leaf path.
- Subtree: a node and all its descendants.

## Node structure
```java
class TreeNode {
    int val;
    TreeNode left, right;
    TreeNode(int val) { this.val = val; }
}

// Building the tree above
TreeNode root = new TreeNode(1);
root.left = new TreeNode(2);
root.right = new TreeNode(3);
root.left.left = new TreeNode(4);
root.left.right = new TreeNode(5);
root.right.right = new TreeNode(6);
```

## Types of binary trees
- Full: every node has 0 or 2 children.
- Complete: all levels full except possibly the last, filled from the left (heaps).
- Perfect: all internal nodes have 2 children and all leaves are at the same level (2^(h+1) − 1 nodes).
- Balanced: heights of left and right subtrees differ by at most 1 everywhere (AVL, red-black trees), keeping height O(log n).
- Degenerate/skewed: each node has one child – effectively a linked list, height O(n).

## Binary search tree (BST)
A BST is a binary tree where, for every node, all values in the left subtree are smaller and all values in the right subtree are larger. This ordering makes searching like binary search.

```text
            8
          /   \
         3     10
        / \      \
       1   6      14
          / \     /
         4   7   13
```

```java
static TreeNode search(TreeNode root, int key) {
    while (root != null && root.val != key) {
        root = key < root.val ? root.left : root.right;
    }
    return root;
}
```

## Complexity
- BST search/insert/delete: O(h) – O(log n) when balanced, O(n) when skewed (e.g. inserting sorted data).
- Traversals visit every node: O(n).
- Self-balancing trees (AVL, red-black – used by Java's TreeMap) guarantee O(log n).

## Applications
- File systems and folder hierarchies, the HTML DOM, organisation charts.
- BSTs / TreeMap: ordered maps, range queries, leaderboards.
- Database indexes use B-trees and B+ trees (wide, shallow trees).
- Expression trees in compilers, decision trees in machine learning, tries for autocomplete.

Note: an in-order traversal of a BST produces its values in sorted order – the basis of many BST problems.
MD,

'Tree Traversals' => <<<'MD'
## The three depth-first traversals
For the tree 1(2(4,5),3):
- Pre-order (root, left, right): 1 2 4 5 3 – used to copy or serialise a tree.
- In-order (left, root, right): 4 2 5 1 3 – sorted order for a BST.
- Post-order (left, right, root): 4 5 2 3 1 – used to delete a tree or evaluate expressions.

## Recursive implementations
```java
static void preorder(TreeNode n, List<Integer> out) {
    if (n == null) return;
    out.add(n.val); preorder(n.left, out); preorder(n.right, out);
}
static void inorder(TreeNode n, List<Integer> out) {
    if (n == null) return;
    inorder(n.left, out); out.add(n.val); inorder(n.right, out);
}
static void postorder(TreeNode n, List<Integer> out) {
    if (n == null) return;
    postorder(n.left, out); postorder(n.right, out); out.add(n.val);
}
```

## Iterative in-order with a stack
```java
static List<Integer> inorderIterative(TreeNode root) {
    List<Integer> out = new ArrayList<>();
    Deque<TreeNode> stack = new ArrayDeque<>();
    TreeNode cur = root;
    while (cur != null || !stack.isEmpty()) {
        while (cur != null) { stack.push(cur); cur = cur.left; }   // go left as far as possible
        cur = stack.pop();
        out.add(cur.val);
        cur = cur.right;
    }
    return out;
}
```

## Iterative pre-order
```java
static List<Integer> preorderIterative(TreeNode root) {
    List<Integer> out = new ArrayList<>();
    if (root == null) return out;
    Deque<TreeNode> stack = new ArrayDeque<>(List.of(root));
    while (!stack.isEmpty()) {
        TreeNode n = stack.pop();
        out.add(n.val);
        if (n.right != null) stack.push(n.right);   // right first so left is processed first
        if (n.left != null) stack.push(n.left);
    }
    return out;
}
```

## Complexity
- Time O(n) for every traversal.
- Space O(h) for recursion or the stack (O(log n) balanced, O(n) skewed). Morris traversal achieves O(1) space by temporarily rewiring pointers.

Note: knowing pre-order plus in-order (or post-order plus in-order) is enough to rebuild a unique binary tree.
MD,

'Level Order Traversal' => <<<'MD'
## Problem
Return the node values level by level, from left to right.

```text
      3
     / \
    9   20
       /  \
      15   7      → [[3], [9, 20], [15, 7]]
```

## Approach: BFS with a queue, one level at a time
Record the queue size at the start of each level; exactly that many nodes belong to the level.

```java
static List<List<Integer>> levelOrder(TreeNode root) {
    List<List<Integer>> result = new ArrayList<>();
    if (root == null) return result;
    Queue<TreeNode> q = new ArrayDeque<>(List.of(root));
    while (!q.isEmpty()) {
        int size = q.size();
        List<Integer> level = new ArrayList<>();
        for (int i = 0; i < size; i++) {
            TreeNode n = q.poll();
            level.add(n.val);
            if (n.left != null) q.offer(n.left);
            if (n.right != null) q.offer(n.right);
        }
        result.add(level);
    }
    return result;
}
```

## Alternative: DFS with depth
Recursively visit nodes passing the depth; append the value to result[depth]. Same output, O(h) recursion space.

## Complexity
- Time O(n), Space O(w) where w is the maximum width (up to n/2 for a complete tree).

## Variations
Zigzag order (reverse every other level), right-side view (last node of each level), average of each level, minimum depth (first leaf found by BFS).
MD,

'Height of Binary Tree' => <<<'MD'
## Problem
Find the height (maximum depth) of a binary tree – the number of nodes (or edges) on the longest path from the root to a leaf. Here we count nodes; an empty tree has height 0.

## Approach 1: recursion (DFS)
Height of a node = 1 + max(height of left, height of right).

```java
static int height(TreeNode root) {
    if (root == null) return 0;
    return 1 + Math.max(height(root.left), height(root.right));
}
```

## Dry run
For 1(2(4,5),3): height(4)=1, height(5)=1 → height(2)=2; height(3)=1 → height(1)=3.

## Approach 2: BFS level counting
Count how many levels the level-order traversal processes.

```java
static int heightBfs(TreeNode root) {
    if (root == null) return 0;
    Queue<TreeNode> q = new ArrayDeque<>(List.of(root));
    int levels = 0;
    while (!q.isEmpty()) {
        levels++;
        for (int i = q.size(); i > 0; i--) {
            TreeNode n = q.poll();
            if (n.left != null) q.offer(n.left);
            if (n.right != null) q.offer(n.right);
        }
    }
    return levels;
}
```

## Complexity
- Both: Time O(n). Space O(h) for recursion, O(w) for BFS.

Note: interviewers may define height in edges; then a single node has height 0 and an empty tree −1. State your convention.
MD,

'Diameter of Binary Tree' => <<<'MD'
## Problem
The diameter is the number of edges on the longest path between any two nodes. The path does not have to pass through the root.

## Approach 1: compute heights at every node (brute force)
For each node, diameter through it = height(left) + height(right); take the maximum over all nodes. Recomputing heights repeatedly costs O(n²) in a skewed tree.

## Approach 2: single DFS returning height (optimal)
While computing each node's height, update a global maximum with leftHeight + rightHeight.

```java
private int best = 0;

int diameter(TreeNode root) {
    depth(root);
    return best;
}
private int depth(TreeNode n) {
    if (n == null) return 0;
    int left = depth(n.left), right = depth(n.right);
    best = Math.max(best, left + right);   // path through n
    return 1 + Math.max(left, right);
}
```

## Dry run on 1(2(4,5),3)
- depth(4)=1, depth(5)=1 → at 2: best = 2, depth 2.
- depth(3)=1 → at 1: best = max(2, 2+1) = 3, the path 4–2–1–3.

## Complexity
- Brute force: O(n²). Optimal: O(n) time, O(h) space.

Note: this "return one value, update a global answer" pattern also solves maximum path sum and longest univalue path.
MD,

'Lowest Common Ancestor' => <<<'MD'
## Problem
Given two nodes p and q, find their lowest common ancestor (LCA): the deepest node that has both as descendants (a node can be a descendant of itself).

## Binary tree (no ordering): recursive search
- If the current node is null, p or q, return it.
- Search left and right. If both sides return non-null, the current node is the LCA; otherwise return the non-null side.

```java
static TreeNode lca(TreeNode root, TreeNode p, TreeNode q) {
    if (root == null || root == p || root == q) return root;
    TreeNode left = lca(root.left, p, q);
    TreeNode right = lca(root.right, p, q);
    if (left != null && right != null) return root;
    return left != null ? left : right;
}
```
Time O(n), Space O(h).

## Binary search tree: use the ordering
If both values are smaller than the node, go left; if both are larger, go right; otherwise the node splits them and is the LCA.

```java
static TreeNode lcaBst(TreeNode root, int p, int q) {
    while (root != null) {
        if (p < root.val && q < root.val) root = root.left;
        else if (p > root.val && q > root.val) root = root.right;
        else return root;
    }
    return null;
}
```
Time O(h), Space O(1).

## Brute force alternative
Store the root-to-p and root-to-q paths in lists and return the last common node. O(n) time, O(h) extra space.

## Real-world use
Finding the closest common manager in an org chart, the nearest common folder of two files, or the merge base of two Git branches.
MD,

'Validate Binary Search Tree' => <<<'MD'
## Problem
Check whether a binary tree is a valid BST: every node's value must be greater than all values in its left subtree and smaller than all values in its right subtree.

## Common mistake
Checking only node.left.val < node.val < node.right.val is not enough:

```text
      5
     / \
    1   6
       / \
      3   7      3 is in 5's right subtree but smaller than 5 → invalid
```

## Approach 1: min/max bounds (optimal)
Pass down the allowed range; going left tightens the upper bound, going right tightens the lower bound.

```java
static boolean isValidBST(TreeNode root) {
    return valid(root, Long.MIN_VALUE, Long.MAX_VALUE);
}
static boolean valid(TreeNode n, long low, long high) {
    if (n == null) return true;
    if (n.val <= low || n.val >= high) return false;
    return valid(n.left, low, n.val) && valid(n.right, n.val, high);
}
```

## Approach 2: in-order traversal must be strictly increasing
```java
private Integer prev = null;
boolean isValidInorder(TreeNode n) {
    if (n == null) return true;
    if (!isValidInorder(n.left)) return false;
    if (prev != null && n.val <= prev) return false;
    prev = n.val;
    return isValidInorder(n.right);
}
```

## Complexity
- Both: Time O(n), Space O(h).

Note: use long bounds (or nullable bounds) so trees containing Integer.MIN_VALUE or MAX_VALUE are handled correctly.
MD,

'Insert and Delete in BST' => <<<'MD'
## Insertion
Walk down as in a search; insert the new node where you fall off the tree.

```java
static TreeNode insert(TreeNode root, int key) {
    if (root == null) return new TreeNode(key);
    if (key < root.val) root.left = insert(root.left, key);
    else if (key > root.val) root.right = insert(root.right, key);
    return root;                                // duplicates ignored
}
```

## Deletion: three cases
1. Leaf node: remove it.
2. One child: replace the node with its child.
3. Two children: replace the node's value with its in-order successor (the smallest value in the right subtree), then delete that successor from the right subtree.

```java
static TreeNode delete(TreeNode root, int key) {
    if (root == null) return null;
    if (key < root.val) root.left = delete(root.left, key);
    else if (key > root.val) root.right = delete(root.right, key);
    else {
        if (root.left == null) return root.right;      // cases 1 and 2
        if (root.right == null) return root.left;
        TreeNode successor = root.right;               // case 3
        while (successor.left != null) successor = successor.left;
        root.val = successor.val;
        root.right = delete(root.right, successor.val);
    }
    return root;
}
```

## Dry run: delete 3 from 8(3(1,6(4,7)),10)
3 has two children → successor is 4 (leftmost of 6) → 3 becomes 4 → delete 4 from the right subtree. Result: 8(4(1,6(·,7)),10).

## Complexity
- Insert and delete: O(h) time – O(log n) balanced, O(n) skewed; O(h) recursion space.

Note: inserting already-sorted data creates a skewed tree; balanced trees (AVL, red-black) rotate nodes to keep h = O(log n).
MD,

'Kth Smallest Element in BST' => <<<'MD'
## Problem
Return the k-th smallest value in a BST (1-indexed).

## Approach 1: full in-order into a list
In-order traversal gives sorted values; return list[k−1]. Time O(n), Space O(n).

## Approach 2: in-order with early stop (optimal)
Iterative in-order traversal; count nodes as they are visited and stop at the k-th.

```java
static int kthSmallest(TreeNode root, int k) {
    Deque<TreeNode> stack = new ArrayDeque<>();
    TreeNode cur = root;
    while (cur != null || !stack.isEmpty()) {
        while (cur != null) { stack.push(cur); cur = cur.left; }
        cur = stack.pop();
        if (--k == 0) return cur.val;
        cur = cur.right;
    }
    throw new IllegalArgumentException("k is larger than the tree");
}
```

## Dry run (k = 3) on 5(3(2(1),4),6)
In-order visits 1, 2, 3 → the third is 3.

## Complexity
- Early-stop in-order: O(h + k) time, O(h) space.

## Follow-up: frequent queries with inserts/deletes
Store the size of each node's left subtree. Then go left if k ≤ leftSize, return the node if k = leftSize + 1, otherwise go right with k − leftSize − 1. Each query is O(h).
MD,

];
