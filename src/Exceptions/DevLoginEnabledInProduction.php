<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Exceptions;

use RuntimeException;

/**
 * Thrown at boot when the package is switched on under `APP_ENV=production`.
 *
 * The alternative is refusing quietly, and a silent no-op is indistinguishable
 * from a package that is working. An application that reaches production with
 * `DEV_LOGIN_ENABLED=true` has a deployment problem, and it should find out
 * from a failed boot rather than from a stranger on the login page.
 *
 * It stops `artisan` as well as the web application, because both boot the
 * same container. The way out is the environment file, not a cache command.
 */
final class DevLoginEnabledInProduction extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'Dev login is enabled while APP_ENV=production. Refusing to boot. '
            .'Remove DEV_LOGIN_ENABLED from the production environment, or set it to false.'
        );
    }
}
