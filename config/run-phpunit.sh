#!/usr/bin/env bash

set -e

# This is a bit of a Drupal WTF;
# @see https://www.drupal.org/project/drupal/issues/2992069#comment-12725521
export BROWSERTEST_OUTPUT_DIRECTORY=/tmp/simpletest-browser-output-tmp

# Various directories used by Drupal's integration test suite.
DIRECTORIES="web/sites web/sites/default/files"

# We don't need all the other supervisor boilerplate.
apache2ctl start
dockerize -wait tcp://web:80 -timeout 120s

mkdir -p web/sites/simpletest/browser_output $DIRECTORIES $BROWSERTEST_OUTPUT_DIRECTORY
chown -R www-data:www-data $DIRECTORIES $BROWSERTEST_OUTPUT_DIRECTORY

set +e

su -p -s /bin/bash www-data -c 'vendor/bin/phpunit web/modules/custom'
exitCode=$?

set -e

# Allow cleanup up so as not to have issues cleaning the CI environment.
chmod -R 777 $DIRECTORIES

exit $exitCode
