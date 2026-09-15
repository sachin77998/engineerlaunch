<?php

namespace Database\Seeders;

use App\Models\IndustrialSector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IndustrialSectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [

            [
                'Steel',
                'Steel, rolling, processing and metal manufacturing',
                'steel.jpg',
            ],

            [
                'Forging',
                'Hot forging, cold forging and precision forging',
                'forging.jpg',
            ],

            [
                'Casting & Foundry',
                'Ferrous and non-ferrous casting and foundry operations',
                'casting.jpg',
            ],

            [
                'Gear Manufacturing',
                'Industrial and automotive gear manufacturing',
                'gear.jpg',
            ],

            [
                'Axle Manufacturing',
                'Vehicle axle and axle assembly manufacturing',
                'axle.jpg',
            ],

            [
                'Braking Systems',
                'Brake discs, drums, calipers, pads and braking systems',
                'brakes.jpg',
            ],

            [
                'Automobile',
                'Passenger vehicles, commercial vehicles and two-wheelers',
                'automobile.jpg',
            ],

            [
                'Auto Components',
                'Automotive components and assemblies',
                'auto-components.jpg',
            ],

            [
                'Textiles',
                'Textile manufacturing, spinning, knitting and garments',
                'textile.jpg',
            ],

            [
                'Pharmaceuticals',
                'Pharmaceutical and healthcare manufacturing',
                'pharma.jpg',
            ],

            [
                'Electronics',
                'Electronic components and manufacturing',
                'electronics.jpg',
            ],

            [
                'Electrical Equipment',
                'Electrical equipment and industrial electrical products',
                'electrical.jpg',
            ],

            [
                'CNC & Machining',
                'CNC, VMC, turning, milling and precision machining',
                'cnc.jpg',
            ],

            [
                'Tool Room',
                'Dies, moulds, tools and precision tooling',
                'tool-room.jpg',
            ],

            [
                'Plastic & Injection Moulding',
                'Plastic processing and injection moulding',
                'plastic.jpg',
            ],

            [
                'Rubber',
                'Tyres, rubber components and industrial rubber',
                'rubber.jpg',
            ],

            [
                'Food Processing',
                'Food and beverage manufacturing',
                'food.jpg',
            ],

            [
                'FMCG',
                'Consumer goods manufacturing',
                'fmcg.jpg',
            ],

            [
                'Paper & Packaging',
                'Paper, packaging and printing',
                'packaging.jpg',
            ],

            [
                'Warehouse & Logistics',
                'Warehousing, logistics, transportation and distribution',
                'logistics.jpg',
            ],

            [
                'Chemical & Petrochemical',
                'Chemical and petrochemical manufacturing',
                'chemical.jpg',
            ],

            [
                'Renewable Energy',
                'Solar, wind and energy equipment manufacturing',
                'renewable.jpg',
            ],

            [
                'Battery & EV',
                'Battery, electric vehicle and EV components',
                'ev.jpg',
            ],

            [
                'Aerospace',
                'Aerospace components and manufacturing',
                'aerospace.jpg',
            ],

            [
                'Heavy Engineering',
                'Heavy machinery, industrial equipment and engineering',
                'heavy-engineering.jpg',
            ],

            [
                'Construction Equipment',
                'Construction and earthmoving equipment',
                'construction-equipment.jpg',
            ],

            [
                'Railway Equipment',
                'Railway components and equipment',
                'railway.jpg',
            ],

            [
                'Defence Manufacturing',
                'Defence components and manufacturing',
                'defence.jpg',
            ],

            [
                'Medical Devices',
                'Medical devices and healthcare equipment',
                'medical.jpg',
            ],

            [
                'Tyres',
                'Tyre and rubber product manufacturing',
                'tyres.jpg',
            ],

            [
                'Glass & Ceramics',
                'Glass, ceramics and sanitaryware',
                'ceramics.jpg',
            ],

            [
                'Leather & Footwear',
                'Leather, footwear and related manufacturing',
                'footwear.jpg',
            ],

            [
                'Sports Goods',
                'Sports equipment and sporting goods manufacturing',
                'sports.jpg',
            ],
        ];

        foreach ($sectors as [$name, $description, $image]) {
            IndustrialSector::updateOrCreate(
                [
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'description' => $description,
                    'hero_image' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
