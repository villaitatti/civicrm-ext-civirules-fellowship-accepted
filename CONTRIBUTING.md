# Contributing

## Development setup
1. Put this extension in your CiviCRM extensions directory.
2. Enable with `cv ext:enable org.civicrm.civirules.fellowshipaccepted`.
3. Clear cache with `cv flush`.

## Before opening a pull request
1. Run `find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l`
2. Run `bash build/release-package.sh`
3. Update `CHANGELOG.md` when behavior changes.

## Versioning and releases
- Use semantic versioning in `info.xml` (`<version>`).
- Create a git tag like `v1.0.1` that matches `info.xml` version.
- Push the tag to trigger GitHub release automation.
