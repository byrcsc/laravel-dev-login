<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\User;

/**
 * The demo data, and nothing else.
 *
 * Seeding is the application's job, which is exactly what this file stands
 * in for: the package resolves profiles to users that already exist and
 * throws a descriptive exception when one does not. These two addresses are
 * the ones the demo's profiles will point at, so this seeder and the demo's
 * config are the config-to-seeder pair the package is designed to keep
 * honest.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Avery Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Morgan Member',
            'email' => 'member@example.com',
        ]);
    }
}
