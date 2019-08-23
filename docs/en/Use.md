# Using TGUserbot

If you are using a web hosting, follow the installation guide and then open in your browser the copied file (`index.php`). The `bot.php` file is inside a folder in `TGUserbot`. You can't have more than one session on a web host.

Start TGUserbot: `php TGUserbot.phar`

Start a different session: `php TGUserbot.phar --session="sessione2"`

Start TGUserbot in backgroud: `php TGUserbot.phar --session="session" --background`

Stop TGUserbot in background: `php TGUserbot.phar --session="session" --kill`

Start all sessions in background: `php TGUserbot.phar --startAll`

Stop all sessions in background: `php TGUserbot.phar --killAll`

Force-stop all sessions in background: `php TGUserbot.phar --forceKillAll`

View sent data: `php TGUserbot.phar data`

🔥 Start account manager: `php TGUserbot.phar accounts`

After having started TGUserbot, it will be asked you phone number. If you wanna login as a bot, type `bot` instead of the phone number.

You can manage sessions in background with: `screen -r`, `screen -r <nome sessione`


## Commands
To create your command, open `bot.php` and put it after ``//COMANDI BOT``. 

To reply to a command:
```php
if ($msg === '/comando') {
  yield $sm($chatID, 'Message');
} 
```
To reply to a message:
```php
if ($msg === '/comando') {
  yield $sm($chatID, 'Message', $msgid); //add $msgid to reply
} 
```

$sm function: `yield $sm(ChatId, Message, Reply, ParseMode);`

⚠️ Before calling a method (`$sm`, `$MadelineProto->...`, ecc), you must add `yield`.


## Include other files
$include function: `yield $include(FilePath, array('variableName' => $variableName));`

Example: 

In `bot.php` commands:
```php
yield $include('plugin.php', ['MadelineProto' => $MadelineProto, 'update' => $update, 'chatID' => $chatID, 'msg' => $msg, 'sm' => $sm]);
```
In `plugin.php`:
```php
if ($msg === '/plugin') {
	yield $sm($chatID, 'A message from plugin.php');
}
```


## Variables
	$update - received update
	$msg - message
	$chatID - chat id
	$userID - user id
	$msgid - message id
	$type - user, bot, group, supergroup, channel
	$name - user's name
	$username - user's username
	$title - chat title
	$chatusername - chat username
	$info - information of the user and the chat
	$me - userbot informations

## Schedule a message
$schedule function: `yield $schedule(Time, Function);`. `Time` can be a timestamp or [strtotime](https://www.php.net/manual/en/function.strtotime.php). So, for example, you can also put `next hour` in Time.

Example:
```php
<?php
if ($msg === '/schedule') {
  yield $sm($chatID, 'Message scheduled.');
  yield $schedule(time() + 10, function () use (&$MadelineProto, &$sm, $chatID) {
    yield $sm($chatID, 'Scheduled message 🤩🤩🤩'); //this message will be sent after 10 seconds
  });
}
```

## MadelineCli
Enable `madelineCli` in settings.

Type on the terminal `namespace.method <json parameters>`

Example: `messages.sendMessage {"peer": "@peppelg", "message": "Hello!"}`

![Example](https://i.imgur.com/JppLzJk.png)

🆕 Now you can send a message in the last chat, just type `.r message`.


### >>[Settings](https://github.com/peppelg/TGUserbot/tree/master/docs/en/Settings.md)<<
