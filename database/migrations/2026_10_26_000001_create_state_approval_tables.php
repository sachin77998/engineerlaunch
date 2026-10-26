<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// State single-window approvals: each state's services (licences, NOCs, connections), their document checklists and the
// state's investment policies and acts, fetched from the state's own single-window portal.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_approval_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->unique()->constrained('industrial_states')->cascadeOnDelete();
            $table->string('portal_name')->nullable();
            $table->string('portal_url', 500)->nullable();
            $table->string('driver', 40)->nullable();
            $table->string('services_url', 500)->nullable();
            $table->string('policies_url', 500)->nullable();
            $table->string('acts_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('services_count')->default(0);
            $table->string('last_status', 400)->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
        Schema::create('state_approval_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('industrial_states')->cascadeOnDelete();
            $table->string('external_id', 40);
            $table->string('department')->nullable()->index();
            $table->string('name', 600);
            $table->string('service_type', 120)->nullable();
            $table->json('stages')->nullable();
            $table->string('integration', 120)->nullable();
            $table->json('risk_categories')->nullable();
            $table->json('unit_sizes')->nullable();
            $table->json('locations')->nullable();
            $table->json('investor_types')->nullable();
            $table->string('checklist_url', 500)->nullable();
            $table->string('form_url', 500)->nullable();
            $table->string('fee_url', 500)->nullable();
            $table->string('sop_url', 500)->nullable();
            $table->string('timeline_url', 500)->nullable();
            $table->string('inspection_url', 500)->nullable();
            $table->string('checklist_hash', 64)->nullable();
            $table->longText('checklist_text')->nullable();
            $table->boolean('locked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
            $table->unique(['state_id', 'external_id']);
        });
        Schema::create('state_approval_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('state_approval_services')->cascadeOnDelete();
            $table->string('source', 12);
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('kind', 12)->default('document');
            $table->string('code', 40)->nullable();
            $table->string('doc_type')->nullable();
            $table->string('issued_by')->nullable();
            $table->text('name');
            $table->string('condition', 500)->nullable();
            $table->string('group_label', 500)->nullable();
            $table->boolean('is_required')->nullable();
            $table->timestamps();
            $table->index(['service_id', 'source', 'position']);
        });
        Schema::create('state_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('industrial_states')->cascadeOnDelete();
            $table->string('kind', 12);
            $table->string('title', 600);
            $table->string('department')->nullable();
            $table->string('url', 500);
            $table->string('url_hash', 40)->unique();
            $table->string('language', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['state_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_policies');
        Schema::dropIfExists('state_approval_documents');
        Schema::dropIfExists('state_approval_services');
        Schema::dropIfExists('state_approval_sources');
    }
};
