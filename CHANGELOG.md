# Changelog

All notable changes to this project will be documented in this file.

## [1.0.6](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.6) (2026-01-08)

### Bug Fixes

- auto-extract webhook_path from trigger nodes (#14) ([a39cd0e](https://github.com/Grazulex/laravel-autobuilder/commit/a39cd0e9e39e5a073705eb084ce7c49f680ea72d))
## [1.0.5](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.5) (2026-01-08)

### Features

- **scheduler:** implement OnSchedule trigger with Laravel scheduler integration (#11) ([0acb56b](https://github.com/Grazulex/laravel-autobuilder/commit/0acb56be5b054261155edaad208df66258aa8ba7))

### Bug Fixes

- resolve pint code style issues (#12) ([f3f39a1](https://github.com/Grazulex/laravel-autobuilder/commit/f3f39a11a7a35eacfb2f06de0220eef93e179a75))
## [1.0.4](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.4) (2026-01-08)

### Features

- **scheduler:** implement OnSchedule trigger with Laravel scheduler integration (#11) ([0acb56b](https://github.com/Grazulex/laravel-autobuilder/commit/0acb56be5b054261155edaad208df66258aa8ba7))
## [1.0.3](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.3) (2026-01-08)
## [1.0.2](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.2) (2026-01-08)

### Bug Fixes

- include critical node/edge data in flow request validators ([573931b](https://github.com/Grazulex/laravel-autobuilder/commit/573931b6d28c3526ba02c045e56051a918663e30))
## [1.0.1](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.1) (2026-01-05)

### Bug Fixes

- include compiled assets in package distribution ([b6bdf99](https://github.com/Grazulex/laravel-autobuilder/commit/b6bdf99bad7553acfbfe0128e43635b1e89a416d))
## [1.0.0](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.0) (2026-01-04)

### Features

- add rate limiting, health endpoints, authorization, and testing infrastructure ([4b75439](https://github.com/Grazulex/laravel-autobuilder/commit/4b7543990691f05a5ac90d1d8d97f6d0964d8cad))
- initial release of Laravel AutoBuilder ([934df38](https://github.com/Grazulex/laravel-autobuilder/commit/934df38c69e35f4ccdb3925d8e1c895016493764))

### Bug Fixes

- **ci:** fix GitHub Actions workflows ([e1fd9ff](https://github.com/Grazulex/laravel-autobuilder/commit/e1fd9ff0ac0198d7fefc063ae3916bc80b1c948e))

### Documentation

- add call for testers and community feedback ([e78ec3b](https://github.com/Grazulex/laravel-autobuilder/commit/e78ec3bcfc644880a06a2b501096234112b7ab6d))

### Chores

- fix code style and drop Laravel 10 support ([35b442e](https://github.com/Grazulex/laravel-autobuilder/commit/35b442ec81617632fd9402bcace28da6b9f30987))
- add GitHub workflows, funding, and standardize project files ([b632d8d](https://github.com/Grazulex/laravel-autobuilder/commit/b632d8d6f2ce3fd1aa1dd310b3a3df2de68ab3f1))
## [0.1.0](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v0.1.0) (2025-12-30)

### Initial Release

- Visual automation builder for Laravel applications
- Brick system: Triggers, Conditions, and Actions
- Built-in triggers: OnModelCreated, OnSchedule, OnWebhook
- Built-in conditions: FieldEquals, UserHasRole, TimeIsBetween
- Built-in actions: SendNotification, CreateModel, CallWebhook
- Flow execution engine with FlowContext
- Variable templating with Blade-like syntax
- Field types for brick configuration
- Database models for flows and execution logs
