#!/bin/sh

VHOSTNAME="virtualhost.local"

if [ "$1" ]
then
    VHOSTNAME="$1"
fi

echo "---> Applying $(tput bold ; tput setaf 2)apache2 configuration$(tput sgr0)"

# enable php-fpm
sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
sudo a2enmod rewrite actions fastcgi alias
echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
#sudo ~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

# configure apache virtual hosts
#sudo cp -f build/travis-ci-apache /etc/apache2/sites-available/default
#sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
#sudo service apache2 restart


echo "--> Enabling virtual host $(tput setaf 2)$VHOSTNAME$(tput sgr0)"
sudo a2ensite $VHOSTNAME

echo "---> Restarting $(tput bold ; tput setaf 2)apache2$(tput sgr0)"

#sudo /etc/init.d/apache2 restart
sudo service apache2 restart
