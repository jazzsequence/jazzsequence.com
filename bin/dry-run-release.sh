#!/bin/bash
VERSION=$(jq -r .version ./version.json) 
# If WORKSPACE_PATH is not set, set it to ./ (the current directory)
WORKSPACE_PATH=${WORKSPACE_PATH:-./}
echo \"Releasing "$VERSION"\"

# Check if the create_release script exists before running it. If it does not, bail.
if [[ ! -f "$WORKSPACE_PATH"/bin/create_release.sh ]]; then
  echo "create_release.sh script not found. Exiting."
  exit 1
fi
# Run the script at the WORKSPACE_PATH.
"$WORKSPACE_PATH"/bin/create_release.sh --version "$VERSION" --dry-run