#!/bin/bash

# Install Pedestal deps
npm install
bower install
bundle install
composer install -o

# Set WPCS as PHPCS install path
./vendor/bin/phpcs --config-set installed_paths lib/wpcs

# Build Pedestal
grunt build
