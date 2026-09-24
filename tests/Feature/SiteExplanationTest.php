<?php
namespace Tests\Feature;

use App\Services\SiteExplanation;
use App\Http\Middleware\ApplySiteChrome;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class SiteExplanationTest extends TestCase
{
    private function requestFor(string $name): Request
    {
        $request=Request::create('/example');
        $route=(new Route('GET','example',fn()=>''))->name($name);
        $request->setRouteResolver(fn()=>$route);return $request;
    }
    public function test_main_workflows_have_distinct_visible_explanations(): void
    {
        foreach(['home','industrial.index','companies.show','company.experiences.index','news.index','resume.builder','practice','about','contact','login','dashboard','employer.jobs.create','admin.dashboard'] as $name){
            $data=app(SiteExplanation::class)->forRequest($this->requestFor($name));
            $this->assertNotNull($data,$name);
            $html=view('partials.visual-explainer',['explanation'=>$data])->render();
            $this->assertStringContainsString('Step-by-step diagram',$html);
            $this->assertStringContainsString('<table>',$html);
            $this->assertStringContainsString('<ul>',$html);
            $this->assertStringNotContainsString('<details',$html);
        }
        $this->assertNull(app(SiteExplanation::class)->forRequest($this->requestFor('learning.show')));
        $this->assertNull(app(SiteExplanation::class)->forRequest($this->requestFor('owner.login')));
        $this->assertNull(app(SiteExplanation::class)->forRequest($this->requestFor('owner.register')));
    }
    public function test_explainer_is_after_hero_once_and_not_added_to_json_or_fragments(): void
    {
        $middleware=new ApplySiteChrome;$request=$this->requestFor('about');
        $page='<html><head></head><body><header data-shared-header>Header</header><main><section class="hero-band"><h1>About</h1></section><p>Content</p><script>const example="</body>";</script></main></body></html>';
        $response=$middleware->handle($request,fn()=>response($page,200,['Content-Type'=>'text/html']));$html=$response->getContent();
        $this->assertSame(1,substr_count($html,'data-visual-explainer='));
        $this->assertStringContainsString('const example="</body>";', $html);
        $this->assertLessThan(strpos($html,'data-visual-explainer='),strpos($html,'<h1>About</h1>'));
        $again=$middleware->handle($request,fn()=>response($html,200,['Content-Type'=>'text/html']))->getContent();
        $this->assertSame(1,substr_count($again,'data-visual-explainer='));
        $json=$middleware->handle($request,fn()=>response()->json(['ok'=>true]));$this->assertSame('{"ok":true}',$json->getContent());
        $fragment=$middleware->handle($request,fn()=>response('<div>Results</div>'));$this->assertSame('<div>Results</div>',$fragment->getContent());
    }
    public function test_industrial_examples_preserve_sibling_components_and_escape_data(): void
    {
        $html=view('industrial.learning-examples',['taxonomy'=>['sector'=>null]])->render();
        $this->assertSame(6,substr_count($html,'data-explain-example='));
        $this->assertStringContainsString('Sibling components',$html);
        foreach(['Brake Disc','Brake Caliper','Brake Pads','Heat Treatment','Core Making'] as $text)$this->assertStringContainsString($text,$html);
        $data=config('site_explanations.jobs');$data['key']='jobs';$data['title']='<script>alert(1)</script>';
        $this->assertStringNotContainsString('<script>alert(1)</script>',view('partials.visual-explainer',['explanation'=>$data])->render());
    }
}
