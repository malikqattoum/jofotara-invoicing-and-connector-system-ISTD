<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test database without transactions to avoid SQLite vacuum issues
        if (config('database.default') === 'sqlite') {
            $this->artisan('migrate:fresh', ['--seed' => false]);
        }
    }
}
