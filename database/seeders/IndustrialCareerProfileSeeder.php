<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialProcess;
use App\Models\IndustrialSector;

class IndustrialCareerProfileSeeder extends Seeder
{
    public function run(): void
    {
        $dept = IndustrialDepartment::firstOrCreate(['slug' => 'cnc'], ['name' => 'CNC', 'is_active' => true]);
        $names = ['CNC Operator', 'CNC Machinist', 'CNC Machine Operator', 'CNC Setter', 'CNC Setter Operator', 'Machine Operator', 'VMC Operator'];
        $skills = ['CNC', 'G-code', 'M-code', 'Vernier', 'Micrometer', 'Tool Offset', 'CNC Programming', 'Drawing Reading'];
        $sectorIds = IndustrialSector::whereIn('slug', ['automobile', 'auto-components', 'forging', 'gear-manufacturing', 'steel', 'heavy-engineering', 'cnc-machining'])->pluck('id');
        $processIds = collect(['CNC Turning', 'CNC Milling', 'Grinding', 'Gear Manufacturing'])->map(fn($name) => IndustrialProcess::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true])->id);
        foreach ($names as $name) {
            $role = IndustrialJobRole::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'department_id' => $dept->id, 'is_active' => true, 'source_name' => 'User-supplied career profile']);
            $role->profile()->updateOrCreate([], ['skills' => $name === 'Machine Operator' ? ['Drawing Reading', 'Vernier', 'Micrometer'] : ($name === 'VMC Operator' ? ['VMC', 'G-code', 'M-code', 'Vernier', 'Micrometer', 'Tool Offset', 'Drawing Reading'] : $skills), 'qualifications' => ['ITI', 'Diploma', 'Mechanical'], 'summary' => 'Career exploration profile. Qualifications and skills are typical backgrounds, not requirements for every opening.', 'source_name' => 'User-supplied CNC example; curated related-role matching']);
            $role->sectors()->syncWithoutDetaching($sectorIds);
            if ($name !== 'Machine Operator') $role->processes()->syncWithoutDetaching($name === 'VMC Operator' ? IndustrialProcess::whereIn('slug', ['vmc-machining', 'cnc-milling'])->pluck('id') : $processIds);
        }
        $cnc = IndustrialJobRole::where('slug', 'cnc-operator')->firstOrFail();
        foreach (['CNC Machine Operator', 'CNC Machinist', 'CNC Setter', 'CNC Setter Operator', 'Machine Operator'] as $alias) $cnc->aliases()->updateOrCreate(['slug' => Str::slug($alias)], ['name' => $alias]);
        $roles = IndustrialJobRole::whereIn('name', $names)->get();
        foreach ($roles as $role) foreach ($roles as $other) {
            if ($role->id === $other->id) continue;
            $broad = $role->name === 'Machine Operator' || $other->name === 'Machine Operator';
            $vmc = $role->name === 'VMC Operator' || $other->name === 'VMC Operator';
            $role->relatedRoles()->syncWithoutDetaching([$other->id => ['weight' => $broad ? 40 : ($vmc ? 65 : 75), 'reason' => $broad ? 'Broader machine-operation role' : ($vmc ? 'Related CNC milling skills; VMC experience may be required' : 'Related CNC operation and setup skills')]]);
        }
    }
}
