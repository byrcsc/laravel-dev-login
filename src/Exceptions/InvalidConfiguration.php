<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when `config/dev-login.php` says something the package cannot act on.
 *
 * Profiles have their own exception. This one is for the keys around them, and
 * every message names the key to edit, because that is the whole fix.
 */
final class InvalidConfiguration extends InvalidArgumentException
{
    public static function resolver(string $resolver, string $contract): self
    {
        return new self(
            "The configured dev login resolver [{$resolver}] does not implement {$contract}. "
            .'Check the [resolver] key in config/dev-login.php.'
        );
    }

    public static function tenantResolver(string $resolver, string $contract): self
    {
        return new self(
            "The configured dev login tenant resolver [{$resolver}] does not implement {$contract}. "
            .'Check the [tenant_resolver] key in config/dev-login.php.'
        );
    }

    public static function path(string $given): self
    {
        return new self(
            "The dev login [path] must be a non-empty string, and is {$given}. "
            .'Check the [path] key in config/dev-login.php.'
        );
    }

    public static function middleware(string $given): self
    {
        return new self(
            "The dev login [middleware] must be a list of middleware, and is {$given}. "
            .'Check the [middleware] key in config/dev-login.php.'
        );
    }
}
