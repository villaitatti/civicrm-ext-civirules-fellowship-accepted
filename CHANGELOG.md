# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [1.0.1] - 2026-02-06
### Added
- GitHub workflow to auto-create a release when `info.xml` version changes on `main`.

### Fixed
- Extension install/enable lifecycle wiring for modern CiviCRM upgrader flow.
- Removed legacy `Upgrader::instance()` calls that caused fatal install errors.
- Corrected upgrader implementation to avoid invalid parent lifecycle method calls.

## [1.0.0] - 2026-02-06
### Added
- Initial CiviCRM extension scaffold for `org.civicrm.civirules.fellowshipaccepted`.
- Custom CiviRules condition: `Fellowship accepted changed to Yes`.
- Strict row-id fallback logic for multi-record custom data.
- GitHub CI workflow for metadata validation, PHP lint, and package build.
- GitHub release workflow that builds and attaches extension zip assets.
