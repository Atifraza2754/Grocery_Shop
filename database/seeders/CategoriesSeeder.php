<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Seed the four starter categories with their SKU prefixes.
     * Idempotent — safe to re-run.
     */
    public function run(): void
    {
        $rows = [
            [
                'name'        => 'Pre-cut Vegetables',
                'slug'        => 'pre-cut-vegetables',
                'prefix'      => 'PRC',
                'description' => 'Fresh, ready-to-cook chopped vegetables.',
                'sort_order'  => 1,
                'is_active'   => true,
            ],
            [
                'name'        => 'Frozen Items',
                'slug'        => 'frozen-items',
                'prefix'      => 'FRZ',
                'description' => 'Frozen kebabs, koftas, and ready-to-fry items.',
                'sort_order'  => 2,
                'is_active'   => true,
            ],
            [
                'name'        => 'Cooking Pastes',
                'slug'        => 'cooking-pastes',
                'prefix'      => 'CPT',
                'description' => 'Ginger-garlic paste, ground spices, masala blends.',
                'sort_order'  => 3,
                'is_active'   => true,
            ],
            [
                'name'        => 'Pre-cut Deals',
                'slug'        => 'pre-cut-deals',
                'prefix'      => 'PCD',
                'description' => 'Bundle deals — combine cuts and pastes for a full meal.',
                'sort_order'  => 4,
                'is_active'   => true,
            ],
        ];

        foreach ($rows as $row) {
            Category::updateOrCreate(
                ['slug' => $row['slug']],
                $row
            );
        }
    }
}
