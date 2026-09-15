<?php

namespace Database\Seeders;

use App\Models\IndustrialDepartment;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialJobRoleAlias;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IndustrialCareerDictionarySeeder extends Seeder
{
    public function run(): void
    {
        $groups = ['Foundry' => 'Mould Master Operator|Moulding Operator|Moulding Technician|Moulding Supervisor|Sand Plant Operator|Sand Plant Technician|Core Maker|Core Making Operator|Core Shooter Operator|Core Shop Technician|Core Inspector|Core Assembly Operator|Melter|Furnace Operator|Furnace Engineer|Furnace Technician|Pouring Operator|Fettling Operator|Fettling Technician|Shot Blasting Operator|Casting Inspector|Foundry Inspector|Foundry Technician|Foundry Engineer|Foundry Supervisor|Pattern Maker|Pattern Technician|Pattern Inspector|Casting Quality Engineer|Metallurgist|Metallurgical Technician|Lab Attendant|Foundry Lab Technician|Material Testing Technician|Mason', 'Machine Shop' => 'CNC Operator|CNC Machine Operator|CNC Programmer|CNC Setter|CNC Setter Operator|VMC Operator|VMC Programmer|VMC Setter|HMC Operator|HMC Programmer|Turning Operator|Turner|Milling Operator|Milling Man|Grinding Operator|Gear Grinding Operator|Gear Hobbing Operator|Gear Shaping Operator|Gear Honing Operator|Broaching Operator|Machine Operator|Machining Technician|Machining Engineer|Production Machining Engineer|CNC Maintenance Technician|Machine Shop Supervisor', 'Tool Room' => 'Tool Room Operator|Tool Room Technician|Tool Room Engineer|Tool Room Supervisor|Die Maker|Die Maker Trainee|Die Maintenance Engineer|Die Maintenance Technician|Die Design Engineer|Press Tool Engineer|Press Tool Maker|Mould Maker|Mould Maintenance Technician|Mould Maintenance Engineer|Tool Designer|Tooling Engineer|Tooling Inspector|Tooling Quality Inspector|Jig Fixture Designer|Jig Fixture Maker|Pattern Maker|Pattern Maintenance Technician', 'Maintenance' => 'Maintenance Engineer|Maintenance Supervisor|Maintenance Technician|Maintenance Fitter|Mechanical Fitter|Machine Maintenance Engineer|Machine Maintenance Technician|Electrical Maintenance Engineer|Electrical Maintenance Technician|Electrician|Industrial Electrician|PLC Technician|PLC Engineer|Automation Engineer|Automation Technician|Hydraulic Technician|Pneumatic Technician|Mechanical Maintenance Engineer|Diesel Mechanic|Automobile Mechanic|Boiler Technician|Compressor Technician|Utility Technician|Utility Engineer', 'Quality' => 'Quality Inspector|Quality Engineer|Quality Technician|Quality Supervisor|Casting Inspector|Core Inspector|Tooling Inspector|Incoming Quality Inspector|Process Quality Inspector|Final Quality Inspector|Supplier Quality Engineer|Customer Quality Engineer|Metrology Technician|CMM Operator|CMM Programmer|NDT Technician|NDT Inspector|UT Technician|MPI Technician|DPT Technician|Material Testing Technician|Lab Technician|Lab Attendant|Metallurgical Technician', 'Production' => 'Production Operator|Production Technician|Production Engineer|Production Supervisor|Production Manager|Assembly Operator|Assembly Technician|Line Operator|Line Supervisor|Shift Supervisor|Plant Operator|Machine Operator|Process Technician|Process Engineer|Manufacturing Engineer|Industrial Engineer|Lean Manufacturing Engineer|Process Improvement Engineer', 'Warehouse' => 'Warehouse Executive|Warehouse Supervisor|Warehouse Manager|Store Keeper|Store Executive|Store Engineer|Inventory Executive|Inventory Controller|Dispatch Executive|Dispatch Supervisor|Loading Supervisor|Material Handler|Forklift Operator|Logistics Executive|Logistics Coordinator|Logistics Manager|Transport Executive|Driver|Heavy Vehicle Driver|Delivery Executive|Delivery Driver'];
        $supportRoles = [
            'Helper' => 'Helper',
            'Production' => 'Production Helper',
            'Machine Shop' => 'Machine Helper',
            'Mechanical Maintenance' => 'Fitter Helper',
            'Electrical Maintenance' => 'Electrician Helper',
            'Welding' => 'Welder|Welder Helper',
            'Fabrication' => 'Fabricator',
            'Painter' => 'Painter|Painter Helper',
            'Foundry' => 'Mason',
            'Security' => 'Security Guard|Security Supervisor',
            'Housekeeping' => 'Housekeeping Staff|Housekeeping Supervisor',
            'Administration' => 'Peon|Office Assistant',
            'Facilities' => 'Facility Technician|Facility Supervisor',
        ];
        foreach ($supportRoles as $department => $names) {
            $groups[$department] = isset($groups[$department]) ? $groups[$department].'|'.$names : $names;
        }
        foreach ($groups as $department => $names) {
            $dept = IndustrialDepartment::firstOrCreate(['slug' => Str::slug($department)], ['name' => $department, 'is_active' => true]);
            foreach (explode('|', $names) as $name) {
                IndustrialJobRole::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'department_id' => $dept->id, 'is_active' => true, 'source_name' => 'User-supplied industrial career dictionary']);
            }
        }
        foreach (['CNC Machine Operator' => 'CNC Operator', 'Turner' => 'Turning Operator', 'Milling Man' => 'Milling Operator', 'Core Making Operator' => 'Core Maker'] as $alias => $canonical) {
            $role = IndustrialJobRole::where('slug', Str::slug($canonical))->firstOrFail();
            IndustrialJobRoleAlias::updateOrCreate(['slug' => Str::slug($alias)], ['name' => $alias, 'job_role_id' => $role->id]);
        }
    }
}
