<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use ByRcsc\LaravelDevLogin\Contracts\UserResolver;

/**
 * A named, preconfigured way into the application: the one noun this package
 * introduces.
 *
 * It is a value object and nothing more. It knows what was configured, not how
 * to act on any of it: the repository builds and validates it, the resolver
 * turns it into a user, and the authenticator drives the guard.
 */
final readonly class Profile
{
    /**
     * @param  string  $key  The config array key, which is also the route parameter.
     * @param  string|null  $guard  Null uses the application's default guard.
     * @param  string|int|null  $tenant  Handed to the tenant resolver untouched.
     * @param  class-string<UserResolver>|null  $resolver  Null uses the configured resolver.
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $email,
        public ?string $guard = null,
        public bool $remember = false,
        public string|int|null $tenant = null,
        public ?string $redirect = null,
        public ?string $resolver = null,
        public bool $fireLoginEvent = true,
    ) {}

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}
