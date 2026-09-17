# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Changed

- Code modernisation pass (Rector, PHP 8.3 / code quality / dead code / type declaration sets): `::class` on objects, first-class callables, explicit return types on arrow functions and closures, `??=` assignments, `=== []` instead of `empty()` on arrays, unused parameters and catch variables removed. No behaviour change intended.
- CI workflows: `actions/checkout` bumped to v5.
- CHANGELOG: entries for v1.0.8 to v1.2.7 reconstructed from the release history.

### Removed

- Untrack local files that do not belong to the package; `coverage.xml` added to `.gitignore`.

## [v1.3.1] - 2026-09-17

### Added

- Laravel 13 support (`illuminate/contracts` and `illuminate/support` now accept `^12.0|^13.0`).
- Forgejo Actions workflows (`.forgejo/workflows/`) for Codeberg mirroring (tests, code style, static analysis).

### Changed

- Minimum PHP version raised to 8.3.
- Development dependencies updated: Orchestra Testbench `^10.0|^11.0`, Pest `^3.8|^4.0`, Pest Laravel plugin `^3.2|^4.0`, PHPStan `^2.0`.
- CI test matrix now covers PHP 8.3 / 8.4 and Laravel 12 / 13 with both `prefer-lowest` and `prefer-stable` dependency resolution.
- PHPStan configuration: the optional stubs exclusion is now marked with `(?)` as required by PHPStan 2.

### Removed

- Laravel 11 support (end of life).
- PHP 8.2 support.

### Fixed

- Restore the correct `composer.json` manifest (package name, autoload and dependencies had been replaced by another package's manifest in an intermediate, withdrawn tag).

## [v1.2.7] - 2026-04-27

### Fixed

- Key-value field: the "Add pair" button had no visible effect because empty rows were dropped on re-render; row state is now kept separately until a key is filled in.

### Changed

- README: updated Laravel version requirements.

## [v1.2.6] - 2026-03-10

### Fixed

- Flows index: send an `Accept: application/json` header and check the HTTP status when fetching flows.
- `LucideIcon` component: guard against an undefined icon name.
- Code style fixes for Pint 1.28.

## [v1.2.5] - 2026-03-10

### Added

- Frontend UI for flow tags: `TagManager` component (autocomplete, create on the fly, attach/detach), tag pills on flow cards and a tag filter on the flows index page.

## [v1.2.2] - 2026-03-10

### Added

- Flow tagging system: `Tag` model with ULIDs and auto-generated slugs, `autobuilder_tags` / `autobuilder_flow_tag` tables, `tags()` relation and `withTag()` scope on `Flow`, `TagController` with `api/tags` and `api/flows/{flow}/tags/{tag}` routes, `?tag=` filter on the flows index (#29).

## [v1.2.1] - 2026-03-10

### Added

- `AUTOBUILDER_LOG` environment variable (`autobuilder.logging.activated` config key) to enable or disable package logging, via a new `AutoBuilderLogger` helper (#36).

## [v1.2.0] - 2026-03-03

### Added

- `WebhookPathNormalizer` for consistent webhook path matching (case, slashes, slugify), applied on storage and lookup.
- HTTP method validation on webhooks (returns 405 when the method does not match).
- `webhook.*` context namespace (ip, content type, user agent) while keeping the flat keys for backward compatibility.
- `WebhookAnswer` action for custom HTTP responses, with matching `FlowContext` methods; flows using it are automatically executed synchronously.
- Webhook prefix/suffix display with copy-to-clipboard in the properties panel; the prefix is now configurable.
- Debug logging for webhook 404s.

### Fixed

- Webhook 404 on valid paths (#34).
- `StopFlow` now actually stops propagation in `FlowRunner`.

## [v1.1.0] - 2026-03-03

### Added

- Condition sub-category filter pills in the node palette (#31).
- Auto-layout button (dagre) to arrange nodes automatically (#22).
- Search/filter input on the flows index page (#30).

## [v1.0.10] - 2026-03-03

### Fixed

- Rebuilt compiled assets so that the v1.0.8 and v1.0.9 frontend changes are actually shipped.

## [v1.0.9] - 2026-03-02

### Added

- Nodes can be renamed from the properties panel (Enter to save, Escape to cancel) (#21).

## [v1.0.8] - 2026-03-02

### Fixed

- `KeyValue` field renderer in the properties panel and `keyvalue` type mapping (#20).
- Defensive string-to-array parsing in the `SwitchCase` condition.
- `sourceHandle` / `targetHandle` accepted by `UpdateFlowRequest` validation; warning logged for edges missing a source handle (#23).
- Use `CarbonInterface` instead of `Carbon` in type declarations for PHPStan compatibility with newer Carbon versions.

## [1.0.7](https://github.com/Grazulex/laravel-autobuilder/releases/tag/v1.0.7) (2026-01-08)

### Bug Fixes

- Select field now sends value instead of label to backend (#18) ([19e81be](https://github.com/Grazulex/laravel-autobuilder/commit/19e81be98b34e90b2844f50158f21c3baff7498b))

### Documentation

- add matt7ds to contributors ([107d681](https://github.com/Grazulex/laravel-autobuilder/commit/107d6819a92d03bbb46915d43e17c1ab52fce020))
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
