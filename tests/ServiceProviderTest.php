<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\DevLoginServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

it('loads the config with its documented defaults', function (): void {
    expect(config('dev-login.enabled'))->toBeFalse()
        ->and(config('dev-login.environments'))->toBe(['local', 'testing'])
        ->and(config('dev-login.allowed_hosts'))->toBe(['localhost', '127.0.0.1', '*.test'])
        ->and(config('dev-login.path'))->toBe('dev-login')
        ->and(config('dev-login.middleware'))->toBe(['web'])
        ->and(config('dev-login.profiles'))->toBe([])
        ->and(config('dev-login.resolver'))->toBeNull()
        ->and(config('dev-login.tenant_resolver'))->toBeNull()
        ->and(config('dev-login.default_redirect'))->toBeNull();
});

/*
 * Off by default is the claim the whole safety model rests on, so it gets a
 * test of its own rather than a line in the defaults above. An installation
 * that turns the package on by merely existing is the failure this package is
 * built to make impossible.
 */
it('is disabled until an application says otherwise', function (): void {
    expect(config('dev-login.enabled'))->toBeFalse();
});

it('never allows production through the environment list', function (): void {
    expect(config('dev-login.environments'))->not->toContain('production');
});

it('registers the dev-login-config publish tag', function (): void {
    expect(ServiceProvider::pathsToPublish(DevLoginServiceProvider::class, 'dev-login-config'))
        ->not->toBeEmpty('The dev-login-config publish tag is not registered.');
});

it('publishes the config file to the application', function (): void {
    $target = config_path('dev-login.php');

    File::delete($target);

    $this->artisan('vendor:publish', ['--tag' => 'dev-login-config'])->assertSuccessful();

    expect(File::exists($target))->toBeTrue();

    /** @var array<string, mixed> $published */
    $published = require $target;

    expect(array_keys($published))->toBe([
        'enabled',
        'environments',
        'allowed_hosts',
        'path',
        'middleware',
        'profiles',
        'resolver',
        'tenant_resolver',
        'default_redirect',
    ]);

    File::delete($target);
});
