<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Auth\TheOldestAccount;
use Workbench\App\Models\Administrator;
use Workbench\App\Models\User;
use Workbench\App\Tenancy\RememberTheTenant;

/**
 * Configures the demo app.
 *
 * This stands in for the `config/dev-login.php` edits a real application
 * would make when installing the package, and for the auth choices it would
 * make in its own `config/auth.php`.
 *
 * Everything here is set in code rather than in `workbench/.env`: Testbench
 * copies that file into the skeleton, the package test suite boots against
 * the same skeleton, and a stranded copy would quietly hand the suite the
 * demo's settings. Only this provider runs, so only the demo app sees any of
 * it.
 */
final class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Where the demo records that Laravel's `Login` event reached it.
     */
    public const LAST_LOGIN_EVENT = 'workbench.last_login_event';

    /**
     * The application's configuration, set while registering.
     *
     * It has to be in `register()` rather than `boot()`, because the package
     * decides whether to register its routes as it boots, and a provider that
     * switched the package on afterwards would be a demo with no dev login
     * page. Setting it here is safe in either provider order: the package
     * merges its own config file in as it registers, and a merge never
     * overwrites what the application already said.
     */
    public function register(): void
    {
        $config = $this->app->make('config');

        // The skeleton's default provider points at the framework's own user
        // model. The demo has its own, and the package resolves users through
        // whatever the guard's provider says, so this is the whole of the
        // wiring.
        $config->set('auth.providers.users.model', User::class);

        // A second guard over a provider of its own, because one guard proves
        // nothing about a package whose profiles each name one.
        $config->set('auth.providers.administrators', [
            'driver' => 'eloquent',
            'model' => Administrator::class,
        ]);

        $config->set('auth.guards.admin', [
            'driver' => 'session',
            'provider' => 'administrators',
        ]);

        // What a developer would put in their `.env`. The demo is the one
        // place in this repository where the package is switched on.
        $config->set('dev-login.enabled', true);

        // The demo's tenancy package, such as it is.
        $config->set('dev-login.tenant_resolver', RememberTheTenant::class);

        $config->set('dev-login.profiles', $this->profiles());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workbench');

        // Something hung on a real login, so that the profile which opts out
        // of the event has something visible to opt out of.
        Event::listen(Login::class, function (Login $login): void {
            $this->app->make('session')->put(
                self::LAST_LOGIN_EVENT,
                (string) $login->user->getAuthIdentifier(),
            );
        });
    }

    /**
     * Every profile shape the package supports, and every failure it can
     * produce, because the workbench is where these get clicked rather than
     * asserted.
     *
     * @return array<string, array<string, mixed>>
     */
    private function profiles(): array
    {
        return [
            'admin' => [
                'label' => 'Admin (admin guard)',
                'email' => 'admin@example.com',
                'guard' => 'admin',
            ],
            'member' => [
                'label' => 'Member, remembered',
                'email' => 'member@example.com',
                'remember' => true,
            ],
            'support' => [
                'label' => 'Support, straight to the profile page',
                'email' => 'support@example.com',
                'redirect' => '/whoami',
            ],
            'acme-owner' => [
                'label' => 'Owner',
                'email' => 'owner@acme.test',
                'tenant' => 'acme',
            ],
            'globex-owner' => [
                'label' => 'Owner',
                'email' => 'owner@globex.test',
                'tenant' => 'globex',
            ],
            // A tenant the demo's resolver has never heard of, so the tenancy
            // failure path has a button of its own.
            'initech-owner' => [
                'label' => 'Owner (this tenant does not exist)',
                'email' => 'owner@acme.test',
                'tenant' => 'initech',
            ],
            // A different person to the one above, so that the listener's
            // record staying where it was is visible on the landing page.
            'quiet' => [
                'label' => 'Support, without the login event',
                'email' => 'support@example.com',
                'fire_login_event' => false,
            ],
            'oldest' => [
                'label' => 'Whoever was seeded first (this app\'s own resolver)',
                'email' => 'ignored@example.com',
                'resolver' => TheOldestAccount::class,
            ],
            // Deliberately nobody. Clicking this is how the demo shows what a
            // config file and a seeder drifting apart looks like.
            'unseeded' => [
                'label' => 'Nobody (this one is meant to fail)',
                'email' => 'nobody@example.com',
            ],
        ];
    }
}
