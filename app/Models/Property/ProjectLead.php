<?php

namespace App\Models\Property;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** An enquiry on a project: number viewed, callback, brochure download or a message to the builder. */
class ProjectLead extends Model
{
    public const KINDS = ['phone' => 'Viewed number', 'callback' => 'Callback request', 'brochure' => 'Brochure download', 'contact' => 'Message', 'visit' => 'Site visit'];

    protected $guarded = ['id'];

    public function project() { return $this->belongsTo(Project::class); }

    public function user() { return $this->belongsTo(User::class); }

    public function unit() { return $this->belongsTo(ProjectUnit::class, 'unit_id'); }
}
