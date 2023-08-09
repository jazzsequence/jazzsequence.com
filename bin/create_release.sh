#!/bin/bash
# shellcheck shell=bash
set -eou pipefail
# Commenting out the line below to disable debug mode for clarity
# set -x

# Usage: ./bin/create_release.sh [--dry-run] [--version <version>]

# Variables
MANUAL_VERSION=""
DRY_RUN=false

# Function to fetch pull request details
function get_pr_details() {
  local pr_number="$1"  # Add "pull/" before the PR number
  local pr_info=''
  pr_info=$(gh pr view "$pr_number" -R jazzsequence/jazzsequence.com --json mergedAt,body,labels 2>/dev/null)
  if [[ -z "$pr_info" ]]; then
    echo "{\"mergedAt\": null, \"body\": \"\", \"labels\": []}"  # Return empty data if PR not found
  else
    echo "$pr_info"
  fi
}

function get_release_title() {
  local pr_body=$1
  local heading=''
  heading=$(echo "$pr_body" | grep -m 1 '^# ')
  
  if [[ -n "$heading" ]]; then
    echo "${heading//\#+([[:space:]])/}"
  else
    echo ""
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
    echo "Dry run enabled. Release not created. Script executed successfully. Time for some tea. 🍵"
  else
    # Uncomment the following line to create the actual release
    gh release create "$version" --title "$release_title" --generate-notes "$release_notes"
    echo "Release created successfully! Time for some tea. 🍵"
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

# Filter out PRs with release version in title or labels and include full PR details
filtered_prs=$(echo "$merged_prs" | jq -c 'map(select(.title | test("Release [0-9]+\\.[0-9]+\\.[0-9]+")))')

# Check if there are any PRs with version patterns
if [[ -z "$filtered_prs" ]]; then
  echo "No PRs with version patterns found."
  # No PRs with version patterns found, check if there is a manual version provided
  if [[ -z "${MANUAL_VERSION}" ]]; then
    echo "Warning: No merged PR found with a title following the pattern 'Release X.X.X', and no manual version provided."
    echo "No previous releases found."
    exit 1
  else
    version="${MANUAL_VERSION}"
    pr_title="Release ${MANUAL_VERSION}"
    pr_body=""
  fi
else
  # Count the number of $filtered_prs.
  pr_count=$(echo "$filtered_prs" | jq -r 'length')
  echo "Found $pr_count PRs with versions in the title."
  # Process the filtered PRs using a for loop and jq directly

  for pr_info in $(echo "$filtered_prs" | jq -r '.[] | @base64'); do
    # Decode base64 encoded JSON for each PR
    decoded_pr_info=$(echo "$pr_info" | base64 --decode)

    # Extract PR details using jq
    pr_number=$(echo "$decoded_pr_info" | jq -r '.number')
    pr_title=$(echo "$decoded_pr_info" | jq -r '.title')
    echo "Processing PR #$pr_number: $pr_title"
    # Get PR details
    pr_info=$(get_pr_details "$pr_number" 2>&1)  # Redirect stderr to stdout
    if [[ ! "$pr_info" ]]; then
      echo "Error fetching PR details for PR #$pr_number:"
      echo "$pr_info"  # Print the captured error message
      continue
    else 
      echo "No problems found in PR #$pr_number."
    fi

    # Clean up the pr_info JSON string
    cleaned_pr_info=$(echo "$pr_info" | tr -d '\r\n' | sed 's/&/and/g')
    # Extract PR body from the pr_info, replace "&" with "and", and perform other replacements
    pr_body=$(echo "$cleaned_pr_info" | jq -r '.body')

    # Check if pr_body is empty.
    if [[ -z "$pr_body" ]]; then
      echo "Warning: PR Body is empty. Skipping release for PR #$pr_number."
      continue
    fi

    # Extract release version from PR title
    version=$(echo "$pr_title" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
    if [[ -z "$version" ]]; then
      echo "Warning: Version not found in PR title: $pr_title. Skipping release for PR #$pr_number."
      continue
    fi

    break  # Exit loop after processing the first PR with a version pattern
  done
fi

# Get release title and release notes from PR
release_title=$(get_release_title "$pr_body")
release_notes=$(echo "$pr_body" | sed '1d' | sed '1d')

# Format release date (you can customize this as per your preference)
release_date=$(date '+%B %d, %Y')

# Create the release
create_release "$version" "$release_title" "$release_notes" "$release_date"
