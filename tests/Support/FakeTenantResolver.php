<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Tests\Support;

use ByRcsc\LaravelDevLogin\Contracts\TenantResolver;
use ByRcsc\LaravelDevLogin\Profile;
use RuntimeException;

/**
 * A tenancy package the size of a class.
 *
 * v1 ships the contract and no adapter, so this stands in for the tenancy
 * stack in the suite: it records the tenant it was asked to make current, and
 * can be told to fail. Config hands the package a class-string rather than an
 * instance, which is why the record it keeps is static.
 */
final class FakeTenantResolver implements TenantResolver
{
    /**
     * @var list<string>
     */
    public static array $log = [];

    public static bool $fails = false;

    public static function reset(): void
    {
        self::$log = [];
        self::$fails = false;
    }

    public static function current(): ?string
    {
        return self::$log === [] ? null : self::$log[array_key_last(self::$log)];
    }

    public function makeCurrent(Profile $profile): void
    {
        if (self::$fails) {
            throw new RuntimeException('No such tenant.');
        }

        self::$log[] = (string) $profile->tenant;
    }
}
