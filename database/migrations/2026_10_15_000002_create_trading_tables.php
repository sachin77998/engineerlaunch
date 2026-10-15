<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Do Trading": mutual fund catalogue (AMFI), market holidays, client onboarding (KYC, nominees, joint holders,
 * minor to major, documents), orders with fees and status history, holdings, audit logs, calculators and settings.
 * Sensitive identifiers (PAN, bank account, ID numbers) are stored encrypted, with a hash for duplicate checks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mf_schemes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('scheme_code')->unique();
            $table->string('isin_growth', 12)->nullable()->index();
            $table->string('isin_reinvest', 12)->nullable();
            $table->string('name');
            $table->string('amc', 120)->index();
            $table->string('scheme_type', 40)->nullable();          // Open Ended, Close Ended, Interval Fund
            $table->string('category', 120)->nullable()->index();  // Equity Scheme - Large Cap Fund
            $table->string('asset_class', 40)->nullable()->index(); // Equity, Debt, Hybrid, Other, Solution Oriented
            $table->string('plan', 20)->nullable();                 // Direct, Regular
            $table->string('option', 20)->nullable();               // Growth, IDCW
            $table->decimal('nav', 16, 4)->nullable();
            $table->date('nav_date')->nullable();
            $table->decimal('prev_nav', 16, 4)->nullable();
            $table->decimal('return_1y', 9, 2)->nullable();
            $table->decimal('return_3y', 9, 2)->nullable();
            $table->decimal('return_5y', 9, 2)->nullable();
            $table->decimal('min_purchase', 14, 2)->nullable();
            $table->decimal('min_additional', 14, 2)->nullable();
            $table->string('exit_load')->nullable();
            $table->string('risk', 30)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['amc', 'category']);
            $table->fullText('name');
        });

        Schema::create('mf_nav_history', function (Blueprint $table) {
            $table->unsignedInteger('scheme_code');
            $table->date('nav_date');
            $table->decimal('nav', 16, 4);
            $table->primary(['scheme_code', 'nav_date']);
            $table->index('nav_date');
        });

        Schema::create('trading_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_on');
            $table->string('segment', 20)->default('CM');
            $table->string('description');
            $table->string('source', 40)->default('NSE');
            $table->timestamps();
            $table->unique(['holiday_on', 'segment']);
        });

        Schema::create('trading_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->string('type', 20)->default('string');   // string, number, percent, time, boolean
            $table->timestamps();
        });

        Schema::create('trading_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('client_code', 20)->unique();
            $table->string('status', 20)->default('draft')->index();  // draft, submitted, verified, rejected, suspended
            $table->unsignedTinyInteger('step')->default(1);
            // Personal
            $table->string('full_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('father_or_spouse_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('nationality', 40)->default('Indian');
            $table->string('residential_status', 20)->default('resident');   // resident, nri, foreign_national
            $table->string('occupation', 40)->nullable();
            $table->string('annual_income', 40)->nullable();
            $table->string('source_of_wealth', 60)->nullable();
            $table->boolean('is_pep')->default(false);
            $table->boolean('tax_resident_elsewhere')->default(false);
            $table->string('birth_place')->nullable();
            // Contact
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('mobile', 15)->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            // Identity
            $table->text('pan')->nullable();
            $table->string('pan_hash', 64)->nullable()->unique();
            $table->string('pan_name')->nullable();
            $table->string('pan_status', 20)->nullable();
            $table->boolean('pan_aadhaar_linked')->nullable();
            $table->timestamp('pan_verified_at')->nullable();
            $table->string('aadhaar_last4', 4)->nullable();
            $table->string('aadhaar_hash', 64)->nullable()->unique();
            $table->string('aadhaar_method', 30)->nullable();     // digilocker, offline_xml, provider_otp, manual
            $table->timestamp('aadhaar_verified_at')->nullable();
            $table->string('ckyc_number', 20)->nullable();
            $table->string('kra_status', 30)->nullable();
            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('state', 60)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->string('country', 60)->default('India');
            $table->string('address_proof_type', 40)->nullable();
            $table->string('address_source', 30)->nullable();
            $table->timestamp('address_verified_at')->nullable();
            $table->boolean('correspondence_same')->default(true);
            $table->json('correspondence_address')->nullable();
            // Bank
            $table->text('bank_account')->nullable();
            $table->string('bank_account_hash', 64)->nullable()->index();
            $table->string('bank_account_last4', 4)->nullable();
            $table->string('ifsc', 11)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_type', 20)->nullable();
            $table->string('bank_holder_name')->nullable();
            $table->timestamp('bank_verified_at')->nullable();
            // Holding, minor, risk, consent
            $table->string('holding_mode', 30)->default('single');   // single, joint, anyone_or_survivor
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation', 30)->nullable();
            $table->text('guardian_pan')->nullable();
            $table->date('guardian_dob')->nullable();
            $table->boolean('nomination_opted')->nullable();
            $table->timestamp('nomination_opt_out_at')->nullable();
            $table->string('risk_profile', 20)->nullable();
            $table->unsignedTinyInteger('risk_score')->nullable();
            $table->json('risk_answers')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trading_client_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_client_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->string('name');
            $table->string('relation', 30);
            $table->date('dob')->nullable();
            $table->string('id_type', 30)->nullable();      // pan, aadhaar_last4, passport, driving_licence
            $table->text('id_number')->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->decimal('share_percent', 5, 2);
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('trading_joint_holders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_client_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');         // 2 or 3
            $table->string('name');
            $table->date('dob')->nullable();
            $table->text('pan')->nullable();
            $table->string('pan_hash', 64)->nullable()->index();
            $table->timestamp('pan_verified_at')->nullable();
            $table->string('relation_with_primary', 30)->nullable();
            $table->string('mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('kyc_status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['trading_client_id', 'sequence']);
        });

        Schema::create('trading_client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_client_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);       // pan_card, aadhaar_masked, address_proof, bank_proof, photo, signature, birth_certificate, guardian_id, nominee_id, major_pan
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 80);
            $table->unsignedInteger('size');
            $table->string('sha256', 64);
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->index(['trading_client_id', 'type']);
        });

        Schema::create('trading_kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_client_id')->constrained()->cascadeOnDelete();
            $table->string('check', 30);       // pan, aadhaar, bank, ifsc, address, email, mobile, ckyc, kra, document
            $table->string('provider', 40);    // format, sandbox, surepass, razorpay_ifsc, data.gov.in, email_otp, manual
            $table->string('status', 20);      // passed, failed, pending
            $table->string('reference')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('message')->nullable();
            $table->json('response')->nullable();   // masked: never full PAN, Aadhaar or account numbers
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['trading_client_id', 'check']);
        });

        Schema::create('trading_minor_major', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_client_id')->constrained()->cascadeOnDelete();
            $table->date('major_on');
            $table->string('status', 20)->default('requested');   // requested, documents_pending, verified, completed, rejected
            $table->text('new_pan')->nullable();
            $table->text('new_bank_account')->nullable();
            $table->string('new_ifsc', 11)->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mf_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 24)->unique();
            $table->foreignId('trading_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('scheme_code')->index();
            $table->string('side', 10);                 // buy, sell
            $table->string('order_type', 12)->default('lumpsum');   // lumpsum, sip, redeem
            $table->decimal('amount', 16, 2)->nullable();
            $table->decimal('units', 18, 4)->nullable();
            $table->boolean('all_units')->default(false);
            $table->string('status', 20)->index();       // otp_pending, verified, submitted, allotted, rejected, cancelled, failed
            $table->string('status_reason')->nullable();
            $table->string('idempotency_key', 64)->nullable();
            $table->date('applicable_nav_date')->nullable();
            $table->decimal('nav', 16, 4)->nullable();
            $table->decimal('allotted_units', 18, 4)->nullable();
            $table->decimal('stamp_duty', 12, 2)->default(0);
            $table->decimal('transaction_charge', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('gst', 12, 2)->default(0);
            $table->decimal('total_fees', 12, 2)->default(0);
            $table->decimal('net_amount', 16, 2)->nullable();
            $table->string('payment_mode', 20)->nullable();
            $table->string('payment_ref', 64)->nullable();
            $table->string('otp_hash')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('allotted_at')->nullable();
            $table->string('channel', 20)->default('simulated');
            $table->string('external_ref', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['trading_client_id', 'created_at']);
            $table->index(['status', 'applicable_nav_date']);
            $table->index('created_at');
        });

        Schema::create('mf_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mf_order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('actor', 20);       // client, system, admin
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('mf_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_client_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('scheme_code');
            $table->decimal('units', 18, 4)->default(0);
            $table->decimal('invested', 16, 2)->default(0);
            $table->decimal('avg_nav', 16, 4)->nullable();
            $table->timestamps();
            $table->unique(['trading_client_id', 'scheme_code']);
        });

        Schema::create('trading_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trading_client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id', 40)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('calculator_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->index();   // lumpsum, sip, emi, amortization, margin
            $table->json('inputs');
            $table->json('result');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('margin_rates', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 30);
            $table->string('series', 5)->default('EQ');
            $table->decimal('var_margin', 7, 2)->nullable();
            $table->decimal('elm', 7, 2)->nullable();
            $table->decimal('applicable_margin', 7, 2);
            $table->date('as_of');
            $table->timestamps();
            $table->unique(['symbol', 'series']);
        });
    }

    public function down(): void
    {
        foreach (['margin_rates', 'calculator_runs', 'trading_audit_logs', 'mf_holdings', 'mf_order_events', 'mf_orders', 'trading_minor_major',
                  'trading_kyc_verifications', 'trading_client_documents', 'trading_joint_holders', 'trading_client_nominees', 'trading_clients',
                  'trading_settings', 'trading_holidays', 'mf_nav_history', 'mf_schemes'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
