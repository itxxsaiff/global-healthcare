<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Reference data (specialties, countries, plans) is needed by almost every test. */
    protected bool $seed = true;

    protected string $seeder = TestSeeder::class;
}
