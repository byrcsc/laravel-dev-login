<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Http\Controllers;

use ByRcsc\LaravelDevLogin\Authenticator;
use ByRcsc\LaravelDevLogin\Profile;
use ByRcsc\LaravelDevLogin\ProfileRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The whole HTTP surface: show the profiles, or become one of them.
 */
final class DevLoginController
{
    public function __construct(
        private readonly ProfileRepository $profiles,
        private readonly Authenticator $authenticator,
        private readonly Repository $config,
    ) {}

    public function show(): View
    {
        return view('dev-login::index', [
            'profiles' => $this->profiles->all(),
        ]);
    }

    public function attempt(Request $request, string $profile): RedirectResponse
    {
        $profile = $this->profiles->find($profile);

        $this->authenticator->login($profile);

        return redirect()->to($this->target($request, $profile));
    }

    /**
     * The profile's own redirect, then the intended URL, then the configured
     * default, then the root. A tenant-bound profile skips the intended URL:
     * a URL captured in one tenant rarely means anything in another.
     */
    private function target(Request $request, Profile $profile): string
    {
        if ($profile->redirect !== null) {
            return $profile->redirect;
        }

        if (! $profile->hasTenant()) {
            $intended = $request->session()->pull('url.intended');

            if (is_string($intended)) {
                return $intended;
            }
        }

        $default = $this->config->get('dev-login.default_redirect');

        return is_string($default) ? $default : '/';
    }
}
