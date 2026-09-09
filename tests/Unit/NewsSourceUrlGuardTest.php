<?php
namespace Tests\Unit;use App\Services\News\SourceUrlGuard;use InvalidArgumentException;use Tests\TestCase;
class NewsSourceUrlGuardTest extends TestCase{public function test_it_rejects_non_https_sources():void{$this->expectException(InvalidArgumentException::class);app(SourceUrlGuard::class)->assertAllowed('http://github.blog/feed/');}public function test_it_rejects_hosts_outside_allowlist():void{$this->expectException(InvalidArgumentException::class);app(SourceUrlGuard::class)->assertAllowed('https://example.invalid/feed');}}
