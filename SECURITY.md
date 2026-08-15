# Security Policy

## Reporting a vulnerability

Please report privately, not in a public issue. Use GitHub's
[private vulnerability reporting](https://github.com/byrcsc/laravel-dev-login/security/advisories/new)
on this repository; it opens a channel visible only to the maintainers.

Include the package version, the Laravel and PHP versions, and enough detail to
reproduce. If it helps, a failing test is the clearest possible report.

You can expect an acknowledgement within a week. Because this is a
single-maintainer package, please do not expect a same-day response; if the
issue is being actively exploited, say so in the title.

**A gate bypass is the highest-severity report this package can receive.**
Anything that lets the dev login page register, render, or authenticate outside
the conditions documented below is a vulnerability, not a bug. Say so in the
title and it moves to the front.

## Supported versions

Security fixes are released for the latest package version and are not
backported, and neither are ordinary bug fixes. Only the current major receives
either; when a new major is released, the previous one stops receiving fixes.
Keep your dependency constraint current.

## What this package does and does not protect

This package authenticates users without a password, on purpose. The boundary
around that is the whole of its security posture.

It **does**:

- refuse to register its routes unless every gate agrees: the environment is on
  the allowlist, `DEV_LOGIN_ENABLED` is set, and the request arrived on an
  allowed host;
- throw at boot rather than fail quietly when it is force-enabled under
  `APP_ENV=production`, so a misconfiguration is loud;
- 404 rather than 403 when a gate says no, so a probe cannot learn that the
  page exists;
- check the request host against `allowed_hosts`, which is the one gate that
  does not trust the application's own configuration and therefore survives an
  `.env` reaching a machine it should not have;
- authenticate through Laravel's own session guards and nothing else, so your
  guard, provider, and session configuration stay in charge;
- CSRF-protect the route that authenticates, and offer no login-by-GET link.

It **does not**:

- authorize anything. Every configured profile is available to anybody who can
  reach the page. There is no second factor and no per-profile permission,
  because there is nobody to check;
- protect you from your own configuration. Adding an environment to
  `environments`, or a public hostname to `allowed_hosts`, does exactly what it
  says;
- create, modify, or delete users. It never writes to your users table, so a
  profile pointing at an address that does not exist throws rather than
  conjuring an account;
- defend against somebody who can already run code in your application, edit
  its config, or read its `.env`. A gate is not a sandbox.

## Installing it

`composer require --dev byrcsc/laravel-dev-login` is the right way to install
it, and it is treated as layer zero rather than as protection: pipelines that
ship dev dependencies to a server exist, and every gate above is written on the
assumption that yours might.

## Reporting something that is documented

The list above is the intended boundary. A report that a documented limitation
exists is not a vulnerability, but a report that the package does not actually
hold a line it claims to hold very much is. When in doubt, report it.
