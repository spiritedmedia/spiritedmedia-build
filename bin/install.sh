#!/bin/bash

yarn install

if [ "$CIRCLECI" = true ] ; then
  composer install -o --no-dev
else
  composer install -o
fi

# Write some necessary PHP INI settings to the custom INI directory
PHP_CUSTOM_INI_DIR=$(php --ini | grep 'Scan for additional' | sed -n -e 's/^Scan for additional .ini files in: //p')
if [ ! -d $PHP_CUSTOM_INI_DIR ]; then
  mkdir $PHP_CUSTOM_INI_DIR
fi
PHP_CUSTOM_INI_FILE="$PHP_CUSTOM_INI_DIR/spiritedmedia.ini"
if [ ! -f $PHP_CUSTOM_INI_FILE ]; then
  # Without this, PHP scripts executed by NodeJS will wait for the timeout
  # before executing the script -- the default timeout is usually 60 seconds,
  # meaning that PHP scripts take 60 seconds plus n seconds to execute
  touch $PHP_CUSTOM_INI_FILE
  echo 'default_socket_timeout = 1' > $PHP_CUSTOM_INI_FILE
fi

# Tell Git to read our hooks
git config core.hooksPath .githooks
