#!/bin/bash

# The directory of the script that is currently running.
CURRENT_DIR="$( cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Deploying to sandbox for testing..."

if [[ -z "$SANDBOX_URL" ]]; then
  echo "No SANDBOX_URL environment variable found."
  exit 1
fi

# Check if sandbox branch exists. If not, create it.
if ! git show-ref --verify --quiet refs/heads/sandbox; then
  git checkout -b sandbox
fi

git remote add sandbox "$SANDBOX_URL"
git checkout dev && git pull
git checkout sandbox && git merge dev
git fetch sandbox
git merge sandbox/master --allow-unrelated-histories
git push sandbox sandbox:master --force

bash "$CURRENT_DIR"/behat-prepare.sh
bash "$CURRENT_DIR"/behat-test.sh
bash "$CURRENT_DIR"/behat-cleanup.sh