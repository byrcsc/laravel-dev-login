# Changelog

All notable changes to `byrcsc/laravel-dev-login` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the package follows [semantic versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-08-18

### Added

- One-click development login through named profiles and Laravel session
  guards, including named guards, remember-me, login-event control, and
  configurable redirects.
- Five independent safety gates covering the environment, enable flag,
  production refusal, conditional route registration, and allowed hosts.
- User resolution through the profile guard's provider, with a swappable
  resolver contract and actionable errors for missing users.
- Tenant-aware profiles through the `TenantResolver` contract, with tenant
  selection before user resolution and authentication.
- A publishable Blade page and embeddable profiles component showing each
  profile's label, email, guard, and optional tenant grouping.
- Support for Laravel 12 and 13 on PHP 8.3 and 8.4.

[Unreleased]: https://github.com/byrcsc/laravel-dev-login/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/byrcsc/laravel-dev-login/releases/tag/v1.0.0
