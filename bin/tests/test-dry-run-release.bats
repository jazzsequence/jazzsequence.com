# Test that composer dry-run-release runs and exits with 0
@test "test dry-run-release" {
  run composer dry-run-release

  # Test that the output contains a string that has a version number in "Creating release X.X.X..."
  [[ "${output[*]}" =~ "Creating release [0-9]+\.[0-9]+\.[0-9]+\\.{3}" ]]

  # Test that the output contains a string that starts with "Title:" and contains text that follows on the same line.
  [[ "${output[*]}" =~ "Title: [[:alnum:][:space:]]+" ]]

  # Test that the output contains a string on a new line following "Body:" and is not empty.
  [[ "${output[*]}" =~ "Body:[[:space:]]+[[:alnum:][:space:]]+" ]]

  # Test that the output contains a string that starts with "Release Date" and contains text that follows on the same line.
  [[ "${output[*]}" =~ "Release Date:[[:space:]]+[[:alnum:][:space:]]+" ]]

  # Test that the last line of the output contains "Dry run enabled. Release not created."
  [[ "${output[-1]}" =~ "Dry run enabled. Release not created." ]]
  [ "$status" -eq 0 ]
}
