<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * The demo app's back-office user, behind its own guard and its own provider.
 *
 * It sits on the same table as the ordinary user on purpose: what the demo
 * needs to show is that the package resolves and authenticates through
 * whichever provider the profile's guard names, not that an application can
 * have two tables.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 */
final class Administrator extends Authenticatable
{
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];
}
