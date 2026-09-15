<?php

namespace Database\Seeders;

use App\Models\IndustrialProcess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IndustrialProcessSeeder extends Seeder
{
    public function run(): void
    {
        $processes = [

            // CASTING

            'Sand Casting',
            'Gravity Die Casting',
            'Pressure Die Casting',
            'Investment Casting',
            'Shell Moulding',
            'Green Sand Moulding',
            'No-Bake Moulding',
            'Core Making',
            'Core Shooting',
            'Core Assembly',
            'Melting',
            'Furnace Operation',
            'Pouring',
            'Fettling',
            'Shot Blasting',
            'Casting Inspection',
            'Casting Repair',

            // FORGING

            'Hot Forging',
            'Cold Forging',
            'Warm Forging',
            'Closed Die Forging',
            'Open Die Forging',
            'Precision Forging',
            'Ring Rolling',
            'Upsetting',
            'Heat Treatment',
            'Normalising',
            'Annealing',
            'Hardening',
            'Tempering',

            // MACHINING

            'CNC Turning',
            'CNC Milling',
            'VMC Machining',
            'HMC Machining',
            'Grinding',
            'Gear Grinding',
            'Gear Hobbing',
            'Gear Shaping',
            'Gear Broaching',
            'Gear Honing',
            'Turning',
            'Milling',
            'Drilling',
            'Boring',
            'Threading',
            'Deburring',
            'Lapping',
            'Polishing',

            // TOOLING

            'Die Making',
            'Die Maintenance',
            'Mould Making',
            'Mould Maintenance',
            'Pattern Making',
            'Pattern Maintenance',
            'Tool Design',
            'Tool Room',
            'Jig & Fixture',
            'Press Tool',
            'Progressive Die',
            'Plastic Mould',
            'Injection Mould',

            // AUTOMOBILE

            'Axle Assembly',
            'Brake Assembly',
            'Brake Disc Manufacturing',
            'Brake Drum Manufacturing',
            'Caliper Manufacturing',
            'Clutch Manufacturing',
            'Transmission Assembly',
            'Engine Assembly',
            'Powertrain',
            'Chassis Assembly',
            'Suspension',
            'Steering',
            'Wheel Manufacturing',
            'Tyre Manufacturing',
            'Battery Assembly',
            'EV Motor Assembly',

            // STEEL

            'Steel Melting',
            'Billet Casting',
            'Continuous Casting',
            'Hot Rolling',
            'Cold Rolling',
            'Wire Rod',
            'Bar Mill',
            'Plate Mill',
            'Tube Manufacturing',
            'Steel Slitting',
            'Pickling',
            'Galvanising',
            'Coating',
            'Steel Inspection',

            // TEXTILE

            'Spinning',
            'Weaving',
            'Knitting',
            'Dyeing',
            'Printing',
            'Garment Manufacturing',
            'Cutting',
            'Stitching',
            'Finishing',
            'Textile Testing',
            'Packing',

            // ELECTRICAL

            'Electrical Assembly',
            'Panel Manufacturing',
            'Motor Manufacturing',
            'Transformer Manufacturing',
            'Switchgear Manufacturing',
            'Cable Manufacturing',
            'Testing & Calibration',

            // PLASTIC

            'Injection Moulding',
            'Blow Moulding',
            'Extrusion',
            'Plastic Assembly',
            'Plastic Inspection',

            // QUALITY

            'Incoming Inspection',
            'In-Process Inspection',
            'Final Inspection',
            'Dimensional Inspection',
            'CMM Inspection',
            'NDT',
            'Ultrasonic Testing',
            'Magnetic Particle Testing',
            'Dye Penetrant Testing',
            'Material Testing',
            'Metallurgical Testing',

            // MAINTENANCE

            'Preventive Maintenance',
            'Breakdown Maintenance',
            'Predictive Maintenance',
            'Machine Maintenance',
            'Electrical Maintenance',
            'Mechanical Maintenance',
            'Hydraulic Maintenance',
            'Pneumatic Maintenance',
            'PLC Maintenance',
            'Automation Maintenance',

            // LOGISTICS

            'Warehouse Operations',
            'Material Handling',
            'Forklift Operations',
            'Dispatch',
            'Loading',
            'Unloading',
            'Inventory Management',
            'Logistics Planning',
            'Transport Operations',

            // SUPPORT

            'Purchase',
            'Procurement',
            'Stores',
            'Accounts',
            'HR',
            'Administration',
            'Security',
            'Housekeeping',
            'Facility Management',
        ];

        foreach ($processes as $process) {
            IndustrialProcess::updateOrCreate(
                [
                    'slug' => Str::slug($process),
                ],
                [
                    'name' => $process,
                    'is_active' => true,
                ]
            );
        }
    }
}
