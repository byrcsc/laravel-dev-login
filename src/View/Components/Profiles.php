<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\View\Components;

use ByRcsc\LaravelDevLogin\Gatekeeper;
use ByRcsc\LaravelDevLogin\ProfileRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * The profile buttons, as a component an application can drop into a page of
 * its own.
 *
 * The package's page is a thin wrapper around this, which is the point: the
 * embeddable thing falls out of building the page rather than being a second
 * surface to keep in step with it.
 *
 * It asks the gates again on its own account. The routes are already gone when
 * a gate says no, but this can be rendered from a route the application owns,
 * and a list of ways into an application is not something to render on a host
 * nobody allowed.
 */
final class Profiles extends Component
{
    public function __construct(
        private readonly ProfileRepository $profiles,
        private readonly Gatekeeper $gatekeeper,
        private readonly Request $request,
    ) {}

    public function render(): View|string
    {
        if (! $this->gatekeeper->passes() || ! $this->gatekeeper->hostIsAllowed($this->request->getHost())) {
            return '';
        }

        return view('dev-login::components.profiles', [
            'groups' => $this->profiles->groupedByTenant(),
        ]);
    }
}
