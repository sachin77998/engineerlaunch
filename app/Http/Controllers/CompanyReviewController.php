<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyReviewRequest;
use App\Models\Company;
use App\Support\DiscoveryCache;
use Illuminate\Validation\ValidationException;

class CompanyReviewController extends Controller
{
    public function store(StoreCompanyReviewRequest $request, Company $company)
    {
        $data = $request->validated();

        if (! empty($data['job_id']) && ! $company->jobs()->whereKey($data['job_id'])->exists()) {
            throw ValidationException::withMessages([
                'job_id' => 'The selected job does not belong to this company.',
            ]);
        }

        $verifiedApplication = $request->user()->applications()
            ->where('status', '!=', 'withdrawn')
            ->whereHas('job', fn ($query) => $query->where('company_id', $company->id))
            ->exists();

        $company->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [...$data, 'status' => 'published', 'is_verified_application' => $verifiedApplication]
        );

        DiscoveryCache::invalidate();

        return back()->with('review_success', 'Your company review has been saved.');
    }
}
