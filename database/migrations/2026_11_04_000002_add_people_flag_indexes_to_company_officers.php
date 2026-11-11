<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// People flags look officers up by DIN (the same person across companies) and by appointment / cessation date.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_officers', function (Blueprint $t) {
            $t->index('din');
            $t->index('appointed_on');
            $t->index('ceased_on');
        });
        Schema::table('company_registry_details', fn (Blueprint $t) => $t->index('incorporation_date'));
    }

    public function down(): void
    {
        Schema::table('company_officers', function (Blueprint $t) {
            $t->dropIndex(['din']);
            $t->dropIndex(['appointed_on']);
            $t->dropIndex(['ceased_on']);
        });
        Schema::table('company_registry_details', fn (Blueprint $t) => $t->dropIndex(['incorporation_date']));
    }
};
