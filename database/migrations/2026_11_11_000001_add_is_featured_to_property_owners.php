<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Featured dealer": an agent the site owner marks as featured (results filter and badge).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_owners', function (Blueprint $t) {
            $t->boolean('is_featured')->default(false)->after('owner_type');
            $t->index(['owner_type', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::table('property_owners', function (Blueprint $t) {
            $t->dropIndex(['owner_type', 'is_featured']);
            $t->dropColumn('is_featured');
        });
    }
};
