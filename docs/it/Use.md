# Usare TGUserbot

‼️ Per impostare la lingua italiana, apri `settings.json` (il file viene creato dopo aver avviato TGUserbot), e al posto di `"language": "en"` metti `"language": "it"`.

Avviare TGUserbot: `php TGUserbot.phar`

Avviare una sessione diversa: `php TGUserbot.phar --session="sessione2"`

Avviare TGUserbot in background: `php TGUserbot.phar --session="session" --background`

Fermare TGUserbot in background: `php TGUserbot.phar --session="session" --kill`

Avviare tutte le sessioni in background: `php TGUserbot.phar --startAll`

Fermare tutte le sessioni in background: `php TGUserbot.phar --killAll`

Forzare la chiusura di tutte le sessioni in background: `php TGUserbot.phar --forceKillAll`

Vedere i dati inviati: `php TGUserbot.phar data`

🔥 Avviare l'account manager: `php TGUserbot.phar accounts`

Dopo aver avviato TGUserbot, ti verrà chiesto il numero di telefono dell'userbot. Se vuoi fare il login con un bot, al posto del numero di telefono scrivi `bot`.

Puoi gestire le sessioni in background con `screen -r`, `screen -r <nome sessione`


## Impostare i comandi
Per creare i tuoi comandi, apri `bot.php` e mettili dopo ``//COMANDI BOT``. 

Per rispondere a un comando:
```php
if ($msg === '/comando') {
  yield $sm($chatID, 'Messaggio');
} 
```
Per rispondere a un messaggio:
```php
if ($msg === '/comando') {
  yield $sm($chatID, 'Messaggio', $msgid); //va aggiunto $msgid
} 
```

Funzione $sm: `yield $sm(ChatId, Message, Reply, ParseMode);`

⚠️ Prima di chiamare un metodo (`$sm`, `$MadelineProto->...`, ecc), devi aggiungere `yield` (serve per async).


## Includere altri file
Funzione $include: `yield $include(PercorsoFile, array('nomeVariabile' => $nomeVariabile));`

Esempio: 

Nei comandi in `bot.php`:
```php
yield $include('plugin.php', ['MadelineProto' => $MadelineProto, 'update' => $update, 'chatID' => $chatID, 'msg' => $msg, 'sm' => $sm]);
```
In `plugin.php`:
```php
if ($msg === '/plugin') {
	yield $sm($chatID, 'A message from plugin.php');
}
```


## Variabili
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
	$info - informazioni su chi invia il messaggio e la chat (contiene username, nome ecc)
	$me - informazioni sull'utente

## Programmare l'invio di un messaggio
Funzione $schedule: `yield $schedule(Time, Function);`. `Time` può essere un timestamp o [strtotime](https://www.php.net/manual/en/function.strtotime.php). Quindi, in time puoi mettere anche `next hour`, per esempio.

Esempio:
```php
if ($msg === '/schedule') {
  yield $sm($chatID, 'Message scheduled.');
  yield $schedule(time() + 10, function () use (&$MadelineProto, &$sm, $chatID) {
    yield $sm($chatID, 'Scheduled message 🤩🤩🤩'); //this message will be sent after 10 seconds
  });
}
```

## MadelineCli
Abilita `madelineCli` nelle impostazioni.

Scrivi nel terminale `namespace.metodo <parametri in json>`

Esempio: `messages.sendMessage {"peer": "@peppelg", "message": "ciao"}`

![Esempio](https://i.imgur.com/JppLzJk.png)

🆕 Adesso è possibile mandare velocemente messaggi nella chat più recente, basta scrivere nel terminale: `.r messaggio`.


### >>[Impostazioni](https://github.com/peppelg/TGUserbot/tree/master/docs/it/Settings.md)<<
