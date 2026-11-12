<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;

// Post-property banner: post a free ad (options.compact for the sidebar card).
class PostPropertyCta extends CitySection
{
    public int $minutes = 0;

    public function data(CityContext $c, array $options): ?array
    {
        $wa = config('property_city.whatsapp_number');
        return ['whatsapp' => $wa ? 'https://wa.me/' . preg_replace('/\D/', '', $wa) . '?text=' . rawurlencode('I want to post my property in ' . $c->city->name) : null];
    }
}
