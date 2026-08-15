<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidConfiguration;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidProfile;
use ByRcsc\LaravelDevLogin\Exceptions\ProfileUserNotFound;
use ByRcsc\LaravelDevLogin\Resolvers\FindUserByEmail;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\NullDispatcher;

/**
 * Turns a profile into a logged-in session.
 *
 * The order is resolve the user, then drive the guard, and the guard is
 * Laravel's own: this class authenticates nothing itself. Everything that
 * makes a real login a login - the session regeneration, the remember cookie,
 * the `Login` event - happens because `SessionGuard::login()` does it.
 */
final class Authenticator
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly Container $container,
        private readonly Repository $config,
        private readonly Dispatcher $events,
    ) {}

    public function login(Profile $profile): Authenticatable
    {
        $user = $this->resolverFor($profile)->resolve($profile);

        if ($user === null) {
            throw ProfileUserNotFound::make($profile);
        }

        $guard = $this->auth->guard($profile->guard);

        // The repository has already refused any profile naming a guard that
        // is not a session guard. A profile that names none can still land on
        // an application whose default guard is a token guard, and this is
        // where that stops.
        if (! $guard instanceof StatefulGuard) {
            throw InvalidProfile::notASessionGuard($profile->key, $profile->guard ?? 'default');
        }

        if ($profile->fireLoginEvent || ! $guard instanceof SessionGuard) {
            $guard->login($user, $profile->remember);

            return $user;
        }

        $this->loginQuietly($guard, $user, $profile->remember);

        return $user;
    }

    /**
     * A profile that opts out gets a guard whose dispatcher swallows the
     * events the login would have fired, and gets its own dispatcher back
     * afterwards. The alternative is reimplementing `login()`, and a login that
     * is only mostly a login is the bug this package exists to avoid.
     */
    private function loginQuietly(SessionGuard $guard, Authenticatable $user, bool $remember): void
    {
        $guard->setDispatcher(new NullDispatcher($this->events));

        try {
            $guard->login($user, $remember);
        } finally {
            $guard->setDispatcher($this->events);
        }
    }

    /**
     * The profile's own resolver wins, then the configured one, then the
     * resolver the package ships.
     */
    private function resolverFor(Profile $profile): UserResolver
    {
        if ($profile->resolver !== null) {
            return $this->makeProfileResolver($profile, $profile->resolver);
        }

        $configured = $this->config->get('dev-login.resolver');

        if ($configured === null) {
            return $this->makeProfileResolver($profile, FindUserByEmail::class);
        }

        // Checked as a class-string before the container is asked for it, so a
        // class that is not a resolver fails with a message about config
        // rather than with an unresolvable-dependency error.
        if (! is_string($configured) || ! is_a($configured, UserResolver::class, allow_string: true)) {
            throw InvalidConfiguration::resolver(
                is_string($configured) ? $configured : get_debug_type($configured),
                UserResolver::class,
            );
        }

        $instance = $this->container->make($configured);

        if (! $instance instanceof UserResolver) {
            throw InvalidConfiguration::resolver($configured, UserResolver::class);
        }

        return $instance;
    }

    /**
     * The repository validates the class-string a profile names, so this is
     * the belt to that pair of braces: an application can hand a `Profile` to
     * this class without going through config at all.
     */
    private function makeProfileResolver(Profile $profile, string $resolver): UserResolver
    {
        if (! is_a($resolver, UserResolver::class, allow_string: true)) {
            throw InvalidProfile::notAResolver($profile->key, $resolver, UserResolver::class);
        }

        $instance = $this->container->make($resolver);

        if (! $instance instanceof UserResolver) {
            throw InvalidProfile::notAResolver($profile->key, $resolver, UserResolver::class);
        }

        return $instance;
    }
}
