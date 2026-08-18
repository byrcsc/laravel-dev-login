# Workbench

A bootable demo application that installs the package the way a real
application would. It is the package's own proof that the dev login page
composes into an application, and the manual QA surface before a release.

A dev login page is a thing you look at. Tests can assert that a guard was
driven and a redirect was issued, but only a browser can tell you whether the
page reads as obviously development-only at a glance, which is a safety
feature and not a cosmetic one. That is what this app is for.

For the package itself, see the [package README](../README.md). The workbench
ships in the repository but not in the Composer dist archive, so nothing here
is installed alongside the package.

## Getting it running

```bash
composer build                      # create the database, migrate, seed
php vendor/bin/testbench serve      # then open http://localhost:8000/dev-login
```

`composer build` creates the SQLite database, runs the skeleton's migrations,
and seeds five users:

| Name | Email |
|---|---|
| Avery Admin | `admin@example.com` |
| Morgan Member | `member@example.com` |
| Sam Support | `support@example.com` |
| Acme Owner | `owner@acme.test` |
| Globex Owner | `owner@globex.test` |

The package ships no migrations of its own, because it writes nothing.

Tear it all down again with `composer clear`.

## What is configured

`app/Providers/WorkbenchServiceProvider.php` is the demo's stand-in for the
`config/dev-login.php` and `config/auth.php` edits a real application makes.

Two guards, over two providers:

| Guard | Provider | Model |
|---|---|---|
| `web` | `users` | `Workbench\App\Models\User` |
| `admin` | `administrators` | `Workbench\App\Models\Administrator` |

Nine profiles: every shape the package supports, and every failure it can
produce. The button labels are what you will see on the page.

| Button | What it is there to show |
|---|---|
| Admin (admin guard) | A profile on a guard that is not the default one |
| Member, remembered | `remember: true`, and the cookie it leaves |
| Support, straight to the profile page | A profile with a `redirect` of its own |
| Support, without the login event | `fire_login_event: false` |
| Whoever was seeded first (this app's own resolver) | A `resolver` of the application's own |
| Owner, under the `acme` heading | A tenant-bound profile, and tenant grouping |
| Owner, under the `globex` heading | The second tenant, and switching between them |
| Owner (this tenant does not exist) | A tenant the resolver refuses |
| Nobody (this one is meant to fail) | A profile pointing at a user nobody seeded |

Tenancy is `app/Tenancy/RememberTheTenant.php`, which is the whole of what an
application writes against the `TenantResolver` contract. It writes the tenant
into the session so the page after the redirect can show it; a real one would
swap a database connection.

The demo also hangs a listener on Laravel's `Login` event, so that the profile
which opts out of that event has something visible to opt out of.

## The demo loop

Three pages, and everything below happens on them:

- `/dev-login` is the package's page.
- `/` and `/whoami` report who you are on each guard, which tenant is current,
  and which user the `Login` listener last saw. They name themselves at the
  top so you can see which one you landed on. That is the whole of the demo's
  own UI.
- `/our-login` is a login page the demo owns, embedding
  `<x-dev-login::profiles />`.

### 1. The page itself

Open `http://localhost:8000/dev-login`.

- The application name, and the environment as a yellow `local` badge.
- Three tenant headings, `acme`, `globex`, and `initech`, each over one Owner
  button.
- The profiles with no tenant last, under no heading.
- A line under the buttons saying anybody who can reach the page can sign in
  as any profile on it.
- Each profile shows its label, email address, and authentication guard.

### 2. Logging in, per guard

Click **Member, remembered**. You land on `/`, which reports:

```
Home
====

web: Morgan Member <member@example.com>
admin: nobody
tenant: none
last login event: user 2
```

Your browser now holds a `remember_web_*` cookie.

Click **Admin (admin guard)**. `/` now reports both: `web` is still Morgan, and
`admin` is Avery. Two guards, two sessions, one click each.

Click **Support, straight to the profile page**. You land on `/whoami`, which
says so at the top, because that profile names its own redirect.

### 3. Re-authenticating

While logged in as Morgan, click any other `web` profile. Each click replaces
the last one on that guard, with no error and no logout step in between.

### 4. The login event, and opting out of it

Click **Member, remembered**, then **Support, without the login event**. The
`web` line changes to Sam Support, and `last login event` stays on Morgan's
id: the second login happened without firing the event that the first one
fired.

### 5. A resolver of the application's own

Click **Whoever was seeded first (this app's own resolver)**. You are logged in
as Avery Admin, whom that profile never names: its `email` is
`ignored@example.com`, and `app/Auth/TheOldestAccount.php` ignored it. That is
the user resolver seam.

### 6. Tenants

- Click **Owner** under `acme`. `/` reports `tenant: acme`, and the user is the
  Acme owner.
- Click **Owner** under `globex`. Both the tenant and the user change.
- Click **Owner (this tenant does not exist)** under `initech`. The login stops
  with `Dev login profile [initech-owner] could not make the tenant [initech]
  current.`, and `/` shows you are still whoever you were: a tenancy failure
  authenticates nobody.

The tenant is made current before the user is looked up, which is the order the
contract promises. `RememberTheTenant` is where to put a `dump()` if you want
to watch it happen.

### 7. The failure everybody meets first

Click **Nobody (this one is meant to fail)**. The error page reads:

> Dev login profile [unseeded] resolves to nobody@example.com, which does not
> exist. Did you run your seeder?

That is the config-to-seeder drift detector, and it is the exception you will
meet if your profiles and your seeder ever disagree.

### 8. The embedded component

Open `http://localhost:8000/our-login`. A page the package did not write, with
the same buttons on it, brought in by one tag. It looks like itself: the
component carries its own styles.

### 9. The gates

Each of these is a thing to watch refuse. Restart `serve` after each change,
and note that a variable set in your shell beats `workbench/.env`.

**The host gate.** The check is on the host the request arrived on, so the
quickest way to see it is to send a different one:

```bash
curl -i -H 'Host: dev-login.example.com' http://localhost:8000/dev-login
```

**404**, not 403. When a gate says no the routes never register, so there is
nothing there to forbid. To see it in a browser instead, point a hostname that
is not `localhost`, `127.0.0.1`, or `*.test` at `127.0.0.1` in `/etc/hosts` and
visit that. `/our-login` still renders, with no buttons on it: the component
asks the same gate.

**The environment allowlist.** Boot the demo in an environment nobody opted
into:

```bash
APP_ENV=staging php vendor/bin/testbench serve
```

`/dev-login` is a 404. `/` still works, because the application is fine; it is
the package that stood down.

**The enable flag.** Comment out the `dev-login.enabled` line in
`WorkbenchServiceProvider` and restart. Same 404, from a different gate.

**The production refusal.** Put the demo in production with the flag still on:

```bash
APP_ENV=production php vendor/bin/testbench serve
```

Every request fails, and so does `php vendor/bin/testbench` on its own:

> Dev login is enabled while APP_ENV=production. Refusing to boot. Remove
> DEV_LOGIN_ENABLED from the production environment, or set it to false.

Loud, and not a silent no-op, because a silent no-op is indistinguishable from
a package that is working.

## Why localhost

`allowed_hosts` defaults to `localhost`, `127.0.0.1`, and `*.test`, and
`testbench serve` binds to `localhost`. That is the one gate that does not
trust the application's own configuration, which is why the demo's own URL is
part of the demo.

## What lives where

- `app/Models/User.php` - the stock Laravel application user, on the
  skeleton's own `users` table. The package never names this class.
- `app/Models/Administrator.php` - the same table behind a second guard and a
  provider of its own.
- `app/Auth/TheOldestAccount.php` - a user resolver the application wrote.
- `app/Providers/WorkbenchServiceProvider.php` - the config a real
  application would set: guards, providers, profiles, and the package
  switched on.
- `app/Tenancy/RememberTheTenant.php` - the demo's tenancy package, in one
  class.
- `database/seeders/DatabaseSeeder.php` - the users the demo's profiles point
  at. Seeding is the application's job, and this is that job.
- `resources/views/our-login.blade.php` - the demo's own login page, with the
  package's component embedded in it.
- `routes/web.php` - the pages that report who you are.
