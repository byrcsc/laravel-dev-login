<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Exceptions;

use ByRcsc\LaravelDevLogin\Profile;
use RuntimeException;

/**
 * Thrown when a profile names a user that does not exist.
 *
 * This is the package's config-to-seeder drift detector. The package never
 * creates the missing user, so the message asks the question the answer is
 * usually behind.
 */
final class ProfileUserNotFound extends RuntimeException
{
    public static function make(Profile $profile): self
    {
        return new self(
            "Dev login profile [{$profile->key}] resolves to {$profile->email}, "
            .'which does not exist. Did you run your seeder?'
        );
    }
}
