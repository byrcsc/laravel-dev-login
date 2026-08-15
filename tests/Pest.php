<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Tests\Support\ArrayUserProvider;
use ByRcsc\LaravelDevLogin\Tests\Support\CountingResolver;
use ByRcsc\LaravelDevLogin\Tests\Support\FakeTenantResolver;
use ByRcsc\LaravelDevLogin\Tests\Support\User;
use ByRcsc\LaravelDevLogin\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/*
 * The test doubles record what they were asked to do in static state, because
 * config hands the package a class-string and never an instance. Static state
 * outlives a test, and this suite runs in random order, so it is cleared for
 * every test rather than by the files that happen to use it.
 */
uses()->beforeEach(function (): void {
    ArrayUserProvider::reset();
    CountingResolver::reset();
    FakeTenantResolver::reset();
})->in(__DIR__);

/**
 * Rebuild the application with the package switched on and the given profiles.
 *
 * Whether the routes exist, and whether the gates opened, is decided while the
 * application boots, so every test that wants a working dev login starts by
 * booting one.
 *
 * @param  array<string, array<string, mixed>>  $profiles
 * @param  array<string, mixed>  $extra
 */
function bootDevLogin(array $profiles, array $extra = []): void
{
    test()->rebootWith(array_merge([
        'dev-login.enabled' => true,
        'dev-login.profiles' => $profiles,
    ], $extra));
}

/**
 * The same, plus the users table, for the tests that go on to log somebody in.
 *
 * The migrations are not run by `bootDevLogin()` itself, because most of this
 * suite boots applications the gates refuse to open, and a migration run is an
 * expensive way to prove a route does not exist.
 *
 * @param  array<string, array<string, mixed>>  $profiles
 * @param  array<string, mixed>  $extra
 */
function bootDevLoginWithUsers(array $profiles, array $extra = []): void
{
    bootDevLogin($profiles, $extra);

    test()->withUsersTable();
}

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
