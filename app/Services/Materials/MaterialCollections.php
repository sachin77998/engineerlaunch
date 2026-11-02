<?php

namespace App\Services\Materials;

use App\Models\Material;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Every category the catalogue has, built from the collections products are filed under, so a collection the store
 * adds shows up here after the next sync. Curated names and menu groups come from config('materials.categories');
 * other collections take a name from their handle and the menu group most of their products belong to.
 */
class MaterialCollections
{
    /** @return array<string, array{title:string, group:?string, icon:string, count:int, image:?string, curated:bool}> */
    public function all(): array
    {
        return Cache::remember('materials:collections', now()->addHour(), function () {
            $curated = config('materials.categories');
            $rows = Material::forSale()->orderByDesc('in_stock')->orderByDesc('quantity_available')->get(['category', 'collections', 'image_url']);
            $out = [];
            foreach ($rows as $m) {
                foreach ($m->collections ?? [] as $handle) {
                    if (!isset($curated[$handle]) && $this->hidden($handle)) continue;
                    $out[$handle] ??= ['count' => 0, 'image' => null, 'groups' => []];
                    $out[$handle]['count']++;
                    $out[$handle]['image'] ??= $m->image_url;
                    if ($m->category) $out[$handle]['groups'][] = $curated[$m->category][1] ?? null;
                }
            }
            $result = [];
            foreach ($out as $handle => $c) {
                if (!isset($curated[$handle]) && $c['count'] < 2) continue;
                $group = $curated[$handle][1] ?? collect($c['groups'])->filter()->countBy()->sortDesc()->keys()->first();
                $result[$handle] = ['title' => $curated[$handle][0] ?? $this->title($handle), 'group' => $group, 'icon' => $curated[$handle][2] ?? '🧩',
                    'count' => $c['count'], 'image' => $c['image'], 'curated' => isset($curated[$handle])];
            }
            // Curated first (in menu order), then the rest by size.
            $order = array_flip(array_keys($curated));
            uksort($result, fn ($a, $b) => [isset($order[$a]) ? 0 : 1, $order[$a] ?? 0, -$result[$a]['count']] <=> [isset($order[$b]) ? 0 : 1, $order[$b] ?? 0, -$result[$b]['count']]);
            return $result;
        });
    }

    public function find(string $handle): ?array
    {
        return $this->all()[$handle] ?? null;
    }

    /** @return array<string, array> group key => collections, for menus. */
    public function byGroup(): array
    {
        return collect($this->all())->groupBy(fn ($c) => $c['group'] ?? 'more', true)->map(fn ($g) => $g->all())->all();
    }

    private function hidden(string $handle): bool
    {
        if ($handle === 'all' || preg_match('/-\d+$/', $handle)) return true; // the whole catalogue, and store-side duplicates such as cement-1
        foreach (config('materials.hidden_collections') as $word) if (str_contains($handle, $word)) return true;
        return false;
    }

    private function title(string $handle): string
    {
        $title = Str::headline(str_replace(['-mdf-', '-cpvc-'], [' MDF ', ' CPVC '], $handle));
        return preg_replace_callback('/\b(Mdf|Hdhmr|Pvc|Cpvc|Mcb|Rccb|Led|Cctv|Frls|Fr|Ss|Pu|Upvc|Opc|Ppc|Wpc|Sbr|Pop|Db|Aac)\b/', fn ($m) => strtoupper($m[1]), $title);
    }
}
