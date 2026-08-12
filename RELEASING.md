# Release process

Releases are generated from the version declared in `seo-for-generatepress.php` after a pull request is merged into `main`.

## Before merging

1. Update the plugin header, `SEOGP_VERSION`, readme stable tag, README status, and changelog to the same semantic version.
2. Run `composer check`.
3. Confirm the pull request's PHP matrix, security and compatibility standards, and production-package Plugin Check jobs pass.
4. Review the installable file set controlled by `.distignore`.

## Automated publishing

After the merge, the release workflow:

1. Re-runs the automated checks.
2. Reads the plugin version.
3. Stops without changing an existing release of that version.
4. Builds `seo-for-generatepress.zip` with the required top-level plugin folder.
5. Creates the matching `vX.Y.Z` tag and GitHub release.
6. Attaches the ZIP expected by the in-plugin updater.

Never replace a published release asset with different code under the same version. Fixes require a new version.
