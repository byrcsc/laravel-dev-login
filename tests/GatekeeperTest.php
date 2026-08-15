<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\Gatekeeper;

/*
 * The gate answers from live config, so these read it directly. What happens
 * at boot - the production refusal, and whether the routes register at all -
 * is asserted against a real application in SafetyGatesTest.
 */

/*
 * Naming `production` in the environment list is not a way in. The production
 * check is asked separately, so the list cannot be edited into an override.
 */
it('never passes in production, even when production is on the list', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    config()->set('dev-login.enabled', true);
    config()->set('dev-login.environments', ['local', 'testing', 'production']);

    expect(app(Gatekeeper::class)->passes())->toBeFalse()
        ->and(app(Gatekeeper::class)->mustRefuseToBoot())->toBeTrue();
});

it('recognises production whatever case it is written in', function (string $environment): void {
    app()->detectEnvironment(fn (): string => $environment);

    config()->set('dev-login.enabled', true);
    config()->set('dev-login.environments', [$environment]);

    expect(app(Gatekeeper::class)->passes())->toBeFalse()
        ->and(app(Gatekeeper::class)->mustRefuseToBoot())->toBeTrue();
})->with(['production', 'Production', 'PRODUCTION']);

it('only demands a refusal when the package is switched on in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    config()->set('dev-login.enabled', false);

    expect(app(Gatekeeper::class)->mustRefuseToBoot())->toBeFalse();
});

it('shuts the gate on config it cannot read as a list', function (mixed $environments): void {
    config()->set('dev-login.enabled', true);
    config()->set('dev-login.environments', $environments);

    expect(app(Gatekeeper::class)->passes())->toBeFalse();
})->with([
    'a bare string' => 'testing',
    'null' => null,
    'an empty list' => [[]],
]);

it('matches exact hosts', function (string $host, bool $allowed): void {
    config()->set('dev-login.allowed_hosts', ['localhost', '127.0.0.1']);

    expect(app(Gatekeeper::class)->hostIsAllowed($host))->toBe($allowed);
})->with([
    ['localhost', true],
    ['LOCALHOST', true],
    ['127.0.0.1', true],
    ['localhost.evil.com', false],
    ['evil.com', false],
    ['', false],
]);

it('matches wildcard hosts against subdomains and only subdomains', function (string $host, bool $allowed): void {
    config()->set('dev-login.allowed_hosts', ['*.test']);

    expect(app(Gatekeeper::class)->hostIsAllowed($host))->toBe($allowed);
})->with([
    ['acme.test', true],
    ['ACME.test', true],
    ['tenant.acme.test', true],
    ['test', false],
    ['acme.test.evil.com', false],
    ['acmetest', false],
    ['acme.testing', false],
]);

/*
 * A bare `*` is not a way to allow every host. Wildcards only ever stand for
 * the subdomain part, so a pattern that is neither an exact host nor `*.`
 * something matches nothing at all, which is the direction a safety gate
 * should fail in.
 */
it('reads a bare wildcard as a host nobody has', function (): void {
    config()->set('dev-login.allowed_hosts', ['*']);

    expect(app(Gatekeeper::class)->hostIsAllowed('evil.com'))->toBeFalse()
        ->and(app(Gatekeeper::class)->hostIsAllowed('localhost'))->toBeFalse();
});

it('allows nothing when the host list is empty', function (): void {
    config()->set('dev-login.allowed_hosts', []);

    expect(app(Gatekeeper::class)->hostIsAllowed('localhost'))->toBeFalse();
});
