<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Verified" listings: checked by the site owner's team (badge on the photo, results filter).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $t) {
            $t->boolean('is_verified')->default(false)->after('status');
            $t->timestamp('verified_at')->nullable()->after('is_verified');
            $t->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $t) {
            $t->dropIndex(['is_verified']);
            $t->dropColumn(['is_verified', 'verified_at']);
        });
    }
};
