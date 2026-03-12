# Release Command

Create a new release tag and push it: $ARGUMENTS

## Steps

1. **Determine version**
   - Run `git tag --sort=-v:refname | head -1` to find the latest tag
   - If arguments contain a version (e.g., `0.3.0` or `v0.3.0`), use that
   - Otherwise, bump the minor version by 1 (e.g., `v0.1.0` → `v0.2.0`)
   - Ensure the version starts with `v` prefix
   - The bare version (without `v`) is used for file updates

2. **Verify state**
   - Run `git status` to ensure working tree is clean (no uncommitted changes)
   - If dirty, abort and tell user to commit first (suggest `/co`)
   - Confirm we're on `main` branch

3. **Bump version strings in source files**
   Update the version number (without `v` prefix) in these files:
   - `plugin/rockaden-chess.php` — both the `Version:` header comment and the `define( 'RC_VERSION', '...' )` line
   - `plugin/phpstan-bootstrap.php` — the `define( 'RC_VERSION', '...' )` line
   - `theme/style.css` — the `Version:` header line

   Stage and commit these changes with message: `Bump version to <version>`

4. **Show what will be released**
   - Run `git log --oneline <latest-tag>..HEAD` to show commits since last release
   - Display the new version number
   - Ask user to confirm before tagging

5. **Create and push tag**
   - Run `git tag <version>`
   - Run `git push origin main` (push the version bump commit)
   - Run `git push origin <version>` (push the tag)
   - Report success with tag name

## Important

- **Abort if working tree is dirty** — all changes must be committed first
- **Never force-push tags** — if tag exists, abort and inform user
- Tag format is always `vX.Y.Z` (semver with `v` prefix)
- The `.github/workflows/release.yml` will automatically build and create a GitHub Release
- **No Claude attribution** in the version bump commit

## Example usage

```
/release          # Bump minor version (v0.1.0 → v0.2.0)
/release 0.3.0    # Release specific version v0.3.0
/release v1.0.0   # Release specific version v1.0.0
```