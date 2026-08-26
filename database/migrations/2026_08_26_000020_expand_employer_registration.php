<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('company_email', 190)->nullable()->unique();
            $table->string('phone_country_code', 8)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('organization_type', 40)->nullable()->index();
            $table->string('business_type', 40)->nullable()->index();
        });
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('phone_country_code', 8)->nullable();
            $table->timestamp('phone_verified_at')->nullable();
        });
        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30)->index();
            $table->string('purpose', 40)->default('employer_registration');
            $table->string('code_hash');
            $table->string('session_id', 100)->index();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('employer_registration_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->index();
            $table->string('registration_token_hash', 64)->unique();
            $table->string('ip_hash', 64)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_registration_audits');
        Schema::dropIfExists('phone_otps');
        Schema::table('employer_profiles', fn (Blueprint $table) => $table->dropColumn(['first_name','last_name','phone_country_code','phone_verified_at']));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['company_email','phone_country_code','phone_number','organization_type','business_type']));
    }
};
