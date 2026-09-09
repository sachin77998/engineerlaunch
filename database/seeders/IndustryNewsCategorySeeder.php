<?php

namespace Database\Seeders;

use App\Models\NewsCategory;
use Illuminate\Database\Seeder;

class IndustryNewsCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('industry_news.feeds', []) as $index => $feed) {
            NewsCategory::updateOrCreate(
                ['slug' => $feed['category']],
                [
                    'name' => $feed['name'],
                    'icon' => $feed['icon'] ?? '⚙️',
                    'color' => '#1769e0',
                    'is_active' => true,
                    'sort_order' => 30 + array_search($index, array_keys(config('industry_news.feeds', [])), true),
                ]
            );
        }
    }
}
