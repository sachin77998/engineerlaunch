<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// US-listed companies outside India got Indian register entries by name search (Oracle Corporation was given an
// "ORACLE …" company in Himatnagar: its address, AGM dates, directors and charges). Those entries and everything read
// under that CIN are removed; the SEC statements are kept. Indian ADR issuers (listing country India) are untouched.
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('us_listed_companies as l')->join('companies as c', 'c.id', '=', 'l.company_id')
            ->where(fn ($q) => $q->whereNull('l.country')->orWhere('l.country', '!=', 'India'))
            ->where(fn ($q) => $q->whereNull('c.country')->orWhereNotIn('c.country', ['India', 'IN']))
            ->leftJoin('company_registry_details as r', 'r.company_id', '=', 'c.id')
            ->where(fn ($q) => $q->whereNotNull('c.registry_cin')->orWhereNotNull('r.cin'))
            ->distinct()->pluck('c.id');
        if ($ids->isEmpty()) return;
        foreach ($ids->chunk(500) as $chunk) {
            // Headquarters copied from the wrong register entry (its city) goes too.
            DB::table('companies as c')->join('company_registry_details as r', 'r.company_id', '=', 'c.id')->whereIn('c.id', $chunk)
                ->whereColumn('c.headquarters', 'r.city')->update(['c.headquarters' => null]);
            foreach (['company_officers', 'company_filings', 'company_shareholdings', 'company_related_parties', 'company_charges', 'company_auditors'] as $table) {
                DB::table($table)->whereIn('company_id', $chunk)->delete();
            }
            DB::table('company_financial_statements')->whereIn('company_id', $chunk)->where(fn ($q) => $q->whereNull('source')->orWhere('source', 'not like', 'SEC %'))->delete();
            DB::table('company_registry_details')->whereIn('company_id', $chunk)->delete();
            DB::table('companies')->whereIn('id', $chunk)->update(['registry_cin' => null, 'registry_status' => null, 'registered_state' => null, 'registry_source_url' => null, 'updated_at' => now()]);
            DB::table('company_credit_assessments')->whereIn('company_id', $chunk)->update(['computed_at' => null]);
        }
    }

    public function down(): void {}
};
