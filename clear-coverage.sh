#!/bin/bash

# clear style
rm -f ./templates/style/*.css
rm -f ./templates/style/blog/*.css

# clear cache
php bin/console cache:clear

