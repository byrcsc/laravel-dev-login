<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Tests\Support;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * A user provider with no database behind it.
 *
 * The package resolves users through whatever provider the guard names, and
 * "whatever" has to include the ones an application wrote itself. This is the
 * cheapest possible one of those.
 */
final class ArrayUserProvider implements UserProvider
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public static array $users = [];

    public static function add(string $email, int $id): void
    {
        self::$users[$email] = ['id' => $id, 'email' => $email, 'name' => $email, 'password' => ''];
    }

    public static function reset(): void
    {
        self::$users = [];
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        foreach (self::$users as $attributes) {
            if ($attributes['id'] === (int) $identifier) {
                return new GenericUser($attributes);
            }
        }

        return null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        //
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $email = $credentials['email'] ?? null;

        if (! is_string($email) || ! array_key_exists($email, self::$users)) {
            return null;
        }

        return new GenericUser(self::$users[$email]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        //
    }
}
