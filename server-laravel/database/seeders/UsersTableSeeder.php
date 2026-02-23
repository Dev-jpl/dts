<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => Str::uuid()->toString(),
                'email' => 'john.doe@example.com',
                'first_name' => 'John',
                'middle_name' => 'P',
                'last_name' => 'Doe',
                'office_id' => 'OFF001',
                'office_name' => 'Main Office',
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'password' => Hash::make('password'),
            ],
            [
                'id' => Str::uuid()->toString(),
                'email' => 'jane.smith@example.com',
                'first_name' => 'Jane',
                'middle_name' => null,
                'last_name' => 'Smith',
                'office_id' => 'OFF002',
                'office_name' => 'Finance Division',
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'password' => Hash::make('password'),
            ],
        ]);
    }
}
