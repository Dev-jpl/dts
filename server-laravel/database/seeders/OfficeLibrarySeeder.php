<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $offices = [];

        // Define possible office types by hierarchy level
        $typesByLevel = [
            1 => 'Agency',
            2 => 'Service',
            3 => 'Division',
            4 => 'Section',
        ];

        // Create 20 offices with up to 4 hierarchy levels
        for ($i = 1; $i <= 20; $i++) {
            $id = 'OFF' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $name = "Office {$i}";

            // Randomly assign a hierarchy level (1–4)
            $level = rand(1, 4);

            // Pick a superior office from lower levels if available
            $superiorOfficeId = null;
            $superiorOfficeName = null;

            if (!empty($offices)) {
                // Filter offices with level < current level
                $possibleSuperiors = array_filter($offices, fn($o) => $o['level'] < $level);

                if (!empty($possibleSuperiors)) {
                    $superior = $possibleSuperiors[array_rand($possibleSuperiors)];
                    $superiorOfficeId = $superior['id'];
                    $superiorOfficeName = $superior['office_name'];
                }
            }

            $office = [
                'id' => $id,
                'office_name' => $name,
                'region_code' => 'R' . rand(1, 5),
                'region_name' => 'Region ' . rand(1, 5),
                'province_code' => 'P' . rand(100, 999),
                'province_name' => 'Province ' . rand(1, 10),
                'municipality_code' => 'M' . rand(100, 999),
                'municipality_name' => 'Municipality ' . rand(1, 20),
                'office_type' => $typesByLevel[$level],
                'superior_office_id' => $superiorOfficeId,
                'superior_office_name' => $superiorOfficeName,
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'level' => $level, // keep level for internal logic
            ];

            $offices[] = $office;
        }

        // Remove 'level' before inserting
        $insertData = array_map(fn($o) => array_diff_key($o, ['level' => '']), $offices);

        DB::table('office_libraries')->insert($insertData);
    }
}
