# TGUserbot

Installazione
-------------
Automatica (Ubuntu/Debian)

	curl https://peppelg.github.io/tguserbot_installer.sh | bash -e
	cd TGUserbot

Manuale:
Installa i pacchetti `git zip screen php php-mbstring php-xml php-gmp php-curl php-mcrypt php-bcmath php-zip php-json`

	curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/bin --filename=composer
	git clone https://github.com/peppelg/TGUserbot
	cd TGUserbot
	composer update

Impostazioni
---------------
Impostazioni in impostazioni.php

	language - imposta la lingua
	session - imposta il nome del file della sessione madeline
	send_errors - invia errori in chat
	readmsg - legge i messaggi in chat privata
	always_online - mantiene lo stato in linea
	old_chatinfo - usa il sistema di TGUserbot V1 per trovare le informazioni delle chat
	auto_reboot - se TGUserbot crasha si riavvia automaticamente


Avvio
-----
	php start.php
Avvio in background:

	php start.php background


Aggiornare la base
------------------
	php start.php update


Multi account
-------------
Carica una sessione: `php start.php sessions/nomesessione.madeline`

Carica una sessione in background: `php start.php sessions/nomesessione.madeline background`

🔥 Gestisci account: `php accountmanager.php`

Variabili e funzioni
--------------------
Variabili

	$msg - messaggio
	$chatID - id della chat
	$userID - id utente
	$name - nome dell'utente
	$username - username dell'utente
	$title - titolo della chat
	$chatusername - username della chat
	
	
Funzioni

	sm(Chat, Message, Reply, ParseMode);
	
	


Supporto
--------
[Gruppo Telegram di TGUserbot](https://t.me/joinchat/HIyPnk3GQ7525LpP62yIWA)

[Gruppo Telegram di peppelg](https://t.me/joinchat/AAAAAEHRBNZBqxOlwtwBaQ)

[Gruppo Telegram di MadelineProto](https://t.me/pwrtelegramgroupita)

[Gruppo Telegram di MadelineProto inglese](https://t.me/joinchat/AAAAAD6K-aJng8nt7zB93w)
