<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Services\Discovery\CompanyClassifier;
use App\Services\SectorCatalogImporter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Files listed companies under the right sector card. A name alone often says nothing ("Britannia Industries",
 * "Nestle India"), so the industry NSE publishes in its index constituent lists comes first, then the NIC division
 * in the CIN, and the name classifier only picks the sub-category inside that industry.
 */
class ListedSectors
{
    private const INDEX_LISTS = [
        'https://nsearchives.nseindia.com/content/indices/ind_niftytotalmarket_list.csv',
        'https://nsearchives.nseindia.com/content/indices/ind_niftymicrocap250_list.csv',
    ];

    // NSE industry => [sectors the name classifier may choose inside it, default sector and sub-category].
    private const INDUSTRIES = [
        'Fast Moving Consumer Goods' => [['Food & Beverages', 'FMCG - Personal & Home Care'], ['FMCG - Personal & Home Care', 'Diversified FMCG & Tobacco']],
        'Healthcare' => [['Pharmaceuticals & Healthcare'], ['Pharmaceuticals & Healthcare', 'Formulations & Contract Manufacturing']],
        'Information Technology' => [['IT Services & Software'], ['IT Services & Software', 'Indian IT Services']],
        'Automobile and Auto Components' => [['Automobile Manufacturers', 'Auto Components, Forging & Casting'], ['Auto Components, Forging & Casting', 'Gears, Steering, Brakes & Systems']],
        'Capital Goods' => [['Heavy Engineering & Capital Goods', 'Electrical Equipment & Appliances', 'Auto Components, Forging & Casting', 'Semiconductors & Electronics', 'Steel, Metals & Pipes'],
            ['Heavy Engineering & Capital Goods', 'Capital Goods & Power Equipment']],
        'Chemicals' => [['Cement, Building Materials & Polymers', 'Agriculture & Agri-Inputs'], ['Cement, Building Materials & Polymers', 'Polymers & Petrochemicals']],
        'Consumer Durables' => [['Electrical Equipment & Appliances', 'Consumer Electronics & Devices', 'Cement, Building Materials & Polymers', 'Apparel, Textiles & Footwear'],
            ['Electrical Equipment & Appliances', 'Consumer Appliances & Air Conditioning']],
        'Consumer Services' => [['Restaurants, Hotels & Travel', 'FMCG - Personal & Home Care', 'Education & Training', 'IT Services & Software', 'Media, Telecom & Entertainment', 'Apparel, Textiles & Footwear'],
            ['FMCG - Personal & Home Care', 'Supermarkets & Retail Chains']],
        'Metals & Mining' => [['Steel, Metals & Pipes'], ['Steel, Metals & Pipes', 'Stainless Steel & Non-Ferrous Metals']],
        'Power' => [['Oil, Gas & Energy'], ['Oil, Gas & Energy', 'Power Generation & Utilities']],
        'Utilities' => [['Oil, Gas & Energy'], ['Oil, Gas & Energy', 'Power Generation & Utilities']],
        'Oil Gas & Consumable Fuels' => [['Oil, Gas & Energy'], ['Oil, Gas & Energy', 'Oil Refining & Marketing']],
        'Realty' => [['Real Estate & Construction'], ['Real Estate & Construction', 'Real Estate & Infrastructure']],
        'Construction' => [['Real Estate & Construction'], ['Real Estate & Construction', 'Real Estate & Infrastructure']],
        'Construction Materials' => [['Cement, Building Materials & Polymers'], ['Cement, Building Materials & Polymers', 'Cement']],
        'Textiles' => [['Apparel, Textiles & Footwear'], ['Apparel, Textiles & Footwear', 'Textiles, Cotton & Yarn']],
        'Telecommunication' => [['Media, Telecom & Entertainment'], ['Media, Telecom & Entertainment', 'Telecom']],
        'Media Entertainment & Publication' => [['Media, Telecom & Entertainment'], ['Media, Telecom & Entertainment', 'Media & Entertainment']],
        'Services' => [['Logistics & Supply Chain', 'Restaurants, Hotels & Travel', 'IT Services & Software', 'Education & Training'], ['Logistics & Supply Chain', 'Courier, Express & Freight']],
        'Forest Materials' => [['Cement, Building Materials & Polymers'], ['Cement, Building Materials & Polymers', 'Paper, Pulp & Packaging']],
    ];

    // NIC 2004 division in the CIN => the NSE industry it corresponds to.
    private const NIC = [
        '01' => 'Chemicals', '15' => 'Fast Moving Consumer Goods', '16' => 'Fast Moving Consumer Goods', '17' => 'Textiles', '18' => 'Textiles', '19' => 'Textiles',
        '20' => 'Forest Materials', '21' => 'Forest Materials', '22' => 'Media Entertainment & Publication', '23' => 'Oil Gas & Consumable Fuels', '25' => 'Chemicals',
        '26' => 'Construction Materials', '27' => 'Metals & Mining', '13' => 'Metals & Mining', '10' => 'Metals & Mining', '11' => 'Oil Gas & Consumable Fuels',
        '28' => 'Capital Goods', '29' => 'Capital Goods', '31' => 'Capital Goods', '30' => 'Information Technology', '72' => 'Information Technology', '32' => 'Consumer Durables',
        '34' => 'Automobile and Auto Components', '35' => 'Automobile and Auto Components', '40' => 'Power', '41' => 'Utilities', '45' => 'Construction', '70' => 'Realty',
        '52' => 'Consumer Services', '55' => 'Consumer Services', '60' => 'Services', '61' => 'Services', '62' => 'Services', '63' => 'Services', '64' => 'Telecommunication',
        '65' => 'Financial Services', '66' => 'Financial Services', '67' => 'Financial Services', '85' => 'Healthcare', '92' => 'Media Entertainment & Publication',
    ];

    private const FALLBACK_SUBS = ['Discovered Companies'];

    public function __construct(private CompanyClassifier $classifier, private SectorCatalogImporter $catalog) {}

    /** Symbol => NSE industry, from the index constituent lists (cached for a day; empty when NSE is unreachable). */
    public function industries(): array
    {
        $cached = Cache::get('listed-sectors:industries');
        if (is_array($cached) && $cached) return $cached;
        $map = [];
        foreach (self::INDEX_LISTS as $url) {
            try {
                $csv = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36'])
                    ->timeout(30)->get($url)->throw()->body();
            } catch (Throwable $e) { report($e); continue; }
            $rows = array_map('str_getcsv', preg_split('/\r?\n/', trim($csv)));
            $head = array_map('trim', array_shift($rows) ?? []);
            [$sym, $ind] = [array_search('Symbol', $head, true), array_search('Industry', $head, true)];
            if ($sym === false || $ind === false) continue;
            foreach ($rows as $r) if (!empty($r[$sym]) && !empty($r[$ind])) $map[strtoupper(trim($r[$sym]))] = trim($r[$ind]);
        }
        if ($map) Cache::put('listed-sectors:industries', $map, now()->addDay());
        return $map;
    }

    /** @return array{0:string,1:string}|null [sector, sub-category] */
    public function classify(string $name, ?string $industry, ?string $cin): ?array
    {
        // The catalog is curated: a company it lists is already where it belongs.
        if ($place = $this->catalogPlaces()[Company::nameKey($name)] ?? null) return $place;
        $named = $this->classifier->classify($name);
        if (!$industry) {
            // A CIN keeps the industry the company was registered with; a recognisable name is more current.
            if ($named[0] === 'Banking & Financial Services') return $this->finance($name, $named, $cin);
            if (!$this->classifier->isFallback($named)) return $named;
            $industry = preg_match('/^[LU](\d{2})/', (string) $cin, $m) ? (self::NIC[$m[1]] ?? null) : null;
        }
        if ($industry === 'Financial Services') return $this->finance($name, $named, $cin);
        if (!$industry || !isset(self::INDUSTRIES[$industry])) return $this->classifier->isFallback($named) ? null : $named;
        [$allowed, $default] = self::INDUSTRIES[$industry];
        if ($industry === 'Fast Moving Consumer Goods' && str_starts_with((string) $cin, 'L15') && $named[0] !== 'Food & Beverages') $default = ['Food & Beverages', 'Packaged Foods & Confectionery'];
        if ($industry === 'Healthcare' && preg_match('/hospital|health ?care|medical (?:centre|center|services)|clinic/i', $name)) return ['Pharmaceuticals & Healthcare', 'Hospitals & Healthcare Services'];
        if ($industry === 'Consumer Services' && !preg_match('/retail|\bmarts?\b|supermart|\bstores?\b|vision|lifestyle|fashion|trent/i', $name)) $default = ['IT Services & Software', 'Product & Internet Companies'];
        return in_array($named[0], $allowed, true) ? $named : $default;
    }

    private function finance(string $name, array $named, ?string $cin): array
    {
        $n = mb_strtolower($name);
        if (preg_match('/insurance|assurance/', $n) || str_starts_with((string) $cin, 'L66')) return $named[0] === 'Insurance' ? $named : ['Insurance', 'General Insurance'];
        if (preg_match('/\bbank\b/', $n)) return $named[0] === 'Banking & Financial Services' && str_contains($named[1], 'Bank') ? $named : ['Banking & Financial Services', 'Indian Banks'];
        if (preg_match('/payment|\bupi\b|wallet|\bcards?\b|one ?97|paytm/', $n)) return ['Banking & Financial Services', 'Payments, UPI & Cards'];
        if (preg_match('/brok|securities|stock|exchange|depositor|asset management|\bamc\b|\bwam\b|registrar|wealth|capital markets|mutual fund|computer age|\bcams\b|ratings?\b|angel one/', $n))
            return ['Banking & Financial Services', 'Fintech, Broking & Market Infrastructure'];
        return ['Banking & Financial Services', 'NBFC, Lending & Housing Finance'];
    }

    /**
     * Files the company under the decided sub-category. Placeholder ("Discovered Companies") links and sub-categories
     * the name classifier picked wrongly inside the same sector (e.g. an NBFC under Broking) are removed; catalog
     * entries stay because the catalog only lists a company where it belongs.
     */
    public function file(Company $company, array $decision): bool
    {
        [$sector, $sub] = $decision;
        $before = $company->categories()->pluck('company_categories.id')->all();
        $this->catalog->attach($company, $sector, $sub);
        $child = CompanyCategory::where('name', $sub)->whereHas('parent', fn ($q) => $q->where('name', $sector))->first();
        $wrong = self::FALLBACK_SUBS;
        if ($sub === 'NBFC, Lending & Housing Finance' && !in_array(Company::nameKey($company->name), $this->catalogBrokers(), true)) $wrong[] = 'Fintech, Broking & Market Infrastructure';
        $stale = CompanyCategory::whereIn('id', $before)->whereNotNull('parent_id')->whereIn('name', $wrong)->pluck('id');
        if ($stale->isNotEmpty()) {
            $company->categories()->detach($stale);
            $parents = CompanyCategory::whereIn('id', $stale)->pluck('parent_id')->filter()->unique();
            $keep = $company->categories()->whereIn('company_categories.parent_id', $parents)->pluck('company_categories.parent_id');
            $company->categories()->detach($parents->diff($keep)->diff([$child?->parent_id]));
        }
        return !in_array($child?->id, $before, true);
    }

    /** Name keys of the companies the catalog itself lists under Broking (they keep that link). */
    private function catalogBrokers(): array
    {
        return $this->brokers ??= $this->catalogKeys('Fintech, Broking & Market Infrastructure');
    }

    /** Name key => [sector, sub-category] where the catalog first lists the company. */
    private function catalogPlaces(): array
    {
        if ($this->places !== null) return $this->places;
        $this->places = [];
        foreach ($this->catalog->sectors() as $sector) {
            foreach ($sector['subsectors'] as $sub) {
                foreach ($sub['companies'] as $c) $this->places[Company::nameKey($c['name'])] ??= [$sector['name'], $sub['name']];
            }
        }
        return $this->places;
    }

    private function catalogKeys(string $subsector): array
    {
        return collect($this->catalog->sectors())->flatMap(fn ($s) => $s['subsectors'])->where('name', $subsector)
            ->flatMap(fn ($sub) => $sub['companies'])->map(fn ($c) => Company::nameKey($c['name']))->unique()->values()->all();
    }

    private ?array $brokers = null;
    private ?array $places = null;
}
