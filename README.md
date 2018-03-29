# TGUserbot
![TGUserbot account manager](https://i.imgur.com/B6TUHyv.png)
![TGUserbot account manager](https://i.imgur.com/USK2Epe.png)
![TGUserbot](https://i.imgur.com/LKit3Ce.png)
Installazione
-------------
Automatica (Ubuntu/Debian)

	curl https://peppelg.github.io/tguserbot_installer.sh | sudo bash
	cd TGUserbot

Manuale:
Installa i pacchetti `git zip screen php php-mbstring php-xml php-gmp php-curl php-bcmath php-zip php-json`

	curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/bin --filename=composer
	git clone https://github.com/peppelg/TGUserbot
	cd TGUserbot
	composer update

Impostazioni
---------------
Impostazioni in impostazioni.php

	language - imposta la lingua
	session - imposta il nome del file della sessione madeline
	cronjobs - attivare i cronjob?
	send_errors - invia errori in chat
	readmsg - legge i messaggi in chat privata
	always_online - mantiene lo stato in linea
	old_chatinfo - usa il sistema di TGUserbot V1 per trovare le informazioni delle chat
	auto_reboot - se TGUserbot crasha si riavvia automaticamente
	multithread - abilita multithread
	send_data - aiuta a migliorare TGUserbot inviando alcuni dati (https://tguserbot.peppelg.space/privacy.txt)


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
	$msgid - id del messaggio
	$type - user, bot, group, supergroup, channel
	$name - nome dell'utente
	$username - username dell'utente
	$title - titolo della chat
	$chatusername - username della chat
	$info - altre informazioni
	$cronjob - id cronjob 
	
	
Funzioni

	sm(Chat, Message, Reply, ParseMode);
	
[Metodi MadelineProto](https://docs.madelineproto.xyz/API_docs/methods/)
	
Cronjobs
---------
Crea un nuovo cronjob (tra un minuto):

	cronjobAdd('next minute', 'cronjobid');
	//oppure
	cronjobAdd(time()+60, 'cronjobid');

Cancella un cronjob:

	cronjobDel('cronjobid');

Cancella tutti i cronjob:

	cronjobReset();
	
Quando sarà il momento, verrà dichiarata la variabile `$cronjob`, potrai gestire tutto da bot.php.

Esempio (bot.php):

	if ($msg == 'Invia tra un minuto un messaggio a 🅱️eppe') {
	  cronjobAdd('next minute', 'messaggio a peppe');
	  sm($chatID, 'Ok! Tra un minuto invierò un messaggio a Peppe');
	}
	if (isset($cronjob) and $cronjob == 'messaggio a peppe') {
	  sm('@peppelg1', 'Zao, kome stai¿¿');
	}

⚠️ I secondi non verrano considerati

Plugin
-------
Per installare un plugin crea una cartella chiamata `plugins` e butttaci dentro i plugin. Facile eh?

[Scarica un plugin di esempio](https://peppelg.github.io/tguserbotPlugin_memoryusage.php)


Creare un bot (non userbot) con TGUserbot
------------------------------------------
Avvia accountmanager.php, vai su Aggiungi account e scrivi il nome della sessione. Quando TGUserbot chiederà il numero di telefono, scrivi `bot` e poi il token del bot.

Supporto
--------
[Gruppo Telegram di TGUserbot](https://t.me/joinchat/HIyPnk3GQ7525LpP62yIWA)

[Gruppo Telegram di peppelg](https://t.me/joinchat/AAAAAEHRBNZBqxOlwtwBaQ)

[Gruppo Telegram di MadelineProto](https://t.me/pwrtelegramgroupita)

[Gruppo Telegram di MadelineProto inglese](https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg)
