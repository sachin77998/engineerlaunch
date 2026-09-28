<?php
$topics=[];
foreach (['banking-withdrawals','account-transfers','university-fees','university-payroll','corporate-salary-batches','failure-retry-reconciliation','transaction-schema-laravel','transaction-interview-cases'] as $slug) {
    $topics[$slug]=json_decode(file_get_contents(resource_path('data/sql-transactions/'.$slug.'.json')),true,512,JSON_THROW_ON_ERROR);
}
return $topics;
