<?php

use App\Services\Approvals\ManualApprovals;
use Illuminate\Database\Migrations\Migration;

// Punjab Director of Factories user manuals: documents, notes and processing steps (config/state_approval_manuals.php).
return new class extends Migration
{
    public function up(): void
    {
        app(ManualApprovals::class)->apply();
    }

    public function down(): void
    {
        // Data; not reversed.
    }
};
