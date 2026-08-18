<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Tests\Support\FakeTenantResolver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    bootDevLogin([
        'admin' => ['label' => 'Admin', 'email' => 'admin@example.com'],
        'member' => ['label' => 'Member', 'email' => 'member@example.com'],
    ]);
});

it('shows the application, environment, and identity details for each profile', function (): void {
    $this->get('/dev-login')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('testing')
        ->assertSee('Admin')
        ->assertSee('admin@example.com')
        ->assertSee('Member')
        ->assertSee('member@example.com')
        ->assertSee('web guard');
});

it('posts each button to its own profile, with a CSRF token', function (): void {
    $response = $this->get('/dev-login')->assertOk();

    expect($response->getContent())
        ->toContain('action="'.route('dev-login.attempt', 'admin').'"')
        ->toContain('action="'.route('dev-login.attempt', 'member').'"')
        ->toContain('method="POST"')
        ->toContain('name="_token"');
});

it('groups the buttons by tenant, and puts the tenantless ones last', function (): void {
    bootDevLogin([
        'acme-owner' => ['label' => 'Acme owner', 'email' => 'owner@acme.test', 'tenant' => 'Acme'],
        'staff' => ['label' => 'Staff', 'email' => 'staff@example.com'],
        'globex-owner' => ['label' => 'Globex owner', 'email' => 'owner@globex.test', 'tenant' => 'Globex'],
    ], ['dev-login.tenant_resolver' => FakeTenantResolver::class]);

    $content = (string) $this->get('/dev-login')->assertOk()->getContent();

    expect($content)->toContain('<h2>Acme</h2>')
        ->toContain('<h2>Globex</h2>')
        ->toContain('Acme owner')
        ->toContain('Globex owner')
        ->toContain('Staff');

    // The tenants in the order they were configured, and no heading over the
    // profiles that name none.
    expect(strpos($content, 'Acme owner'))->toBeLessThan((int) strpos($content, 'Globex owner'))
        ->and(strpos($content, 'Globex owner'))->toBeLessThan((int) strpos($content, 'Staff'))
        ->and($content)->toContain('aria-label="Profiles with no tenant"');
});

it('says so when there are no profiles at all', function (): void {
    bootDevLogin([]);

    $this->get('/dev-login')->assertOk()->assertSee('No profiles are configured.');
});

/*
 * The component is the product and the page is a wrapper around it, so an
 * application embedding it in its own login page gets the same buttons and the
 * same styles.
 */
describe('the profiles component', function (): void {
    beforeEach(function (): void {
        View::addLocation(__DIR__.'/Support/views');

        Route::middleware('web')->get('/our-login', fn (): string => (string) view('embedded'));
    });

    it('renders the same buttons, and its own styles, anywhere it is dropped', function (): void {
        $content = (string) $this->get('/our-login')->assertOk()->getContent();

        expect($content)->toContain('Our own login page')
            ->toContain('Admin')
            ->toContain('Member')
            ->toContain('class="dev-login-profiles"')
            ->toContain('.dev-login-profiles button');
    });

    it('renders nothing at all when a gate says no', function (): void {
        bootDevLogin(
            ['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']],
            ['dev-login.enabled' => false],
        );

        View::addLocation(__DIR__.'/Support/views');
        Route::middleware('web')->get('/our-login', fn (): string => (string) view('embedded'));

        $content = (string) $this->get('/our-login')->assertOk()->getContent();

        expect($content)->toContain('Our own login page')
            ->not->toContain('Admin');
    });

    it('renders nothing in an environment nobody opted into', function (): void {
        bootDevLogin(
            ['admin' => ['label' => 'Admin', 'email' => 'admin@example.com']],
            ['app.env' => 'staging'],
        );

        View::addLocation(__DIR__.'/Support/views');
        Route::middleware('web')->get('/our-login', fn (): string => (string) view('embedded'));

        $content = (string) $this->get('/our-login')->assertOk()->getContent();

        expect($content)->toContain('Our own login page')
            ->not->toContain('Admin');
    });

    it('renders nothing on a host nobody allowed', function (): void {
        $content = (string) $this->get('http://dev-login.example.com/our-login')->assertOk()->getContent();

        expect($content)->toContain('Our own login page')
            ->not->toContain('Admin');
    });
});

describe('published views', function (): void {
    afterEach(function (): void {
        File::deleteDirectory(resource_path('views/vendor/dev-login'));
    });

    it('publishes under the documented tag', function (): void {
        $published = resource_path('views/vendor/dev-login');

        File::deleteDirectory($published);

        $this->artisan('vendor:publish', ['--tag' => 'dev-login-views'])->assertSuccessful();

        expect(File::exists($published.'/index.blade.php'))->toBeTrue()
            ->and(File::exists($published.'/components/profiles.blade.php'))->toBeTrue();
    });

    it('let an application replace the page', function (): void {
        $published = resource_path('views/vendor/dev-login');

        File::ensureDirectoryExists($published);
        File::put($published.'/index.blade.php', 'Our own dev login page.');

        $this->get('/dev-login')->assertOk()->assertSee('Our own dev login page.')->assertDontSee('Admin');
    });

    it('let an application replace the buttons too', function (): void {
        $published = resource_path('views/vendor/dev-login/components');

        File::ensureDirectoryExists($published);
        File::put($published.'/profiles.blade.php', 'Buttons we drew ourselves.');

        $this->get('/dev-login')
            ->assertOk()
            ->assertSee('Buttons we drew ourselves.')
            ->assertDontSee('Admin');
    });
});
