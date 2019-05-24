# TGUserbot
![TGUserbot account manager](https://i.imgur.com/B6TUHyv.png)
![TGUserbot account manager](https://i.imgur.com/USK2Epe.png)
![TGUserbot](https://i.imgur.com/LKit3Ce.png)

Index
-----
- [Perchè usare TGUserbot](#perchè-usare-tguserbot)
- [Installazione](#installazione)
- [Avvio](#avvio)
- [Aggiornare Madeline](#aggiornare-madeline)
- [Multi account](#multi-account)
- [Come fare il backup delle sessioni](#creare-backup-delle-sessioni)
- [Suggerimenti per non fare bloccare il tuo bot](#come-non-fare-bloccare-il-tuo-bot)
- [Usare MadelineCli](#usare-madelinecli)
- [Promise](#promise)
- [Impostazioni](#impostazioni)
- [Variabili e funzioni](#variabili-e-funzioni)
- [Proxy](#proxy)
- [Creare cronjob](#cronjobs)
- [Plugin](#plugin)
- [Supporto](#supporto)

Perchè usare TGUserbot
-----------------------
- È facile da usare
- [Puoi usare più account](#multi-account)
- [Puoi facilmente fare un backup di tutti i tuoi account](#creare-backup-delle-sessioni)
- [È asincrono](#come-non-fare-bloccare-il-tuo-bot)
- [Puoi inviare messaggi direttamente dal terminale](#usare-madelinecli)
- [Puoi usare i cronjob](#cronjobs)
- [Puoi creare ed usare plugin](#plugin)
- [È facile usare (e anche cercare automaticamente!) un proxy](#proxy)
- Gestisce automaticamente gli update, legge i messaggi, può inviare gli errori in chat

Installazione
--------------
Automatica (Solo Ubuntu)

	curl https://peppelg.github.io/tguserbot_installer.sh | sudo bash
	cd TGUserbot

Manuale:
Installa i pacchetti `git zip screen php php-mbstring php-xml php-gmp php-curl php-bcmath php-zip php-json php-cli`

	git clone https://github.com/peppelg/TGUserbot
	cd TGUserbot && rm -rf src
	./TGUserbot.phar

[Passare da TGUserbotV3 a TGUserbotV4](https://t.me/TGUserbotChannel/13)

Avvio
-----
	./TGUserbot.phar
Avvio in background:

	./TGUserbot.phar --background

Aggiornare Madeline
------------------
Rimuovi il file `madeline.phar` e riavvia TGUserbot.

🌟 TGUserbot.phar sarà aggiornato automaticamente.


Multi account
-------------
Carica una sessione: `./TGUserbot.phar --session="nomesessione"`

Carica una sessione in background: `./TGUserbot.phar --session="nomesessione"` --background

Avvia tutte le sessioni in background: `./TGUserbot.phar startAll`

🔥 Gestisci account: `./TGUserbot.phar accounts`

Creare backup delle sessioni
----------------------------
🔥 `./TGUserbot.phar backup`

![Backup](https://i.imgur.com/8js8yQT.png)

Verrà creato un nuovo file contenente tutte le sessioni.

Come non fare bloccare il tuo bot
----------------------------------
Se hai un bot pesante, che perde molto tempo per fare delle azioni (es. eliminare una grossa quantità di messaggi), è consigliato renderlo asincrono, hai due opzioni:
- Usare [MadelinePromise](#promise) (consigliato, è più veloce ed occupa meno risorse)
- Abilitare il multithread nelle impostazioni (sconsigliato, è più lento e occupa più risorse)

🌟 Per rendere il tuo bot più veloce disabilita delle impostazioni le funzioni che non usi.

Usare MadelineCli
------------------
Abilita `cli` nelle impostazioni.

Scrivi nel terminale `namespace.metodo <parametri in json`

Esempio: `messages.sendMessage {"peer": "@peppelg", "message": "ciao"}`

![Esempio](https://i.imgur.com/JppLzJk.png)

Promise
--------------
Abilita `madelinePromise` nelle impostazioni.

Esempio:

```php
$MadelineProto->messages->sendMessage(['chat_id' => $chatID, 'message' => 'Messaggio'], function($response) use($MadelineProto, $chatID) {
  //fai qualcosa dopo aver inviato il messaggio
});
```
Trovi un esempio , in `bot.php`.

MadelinePromise è asincrono, quindi non bloccherà tutto TGUserbot.


Impostazioni
---------------
Impostazioni in settings.php

	bot_file - imposta la path del file bot.php
	madelinephar - file da includere per usare madeline (con valore default userà MadelineProtoPharProfessional, per usare l'ultima versione di MadelineProto puoi scaricare madeline.php ed impostare madeline.php come valore di madelinephar,.,)
	language - imposta la lingua
	cronjobs - attivare i cronjob?
	send_errors - invia errori in chat
	readmsg - legge i messaggi in chat privata
	always_online - mantiene lo stato in linea
	auto_reboot - se TGUserbot crasha si riavvia automaticamente
	multithread - abilita multithread
	send_data - aiuta a migliorare TGUserbot inviando alcuni dati (https://tguserbot.peppelg.space/privacy.txt)
	cli - usa madelinecli
	madelinePromise - usa promise
	proxy - vedi https://github.com/peppelg/TGUserbot#proxy
	madeline - impostazioni di madeline


Variabili e funzioni
--------------------
Variabili

	$update - update ricevuto
	$msg - messaggio
	$chatID - id della chat
	$userID - id utente
	$msgid - id del messaggio
	$type - user, bot, group, supergroup, channel
	$name - nome dell'utente
	$username - username dell'utente
	$title - titolo della chat
	$chatusername - username della chat
	$cronjob - id cronjob
	$me - informazioni sull'utente


Funzioni in functions.php

	sm(Chat, Message, Reply, ParseMode);

[Metodi MadelineProto](https://docs.madelineproto.xyz/API_docs/methods/)

Proxy
------
1. Proxy automatico: TGUserbot otterrà automaticamente un proxy e lo userà, aggiungi in settings.php ```'proxy' => 'auto'```
2. Manuale: Aggiungi in settings.php ```proxy => ['type' => 'socks5', 'ip' => 'ip del proxy', 'port' => 'porta del proxy', 'username' => 'username proxy', 'password' => 'password proxy']```. Per i proxy http sostituisci `socks5` con `http`. Puoi omettere username e password.

Cronjobs
---------
Crea un nuovo cronjob (tra un minuto):

```php
$cron->add('next minute', 'cronjobid');
//oppure
$cron->add(time()+60, 'cronjobid');
```

Cancella un cronjob:

```php
$cron->delete('cronjobid');
```

Cancella tutti i cronjob:

```php
$cron->delete();
```

Quando sarà il momento, verrà dichiarata la variabile `$cronjob`, potrai gestire tutto da bot.php.

Esempio (bot.php):

```php
if ($msg == 'Invia tra un minuto un messaggio a 🅱️eppe') {
  $cron->add('next minute', 'messaggio a peppe');
  sm($chatID, 'Ok! Tra un minuto invierò un messaggio a Peppe');
}
if ($cronjob == 'messaggio a peppe') {
  sm('@peppelg1', 'Zao, kome stai¿¿');
}
```

⚠️ I secondi non verrano considerati

Plugin
-------
Per installare un plugin crea una cartella chiamata `plugins` e metti dentro i plugin. Facile eh?

[Scarica un plugin di esempio](https://peppelg.github.io/tguserbotPlugin_memoryusage.php)


Creare un bot (non userbot) con TGUserbot
------------------------------------------
Avvia ./TGuserbot.phar, vai su Aggiungi account e scrivi il nome della sessione. Quando TGUserbot chiederà il numero di telefono, scrivi `bot` e poi il token del bot.

Supporto
--------
[Canale Telegram di TGUserbot](https://t.me/TGUserbotChannel)

[Gruppo Telegram di TGUserbot](https://t.me/joinchat/HIyPnk3GQ7525LpP62yIWA)

[Gruppo Telegram di peppelg](https://t.me/joinchat/AAAAAEHRBNZBqxOlwtwBaQ)

[Gruppo Telegram di MadelineProto](https://t.me/pwrtelegramgroupita)

[Gruppo Telegram di MadelineProto inglese](https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg)

[Metodi MadelineProto](https://docs.madelineproto.xyz/API_docs/methods/)
