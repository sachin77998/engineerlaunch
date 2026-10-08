<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Production was missing company_facilities (the career explorer migration skipped it), which broke /sectors.
// Created without a foreign key so a companies.id type difference on the server cannot block it again.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_facilities')) {
            Schema::create('company_facilities', function (Blueprint $t) {
                $t->id(); $t->unsignedBigInteger('company_id')->index(); $t->string('slug')->unique();
                $t->string('name'); $t->string('facility_type')->nullable(); $t->string('country')->default('India');
                $t->string('state')->nullable(); $t->string('city')->nullable(); $t->string('industrial_area')->nullable();
                $t->string('industry'); $t->text('products')->nullable(); $t->unsignedInteger('employee_min')->nullable();
                $t->unsignedInteger('employee_max')->nullable(); $t->string('employee_scope')->nullable();
                $t->text('source_url'); $t->date('verified_on'); $t->timestamps();
            });
        }
        $indexes = collect(Schema::getConnection()->select('SHOW INDEX FROM company_facilities'))->pluck('Key_name');
        Schema::table('company_facilities', function (Blueprint $t) use ($indexes) {
            if (!$indexes->contains('company_facilities_state_city_index')) $t->index(['state', 'city'], 'company_facilities_state_city_index');
            if (!$indexes->contains('company_facilities_area_index')) $t->index('industrial_area', 'company_facilities_area_index');
        });
    }

    public function down(): void
    {
        // Intentionally empty: the table may hold data created before this repair.
    }
};
