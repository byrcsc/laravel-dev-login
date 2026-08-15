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
 * throws a descriptive exception when one does not. These addresses are the
 * ones the demo's profiles point at, so this seeder and the demo's config are
 * the config-to-seeder pair the package is designed to keep honest.
 *
 * `nobody@example.com` is missing on purpose, and the profile pointing at it
 * is how the demo shows what that failure reads like.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            'Avery Admin' => 'admin@example.com',
            'Morgan Member' => 'member@example.com',
            'Sam Support' => 'support@example.com',
            'Acme Owner' => 'owner@acme.test',
            'Globex Owner' => 'owner@globex.test',
        ];

        foreach ($people as $name => $email) {
            User::factory()->create(['name' => $name, 'email' => $email]);
        }
    }
}
