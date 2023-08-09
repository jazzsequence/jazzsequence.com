#!/bin/bash
VERSION=$(jq -r .version ./version.json) 
# If WORKSPACE_PATH is not set, set it to ./ (the current directory)
WORKSPACE_PATH=${WORKSPACE_PATH:-./}
PATH=${PATH:-./}
echo \"Releasing "$VERSION"\"
# Run the script at the WORKSPACE_PATH.
"$WORKSPACE_PATH"/bin/create_release.sh --version "$VERSION" --dry-run