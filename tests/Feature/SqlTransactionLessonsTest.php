<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Http\Middleware\TrackActivity;
class SqlTransactionLessonsTest extends TestCase {
 public function test_all_transaction_lessons_are_linked_under_sql_and_render():void {
  $this->withoutMiddleware(TrackActivity::class);
  $index=$this->get('/learn/sql')->assertOk();
  foreach(config('sql_transactions') as $slug=>$topic) {
   $index->assertSee('/learn/sql/'.$slug,false);
   $page=$this->get('/learn/sql/'.$slug)->assertOk();
   foreach($topic['questions'] as $item) $page->assertSee($item['question']);
   $page->assertSee('class="learning-shell"',false);
  }
  $this->get('/learn/sql/transaction-schema-laravel')->assertSee('<pre><code>',false)->assertSee('afterCommit()')->assertSee('CREATE TABLE withdrawal_requests');
  $this->get('/learn/mysql/corporate-salary-batches')->assertOk()->assertSee('75 crore');
 }
}
