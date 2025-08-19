<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class SQLiteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Enable foreign key constraints for SQLite in testing environment
        if ($this->app->environment('testing')) {
            DB::statement('PRAGMA foreign_keys=on;');
        }
    }
}
