<?php

namespace App\Services\Recommendations;

use App\Models\Job;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Skill vocabulary + a skill-to-skill similarity graph learned from job postings.
 *
 * Every active job is reduced to the set of skills it mentions; skills that appear together more often than chance
 * are related. Relatedness is the lift P(a,b) / (P(a)·P(b)), squashed to 0..1 and kept for pairs seen in at least
 * a few postings. "C++" therefore pulls in "C", "Embedded C" or "Linux" because employers ask for them together.
 * Until the graph has been built (recommendations:build-skill-graph) a small curated map is used.
 */
class SkillGraph
{
    private const FILE = 'recommendations/skill-graph.json';

    private const CURATED = [
        'C++' => ['C', 'Embedded C', 'Data Structures & Algorithms', 'Linux', 'Rust', 'Qt'],
        'C' => ['C++', 'Embedded C', 'Linux', 'Data Structures & Algorithms'],
        'Java' => ['Spring Boot', 'Hibernate', 'Microservices', 'Kotlin', 'SQL'],
        'Spring Boot' => ['Java', 'Microservices', 'Hibernate', 'Kafka', 'SQL'],
        'PHP' => ['Laravel', 'MySQL', 'CodeIgniter', 'WordPress', 'JavaScript'],
        'Laravel' => ['PHP', 'MySQL', 'Vue.js', 'REST API'],
        'Python' => ['Django', 'Flask', 'FastAPI', 'Data Science', 'Machine Learning'],
        'JavaScript' => ['TypeScript', 'React', 'Node.js', 'HTML & CSS'],
        'React' => ['JavaScript', 'TypeScript', 'Next.js', 'Redux', 'Node.js'],
        'Node.js' => ['JavaScript', 'TypeScript', 'Express', 'MongoDB'],
        'AutoCAD' => ['SolidWorks', 'CATIA', 'Creo', 'GD&T'],
        'CNC Programming' => ['VMC Operator', 'CNC Operator', 'GD&T', 'Machining'],
        'PLC' => ['SCADA', 'Industrial Automation', 'Electrician'],
        'Accounting' => ['GST & Taxation', 'Tally', 'Financial Analysis'],
    ];

    private ?array $matcher = null;
    private ?array $graph = null;

    /** @return array<string, string> skill => field */
    public function fields(): array
    {
        $this->compile();
        return $this->matcher['fields'];
    }

    /** Skills mentioned in a text, with how many times each appears. @return array<string,int> */
    public function skillsIn(string $text): array
    {
        $this->compile();
        $found = [];
        $text = ' ' . mb_strtolower($text) . ' ';
        foreach ($this->matcher['patterns'] as $pattern) {
            if (!preg_match_all($pattern, $text, $matches)) continue;
            foreach ($matches[0] as $alias) {
                $skill = $this->matcher['aliases'][trim($alias)] ?? null;
                if ($skill) $found[$skill] = ($found[$skill] ?? 0) + 1;
            }
        }
        return $found;
    }

    /** Related skills with weights 0..1, strongest first. @return array<string,float> */
    public function related(string $skill, int $limit = 6): array
    {
        // Learned pairs are discounted; curated pairs (known stacks) stay on top while posting data is thin.
        $related = array_map(fn ($w) => $w * 0.8, $this->graph()['related'][$skill] ?? []);
        foreach (self::CURATED[$skill] ?? [] as $i => $other) $related[$other] = max($related[$other] ?? 0, 0.9 - $i * 0.05);
        arsort($related);
        return array_slice($related, 0, $limit, true);
    }

    /** Inverse document frequency of a skill across postings (rare skills count more). */
    public function idf(string $skill): float
    {
        $graph = $this->graph();
        $jobs = max(1, $graph['jobs'] ?? 0);
        $df = $graph['df'][$skill] ?? null;
        return $df === null ? 1.0 : log(1 + $jobs / (1 + $df));
    }

    public function build(): array
    {
        $df = [];
        $pairs = [];
        $jobs = 0;
        Job::active()->select(['id', 'title', 'requirements'])->lazyById(1000)->each(function (Job $job) use (&$df, &$pairs, &$jobs) {
            $skills = array_keys($this->skillsIn($job->title . ' ' . mb_substr(is_string($job->requirements) ? $job->requirements : json_encode($job->requirements), 0, 3000)));
            if (!$skills) return;
            $jobs++;
            sort($skills);
            foreach ($skills as $i => $a) {
                $df[$a] = ($df[$a] ?? 0) + 1;
                for ($j = $i + 1; $j < count($skills); $j++) $pairs[$a . '|' . $skills[$j]] = ($pairs[$a . '|' . $skills[$j]] ?? 0) + 1;
            }
        });
        $related = [];
        foreach ($pairs as $key => $together) {
            if ($together < 3) continue;
            [$a, $b] = explode('|', $key);
            $lift = ($together * $jobs) / ($df[$a] * $df[$b]);
            if ($lift <= 1.2) continue;
            // Confidence-weighted lift in 0..1: frequent and surprising pairs score highest.
            $score = round(min(1, log($lift) / 3) * min(1, $together / 10), 3);
            $related[$a][$b] = $score;
            $related[$b][$a] = $score;
        }
        $graph = ['built_at' => now()->toIso8601String(), 'jobs' => $jobs, 'df' => $df, 'related' => $related];
        Storage::put(self::FILE, json_encode($graph));
        Cache::forget('skill-graph');
        $this->graph = $graph;
        return ['jobs' => $jobs, 'skills' => count($df), 'pairs' => array_sum(array_map('count', $related)) / 2];
    }

    private function graph(): array
    {
        return $this->graph ??= Cache::remember('skill-graph', now()->addHours(6), function () {
            return Storage::exists(self::FILE) ? (json_decode(Storage::get(self::FILE), true) ?: []) : [];
        });
    }

    /** One regex per chunk of aliases (longest first) instead of hundreds of separate matches per text. */
    private function compile(): void
    {
        if ($this->matcher) return;
        $aliases = [];
        $fields = [];
        foreach (config('skill_taxonomy', []) as $field => $skills) {
            foreach ($skills as $skill => $list) {
                $fields[$skill] = $field;
                foreach ($list as $alias) {
                    // Very short or ambiguous aliases ("ca", "go", "js") cause false matches inside job text.
                    if (mb_strlen($alias) < 2 && $alias !== 'c') continue;
                    if (in_array($alias, ['ca', 'mr', 'gc', 'sta', 'cam', 'ev', 'go', 'it'], true)) continue;
                    $aliases[mb_strtolower($alias)] ??= $skill;
                }
            }
        }
        $keys = array_keys($aliases);
        usort($keys, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $patterns = [];
        foreach (array_chunk($keys, 200) as $chunk) {
            $patterns[] = '/(?<![\pL\pN+#.&])(?:' . implode('|', array_map(fn ($a) => preg_quote($a, '/'), $chunk)) . ')(?![\pL\pN+#&])/u';
        }
        $this->matcher = ['aliases' => $aliases, 'patterns' => $patterns, 'fields' => $fields];
    }
}
