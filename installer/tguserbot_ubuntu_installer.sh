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
apt-get -y install git zip screen curl python php7.2 php7.2-mbstring php7.2-xml php7.2-gmp php7.2-curl php7.2-bcmath php7.2-zip php7.2-json php7.2-cli
mkdir TGUserbot
cd TGUserbot
wget https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar
wget https://raw.githubusercontent.com/peppelg/TGUserbot/master/bot.php
chmod 777 TGUserbot.phar
apt-get -y install php7.2-cli
update-alternatives --set php /usr/bin/php7.2
echo OK
exit
