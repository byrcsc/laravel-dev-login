<?php

declare(strict_types=1);

namespace Workbench\App\Tenancy;

use ByRcsc\LaravelDevLogin\Contracts\TenantResolver;
use ByRcsc\LaravelDevLogin\Exceptions\TenantNotResolved;
use ByRcsc\LaravelDevLogin\Profile;
use Illuminate\Contracts\Session\Session;

/**
 * The demo's tenancy package, in one class.
 *
 * A real one swaps a database connection or prefixes a cache; this writes the
 * tenant into the session, because all the demo has to prove is that the
 * tenant was made current before the user was looked up, and that the page
 * after the redirect can tell. The package ships the contract and no adapter,
 * so this is exactly the shape an application writes for itself.
 */
final class RememberTheTenant implements TenantResolver
{
    public const KEY = 'workbench.tenant';

    /**
     * The tenants this demo has. A profile naming anything else is how the
     * failure path gets exercised by hand.
     *
     * @var list<string>
     */
    public const TENANTS = ['acme', 'globex'];

    public function __construct(private readonly Session $session) {}

    public function makeCurrent(Profile $profile): void
    {
        if (! in_array($profile->tenant, self::TENANTS, strict: true)) {
            throw TenantNotResolved::make($profile);
        }

        $this->session->put(self::KEY, $profile->tenant);
    }
}
