<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "%term%" title searches read every job row with its description (27s+ on 1.5 lakh jobs); scanning this index instead
// takes a fraction of a second and returns the ids to load.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', fn (Blueprint $t) => $t->index('title', 'jobs_title_index'));
    }

    public function down(): void
    {
        Schema::table('jobs', fn (Blueprint $t) => $t->dropIndex('jobs_title_index'));
    }
};
