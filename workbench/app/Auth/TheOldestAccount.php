<?php

declare(strict_types=1);

namespace Workbench\App\Auth;

use ByRcsc\LaravelDevLogin\Contracts\UserResolver;
use ByRcsc\LaravelDevLogin\Profile;
use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Models\User;

/**
 * A user resolver of the application's own, ignoring the profile's email
 * entirely and taking whoever was seeded first.
 *
 * The point is not that this is useful. It is that the seam exists: an
 * application whose users are not found by email address writes one of these
 * and names it on the profile, and the package's own resolver never runs.
 */
final class TheOldestAccount implements UserResolver
{
    public function resolve(Profile $profile): ?Authenticatable
    {
        return User::query()->oldest('id')->first();
    }
}
