#!/bin/bash

if [ "$(uname)" == "Darwin" ]; then
  mysql.server stop
fi
