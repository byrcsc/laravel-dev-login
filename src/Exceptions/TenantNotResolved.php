<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Exceptions;

use ByRcsc\LaravelDevLogin\Contracts\TenantResolver;
use ByRcsc\LaravelDevLogin\Profile;
use RuntimeException;
use Throwable;

/**
 * Thrown when a profile's tenant cannot be made current.
 *
 * Nobody is authenticated when this is thrown: the tenant is made current
 * before the user is resolved, so a tenancy failure stops the login rather
 * than logging somebody into the wrong tenant.
 */
final class TenantNotResolved extends RuntimeException
{
    public static function make(Profile $profile, ?Throwable $previous = null): self
    {
        return new self(
            "Dev login profile [{$profile->key}] could not make the tenant [{$profile->tenant}] current.",
            previous: $previous,
        );
    }

    public static function noResolver(Profile $profile): self
    {
        return new self(
            "Dev login profile [{$profile->key}] names the tenant [{$profile->tenant}], "
            .'but no [tenant_resolver] is configured in config/dev-login.php. '
            .'The package ships the '.TenantResolver::class.' contract and no adapter.'
        );
    }
}
