<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_profiles', 'locality')) $table->string('locality')->nullable()->after('city')->index();
            if (!Schema::hasColumn('candidate_profiles', 'job_search_status')) $table->string('job_search_status', 40)->nullable()->index();
            if (!Schema::hasColumn('candidate_profiles', 'search_appearances')) $table->unsignedBigInteger('search_appearances')->default(0);
            if (!Schema::hasColumn('candidate_profiles', 'recruiter_actions')) $table->unsignedBigInteger('recruiter_actions')->default(0);
            if (!Schema::hasColumn('candidate_profiles', 'profile_views_count')) $table->unsignedBigInteger('profile_views_count')->default(0);
            if (!Schema::hasColumn('candidate_profiles', 'phone_confirmed_at')) $table->timestamp('phone_confirmed_at')->nullable();
            if (!Schema::hasColumn('candidate_profiles', 'guidance_completed_at')) $table->timestamp('guidance_completed_at')->nullable();
        });

        if (!Schema::hasTable('recruiter_profile_views')) {
            Schema::create('recruiter_profile_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
                $table->string('source', 40)->default('application');
                $table->date('viewed_on');
                $table->timestamps();
                $table->unique(['recruiter_id', 'candidate_profile_id', 'source', 'viewed_on'], 'recruiter_profile_daily_view_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_profile_views');
        $columns = collect(['locality','job_search_status','search_appearances','recruiter_actions','profile_views_count','phone_confirmed_at','guidance_completed_at'])
            ->filter(fn ($column) => Schema::hasColumn('candidate_profiles', $column))->all();
        if ($columns) Schema::table('candidate_profiles', fn (Blueprint $table) => $table->dropColumn($columns));
    }
};
