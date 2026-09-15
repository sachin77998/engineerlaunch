<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Http\Middleware\TrackActivity;
use App\Services\LearningPresentation;

class VisualLearningTest extends TestCase
{
    public function test_all_tracks_offer_a_visual_overview_and_accessible_comparison_table(): void
    {
        $this->withoutMiddleware(TrackActivity::class);
        foreach (array_keys(config('learning_tracks')) as $track) {
            $this->get('/learn/'.$track)->assertOk()->assertSee('Concept flow')->assertSee('scope="col"', false);
        }
    }

    public function test_questions_preserve_answers_and_code_and_label_unanswered_practice(): void
    {
        $this->withoutMiddleware(TrackActivity::class);
        $this->get('/learn/kafka/fundamentals')->assertOk()->assertSee('data-vl-recall-mode', false)->assertSee('durable records')->assertSee('data-vl-notes', false);
        $this->get('/learn/sql/fundamentals')->assertOk()->assertSee('SELECT *')->assertSee('Reveal explanation');
        $this->get('/learn/java/class')->assertOk()->assertSee('Practice question')->assertSee('What is a class in Java?');
    }

    public function test_answer_formatting_keeps_sql_as_one_code_block_and_escapes_html(): void
    {
        $presenter = new LearningPresentation;
        $sql = "SELECT name\nFROM employees\nWHERE salary > 50000;";
        $this->assertSame([['type'=>'code','text'=>$sql]], $presenter->blocks($sql));
        $html = view('learning.partials.answer-blocks', ['answerText'=>'<script>alert(1)</script>'])->render();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
