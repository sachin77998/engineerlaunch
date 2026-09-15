<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class IndustrialEnrichmentSeeder extends Seeder {public function run(): void {$this->call([IndustrialTaxonomySeeder::class,IndustrialVisualAssetSeeder::class,IndustrialCareerPathwaySeeder::class,IndustrialClusterEvidenceSeeder::class]);}}
