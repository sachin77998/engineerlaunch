<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('candidate_profiles',function(Blueprint $t){$t->string('current_salary_currency',3)->default('INR')->after('current_salary');$t->string('expected_salary_currency',3)->default('INR')->after('expected_salary');});}public function down():void{Schema::table('candidate_profiles',fn(Blueprint $t)=>$t->dropColumn(['current_salary_currency','expected_salary_currency']));}};
