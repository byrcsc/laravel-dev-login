<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers the package.
 *
 * At this stage that is the config file and nothing else: no routes, no
 * views, no bindings. The safety gates decide whether the routes register at
 * all, so they land before anything that could be reached over HTTP.
 */
final class DevLoginServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-dev-login')
            ->hasConfigFile('dev-login');
    }
}
