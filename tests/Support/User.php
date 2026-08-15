<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Tests\Support;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * The suite's user, on the framework's own `users` table.
 *
 * A model of the application's rather than one of the package's, because that
 * is the only kind this package ever meets: it resolves whatever the guard's
 * user provider hands back and never names a model of its own.
 */
final class User extends Authenticatable
{
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];
}
