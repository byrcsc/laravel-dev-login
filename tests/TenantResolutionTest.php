<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Authenticator;
use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidConfiguration;
use ByRcsc\LaravelDevLogin\Exceptions\TenantNotResolved;
use ByRcsc\LaravelDevLogin\Profile;
use ByRcsc\LaravelDevLogin\Tests\Support\FakeTenantResolver;
use ByRcsc\LaravelDevLogin\Tests\Support\User;
use Illuminate\Contracts\Auth\Authenticatable;

beforeEach(function (): void {
    $this->withUsersTable();
});

function tenantProfile(string $key = 'owner', string $email = 'owner@acme.test'): Profile
{
    return new Profile(key: $key, label: 'Owner', email: $email, tenant: 'acme');
}

it('makes the tenant current before the user is looked up', function (): void {
    config()->set('dev-login.tenant_resolver', FakeTenantResolver::class);

    seedUser('owner@acme.test');

    $resolver = new class implements UserResolver
    {
        public ?string $tenantWhenAsked = null;

        public function resolve(Profile $profile): ?Authenticatable
        {
            $this->tenantWhenAsked = FakeTenantResolver::current();

            return User::query()->where('email', $profile->email)->first();
        }
    };

    app()->instance($resolver::class, $resolver);
    config()->set('dev-login.resolver', $resolver::class);

    app(Authenticator::class)->login(tenantProfile());

    expect($resolver->tenantWhenAsked)->toBe('acme')
        ->and(auth()->check())->toBeTrue();
});

it('hands the resolver the tenant exactly as it was configured', function (): void {
    config()->set('dev-login.tenant_resolver', FakeTenantResolver::class);

    seedUser('owner@acme.test');

    app(Authenticator::class)->login(tenantProfile());

    expect(FakeTenantResolver::$log)->toBe(['acme']);
});

/*
 * An application without tenancy has to behave as though the feature were not
 * there at all, which is the whole reason the contract can ship without an
 * adapter.
 */
it('never asks a resolver about a profile with no tenant', function (): void {
    config()->set('dev-login.tenant_resolver', FakeTenantResolver::class);

    seedUser('admin@example.com');

    app(Authenticator::class)->login(new Profile(key: 'admin', label: 'Admin', email: 'admin@example.com'));

    expect(FakeTenantResolver::$log)->toBe([])
        ->and(auth()->check())->toBeTrue();
});

it('stops the login when the resolver fails, before anybody is authenticated', function (): void {
    config()->set('dev-login.tenant_resolver', FakeTenantResolver::class);

    FakeTenantResolver::$fails = true;

    seedUser('owner@acme.test');

    expect(fn () => app(Authenticator::class)->login(tenantProfile()))
        ->toThrow(TenantNotResolved::class, 'Dev login profile [owner] could not make the tenant [acme] current.');

    expect(auth()->check())->toBeFalse();
});

it('keeps what the tenancy package said, underneath its own message', function (): void {
    config()->set('dev-login.tenant_resolver', FakeTenantResolver::class);

    FakeTenantResolver::$fails = true;

    try {
        app(Authenticator::class)->login(tenantProfile());
    } catch (TenantNotResolved $exception) {
        expect($exception->getPrevious()?->getMessage())->toBe('No such tenant.');

        return;
    }

    $this->fail('A failing tenant resolver did not stop the login.');
});

/*
 * A profile that names a tenant in an application with no resolver is a
 * profile that cannot do what it says. Saying so beats logging somebody in
 * without the tenant they asked for.
 */
it('refuses a tenant-bound profile when no resolver is configured', function (): void {
    seedUser('owner@acme.test');

    expect(fn () => app(Authenticator::class)->login(tenantProfile()))
        ->toThrow(TenantNotResolved::class, 'no [tenant_resolver] is configured');

    expect(auth()->check())->toBeFalse();
});

it('refuses a configured tenant resolver that is not one', function (): void {
    config()->set('dev-login.tenant_resolver', Profile::class);

    expect(fn () => app(Authenticator::class)->login(tenantProfile()))
        ->toThrow(InvalidConfiguration::class, 'Check the [tenant_resolver] key in config/dev-login.php.');
});
