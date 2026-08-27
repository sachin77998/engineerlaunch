<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyCategory extends Model
{
    protected $fillable = ['parent_id','name','slug','taxonomy','symbol','description','sort_order','is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order'); }
    public function companies(): BelongsToMany { return $this->belongsToMany(Company::class, 'company_category_company')->withTimestamps(); }
}
