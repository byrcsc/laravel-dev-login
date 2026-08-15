<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * The demo's landing page, and the thing a dev login redirects onto.
 *
 * It answers one question - who am I logged in as - because that is the only
 * question the package's own page has any business answering. There is no UI
 * here on purpose: the page under test is the one the package ships.
 */
Route::get('/', function (Request $request): string {
    $user = $request->user();

    return $user === null
        ? 'Nobody is logged in. The dev login page is at /dev-login.'
        : "Logged in as {$user->getAuthIdentifier()}.";
})->name('workbench.home');
