<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

/** A visitor report on a listing (sold out, wrong price, fake…), reviewed on the admin page. */
class PropertyReport extends Model
{
    protected $guarded = ['id'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
