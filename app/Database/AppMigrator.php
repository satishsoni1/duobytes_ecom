<?php

namespace App\Database;

use Illuminate\Database\Migrations\Migrator;

/**
 * The base schema ships as shop.sql + sqlupdates/*.sql, and those tables
 * already exist. Several packages (sanctum, socialiter, payku, ...) register
 * their own migrations for the same tables, which makes `php artisan migrate`
 * fail. Ignoring package paths means only database/migrations is run.
 */
class AppMigrator extends Migrator
{
    public function paths()
    {
        return [];
    }
}
