<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\User;

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
    public function register(): void {}

    /**
     * Set in `boot()` rather than `register()`: the package merges its own
     * config file in as it registers, and this provider is listed first.
     */
    public function boot(): void
    {
        $config = $this->app->make('config');

        // The skeleton's default provider points at the framework's own user
        // model. The demo has its own, and the package resolves users through
        // whatever the guard's provider says, so this is the whole of the
        // wiring.
        $config->set('auth.providers.users.model', User::class);

        // What a developer would put in their `.env`. The demo is the one
        // place in this repository where the package is switched on.
        $config->set('dev-login.enabled', true);
    }
}
