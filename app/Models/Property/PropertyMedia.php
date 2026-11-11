<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PropertyMedia extends Model
{
    protected $table = 'property_media';
    protected $guarded = ['id'];

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
