<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Exceptions\InvalidConfiguration;
use ByRcsc\LaravelDevLogin\Tests\Support\FakeTenantResolver;
use ByRcsc\LaravelDevLogin\Tests\Support\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

/**
 * @param  array<string, array<string, mixed>>  $profiles
 * @param  array<string, mixed>  $extra
 */
function bootWith(array $profiles, array $extra = []): void
{
    test()->rebootWith(array_merge([
        'dev-login.enabled' => true,
        'dev-login.profiles' => $profiles,
    ], $extra));

    test()->withUsersTable();
}

beforeEach(function (): void {
    bootWith([
        'admin' => ['label' => 'Admin', 'email' => 'admin@example.com'],
        'member' => ['label' => 'Member', 'email' => 'member@example.com', 'remember' => true],
    ]);

    seedUser('admin@example.com', 'Avery Admin');
    seedUser('member@example.com', 'Morgan Member');
});

it('registers both routes, and only those', function (): void {
    expect(route('dev-login.show'))->toEndWith('/dev-login')
        ->and(route('dev-login.attempt', 'admin'))->toEndWith('/dev-login/admin');

    $this->get('/dev-login')->assertOk()->assertSee('Admin')->assertSee('Member');
});

it('authenticates the profile user and regenerates the session', function (): void {
    $this->get('/dev-login');

    $before = session()->getId();

    $this->post('/dev-login/admin')->assertRedirect('/');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()?->getAttribute('email'))->toBe('admin@example.com')
        ->and(session()->getId())->not->toBe($before);
});

it('remembers the profiles that ask to be remembered', function (): void {
    $this->post('/dev-login/member');

    expect(User::query()->where('email', 'member@example.com')->value('remember_token'))->not->toBeNull();
});

it('leaves no remember token behind for the profiles that do not', function (): void {
    $this->post('/dev-login/admin');

    expect(User::query()->where('email', 'admin@example.com')->value('remember_token'))->toBeNull();
});

it('fires the login event, so anything hung on a real login also runs', function (): void {
    Event::fake([Login::class]);

    $this->post('/dev-login/admin');

    Event::assertDispatched(Login::class);
});

it('keeps quiet for a profile that opts out of the login event', function (): void {
    bootWith([
        'silent' => [
            'label' => 'Silent',
            'email' => 'admin@example.com',
            'fire_login_event' => false,
        ],
    ]);

    seedUser('admin@example.com');

    Event::fake([Login::class]);

    $this->post('/dev-login/silent');

    Event::assertNotDispatched(Login::class);

    expect(auth()->check())->toBeTrue();
});

describe('the redirect chain', function (): void {
    it('follows the profile first', function (): void {
        bootWith(
            ['admin' => ['label' => 'Admin', 'email' => 'admin@example.com', 'redirect' => '/from-the-profile']],
            ['dev-login.default_redirect' => '/from-the-config'],
        );

        seedUser('admin@example.com');

        $this->withSession(['url.intended' => '/from-the-session'])
            ->post('/dev-login/admin')
            ->assertRedirect('/from-the-profile');
    });

    it('follows the intended URL next', function (): void {
        bootWith(
            ['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']],
            ['dev-login.default_redirect' => '/from-the-config'],
        );

        seedUser('admin@example.com');

        $this->withSession(['url.intended' => '/from-the-session'])
            ->post('/dev-login/admin')
            ->assertRedirect('/from-the-session');
    });

    it('falls back to the configured default', function (): void {
        bootWith(
            ['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']],
            ['dev-login.default_redirect' => '/from-the-config'],
        );

        seedUser('admin@example.com');

        $this->post('/dev-login/admin')->assertRedirect('/from-the-config');
    });

    it('lands on the root when nothing else says otherwise', function (): void {
        $this->post('/dev-login/admin')->assertRedirect('/');
    });

    /*
     * A URL captured in one tenant rarely means anything in another, so a
     * tenant-bound profile ignores the intended URL rather than following it
     * somewhere that no longer exists.
     */
    it('ignores the intended URL for a tenant-bound profile', function (): void {
        bootWith(
            ['owner' => ['label' => 'Owner', 'email' => 'admin@example.com', 'tenant' => 'acme']],
            [
                'dev-login.default_redirect' => '/from-the-config',
                'dev-login.tenant_resolver' => FakeTenantResolver::class,
            ],
        );

        seedUser('admin@example.com');

        $this->withSession(['url.intended' => '/from-the-session'])
            ->post('/dev-login/owner')
            ->assertRedirect('/from-the-config');
    });
});

it('re-authenticates as whichever profile was clicked last', function (): void {
    $this->post('/dev-login/admin');

    expect(auth()->user()?->getAttribute('email'))->toBe('admin@example.com');

    $this->post('/dev-login/member');

    expect(auth()->user()?->getAttribute('email'))->toBe('member@example.com');
});

it('authenticates on the profile guard, not only the default one', function (): void {
    config()->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => User::class]);
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);

    bootWith([
        'web' => ['label' => 'Web', 'email' => 'member@example.com'],
        'admin' => ['label' => 'Admin', 'email' => 'admin@example.com', 'guard' => 'admin'],
    ], [
        'auth.providers.admins' => ['driver' => 'eloquent', 'model' => User::class],
        'auth.guards.admin' => ['driver' => 'session', 'provider' => 'admins'],
    ]);

    seedUser('admin@example.com');
    seedUser('member@example.com');

    $this->post('/dev-login/web')->assertRedirect('/');
    $this->post('/dev-login/admin')->assertRedirect('/');

    expect(auth('admin')->user()?->getAttribute('email'))->toBe('admin@example.com')
        ->and(auth('web')->user()?->getAttribute('email'))->toBe('member@example.com');
});

it('has no login-by-GET route', function (): void {
    $this->get('/dev-login/admin')->assertMethodNotAllowed();
});

/*
 * A GET that logs you in can be fired by an image tag, and a POST without CSRF
 * can be fired by a form on somebody else's page. Laravel's own middleware
 * waves the suite's requests through, so what is asserted here is that the
 * middleware is on the route at all.
 */
it('protects the attempt route with CSRF', function (): void {
    // The `web` group only expands into classes once the HTTP kernel has run,
    // so this asks the router after a real request rather than before one.
    $this->get('/dev-login')->assertOk();

    $route = Route::getRoutes()->getByName('dev-login.attempt');

    expect($route)->not->toBeNull();

    // Laravel 13 renamed the middleware, and this package supports both.
    $csrf = array_filter(
        app('router')->gatherRouteMiddleware($route),
        fn (mixed $middleware): bool => is_string($middleware)
            && (str_ends_with($middleware, 'ValidateCsrfToken') || str_ends_with($middleware, 'PreventRequestForgery')),
    );

    expect($csrf)->not->toBeEmpty();
});

it('moves both routes when the path is configured', function (): void {
    bootWith(
        ['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']],
        ['dev-login.path' => 'secret/way-in'],
    );

    seedUser('admin@example.com');

    expect(route('dev-login.show'))->toEndWith('/secret/way-in');

    $this->get('/secret/way-in')->assertOk();
    $this->get('/dev-login')->assertNotFound();
    $this->post('/secret/way-in/admin')->assertRedirect('/');
});

it('refuses to register routes on a path or a middleware list it cannot read', function (mixed $config, string $expected): void {
    expect(fn () => bootWith(['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']], $config))
        ->toThrow(InvalidConfiguration::class, $expected);
})->with([
    'a path that is not a string' => [['dev-login.path' => ['dev-login']], 'Check the [path] key'],
    'an empty path' => [['dev-login.path' => ''], 'Check the [path] key'],
    'middleware as one string' => [['dev-login.middleware' => 'web'], 'Check the [middleware] key'],
]);

it('applies the application middleware from config, and the host gate after it', function (): void {
    $route = Route::getRoutes()->getByName('dev-login.show');

    expect($route)->not->toBeNull()
        ->and($route?->gatherMiddleware())->toContain('web', 'dev-login.host');
});

/*
 * The gates, now that there are routes for them to withhold. Every failure is
 * a 404 rather than a 403, and the route does not exist to be forbidden.
 */
describe('a gate that says no', function (): void {
    it('leaves no route behind when the flag is off', function (): void {
        bootWith(['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']], ['dev-login.enabled' => false]);

        expect(Route::getRoutes()->getByName('dev-login.show'))->toBeNull();

        $this->get('/dev-login')->assertNotFound();
        $this->post('/dev-login/admin')->assertNotFound();
    });

    it('leaves no route behind in an environment nobody opted into', function (): void {
        bootWith(['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']], ['app.env' => 'staging']);

        $this->get('/dev-login')->assertNotFound();
    });

    it('answers 404 on a host nobody allowed', function (): void {
        $this->get('http://dev-login.example.com/dev-login')->assertNotFound();
        $this->post('http://dev-login.example.com/dev-login/admin')->assertNotFound();
    });
});
