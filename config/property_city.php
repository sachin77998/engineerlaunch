<?php

use App\Services\Property\CitySections as S;

// City landing page (/real-estate/{city}). Blocks render top to bottom in each column; reorder, remove or add a block here.
// An entry is a section class or [class, options]. Hidden automatically when it has nothing real to show.
return [
    'whatsapp_number' => env('PROPERTY_WHATSAPP_NUMBER'), // "Post via WhatsApp" link; hidden when empty
    'sections' => [
        'top' => [[S\FeaturedBanner::class, ['limit' => 5]], S\SearchBox::class],
        'main' => [
            [S\ContinueBrowsing::class, ['title' => 'Recently viewed properties']],
            [S\Recommended::class, ['title' => 'Recommended properties']],
            [S\HomeTypes::class, ['title' => 'Homes by type', 'listing' => 'sale', 'mode' => 'buy']],
            [S\ProjectRail::class, ['order' => 'demand', 'title' => 'Most viewed projects', 'sub' => 'Last 30 days']],
            [S\ProjectRail::class, ['order' => 'featured', 'title' => 'Featured projects', 'sub' => 'Selected by our team']],
            [S\ProjectRail::class, ['order' => 'new', 'title' => 'Newly launched projects', 'sub' => 'Launched in the last 12 months']],
            [S\LocalityInsights::class, ['title' => 'Locality insights', 'limit' => 8]],
            [S\SearchDemand::class, ['title' => 'Demand in', 'min_searches' => 5]],
            [S\ProjectOffers::class, ['title' => 'Builder offers']],
            [S\PopularBuilders::class, ['title' => 'Popular builders', 'limit' => 12]],
            [S\BhkChoice::class, ['title' => 'Browse by bedrooms']],
            [S\PostedBy::class, ['title' => 'Listings by advertiser']],
            [S\PossessionDate::class, ['title' => 'Projects by possession date']],
            [S\RateLocality::class, ['title' => 'Rate where you live']],
        ],
        'side' => [
            S\GuestActivity::class,
            [S\PostPropertyCta::class, ['compact' => true]],
        ],
        'bottom' => [
            [S\Articles::class, ['title' => 'Property news and guides',
                'tabs' => ['News' => 'real-estate', 'Tax & legal' => 'property-tax-legal', 'Help guides' => 'home-buying-guides', 'Investment' => 'real-estate-investment'],
                'aliases' => ['Bengaluru' => ['Bangalore'], 'Gurugram' => ['Gurgaon'], 'Mumbai' => ['Bombay']]]],
            S\PostPropertyCta::class,
            [S\ExploreServices::class, ['title' => 'Our property services', 'items' => [
                ['Buy commercial', 'Offices, shops, showrooms and warehouses for sale', '🏢', 'properties.search', ['mode' => 'commercial-buy']],
                ['Lease commercial', 'Offices, shops and warehouses on rent', '🏬', 'properties.search', ['mode' => 'commercial-rent']],
                ['Plots and land', 'Residential plots and farm land for sale', '🌾', 'properties.search', ['mode' => 'buy', 'type' => ['plot']]],
                ['Homes on rent', 'Flats, houses and builder floors to rent', '🛋️', 'properties.search', ['mode' => 'rent']],
                ['PG and co-living', 'PGs and flatmates posted by owners', '🛏️', 'properties.search', ['mode' => 'rent', 'kind' => 'pg']],
                ['List your property', 'Free ad, buyers and tenants contact you directly', '📣', 'properties.post'],
                ['Home loan', 'Compare lenders and apply', '🏦', 'home-loan.index'],
                ['Rent agreement', 'Online, delivered to your door', '📝', 'rent-agreement.index'],
                ['Legal services', 'Title checks and documentation', '⚖️', 'legal.index'],
                ['Interiors', 'Design and execution', '🎨', 'interiors.index'],
                ['Home services', 'Painting, cleaning, repairs', '🧰', 'home-services.index'],
                ['Pay rent', 'By credit card', '💳', 'pay-rent.index'],
            ]]],
            [S\TopCities::class, ['title' => 'Popular cities', 'limit' => 12]],
        ],
    ],
];
