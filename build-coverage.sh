#!/usr/bin/env bash

#
# MIT License
#
# Copyright (c) 2021-2024 machinateur
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
#

# create sitemap
php bin/console sitemap "./var/build/coverage-sitemap.txt" --url-scheme="https" --url-host="127.0.0.1" --url-port="1312"

# clear style
rm -f ./templates/style/*.css
rm -f ./templates/style/blog/*.css

# clear cache
php bin/console cache:clear

# start server
#symfony local:server:start --port=1312 -d

# run style coverage
node ./build-coverage.js

# stop server
#symfony local:server:stop

# destroy sitemap
rm -f ./var/build/coverage-sitemap.txt

# clear cache
php bin/console cache:clear
