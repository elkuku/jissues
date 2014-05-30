#!/bin/sh

EXTRA_PACKETS="apache2.2-common apache2 libapache2-mod-fastcgi php5 php5-mysql"
if [ "$1" ]
then
    EXTRA_PACKETS="$EXTRA_PACKETS $1"
fi

echo "---> Starting $(tput bold ; tput setaf 2)packets installation$(tput sgr0)"
echo "---> Packets to install : $(tput bold ; tput setaf 3)$EXTRA_PACKETS$(tput sgr0)"

sudo apt-add-repository ppa:ondrej/php5 -y
sudo apt-get update
sudo apt-get install -y --force-yes $EXTRA_PACKETS
