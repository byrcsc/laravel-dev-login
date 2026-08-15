<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Contracts;

use ByRcsc\LaravelDevLogin\Profile;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Turns a profile into the user it names.
 *
 * Returning `null` means the user does not exist, and the caller turns that
 * into a descriptive failure. A resolver never creates the user it cannot
 * find: the package does not write to your users table, and an application
 * that wants factory-made users writes a resolver that does.
 */
interface UserResolver
{
    public function resolve(Profile $profile): ?Authenticatable;
}
