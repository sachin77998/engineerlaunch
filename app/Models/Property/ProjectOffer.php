<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

/** An offer the builder runs on a project (entered by the builder or our team; shown until it expires). */
class ProjectOffer extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['valid_until' => 'date', 'is_active' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }
}
