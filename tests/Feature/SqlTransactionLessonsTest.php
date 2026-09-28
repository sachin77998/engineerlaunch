<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Http\Middleware\TrackActivity;
use App\Services\LearningCodeHighlighter;
class SqlTransactionLessonsTest extends TestCase
{
    public function test_all_expanded_lessons_keep_navigation_and_complete_lifecycle(): void
    {
        $this->withoutMiddleware(TrackActivity::class);
        $topics=config('sql_transactions');
        $this->assertCount(8,$topics);
        $index=$this->get('/learn/sql')->assertOk();
        foreach($topics as $slug=>$topic) {
            $index->assertSee('/learn/sql/'.$slug,false);
            $this->assertCount(23,$topic['sections']);
            $ids=array_column($topic['sections'],'id');
            $this->assertCount(23,array_unique($ids));
            $page=$this->get('/learn/sql/'.$slug)->assertOk();
            $page->assertSee('Illustrative production scenario')->assertSee('Final Database State')
                ->assertSee('Optional study guide')->assertSee('Other courses')
                ->assertSee('class="learning-shell"',false)->assertSee('data-shared-header',false);
            foreach($topic['sections'] as $section) {
                $page->assertSee('href="#'.$section['id'].'"',false)->assertSee('id="'.$section['id'].'"',false);
                foreach($section['blocks'] as $block) {
                    $this->assertArrayNotHasKey('shared',$block);
                    if($block['type']==='table') foreach($block['rows'] as $row) $this->assertCount(count($block['columns']),$row,$slug.' '.$block['caption']);
                    if($block['type']==='links') foreach($block['items'] as $link) $this->assertArrayHasKey($link[1],config('learning_tracks.sql.topics'));
                }
            }
            // A long lifecycle stays on one page; page=2 must not hide its final state.
            $this->get('/learn/sql/'.$slug.'?page=2')->assertOk()->assertSee('Final Database State');
        }
        $this->get('/learn/mysql/university-fees')->assertOk()->assertSee('REC-2026-000502');
        $this->get('/learn/sql/fundamentals')->assertOk()->assertSee('What is SQL?');
    }

    public function test_examples_preserve_financial_distinctions_and_code(): void
    {
        $this->withoutMiddleware(TrackActivity::class);
        $fee=$this->get('/learn/sql/university-fees')->assertOk()->assertSee('90000.00')->assertSee('10000.00')->assertSee('4,980');
        $this->get('/learn/sql/corporate-salary-batches')->assertOk()->assertSee('is_final=false')->assertSee('BANK_TIMEOUT');
        $code=$this->get('/learn/sql/transaction-schema-laravel')->assertOk()->getContent();
        $plain=html_entity_decode(strip_tags($code),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $this->assertStringContainsString('CREATE TABLE withdrawal_requests',$plain);
        $this->assertStringContainsString('->afterCommit()',$plain);
        $this->assertStringContainsString('sql-token-keyword',$code);
    }

    public function test_highlighter_preserves_text_without_executing_or_injecting_it(): void
    {
        $source="SELECT '<script>alert(1)</script>' AS value; -- comment";
        $html=(string)app(LearningCodeHighlighter::class)->render($source);
        $this->assertStringNotContainsString('<script>',$html);
        $this->assertSame($source,html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8'));
        $this->assertStringContainsString('sql-token-string',$html);
    }
}
