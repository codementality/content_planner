#!/usr/bin/env bash
set -e

if [[ $ENVIRONMENT == 'DEV' || $ENVIRONMENT == 'STAGING' ]]; then
  wait-for-it $DB_HOST:3306 --timeout=0 --strict -- echo "database service is up, proceeding..."
  QUERYRESULTS=$(drush sql-query 'show tables like "users";');

  if [[ $INSTALL_FROM_CONFIG && $QUERYRESULTS != "users" ]]; then
    echo "No 'users' table found. Installing from site config."
    drush -y site:install minimal --account-name=admin --account-pass=admin --existing-config
    LOGERRORS=$(drush ws --severity=Error)
    if [[ $LOGERRORS ]]; then
      echo "Errors detected during site initialization.  Check logs for details"
      echo "Errors: $LOGERRORS"
      exit 1
    fi
    LOGWARNINGS=$(drush ws --severity=Warning)
    if [[ $LOGWARNINGS ]]; then
      echo "Warnings detected during site initialization.  Check logs for details"
      echo "Warning: $LOGWARNINGS"
      exit 1
    fi
    LOGNOTICES=$(drush ws --severity=Notice)
    if [[ $LOGNOTICES ]]; then
      echo "Notices detected during site initialization.  Check logs for details"
      echo "Notice: $LOGNOTICES"
    fi
  fi
fi

if [[ ${DRUSH_SKIP_DEPLOY-0} != 1 ]]; then
  drush -y deploy
fi
