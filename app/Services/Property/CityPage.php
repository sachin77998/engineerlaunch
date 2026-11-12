<?php

namespace App\Services\Property;

use App\Services\Property\CitySections\CitySection;
use Throwable;

// Builds the city page from the section registry. A failing section is reported and left out instead of breaking the page.
class CityPage
{
    /** @return array<string, array<int, array{view: string, data: array}>> column => rendered blocks */
    public function build(CityContext $c): array
    {
        $out = [];
        foreach (config('property_city.sections') as $column => $entries) {
            foreach ($entries as $i => $entry) {
                [$class, $options] = is_array($entry) ? [$entry[0], $entry[1] ?? []] : [$entry, []];
                try {
                    /** @var CitySection $section */
                    $section = app($class);
                    $data = $section->minutes > 0
                        ? PropertyCache::remember($c->slug(), $column . $i . ':' . class_basename($class) . ':' . $c->cacheKey(), now()->addMinutes($section->minutes), fn () => $section->data($c, $options) ?? false)
                        : $section->data($c, $options);
                    if ($data) $out[$column][] = ['view' => $options['view'] ?? $section->view(), 'data' => $data + ['options' => $options]];
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }
        return $out;
    }
}
