<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('vendor_observations',function(Blueprint $t){
 $t->id(); $t->unsignedBigInteger('company_id')->index(); $t->string('reference',100);
 $t->string('kind',30); $t->string('material',100); $t->date('issued_on'); $t->date('due_on');
 $t->date('completed_on')->nullable(); $t->decimal('amount',19,2); $t->string('currency',3);
 $t->string('quality',20)->nullable(); $t->string('evidence',500); $t->unsignedBigInteger('recorded_by');
 $t->timestamps(); $t->unique(['company_id','kind','reference'],'vendor_observation_reference');
 }); }
 public function down(): void { Schema::dropIfExists('vendor_observations'); }
};
