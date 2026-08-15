<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Resolvers;

use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidProfile;
use ByRcsc\LaravelDevLogin\Profile;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * The resolver the package ships: look the profile's email up on the user
 * provider behind its guard.
 *
 * Asking the guard for its provider, rather than a model or a config key, is
 * what makes custom user models, several providers, and non-Eloquent providers
 * work without a line of configuration. The provider an application already
 * trusts to find users at login is the one that finds them here.
 */
final class FindUserByEmail implements UserResolver
{
    public function __construct(private readonly AuthManager $auth) {}

    public function resolve(Profile $profile): ?Authenticatable
    {
        return $this->providerFor($profile)->retrieveByCredentials(['email' => $profile->email]);
    }

    private function providerFor(Profile $profile): UserProvider
    {
        $guard = $this->auth->guard($profile->guard);

        $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;

        if (! $provider instanceof UserProvider) {
            throw InvalidProfile::notASessionGuard($profile->key, $profile->guard ?? 'default');
        }

        return $provider;
    }
}
