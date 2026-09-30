<?php
namespace App\Services;

/** Portable geography lookups with bounded shard storage; no PDO driver required. */
class GeographyCatalog
{
    private array $shards = [];
    private array $tables = [];
    private array $terms = [];
    private ?array $stateCodes = null;
    private function read(string $file): array
    {
        return json_decode(file_get_contents(resource_path('data/job-geography/portable/'.$file.'.json')), true, 512, JSON_THROW_ON_ERROR);
    }
    public function table(string $name): array
    {
        return $this->tables[$name] ??= $this->read($name);
    }
    public function places(array $terms, ?string $kind = null): array
    {
        $result = [];
        foreach (array_unique($terms) as $term) {
            $termKey = 't:'.$term;
            if (!array_key_exists($termKey, $this->terms)) {
                $bucket = 'p'.substr(sha1($term), 0, 2);
                if (!isset($this->shards[$bucket])) {
                    if (count($this->shards) >= 16) array_shift($this->shards);
                    $this->shards[$bucket] = $this->read('places/'.substr($bucket, 1));
                }
                if (count($this->terms) >= 4096) array_shift($this->terms);
                $this->terms[$termKey] = $this->shards[$bucket][$term] ?? [];
            }
            foreach ($this->terms[$termKey] as $row) {
                if ($kind !== null && $row[3] !== $kind) continue;
                $result[] = array_combine(['term','country','state','city','kind','population'], array_merge([$term], $row));
            }
        }
        return $result;
    }
    public function statesForCode(string $country, string $code): array
    {
        if ($this->stateCodes === null) {
            $this->stateCodes = [];
            foreach ($this->table('states') as $row)
                $this->stateCodes[$row['country'].'/'.$row['code']][] = ['country'=>$row['country'], 'state'=>$row['id']];
        }
        return $this->stateCodes[$country.'/'.$code] ?? [];
    }

    public function cities(string $country, ?string $state, string $prefix): array
    {
        $result = [];
        if (!is_file(resource_path('data/job-geography/portable/cities/'.$country.'.json'))) return [];
        foreach ($this->read('cities/'.$country) as [$parent, $name, $term, $aliases]) {
            if ($state && $parent !== $state) continue;
            if ($prefix !== '' && !str_starts_with($term, $prefix)) {
                $matches = false;
                foreach ($aliases as $alias) if (str_starts_with($alias, $prefix)) { $matches = true; break; }
                if (!$matches) continue;
            }
            $result[$name] = ['value'=>$name, 'label'=>$name];
        }
        ksort($result, SORT_STRING);
        return array_slice(array_values($result), 0, 100);
    }
}
