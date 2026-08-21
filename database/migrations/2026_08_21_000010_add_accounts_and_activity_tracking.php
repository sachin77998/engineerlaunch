<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', fn(Blueprint $t) => $t->string('role',30)->default('student')->index());
  Schema::create('email_otps', function(Blueprint $t){$t->id();$t->string('email')->index();$t->string('code_hash');$t->timestamp('expires_at');$t->timestamp('used_at')->nullable();$t->timestamps();});
  Schema::create('activity_logs', function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('session_id',100)->nullable()->index();$t->string('method',10);$t->string('path',500)->index();$t->string('action',80)->default('page_view')->index();$t->string('ip_hash',64)->nullable();$t->string('user_agent',500)->nullable();$t->timestamp('created_at')->useCurrent()->index();});
 }
 public function down(): void {Schema::dropIfExists('activity_logs');Schema::dropIfExists('email_otps');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role'));}
};
