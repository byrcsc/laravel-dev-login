<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | The master switch, and one of five independent gates. It is deliberately
    | separate from the environment allowlist below: being in `local` is never
    | on its own enough to expose a page that logs anybody in without a
    | password. Both have to say yes.
    |
    | Default off. Turning it on is something a developer does on purpose, in
    | their own `.env`, on their own machine.
    |
    */

    'enabled' => (bool) env('DEV_LOGIN_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | The environments the package is allowed to run in at all. Staging is not
    | here on purpose: a team that wants it writes it in, which is an edit
    | somebody reviews rather than a default nobody notices.
    |
    | `production` is not a value this list accepts. Naming it, with the switch
    | above on, throws at boot rather than quietly doing nothing, because a
    | silent no-op is indistinguishable from a package that is working.
    |
    */

    'environments' => ['local', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | Allowed hosts
    |--------------------------------------------------------------------------
    |
    | The one gate that does not trust the application's own configuration.
    | Everything above can be turned on by an `.env` that reaches the wrong
    | machine; this is checked against the host the request actually arrived
    | on, so a leaked `.env` alone does not open the page to the internet.
    |
    | Entries may be exact hosts or a leading-wildcard pattern such as
    | `*.test`. An empty list allows nothing.
    |
    */

    'allowed_hosts' => ['localhost', '127.0.0.1', '*.test'],

    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | Where the page lives. `GET {path}` renders it and `POST {path}/{profile}`
    | authenticates, named `dev-login.show` and `dev-login.attempt`. There is
    | deliberately no login-by-GET link, so nothing can log you in from an
    | image tag or a prefetch.
    |
    */

    'path' => 'dev-login',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Applied to both routes, ahead of the package's own gate middleware. The
    | session guard needs a session and the POST route is CSRF-protected, so
    | `web` is the floor rather than a suggestion. Add to this list; replacing
    | it wholesale is how the page stops working.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Profiles
    |--------------------------------------------------------------------------
    |
    | The one noun this package introduces: a named, preconfigured way into
    | the application. Each key is the route parameter, `label` is the button
    | text, and a minimal profile is the two lines the first example shows.
    |
    |     'admin' => [
    |         'label'    => 'Admin',
    |         'email'    => 'admin@example.com',
    |         'guard'    => null,   // null uses the default guard
    |         'remember' => false,
    |         'tenant'   => null,   // handed to the tenant resolver as-is
    |         'redirect' => null,   // null follows the precedence chain
    |         'resolver' => null,   // null uses the resolver below
    |     ],
    |
    | Profiles point at users that already exist. Seeding them is your
    | application's job, which is why this file is a good place to read the
    | same env values your seeder does: the two cannot drift if they share a
    | source.
    |
    | No closures anywhere in this file. It has to survive `config:cache`, so
    | every seam is a class-string.
    |
    */

    'profiles' => [],

    /*
    |--------------------------------------------------------------------------
    | User resolver
    |--------------------------------------------------------------------------
    |
    | The class-string of the resolver that turns a profile into a user, used
    | by every profile that does not name its own. `null` uses the resolver
    | the package ships, which looks the profile's `email` up on the guard's
    | own user provider.
    |
    | The package never writes to your users table. An application that wants
    | factory-created users implements a resolver that does.
    |
    */

    'resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Tenant resolver
    |--------------------------------------------------------------------------
    |
    | The class-string of the resolver that makes a profile's tenant current
    | before authentication, or `null` for an application that has no tenancy.
    | The package ships the contract and no adapter: how a tenant becomes the
    | current one is something only your tenancy package knows.
    |
    */

    'tenant_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Default redirect
    |--------------------------------------------------------------------------
    |
    | Where a profile lands when it does not name a `redirect` of its own and
    | there is no intended URL in the session. `null` falls through to `/`.
    |
    | The full precedence is: the profile's `redirect`, then the session's
    | intended URL, then this, then `/`. A tenant-bound profile skips the
    | intended URL, because a URL captured in one tenant rarely means anything
    | in another.
    |
    */

    'default_redirect' => null,

];
