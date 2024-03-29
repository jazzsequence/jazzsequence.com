#!/bin/bash

###
# Execute the Behat test suite against a prepared Pantheon site environment.
###

WORDPRESS_ADMIN_USERNAME="testuser"
WORDPRESS_ADMIN_PASSWORD="testpassword"
TERMINUS_SITE="jazzsequence-sbox"
TERMINUS_ENV="behat"

if [ -z "$TERMINUS_MACHINE_TOKEN" ]; then
	terminus auth:login --machine-token="$TERMINUS_MACHINE_TOKEN"
fi

if [ "$(terminus whoami)" -ne 0 ]; then
	echo "Terminus unauthenticated; assuming unauthenticated build"
	exit 0
fi

if [ -z "$TERMINUS_SITE" ] || [ -z "$TERMINUS_ENV" ]; then
	echo "TERMINUS_SITE and TERMINUS_ENV environment variables must be set"
	exit 1
fi

if [ -z "$WORDPRESS_ADMIN_USERNAME" ] || [ -z "$WORDPRESS_ADMIN_PASSWORD" ]; then
	echo "WORDPRESS_ADMIN_USERNAME and WORDPRESS_ADMIN_PASSWORD environment variables must be set"
	exit 1
fi

set -ex

export BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension" : {"base_url" : "http://'$TERMINUS_ENV'-'$TERMINUS_SITE'.pantheonsite.io"} }}'

./vendor/bin/behat "$@" --strict