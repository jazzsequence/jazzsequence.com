#!/bin/bash
VERSION=$(jq -r .version ./version.json) 
# If WORKSPACE_PATH is not set, set it to ./ (the current directory)
WORKSPACE_PATH=${WORKSPACE_PATH:-./}
echo "Dry running release $VERSION"

# Check if the create_release script exists before running it. If it does not, bail.
if [[ ! -f "$WORKSPACE_PATH"/bin/create_release.sh ]]; then
  echo "create_release.sh script not found. Exiting."
  exit 1
fi

# Check permissions of the create_release script. If it's not set to execute, add +x.
if [[ ! -x "$WORKSPACE_PATH"/bin/create_release.sh ]]; then
  echo "create_release.sh script is not executable. Adding +x."
  chmod +x "$WORKSPACE_PATH"/bin/create_release.sh
fi

# Run the script at the WORKSPACE_PATH.
"$WORKSPACE_PATH"/bin/create_release.sh --version "$VERSION" --dry-run