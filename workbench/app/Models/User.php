<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Workbench\Database\Factories\UserFactory;

/**
 * The demo app's user, on the Testbench skeleton's own `users` table.
 *
 * Deliberately the stock Laravel application user and nothing more. That is
 * the whole claim this package makes: it authenticates the users an
 * application already has, through the guard that application already
 * configured, and it never writes to this table.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];
}
