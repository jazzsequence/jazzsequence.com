#!/usr/bin/env bats

set +x

# Test that composer dry-run-release runs and exits with 0
@test "test dry-run-release" {
  run composer dry-run-release

  # Test that the output contains a string that has a version number in "Creating release X.X.X..."
  echo "${output[@]}" | grep -qE "Creating release [0-9]+\.[0-9]+\.[0-9]+..."

  # Test that the output contains a string that starts with "Title:"
  echo "${output[@]}" | grep -qE "Title: "

  # Test that the output contains a string on a new line following "Body:"
  echo "${output[@]}" | grep -qE "Body:$"

  # Test that the output contains a string that starts with "Release Date"
  echo "${output[@]}" | grep -qE "Release Date: "

  # Test that the last line of the output contains "Dry run enabled. Release not created."
  [[ "${output[-1]}" == "Dry run enabled. Release not created." ]]
  [ "$status" -eq 0 ]
}
