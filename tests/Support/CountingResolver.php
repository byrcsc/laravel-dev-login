<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Tests\Support;

use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\Profile;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A resolver that records that it ran, for the tests that care which resolver
 * was chosen rather than which user came back.
 */
final class CountingResolver implements UserResolver
{
    /**
     * @var list<string>
     */
    public static array $calls = [];

    public static ?Authenticatable $user = null;

    public static function reset(): void
    {
        self::$calls = [];
        self::$user = null;
    }

    public function resolve(Profile $profile): ?Authenticatable
    {
        self::$calls[] = $profile->key;

        return self::$user;
    }
}
