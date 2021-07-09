#!/bin/bash

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
