<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\InitialAdminSeeder;
use Database\Seeders\SystemSettingSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            InitialAdminSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
