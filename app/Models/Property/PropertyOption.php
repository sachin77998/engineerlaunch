<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

// One row of a database-backed option list. Every list has its own table; use PropertyOption::in('amenities').
class PropertyOption extends Model
{
    protected $guarded = [];

    protected $casts = ['extra' => 'array', 'is_active' => 'boolean'];

    public static function tableFor(string $group): string
    {
        return 'popt_' . str_replace(['.', '-'], '_', $group);
    }

    /** Query builder bound to one group's table. */
    public static function in(string $group)
    {
        return (new static)->setTable(static::tableFor($group))->newQuery();
    }

    /** Value as it appears in config: a label, or [label, ...extra]. */
    public function value(string $shape)
    {
        return $shape === 'list' ? array_merge([$this->label], array_values($this->extra ?? [])) : $this->label;
    }
}
