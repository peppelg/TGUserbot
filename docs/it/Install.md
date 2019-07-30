# Installazione
Puoi installare TGUserbot automaticamente se hai Ubuntu, usi Termux, o Windows. Se usi una distro diversa da Ubuntu, puoi installarlo manualmente.

Ubuntu
------
     curl https://raw.githubusercontent.com/peppelg/TGUserbot/master/installer/tguserbot_ubuntu_installer.sh | sudo bash
     cd TGUserbot
     
Termux
------
    curl https://raw.githubusercontent.com/peppelg/TGUserbot/master/installer/tguserbot_termux_installer.sh | bash
    cd TGUserbot

Windows
-------
Scarica il file `.zip` da https://t.me/TGUserbotReleases, estrailo, avvia `cmd.bat` e scrivi `php TGUserbot.phar`.

Manualmente
------------
Prima, installa i pacchetti `git zip screen php php-mbstring php-xml php-gmp php-curl php-bcmath php-zip php-json php-cli`. Assicurati di aver installato PHP 7.1 o superiore.

Poi scarica TGUserbot con `mkdir TGUserbot && cd TGUserbot && wget https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar && wget https://github.com/peppelg/TGUserbot/raw/master/bot.php`



### >>[Usare TGUserbot](https://github.com/peppelg/TGUserbot/tree/master/docs/it/Use.md)<<
