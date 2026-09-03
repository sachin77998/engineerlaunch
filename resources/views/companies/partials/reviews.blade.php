@php
    $reviewCount = (int) ($company->published_reviews_count ?? $companyReviews->count());
    $reviewAverage = $reviewCount ? (float) ($company->published_reviews_avg_rating ?? $companyReviews->avg('rating')) : 0;
    $viewer = auth()->user();
    $canReview = $viewer && !in_array($viewer->role, ['employer', 'admin'], true) && !in_array((int) $viewer->role_code, [0, 2], true);
    $reviewJob = $reviewJob ?? null;
@endphp

@once
    @push('styles')
        <style>
            .company-reviews{margin-top:26px;padding:28px}.review-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap}.review-score{display:flex;align-items:center;gap:10px}.review-score strong{font-size:30px}.review-stars,.review-star{color:#f59e0b}.review-layout{display:grid;grid-template-columns:minmax(0,1fr) 390px;gap:22px;margin-top:22px}.review-list{display:grid;gap:13px}.review-card{border:1px solid #dce6f4;border-radius:14px;padding:18px;background:#f8fbff}.review-card-head{display:flex;justify-content:space-between;gap:12px}.review-card h3{font-size:17px;margin:8px 0}.review-meta{font-size:12px;color:#64748b}.verified-review{display:inline-flex;padding:4px 8px;border-radius:999px;background:#dcfce7;color:#166534;font-size:11px;font-weight:750}.review-form{border:1px solid #cbdcf5;border-radius:15px;padding:20px;background:#eef6ff}.review-form label{display:block;font-weight:700;margin:12px 0 6px}.review-form input,.review-form select,.review-form textarea{width:100%}.review-form textarea{min-height:115px;resize:vertical}.review-empty{padding:25px;border:1px dashed #bdd0ea;border-radius:14px;color:#64748b}.review-alert{padding:12px 14px;border-radius:9px;background:#ecfdf3;color:#087443;margin-top:15px}@media(max-width:850px){.review-layout{grid-template-columns:1fr}}
        </style>
    @endpush
@endonce

<section class="panel company-reviews" id="company-reviews">
    <div class="review-heading">
        <div>
            <h2>Job seeker reviews of {{ $company->name }}</h2>
            <p class="meta">First-party reviews shared by people using Ascendia. We do not copy reviews from other job portals.</p>
        </div>
        <div class="review-score" aria-label="{{ number_format($reviewAverage, 1) }} out of 5 from {{ $reviewCount }} reviews">
            <span class="review-stars">★★★★★</span>
            <strong>{{ $reviewCount ? number_format($reviewAverage, 1) : 'New' }}</strong>
            <span>{{ number_format($reviewCount) }} {{ Str::plural('review', $reviewCount) }}</span>
        </div>
    </div>

    @if(session('review_success'))<div class="review-alert">{{ session('review_success') }}</div>@endif

    <div class="review-layout">
        <div class="review-list">
            @forelse($companyReviews as $review)
                <article class="review-card">
                    <div class="review-card-head">
                        <div><strong>{{ $review->reviewer?->name ?: 'Ascendia job seeker' }}</strong><div class="review-meta">{{ str_replace('_', ' ', ucfirst($review->relationship ?: 'job seeker')) }} · {{ $review->created_at->diffForHumans() }}</div></div>
                        <div class="review-star">★ {{ number_format($review->rating, 1) }}/5</div>
                    </div>
                    @if($review->is_verified_application)<span class="verified-review">Verified application</span>@endif
                    @if($review->title)<h3>{{ $review->title }}</h3>@endif
                    <p>{{ $review->review }}</p>
                    @if($review->pros)<p><strong>What worked well:</strong> {{ $review->pros }}</p>@endif
                    @if($review->cons)<p><strong>What could improve:</strong> {{ $review->cons }}</p>@endif
                </article>
            @empty
                <div class="review-empty">No reviews have been published yet. Be the first job seeker to share a genuine experience.</div>
            @endforelse
        </div>

        <div>
            @if($canReview)
                <form class="review-form" method="POST" action="{{ route('companies.reviews.store', $company) }}">
                    @csrf
                    @if($reviewJob)<input type="hidden" name="job_id" value="{{ $reviewJob->id }}">@endif
                    <h3>{{ $myCompanyReview ? 'Update your review' : 'Share your experience' }}</h3>
                    <p class="meta">Please write only about your own application, interview, or employment experience.</p>
                    <label for="review-rating">Rating *</label>
                    <select id="review-rating" name="rating" required>
                        @foreach([5=>'Excellent',4=>'Good',3=>'Average',2=>'Needs improvement',1=>'Poor'] as $rating=>$label)
                            <option value="{{ $rating }}" @selected((int)old('rating',$myCompanyReview?->rating)===$rating)>{{ $rating }} — {{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="review-relationship">Your relationship</label>
                    <select id="review-relationship" name="relationship">
                        @foreach(['job_applicant'=>'Job applicant','interview_candidate'=>'Interview candidate','current_employee'=>'Current employee','former_employee'=>'Former employee','other'=>'Other'] as $value=>$label)
                            <option value="{{ $value }}" @selected(old('relationship',$myCompanyReview?->relationship)===$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="review-title">Review title</label>
                    <input id="review-title" name="title" maxlength="120" value="{{ old('title',$myCompanyReview?->title) }}" placeholder="Example: Clear interview process">
                    <label for="review-body">Your review *</label>
                    <textarea id="review-body" name="review" minlength="20" maxlength="2000" required placeholder="Describe your genuine experience (minimum 20 characters)">{{ old('review',$myCompanyReview?->review) }}</textarea>
                    @error('rating')<div class="validation-errors">{{ $message }}</div>@enderror
                    @error('review')<div class="validation-errors">{{ $message }}</div>@enderror
                    <button class="primary" type="submit">{{ $myCompanyReview ? 'Update review' : 'Publish review' }}</button>
                </form>
            @elseif(!$viewer)
                <div class="review-form"><h3>Have experience with this company?</h3><p>Sign in as a student or job seeker to write a review.</p><a class="primary" href="{{ route('login') }}">Sign in to review</a></div>
            @endif
        </div>
    </div>
</section>
