# CiviRules Fellowship Accepted Condition

Adds a custom CiviRules CONDITION for multi-record custom data, for this specific field:

- Custom Group ID: `4` (`Custom_Fellowships`)
- Custom Field ID: `115`
- API Field Name: `Fellowship_accepted_In_use_from_2026_`

Condition label in UI:

- `Fellowship accepted changed to Yes`

It passes only when the changed field is `115` and NEW value is truthy (`true`, `1`, `"1"`, `"true"`).

## Files

- Condition class: `CRM/Civirulesfellowshipaccepted/CivirulesConditions/Contact/FellowshipAcceptedChangedToYes.php`
- CiviRules condition registration: `civirules_conditions.json`
- Upgrader install/enable/upgrade registration: `CRM/Civirulesfellowshipaccepted/Upgrader.php`

## Build / Install

1. Put this extension directory in your CiviCRM extensions path.
2. Enable the extension:

```bash
cv ext:enable org.civicrm.civirules.fellowshipaccepted
```

3. Clear caches:

```bash
cv flush
```

If `cv` is not available in your PATH, run with your local `cv` binary path.

## Local Development Commands

```bash
make lint
make package
make clean
```

Package output:

- `dist/org.civicrm.civirules.fellowshipaccepted-<version>.zip`

## Use In Rule

1. Open your existing CiviRules rule with trigger:
   - `Custom Data on Contact (of any Type) Changed`
2. Keep condition:
   - `Contact Custom Field Changed is one of` including field `115`
3. Add this new condition:
   - `Fellowship accepted changed to Yes`

## Trigger Payload / Fallback Notes

The condition reads changed-field/new-value data from trigger context payload (entity/original data containers).

- If changed field is not `115` => condition returns `FALSE`
- If NEW value is present and truthy => `TRUE`
- If NEW value is missing but row id is available => fallback APIv4 lookup on `Custom_Fellowships` row id
- If fallback cannot resolve row/value => `FALSE`

Fallback API call used:

```php
civicrm_api4('Custom_Fellowships', 'get', [
  'select' => ['Fellowship_accepted_In_use_from_2026_'],
  'where' => [['id', '=', $rowId]],
  'limit' => 1,
  'checkPermissions' => TRUE,
]);
```

## Troubleshooting

- Condition not visible in CiviRules UI:
  - Confirm extension is enabled: `cv ext:list | grep fellowshipaccepted`
  - Re-enable extension, then `cv flush`
- Condition visible but never matches:
  - Confirm field id is exactly `115`
  - Confirm trigger is `Custom Data on Contact (of any Type) Changed`
  - Confirm rule still includes `Contact Custom Field Changed is one of`
- Missing row id/new value in trigger payload:
  - This extension already attempts payload parsing across multiple trigger-data containers and then APIv4 fallback.
  - If payload omits both NEW value and row id, matching is not possible and condition safely returns `FALSE`.

## GitHub Automation

- CI workflow: `.github/workflows/ci.yml`
- Auto-release workflow: `.github/workflows/auto-release-on-version-bump.yml`
- Release workflow: `.github/workflows/release.yml`

Release behavior (recommended):

1. Update version in `info.xml` and `CHANGELOG.md`.
2. Commit and push to `main`.
3. The auto-release workflow compares `info.xml` version to the latest `v*` tag.
4. If the version is different, it creates a GitHub Release with tag `v<version>` and uploads the package zip.
5. If the version is unchanged, it skips release.

Manual release options (fallback):

1. Create and push a matching git tag, for example:

```bash
git tag v1.0.1
git push origin v1.0.1
```

2. GitHub Actions builds the zip and publishes it as a GitHub Release asset.

Manual release from Actions UI:

- Run the `Release` workflow with an existing tag (for example `v1.0.1`).
