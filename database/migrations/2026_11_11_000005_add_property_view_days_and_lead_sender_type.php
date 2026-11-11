<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Views per listing per day ("N people viewed this property yesterday") and who sent an enquiry (individual or dealer).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_view_days', function (Blueprint $t) {
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->date('day');
            $t->unsignedInteger('views')->default(0);
            $t->primary(['property_id', 'day']);
        });
        Schema::table('property_leads', fn (Blueprint $t) => $t->string('sender_type', 12)->nullable()->after('kind'));
    }

    public function down(): void
    {
        Schema::table('property_leads', fn (Blueprint $t) => $t->dropColumn('sender_type'));
        Schema::dropIfExists('property_view_days');
    }
};
