# Laravel Dev Login

[![Latest Version on Packagist](https://img.shields.io/packagist/v/byrcsc/laravel-dev-login.svg?style=flat-square)](https://packagist.org/packages/byrcsc/laravel-dev-login)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/byrcsc/laravel-dev-login/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/byrcsc/laravel-dev-login/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/byrcsc/laravel-dev-login/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/byrcsc/laravel-dev-login/actions?query=workflow%3APHPStan+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/byrcsc/laravel-dev-login.svg?style=flat-square)](https://packagist.org/packages/byrcsc/laravel-dev-login)

> **In progress, pre-release.**

A development login page for Laravel. Configure named profiles, visit
`/dev-login`, and click one to be authenticated as that user through your own
guard. Profiles can carry a tenant, so a click can put you inside the right
tenant as well as the right account.

Your users stay yours. The package never writes to your users table, never
touches passwords, and never authenticates anything itself: it drives Laravel's
session guard and gets out of the way. Five independent gates decide whether it
runs at all, and none of them is "we installed it with `--dev`".

| Laravel | Tested PHP versions |
|---|---|
| 12.x | 8.3, 8.4 |
| 13.x | 8.3, 8.4 |

## Installation

```bash
composer require --dev byrcsc/laravel-dev-login
```

Publish the config file:

```bash
php artisan vendor:publish --tag=dev-login-config
```

Then turn it on, in your own `.env` and nowhere else:

```dotenv
DEV_LOGIN_ENABLED=true
```

Installing as a dev dependency is the right thing to do and is treated as layer
zero rather than as protection. Every gate below is written on the assumption
that your pipeline might ship dev dependencies anyway.

## What a profile is

A profile is a named, preconfigured way into your application: a user
reference, a guard, an optional tenant, and an optional redirect. It is the one
noun this package introduces.

The page shows one button per profile. Clicking a button resolves the profile
to a user that already exists, makes its tenant current if it has one,
authenticates through the profile's guard, and redirects. That is the whole
model.

Profiles point at users, they do not create them. Seeding is your
application's job, which means the published config is a good place to read the
same env values your seeder does: two files that share a source cannot drift
apart.

## Quick start

Add profiles to `config/dev-login.php`. The minimal one is two lines:

```php
'profiles' => [
    'admin' => [
        'label' => 'Admin',
        'email' => 'admin@example.com',
    ],
],
```

Visit `/dev-login` and click Admin. You are logged in as
`admin@example.com` on the default guard, and back on `/`.

The full shape of a profile, with every key at its default:

```php
'profiles' => [
    'admin' => [
        'label'            => 'Admin',
        'email'            => 'admin@example.com',
        'guard'            => null,   // null uses the default guard
        'remember'         => false,
        'tenant'           => null,   // handed to the tenant resolver as-is
        'redirect'         => null,   // null follows the precedence chain
        'resolver'         => null,   // null uses the configured resolver
        'fire_login_event' => true,   // false logs in silently
    ],
],
```

The array key is the route parameter and `label` is the button text. `guard`
names a session guard from your own `config/auth.php`, and a profile that names
anything else says so rather than failing later.

A click authenticates through Laravel's session guard, which means Laravel's
`Login` event fires: side effects an application hangs on a real login, such as
recording a last-seen timestamp, also run on a dev login. That is the default
because a login that skips them is not the login you are trying to reproduce.
Setting `fire_login_event` to `false` silences the guard for that one login,
which means `Login` and the `Authenticated` event fired alongside it.

## Safety

Development-only is enforced in five independent layers. All five ship in v1,
and all five have to agree before a single route exists.

1. **Environment allowlist.** `['local', 'testing']` by default. Staging is an
   explicit opt-in a team writes into config, which is an edit somebody reviews
   rather than a default nobody notices.
2. **Enable flag.** `DEV_LOGIN_ENABLED`, separate from the environment. Being
   in `local` is never on its own enough.
3. **Production refusal.** Force-enabling under `APP_ENV=production` throws at
   boot. Loudly, never as a silent no-op, because a silent no-op is
   indistinguishable from a package that is working.
4. **No routes when disabled.** When any gate says no, the routes do not
   register. The endpoints 404, never 403: a 403 tells a stranger the page is
   there.
5. **Allowed hosts.** `['localhost', '127.0.0.1', '*.test']` by default. This
   is the one layer that does not trust your application's own configuration,
   so it still holds when an `.env` reaches a machine it should not have.

The page also shows your current environment as a badge. That is a deliberate
sixth layer aimed at the human looking at the screen, and it is why the page is
not themeable into something that looks like your real login.

## Tenancy

A profile can name a tenant, and the login flow becomes: resolve the tenant,
make it current, authenticate, redirect.

How a tenant becomes the current one is something only your tenancy package
knows, so v1 ships the contract and no adapter:

```php
namespace ByRcsc\LaravelDevLogin\Contracts;

interface TenantResolver
{
    public function makeCurrent(Profile $profile): void;
}
```

Point `tenant_resolver` at your implementation. The value handed to it is
whatever scalar you put in the profile's `tenant` key, untouched.

## Routes and redirects

| Method | URI | Name |
|---|---|---|
| `GET` | `/dev-login` | `dev-login.show` |
| `POST` | `/dev-login/{profile}` | `dev-login.attempt` |

The POST route is CSRF-protected, and there is deliberately no login-by-GET
link: a GET that logs you in can be fired by an image tag or a prefetch. Both
the path and the middleware stack are configurable.

After authenticating, the redirect is decided in this order:

1. the profile's own `redirect`
2. the session's intended URL
3. `default_redirect` from the config
4. `/`

A tenant-bound profile skips step two, because a URL captured in one tenant
rarely means anything in another.

## Customizing the page

The page is one publishable Blade view rendering a `<x-dev-login::profiles>`
component, which you can drop anywhere yourself. It has no CSS dependencies,
just a small inline style block, and it groups the buttons by tenant when your
profiles name any.

```bash
php artisan vendor:publish --tag=dev-login-views
```

Publishing the views is the whole customization story. There is no theming
config, and that is a decision rather than an omission: see Safety.

## Out of scope

Boundaries, not gaps:

- **Impersonation**, or switching users from inside the application.
- **User creation and seeding.** The package never writes to your users table.
  A profile pointing at a missing user throws a descriptive exception, which
  doubles as how you find out your config and your seeder have drifted.
- **API and token guards.** Session guards only.
- **Production or support login.** The gates make it impossible rather than
  inconvenient.
- **UI theming** beyond publishing the views.
- **Being a login system.** No passwords, registration, throttling, or 2FA. It
  drives the guard you already have.

## Versioning

The package follows [semantic versioning](https://semver.org/spec/v2.0.0.html).

- Upgrading within `1.x` is safe. Nothing you use will break.
- Only a new major version, like `2.0.0`, can break your code.
- If the README or the documentation describes it, it is safe to build on.
  If they don't, treat it as internal and expect it to change.

Bug fixes go into the newest version only. To get a fix, upgrade to it.

## Questions and issues

- **Stuck, or have an idea?** Start a
  [discussion](https://github.com/byrcsc/laravel-dev-login/discussions). Usage questions
  and feature ideas both live there.
- **Found a bug you can reproduce?**
  [Open an issue](https://github.com/byrcsc/laravel-dev-login/issues). A failing test is
  the fastest way to a fix, and a short reproduction is the next best thing.
- **Found a security problem?** Please don't open a public issue. See
  [SECURITY.md](SECURITY.md) for how to report it privately.
- **Planning a pull request?** [CONTRIBUTING.md](CONTRIBUTING.md) covers the
  setup and the three checks it needs to pass.

This package is maintained by one person, so replies can take a while.
Everything gets read.

## License

MIT. See [LICENSE.md](LICENSE.md). Changelog in [CHANGELOG.md](CHANGELOG.md).
