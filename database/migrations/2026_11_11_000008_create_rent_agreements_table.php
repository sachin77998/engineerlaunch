<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Online rent agreements (drafted from our own template, ordered as a legal services booking) and their add-on services.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_services', function (Blueprint $table) {
            $table->boolean('addon_only')->default(false)->after('is_active');
        });

        Schema::create('rent_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('city', 80);
            $table->string('state', 60)->nullable();
            $table->string('created_by', 10)->default('owner'); // owner | tenant
            $table->string('usage', 12)->default('residential');
            $table->string('type', 12)->default('notarised');
            $table->json('landlord')->nullable();
            $table->json('tenant')->nullable();
            $table->json('property')->nullable();
            $table->unsignedInteger('rent')->default(0);
            $table->unsignedInteger('deposit')->default(0);
            $table->unsignedTinyInteger('months')->default(11);
            $table->date('start_date')->nullable();
            $table->json('terms')->nullable(); // due day, maintenance, escalation, notice, lock-in, pets, extra clauses
            $table->json('addons')->nullable();
            $table->string('delivery_address', 255)->nullable();
            $table->string('draft_path', 255)->nullable();
            $table->string('draft_name', 191)->nullable();
            $table->json('steps_done')->nullable();
            $table->string('status', 12)->default('draft'); // draft | ordered
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        $now = now();
        $add = fn ($slug, $name, $aud, $summary, $includes, $addonOnly) => ['slug' => $slug, 'name' => $name, 'category' => 'agreements', 'audiences' => json_encode($aud), 'summary' => $summary,
            'includes' => json_encode($includes), 'documents' => json_encode([]), 'cities' => null, 'sort' => 50, 'is_active' => true, 'addon_only' => $addonOnly, 'created_at' => $now, 'updated_at' => $now];
        DB::table('legal_services')->insert([
            $add('aadhaar-esign', 'Aadhaar e-sign', ['owner', 'general'], 'Landlord and tenant sign the soft copy with an Aadhaar OTP, so nobody has to meet in person.', ['E-sign links for both parties', 'Signed PDF by email'], true),
            $add('doorstep-delivery', 'Doorstep delivery of printed copy', ['owner', 'general'], 'The printed, stamped agreement is delivered to your address for signing.', ['Printed on e-stamp paper', 'Delivered to one address'], true),
            $add('tenant-verification', 'Tenant verification', ['owner'], 'Checks the tenant\'s identity with PAN and Aadhaar and helps with the police tenant verification form.', ['ID check', 'Police verification form help'], false),
        ]);
    }

    public function down(): void
    {
        DB::table('legal_services')->whereIn('slug', ['aadhaar-esign', 'doorstep-delivery', 'tenant-verification'])->delete();
        Schema::dropIfExists('rent_agreements');
        Schema::table('legal_services', fn (Blueprint $table) => $table->dropColumn('addon_only'));
    }
};
