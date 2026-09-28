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
}
