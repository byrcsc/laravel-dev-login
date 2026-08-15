<?php

declare(strict_types=1);

use ByRcsc\LaravelDevLogin\DevLoginServiceProvider;
use ByRcsc\LaravelDevLogin\Exceptions\DevLoginEnabledInProduction;
use ByRcsc\LaravelDevLogin\Gatekeeper;
use ByRcsc\LaravelDevLogin\Http\Middleware\EnsureHostIsAllowed;
use Illuminate\Support\Facades\Route;

/*
 * These boot a whole application, because the boot is what is under test: the
 * production refusal fires there, and whether the routes register at all is
 * decided there. The gate's own truth table is asserted in GatekeeperTest.
 */

it('opens the gate when the flag is on in an allowed environment', function (): void {
    $this->rebootWith(['dev-login.enabled' => true]);

    expect(app(Gatekeeper::class)->passes())->toBeTrue();
});

it('keeps the gate shut while the flag is unset', function (): void {
    $this->rebootWith([]);

    expect(app(Gatekeeper::class)->passes())->toBeFalse();
});

it('keeps the gate shut in an environment nobody opted into', function (): void {
    $this->rebootWith([
        'app.env' => 'staging',
        'dev-login.enabled' => true,
    ]);

    expect(app(Gatekeeper::class)->passes())->toBeFalse();
});

it('opens the gate in staging once a team writes staging in', function (): void {
    $this->rebootWith([
        'app.env' => 'staging',
        'dev-login.enabled' => true,
        'dev-login.environments' => ['local', 'testing', 'staging'],
    ]);

    expect(app(Gatekeeper::class)->passes())->toBeTrue();
});

it('refuses to boot when the flag is on in production', function (): void {
    expect(fn () => $this->rebootWith([
        'app.env' => 'production',
        'dev-login.enabled' => true,
    ]))->toThrow(DevLoginEnabledInProduction::class);
});

it('names the way out in the refusal message', function (): void {
    try {
        $this->rebootWith([
            'app.env' => 'production',
            'dev-login.enabled' => true,
        ]);
    } catch (DevLoginEnabledInProduction $exception) {
        expect($exception->getMessage())
            ->toContain('APP_ENV=production')
            ->toContain('DEV_LOGIN_ENABLED');

        return;
    }

    $this->fail('Booting with the package enabled in production did not throw.');
});

it('boots quietly in production while the flag is off', function (): void {
    $this->rebootWith(['app.env' => 'production']);

    expect(app(Gatekeeper::class)->passes())->toBeFalse();
});

/*
 * The host gate is the only one asked per request, so it is the only one with
 * a response to assert. It answers 404 rather than 403 for the same reason
 * every other gate does: a 403 confirms the page exists.
 */
describe('the host gate', function (): void {
    beforeEach(function (): void {
        Route::middleware(DevLoginServiceProvider::HOST_MIDDLEWARE)
            ->get('/gated', fn (): string => 'through');
    });

    it('is registered under its alias', function (): void {
        expect(app('router')->getMiddleware())
            ->toHaveKey(DevLoginServiceProvider::HOST_MIDDLEWARE, EnsureHostIsAllowed::class);
    });

    it('lets an allowed host through', function (): void {
        $this->get('http://localhost/gated')->assertOk();
    });

    it('answers 404, never 403, on a host nobody allowed', function (): void {
        $response = $this->get('http://dev-login.example.com/gated');

        $response->assertNotFound();

        expect($response->getStatusCode())->not->toBe(403);
    });

    it('lets a wildcard subdomain through', function (): void {
        $this->get('http://acme.test/gated')->assertOk();
    });
});

/*
 * `config:cache` freezes the config file into a `var_export()`ed array that a
 * later process requires back without ever calling `env()` again. That is the
 * failure mode worth testing: a value the file read from the environment has
 * to survive the environment going away.
 */
it('reads the same gates from a config frozen the way config:cache freezes it', function (): void {
    putenv('DEV_LOGIN_ENABLED=true');

    $evaluated = require __DIR__.'/../config/dev-login.php';

    $path = tempnam(sys_get_temp_dir(), 'dev-login-config').'.php';
    file_put_contents($path, '<?php return '.var_export($evaluated, true).';');

    putenv('DEV_LOGIN_ENABLED');

    /** @var array<string, mixed> $frozen */
    $frozen = require $path;
    unlink($path);

    expect($frozen)->toBe($evaluated);

    config()->set('dev-login', $frozen);

    expect(app(Gatekeeper::class)->passes())->toBeTrue()
        ->and(app(Gatekeeper::class)->hostIsAllowed('acme.test'))->toBeTrue()
        ->and(app(Gatekeeper::class)->hostIsAllowed('example.com'))->toBeFalse();
});
