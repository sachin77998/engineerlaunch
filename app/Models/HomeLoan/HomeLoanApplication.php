<?php

namespace App\Models\HomeLoan;

use App\Models\Property\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HomeLoanApplication extends Model
{
    protected $guarded = [];

    protected $casts = ['steps_done' => 'array', 'property_finalized' => 'boolean', 'submitted_at' => 'datetime'];

    public const STEPS = ['income' => 'Income details', 'offers' => 'Bank offers', 'property' => 'Property details', 'documents' => 'Upload documents'];

    public function user() { return $this->belongsTo(User::class); }
    public function bank() { return $this->belongsTo(HomeLoanBank::class, 'bank_id'); }
    public function property() { return $this->belongsTo(Property::class); }
    public function documents() { return $this->hasMany(HomeLoanDocument::class); }

    public function markDone(string $step): void
    {
        $this->steps_done = array_values(array_unique([...($this->steps_done ?? []), $step]));
    }

    public function isDone(string $step): bool
    {
        return in_array($step, $this->steps_done ?? [], true);
    }

    public function statusLabel(): string
    {
        return config('home_loans.statuses')[$this->status] ?? ucfirst($this->status);
    }

    /** Short reference shown to the applicant and the owner. */
    public function reference(): string
    {
        return 'HL' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
