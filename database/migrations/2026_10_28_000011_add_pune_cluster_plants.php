<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Pune automotive plants named in the "Pune – India's next Detroit" cluster overview that the registry and OpenStreetMap
// scans did not record: Harman's Chakan electronics plant and Force Motors' Chakan unit.
return new class extends Migration
{
    private const PLANTS = [
        ['company' => 'Harman International (India)%', 'industry' => 'Auto Components, Forging & Casting', 'products' => 'Connected car electronics, infotainment and audio systems', 'source' => 'https://www.harman.com'],
        ['company' => 'Force Motors%', 'industry' => 'Automobile Manufacturers', 'products' => 'Commercial and utility vehicles; engines and axles for OEM partners', 'source' => 'https://www.forcemotors.com'],
    ];

    public function up(): void
    {
        foreach (self::PLANTS as $p) {
            $company = DB::table('companies')->where('name', 'like', $p['company'])->orderBy('id')->first(['id', 'name']);
            if (!$company) continue;
            $slug = Str::limit(Str::slug($company->name . ' pune chakan midc'), 240, '');
            DB::table('company_facilities')->insertOrIgnore([
                'company_id' => $company->id, 'slug' => $slug, 'name' => Str::limit($company->name . ' - Chakan MIDC', 250, ''), 'facility_type' => 'Plant / Unit',
                'country' => 'India', 'state' => 'Maharashtra', 'city' => 'Pune', 'industrial_area' => 'Chakan MIDC', 'industry' => $p['industry'], 'products' => $p['products'],
                'source_url' => $p['source'], 'verified_on' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('company_facilities')->where('industrial_area', 'Chakan MIDC')->where(fn ($q) => $q->where('slug', 'like', 'harman-international-india%')->orWhere('slug', 'like', 'force-motors%'))->delete();
    }
};
