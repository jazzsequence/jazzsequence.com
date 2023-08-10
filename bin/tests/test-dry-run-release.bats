#!/usr/bin/env bats
set +x
VERSION=$(jq -r .version $WORKSPACE_PATH/version.json) 
if [[ ! -x "$WORKSPACE_PATH"/bin/create_release.sh ]]; then
  echo "create_release.sh script is not executable. Adding +x."
  chmod +x "$WORKSPACE_PATH"/bin/create_release.sh
fi

# Test that release dry-run runs and exits with 0
@test "test dry-run" {
  run "$WORKSPACE_PATH"/bin/create_release.sh --version "$VERSION" --dry-run
  echo $output

  # Test that the output contains a string that has a version number in "Creating release X.X.X..."
  echo "$output" | grep -qE "Creating release [0-9]+\.[0-9]+\.[0-9]+..."
  [ "$status" -eq 0 ]

  # Test that the output contains a string that starts with "Title:"
  echo "$output" | grep -qE "Title: "
  [ "$status" -eq 0 ]

  # Test that the output contains a string on a new line following "Body:"
  echo "$output" | grep -qE "Body:$"
  [ "$status" -eq 0 ]

  # Test that the output contains a string that starts with "Release Date"
  echo "$output" | grep -qE "Release Date: "
  [ "$status" -eq 0 ]

  # Test that the last line of the output contains "Dry run enabled. Release not created."
  echo "$output" | grep -qE "Dry run enabled. Release not created."
  [ "$status" -eq 0 ]
}
