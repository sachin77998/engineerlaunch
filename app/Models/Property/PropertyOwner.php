<?php

namespace App\Models\Property;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Contact details of the person or firm posting listings (one per user). */
class PropertyOwner extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['whatsapp' => 'boolean', 'is_featured' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function typeLabel(): string
    {
        if ($this->owner_type === 'agent' && $this->is_featured) return 'Featured dealer';
        return config('properties.owner_types.' . $this->owner_type, 'Owner');
    }
}
