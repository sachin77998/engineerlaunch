<?php

namespace Database\Seeders;

use App\Models\IndustrialAsset;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialProcess;
use App\Models\IndustrialSector;
use App\Models\IndustrialSubsector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IndustrialTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([IndustrialSectorSeeder::class, IndustrialProcessSeeder::class, IndustrialCareerDictionarySeeder::class]);
        foreach (['automobile' => ['Auto Components/Transmission/Gears' => ['Gear Hobbing', 'Gear Shaping', 'Heat Treatment', 'Gear Grinding', 'Gear Honing', 'Dimensional Inspection'], 'Chassis/Axle' => ['Axle Assembly', 'Chassis Assembly', 'Final Inspection'], 'Braking System/Brake Disc' => ['Brake Disc Manufacturing', 'CNC Turning', 'Final Inspection'], 'Braking System/Brake Caliper' => ['Caliper Manufacturing', 'CNC Milling', 'Final Inspection'], 'Braking System/Brake Pads' => ['Brake Pad Manufacturing', 'Brake Assembly', 'Final Inspection'], 'Vehicle Manufacturing' => ['Axle Assembly', 'Brake Assembly', 'Brake Disc Manufacturing', 'Brake Drum Manufacturing', 'Caliper Manufacturing', 'Clutch Manufacturing', 'Transmission Assembly', 'Engine Assembly', 'Powertrain', 'Chassis Assembly', 'Suspension', 'Steering', 'Wheel Manufacturing', 'Tyre Manufacturing', 'Battery Assembly', 'EV Motor Assembly']], 'auto-components' => ['Transmission/Gears' => ['Gear Hobbing', 'Gear Shaping', 'Gear Grinding'], 'Chassis/Axle' => ['Axle Assembly', 'Final Inspection'], 'Braking System' => ['Brake Assembly', 'Final Inspection']], 'casting-foundry' => ['Sand Casting' => ['Moulding', 'Core Making', 'Melting', 'Pouring', 'Fettling', 'Casting Inspection'], 'Die Casting' => ['Pressure Die Casting', 'Deburring', 'Casting Inspection'], 'Investment Casting' => ['Investment Casting', 'Heat Treatment', 'Casting Inspection'], 'Foundry Operations' => ['Sand Casting', 'Gravity Die Casting', 'Pressure Die Casting', 'Investment Casting', 'Shell Moulding', 'Green Sand Moulding', 'No-Bake Moulding', 'Core Making', 'Core Shooting', 'Core Assembly', 'Melting', 'Furnace Operation', 'Pouring', 'Fettling', 'Shot Blasting', 'Casting Inspection', 'Casting Repair']], 'forging' => ['Hot Forging/Closed Die Forging' => ['Hot Forging', 'Closed Die Forging', 'Heat Treatment', 'Machining', 'Final Inspection'], 'Hot Forging/Open Die Forging' => ['Hot Forging', 'Open Die Forging', 'Heat Treatment', 'Final Inspection'], 'Cold Forging' => ['Cold Forging', 'Final Inspection'], 'Forging Operations' => ['Hot Forging', 'Cold Forging', 'Warm Forging', 'Closed Die Forging', 'Open Die Forging', 'Precision Forging', 'Ring Rolling', 'Upsetting', 'Heat Treatment', 'Normalising', 'Annealing', 'Hardening', 'Tempering']], 'gear-manufacturing' => ['Transmission/Gears' => ['Gear Hobbing', 'Gear Shaping', 'Heat Treatment', 'Gear Grinding', 'Gear Honing', 'Dimensional Inspection']], 'axle-manufacturing' => ['Chassis/Axle' => ['CNC Turning', 'Heat Treatment', 'Axle Assembly', 'Final Inspection']], 'braking-systems' => ['Brake Disc' => ['Brake Disc Manufacturing', 'CNC Turning', 'Final Inspection'], 'Brake Caliper' => ['Caliper Manufacturing', 'CNC Milling', 'Final Inspection'], 'Brake Pads' => ['Brake Pad Manufacturing', 'Brake Assembly', 'Final Inspection']], 'steel' => ['Steelmaking' => ['Steel Melting', 'Continuous Casting', 'Steel Inspection'], 'Rolling Mills' => ['Hot Rolling', 'Cold Rolling', 'Steel Slitting', 'Steel Inspection'], 'Steel Processing' => ['Steel Melting', 'Billet Casting', 'Continuous Casting', 'Hot Rolling', 'Cold Rolling', 'Wire Rod', 'Bar Mill', 'Plate Mill', 'Tube Manufacturing', 'Steel Slitting', 'Pickling', 'Galvanising', 'Coating', 'Steel Inspection']], 'cnc-machining' => ['Precision Machining' => ['CNC Turning', 'CNC Milling', 'VMC Machining', 'HMC Machining', 'Grinding', 'CMM Inspection'], 'Machining Operations' => ['CNC Turning', 'CNC Milling', 'VMC Machining', 'HMC Machining', 'Grinding', 'Gear Grinding', 'Gear Hobbing', 'Gear Shaping', 'Gear Broaching', 'Gear Honing', 'Turning', 'Milling', 'Drilling', 'Boring', 'Threading', 'Deburring', 'Lapping', 'Polishing']], 'tool-room' => ['Tooling Operations' => ['Die Making', 'Die Maintenance', 'Mould Making', 'Mould Maintenance', 'Pattern Making', 'Pattern Maintenance', 'Tool Design', 'Tool Room', 'Jig & Fixture', 'Press Tool', 'Progressive Die', 'Plastic Mould', 'Injection Mould']], 'textiles' => ['Textile Production' => ['Spinning', 'Weaving', 'Knitting', 'Dyeing', 'Printing', 'Garment Manufacturing', 'Cutting', 'Stitching', 'Finishing', 'Textile Testing', 'Packing']], 'electrical-equipment' => ['Electrical Production' => ['Electrical Assembly', 'Panel Manufacturing', 'Motor Manufacturing', 'Transformer Manufacturing', 'Switchgear Manufacturing', 'Cable Manufacturing', 'Testing & Calibration']], 'plastic-injection-moulding' => ['Plastic Processing' => ['Injection Moulding', 'Blow Moulding', 'Extrusion', 'Plastic Assembly', 'Plastic Inspection']], 'heavy-engineering' => ['Quality & Testing' => ['Incoming Inspection', 'In-Process Inspection', 'Final Inspection', 'Dimensional Inspection', 'CMM Inspection', 'NDT', 'Ultrasonic Testing', 'Magnetic Particle Testing', 'Dye Penetrant Testing', 'Material Testing', 'Metallurgical Testing'], 'Maintenance' => ['Preventive Maintenance', 'Breakdown Maintenance', 'Predictive Maintenance', 'Machine Maintenance', 'Electrical Maintenance', 'Mechanical Maintenance', 'Hydraulic Maintenance', 'Pneumatic Maintenance', 'PLC Maintenance', 'Automation Maintenance'], 'Plant Support' => ['Purchase', 'Procurement', 'Stores', 'Accounts', 'HR', 'Administration', 'Security', 'Housekeeping', 'Facility Management']], 'warehouse-logistics' => ['Logistics Operations' => ['Warehouse Operations', 'Material Handling', 'Forklift Operations', 'Dispatch', 'Loading', 'Unloading', 'Inventory Management', 'Logistics Planning', 'Transport Operations']]] as $sectorSlug => $paths) {
            $sector = IndustrialSector::where('slug', $sectorSlug)->firstOrFail();
            foreach ($paths as $path => $steps) {
                $parent = null;
                $slug = $sectorSlug;
                foreach (explode('/', $path) as $name) {
                    $slug .= '-'.Str::slug($name);
                    $node = IndustrialSubsector::updateOrCreate(['slug' => $slug], ['sector_id' => $sector->id, 'parent_id' => $parent, 'name' => $name, 'is_active' => true]);
                    $parent = $node->id;
                }
                foreach ($steps as $order => $name) {
                    $process = IndustrialProcess::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]);
                    $node->processes()->syncWithoutDetaching([$process->id => ['sort_order' => $order]]);
                }
            }
        }
        foreach (['Core Making' => ['Core Maker', 'Core Inspector', 'Core Shop Technician'], 'Melting' => ['Melter', 'Furnace Operator', 'Furnace Engineer'], 'Moulding' => ['Mould Master Operator', 'Moulding Operator', 'Sand Plant Operator'], 'Fettling' => ['Fettling Operator', 'Fettling Technician'], 'CNC Turning' => ['CNC Operator', 'CNC Programmer', 'CNC Setter', 'Turning Operator'], 'Gear Hobbing' => ['Gear Hobbing Operator'], 'Gear Grinding' => ['Gear Grinding Operator'], 'CMM Inspection' => ['CMM Operator', 'CMM Programmer'], 'Die Making' => ['Die Maker', 'Tooling Inspector'], 'Machine Maintenance' => ['Maintenance Engineer', 'Maintenance Technician'], 'Warehouse Operations' => ['Warehouse Executive', 'Warehouse Supervisor', 'Warehouse Manager'], 'Final Inspection' => ['Quality Inspector', 'Final Quality Inspector'], 'Pouring' => ['Pouring Operator'], 'Material Testing' => ['Material Testing Technician', 'Lab Technician']] as $name => $roles) {
            $process = IndustrialProcess::where('slug', Str::slug($name))->firstOrFail();
            foreach ($roles as $role) {
                $record = IndustrialJobRole::where('slug', Str::slug($role))->firstOrFail();
                $process->roles()->syncWithoutDetaching([$record->id]);
            }
        }
        foreach (['steel' => ['steel', 7, 1], 'forging' => ['forging', 7, 2], 'casting-foundry' => ['casting', 6, 1], 'gear-manufacturing' => ['gear', 7, 6], 'axle-manufacturing' => ['axle', 6, 2], 'braking-systems' => ['brake', 6, 5]] as $slug => [$prefix,$count,$hero]) {
            $sector = IndustrialSector::where('slug', $slug)->firstOrFail();
            $sector->update(['family' => in_array($prefix, ['steel', 'forging', 'casting']) ? 'Metal' : 'Automobile', 'hero_image' => "images/industries/$prefix/$prefix$hero.jpeg"]);
            for ($i = 1; $i <= $count; $i++) {
                IndustrialAsset::updateOrCreate(['path' => "images/industries/$prefix/$prefix$i.jpeg"], ['sector_id' => $sector->id, 'alt' => $sector->name.' - production photograph '.$i, 'source_name' => 'User-supplied category photograph', 'sort_order' => $i]);
            }
        }
        $this->call(IndustrialCareerProfileSeeder::class);
    }
}
