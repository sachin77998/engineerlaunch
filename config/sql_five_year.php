<?php

// 5-year experience SQL interview series, rendered by learning.sql-case-studies.
$code = fn (string $caption, string $source) => ['type' => 'code', 'caption' => $caption, 'language' => 'sql', 'source' => trim($source)];
$table = fn (string $caption, array $columns, array $rows) => ['type' => 'table', 'caption' => $caption, 'columns' => $columns, 'rows' => $rows];
$text = fn (string ...$paragraphs) => ['type' => 'text', 'paragraphs' => $paragraphs];
$ask = fn (array $items) => ['type' => 'questions', 'items' => $items];

$salaries = $table('employees', ['id', 'name', 'salary'], [[1, 'Amit', 50000], [2, 'Rahul', 80000], [3, 'Priya', 70000], [4, 'Neha', 80000], [5, 'Raj', 60000]]);
$departmentSalaries = $table('employees', ['id', 'name', 'department_id', 'salary'], [[1, 'Amit', 1, 50000], [2, 'Rahul', 1, 80000], [3, 'Priya', 1, 70000], [4, 'Neha', 2, 90000], [5, 'Raj', 2, 60000], [6, 'Karan', 3, 100000]]);
$orders = $table('orders', ['id', 'customer_id', 'total_amount', 'order_date'], [[1, 101, 5000, '2026-01-10'], [2, 101, 7000, '2026-02-15'], [3, 102, 3000, '2026-01-20'], [4, 102, 9000, '2026-03-10'], [5, 103, 4000, '2026-02-05']]);

$sections = [
    ['id' => 'schema', 'title' => 'Practice Database Structure', 'blocks' => [
        $text('Every question in this series uses the same six tables, so you can create them once and run each query yourself.'),
        ['type' => 'schema', 'name' => 'customers', 'columns' => [['id', 'BIGINT PK'], ['name', 'VARCHAR(100)'], ['email', 'VARCHAR(190)'], ['city', 'VARCHAR(100)'], ['created_at', 'TIMESTAMP']], 'note' => 'Used for duplicate emails (Q7) and customers without orders (Q8).'],
        ['type' => 'schema', 'name' => 'products', 'columns' => [['id', 'BIGINT PK'], ['name', 'VARCHAR(150)'], ['category', 'VARCHAR(80)'], ['price', 'DECIMAL(12,2)'], ['stock', 'INT']], 'note' => 'Catalogue joined through order_items.'],
        ['type' => 'schema', 'name' => 'orders', 'columns' => [['id', 'BIGINT PK'], ['customer_id', 'BIGINT FK customers.id'], ['status', 'VARCHAR(20)'], ['total_amount', 'DECIMAL(12,2)'], ['order_date', 'DATE']], 'note' => 'Latest order (Q9), top customer (Q10) and monthly sales (Q11–Q12).'],
        ['type' => 'schema', 'name' => 'order_items', 'columns' => [['id', 'BIGINT PK'], ['order_id', 'BIGINT FK orders.id'], ['product_id', 'BIGINT FK products.id'], ['quantity', 'INT'], ['price', 'DECIMAL(12,2)']], 'note' => 'One row per product line in an order.'],
        ['type' => 'schema', 'name' => 'employees', 'columns' => [['id', 'BIGINT PK'], ['name', 'VARCHAR(100)'], ['department_id', 'BIGINT FK departments.id'], ['salary', 'DECIMAL(12,2)'], ['manager_id', 'BIGINT FK employees.id'], ['joining_date', 'DATE']], 'note' => 'Salary ranking and department questions (Q1–Q6).'],
        ['type' => 'schema', 'name' => 'departments', 'columns' => [['id', 'BIGINT PK'], ['name', 'VARCHAR(100)']], 'note' => 'Joined to employees on department_id.'],
    ]],
    ['id' => 'q1', 'title' => 'Q1. Find the Second-Highest Salary', 'blocks' => [
        $salaries,
        $text('Find the second-highest distinct salary.'),
        $code('Subquery solution', <<<'SQL'
SELECT MAX(salary) AS second_highest_salary
FROM employees
WHERE salary < (
    SELECT MAX(salary)
    FROM employees
);
SQL),
        $table('Output', ['second_highest_salary'], [[70000]]),
        $code('Better: window function', <<<'SQL'
SELECT salary
FROM (
    SELECT
        salary,
        DENSE_RANK() OVER (ORDER BY salary DESC) AS rnk
    FROM employees
) x
WHERE rnk = 2;
SQL),
        $table('How DENSE_RANK() ranks the salaries', ['salary', 'rank'], [[80000, 1], [80000, 1], [70000, 2], [60000, 3], [50000, 4]]),
        $ask([['Why DENSE_RANK() instead of ROW_NUMBER()?', '80000 occurs twice. ROW_NUMBER() would give the two 80000 rows ranks 1 and 2, so "rank 2" would wrongly return 80000. DENSE_RANK() gives both rank 1 and the next distinct salary (70000) rank 2.']]),
    ]],
    ['id' => 'q2', 'title' => 'Q2. Find the Third-Highest Salary', 'blocks' => [
        $code('Solution', <<<'SQL'
SELECT salary
FROM (
    SELECT
        salary,
        DENSE_RANK() OVER (
            ORDER BY salary DESC
        ) AS rnk
    FROM employees
) x
WHERE rnk = 3;
SQL),
        $table('Output', ['salary'], [[60000]]),
        $ask([['The interviewer asks for the 5th highest salary. What do you write?', 'Do not write five nested subqueries. Change the filter to rnk = 5 on the DENSE_RANK() query, or use another appropriate ranking technique.']]),
    ]],
    ['id' => 'q3', 'title' => 'Q3. Highest-Paid Employee in Each Department', 'blocks' => [
        $departmentSalaries,
        $code('Solution', <<<'SQL'
SELECT *
FROM (
    SELECT
        e.*,
        ROW_NUMBER() OVER (
            PARTITION BY department_id
            ORDER BY salary DESC
        ) AS rn
    FROM employees e
) x
WHERE rn = 1;
SQL),
        $table('Output', ['name', 'department_id', 'salary'], [['Rahul', 1, 80000], ['Neha', 2, 90000], ['Karan', 3, 100000]]),
        $ask([['What does PARTITION BY department_id mean?', 'Restart the numbering for every department, so each department gets its own rank 1.']]),
    ]],
    ['id' => 'q4', 'title' => 'Q4. Top 3 Employees from Each Department', 'blocks' => [
        $text('One of the most commonly asked questions at 5 years of experience.'),
        $code('Solution', <<<'SQL'
SELECT *
FROM (
    SELECT
        e.*,
        DENSE_RANK() OVER (
            PARTITION BY department_id
            ORDER BY salary DESC
        ) AS rnk
    FROM employees e
) x
WHERE rnk <= 3;
SQL),
        $ask([['Why DENSE_RANK() here?', 'If two employees have the same salary they should receive the same rank, so ties are not cut off arbitrarily.']]),
    ]],
    ['id' => 'q5', 'title' => 'Q5. Employees Earning More Than the Company Average', 'blocks' => [
        $code('Solution', <<<'SQL'
SELECT
    id,
    name,
    salary
FROM employees
WHERE salary > (
    SELECT AVG(salary)
    FROM employees
);
SQL),
        $code('Follow-up: the same query with a CTE', <<<'SQL'
WITH company_average AS (
    SELECT AVG(salary) AS avg_salary
    FROM employees
)
SELECT
    e.id,
    e.name,
    e.salary
FROM employees e
CROSS JOIN company_average a
WHERE e.salary > a.avg_salary;
SQL),
    ]],
    ['id' => 'q6', 'title' => 'Q6. Employees Earning More Than Their Department Average', 'blocks' => [
        $code('Solution', <<<'SQL'
SELECT *
FROM (
    SELECT
        e.*,
        AVG(salary) OVER (
            PARTITION BY department_id
        ) AS department_avg
    FROM employees e
) x
WHERE salary > department_avg;
SQL),
        $table('Department 1 example (average 66666.67)', ['name', 'salary', 'returned'], [['Amit', 50000, 'No'], ['Rahul', 80000, 'Yes'], ['Priya', 70000, 'Yes']]),
    ]],
    ['id' => 'q7', 'title' => 'Q7. Find Duplicate Customer Emails', 'blocks' => [
        $table('customers', ['id', 'name', 'email'], [[1, 'Amit', 'amit@gmail.com'], [2, 'Raj', 'raj@gmail.com'], [3, 'Neha', 'amit@gmail.com'], [4, 'Karan', 'karan@gmail.com']]),
        $code('Solution', <<<'SQL'
SELECT
    email,
    COUNT(*) AS total
FROM customers
GROUP BY email
HAVING COUNT(*) > 1;
SQL),
        $table('Output', ['email', 'total'], [['amit@gmail.com', 2]]),
        $code('Follow-up: prevent duplicates at the database level', <<<'SQL'
ALTER TABLE customers
ADD CONSTRAINT uk_customer_email UNIQUE (email);
SQL),
        $ask([['Why a unique constraint instead of only application validation?', 'Two concurrent requests can both pass an application check; only the database constraint guarantees uniqueness.']]),
    ]],
    ['id' => 'q8', 'title' => 'Q8. Customers Who Never Placed an Order', 'blocks' => [
        $code('Solution 1: LEFT JOIN', <<<'SQL'
SELECT
    c.id,
    c.name
FROM customers c
LEFT JOIN orders o
    ON c.id = o.customer_id
WHERE o.id IS NULL;
SQL),
        $code('Solution 2: NOT EXISTS', <<<'SQL'
SELECT
    c.id,
    c.name
FROM customers c
WHERE NOT EXISTS (
    SELECT 1
    FROM orders o
    WHERE o.customer_id = c.id
);
SQL),
        $ask([['Which is faster, LEFT JOIN or NOT EXISTS?', 'There is no universal answer: the optimizer, indexes, engine, data distribution and query shape all matter. A good answer is "I would check the execution plan rather than assume one is always faster."']]),
    ]],
    ['id' => 'q9', 'title' => 'Q9. Latest Order of Every Customer', 'blocks' => [
        $orders,
        $code('Solution', <<<'SQL'
SELECT *
FROM (
    SELECT
        o.*,
        ROW_NUMBER() OVER (
            PARTITION BY customer_id
            ORDER BY order_date DESC, id DESC
        ) AS rn
    FROM orders o
) x
WHERE rn = 1;
SQL),
        $table('Output', ['customer_id', 'order id'], [[101, 2], [102, 4], [103, 5]]),
        $ask([['Why add id DESC?', 'If two orders share exactly the same order_date, id DESC is a deterministic tie-breaker. Interviewers at this level look for that detail.']]),
    ]],
    ['id' => 'q10', 'title' => 'Q10. Customer with the Highest Total Purchase', 'blocks' => [
        $code('Solution', <<<'SQL'
SELECT
    customer_id,
    SUM(total_amount) AS total_purchase
FROM orders
GROUP BY customer_id
ORDER BY total_purchase DESC
LIMIT 1;
SQL),
        $code('Better when ties matter', <<<'SQL'
SELECT *
FROM (
    SELECT
        customer_id,
        SUM(total_amount) AS total_purchase,
        DENSE_RANK() OVER (
            ORDER BY SUM(total_amount) DESC
        ) AS rnk
    FROM orders
    GROUP BY customer_id
) x
WHERE rnk = 1;
SQL),
        $text('If two customers both spent ₹5 lakh, the ranked version returns both, while LIMIT 1 silently drops one.'),
    ]],
    ['id' => 'q11', 'title' => 'Q11 (Bonus). Monthly Sales', 'blocks' => [
        $code('Solution', <<<'SQL'
SELECT
    YEAR(order_date) AS year,
    MONTH(order_date) AS month,
    SUM(total_amount) AS total_sales
FROM orders
GROUP BY
    YEAR(order_date),
    MONTH(order_date)
ORDER BY
    year,
    month;
SQL),
        $table('Result', ['year', 'month', 'total_sales'], [[2026, 1, 250000], [2026, 2, 380000], [2026, 3, 450000]]),
    ]],
    ['id' => 'q12', 'title' => 'Q12 (Bonus). Month-over-Month Sales Growth', 'blocks' => [
        $code('Step 1: monthly sales with the previous month', <<<'SQL'
WITH monthly_sales AS (
    SELECT
        YEAR(order_date) AS year,
        MONTH(order_date) AS month,
        SUM(total_amount) AS sales
    FROM orders
    GROUP BY
        YEAR(order_date),
        MONTH(order_date)
)
SELECT
    year,
    month,
    sales,
    LAG(sales) OVER (
        ORDER BY year, month
    ) AS previous_month_sales
FROM monthly_sales;
SQL),
        $code('Step 2: growth percentage', <<<'SQL'
WITH monthly_sales AS (
    SELECT
        YEAR(order_date) AS year,
        MONTH(order_date) AS month,
        SUM(total_amount) AS sales
    FROM orders
    GROUP BY
        YEAR(order_date),
        MONTH(order_date)
),
sales_with_previous AS (
    SELECT
        year,
        month,
        sales,
        LAG(sales) OVER (
            ORDER BY year, month
        ) AS previous_sales
    FROM monthly_sales
)
SELECT
    year,
    month,
    sales,
    previous_sales,
    ROUND(
        ((sales - previous_sales) / previous_sales) * 100,
        2
    ) AS growth_percentage
FROM sales_with_previous;
SQL),
    ]],
    ['id' => 'progression', 'title' => '5-Year SQL Interview Progression', 'blocks' => [
        $table('Series plan', ['Part', 'Topics', 'Questions'], [
            ['Part 1', 'Ranking, joins, aggregation', '1–12'], ['Part 2', 'Complex joins, CTEs, subqueries', '13–20'],
            ['Part 3', 'Window functions', '21–30'], ['Part 4', 'Real-world business problems', '31–40'],
            ['Part 5', 'Transactions & concurrency', '41–45'], ['Part 6', 'Indexes & optimization', '46–50'],
        ]),
    ]],
];

$questions = [];
foreach ($sections as $section) {
    if (!str_starts_with($section['id'], 'q')) continue;
    $firstCode = current(array_filter($section['blocks'], fn ($block) => ($block['type'] ?? null) === 'code')) ?: [];
    $questions[] = ['number' => count($questions) + 1, 'question' => $section['title'], 'answer' => $firstCode['source'] ?? ''];
}

return [
    'five-year-interview-part-1' => [
        'title' => '5-Year Interview: Ranking, Joins & Aggregation',
        'view' => 'learning.sql-case-studies',
        'summary' => 'Part 1 of the 5-year SQL interview series: salary ranking, per-department top-N, duplicates, anti-joins, latest records and month-over-month growth.',
        'example_label' => 'Practice data on customers, products, orders, order_items, employees and departments',
        'questions' => $questions,
        'sections' => $sections,
    ],
];
