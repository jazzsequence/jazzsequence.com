# Release Workflow

## Workflow

* `dev` is the default branch.
* New features branch off `dev` or are pushed straight to `dev`.
* `main` is the production branch.
* When a release is ready, `dev` **squash merges** into `main`.
* `main` auto-deploys to production.
* GitHub Actions automatically syncs `dev` to match `main` after release.
* 🎉

## Preparing a Release
When `dev` is ready to go to production, create a new pull request with the title "Release X.X.X" (where X.X.X is the version number). This pull request _must_ contain a heading in `#` syntax, followed by a brief summary of what's been updated. This text will be used in the release notes with the heading as the release title. 

Before merging a release PR, the `version.json` file must be bumped appropriately. This file is used to determine the version number of the release and is used in the release notes. The version number is a semantic version number, and should be bumped according to the following rules:
* Updates and patches should always be x.x.Y patch updates. 
* Major WordPress updates (e.g. from 6.3 to 6.4) or major feature additions (like new plugins or themes) should be x.Y.x minor updates. 
* Major site updates should be Y.x.x major updates.

## PR Grooming for Release Categorization
The `.github/releases.yml` file handles custom categorization of PRs in the auto-generated release notes based on labels applied to the PR. These _must_ also be used to determine the version number of the release. The following labels are used:

* *Major Updates*
	- `major`
	- `breaking-change`
* *New Features*
	- `feature`
	- `enhancement`
* *Other Changes*
	- All other tags
	
PRs _must_ have the appropriate label applied if they include new site features or major updates. If *New Features* or *Major Updates* are included in the release, the version number _must_ be bumped accordingly.

## Merge Strategy

* **Always use "Squash and Merge"** when merging release PRs
* This creates a clean, linear history with one commit per release
* The squashed commit preserves all individual commits in the PR history

## Branch Synchronization

After each release is merged to `main`:
1. GitHub Actions automatically syncs the `dev` branch to match `main`
2. This prevents branch divergence and keeps histories aligned
3. The `dev` branch is force-pushed to match `main` exactly
4. Individual commit history is preserved in the merged PR

**Note:** Never manually merge `main` back into `dev` - let the automation handle this to maintain clean history.
