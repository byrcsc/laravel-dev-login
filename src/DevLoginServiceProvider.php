<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use ByRcsc\LaravelDevLogin\Exceptions\DevLoginEnabledInProduction;
use ByRcsc\LaravelDevLogin\Http\Middleware\EnsureHostIsAllowed;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers the package.
 *
 * The safety gates decide whether anything reachable over HTTP exists at all,
 * so they run before the routes and the routes register only if they agree.
 */
final class DevLoginServiceProvider extends PackageServiceProvider
{
    /**
     * The alias the host gate answers to, so an application that embeds the
     * profiles component on a route of its own can hold the same line.
     */
    public const HOST_MIDDLEWARE = 'dev-login.host';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-dev-login')
            ->hasConfigFile('dev-login');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Gatekeeper::class, static fn (Application $app): Gatekeeper => new Gatekeeper(
            $app,
            $app->make(Repository::class),
        ));
    }

    public function packageBooted(): void
    {
        $gatekeeper = $this->app->make(Gatekeeper::class);

        if ($gatekeeper->mustRefuseToBoot()) {
            throw DevLoginEnabledInProduction::make();
        }

        $this->app->make(Router::class)->aliasMiddleware(self::HOST_MIDDLEWARE, EnsureHostIsAllowed::class);

        if (! $gatekeeper->passes()) {
            return;
        }

        $this->registerRoutes();
    }

    /**
     * Reached only when every boot-time gate has agreed. The routes it
     * registers land in issue 04; the host gate is applied there, on top of
     * the application's own middleware from config.
     */
    private function registerRoutes(): void
    {
        //
    }
}
