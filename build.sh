#!/bin/bash

# clone source
git clone --depth 1 --branch v5.0.1 https://github.com/twbs/bootstrap.git ./var/build/bootstrap
rm -rf ./var/build/bootstrap/.git

# enter directory
cd ./var/build/bootstrap || exit 1
npm i

# replace source style
cp -f ../../../res/style/bootstrap.scss ./scss/bootstrap.scss

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

# delete source
rm -rf ./var/build/bootstrap
