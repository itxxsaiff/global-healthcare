<?php

namespace Tests;

use Database\Seeders\PageSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([ReferenceDataSeeder::class, PlanSeeder::class, PageSeeder::class]);
    }
}
