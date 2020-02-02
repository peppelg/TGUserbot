#!/bin/bash
if [ "$EUID" -ne 0 ]
  then 
    echo "Please run this shit as root"
    exit
fi 
apt-get install -y software-properties-common
apt-get install -y language-pack-en-base
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get -y install git zip screen curl python php7.4 php7.4-mbstring php7.4-xml php7.4-gmp php7.4-curl php7.4-bcmath php7.4-zip php7.4-json php7.4-cli
mkdir TGUserbot
cd TGUserbot
wget https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar
wget https://raw.githubusercontent.com/peppelg/TGUserbot/master/bot.php
chmod 777 TGUserbot.phar
apt-get -y install php7.4-cli
update-alternatives --set php /usr/bin/php7.4
echo OK
exit
