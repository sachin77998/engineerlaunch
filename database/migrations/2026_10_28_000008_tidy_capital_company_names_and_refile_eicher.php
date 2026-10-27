<?php

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Services\SectorCatalogImporter;
use App\Services\SectorDirectory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Registry and NSE names stored in capitals ("BIRLASOFT LIMITED") get display capitalisation; Eicher Motors (Royal Enfield
// motorcycles) moves from Passenger Cars, where "Motors" had put it, to Two-Wheelers & Three-Wheelers.
return new class extends Migration
{
    public function up(): void
    {
        Company::whereRaw('BINARY name = UPPER(name)')->select('id', 'name')->chunkById(500, function ($companies) {
            foreach ($companies as $c) {
                $tidy = Company::tidyName($c->name);
                if ($tidy !== $c->name) DB::table('companies')->where('id', $c->id)->update(['name' => $tidy]);
            }
        });

        $cars = CompanyCategory::where('slug', 'sector-automobile-manufacturers-passenger-cars')->value('id');
        foreach (Company::where('name', 'like', 'Eicher Motors%')->get() as $eicher) {
            if ($cars) $eicher->categories()->detach($cars);
            app(SectorCatalogImporter::class)->attach($eicher, 'Automobile Manufacturers', 'Two-Wheelers & Three-Wheelers');
            $eicher->forceFill(['industry' => 'Automobile Manufacturers', 'sector' => 'Two-Wheelers & Three-Wheelers'])->save();
        }
        SectorDirectory::flush();
    }

    public function down(): void
    {
        // Data correction; not reversed.
    }
};
