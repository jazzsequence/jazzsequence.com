#!/bin/bash

set -eou pipefail
# Commenting out the line below to disable debug mode for clarity
# set -x

# Variables
PR_TITLE_PREFIX="Release"
MANUAL_VERSION=""
DRY_RUN=false

# Function to fetch pull request details
function get_pr_details() {
  local pr_number="pull/$1"  # Add "pull/" before the PR number
  local pr_info=$(gh pr view "$pr_number" -R jazzsequence/jazzsequence.com --json mergedAt,body,labels 2>/dev/null)
  if [[ -z "$pr_info" ]]; then
    echo "{\"mergedAt\": null, \"body\": \"\", \"labels\": []}"  # Return empty data if PR not found
  else
    echo "$pr_info"
  fi
}

function get_current_version() {
  local release_list=$(gh release list)
  echo "$release_list" | grep -o 'Latest[[:space:]]\+[0-9]\+\.[0-9]\+\.[0-9]\+' | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1
}

function increment_version() {
  local pr_title=$1
  local pr_labels=$2

  local current_version=$(get_current_version)
  local version=""

  # Attempt to extract version from PR title
  version=$(echo "$pr_title" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')

  if [[ -z "$version" ]]; then
    # If version not found in PR title, try to extract from labels
    version=$(echo "$pr_labels" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
  fi

  if [[ -z "$version" ]]; then
    # If version still not found, fallback to current version
    version=$current_version
  fi

  echo "$version"
}

function get_release_title() {
  local pr_body=$1
  local heading=$(echo "$pr_body" | grep -m 1 '^# ')

  if [[ -n "$heading" ]]; then
    echo "${heading/#\#+([[:space:]])/}"
  else
    echo "Release $version"
  fi
}

function create_release() {
  local version=$1
  local release_title=$2
  local release_notes=$3
  local release_date=$4

  echo "Creating release $version..."
  echo "Title: $release_title"
  echo "Body:"
  echo "$release_notes"
  echo "Release Date: $release_date"

  if [[ "$DRY_RUN" == "true" ]]; then
    # Print the dry run message only once
    if [[ ! "$dry_run_printed" ]]; then
      echo "Dry run enabled. Release not created."
      dry_run_printed=true
    fi
  else
    # Uncomment the following line to create the actual release
    # gh release create "v$version" --title "$release_title" --notes "$release_notes"
    echo "Release created successfully!"
  fi
}

# Parse command-line arguments
while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    --dry-run)
      DRY_RUN=true
      shift
      ;;
    --version)
      MANUAL_VERSION="$2"
      shift
      shift
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

# Get the merged PRs from dev to main with titles following the pattern 'Release X.X.X'
# Fetch merged PRs
merged_prs=$(gh pr list -R jazzsequence/jazzsequence.com -s merged -B main --json number,title,labels)

# Filter out PRs with release version in title or labels
filtered_prs=$(echo "$merged_prs" | jq -c 'map(select(.title | test("Release [0-9]+\\.[0-9]+\\.[0-9]+")))')

# Process the filtered PRs using a for loop and jq directly
dry_run_printed=false
while IFS= read -r pr_info; do
  # Decode base64 encoded JSON for each PR
  decoded_pr_info=$(echo "$pr_info" | base64 --decode)

  # Extract PR details using jq
  pr_number=$(echo "$decoded_pr_info" | jq -r '.number')
  pr_title=$(echo "$decoded_pr_info" | jq -r '.title')
  pr_labels=$(echo "$decoded_pr_info" | jq -r '.labels')

  # Get PR details
  merged_at=$(get_pr_details "$pr_number" | jq -r '.mergedAt')
  pr_body=$(get_pr_details "$pr_number" | jq -r '.body')

  # Increment version
  version=$(increment_version "$pr_title" "$pr_labels")

  # Get release title
  release_title=$(get_release_title "$pr_body")

  # Format release date (you can customize this as per your preference)
  release_date=$(date '+%B %d, %Y')

  # Create the release
  create_release "$version" "$release_title" "$pr_body" "$release_date"

done <<< "$filtered_prs"

if [[ -z "${merged_prs}" ]]; then
  if [[ -z "${MANUAL_VERSION}" ]]; then
    # Get the latest release version using the provided script
    latest_release=$(get_current_version)

    if [[ -z "${latest_release}" ]]; then
      echo "No merged PR found with a title following the pattern 'Release X.X.X', and no manual version provided."
      echo "No previous releases found."

      # Exit successfully as there's nothing to release
      exit 0
    fi

    version=$(increment_version "" "")
    release_title="${PR_TITLE_PREFIX} ${version}"
    release_notes="Release from previous release"
    release_date=$(date "+%B %d, %Y")
    create_release "${version}" "${release_title}" "${release_notes}" "${release_date}"
  else
    echo "Warning: No merged PR found with a title following the pattern 'Release X.X.X'. Using manual version provided."
    version="${MANUAL_VERSION}"
    pr_title="Release ${MANUAL_VERSION}"
    release_title="${PR_TITLE_PREFIX} ${version}"
    release_notes="Manual release"
    release_date=$(date "+%B %d, %Y")
    create_release "${version}" "${release_title}" "${release_notes}" "${release_date}"
  fi
fi
