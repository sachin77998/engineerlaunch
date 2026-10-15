<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VendorObservation extends Model {
 protected $guarded=['id'];
 protected $casts=['issued_on'=>'date','due_on'=>'date','completed_on'=>'date','amount'=>'decimal:2'];
}
