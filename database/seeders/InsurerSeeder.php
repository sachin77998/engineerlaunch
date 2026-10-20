<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * IRDAI-registered insurers for the insurance advisor: names, sector, products and websites.
 * Claim settlement ratios and other figures are entered by the owner from the IRDAI annual report
 * (/admin/insurers); existing figures are never overwritten here.
 */
class InsurerSeeder extends Seeder
{
    public function run(): void
    {
        $insurers = [
            // Life insurers (term plans)
            ['Life Insurance Corporation of India (LIC)', 'life', 'term', 'https://licindia.in', true],
            ['HDFC Life', 'life', 'term', 'https://www.hdfclife.com', false],
            ['ICICI Prudential Life', 'life', 'term', 'https://www.iciciprulife.com', false],
            ['SBI Life', 'life', 'term', 'https://www.sbilife.co.in', false],
            ['Axis Max Life', 'life', 'term', 'https://www.axismaxlife.com', false],
            ['Tata AIA Life', 'life', 'term', 'https://www.tataaia.com', false],
            ['Bajaj Allianz Life', 'life', 'term', 'https://www.bajajallianzlife.com', false],
            ['Kotak Life', 'life', 'term', 'https://www.kotaklife.com', false],
            ['Aditya Birla Sun Life Insurance', 'life', 'term', 'https://lifeinsurance.adityabirlacapital.com', false],
            ['PNB MetLife', 'life', 'term', 'https://www.pnbmetlife.com', false],
            ['Canara HSBC Life', 'life', 'term', 'https://www.canarahsbclife.com', false],
            ['Edelweiss Life', 'life', 'term', 'https://www.edelweisslife.in', false],
            ['Bandhan Life', 'life', 'term', 'https://www.bandhanlife.com', false],
            ['IndiaFirst Life', 'life', 'term', 'https://www.indiafirstlife.com', false],
            ['Star Union Dai-ichi Life', 'life', 'term', 'https://www.sudlife.in', false],
            ['Ageas Federal Life', 'life', 'term', 'https://www.ageasfederal.com', false],
            ['Pramerica Life', 'life', 'term', 'https://www.pramericalife.in', false],
            ['Shriram Life', 'life', 'term', 'https://www.shriramlife.com', false],
            ['Future Generali India Life', 'life', 'term', 'https://life.futuregenerali.in', false],
            ['Aviva Life', 'life', 'term', 'https://www.avivaindia.com', false],
            // Standalone health insurers
            ['Star Health', 'health', 'health', 'https://www.starhealth.in', false],
            ['Niva Bupa', 'health', 'health', 'https://www.nivabupa.com', false],
            ['Care Health', 'health', 'health', 'https://www.careinsurance.com', false],
            ['Aditya Birla Health', 'health', 'health', 'https://www.adityabirlacapital.com/healthinsurance', false],
            ['ManipalCigna Health', 'health', 'health', 'https://www.manipalcigna.com', false],
            ['Galaxy Health', 'health', 'health', 'https://www.galaxyhealth.com', false],
            ['Narayana Health Insurance', 'health', 'health', 'https://www.narayanahealth.insurance', false],
            // General insurers (health, car, bike)
            ['ICICI Lombard', 'general', 'health,car,bike', 'https://www.icicilombard.com', false],
            ['HDFC ERGO', 'general', 'health,car,bike', 'https://www.hdfcergo.com', false],
            ['Bajaj Allianz General', 'general', 'health,car,bike', 'https://www.bajajallianz.com', false],
            ['Tata AIG', 'general', 'health,car,bike', 'https://www.tataaig.com', false],
            ['Go Digit', 'general', 'health,car,bike', 'https://www.godigit.com', false],
            ['Acko', 'general', 'health,car,bike', 'https://www.acko.com', false],
            ['SBI General', 'general', 'health,car,bike', 'https://www.sbigeneral.in', false],
            ['Reliance General', 'general', 'health,car,bike', 'https://www.reliancegeneral.co.in', false],
            ['Zurich Kotak General', 'general', 'health,car,bike', 'https://www.zurichkotak.com', false],
            ['Cholamandalam MS General', 'general', 'health,car,bike', 'https://www.cholainsurance.com', false],
            ['Royal Sundaram General', 'general', 'health,car,bike', 'https://www.royalsundaram.in', false],
            ['IFFCO Tokio General', 'general', 'health,car,bike', 'https://www.iffcotokio.co.in', false],
            ['Future Generali India Insurance', 'general', 'health,car,bike', 'https://general.futuregenerali.in', false],
            ['Universal Sompo General', 'general', 'health,car,bike', 'https://www.universalsompo.com', false],
            ['Liberty General', 'general', 'health,car,bike', 'https://www.libertyinsurance.in', false],
            ['Shriram General', 'general', 'car,bike', 'https://www.shriramgi.com', false],
            ['Zuno General', 'general', 'health,car,bike', 'https://www.hizuno.com', false],
            ['Navi General', 'general', 'health', 'https://navi.com', false],
            ['New India Assurance', 'general', 'health,car,bike', 'https://www.newindia.co.in', true],
            ['United India Insurance', 'general', 'health,car,bike', 'https://uiic.co.in', true],
            ['The Oriental Insurance Company', 'general', 'health,car,bike', 'https://orientalinsurance.org.in', true],
            ['National Insurance Company', 'general', 'health,car,bike', 'https://nationalinsurance.nic.co.in', true],
        ];
        $now = now();
        foreach ($insurers as [$name, $sector, $products, $website, $psu]) {
            DB::table('insurers')->updateOrInsert(['name' => $name], ['slug' => Str::slug($name), 'sector' => $sector, 'products' => $products,
                'website' => $website, 'is_public_sector' => $psu, 'updated_at' => $now] + (DB::table('insurers')->where('name', $name)->exists() ? [] : ['created_at' => $now, 'is_active' => true]));
        }
    }
}
