<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Services\JobSkillVocabulary;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobSkillSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('regexp', fn ($pattern, $value) => preg_match('~'.$pattern.'~iu', $value ?? '') === 1);
        Schema::create('companies', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('technologies', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('job_technology', function (Blueprint $t) { $t->integer('job_id'); $t->integer('technology_id'); });
        Schema::create('jobs', function (Blueprint $t) {
            $t->id(); $t->integer('company_id')->nullable(); $t->string('title');
            $t->text('description')->nullable(); $t->text('requirements')->nullable();
            $t->string('location')->nullable(); $t->softDeletes();
        });
        DB::table('jobs')->insert([
            ['id'=>1,'title'=>'C engineer','description'=>'Embedded systems'],
            ['id'=>2,'title'=>'C++ engineer','description'=>'C++ development'],
            ['id'=>3,'title'=>'JavaScript engineer','description'=>'Web applications'],
            ['id'=>4,'title'=>'Java engineer','description'=>'Spring Boot backend'],
            ['id'=>5,'title'=>'Platform engineer','description'=>'Node.js TypeScript Kubernetes Apache Kafka Docker'],
            ['id'=>6,'title'=>'Frontend engineer','description'=>'React Native React-Bootstrap HTML5 CSS3'],
            ['id'=>7,'title'=>'Web engineer','description'=>'Python PHP Ruby Swift Django Laravel'],
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_short_and_overlapping_languages_are_distinct(): void
    {
        $this->assertSame([1], Job::search('c')->pluck('id')->all());
        $this->assertSame([2], Job::search('c++')->pluck('id')->all());
        $this->assertSame([2], Job::search('cpp')->pluck('id')->all());
        $this->assertSame([4], Job::search('java')->pluck('id')->all());
        $this->assertSame([3], Job::search('javascript')->pluck('id')->all());
    }

    public function test_user_requested_skill_spellings_find_descriptions_without_tags(): void
    {
        foreach (['node js','nodejs','type script','typescript','k8s','kubernetes','kafka','docker'] as $term) {
            $this->assertSame([5], Job::search($term)->pluck('id')->all(), $term);
        }
        foreach (['react native','react bootstrap','html','css'] as $term) {
            $this->assertSame([6], Job::search($term)->pluck('id')->all(), $term);
        }
        foreach (['python','php','ruby','swift','django','laravel'] as $term) {
            $this->assertSame([7], Job::search($term)->pluck('id')->all(), $term);
        }
        $this->assertSame([4], Job::search('springboot')->pluck('id')->all());
        $this->assertSame([6], Job::search('rect')->pluck('id')->all());
        $this->assertSame([], Job::search('java docker')->pluck('id')->all());
    }

    public function test_skill_extraction_handles_punctuation_and_aliases(): void
    {
        $this->assertTrue(JobSkillVocabulary::matches('Experience with C++, Node JS, HTML and CSS.', 'C++'));
        $this->assertTrue(JobSkillVocabulary::matches('Experience with Node JS.', 'Node.js'));
        $this->assertTrue(JobSkillVocabulary::matches('HTML and CSS', 'HTML5'));
        $this->assertFalse(JobSkillVocabulary::matches('JavaScript engineer', 'Java'));
        $this->assertFalse(JobSkillVocabulary::matches('C++ and C#', 'C'));
    }
}
