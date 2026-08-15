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

## The demo loop

```bash
composer build                      # migrate + seed from clean
php vendor/bin/testbench serve      # then open http://localhost:8000/dev-login
```

`composer build` creates the SQLite database, runs the skeleton's migrations,
and seeds two users: `admin@example.com` and `member@example.com`. The package
ships no migrations of its own, because it writes nothing.

`http://localhost:8000/` reports who you are currently logged in as, which is
the whole of the demo's own UI. Everything else you are looking at is the page
the package ships.

Tear it all down again with `composer clear`.

## Why localhost

`allowed_hosts` defaults to `localhost`, `127.0.0.1`, and `*.test`, and
`testbench serve` binds to `localhost`. Serve the demo on any other host and
`/dev-login` returns a 404 rather than a 403. That is the host gate working,
not the demo breaking: when a gate says no, the routes never register, so
there is nothing there to forbid.

## What lives where

- `app/Models/User.php` - the stock Laravel application user, on the
  skeleton's own `users` table. The package never names this class.
- `app/Providers/WorkbenchServiceProvider.php` - the config a real
  application would set: the user model, and the package switched on.
- `database/seeders/DatabaseSeeder.php` - the two users the demo's profiles
  point at. Seeding is the application's job, and this is that job.
- `routes/web.php` - the landing page a dev login redirects onto.
