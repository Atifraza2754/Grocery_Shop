<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    /**
     * Sample delivery areas. Idempotent — safe to re-run.
     * Edit / delete via Operations → Areas.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'DHA Phase 1',     'city' => 'Karachi', 'delivery_charge' => 100, 'sort_order' => 1],
            ['name' => 'DHA Phase 2',     'city' => 'Karachi', 'delivery_charge' => 100, 'sort_order' => 2],
            ['name' => 'DHA Phase 5',     'city' => 'Karachi', 'delivery_charge' => 150, 'sort_order' => 3],
            ['name' => 'Clifton Block 2', 'city' => 'Karachi', 'delivery_charge' => 100, 'sort_order' => 4],
            ['name' => 'Gulshan-e-Iqbal', 'city' => 'Karachi', 'delivery_charge' => 200, 'sort_order' => 5],
            ['name' => 'Bahadurabad',     'city' => 'Karachi', 'delivery_charge' => 200, 'sort_order' => 6],
            ['name' => 'PECHS',           'city' => 'Karachi', 'delivery_charge' => 150, 'sort_order' => 7],
            ['name' => 'North Nazimabad', 'city' => 'Karachi', 'delivery_charge' => 250, 'sort_order' => 8],
        ];

        foreach ($rows as $row) {
            Area::updateOrCreate(
                ['name' => $row['name']],
                $row + ['is_active' => true]
            );
        }
    }
}
