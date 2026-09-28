# Expanded SQL transaction lessons

The eight existing real-world SQL topic URLs are unchanged. Each now renders 23 lifecycle sections on one page, inside the existing header, left topic sidebar, main content and right optional-guide/course sidebar. Existing SQL interview topics and the mysql alias remain available.

Content remains JSON/config based, not database backed. Topic data is in resources/data/sql-transactions/*.json. Repeated blocks are defined in resources/data/sql-transaction-shared.json and resolved by config/sql_transactions.php. A missing shared reference fails configuration loading instead of silently omitting a section.

The existing resources/views/learning/sql-case-studies.blade.php renders dynamic sections through resources/views/learning/partials/sql-case-block.blade.php. Supported blocks: text, schema, table, flow, highlighted code, questions, related links and preserved code-lab examples. LearningCodeHighlighter escapes source without executing it. Table regions scroll horizontally and are keyboard focusable. Section IDs are stable and unique.

No migrations, seeders, financial models, controller changes or route changes were added. Displayed SQL and payment records are educational only. These lessons never send bank requests or create financial tables in the portal database.

Validation:

    php artisan test --filter=SqlTransactionLessonsTest
    php artisan test --filter=VisualLearningTest
    php artisan test --filter=LearningDirectTopicsTest
    php artisan config:cache
    php artisan view:cache

After local cache checks, use php artisan config:clear and php artisan view:clear to restore uncached development.

Open /learn/sql and each of the eight real-world topics. Check table-of-contents anchors, schema/sample/final tables, highlighted SQL/PHP, previous/next links and both sidebars. At narrow widths, tables and code scroll inside the main content area. A connected-browser visual review was unavailable during implementation.

For cPanel, Update from Remote then Deploy HEAD Commit. No manual database import is needed for this content update.
