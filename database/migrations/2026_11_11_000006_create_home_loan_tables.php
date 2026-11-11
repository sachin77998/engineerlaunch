<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Home loans: lenders the owner keeps up to date, applications (income → offers → property → documents) and their private documents.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_loan_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('kind', 10)->default('bank'); // bank | nbfc
            $table->decimal('rate_from', 5, 2);
            $table->string('processing_fee', 191)->nullable();
            $table->unsignedTinyInteger('max_tenure')->default(30);
            $table->unsignedTinyInteger('max_funding')->default(80); // % of property value
            $table->string('color', 9)->default('#4338ca');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'rate_from']);
        });

        Schema::create('home_loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('email', 191)->nullable();
            $table->string('city', 80);
            $table->unsignedBigInteger('loan_amount')->nullable();
            $table->boolean('property_finalized')->default(false);
            $table->string('employment', 15)->nullable(); // salaried | self_employed
            $table->unsignedBigInteger('monthly_income')->nullable();
            $table->unsignedBigInteger('current_emis')->default(0);
            $table->unsignedTinyInteger('tenure')->default(20);
            $table->foreignId('bank_id')->nullable()->constrained('home_loan_banks')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('property_type', 30)->nullable();
            $table->string('property_stage', 30)->nullable();
            $table->string('property_city', 80)->nullable();
            $table->string('property_address', 255)->nullable();
            $table->unsignedBigInteger('property_value')->nullable();
            $table->json('steps_done')->nullable();
            $table->string('status', 20)->default('new'); // new | submitted | contacted | sanctioned | rejected | closed
            $table->text('admin_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('home_loan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('path', 255);
            $table->string('original_name', 191);
            $table->string('mime', 80);
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        $now = now();
        DB::table('home_loan_banks')->insert(collect([
            ['Indian Bank', 'bank', 7.35, 'No processing fee; legal & technical charges extra', 30, 90, '#1d4ed8'],
            ['Bajaj Housing Finance', 'nbfc', 7.35, 'Up to 0.3% of loan + GST', 30, 80, '#0369a1'],
            ['Bank of Baroda', 'bank', 7.40, 'No processing fee; legal & technical charges extra', 30, 90, '#ea580c'],
            ['HDFC Bank', 'bank', 7.45, 'Fixed fee + GST; legal charges extra', 30, 90, '#1e3a8a'],
            ['State Bank of India', 'bank', 7.50, '0.35% of loan, capped, + GST', 30, 90, '#0e7490'],
            ['Godrej Housing Finance', 'nbfc', 7.90, null, 30, 80, '#be123c'],
            ['ICICI Bank', 'bank', 7.75, '0.5% of loan + GST', 30, 90, '#b45309'],
            ['Axis Bank', 'bank', 8.35, 'Up to 1% of loan + GST', 30, 90, '#9f1239'],
            ['Punjab National Bank', 'bank', 7.45, '0.35% of loan, capped, + GST', 30, 90, '#a16207'],
            ['LIC Housing Finance', 'nbfc', 7.50, 'Fixed fee + GST', 30, 90, '#1e40af'],
        ])->map(fn ($b, $i) => ['name' => $b[0], 'kind' => $b[1], 'rate_from' => $b[2], 'processing_fee' => $b[3], 'max_tenure' => $b[4], 'max_funding' => $b[5], 'color' => $b[6], 'sort' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('home_loan_documents');
        Schema::dropIfExists('home_loan_applications');
        Schema::dropIfExists('home_loan_banks');
    }
};
