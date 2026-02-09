# CiviRules Fellowship Accepted Condition

Adds a custom CiviRules CONDITION for multi-record custom data, for this specific field:

- Custom Group ID: `4` (`Custom_Fellowships`)
- Custom Field ID: `115`
- API Field Name: `Fellowship_accepted_In_use_from_2026_`

Condition label in UI:

- `Fellowship accepted changed to Yes`

It passes only when the changed field is `115` and NEW value is truthy (`true`, `1`, `"1"`, `"true"`).

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
