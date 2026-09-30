<?php

namespace App\Services;

use App\Models\Job;
use App\Support\DiscoveryCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Offline geography matching. Unspecified locations are never expanded to every country. */
class JobGeography
{
    private GeographyCatalog $catalog;
    public function __construct(GeographyCatalog $catalog)
    {
        $this->catalog = $catalog;
    }
    public function key(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($text))));
    }
    public function country(string $value): ?string
    {
        foreach ($this->catalog->table('countries') as $row) if ($row['code'] === strtoupper($value)) return $row['code'];
        return $this->catalog->places([$this->key($value)], 'country')[0]['country'] ?? null;
    }
    public function options(array $filters): array
    {
        $country = $this->country($filters['country'] ?? '');
        $state = $this->state($filters['state'] ?? '', $country);
        $countries = []; $regions = []; $states = [];
        foreach ($this->catalog->table('countries') as $row) {
            $regions[$row['region']] = ['value'=>$row['region'], 'label'=>$row['region']];
            if (empty($filters['region']) || $row['region'] === $filters['region'])
                $countries[] = ['value'=>$row['code'], 'label'=>$row['name'], 'region'=>$row['region']];
        }
        if ($country) foreach ($this->catalog->table('states') as $row)
            if ($row['country'] === $country) $states[] = ['value'=>$row['id'], 'label'=>$row['name']];
        usort($countries, fn($a,$b)=>strcmp($a['label'],$b['label']));
        usort($states, fn($a,$b)=>strcmp($a['label'],$b['label']));
        ksort($regions, SORT_STRING);
        $cities = $country ? $this->catalog->cities($country, $state, $this->key($filters['q'] ?? '')) : [];
        return ['regions'=>array_values($regions), 'countries'=>$countries, 'states'=>$states, 'cities'=>$cities];
    }
    private function state(string $value, ?string $country): ?string
    {
        if ($value === '') return null;
        foreach ($this->catalog->table('states') as $row) {
            if ($country && $row['country'] !== $country) continue;
            if ($row['id'] === $value || strtolower($row['name']) === strtolower($value) || $row['code'] === strtoupper($value)) return $row['id'];
        }
        foreach ($this->catalog->places([$this->key($value)], 'state') as $row)
            if (!$country || $row['country'] === $country) return $row['state'];
        return null;
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
        $matches = $this->catalog->places(array_keys($terms));
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
                foreach ($this->catalog->statesForCode($code, $token) as $s) $states[]=$s;
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
        $parameters = array_intersect_key($filters, array_flip(['region','country','state','city','location']));
        $ids = Cache::remember(DiscoveryCache::key('job-geography.matches.v3', $parameters), DiscoveryCache::ttl(), function () use ($filters) {
            $country = $this->country($filters['country'] ?? '');
            $state = $this->state($filters['state'] ?? '', $country);
            if ((!empty($filters['country']) && !$country) || (!empty($filters['state']) && !$state)) { return []; }
            $regions=array_column($this->catalog->table('countries'),'region','code');
            $cityKey=$this->key($filters['city'] ?? '');
            $cityNames=[$cityKey];
            if ($cityKey) foreach ($this->catalog->places([$cityKey], 'city') as $c) $cityNames[]=$this->key($c['city']);
            $term=$this->key($filters['location'] ?? '');
            $termPlaces=$term ? $this->resolve($filters['location']) : [];
            $ids=[];
            $resolved = [];
            foreach (Job::active()->select('id','location','country')->toBase()->lazyById(500) as $job) {
                $locationKey = $job->location.'|'.$job->country;
                if (!isset($resolved[$locationKey])) {
                    if (count($resolved) >= 4096) array_shift($resolved);
                    $resolved[$locationKey] = $this->resolve((string)$job->location, (string)$job->country);
                }
                $row = ['id'=>$job->id, 'text'=>$this->key((string)$job->location), 'places'=>$resolved[$locationKey]];

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
            return $ids;
        });
        $query->whereIntegerInRaw('jobs.id',$ids);
    }
}