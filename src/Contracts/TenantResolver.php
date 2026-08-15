<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Contracts;

use ByRcsc\LaravelDevLogin\Profile;

/**
 * Makes a profile's tenant the current one.
 *
 * How a tenant becomes current is something only your tenancy package knows,
 * so this is the whole of what the package ships: the seam, and no adapter
 * behind it. The value to act on is the profile's `tenant`, exactly as it was
 * written in config.
 *
 * Implementations run before the user is resolved, so the user lookup happens
 * inside the tenant. Returning means the tenant is current; a tenant that
 * cannot be made current throws, and the login stops before anybody is
 * authenticated.
 *
 * There is no matching method to end tenancy, because nothing in the login
 * flow would call one. A failure after this returns - a profile pointing at a
 * user who does not exist, say - leaves the tenant current for the rest of a
 * request that is about to end in an error page.
 */
interface TenantResolver
{
    public function makeCurrent(Profile $profile): void;
}
