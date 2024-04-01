#!/bin/bash

TERMINUS_SITE="jazzsequence-sbox"
TERMINUS_ENV="behat"
SITE_ENV="${TERMINUS_SITE}.${TERMINUS_ENV}"

if [ -z "$TERMINUS_MACHINE_TOKEN" ]; then
	terminus auth:login --machine-token="$TERMINUS_MACHINE_TOKEN"
fi

###
# Delete the Pantheon site environment after the Behat test suite has run.
###

if [ ! "$(terminus whoami)" -ne 0 ]; then
	echo "Terminus unauthenticated; assuming unauthenticated build"
	exit 0
fi

set -ex

###
# Delete the environment used for this test run.
###
terminus multidev:delete $SITE_ENV --delete-branch --yes