#!/bin/bash

set -eou pipefail

# Variables
PR_TITLE_PREFIX="Release"
DRY_RUN=false

# Function to create a tag and release
create_release() {
  local version="$1"
  local release_notes="$2"
  local release_date="$3"

  # Create a new tag
  gh api "repos/jazzsequence/jazzsequence.com/git/refs" -f ref="refs/tags/v${version}" -f sha="main"

  # Create the release
  if [[ "${DRY_RUN}" == "true" ]]; then
    echo "Dry run enabled. Preview of release:"
    echo "------------------------------------"
    echo "Tag: v${version}"
    echo "Title: ${PR_TITLE_PREFIX} ${version}"
    echo "Body:"
    echo "${release_notes}"
    echo "Release Date: ${release_date}"
    echo "------------------------------------"
  else
    gh release create "v${version}" \
      --title "${PR_TITLE_PREFIX} ${version}" \
      --notes "${release_notes}" \
      --date "${release_date}"
  fi
}

# Function to preview release notes
preview_release() {
  local version="$1"
  local release_notes="$2"
  local release_date="$3"

  echo "Preview of release:"
  echo "------------------------------------"
  echo "Tag: v${version}"
  echo "Title: ${PR_TITLE_PREFIX} ${version}"
  echo "Body:"
  echo "${release_notes}"
  echo "Release Date: ${release_date}"
  echo "------------------------------------"
}

# Parse command-line arguments
while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    --dry-run)
      DRY_RUN=true
      shift
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

# Get the latest merged PR from dev to main
latest_pr=$(gh api "repos/jazzsequence/jazzsequence.com/pulls?base=main&state=closed" | jq '.[0]')

# Get PR title, body, and merge date
pr_title=$(jq -r '.title' <<<"${latest_pr}")
pr_body=$(jq -r '.body' <<<"${latest_pr}")
merge_date=$(jq -r '.merged_at' <<<"${latest_pr}")

# Extract version number from PR title
version=$(echo "${pr_title}" | grep -oE "${PR_TITLE_PREFIX} ([0-9]+\.[0-9]+\.[0-9]+)" | cut -d " " -f2)

# Format the release notes with the PR body and current date
release_notes="${pr_body}
Release Date: $(date -d "${merge_date}" '+%e %B, %Y')"

# Preview the release in dry-run mode
if [[ "${DRY_RUN}" == "true" ]]; then
  preview_release "${version}" "${release_notes}" "$(date -d "${merge_date}" '+%Y-%m-%d')"
else
  create_release "${version}" "${release_notes}" "$(date -d "${merge_date}" '+%Y-%m-%d')"
fi
