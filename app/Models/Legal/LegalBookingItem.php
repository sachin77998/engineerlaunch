<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;

class LegalBookingItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function service() { return $this->belongsTo(LegalService::class, 'legal_service_id'); }
}
