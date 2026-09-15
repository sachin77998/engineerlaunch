<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('industrial_assets',function(Blueprint $t){$t->string('asset_type')->default('card');$t->string('title')->nullable();$t->text('image_url')->nullable();$t->string('alt_text')->nullable();$t->text('source_url')->nullable();$t->boolean('is_active')->default(true);});
  Schema::table('industrial_subsectors',function(Blueprint $t){$t->foreignId('visual_sector_id')->nullable()->constrained('industrial_sectors')->nullOnDelete();});
  Schema::table('industrial_role_profiles',function(Blueprint $t){$t->string('career_level')->nullable();});
 }
 public function down(): void {
  \App\Support\IndustrialSchema::dropColumns('industrial_role_profiles',['career_level']);
  if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') Schema::table('industrial_subsectors',fn(Blueprint $t)=>$t->dropForeign(['visual_sector_id']));
  \App\Support\IndustrialSchema::dropColumns('industrial_subsectors',['visual_sector_id']);
  \App\Support\IndustrialSchema::dropColumns('industrial_assets',['asset_type','title','image_url','alt_text','source_url','is_active']);
 }
};
