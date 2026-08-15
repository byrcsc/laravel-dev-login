<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use ByRcsc\LaravelDevLogin\Exceptions\DevLoginEnabledInProduction;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidConfiguration;
use ByRcsc\LaravelDevLogin\Http\Controllers\DevLoginController;
use ByRcsc\LaravelDevLogin\Http\Middleware\EnsureHostIsAllowed;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
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
            ->hasConfigFile('dev-login')
            ->hasViews('dev-login');
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

        // Registered whatever the gates say, because the component answers to
        // them itself and an application may embed it on a page of its own.
        Blade::componentNamespace('ByRcsc\\LaravelDevLogin\\View\\Components', 'dev-login');

        if (! $gatekeeper->passes()) {
            return;
        }

        $this->registerRoutes();
    }

    /**
     * Reached only when every boot-time gate has agreed, so a failing gate
     * leaves nothing to forbid.
     *
     * There is deliberately no GET route that authenticates: a GET that logs
     * you in can be fired by an image tag or a prefetch.
     */
    private function registerRoutes(): void
    {
        $path = $this->config()->get('dev-login.path', 'dev-login');

        if (! is_string($path) || $path === '') {
            throw InvalidConfiguration::path(get_debug_type($path));
        }

        $middleware = $this->config()->get('dev-login.middleware', ['web']);

        if (! is_array($middleware)) {
            throw InvalidConfiguration::middleware(get_debug_type($middleware));
        }

        $middleware = array_values($middleware);

        $router = $this->app->make(Router::class);

        $router->middleware([...$middleware, self::HOST_MIDDLEWARE])->group(function (Router $router) use ($path): void {
            $router->get($path, [DevLoginController::class, 'show'])->name('dev-login.show');
            $router->post($path.'/{profile}', [DevLoginController::class, 'attempt'])->name('dev-login.attempt');
        });
    }

    private function config(): Repository
    {
        return $this->app->make(Repository::class);
    }
}
