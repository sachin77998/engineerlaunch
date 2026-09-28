<?php
$topics = [];
$shared = json_decode(file_get_contents(resource_path('data/sql-transaction-shared.json')), true, 512, JSON_THROW_ON_ERROR);
foreach (['banking-withdrawals','account-transfers','university-fees','university-payroll','corporate-salary-batches','failure-retry-reconciliation','transaction-schema-laravel','transaction-interview-cases'] as $slug) {
    $topic = json_decode(file_get_contents(resource_path('data/sql-transactions/'.$slug.'.json')), true, 512, JSON_THROW_ON_ERROR);
    foreach ($topic['sections'] as &$section) {
        foreach ($section['blocks'] as &$block) {
            if (isset($block['shared'])) {
                if (!isset($shared[$block['shared']])) throw new RuntimeException('Missing SQL lesson block: '.$block['shared']);
                $block = $shared[$block['shared']];
            }
        }
        unset($block);
    }
    unset($section);
    // Preserve the existing detailed code lab and interview cases inside the expanded lesson.
    if ($slug === 'transaction-schema-laravel') {
        foreach ($topic['questions'] as $question) $topic['sections'][6]['blocks'][] = [
            'type'=>'legacy', 'title'=>$question['question'], 'answer'=>$question['answer'],
        ];
    }
    if ($slug === 'transaction-interview-cases') {
        $topic['sections'][21]['blocks'][] = ['type'=>'questions','items'=>array_map(
            fn($question)=>[$question['question'],$question['answer']], $topic['questions']
        )];
    }
    $topics[$slug] = $topic;
}
return $topics;
