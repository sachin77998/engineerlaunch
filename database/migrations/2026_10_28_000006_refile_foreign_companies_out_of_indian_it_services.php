<?php

use App\Services\Discovery\CompanyClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Registry and Wikidata imports filed every IT company under "Indian IT Services", whatever its country (4,484 of 4,858 were
// foreign). They move to Software Products & SaaS, Cybersecurity, European IT Services or Global IT Services & Consulting.
return new class extends Migration
{
    public function up(): void
    {
        $parent = DB::table('company_categories')->where('name', 'IT Services & Software')->whereNull('parent_id')->value('id');
        if (!$parent) return;
        $ids = DB::table('company_categories')->where('parent_id', $parent)->pluck('id', 'name');
        $from = $ids['Indian IT Services'] ?? null;
        if (!$from) return;
        $classifier = app(CompanyClassifier::class);
        DB::table('company_category_company as p')->join('companies as c', 'c.id', '=', 'p.company_id')
            ->where('p.company_category_id', $from)->whereNotNull('c.country')->where('c.country', '!=', 'India')
            ->select('c.id', 'c.name', 'c.country', 'c.sector')
            ->chunkById(500, function ($rows) use ($classifier, $ids, $from) {
                foreach ($rows as $row) {
                    $to = $classifier->localise(['IT Services & Software', 'Indian IT Services'], $row->country, $row->name)[1];
                    if ($to === 'Indian IT Services' || !isset($ids[$to])) continue;
                    // The company may already be in the target category; then the old link is simply removed.
                    DB::table('company_category_company')->insertOrIgnore(['company_id' => $row->id, 'company_category_id' => $ids[$to], 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('company_category_company')->where('company_id', $row->id)->where('company_category_id', $from)->delete();
                    if ($row->sector === 'Indian IT Services') DB::table('companies')->where('id', $row->id)->update(['sector' => $to]);
                }
            }, 'c.id', 'id');
    }

    public function down(): void
    {
        // Data correction; not reversed.
    }
};
