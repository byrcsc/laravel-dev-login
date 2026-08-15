# Contributing

Thanks for helping. Clear, focused pull requests are easier to review and
maintain.

## Getting set up

```bash
git clone https://github.com/byrcsc/laravel-dev-login
cd laravel-dev-login
composer install
```

No database server and no `.env` are needed. The suite runs against an
in-memory SQLite database that Testbench sets up for you.

To reproduce a CI matrix failure locally, point the suite at a real engine with
`DB_DRIVER`:

```bash
DB_DRIVER=mysql composer test
DB_DRIVER=pgsql composer test
```

The package ships no schema of its own, but it resolves users through the
guard's user provider, and that is a database read on whatever engine the
application runs.

## The three checks

All three must be green before a pull request can be merged. CI runs them too,
but running them locally is faster than waiting.

```bash
composer test      # Pest, random order
composer analyse   # PHPStan, level max
composer format    # Pint, applies fixes
```

Two things worth knowing:

- **PHPStan runs at level max with no baseline.**
  If an error is genuinely a false positive, explain it in the pull request so
  we can find the right fix. Do not add `@phpstan-ignore`, a baseline entry, or
  a cast only to silence it.
- **Pint is the only style authority.** Run `composer format` before pushing.
  Avoid manual formatting that conflicts with its output.

The suite runs in random order and fails on warnings, risky tests, and an empty
suite. A test that passes only in a particular order is a bug in the test.

## The workbench

`workbench/` is a bootable demo application that installs the package the way a
real application would. It is where a change is driven by hand before it is
documented, and it matters more here than in a headless package: this one ships
a page, and only a browser tells you whether that page reads as obviously
development-only at a glance.

```bash
composer build                      # set the demo up
php vendor/bin/testbench serve      # then open http://localhost:8000/dev-login
composer clear                      # tear it down again
```

See [workbench/README.md](workbench/README.md) for the demo loop. It is
excluded from the Composer dist archive but **is** covered by CI: a job runs
`composer build` on every push, so a demo that stops booting stops the build.

## The safety gates come first

Five independent gates decide whether this package does anything at all: the
environment allowlist, the enable flag, the production refusal, routes that do
not register when any gate says no, and the allowed-hosts check. They are the
reason the package can exist.

Two rules follow from that, and a pull request that breaks either will not be
merged:

- **A change to a gate needs a test that fails before it.** Not a test that the
  gate works, a test that the specific hole is closed.
- **When a gate says no, nothing registers.** Not a 403, not a redirect, not a
  flash message: no route. A 403 tells a stranger the page is there, and a
  registered route is one refactor away from being reachable.

Adding a sixth gate is welcome. Making an existing one conditional, or trading
two for one that "covers both", is the change that needs the most convincing.

## Where tests go

The primary seam is the documented public API: the page, the profile
configuration, the user resolver contract, and the tenant resolver contract.
Exercise them against a real guard rather than mocking package internals; use
the framework fakes (`Event::fake()`) for what the framework owns.

`tests/ArchTest.php` enforces strict types, no leftover debugging calls, and
that the shipped config file holds no closure. That last one is load-bearing:
the config has to survive `config:cache`, which is why every seam in this
package is a class-string rather than a callable.

## What a good change looks like

**Touching profiles.** A profile is data in a cacheable config file. Anything
that cannot be expressed as a scalar or a class-string does not belong in one.

**Touching authentication.** The package drives Laravel's session guard and
does not reimplement any part of it. No passwords, no throttling, no
registration.

**Touching the tenant contract.** `TenantResolver` is the riskiest public
surface in the package, because reshaping a published interface forces a major
version. Changes to it want a paper check against at least two real tenancy
packages in the pull request.

**Fixing behavior.** Please include a test that fails before the change.

## Language

The package has one noun: a **profile**, a named, preconfigured way into the
app, made of a user reference, a guard, an optional tenant, and an optional
redirect. The **page** shows one button per profile. A **resolver** turns a
profile into a user; a **tenant resolver** makes a profile's tenant current.
Documentation and docblocks state behavior, constraints, and tradeoffs
directly.

## Package scope

The following are deliberate boundaries rather than gaps. Explain any proposed
change to them in the pull request:

- **No impersonation.** Switching users from inside the application is a
  different feature with a different threat model.
- **No user creation or seeding.** The package never writes to your users
  table. A profile pointing at a missing user throws, and that exception is
  also how you find out your config and your seeder have drifted apart.
- **No API or token guards.** Session guards only.
- **No production or support login.** The gates make it impossible rather than
  inconvenient, and that is not negotiable.
- **No theming config.** Customizing the page is publishing the views.
- **No compatibility aliases or deprecation shims.** A removed name is removed
  in the major release that removes it, and documented in the changelog.

## Commits and branches

Branch off `main` as `feat/…`, `fix/…`, `docs/…`, `refactor/…`, or `chore/…`,
and write [Conventional Commits](https://www.conventionalcommits.org/)
(`feat:`, `fix:`, `docs:`, `refactor:`, `chore:`). Mark breaking changes with
`!`, which is what decides whether a release is a major one.

## Security

Do not open a public issue for a vulnerability. See [SECURITY.md](SECURITY.md).
