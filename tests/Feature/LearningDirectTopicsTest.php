<?php
namespace Tests\Feature;
use Tests\TestCase;
class LearningDirectTopicsTest extends TestCase {
 public function test_topics_are_shown_without_intro_panel():void {
  $this->get('/learn')->assertOk()->assertSee('Learning topics')->assertDontSee('Turn a concept into something you can explain.')->assertDontSee('See it. Understand it. Build it.');
 }
 public function test_track_places_optional_guide_after_topic_links():void {
  $response=$this->get('/learn/php-laravel');$response->assertOk()->assertSee('Optional study guide');
  $html=$response->getContent();$this->assertLessThan(strpos($html,'Optional study guide'),strpos($html,'class="topic-card"'));
 }
 public function test_backend_concepts_and_laravel_part_two_render_in_depth():void {
  foreach(array_keys(config('learning_tracks.backend-concepts.topics')) as $topic){
   $this->get('/learn/backend-concepts/'.$topic)->assertOk()->assertSee('class="sql-case-section"',false)->assertSee('<pre>',false)->assertDontSee('SQL topics');
  }
  $this->get('/learn/backend-concepts/queues-batches-cron-scheduling')->assertSee('withoutOverlapping')->assertSee('@Scheduled');
  $this->get('/learn/php-laravel/laravel-interview-2?page=3')->assertOk()->assertSee('Q50. Design a secure Laravel login API.');
 }
}
