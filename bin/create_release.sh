#!/bin/bash
# shellcheck shell=bash
set -eou pipefail
# Commenting out the line below to disable debug mode for clarity
# set -x

# Usage: ./bin/create_release.sh [--dry-run] [--version <version>]

# Variables
MANUAL_VERSION=""
DRY_RUN=false

function wait_for_pr_merge() {
  local expected_version="$1"
  local max_attempts=30
  local attempt=1
  
  echo "Waiting for PR 'Release $expected_version' to be available in merged PR list..."
  
  while [[ $attempt -le $max_attempts ]]; do
    echo "Attempt $attempt/$max_attempts: Checking for merged PR..."
    
    # Check if PR with the expected version is in the merged list
    merged_prs=$(gh pr list -R jazzsequence/jazzsequence.com -s merged -B main --json number,title,labels)
    pr_found=$(echo "$merged_prs" | jq -r ".[] | select(.title == \"Release $expected_version\") | .number")
    
    if [[ -n "$pr_found" ]]; then
      echo "✅ Found merged PR #$pr_found for Release $expected_version"
      return 0
    fi
    
    echo "⏳ PR not found in merged list yet, waiting 5 seconds..."
    sleep 5
    ((attempt++))
  done
  
  echo "❌ Timeout: PR 'Release $expected_version' not found in merged PR list after $max_attempts attempts"
  return 1
}

function get_release_title() {
  local pr_body="$1"
  local pr_title="$2"
  local heading=""
  heading=$(echo "$pr_body" | grep -m 1 '^# ')
  
  if [[ -n "$heading" ]]; then
    echo "${heading#*# }"
  else
    # Fallback to PR title if no heading found
    echo "$pr_title"
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
    gh release create "$version" --title "$release_title" --generate-notes

    # Get the autogenerated release notes and store them in a variable.
    autogenerated_release_notes=$(gh release view "$version" --json body -q .body)

    # Combine the notes with our notes.
    combined_release_notes="$release_notes\n\n---\n\n$autogenerated_release_notes"

    # Update the release with the combined notes.
    echo -e "$combined_release_notes" | gh release edit "$version" --title "$release_title" --notes -
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
echo "DEBUG: All merged PRs:"
echo "$merged_prs" | jq -r '.[] | "#\(.number): \(.title)"'

# Filter out PRs with release version in title or labels and include full PR details
filtered_prs=$(echo "$merged_prs" | jq -c 'map(select(.title | test("Release [0-9]+\\.[0-9]+\\.[0-9]+")))')
echo "DEBUG: Filtered PRs matching release pattern:"
echo "$filtered_prs" | jq -r '.[] | "#\(.number): \(.title)"'

# Check if there are any PRs with version patterns
if [[ -z "$filtered_prs" ]] || [[ "$filtered_prs" == "[]" ]]; then
  echo "No PRs with version patterns found."
  # No PRs with version patterns found, check if there is a manual version provided
  if [[ -z "${MANUAL_VERSION}" ]]; then
    echo "Warning: No merged PR found with a title following the pattern 'Release X.X.X', and no manual version provided."
    echo "No previous releases found."
    exit 1
  else
    version="${MANUAL_VERSION}"
    pr_title="Release ${MANUAL_VERSION}"
    # Set fallback first
    pr_body="${pr_title}"

    # Try to get the actual PR body
    if wait_for_pr_merge "${MANUAL_VERSION}"; then
      pr_number=$(echo "$merged_prs" | jq -r ".[] | select(.title == \"$pr_title\") | .number")
      if [[ -n "$pr_number" ]]; then
        pr_info=$(gh pr view "$pr_number" -R jazzsequence/jazzsequence.com --json body)
        fetched_body=$(echo "$pr_info" | jq -r '.body | gsub("\r\n";"\n") | gsub("&";"and")')
        if [[ -n "$fetched_body" && "$fetched_body" != "null" ]]; then
          pr_body="$fetched_body"
          echo "✅ Using PR body from #$pr_number"
        else
          echo "⚠️  PR body was empty, using fallback"
        fi
      else
        echo "⚠️  Could not find PR number, using fallback"
      fi
    else
      echo "⚠️  PR not found, using fallback release notes"
    fi
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
    pr_info=$(gh pr view "$pr_number" -R jazzsequence/jazzsequence.com --json mergedAt,body,labels 2>/dev/null)
 
    if [[ ! "$pr_info" ]]; then
      echo "Error fetching PR details for PR #$pr_number:"
      echo "$pr_info"  # Print the captured error message
      continue
    else 
      echo "No problems found in PR #$pr_number."
      # echo "Debug:"
      # echo "$pr_info" | jq -r '.body | gsub("\r\n";"\n") | gsub("&";"and")'
    fi

    # Extract PR body from the pr_info, replace "&" with "and", and perform other replacements
    pr_body=$(echo "$pr_info" | jq -r '.body | gsub("\r\n";"\n") | gsub("&";"and")')
    echo "DEBUG: Raw PR body length: ${#pr_body}"
    echo "DEBUG: First 200 chars of PR body: ${pr_body:0:200}"

    # Check if pr_body is empty.
    if [[ -z "$pr_body" ]] || [[ "$pr_body" == "null" ]]; then
      echo "Warning: PR Body is empty or null. Skipping release for PR #$pr_number."
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
release_title=$(get_release_title "$pr_body" "$pr_title")
# If PR body starts with a title (# ), remove the title and blank line
# Otherwise, use the full PR body as release notes
if echo "$pr_body" | grep -q '^# '; then
  release_notes=$(echo "$pr_body" | sed '1d' | sed '1d')
else
  release_notes="$pr_body"
fi

# Format release date (you can customize this as per your preference)
release_date=$(date '+%B %d, %Y')

# Create the release
create_release "$version" "$release_title" "$release_notes" "$release_date"
