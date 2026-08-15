<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Workbench\App\Providers\WorkbenchServiceProvider;
use Workbench\App\Tenancy\RememberTheTenant;

/*
 * The demo's own pages, and nothing more than the questions the package's page
 * cannot answer for itself: who am I now, on which guard, in which tenant, and
 * did the login event reach the application.
 *
 * There is no UI here on purpose. The page under test is the one the package
 * ships.
 */

$whoami = function (Request $request, string $page): Response {
    $lines = [$page, str_repeat('=', strlen($page)), ''];

    foreach (['web', 'admin'] as $guard) {
        $user = Auth::guard($guard)->user();

        $lines[] = $user instanceof Model
            ? "{$guard}: {$user->getAttribute('name')} <{$user->getAttribute('email')}>"
            : "{$guard}: nobody";
    }

    $tenant = $request->session()->get(RememberTheTenant::KEY);
    $event = $request->session()->get(WorkbenchServiceProvider::LAST_LOGIN_EVENT);

    $lines[] = 'tenant: '.(is_string($tenant) ? $tenant : 'none');
    $lines[] = 'last login event: '.(is_string($event) ? "user {$event}" : 'none seen');
    $lines[] = '';
    $lines[] = 'The dev login page is at /dev-login, and the demo has one of its own at /our-login.';

    return response(implode("\n", $lines))->header('Content-Type', 'text/plain');
};

Route::get('/', fn (Request $request): Response => $whoami($request, 'Home'))->name('workbench.home');

// The redirect one of the demo's profiles names, so the redirect chain has
// somewhere of its own to land, and says which page it is so that landing here
// is visible rather than deduced from the address bar.
Route::get('/whoami', fn (Request $request): Response => $whoami($request, 'Who am I'))
    ->name('workbench.whoami');

/*
 * The application's own login page, embedding the package's component. This is
 * the second half of the UI claim: the same buttons, on a page the package did
 * not write.
 */
Route::get('/our-login', fn () => view('workbench::our-login'))->name('workbench.login');
