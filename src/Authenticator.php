<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use ByRcsc\LaravelDevLogin\Contracts\TenantResolver;
use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidConfiguration;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidProfile;
use ByRcsc\LaravelDevLogin\Exceptions\ProfileUserNotFound;
use ByRcsc\LaravelDevLogin\Exceptions\TenantNotResolved;
use ByRcsc\LaravelDevLogin\Resolvers\FindUserByEmail;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\NullDispatcher;
use Throwable;

/**
 * Turns a profile into a logged-in session.
 *
 * The order is make the tenant current, resolve the user, then drive the
 * guard - the user lookup happens inside the tenant. The guard is
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
        $guard = $this->auth->guard($profile->guard);

        // The repository has already refused any profile naming a guard that
        // is not a session guard. A profile that names none can still land on
        // an application whose default guard is a token guard, and this is
        // where that stops - before the tenant switch, so config this class
        // could have checked first never moves tenancy state.
        if (! $guard instanceof StatefulGuard) {
            throw InvalidProfile::notASessionGuard($profile->key, $profile->guard ?? 'default');
        }

        $this->makeTenantCurrent($profile);

        $user = $this->resolverFor($profile)->resolve($profile);

        if ($user === null) {
            throw ProfileUserNotFound::make($profile);
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
     * The tenant becomes current before the user is resolved, so the user
     * lookup happens inside the tenant. A profile without a tenant skips this
     * entirely, which is why an application with no tenancy behaves as though
     * the feature were not there.
     */
    private function makeTenantCurrent(Profile $profile): void
    {
        if (! $profile->hasTenant()) {
            return;
        }

        $configured = $this->config->get('dev-login.tenant_resolver');

        if ($configured === null) {
            throw TenantNotResolved::noResolver($profile);
        }

        $resolver = $this->implementing($configured, TenantResolver::class);

        if (! $resolver instanceof TenantResolver) {
            throw InvalidConfiguration::tenantResolver(
                is_string($configured) ? $configured : get_debug_type($configured),
                TenantResolver::class,
            );
        }

        try {
            $resolver->makeCurrent($profile);
        } catch (TenantNotResolved $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Whatever a tenancy package throws, the profile and the tenant it
            // named are what the developer needs to read first.
            throw TenantNotResolved::make($profile, $exception);
        }
    }

    /**
     * The profile's own resolver wins, then the configured one, then the
     * resolver the package ships.
     */
    private function resolverFor(Profile $profile): UserResolver
    {
        $named = $profile->resolver;

        if ($named !== null) {
            $resolver = $this->implementing($named, UserResolver::class);

            // The repository validates the class-string a profile reads from
            // config. This is the belt to that pair of braces: an application
            // can hand a `Profile` to this class without config at all.
            if (! $resolver instanceof UserResolver) {
                throw InvalidProfile::notAResolver($profile->key, $named, UserResolver::class);
            }

            return $resolver;
        }

        $configured = $this->config->get('dev-login.resolver') ?? FindUserByEmail::class;

        $resolver = $this->implementing($configured, UserResolver::class);

        if (! $resolver instanceof UserResolver) {
            throw InvalidConfiguration::resolver(
                is_string($configured) ? $configured : get_debug_type($configured),
                UserResolver::class,
            );
        }

        return $resolver;
    }

    /**
     * Build a configured class-string, or return null when it is not the seam
     * it was configured as.
     *
     * The class-string is checked before the container is asked for it, so a
     * class that is not a resolver fails with a message about config rather
     * than with an unresolvable-dependency error from somewhere further down.
     *
     * @param  class-string  $contract
     */
    private function implementing(mixed $class, string $contract): ?object
    {
        if (! is_string($class) || ! is_a($class, $contract, allow_string: true)) {
            return null;
        }

        $instance = $this->container->make($class);

        return is_object($instance) ? $instance : null;
    }
}
