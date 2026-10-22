<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringEmailTemplate extends Model
{
    protected $fillable = ['owner_id', 'name', 'subject', 'body'];
}
