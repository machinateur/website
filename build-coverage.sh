#!/bin/bash

# create sitemap
php bin/console app:sitemap "./var/build/coverage-sitemap.txt" --url-scheme="https" --url-host="127.0.0.1" --url-port="1312"

# clear cache
php bin/console cache:clear

# clear style
rm -f ./templates/style/*.css
rm -f ./templates/style/blog/*.css

# start server
#symfony local:server:start --port=1312 -d

# run style coverage
node ./build-coverage.js

# stop server
#symfony local:server:stop

# destroy sitemap
rm -rf ./var/build/coverage-sitemap.txt

# clear cache
php bin/console cache:clear
