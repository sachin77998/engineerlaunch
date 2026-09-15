<?php

namespace App\Services;

use App\Models\Job;
use App\Support\DiscoveryCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PDO;

/** Offline geography matching. Unspecified locations are never expanded to every country. */
class JobGeography
{
    private PDO $db;
    public function __construct()
    {
        $this->db = new PDO('sqlite:'.resource_path('data/job-geography/geography.sqlite'));
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->exec('PRAGMA query_only=ON');
    }
    public function key(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($text))));
    }
    private function rows(string $sql, array $args = []): array
    {
        $q = $this->db->prepare($sql); $q->execute($args); return $q->fetchAll();
    }
    public function country(string $value): ?string
    {
        $rows = $this->rows('SELECT code FROM countries WHERE code = ?', [strtoupper($value)]);
        return $rows[0]['code'] ?? ($this->rows("SELECT country FROM places WHERE term=? AND kind='country' LIMIT 1", [$this->key($value)])[0]['country'] ?? null);
    }
    public function options(array $filters): array
    {
        $country = $this->country($filters['country'] ?? '');
        $state = $this->state($filters['state'] ?? '', $country);
        $region = $filters['region'] ?? '';
        $countries = $this->rows('SELECT code AS value,name AS label,region FROM countries'.($region ? ' WHERE region=?' : '').' ORDER BY name', $region ? [$region] : []);
        $states = $country ? $this->rows('SELECT id AS value,name AS label FROM states WHERE country=? ORDER BY name', [$country]) : [];
        $cities = [];
        if ($country) {
            $sql = 'SELECT DISTINCT name AS value,name AS label FROM cities WHERE country=?'; $args = [$country];
            if ($state) { $sql .= ' AND state=?'; $args[] = $state; }
            if (!empty($filters['q'])) { $sql .= " AND (term LIKE ? OR name IN (SELECT city FROM places WHERE kind='city' AND term LIKE ?))"; $prefix = str_replace(['%','_'], '', $this->key($filters['q'])).'%'; $args[]=$prefix; $args[]=$prefix; }
            $cities = $this->rows($sql.' ORDER BY name LIMIT 100', $args);
        }
        return ['regions' => $this->rows('SELECT DISTINCT region AS value,region AS label FROM countries ORDER BY region'), 'countries' => $countries, 'states' => $states, 'cities' => $cities];
    }
    private function state(string $value, ?string $country): ?string
    {
        if ($value === '') return null;
        $rows = $this->rows('SELECT id FROM states WHERE (id=? OR lower(name)=? OR code=?)'.($country ? ' AND country=?' : '').' LIMIT 1', array_merge([$value, strtolower($value), strtoupper($value)], $country ? [$country] : []));
        if ($rows) return $rows[0]['id'];
        $rows = $this->rows("SELECT state FROM places WHERE term=? AND kind='state'".($country ? ' AND country=?' : '').' LIMIT 1', array_merge([$this->key($value)], $country ? [$country] : []));
        return $rows[0]['state'] ?? null;
    }
    public function resolve(string $location, string $fallback = ''): array
    {
        $terms = [];
        foreach (preg_split('/[,;|\/\n]+/', $location) as $part) {
            $words = explode(' ', $this->key($part));
            for ($i=0; $i<count($words); $i++) for ($n=1; $n<=min(6,count($words)-$i); $n++) {
                $term = implode(' ',array_slice($words,$i,$n)); if (strlen($term)>2 || in_array($term,['us','uk'])) $terms[$term]=true;
            }
        }
        if (!$terms) return [];
        $matches = $this->rows('SELECT DISTINCT * FROM places WHERE term IN ('.implode(',',array_fill(0,count($terms),'?')).')', array_keys($terms));
        // Prefer complete place names over shorter names inside them (New Delhi vs Delhi).
        $matches = array_values(array_filter($matches, function ($m) use ($matches) {
            foreach ($matches as $other) if ($m['kind']===$other['kind'] && $m['term']!==$other['term'] && str_contains(' '.$other['term'].' ', ' '.$m['term'].' ')) return false;
            return true;
        }));
        $countries = array_values(array_unique(array_column(array_filter($matches,fn($m)=>$m['kind']==='country'),'country')));
        $fallbackCode = $this->country($fallback);
        $states = array_values(array_filter($matches,fn($m)=>$m['kind']==='state' && (!$countries || in_array($m['country'],$countries))));
        // State abbreviations are meaningful only in an explicit country context.
        foreach ($countries ?: ($fallbackCode ? [$fallbackCode] : []) as $code) {
            foreach (preg_split('/[,;|\s]+/', $location) as $token) {
                if (!preg_match('/^[A-Z]{2,3}$/', $token)) continue;
                foreach ($this->rows('SELECT id AS state,country FROM states WHERE country=? AND code=?',[$code,$token]) as $s) $states[]=$s;
            }
        }
        $groups=[];
        foreach ($matches as $m) if ($m['kind']==='city' && (!$countries || in_array($m['country'],$countries))) $groups[$m['term']][]=$m;
        if (count($groups)>1) foreach ($states as $s) if (isset($s['term'])) unset($groups[$s['term']]);
        $result=[];
        foreach ($groups as $cities) {
            $inState=array_values(array_filter($cities,fn($c)=>collect($states)->contains(fn($s)=>$s['country']===$c['country'] && $s['state']===$c['state'])));
            if ($inState) $cities=$inState;
            elseif ($states) continue;
            elseif (!$countries && $fallbackCode) {
                $preferred=array_values(array_filter($cities,fn($c)=>$c['country']===$fallbackCode)); if ($preferred) $cities=$preferred;
            }
            usort($cities,fn($a,$b)=>(int)$b['population']<=>(int)$a['population']);
            $cities=array_values(collect($cities)->unique(fn($c)=>$c['country'].'/'.$c['state'].'/'.$c['city'])->all());
            if (count($cities)===1 || (int)$cities[0]['population']>=100000) {
                $c=$cities[0]; $result[]=['country'=>$c['country'],'state'=>$c['state'],'city'=>$this->key($c['city'])];
            }
        }
        if (!$countries) {
            $resolvedCountries=array_values(array_unique(array_column($result,'country')));
            if ($resolvedCountries) $states=array_values(array_filter($states,fn($s)=>in_array($s['country'],$resolvedCountries)));
            elseif ($fallbackCode) $states=array_values(array_filter($states,fn($s)=>$s['country']===$fallbackCode));
            elseif (count(array_unique(array_column($states,'country')))>1) $states=[];
        }
        foreach ($states as $s) if (!collect($result)->contains(fn($r)=>$r['country']===$s['country'] && $r['state']===$s['state'])) $result[]=['country'=>$s['country'],'state'=>$s['state'],'city'=>''];
        foreach ($countries ?: ($result ? [] : ($fallbackCode ? [$fallbackCode] : [])) as $c) if (!collect($result)->contains('country',$c)) $result[]=['country'=>$c,'state'=>'','city'=>''];
        return $result;
    }
    public function apply($query, array $filters): void
    {
        if (!collect($filters)->only(['region','country','state','city','location'])->filter(fn($v)=>$v!==null && $v!=='')->count()) return;
        $index = Cache::remember(DiscoveryCache::key('job-geography.v2'), DiscoveryCache::ttl(), function () {
            $rows=[]; $resolved=[];
            foreach (Job::active()->select('id','location','country')->get() as $job) {
                $key=$job->location.'|'.$job->country;
                $resolved[$key] ??= $this->resolve((string)$job->location,(string)$job->country);
                $rows[]=['id'=>$job->id,'text'=>$this->key((string)$job->location),'places'=>$resolved[$key]];
            }
            return $rows;
        });
        $country = $this->country($filters['country'] ?? '');
        $state = $this->state($filters['state'] ?? '', $country);
        if ((!empty($filters['country']) && !$country) || (!empty($filters['state']) && !$state)) { $query->whereRaw('1=0'); return; }
        $regions=array_column($this->rows('SELECT code,region FROM countries'),'region','code');
        $cityKey=$this->key($filters['city'] ?? '');
        $cityNames=[$cityKey];
        if ($cityKey) foreach ($this->rows("SELECT city FROM places WHERE term=? AND kind='city'",[$cityKey]) as $c) $cityNames[]=$this->key($c['city']);
        $term=$this->key($filters['location'] ?? '');
        $termPlaces=$term ? $this->resolve($filters['location']) : [];
        $ids=[];
        foreach ($index as $row) {
            $geographic = empty($filters['region']) && !$country && !$state && !$cityKey;
            foreach ($row['places'] as $p) {
                if (!empty($filters['region']) && ($regions[$p['country']]??'')!==$filters['region']) continue;
                if ($country && $p['country']!==$country) continue;
                if ($state && $p['state']!==$state) continue;
                if ($cityKey && !in_array($p['city'],$cityNames,true)) continue;
                $geographic=true; break;
            }
            if (!$geographic) continue;
            if ($term && !str_contains($row['text'],$term)) {
                $matched=false;
                foreach ($termPlaces as $t) foreach ($row['places'] as $p) if ($t['country']===$p['country'] && (!$t['state'] || $t['state']===$p['state']) && (!$t['city'] || $t['city']===$p['city'])) $matched=true;
                if (!$matched) continue;
            }
            $ids[]=(int)$row['id'];
        }
        $query->whereIntegerInRaw('jobs.id',$ids);
    }
}