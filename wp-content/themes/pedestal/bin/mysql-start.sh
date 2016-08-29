#!/bin/bash

if [ "$(uname)" == "Darwin" ]; then
  mysql.server start
fi
