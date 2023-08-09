#!/bin/bash
VERSION=$(jq -r .version ./version.json) 
echo \"Releasing "$VERSION"\"
./bin/create_release.sh --version "$VERSION" --dry-run