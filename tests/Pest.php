<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Tests\Support\User;
use ByRcsc\LaravelDevLogin\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Put a user in the application's users table.
 *
 * Seeding is the application's job, and in this suite the application is us.
 * Every profile the tests configure points at somebody made here.
 */
function seedUser(string $email, string $name = 'Test person'): User
{
    return User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => 'irrelevant',
    ]);
}
