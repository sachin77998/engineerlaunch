<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Amazon's job feed was configured on the brand record "Amazon (Vedaka)", so 10,000+ Amazon jobs showed under the
// wrong company. Move them to "Amazon" and keep only that record syncing the amazon.jobs feed.
return new class extends Migration
{
    public function up(): void
    {
        $brand = DB::table('companies')->where('name', 'Amazon (Vedaka)')->value('id');
        $amazon = DB::table('companies')->where('name', 'Amazon')->value('id');
        if (!$brand || !$amazon) return;
        DB::table('jobs')->where('company_id', $brand)->update(['company_id' => $amazon]);
        DB::table('companies')->where('id', $brand)->update(['ats_provider' => null, 'ats_identifier' => null, 'jobs_feed_url' => null, 'sync_enabled' => false]);
        DB::table('companies')->where('id', $amazon)->update(['ats_provider' => 'amazon', 'sync_enabled' => true]);
    }

    public function down(): void {}
};
