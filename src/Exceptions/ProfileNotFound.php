<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Exceptions;

use RuntimeException;

/**
 * Thrown when a request names a profile that is not configured.
 */
final class ProfileNotFound extends RuntimeException
{
    /**
     * @param  list<string>  $configured
     */
    public static function make(string $key, array $configured): self
    {
        $known = $configured === []
            ? 'No dev login profiles are configured.'
            : 'Configured profiles: '.implode(', ', $configured).'.';

        return new self("There is no dev login profile [{$key}]. {$known}");
    }
}
