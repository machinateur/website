#!/usr/bin/env bash

#
# MIT License
#
# Copyright (c) 2021-2021 machinateur
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

# clone source
if [ ! -d "./var/build/bootstrap" ]; then
  git clone --depth 1 --branch v5.0.2 https://github.com/twbs/bootstrap.git ./var/build/bootstrap
  rm -rf ./var/build/bootstrap/.git
fi

# enter directory
cd ./var/build/bootstrap || exit 1
npm i

# replace source style
cp -f ../../../res/style/bootstrap.scss ./scss/bootstrap.scss
cp -f ../../../res/style/_forms.scss ./scss/_forms.scss
cp -f ../../../res/style/_helpers.scss ./scss/_helpers.scss

# replace source script
cp -f ../../../res/script/index.umd.js ./js/index.umd.js

# execute build
npm run dist

# copy build result
cp -f ./dist/css/bootstrap.min.css ../../../public/res/style/bootstrap.min.css
cp -f ./dist/css/bootstrap.min.css.map ../../../public/res/style/bootstrap.min.css.map
cp -f ./dist/js/bootstrap.min.js ../../../public/res/script/bootstrap.bundle.min.js
cp -f ./dist/js/bootstrap.min.js.map ../../../public/res/script/bootstrap.bundle.min.js.map

# exit directory
cd ../../../

sed -i -e 's/bootstrap\.min\.js\.map/bootstrap\.bundle\.min\.js\.map/g' ./public/res/script/bootstrap.bundle.min.js

# delete source
#rm -rf ./var/build/bootstrap
