<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Authenticator;
use ByRcsc\LaravelDevLogin\Contracts\TenantResolver;
use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\DevLoginServiceProvider;
use ByRcsc\LaravelDevLogin\Exceptions\DevLoginEnabledInProduction;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidConfiguration;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidProfile;
use ByRcsc\LaravelDevLogin\Exceptions\ProfileNotFound;
use ByRcsc\LaravelDevLogin\Exceptions\ProfileUserNotFound;
use ByRcsc\LaravelDevLogin\Exceptions\TenantNotResolved;
use ByRcsc\LaravelDevLogin\Gatekeeper;
use ByRcsc\LaravelDevLogin\Http\Controllers\DevLoginController;
use ByRcsc\LaravelDevLogin\Http\Middleware\EnsureHostIsAllowed;
use ByRcsc\LaravelDevLogin\Profile;
use ByRcsc\LaravelDevLogin\ProfileRepository;
use ByRcsc\LaravelDevLogin\Resolvers\FindUserByEmail;
use ByRcsc\LaravelDevLogin\View\Components\Profiles as ProfilesComponent;

it('keeps the public classes loadable', function (): void {
    $classes = [
        Authenticator::class,
        DevLoginServiceProvider::class,
        DevLoginEnabledInProduction::class,
        DevLoginController::class,
        EnsureHostIsAllowed::class,
        FindUserByEmail::class,
        Gatekeeper::class,
        InvalidConfiguration::class,
        InvalidProfile::class,
        Profile::class,
        ProfileNotFound::class,
        ProfileRepository::class,
        ProfilesComponent::class,
        ProfileUserNotFound::class,
        TenantNotResolved::class,
    ];

    foreach ($classes as $class) {
        expect(class_exists($class))->toBeTrue("Public class [{$class}] is not loadable.");
    }
});

it('keeps the public contracts loadable', function (): void {
    $contracts = [
        TenantResolver::class,
        UserResolver::class,
    ];

    foreach ($contracts as $contract) {
        expect(interface_exists($contract))->toBeTrue("Public contract [{$contract}] is not loadable.");
    }
});
