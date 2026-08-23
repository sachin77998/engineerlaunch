<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::table('users',fn(Blueprint $t)=>$t->unsignedTinyInteger('role_code')->default(1)->after('role')->index());
  DB::table('users')->where('role','employer')->update(['role_code'=>0]);DB::table('users')->where('role','student')->update(['role_code'=>1]);DB::table('users')->where('role','admin')->update(['role_code'=>2]);
  Schema::table('jobs',fn(Blueprint $t)=>$t->softDeletes());
  Schema::create('search_logs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('session_id',100)->nullable()->index();$t->string('keyword',160)->nullable()->index();$t->string('location',160)->nullable()->index();$t->json('filters')->nullable();$t->unsignedInteger('results_count')->default(0);$t->timestamp('created_at')->useCurrent()->index();});
  Schema::create('deleted_job_events',function(Blueprint $t){$t->id();$t->unsignedBigInteger('job_id')->nullable()->index();$t->foreignId('employer_id')->nullable()->constrained('users')->nullOnDelete();$t->foreignId('company_id')->nullable()->constrained()->nullOnDelete();$t->string('job_title');$t->text('reason')->nullable();$t->timestamp('deleted_at')->useCurrent()->index();});
 }
 public function down():void{Schema::dropIfExists('deleted_job_events');Schema::dropIfExists('search_logs');Schema::table('jobs',fn(Blueprint $t)=>$t->dropSoftDeletes());Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role_code'));}
};
