<?php

namespace App\Services\Property;

use App\Models\Property\PropertyCity;
use App\Models\Property\PropertyLocality;
use App\Models\Property\PropertyOption;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

// Database-backed option lists for the property module (one table per list), cached in Redis and merged into config at boot.
class PropertyOptions
{
    public function groups(): array
    {
        return config('property_options.groups');
    }

    /** Creates any missing group tables and seeds empty ones from config/properties.php or the registry defaults. */
    public function sync(): array
    {
        $report = [];
        foreach ($this->groups() as $group => $def) {
            $table = PropertyOption::tableFor($group);
            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $t) {
                    $t->id();
                    $t->string('key', 60)->unique();
                    $t->string('label', 160);
                    $t->json('extra')->nullable();
                    $t->unsignedSmallInteger('sort')->default(0);
                    $t->boolean('is_active')->default(true);
                    $t->timestamps();
                });
            }
            if (PropertyOption::in($group)->exists()) { $report[$group] = 'kept'; continue; }
            $seed = config('properties.' . $group) ?? ($def[2] ?? []);
            $i = 0;
            foreach ((array) $seed as $key => $value) {
                $label = is_array($value) ? (string) array_shift($value) : (string) $value;
                PropertyOption::in($group)->insert(['key' => (string) $key, 'label' => $label, 'extra' => is_array($value) && $value ? json_encode(array_values($value)) : null,
                    'sort' => $i++, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            $report[$group] = 'seeded ' . $i;
        }
        $this->forget();
        return $report;
    }

    /** Every group as [group => config-shaped array], plus cities and localities. Cached until an admin edit. */
    public function all(): array
    {
        return Cache::rememberForever(config('property_options.cache_key'), function () {
            $out = [];
            foreach ($this->groups() as $group => $def) {
                $table = PropertyOption::tableFor($group);
                if (!Schema::hasTable($table)) continue;
                $out[$group] = PropertyOption::in($group)->where('is_active', true)->orderBy('sort')->orderBy('id')->get()
                    ->mapWithKeys(fn ($o) => [$o->key => $o->value($def[1])])->all();
            }
            if (Schema::hasTable('property_cities')) {
                $out['cities'] = PropertyCity::where('is_active', true)->orderBy('sort')->orderBy('name')->get()->groupBy('country')->map(fn ($g) => $g->pluck('name')->all())->all();
                $out['localities'] = PropertyLocality::with('city:id,name')->where('is_active', true)->orderBy('name')->get()
                    ->groupBy(fn ($l) => $l->city->name)->map(fn ($g) => $g->pluck('name')->all())->all();
            }
            return $out;
        });
    }

    /** Writes the database values over config('properties.*'). Called once per request from the service provider. */
    public function apply(): void
    {
        foreach ($this->all() as $group => $value) {
            if ($value !== []) config(['properties.' . $group => $value]);
        }
    }

    public function forget(): void
    {
        Cache::forget(config('property_options.cache_key'));
        Cache::forget('properties:cities');
    }
}
