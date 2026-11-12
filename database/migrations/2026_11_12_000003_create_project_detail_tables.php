<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Project page details: unit configurations with floor plans and prices, "why consider" highlights, videos, FAQs,
// extra charges, enquiries (view number / callback / brochure / contact builder) and project shortlists.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $t) {
            $t->string('logo_path')->nullable()->after('description');
            $t->string('brochure_path')->nullable()->after('logo_path');
            $t->string('contact_phone', 20)->nullable()->after('brochure_path');
            $t->string('contact_email', 191)->nullable()->after('contact_phone');
            $t->boolean('no_brokerage')->default(false)->after('contact_email');
        });
        Schema::table('builders', function (Blueprint $t) {
            $t->string('phone', 20)->nullable()->after('website');
            $t->string('email', 191)->nullable()->after('phone');
        });
        Schema::create('project_units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('label', 80);                 // "2 BHK Apartment" — the tab it shows under
            $t->string('bhk', 6)->nullable();        // popt_bhk key
            $t->string('area_type', 20)->default('super_built_up'); // super_built_up | built_up | carpet | plot
            $t->decimal('area_sqft', 10, 2);
            $t->decimal('price', 16, 2)->nullable(); // null = price on request
            $t->string('floor_plan_path')->nullable();
            $t->unsignedTinyInteger('bathrooms')->nullable();
            $t->unsignedTinyInteger('balconies')->nullable();
            $t->date('possession_on')->nullable();   // overrides the project's
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->index(['project_id', 'label']);
        });
        Schema::create('project_highlights', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('text', 255);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });
        Schema::create('project_videos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('url', 300);          // YouTube or Vimeo link
            $t->string('title', 160)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });
        Schema::create('project_faqs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('question', 255);
            $t->text('answer');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });
        Schema::create('project_charges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('name', 120);          // "Car parking", "Club membership", "Stamp duty"
            $t->decimal('amount', 14, 2)->nullable();
            $t->string('note', 160)->nullable(); // "per sq ft", "as per government rates"
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });
        Schema::create('project_leads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('unit_id')->nullable()->constrained('project_units')->nullOnDelete();
            $t->string('kind', 12); // phone | callback | brochure | contact
            $t->string('name', 120)->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('email', 191)->nullable();
            $t->string('message', 1000)->nullable();
            $t->string('status', 12)->default('new'); // new | contacted | closed
            $t->timestamps();
            $t->index(['project_id', 'kind', 'created_at']);
        });
        Schema::create('project_shortlists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['user_id', 'project_id']);
        });
        // New option lists (amenity icons, area types) get their tables on deploy.
        app(\App\Services\Property\PropertyOptions::class)->sync();
    }

    public function down(): void
    {
        foreach (['project_shortlists', 'project_leads', 'project_charges', 'project_faqs', 'project_videos', 'project_highlights', 'project_units'] as $t) Schema::dropIfExists($t);
        Schema::table('builders', fn (Blueprint $t) => $t->dropColumn(['phone', 'email']));
        Schema::table('projects', fn (Blueprint $t) => $t->dropColumn(['logo_path', 'brochure_path', 'contact_phone', 'contact_email', 'no_brokerage']));
    }
};
