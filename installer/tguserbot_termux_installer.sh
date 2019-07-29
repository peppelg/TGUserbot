#!/bin/bash
pkg update -y
pkg install git screen zip wget curl php -y
mkdir TGUserbot
cd TGUserbot
wget https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar
wget https://raw.githubusercontent.com/peppelg/TGUserbot/master/bot.php
chmod 777 TGUserbot.phar
echo OK
exit
