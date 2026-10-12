<?php
namespace App\Services;

class LearningPresentation
{
    /** Keep code blocks and line breaks intact; never execute supplied examples. */
    public function blocks(string $text): array
    {
        $blocks = [];
        // In-depth explanations (config/learning_deep) use "## Heading", "- item" lists, paragraphs and ``` code.
        $structured = (bool) preg_match('/^(##\s|-\s|\d+\.\s)/m', $text);
        foreach (preg_split('/(```[^\n]*\n.*?\n```)/s', trim($text), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $segment) {
            if (str_starts_with($segment, '```')) {
                $blocks[] = ['type' => 'code', 'text' => trim(preg_replace('/^```[^\n]*\n|\n```$/', '', $segment), "\n")];
                continue;
            }
            array_push($blocks, ...($structured ? $this->structuredBlocks($segment) : $this->paragraphBlocks($segment)));
        }
        return $blocks;
    }

    /** Headings, numbered/bulleted lists, notes and plain paragraphs, in order. */
    private function structuredBlocks(string $text): array
    {
        $blocks = [];
        foreach (preg_split('/\R\s*\R/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) as $chunk) {
            $lines = preg_split('/\R/u', trim($chunk));
            $paragraph = [];
            $list = null;
            $flush = function () use (&$blocks, &$paragraph, &$list) {
                if ($paragraph) {
                    $text = trim(implode(' ', $paragraph));
                    $blocks[] = preg_match('/^(Note|Tip|Important|Remember):/i', $text) ? ['type' => 'note', 'text' => $text] : ['type' => 'paragraph', 'text' => $text];
                    $paragraph = [];
                }
                if ($list) { $blocks[] = $list; $list = null; }
            };
            foreach ($lines as $line) {
                $line = rtrim($line);
                if (preg_match('/^#{2,3}\s+(.+)$/', $line, $m)) { $flush(); $blocks[] = ['type' => 'heading', 'text' => trim($m[1])]; continue; }
                if (preg_match('/^\s*(?:[-•*]|(\d+)\.)\s+(.+)$/u', $line, $m)) {
                    if ($paragraph) { $saved = $list; $list = null; $flush(); $list = $saved; }
                    $ordered = $m[1] !== '';
                    if (!$list || $list['ordered'] !== $ordered) { if ($list) $blocks[] = $list; $list = ['type' => 'points', 'ordered' => $ordered, 'items' => []]; }
                    $list['items'][] = trim($m[2]);
                    continue;
                }
                if ($list) { $blocks[] = $list; $list = null; }
                $paragraph[] = trim($line);
            }
            $flush();
        }
        return $blocks;
    }

    private function paragraphBlocks(string $text): array
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

    /** In-depth explanation for a lesson or question title, from config/learning_deep/{track}.php. */
    public static function deep(string $track, string $title): ?string
    {
        static $cache = [];
        // A track is either one file (learning_deep/{track}.php) or a folder of topic files (learning_deep/{track}/*.php).
        $deep = $cache[$track] ??= collect(config('learning_deep.' . $track, []))
            ->flatMap(fn ($value, $key) => is_array($value) ? $value : [$key => $value])->all();
        return $deep[$title] ?? $deep[trim($title)] ?? null;
    }
}
