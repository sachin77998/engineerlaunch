<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// XML sitemaps for search engines: an index (/sitemap.xml) and one file per section, jobs split into pages of 40,000
// (the protocol allows 50,000). Thin pages are left out: company pages only when the company has open jobs, plants or
// financials. Each file is cached for 6 hours.
class SitemapController extends Controller
{
    private const PER_FILE = 40000;
    private const TTL = 21600;

    public function index()
    {
        $xml = Cache::remember('sitemap:index', self::TTL, function () {
            $parts = ['static', 'sectors', 'industrial-areas', 'approvals', 'companies', 'insights', 'news', 'technologies'];
            $jobPages = max(1, (int) ceil($this->jobQuery()->count() / self::PER_FILE));
            for ($i = 1; $i <= $jobPages; $i++) $parts[] = 'jobs-' . $i;
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($parts as $p) $out .= '<sitemap><loc>' . e(url('/sitemaps/' . $p . '.xml')) . '</loc><lastmod>' . now()->toDateString() . '</lastmod></sitemap>';
            return $out . '</sitemapindex>';
        });
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function part(string $part)
    {
        abort_unless(preg_match('/^(static|sectors|industrial-areas|approvals|companies|insights|news|technologies|jobs-\d{1,3})$/', $part), 404);
        $xml = Cache::remember('sitemap:' . $part, self::TTL, function () use ($part) {
            $urls = $this->urls($part);
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($urls as [$loc, $lastmod]) $out .= '<url><loc>' . e($loc) . '</loc>' . ($lastmod ? '<lastmod>' . substr((string) $lastmod, 0, 10) . '</lastmod>' : '') . '</url>';
            return $out . '</urlset>';
        });
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** @return iterable<array{0:string,1:?string}> */
    private function urls(string $part): iterable
    {
        if (str_starts_with($part, 'jobs-')) {
            $page = (int) substr($part, 5);
            return $this->jobQuery()->orderBy('id')->offset(($page - 1) * self::PER_FILE)->limit(self::PER_FILE)->get(['id', 'slug', 'updated_at'])
                ->map(fn ($j) => [$j->slug ? route('jobs.show', $j->slug) : route('opportunities.show', $j->id), $j->updated_at]);
        }
        return match ($part) {
            'static' => collect(['home', 'companies.index', 'career-explorer', 'sectors.index', 'industrial.index', 'approvals.index', 'insights.index', 'news.index',
                'technologies.index', 'learning.index', 'practice', 'calculators.index', 'company.experiences.index', 'money.index', 'trading.index', 'about', 'contact', 'bills.index'])
                ->filter(fn ($r) => \Route::has($r))->map(fn ($r) => [route($r), null])
                ->concat(collect(config('bill_payments.categories'))->keys()->map(fn ($k) => [route('bills.category', $k), null]))
                ->concat(collect(config('bill_payments.cities'))->map(fn ($c) => [route('bills.city', \Illuminate\Support\Str::slug($c[0])), null])),
            'sectors' => DB::table('company_categories as c')->leftJoin('company_categories as p', 'p.id', '=', 'c.parent_id')->where('c.taxonomy', 'sector')
                ->orderBy('c.id')->get(['c.slug', 'p.slug as parent', 'c.updated_at'])
                ->map(fn ($c) => [$c->parent ? route('sectors.subcategory', [$c->parent, $c->slug]) : route('sectors.show', $c->slug), $c->updated_at]),
            'industrial-areas' => DB::table('industrial_areas as a')->join('industrial_states as s', 's.id', '=', 'a.state_id')->where('a.is_active', true)
                ->orderBy('a.id')->get(['a.slug', 's.slug as state', 'a.updated_at'])->map(fn ($a) => [route('industrial.show', [$a->state, $a->slug]), $a->updated_at]),
            'approvals' => DB::table('state_approval_services as v')->join('industrial_states as s', 's.id', '=', 'v.state_id')->where('v.is_active', true)
                ->orderBy('v.id')->get(['v.external_id', 's.slug as state', 'v.updated_at'])
                ->filter(fn ($v) => preg_match('/^[A-Za-z0-9]+-[A-Za-z0-9]+$/', $v->external_id))
                ->map(fn ($v) => [route('approvals.service', [$v->state, $v->external_id]), $v->updated_at])
                ->concat(DB::table('industrial_states')->where('is_active', true)->pluck('slug')->map(fn ($s) => [route('approvals.state', $s), null])),
            // Companies with something to show: open jobs or recorded plants / offices.
            'companies' => DB::table('companies as c')->where('c.is_active', true)->whereNotNull('c.slug')
                ->where(fn ($q) => $q->whereExists(fn ($e) => $e->from('jobs')->whereColumn('jobs.company_id', 'c.id')->where('jobs.is_active', true))
                    ->orWhereExists(fn ($e) => $e->from('company_facilities')->whereColumn('company_facilities.company_id', 'c.id')->where('facility_type', '!=', 'Registered office')))
                ->orderBy('c.id')->limit(self::PER_FILE)->get(['c.slug', 'c.updated_at'])->map(fn ($c) => [route('companies.show', $c->slug), $c->updated_at]),
            'insights' => DB::table('companies as c')->whereNotNull('c.slug')->whereExists(fn ($e) => $e->from('company_financial_statements')->whereColumn('company_financial_statements.company_id', 'c.id'))
                ->orderBy('c.id')->limit(self::PER_FILE)->get(['c.slug', 'c.updated_at'])->map(fn ($c) => [route('insights.show', $c->slug), $c->updated_at]),
            'news' => DB::table('news_articles')->where('is_published', true)->orderByDesc('published_at')->limit(self::PER_FILE)->get(['slug', 'updated_at'])
                ->map(fn ($n) => [route('news.show', $n->slug), $n->updated_at]),
            'technologies' => DB::table('technologies')->where('jobs_count', '>', 0)->orderBy('id')->get(['slug', 'updated_at'])->map(fn ($t) => [route('technologies.show', $t->slug), $t->updated_at]),
        };
    }

    private function jobQuery()
    {
        // Jobs with a slug live at /jobs/{slug}; imported jobs without one at /opportunities/{id}.
        return Job::active();
    }
}
