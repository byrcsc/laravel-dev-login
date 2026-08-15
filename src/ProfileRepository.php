<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\Exceptions\InvalidProfile;
use ByRcsc\LaravelDevLogin\Exceptions\ProfileNotFound;
use Illuminate\Contracts\Config\Repository;

/**
 * Reads the configured profiles and hands out validated ones.
 *
 * Validation happens here rather than at boot, so a typo in one profile is a
 * readable exception on the page that needs it instead of an application that
 * will not start. Every message names the profile key.
 */
final class ProfileRepository
{
    /**
     * Everything a profile may configure. Anything else is a typo, and a typo
     * that is quietly ignored is a setting that quietly does nothing.
     */
    private const KEYS = [
        'label',
        'email',
        'guard',
        'remember',
        'tenant',
        'redirect',
        'resolver',
        'fire_login_event',
    ];

    public function __construct(private readonly Repository $config) {}

    /**
     * @return array<string, Profile>
     */
    public function all(): array
    {
        $profiles = [];

        foreach ($this->configured() as $key => $settings) {
            $profiles[$key] = $this->hydrate($key, $settings);
        }

        return $profiles;
    }

    public function find(string $key): Profile
    {
        $configured = $this->configured();

        if (! array_key_exists($key, $configured)) {
            throw ProfileNotFound::make($key, array_keys($configured));
        }

        return $this->hydrate($key, $configured[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    private function configured(): array
    {
        $profiles = $this->config->get('dev-login.profiles', []);

        if (! is_array($profiles)) {
            return [];
        }

        /** @var array<string, mixed> $profiles */
        return $profiles;
    }

    private function hydrate(string $key, mixed $settings): Profile
    {
        if (! is_array($settings)) {
            throw InvalidProfile::notAnArray($key);
        }

        /** @var array<string, mixed> $settings */
        $unknown = array_diff(array_keys($settings), self::KEYS);

        if ($unknown !== []) {
            throw InvalidProfile::unknownKeys($key, implode(', ', $unknown));
        }

        $guard = $this->guard($key, $settings['guard'] ?? null);

        return new Profile(
            key: $key,
            label: $this->requiredString($key, $settings, 'label'),
            email: $this->requiredString($key, $settings, 'email'),
            guard: $guard,
            remember: $this->boolean($key, $settings, 'remember', false),
            tenant: $this->tenant($key, $settings['tenant'] ?? null),
            redirect: $this->optionalString($key, $settings, 'redirect'),
            resolver: $this->resolver($key, $settings['resolver'] ?? null),
            fireLoginEvent: $this->boolean($key, $settings, 'fire_login_event', true),
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function requiredString(string $key, array $settings, string $field): string
    {
        $value = $settings[$field] ?? null;

        if ($value === null || $value === '') {
            throw InvalidProfile::missing($key, $field);
        }

        if (! is_string($value)) {
            throw InvalidProfile::badType($key, $field, 'a string');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function optionalString(string $key, array $settings, string $field): ?string
    {
        $value = $settings[$field] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw InvalidProfile::badType($key, $field, 'a string');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function boolean(string $key, array $settings, string $field, bool $default): bool
    {
        $value = $settings[$field] ?? $default;

        if (! is_bool($value)) {
            throw InvalidProfile::badType($key, $field, 'true or false');
        }

        return $value;
    }

    private function tenant(string $key, mixed $tenant): string|int|null
    {
        if ($tenant === null || is_string($tenant) || is_int($tenant)) {
            return $tenant;
        }

        throw InvalidProfile::badType($key, 'tenant', 'a string, an integer, or null');
    }

    /**
     * Guards are checked against the application's own `config/auth.php`,
     * because a profile naming a guard that does not exist is a config error
     * the developer can fix, and a guard that is not a session guard is one
     * this package cannot drive at all.
     */
    private function guard(string $key, mixed $guard): ?string
    {
        if ($guard === null) {
            return null;
        }

        if (! is_string($guard)) {
            throw InvalidProfile::badType($key, 'guard', 'a string');
        }

        $configured = $this->config->get("auth.guards.{$guard}");

        if (! is_array($configured)) {
            throw InvalidProfile::unknownGuard($key, $guard);
        }

        if (($configured['driver'] ?? null) !== 'session') {
            throw InvalidProfile::notASessionGuard($key, $guard);
        }

        return $guard;
    }

    /**
     * @return class-string<UserResolver>|null
     */
    private function resolver(string $key, mixed $resolver): ?string
    {
        if ($resolver === null) {
            return null;
        }

        if (! is_string($resolver) || ! is_a($resolver, UserResolver::class, allow_string: true)) {
            throw InvalidProfile::notAResolver(
                $key,
                is_string($resolver) ? $resolver : get_debug_type($resolver),
                UserResolver::class,
            );
        }

        return $resolver;
    }
}
