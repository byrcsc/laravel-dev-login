<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a configured profile cannot be read as one.
 *
 * Every message names the profile key, because the config file is where the
 * fix is and the key is how it is found there.
 */
final class InvalidProfile extends InvalidArgumentException
{
    public static function make(string $key, string $problem): self
    {
        return new self("Dev login profile [{$key}] {$problem}.");
    }

    public static function notAnArray(string $key): self
    {
        return self::make($key, 'must be an array of settings');
    }

    public static function missing(string $key, string $field): self
    {
        return self::make($key, "is missing a [{$field}], which every profile needs");
    }

    public static function badType(string $key, string $field, string $expected): self
    {
        return self::make($key, "has a [{$field}] that is not {$expected}");
    }

    public static function unknownKeys(string $key, string $keys): self
    {
        return self::make($key, "has settings this package does not know: {$keys}");
    }

    public static function unknownGuard(string $key, string $guard): self
    {
        return self::make($key, "names the guard [{$guard}], which is not in config/auth.php");
    }

    public static function notASessionGuard(string $key, string $guard): self
    {
        return self::make(
            $key,
            "names the guard [{$guard}], which is not a session guard. "
            .'This package drives session guards only'
        );
    }

    public static function notAResolver(string $key, string $resolver, string $contract): self
    {
        return self::make($key, "names the resolver [{$resolver}], which does not implement {$contract}");
    }
}
