#!/usr/bin/env bash

terminus site backup create --site=billypenn --env=live
terminus site clone-env --site=billypenn --from-env=live --to-env=test --db
terminus wp bp neutralize-db --site=billypenn --env=test
terminus site backup create --site=billypenn --env=test
