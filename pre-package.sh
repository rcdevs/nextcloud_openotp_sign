#!/bin/bash
APP_NAME=openotp_sign
VERSION=1.3.3

# Clean Nextcloud
# rm -fr /var/www/html/nextcloud-25.0.3/apps/$APP_NAME
rm -fr ../$APP_NAME-$VERSION.tar.gz

make

# prompting for choice
read -p "Do you wish to compress this app ? (y)Yes/(n)No:- " choice

# giving choices there tasks using
case $choice in
    [yY]* ) echo "Create Gzip" ;;
    [nN]* ) exit ;;
    *) exit ;;
esac

rm -f babel.config.js
rm -rf build/
rm -f composer.json
rm -f composer.lock
rm -f .eslintrc.js
rm -rf .git/
rm -f .gitignore
rm -f Makefile
#rm -rf node_modules/
find ./node_modules -mindepth 1 ! -regex '^./node_modules/vuejs-paginate\(/.*\)?' -delete
rm -f package.json
rm -f package-lock.json
rm -f phpunit.integration.xml
rm -f phpunit.xml
rm -f README.md
rm -rf src/
rm -f stylelint.config.js
rm -rf tests/
rm -rf translationfiles/
rm -f .travis.yml
rm -f webpack.js
rm -f "Ω $APP_NAME.code-workspace"


rm -f pre-package.sh

# Sign App
sudo -u www-data php /var/www/bayssette.fr/nextcloud-25.0.3/occ integrity:sign-app --privateKey=/opt/$APP_NAME.key --certificate=/opt/$APP_NAME.crt --path=`pwd`

# Compress to upload to GitHub
cd ..
tar -czvf $APP_NAME-$VERSION.tar.gz $APP_NAME
