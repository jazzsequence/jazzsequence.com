#!/bin/bash

set -eou pipefail

# Variables
PR_TITLE_PREFIX="Release"
MANUAL_VERSION=""
DRY_RUN=false

# Function to fetch pull request details
get_pr_details() {
  local pr_number="$1"
  gh pr view "${pr_number}" -R "jazzsequence/jazzsequence.com" --json mergedAt,body,labels
}

# Function to create a tag and release
create_release() {
  local version="$1"
  local release_title="$2"
  local release_notes="$3"
  local release_date="$4"

  if [[ "${DRY_RUN}" == "true" ]]; then
    echo "Dry run enabled. Preview of release:"
    echo "------------------------------------"
    echo "Tag: ${version}"
    echo "Title: ${release_title}"
    echo "Body:"
    echo "${release_notes}"
    echo "Release Date: ${release_date}"
    echo "------------------------------------"
  else
    # Create a new tag using git command
    git tag "${version}" -m "${release_title}"
    git push origin "${version}"

    # Create the release using GitHub CLI (gh)
    gh release create "${version}" \
      --title "${release_title}" \
      --notes "${release_notes}" \
      --date "${release_date}"
  fi
}

get_current_version() {
  # Run gh release list and store the output in a variable
  release_list=$(gh release list)

  # Extract the latest release number by finding the first numeric string after the word "Latest"
  latest_release=$(echo "$release_list" | grep -o 'Latest[[:space:]]\+[0-9]\+\.[0-9]\+\.[0-9]\+' | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1)

  # Print the latest release number
  echo "${latest_release}"
}

# Function to increment version based on label or title
increment_version() {
  local current_version=$(get_current_version)
  local pr_title="$1"
  local pr_labels="$2"

  if [[ "${pr_labels}" == *"major"* ]]; then
    # Increment the major version
    current_version="${current_version%%.*}.$((${current_version##*.} + 1)).0"
  elif [[ "${pr_labels}" == *"minor"* ]]; then
    # Increment the minor version
    current_version="${current_version%.*}.$((${current_version##*.*} + 1)).0"
  else
    # Increment the patch version
    current_version="${current_version%.*}.$((${current_version##*.*} + 1))"
  fi

  echo "${current_version}"
}

# Function to get the release title from the PR body if available
get_release_title() {
  local pr_body="$1"
  local heading=$(echo "${pr_body}" | grep -m 1 '^# ')

  if [[ -n "${heading}" ]]; then
    echo "${heading#*# }"
  else
    echo "${PR_TITLE_PREFIX} ${version}"
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
merged_prs=$(gh pr list -R "jazzsequence/jazzsequence.com" -s merged -B main --json number,title,labels)

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
else
  # Loop through the merged PRs and extract information from each
  echo "$merged_prs" | jq -r '.[] | select(.title | test("Release [0-9]+\\.[0-9]+\\.[0-9]+")) | .number,.title,.labels' | while read -r pr_number; do
    read -r pr_title
    read -r pr_labels

    # Fetch the mergedAt field
    merged_at=$(get_pr_details "${pr_number}" | jq -r '.mergedAt')
    pr_body=$(get_pr_details "${pr_number}" | jq -r '.body')

    # Extract version number from PR title
    version=$(echo "$pr_title" | grep -oE "[0-9]+\.[0-9]+\.[0-9]+")

    if [[ -z "${version}" ]]; then
      # If the version number is not found in the PR title, try to get it from the latest release
      release_list=$(gh release list)
      latest_release=$(echo "$release_list" | grep -o 'Latest[[:space:]]\+[0-9]\+\.[0-9]\+\.[0-9]\+' | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1)
      if [[ -z "${latest_release}" ]]; then
        echo "No version number found in the PR title, and no previous releases found."
        echo "Please provide a version number using --version option."
        exit 1
      fi

      version=$(increment_version "${pr_title}" "${pr_labels}")
      release_title=$(get_release_title "${pr_body}")
      release_notes="Release from previous release (No version found in PR title)"
      release_date=$(date "+%B %d, %Y")
      create_release "${version}" "${release_title}" "${release_notes}" "${release_date}"
    else
      # Determine the new version based on the labels and title
      version=$(increment_version "${pr_title}" "${pr_labels}")
      release_title=$(get_release_title "${pr_body}")

      # Format the release notes with the PR body
      release_notes="${pr_body}"
      release_date=$(date "+%B %d, %Y")
      create_release "${version}" "${release_title}" "${release_notes}" "${release_date}"
    fi
  done
fi
