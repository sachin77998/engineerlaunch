<?php

namespace App\Services\Discovery;

/** Maps free text (company name, Wikidata industry, OSM tags, MCA activity) to a catalog sector and subsector. */
class CompanyClassifier
{
    public const FALLBACK = ['Other Industries', 'Discovered Companies'];

    // Ordered: the first matching rule wins, so specific trades come before broad words like "engineering".
    private const RULES = [
        ['\\bforg', 'Auto Components, Forging & Casting', 'Forging'],
        ['(?<![a-z])casting|foundry|die-cast|die cast|technocast|metal melt', 'Auto Components, Forging & Casting', 'Casting & Die Casting (India)'],
        ['bearing', 'Auto Components, Forging & Casting', 'Bearings'],
        ['fastener|bolt|screws|rivet', 'Auto Components, Forging & Casting', 'Fasteners'],
        ['wiring|harness|auto electric', 'Auto Components, Forging & Casting', 'Electricals, Wiring & Electronics'],
        ['tractor|harvester|agricultural machine|farm equipment', 'Automobile Manufacturers', 'Tractors & Farm Machinery'],
        ['motorcycle|scooter|two-wheeler|two wheeler|bicycle|cycles', 'Automobile Manufacturers', 'Two-Wheelers & Three-Wheelers'],
        ['truck|commercial vehicle|\bbus\b|coach', 'Automobile Manufacturers', 'Commercial Vehicles & Buses'],
        ['auto part|automotive_parts|auto component|automotive component|axle|gear|steering|brake|clutch|piston|radiator|shock absorber|diesel component|engine component|rubber component|automotive', 'Auto Components, Forging & Casting', 'Gears, Steering, Brakes & Systems'],
        ['automobile|automaker|car manufactur|motor vehicle|motors\b', 'Automobile Manufacturers', 'Passenger Cars'],
        ['stainless|alumin|zinc|copper|non-ferrous|brass', 'Steel, Metals & Pipes', 'Stainless Steel & Non-Ferrous Metals'],
        ['\\bpipes?\\b|\\btubes?\\b|tubular', 'Steel, Metals & Pipes', 'Pipes & Tubes'],
        ['steel|rolling mill|tmt|\biron\b|ispat|metals', 'Steel, Metals & Pipes', 'TMT Bars & Secondary Steel'],
        ['cement', 'Cement, Building Materials & Polymers', 'Cement'],
        ['paint|adhesive|plywood|laminate|construction chemical', 'Cement, Building Materials & Polymers', 'Paints, Panels & Construction Chemicals'],
        ['paper|pulp|corrugat|carton|packag', 'Cement, Building Materials & Polymers', 'Paper, Pulp & Packaging'],
        ['plastic|polymer|rubber|tyre|petrochemical|resin', 'Cement, Building Materials & Polymers', 'Polymers & Petrochemicals'],
        ['ayurved|herbal', 'Pharmaceuticals & Healthcare', 'Ayurvedic & Herbal'],
        ['pharma|drug|formulation|laborator|lifescience|life science|biotech|medic|healthcare|hospital|diagnostic|surgical', 'Pharmaceuticals & Healthcare', 'Formulations & Contract Manufacturing'],
        ['dairy|milk|ghee', 'Food & Beverages', 'Dairy, Ghee & Milk Products'],
        ['ice cream|frozen dessert', 'Food & Beverages', 'Ice Cream & Frozen Desserts'],
        ['biscuit|bakery|bread', 'Food & Beverages', 'Biscuits & Bakery'],
        ['snack|namkeen|sweets|chips', 'Food & Beverages', 'Snacks, Namkeen & Sweets'],
        ['beverage|soft drink|juice|bottl|brewer|distill', 'Food & Beverages', 'Beverages & Soft Drinks'],
        ['edible oil|refined oil|oil mill|solvent extraction', 'Food & Beverages', 'Edible Oil & Refined Oil'],
        ['\brice\b|flour|atta|\bdal\b|pulses|spice|grain|mill(s)?\b.*food', 'Food & Beverages', 'Atta, Besan, Sooji & Rice'],
        ['food|confection|chocolate|noodle', 'Food & Beverages', 'Packaged Foods & Confectionery'],
        ['beauty|cosmetic|salon|skincare|skin care|makeup', 'FMCG - Personal & Home Care', 'Beauty & Cosmetics'],
        ['soap|detergent|personal care|toiletr|shampoo|toothpaste|fmcg|consumer goods', 'FMCG - Personal & Home Care', 'Soaps & Body Wash'],
        ['footwear|shoe', 'Apparel, Textiles & Footwear', 'Footwear'],
        ['sport', 'Apparel, Textiles & Footwear', 'Sportswear & Sports Goods'],
        ['spinning|yarn|cotton|fabric|textile|weav|denim|dyeing', 'Apparel, Textiles & Footwear', 'Textiles, Cotton & Yarn'],
        ['knit|hosiery|garment|apparel|fashion|clothing|woollen|woolen', 'Apparel, Textiles & Footwear', 'Denim & Casual Wear'],
        ['hotel|resort|hospitality', 'Restaurants, Hotels & Travel', 'Hotels & Resorts'],
        ['restaurant|food service|cafe|pizza|burger', 'Restaurants, Hotels & Travel', 'Quick Service Restaurants (QSR)'],
        ['airline|aviation|airport', 'Restaurants, Hotels & Travel', 'Airlines & Aviation'],
        ['travel|tourism', 'Restaurants, Hotels & Travel', 'Travel & Tourism'],
        ['life insurance', 'Insurance', 'Life Insurance'],
        ['health insurance', 'Insurance', 'Health Insurance'],
        ['insurance|assurance', 'Insurance', 'General Insurance'],
        ['broking|broker|stock|securities|mutual fund|asset management|registrar|depositor|fintech|payment|wealth|exchange', 'Banking & Financial Services', 'Fintech, Broking & Market Infrastructure'],
        ['bank', 'Banking & Financial Services', 'Indian Banks'],
        ['semiconductor|chip|integrated circuit', 'Semiconductors & Electronics', 'Semiconductors & Chip Makers'],
        ['electronic|pcb|mobile phone|smartphone', 'Semiconductors & Electronics', 'Electronics Manufacturing (EMS)'],
        ['appliance|air condition|refrigerat|washing machine', 'Electrical Equipment & Appliances', 'Consumer Appliances & Air Conditioning'],
        ['electrical|switchgear|switch|cable|wire|transformer|lighting|\bled\b|\bfan\b|inverter', 'Electrical Equipment & Appliances', 'Electrical Equipment, Switches & Cables'],
        ['solar', 'Oil, Gas & Energy', 'Solar Panel Manufacturing'],
        ['batter', 'Oil, Gas & Energy', 'Batteries & Energy Storage'],
        ['renewable|wind energy|power generation|electric utility|power plant', 'Oil, Gas & Energy', 'Renewable Power'],
        ['petroleum|refiner|\boil\b', 'Oil, Gas & Energy', 'Oil Refining & Marketing'],
        ['natural gas|city gas|\bgas\b|lpg|cng', 'Oil, Gas & Energy', 'Natural Gas & City Gas'],
        ['logistic|courier|freight|shipping|transport|warehous|cargo', 'Logistics & Supply Chain', 'Courier, Express & Freight'],
        ['fertili|seed|agro|crop|irrigation|pesticide|agricultur', 'Agriculture & Agri-Inputs', 'Fertilisers, Seeds & Crop Protection'],
        ['edtech|e-learning|online learning|test prep', 'Education & Training', 'EdTech'],
        ['education|universit|school|institute|college', 'Education & Training', 'Universities & Institutes'],
        ['e-commerce|ecommerce|internet|online retail|marketplace|social media|search engine', 'IT Services & Software', 'Product & Internet Companies'],
        ['software|information technology|it service|it consult|saas|cloud|technolog|infotech|informatic|digital|cyber|data|analytics|computer', 'IT Services & Software', 'Indian IT Services'],
        ['consult|audit|accounting|advisory', 'IT Services & Software', 'Global IT Services & Consulting'],
        ['machine|machinery|engineering|fabricat|hydraulic|press|tool|pump|valve|compressor|boiler|crane', 'Heavy Engineering & Capital Goods', 'Machine Tools & Industrial Machinery'],
        ['aerospace|weapon|defen[cs]e|shipbuild|shipyard|missile|ordnance', 'Heavy Engineering & Capital Goods', 'Aerospace, Defence & Shipbuilding'],
        ['electricity|electric power|energy supply|power transmission|power distribution', 'Oil, Gas & Energy', 'Power Generation & Utilities'],
        ['gastronomy|caf[eé]|coffee|tea house', 'Restaurants, Hotels & Travel', 'Quick Service Restaurants (QSR)'],
        ['market research|business process|outsourc|bpo|staffing|recruit', 'IT Services & Software', 'Global IT Services & Consulting'],
        ['postal|mobility', 'Logistics & Supply Chain', 'Courier, Express & Freight'],
        ['stationery|pen|pencil|office supplies', 'FMCG - Personal & Home Care', 'Stationery & Office Products'],
        ['telecom|mobile network|broadband|internet service provider', 'Media, Telecom & Entertainment', 'Telecom'],
        ['media|film|television|broadcast|news|publish|entertainment|music|newspaper|advertis|gaming|animation', 'Media, Telecom & Entertainment', 'Media & Entertainment'],
        ['real estate|property|construction|infrastructure|housing|builder|developer of|architecture', 'Real Estate & Construction', 'Real Estate & Infrastructure'],
        ['conglomerate|holding company|investment|venture capital|private equity|finance|financial|loan|nbfc|credit', 'Banking & Financial Services', 'Fintech, Broking & Market Infrastructure'],
        ['mining|coal|mineral', 'Steel, Metals & Pipes', 'Stainless Steel & Non-Ferrous Metals'],
        ['chemical|dye|pigment|fertiliser|explosive', 'Cement, Building Materials & Polymers', 'Polymers & Petrochemicals'],
        ['retail|supermarket|department store', 'Apparel, Textiles & Footwear', 'Denim & Casual Wear'],
    ];

    /** @return array{0:string,1:string} [sector, subsector] */
    public function classify(string ...$texts): array
    {
        $text = mb_strtolower(implode(' ', array_filter($texts)));
        $text = str_replace('_', ' ', $text);
        foreach (self::RULES as [$pattern, $sector, $subsector]) {
            if (preg_match('~' . $pattern . '~u', $text)) return [$sector, $subsector];
        }
        return self::FALLBACK;
    }

    public function isFallback(array $match): bool
    {
        return $match === self::FALLBACK;
    }
}
