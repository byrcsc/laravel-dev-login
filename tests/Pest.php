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
