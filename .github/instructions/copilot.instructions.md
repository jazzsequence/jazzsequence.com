## Copilot Onboarding

### Repository Overview
- jazzsequence.com is a WordPress multisite built on [Altis DXP] with numerous custom plugins/themes vendored via Composer; code lives in a classic WP docroot (`wp-*.php`, `wp-content`, `vendor`, `packages`, etc.).
- Infra: deployed to DigitalOcean via rsync, served by OpenLiteSpeed with LiteSpeed Cache; CI/CD is GitHub Actions only.
- Repo footprint is large (`du -sh .` ≈ 37 GB) because WordPress core, vendor libraries, and compiled assets are committed. Avoid whole-repo searches when possible.
- README badges track `test-scripts` and `dry-run-deploy`; RELEASES.md defines the `dev` → `main` squash-release workflow and semantic versioning policy (also duplicated in `version.json`).
- High-value local code sits under `wp-content/mu-plugins/*.php` (custom loaders and site logic) plus `wp-content/plugins/js-*` and `wp-content/themes/artbox` or `packages/jz-222` for bespoke themes. Most other plugins/themes are Composer managed and should not be edited in-place.

### Environment & Toolchain Expectations
- PHP: Composer pins platform PHP 8.2, while local CLI currently runs PHP 8.3.19. Use PHP ≥8.2 to match production; GitHub Actions sets up PHP 8.4 for deploy workflows.
- Composer 2.8.3 is used. Every `composer install`/`update` automatically backs up `wp-config.php` + `index.php` before running scripts and restores them afterward.
- You **must** keep `auth.json` (private) populated with HTTP-basic credentials for Object Cache Pro (`composer config --auth http-basic.objectcache.pro token ...`) before installing.
- Tooling relied on by scripts/CI: `shellcheck`, `jq`, `gh` CLI, `rsync`, and `bats`. `bin/dry-run-release.sh`/`bin/create_release.sh` shell out to `gh` + `jq`; release workflows expect `GH_TOKEN` in the environment.
- PHP_CodeSniffer is configured via `phpcs.xml` to enforce Pantheon + WordPress VIP minimum + PHPCompatibility rules; follow WP coding standards for PHP, use DocBlocks, and avoid touching vendored code.

### Bootstrap, Build, Lint, and Test Workflow
1. **Install dependencies**  
   - Command (validated): `composer install` (~1.5 s locally). Backs up/restores `wp-config.php` + `index.php` automatically. Matches all CI workflows.
2. **Shell script linting**  
   - Command (validated): `composer shellcheck`. Requires the `shellcheck` binary on PATH. Passes cleanly today and is what the `test-scripts` workflow runs before executing Bats.
3. **PHPCS (PHP lint)**  
   - Command (validated): `composer phpcs` and `vendor/bin/phpcs -s .`. Passes cleanly today; matches the `lint` workflow job.
4. **Release script dry-run**  
   - Command (attempted): `bash bin/dry-run-release.sh`. Needs `jq` and will chmod `bin/create_release.sh` automatically. Requires `GH_TOKEN` in the environment to query GitHub; set this locally to a personal access token with `repo` scope to run successfully. Matches the `test-scripts` workflow job.
5. **Bats tests (`bin/tests/test-dry-run-release.bats`)**  
   - Mirrors CI: clone https://github.com/bats-core/bats-core into `bin/bats`, ensure `bin/create_release.sh` is executable, then run `bin/bats/bin/bats bin/tests/*.bats` with `WORKSPACE_PATH` pointing to the repo, `VERSION=$(jq -r .version version.json)`, and `GH_TOKEN` set. These tests invoke the release script, so the same GH authentication requirement applies.
6. **Dashboard Changelog vendor install**  
   - After the main Composer install, workflows run `cd wp-content/mu-plugins/dashboard-changelog && composer install --no-dev --no-progress` when `vendor/erusev/parsedown/Parsedown.php` exists. Do the same locally if you touch that MU plugin; it relies on its own Composer autoloader.

Always run `composer install` (and the Dashboard Changelog step when relevant) before any lint/test command so autoloaders exist. When cleaning up, respect the automatically created `.bak` files; Composer removes them, so do not delete manually mid-install.

### CI/CD Reference
- `.github/workflows/test-scripts.yml` (push): runs `composer install`, `composer shellcheck`, `bash bin/dry-run-release.sh`, and installs Bats to execute `bin/tests/*.bats`.
- `.github/workflows/lint.yml` (push): `composer install` + `composer phpcs` on ubuntu-latest.
- `.github/workflows/dry-run-deploy.yml` (push to `dev`): installs PHP 8.4, configures auth.json, performs production-style `composer install --no-dev --optimize-autoloader --ignore-platform-reqs`, re-installs Dashboard Changelog, and does an rsync dry run to DigitalOcean.
- `.github/workflows/deploy.yml` (push to `main` or manual dispatch): same as dry-run but performs the real rsync deploy and skips `.github`, `bin`, `content`, and `node_modules`.
- `.github/workflows/tag.yml` (push to `main`): grabs `version.json`, runs `bin/create_release.sh --version`, then force-syncs `dev` to `main`; requires `GH_TOKEN`/`TOKEN`.
- `.github/workflows/composer-diff.yml` (PRs touching `composer.lock`): posts dependency diffs via sticky PR comments.

Releases: bump `version.json`, create a PR named `Release X.Y.Z` from `dev` to `main` with a Markdown heading and summary (used in release notes), squash-merge into `main`, and let tag + deploy workflows run. Never merge `main` back to `dev`; the tag workflow resets `dev` automatically.

### Layout & Key Files
- **Repo root (selected):**
  - `_misc/` (ignored scratch)
  - `audio/` (ignored media)
  - `bin/` (release/test scripts)
  - `composer.json` / `composer.lock`
  - `content/` (alt content tree, should remain empty and uncommitted)
  - `images/`
  - `index.php`
  - `packages/` (path repo stubs, e.g., `packages/jz-222`)
  - `phpcs.xml`
  - `README.md`
  - `RELEASES.md`
  - `server-config.php`
  - `vendor/`
  - `version.json`
  - `WARP.md`
  - `wp-config.php`
  - `wp-content/`
  - `wp-content/uploads` (ignored)
  - `auth.json` (exists but must never be committed with secrets; unused—Object Cache Pro is not installed)
- **`bin/`**:  
  - `create_release.sh`: drives tagging by querying merged PRs via `gh` + `jq`, generating notes, and (unless `--dry-run`) creating/updating GitHub releases. Requires `GH_TOKEN`.  
  - `dry-run-release.sh`: convenience wrapper used by CI to sanity-check release notes.  
  - `tests/test-dry-run-release.bats`: Bats assertions ensuring the dry-run script prints expected strings.
- **`wp-content/mu-plugins/`** (Composer ignores everything except curated files):  
  - `loader.php`: loads Composer autoloader, forces multisite cookie domains, enumerates MU plugins (CMB2 + Dashboard Changelog), and customizes the login logo.  
  - `disallow-updates.php`: removes selected plugins (`two-factor` now, extensible via TODO) from WP’s update transient.  
  - `hide-update-nag.php`, `redirects.php`, `not-so-autoload.php`, etc., add site-specific behavior.  
  - `dashboard-changelog/` contains its own Composer project; remember to install dependencies when editing.
- **`wp-content/plugins/`**: mostly Composer-managed. Custom single-file plugins live at `wp-content/plugins/js-*.php` (e.g., `js-teh-s3quence.php`). Treat the rest as vendor code—adjust via Composer when possible.
- **`wp-content/themes/`**: `artbox/` is the bespoke theme checked in here; `jz-222/` also exists inside `packages/` for local dev. Twenty* themes and other upstream themes are present for fallback/testing.
- **Config highlights**:  
  - `phpcs.xml` excludes most vendor directories and enforces Pantheon + PHPCompatibility rules.  
  - `.gitignore` leaves `wp-content/uploads`, vendor-provided plugins/themes, `_misc/`, `audio/`, and IDE settings untracked.  
  - `server-config.php` loads `wp-config-local.php` when present and otherwise defines placeholder DB creds plus DigitalOcean CDN settings—replace locally but never commit secrets.  
  - `version.json` stores the release version and reminder text for semantic bumps.

Key documentation: `README.md` gives technology bullets (WordPress, Altis DXP, Composer, Shellcheck, Bats, GitHub Actions, OpenLiteSpeed, DigitalOcean) and a screenshot; `RELEASES.md` is the canonical release checklist; `WARP.md` contains an architectural deep dive that mirrors these notes and is safe to consult for plugin descriptions.

### Working Notes & Expectations
- Default branch for day-to-day work is `dev`. Target `dev` unless specifically patching production in `main`.
- Before editing PHP, read related MU plugin/theme files to understand hook usage—`loader.php` wires most site behavior via `add_action`/`add_filter`.
- Tests rely on GitHub CLI access; set `GH_TOKEN` in CI or local env before running release scripts or Bats.
- When deploying or reproducing CI, mimic workflow steps exactly: install PHP 8.4 (if matching deploy job), run Composer with `--no-dev --optimize-autoloader --ignore-platform-reqs`, and re-install Dashboard Changelog’s dependencies.
- The repo is huge; focus searches within the relevant directory (e.g., `rg pattern wp-content/mu-plugins` or `rg --files wp-content/themes/artbox`). Do **not** walk the entire tree unless absolutely necessary.
- Trust this document for setup, commands, and layout. Only run additional searches or exploratory commands if you discover missing/incorrect information here, and update the instructions if you find discrepancies.
