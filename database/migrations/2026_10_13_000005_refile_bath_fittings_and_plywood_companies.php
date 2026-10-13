<?php

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Services\SectorCatalogImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Jaquar was filed under electrical switches and Greenply under "Other Industries"; move them to their real sub-categories.
return new class extends Migration
{
    private const MOVES = [
        'Jaquar & Company' => ['Cement, Building Materials & Polymers', 'Sanitaryware & Bath Fittings'],
        'Greenply' => ['Cement, Building Materials & Polymers', 'Wood, Plywood & Furniture'],
        'Greenply Industries' => ['Cement, Building Materials & Polymers', 'Wood, Plywood & Furniture'],
    ];

    public function up(): void
    {
        $importer = app(SectorCatalogImporter::class);
        foreach (self::MOVES as $name => [$sector, $subsector]) {
            foreach (Company::where('name', $name)->get() as $company) {
                $old = CompanyCategory::where('taxonomy', 'sector')->whereIn('id', $company->categories()->pluck('company_categories.id'))->pluck('id');
                if ($old->isNotEmpty()) DB::table('company_category_company')->where('company_id', $company->id)->whereIn('company_category_id', $old)->delete();
                $company->forceFill(['industry' => $sector, 'sector' => $subsector])->save();
                $importer->attach($company, $sector, $subsector);
            }
        }
    }

    public function down(): void {}
};
