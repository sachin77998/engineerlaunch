<?php

namespace App\Services\Technologies;

use App\Models\Job;
use App\Models\Technology;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Dynamic technology directory.
 *
 * discover(): programming languages, frameworks, libraries, databases, DevOps and cloud tools from Wikidata (CC0),
 *             ranked by how many Wikipedia language editions describe them, plus every skill in config/skill_taxonomy.
 * linkJobs(): matches every active job's title and requirements against all technology names and aliases, fills
 *             job_technology and stores jobs_count, so each technology page lists real openings.
 */
class TechnologyCatalog
{
    // Wikidata class => [directory category, minimum Wikipedia sitelinks, include subclasses].
    public const CLASSES = [
        'Q9143' => ['Programming Languages', 8, true],
        'Q1330336' => ['Frameworks', 4, false],
        'Q271680' => ['Frameworks', 10, false],
        'Q783866' => ['Frameworks', 4, false],
        'Q3932296' => ['Databases', 4, false],
        'Q176165' => ['Databases', 6, false],
        'Q7935198' => ['DevOps & Cloud', 2, false],
        'Q118218718' => ['DevOps & Cloud', 2, false],
        'Q16947796' => ['DevOps & Cloud', 2, false],
        'Q1186723' => ['DevOps & Cloud', 3, false],
        'Q1153767' => ['DevOps & Cloud', 10, false],
        'Q1092177' => ['Data & Messaging', 2, false],
        'Q188860' => ['Libraries, Data & ML', 20, false],
        'Q29642950' => ['Libraries, Data & ML', 8, false],
    ];

    // Names that are also ordinary words or letters: matched only with exact capitalisation and in a programming
    // context — a skills-list item ("Java, Go, Rust"), "C/C++", or before developer/engineer. Not "Go-to-market".
    private const CASE_SENSITIVE = [
        'go',
        'r',
        'c',
        'swift',
        'rust',
        'dart',
        'julia',
        'ada',
        'elm',
        'crystal',
        'red',
        'pascal',
        'logo',
        'scratch',
        'io',
        'hack',
        'pony', 
        'chapel',
        'nim',
        'zig',
        'ring',
        'joy',
        'self',
        'alice',
        'turing',
        'spring',
        'express',
        'react',
        'ember',
        'backbone',
        'meteor',
        'flask',
        'pandas',
        'keras', 
        'spark',
        'hive', 
        'pig',
        'storm',
        'flink',
        'beam', 
        'chef', 
        'puppet',
        'salt', 
        'swing', 
        'struts',
        'play',
        'rocket',
         'gin',
          'echo',
           'fiber',
            'phoenix',
             'mercury',
              'oz',
               'apex', 
               'shell',
                'assembly',
                 'access', 
                 'excel', 
                 'word',
                  'sage', 
                  'nest', 
                  'next',
        'vue', 
        'svelte', 
        'solid', 
        'remix', 
        'astro', 
        'gatsby', 
        'ionic', 
        'cordova', 
        'xamarin', 
        'unity', 
        'unreal', 
        'godot', 
        'maven', 
        'gradle', 
        'ant',
        'make', 
        'cmake', 
        'bazel', 
        'helm', 
        'vault', 
        'consul', 
        'nomad', 
        'kong', 
        'envoy', 
        'istio', 
        'linkerd', 
        'airflow', 
        'luigi', 
        'dagster', 
        'prefect',
        'cobra', 'viper', 'sinatra', 'grape', 'hanami', 'lucky', 'amber', 'kemal', 'pyramid', 'bottle', 'falcon', 'sanic', 'tornado', 'twisted',
        'dash', 'streamlit', 'gradio', 'shiny', 'plumber', 'bootstrap', 'foundation', 'bulma', 'tailwind', 'material', 'ant design', 'chakra', 'prime',
        'jquery', 'lodash', 'moment', 'axios', 'redux', 'mobx', 'recoil', 'zustand', 'jotai', 'rxjs', 'three', 'chart', 'd3', 'p5', 'pixi', 'phaser',
        'air', 'net', 'base', 'core', 'data', 'flow', 'form', 'gate', 'hub', 'lab', 'link', 'log', 'mesh', 'node', 'page', 'path', 'pipe', 'port', 'post',
        'rest', 'root', 'rule', 'run', 'scope', 'shape', 'sheet', 'slim', 'snap', 'sort', 'spec', 'stack', 'step', 'tag', 'task', 'test', 'time', 'tool',
        'track', 'view', 'web', 'work', 'zone', 'ace', 'act', 'age', 'aim', 'app', 'art', 'bit', 'box', 'bus', 'cap', 'cell', 'clip', 'code', 'cube',
        'deep', 'dot', 'edge', 'fast', 'fire', 'flat', 'fly', 'fox', 'gem', 'glue', 'grid', 'ice', 'ink', 'iron', 'jam', 'jet', 'key', 'kit', 'lead',
        'lens', 'light', 'line', 'mint', 'nano', 'nova', 'opal', 'pearl', 'pilot', 'plan', 'pulse', 'quest', 'rain', 'ray', 'reach', 'scale', 'seed',
        'sense', 'shift', 'sign', 'smart', 'source', 'space', 'star', 'stream', 'sun', 'tide', 'wave', 'wing', 'wolf', 'yarn', 'zen'];

    public const TAXONOMY_FIELDS = ['Computer Science & IT'];

    // Single letters other than C and R are too ambiguous to match at all.
    private const SKIP = ['b', 'd', 'e', 'f', 'j', 'k', 'q', 's', 't', 'v', 'x', 'z', 'a', 'language', 'programming language', 'software'];

    /** @return array{created:int, updated:int} */
    public function discover(): array
    {
        $created = 0;
        $updated = 0;
        foreach (self::CLASSES as $qid => [$category, $minLinks, $subclasses]) {
            foreach ($this->fetch($qid, $minLinks, $subclasses) as $row) {
                $result = $this->upsert($row['name'], $category, $row['aliases'], $row['qid'], $row['popularity']);
                $result === 'created' ? $created++ : $updated++;
            }
            usleep(800000);
        }
        // Only the software/IT part of the skill taxonomy belongs in a technology directory.
        foreach (array_intersect_key(config('skill_taxonomy', []), array_flip(self::TAXONOMY_FIELDS)) as $field => $skills) {
            foreach ($skills as $skill => $aliases) {
                $result = $this->upsert($skill, $field, $aliases, null, 0);
                $result === 'created' ? $created++ : $updated++;
            }
        }
        return ['created' => $created, 'updated' => $updated];
    }

    public function fetch(string $class, int $minLinks, bool $subclasses): array
    {
        $type = $subclasses ? "?item wdt:P31 ?type. ?type wdt:P279* wd:{$class}." : "?item wdt:P31 wd:{$class}.";
        $query = <<<SPARQL
SELECT ?item ?itemLabel ?links (GROUP_CONCAT(DISTINCT ?alias; separator="|") AS ?aliases) WHERE {
  {$type}
  ?item wikibase:sitelinks ?links. FILTER(?links >= {$minLinks})
  OPTIONAL { ?item skos:altLabel ?alias. FILTER(LANG(?alias) = "en") }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
} GROUP BY ?item ?itemLabel ?links ORDER BY DESC(?links) LIMIT 1500
SPARQL;
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent'), 'Accept' => 'application/sparql-results+json'])
            ->timeout(120)->retry(2, 5000)->get(config('discovery.wikidata_url'), ['query' => $query]);
        if (!$response->successful()) throw new RuntimeException('Wikidata returned HTTP ' . $response->status());
        $rows = [];
        foreach ($response->json('results.bindings', []) as $row) {
            $name = $this->clean($row['itemLabel']['value'] ?? '');
            if ($name === null) continue;
            $aliases = array_values(array_filter(array_map(fn ($a) => $this->clean($a), explode('|', $row['aliases']['value'] ?? '')),
                fn ($a) => $a !== null && mb_strlen($a) >= 2 && mb_strlen($a) <= 30));
            $rows[] = ['name' => $name, 'aliases' => array_slice($aliases, 0, 6), 'qid' => Str::afterLast($row['item']['value'], '/'), 'popularity' => (int) ($row['links']['value'] ?? 0)];
        }
        return $rows;
    }

    /** @return array{jobs:int, links:int, technologies:int} */
    public function linkJobs(?int $sinceJobId = null): array
    {
        $matchers = $this->matchers();
        $jobs = 0;
        $links = 0;
        Job::active()->when($sinceJobId, fn ($q) => $q->where('id', '>', $sinceJobId))->select(['id', 'title', 'requirements'])
            ->lazyById(500)->chunk(500)->each(function ($chunk) use ($matchers, &$jobs, &$links) {
                $rows = [];
                foreach ($chunk as $job) {
                    $jobs++;
                    $requirements = is_array($job->requirements) ? implode(' , ', array_map(fn ($r) => is_array($r) ? implode(' ', $r) : (string) $r, $job->requirements)) : (string) $job->requirements;
                    foreach ($this->match($matchers, $job->title . ' , ' . mb_substr($requirements, 0, 4000)) as $technologyId) {
                        $rows[] = ['job_id' => $job->id, 'technology_id' => $technologyId];
                    }
                }
                foreach (array_chunk($rows, 1000) as $batch) $links += DB::table('job_technology')->insertOrIgnore($batch);
            });
        // Counts use active jobs only, so expired openings drop out of the directory.
        $counts = DB::table('job_technology')->join('jobs', 'jobs.id', '=', 'job_technology.job_id')
            ->where('jobs.is_active', true)->where('jobs.status', 'published')
            ->selectRaw('technology_id, COUNT(*) as n')->groupBy('technology_id')->pluck('n', 'technology_id');
        Technology::query()->update(['jobs_count' => 0]);
        foreach ($counts as $id => $n) Technology::whereKey($id)->update(['jobs_count' => $n]);
        return ['jobs' => $jobs, 'links' => $links, 'technologies' => $counts->count()];
    }

    /** Technology ids mentioned in a text. */
    public function match(array $matchers, string $text): array
    {
        $found = [];
        foreach ($matchers['insensitive'] as $pattern) {
            if (preg_match_all($pattern, mb_strtolower($text), $m)) foreach ($m[0] as $alias) $found[$matchers['ids'][mb_strtolower($alias)] ?? 0] = true;
        }
        // Ambiguous names: cheap substring test first, then a per-name context check.
        foreach ($matchers['sensitive'] as $alias => $pattern) {
            if (str_contains($text, $alias) && preg_match($pattern, $text)) $found[$matchers['exact'][$alias]] = true;
        }
        unset($found[0]);
        return array_keys($found);
    }

    public function matchers(): array
    {
        $ids = [];
        $exact = [];
        foreach (Technology::query()->get(['id', 'name', 'aliases']) as $technology) {
            foreach (array_merge([$technology->name], $technology->aliases ?? []) as $alias) {
                $alias = trim((string) $alias);
                $lower = mb_strtolower($alias);
                if ($alias === '' || in_array($lower, self::SKIP, true)) continue;
                if (in_array($lower, self::CASE_SENSITIVE, true) || mb_strlen($alias) <= 2) $exact[$alias] ??= $technology->id;
                else $ids[$lower] ??= $technology->id;
            }
        }
        return ['ids' => $ids, 'exact' => $exact, 'insensitive' => $this->patterns(array_keys($ids)), 'sensitive' => array_combine(array_keys($exact), array_map(fn ($alias) => '/(?<=^|[\s,\/(|;])' . preg_quote($alias, '/')
            . '(?=$|\s*[,\/)|;]|\s+(?i:developers?|engineers?|programmers?|programming|language|lang|backend|stack))/u', array_keys($exact)))];
    }

    private function patterns(array $aliases): array
    {
        usort($aliases, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        return array_map(fn ($chunk) => '/(?<![\pL\pN+#.&])(?:' . implode('|', array_map(fn ($a) => preg_quote($a, '/'), $chunk)) . ')(?![\pL\pN+#&])/u',
            array_chunk($aliases, 250));
    }

    /** Removes links of ambiguous technologies so the stricter matcher can rebuild them. */
    public function resetAmbiguousLinks(): int
    {
        $ids = Technology::query()->get(['id', 'name'])->filter(fn ($t) => in_array(mb_strtolower($t->name), self::CASE_SENSITIVE, true) || mb_strlen($t->name) <= 2)->pluck('id');
        return DB::table('job_technology')->whereIn('technology_id', $ids)->delete();
    }

    private function upsert(string $name, string $category, array $aliases, ?string $qid, int $popularity): string
    {
        $slug = Str::slug(str_replace(['++', '#', '.'], ['pp', 'sharp', ' dot '], $name)) ?: Str::slug($name);
        $technology = ($qid ? Technology::where('wikidata_id', $qid)->first() : null)
            ?? Technology::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? Technology::where('slug', $slug)->first();
        $isNew = !$technology;
        $technology ??= new Technology(['name' => $name, 'slug' => $slug, 'category' => $category]);
        $technology->aliases = array_values(array_unique(array_merge($technology->aliases ?? [], array_filter($aliases, fn ($a) => mb_strtolower($a) !== mb_strtolower($name)))));
        if ($qid) $technology->wikidata_id ??= $qid;
        $technology->popularity = max((int) $technology->popularity, $popularity);
        $technology->save();
        return $isNew ? 'created' : 'updated';
    }

    /** Wikidata labels like "Go (programming language)" or "Q12345" -> clean display names or null. */
    private function clean(string $label): ?string
    {
        $label = trim(preg_replace('/\s*\((programming language|software|framework|library|database)\)$/i', '', trim($label)));
        if ($label === '' || preg_match('/^Q\d+$/', $label) || mb_strlen($label) > 40 || !preg_match('/^[\pL\pN][\pL\pN +#.\/\-_]*$/u', $label)) return null;
        return $label;
    }
}
