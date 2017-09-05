#!/bin/bash
# Run after `composer install` or `composer update`

# Make PHPCS aware of WPCS
if [ -f ./vendor/bin/phpcs ]; then
  ./vendor/bin/phpcs --config-set installed_paths ../../wp-coding-standards/wpcs
fi
