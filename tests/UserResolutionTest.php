<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Authenticator;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidConfiguration;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidProfile;
use ByRcsc\LaravelDevLogin\Exceptions\ProfileUserNotFound;
use ByRcsc\LaravelDevLogin\Profile;
use ByRcsc\LaravelDevLogin\Resolvers\FindUserByEmail;
use ByRcsc\LaravelDevLogin\Tests\Support\ArrayUserProvider;
use ByRcsc\LaravelDevLogin\Tests\Support\CountingResolver;
use ByRcsc\LaravelDevLogin\Tests\Support\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function (): void {
    $this->withUsersTable();
});

it('finds the profile user on the default guard provider', function (): void {
    $user = seedUser('admin@example.com');

    $resolved = app(FindUserByEmail::class)->resolve(
        new Profile(key: 'admin', label: 'Admin', email: 'admin@example.com'),
    );

    expect($resolved?->getAuthIdentifier())->toBe($user->getKey());
});

it('returns nothing when the user does not exist', function (): void {
    $resolved = app(FindUserByEmail::class)->resolve(
        new Profile(key: 'admin', label: 'Admin', email: 'ghost@example.com'),
    );

    expect($resolved)->toBeNull();
});

/*
 * Two guards over two providers is the shape every multi-guard application
 * has, and resolving through the guard's own provider is what makes it work
 * without a line of package config.
 */
it('finds the user on the provider behind a named guard', function (): void {
    config()->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => User::class]);
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);

    $user = seedUser('owner@example.com');

    $resolved = app(FindUserByEmail::class)->resolve(
        new Profile(key: 'owner', label: 'Owner', email: 'owner@example.com', guard: 'admin'),
    );

    expect($resolved?->getAuthIdentifier())->toBe($user->getKey());
});

it('finds the user through a provider that is not Eloquent', function (): void {
    Auth::provider('array', fn (): ArrayUserProvider => new ArrayUserProvider);

    config()->set('auth.providers.people', ['driver' => 'array']);
    config()->set('auth.guards.people', ['driver' => 'session', 'provider' => 'people']);

    ArrayUserProvider::add('person@example.com', 99);

    $resolved = app(FindUserByEmail::class)->resolve(
        new Profile(key: 'person', label: 'Person', email: 'person@example.com', guard: 'people'),
    );

    expect($resolved?->getAuthIdentifier())->toBe(99);
});

it('lets a profile name a resolver of its own', function (): void {
    CountingResolver::$user = seedUser('admin@example.com');

    app(Authenticator::class)->login(new Profile(
        key: 'admin',
        label: 'Admin',
        email: 'admin@example.com',
        resolver: CountingResolver::class,
    ));

    expect(CountingResolver::$calls)->toBe(['admin'])
        ->and(auth()->id())->toBe(CountingResolver::$user->getAuthIdentifier());
});

it('uses the configured resolver for profiles that do not name one', function (): void {
    config()->set('dev-login.resolver', CountingResolver::class);

    CountingResolver::$user = seedUser('admin@example.com');

    app(Authenticator::class)->login(new Profile(key: 'admin', label: 'Admin', email: 'admin@example.com'));

    expect(CountingResolver::$calls)->toBe(['admin']);
});

it('refuses a configured resolver that is not one', function (): void {
    config()->set('dev-login.resolver', Profile::class);

    expect(fn () => app(Authenticator::class)->login(
        new Profile(key: 'admin', label: 'Admin', email: 'admin@example.com'),
    ))->toThrow(InvalidConfiguration::class, 'Check the [resolver] key in config/dev-login.php.');
});

/*
 * A profile can be built in code as well as read from config, so the class it
 * names is checked again here - and the message points at the profile rather
 * than at the config key somebody did not set.
 */
it('refuses a profile resolver that is not one, and names the profile', function (): void {
    expect(fn () => app(Authenticator::class)->login(new Profile(
        key: 'admin',
        label: 'Admin',
        email: 'admin@example.com',
        resolver: Profile::class,
    )))->toThrow(InvalidProfile::class, 'Dev login profile [admin] names the resolver');
});

/*
 * The config-to-seeder drift detector. It is the one error a developer using
 * this package is most likely to meet, so the message has to name the profile,
 * the address, and the usual cause.
 */
it('says which profile is pointing at a user that is not there', function (): void {
    expect(fn () => app(Authenticator::class)->login(
        new Profile(key: 'admin', label: 'Admin', email: 'admin@example.com'),
    ))->toThrow(
        ProfileUserNotFound::class,
        'Dev login profile [admin] resolves to admin@example.com, which does not exist. Did you run your seeder?',
    );
});

it('never writes the user it cannot find', function (): void {
    try {
        app(Authenticator::class)->login(new Profile(key: 'admin', label: 'Admin', email: 'admin@example.com'));
    } catch (ProfileUserNotFound) {
        // The point is the table below, not the exception above.
    }

    expect(User::query()->count())->toBe(0);
});
