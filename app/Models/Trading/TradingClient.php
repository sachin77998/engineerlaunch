<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A trading client: onboarding, KYC and bank details. PAN and bank account are encrypted. */
class TradingClient extends Model
{
    use SoftDeletes;

    protected $table = 'trading_clients';
    protected $guarded = ['id'];
    protected $casts = ['pan' => 'encrypted', 'bank_account' => 'encrypted', 'guardian_pan' => 'encrypted', 'dob' => 'date', 'guardian_dob' => 'date', 'email_verified_at' => 'datetime', 'mobile_verified_at' => 'datetime', 'pan_verified_at' => 'datetime', 'aadhaar_verified_at' => 'datetime', 'address_verified_at' => 'datetime', 'bank_verified_at' => 'datetime', 'verified_at' => 'datetime', 'consent_at' => 'datetime', 'nomination_opt_out_at' => 'datetime', 'correspondence_address' => 'array', 'risk_answers' => 'array', 'is_pep' => 'boolean', 'tax_resident_elsewhere' => 'boolean', 'is_minor' => 'boolean', 'correspondence_same' => 'boolean', 'nomination_opted' => 'boolean', 'pan_aadhaar_linked' => 'boolean'];
    protected $hidden = ['pan', 'pan_hash', 'aadhaar_hash', 'bank_account', 'bank_account_hash', 'guardian_pan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(TradingClientNominee::class)->orderBy('sequence');
    }

    public function jointHolders(): HasMany
    {
        return $this->hasMany(TradingJointHolder::class)->orderBy('sequence');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TradingClientDocument::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(TradingKycVerification::class)->latest('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MfOrder::class);
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(MfHolding::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /** PAN shown as ABCDE****F. */
    public function maskedPan(): ?string
    {
        return $this->pan ? substr($this->pan, 0, 5) . '****' . substr($this->pan, -1) : null;
    }

    /** Keyed hash for duplicate checks without storing the value in clear. */
    public static function hashOf(string $value): string
    {
        return hash_hmac('sha256', strtoupper(preg_replace('/\s+/', '', $value)), (string) config('app.key'));
    }
}
