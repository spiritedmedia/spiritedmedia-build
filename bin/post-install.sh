#!/bin/bash
# Run after `composer install` or `composer update`

# Install dependencies for some required Git repos
cd wp-content/plugins/wp-pagenavi && composer update && cd ../../..

# Make PHPCS aware of WPCS
if [ -f ./vendor/bin/phpcs ]; then
  ./vendor/bin/phpcs --config-set installed_paths ../../wp-coding-standards/wpcs
fi
