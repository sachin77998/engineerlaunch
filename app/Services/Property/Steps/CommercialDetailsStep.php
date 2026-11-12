<?php

namespace App\Services\Property\Steps;

use App\Contracts\PropertyStep;
use App\Models\Property\Property;
use App\Models\Property\PropertyCommercialDetail;
use App\Models\Property\PropertyFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Offices, shops and other commercial buildings: office type, building, seats, cabins, pre-lease, facilities and RERA.
class CommercialDetailsStep implements PropertyStep
{
    public function key(): string { return 'commercial'; }

    public function label(): string { return 'Office & building'; }

    public function icon(): string { return '🏢'; }

    public function after(): string { return 'details'; }

    public function applies(Property $p): bool
    {
        return $p->segment === 'commercial' && !$p->isLand();
    }

    public function view(): string { return 'properties.post.steps.commercial'; }

    public function viewData(Property $p): array
    {
        return ['c' => $p->commercialDetail ?? new PropertyCommercialDetail(), 'ticked' => $p->facilities()->pluck('facility')->all()];
    }

    public function save(Request $request, Property $p): void
    {
        $o = config('properties');
        $in = fn ($group) => 'nullable|in:' . implode(',', array_keys($o[$group] ?? []));
        $data = $request->validate([
            'office_type' => $in('office_types'), 'situated_in' => $in('situated_in'), 'transaction_type' => $p->listing === 'sale' ? $in('transaction_types') : 'nullable|prohibited',
            'seats_min' => 'nullable|integer|min:1|max:100000', 'seats_max' => 'nullable|integer|gte:seats_min|max:100000',
            'cabins' => 'nullable|integer|min:0|max:5000', 'meeting_rooms' => 'nullable|integer|min:0|max:5000',
            'washrooms' => 'nullable|string|max:120', 'car_parking' => 'nullable|string|max:160',
            'is_pre_leased' => 'nullable|boolean',
            'lease_amount' => 'nullable|required_if:is_pre_leased,1|numeric|min:1|max:10000000000', 'lease_tenure_years' => 'nullable|numeric|min:0.5|max:99',
            'tenant_name' => 'nullable|string|max:160',
            'rera_status' => 'nullable|in:registered,not_available,not_required', 'rera_number' => 'nullable|required_if:rera_status,registered|string|max:80',
            'facilities' => 'nullable|array', 'facilities.*' => 'in:' . implode(',', array_keys($o['facilities'] ?? [])),
        ], ['lease_amount.required_if' => 'Enter the monthly lease amount of the pre-leased unit.', 'rera_number.required_if' => 'Enter the RERA registration number.']);
        $preLeased = $p->listing === 'sale' && $request->boolean('is_pre_leased');
        DB::transaction(function () use ($p, $data, $preLeased) {
            PropertyCommercialDetail::updateOrCreate(['property_id' => $p->id], array_merge(collect($data)->except('facilities')->all(), [
                'is_pre_leased' => $preLeased,
                'lease_amount' => $preLeased ? $data['lease_amount'] ?? null : null, 'lease_tenure_years' => $preLeased ? $data['lease_tenure_years'] ?? null : null,
                'tenant_name' => $preLeased ? $data['tenant_name'] ?? null : null,
                'rera_number' => ($data['rera_status'] ?? null) === 'registered' ? $data['rera_number'] ?? null : null,
            ]));
            PropertyFacility::where('property_id', $p->id)->delete();
            foreach (array_unique($data['facilities'] ?? []) as $f) PropertyFacility::create(['property_id' => $p->id, 'facility' => $f]);
        });
        $p->unsetRelation('commercialDetail');
    }
}
