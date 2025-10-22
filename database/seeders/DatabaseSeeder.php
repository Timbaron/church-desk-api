<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the specific seeder for the Finance Requisition application
        $this->call([
            AppSeeder::class,
        ]);
    }
}
