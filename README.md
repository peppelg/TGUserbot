# TGUserbot

Installazione
-------------
Automatica (Ubuntu/Debian)

	curl https://peppelg.github.io/tguserbot_install.sh | sudo bash -e
	cd TGUserbot

Manuale
Installa i pacchetti `php php-mbstring php-xml php-gmp php-curl php-mcrypt php-bcmath`

	curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/bin --filename=composer
	git clone https://github.com/peppelg/TGUserbot
	cd TGUserbot
	composer update
	
Configurazione
---------------
Apri il file `.env` e sostituisci `+390123456789` col numero di telefono.
E' consigliato [creare](https://my.telegram.org) l'api_id e l'api_hash.
Per "personalizzare" il bot modifica `bot.php`


Avvio
-----
	php start.php
Avvio in background:

	php start.php background


Aggiornare la base
------------------
	php start.php update


Supporto
--------
[Gruppo Telegram](https://t.me/joinchat/AAAAAEHRBNZBqxOlwtwBaQ)

[Gruppo Telegram di MadelineProto](https://t.me/pwrtelegramgroupita)

[Gruppo Telegram di MadelineProto inglese](https://t.me/pwrtelegramgroup)
