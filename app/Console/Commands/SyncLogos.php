<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Branding\Logos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Links fund houses and listed stocks to website domains, then downloads missing logos in batches.
class SyncLogos extends Command
{
    protected $signature = 'logos:sync {--limit=400 : Logos to download in this run}';
    protected $description = 'Resolve logo domains for fund houses, stocks and companies and fetch their logos';

    public function handle(Logos $logos): int
    {
        // 1. Fund houses: official domains.
        $amcs = 0;
        foreach (DB::table('mf_amcs')->get(['id', 'name', 'website']) as $a) {
            if (!blank($a->website) || !($d = Logos::amcDomain($a->name))) continue;
            DB::table('mf_amcs')->where('id', $a->id)->update(['website' => 'https://' . $d]); $amcs++;
        }
        // 2. Stocks: the linked company's website, else a company whose normalised name matches (prefix for names cut at 30 chars).
        $byKey = [];
        DB::table('companies')->whereNotNull('website')->where('website', '!=', '')->orderBy('id')->select(['name_key', 'website'])->chunk(5000, function ($rows) use (&$byKey) {
            foreach ($rows as $r) if ($r->name_key && ($d = Logos::domain($r->website)) && !isset($byKey[$r->name_key])) $byKey[$r->name_key] = $d;
        });
        $keys = array_keys($byKey);
        $stocks = 0;
        DB::table('market_instruments as m')->leftJoin('companies as c', 'c.id', '=', 'm.company_id')->whereNull('m.logo_domain')->where('m.instrument_type', 'STOCK')
            ->select(['m.id', 'm.name', 'c.website'])->orderBy('m.id')->chunk(1000, function ($rows) use ($byKey, $keys, &$stocks) {
                foreach ($rows as $r) {
                    $domain = Logos::domain($r->website);
                    if (!$domain && $r->name) {
                        $name = preg_replace('/\s+(L|LT|LTD|LIMITE|LIMIT|LIM)$/i', '', trim($r->name));
                        $key = Company::nameKey($name);
                        $domain = $byKey[$key] ?? null;
                        // Exchange names are cut at 30 characters, so a longer company name may start with them.
                        if (!$domain && strlen($r->name) >= 29 && strlen($key) >= 10) foreach ($keys as $k) if (str_starts_with($k, $key)) { $domain = $byKey[$k]; break; }
                    }
                    if ($domain) { DB::table('market_instruments')->where('id', $r->id)->update(['logo_domain' => $domain]); $stocks++; }
                }
            });
        $this->info("Domains linked: $amcs fund houses, $stocks stocks.");

        // 3. Download logos not yet checked (or missing for 30+ days): fund houses and stocks first, then companies.
        $known = DB::table('brand_logos')->where(fn ($q) => $q->where('status', 'ok')->orWhere('checked_at', '>', now()->subDays(30)))->pluck('domain')->flip();
        // [domain => name]; the name lets Wikidata confirm the logo by the company's official website.
        $queue = [];
        $add = function ($domain, $name) use (&$queue, $known) { if ($domain && !isset($known[$domain]) && !isset($queue[$domain])) $queue[$domain] = $name; };
        foreach (DB::table('mf_amcs')->get(['name', 'website']) as $a) $add(Logos::domain($a->website), $a->name);
        foreach (DB::table('market_instruments as m')->leftJoin('companies as c', 'c.id', '=', 'm.company_id')->whereNotNull('m.logo_domain')->orderByDesc('m.turnover_lakhs')->get(['m.logo_domain', 'm.name', 'c.name as cname']) as $r) $add($r->logo_domain, $r->cname ?: preg_replace('/\s+L$/', '', $r->name));
        DB::table('companies')->whereNotNull('website')->where('website', '!=', '')->orderByDesc('is_active')->orderBy('id')->limit(20000)->get(['name', 'website'])->each(fn ($c) => $add(Logos::domain($c->website), $c->name));
        $queue = array_slice($queue, 0, (int) $this->option('limit'), true);
        $ok = 0; $i = 0;
        foreach ($queue as $domain => $name) {
            $i++;
            if ($logos->fetch($domain, $name)) $ok++;
            if ($i % 50 === 0) $this->line("$i checked, $ok logos");
            usleep(100000);
        }
        Cache::forget('brand-logos:map');
        $this->info("Logos stored: $ok of " . count($queue) . " checked.");
        return self::SUCCESS;
    }
}
