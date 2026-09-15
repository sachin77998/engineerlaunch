<?php
namespace App\Services;

class LearningPresentation
{
    /** Keep code blocks and line breaks intact; never execute supplied examples. */
    public function blocks(string $text): array
    {
        $blocks = [];
        foreach (preg_split('/\R\s*\R/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) as $paragraph) {
            $code = preg_match('/(?:^|\n)(?:SELECT\b|WITH\b|INSERT\b|UPDATE\b|DELETE\b|CREATE\b|ALTER\b|DROP\b|GRANT\b|REVOKE\b|COMMIT\b|ROLLBACK\b|SAVEPOINT\b|TRUNCATE\b|(?:MySQL|SQL Server):\s*SELECT\b)/i', $paragraph);
            if ($code) {
                $blocks[] = ['type' => 'code', 'text' => $paragraph];
            } else {
                $points = preg_split('/\R+|(?<=[.!?])\s+(?=[A-Z])/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY);
                $blocks[] = ['type' => 'points', 'items' => $points];
            }
        }
        return $blocks;
    }

    public function overview(string $track): array
    {
        return config('learning_visuals.'.$track, []);
    }
}
