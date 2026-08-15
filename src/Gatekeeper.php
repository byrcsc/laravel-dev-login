<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;

/**
 * Answers one question: may the package operate right now.
 *
 * The checks are deliberately separate and deliberately all required. Three of
 * them can be answered while the application boots, which is what decides
 * whether the routes register at all; the host is only known once a request
 * arrives, so it is asked again per request by the host middleware.
 *
 * Config this class cannot make sense of is treated as an empty list, so a
 * malformed `environments` or `allowed_hosts` value shuts the gate rather than
 * opening it.
 */
final class Gatekeeper
{
    public function __construct(
        private readonly Application $app,
        private readonly Repository $config,
    ) {}

    /**
     * Every boot-time gate agrees. This is what route registration hangs on.
     */
    public function passes(): bool
    {
        return $this->isEnabled()
            && ! $this->isProduction()
            && $this->environmentIsAllowed();
    }

    /**
     * The one failure the package refuses to absorb quietly.
     */
    public function mustRefuseToBoot(): bool
    {
        return $this->isEnabled() && $this->isProduction();
    }

    /**
     * Hosts are matched case-insensitively, and a leading `*.` matches
     * subdomains only: `*.test` covers `acme.test` but never `test` itself.
     * A pattern that is not an exact host or a `*.` prefix matches nothing.
     */
    public function hostIsAllowed(string $host): bool
    {
        $host = Str::lower($host);

        foreach ($this->allowedHosts() as $pattern) {
            $pattern = Str::lower($pattern);

            if (str_starts_with($pattern, '*.')) {
                $suffix = substr($pattern, 1);

                if (str_ends_with($host, $suffix) && strlen($host) > strlen($suffix)) {
                    return true;
                }

                continue;
            }

            if ($host === $pattern) {
                return true;
            }
        }

        return false;
    }

    private function isEnabled(): bool
    {
        return (bool) $this->config->get('dev-login.enabled', false);
    }

    private function environmentIsAllowed(): bool
    {
        return in_array($this->environment(), $this->environments(), strict: true);
    }

    /**
     * Matched case-insensitively, unlike the environment allowlist. An
     * `APP_ENV=Production` that slid past the refusal would turn the loudest
     * thing this package does into the silence it exists to prevent.
     */
    private function isProduction(): bool
    {
        return Str::lower($this->environment()) === 'production';
    }

    private function environment(): string
    {
        return (string) $this->app->environment();
    }

    /**
     * @return list<string>
     */
    private function environments(): array
    {
        return $this->stringList($this->config->get('dev-login.environments', []));
    }

    /**
     * @return list<string>
     */
    private function allowedHosts(): array
    {
        return $this->stringList($this->config->get('dev-login.allowed_hosts', []));
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(strval(...), array_filter($value, is_scalar(...))));
    }
}
