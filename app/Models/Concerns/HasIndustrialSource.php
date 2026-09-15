<?php

namespace App\Models\Concerns;

trait HasIndustrialSource
{
    public function getVerificationLabelAttribute(): string
    {
        return match ($this->verification_status) {
            'government_source' => 'Government / Official Source',
            'company_source' => 'Company Verified',
            'admin_verified' => 'Admin Verified',
            'community_verified' => 'Community Reported',
            default => $this->source_name === 'User-supplied starter master' ? 'Unverified starter record' : 'Unverified',
        };
    }
}
