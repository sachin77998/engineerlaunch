<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Site-visit bookings for listings and projects (one table, polymorphic), plus each project's visiting schedule.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $t) {
            $t->id();
            $t->string('visitable_type', 20);     // property | project
            $t->unsignedBigInteger('visitable_id');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name', 120);
            $t->string('phone', 20);
            $t->string('email', 191)->nullable();
            $t->dateTime('visit_at');
            $t->unsignedTinyInteger('people')->default(1);
            $t->string('notes', 500)->nullable();
            $t->string('status', 12)->default('requested'); // requested | confirmed | completed | no_show | cancelled
            $t->string('host_note', 500)->nullable();
            $t->string('cancelled_by', 10)->nullable();     // visitor | host
            $t->timestamp('confirmed_at')->nullable();
            $t->unsignedTinyInteger('reschedules')->default(0);
            $t->timestamps();
            $t->index(['visitable_type', 'visitable_id', 'visit_at']);
            $t->index(['user_id', 'visit_at']);
        });
        Schema::table('projects', function (Blueprint $t) {
            $t->json('visit_days')->nullable()->after('no_brokerage');   // ISO weekdays 1–7; null = every day
            $t->string('visit_start', 5)->nullable()->after('visit_days'); // "10:00"
            $t->string('visit_end', 5)->nullable()->after('visit_start');  // "18:00"
            $t->unsignedTinyInteger('visit_capacity')->nullable()->after('visit_end'); // bookings per slot
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
        Schema::table('projects', fn (Blueprint $t) => $t->dropColumn(['visit_days', 'visit_start', 'visit_end', 'visit_capacity']));
    }
};
