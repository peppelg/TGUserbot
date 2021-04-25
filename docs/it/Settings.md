# Impostazioni

Il file delle impostazioni viene creato automaticamente al primo avvio.

Il nome del file delle impostazioni è `settings.php`.

`$settings` è un array con le impostazioni di TGUserbot:

| Impostazione | Descrizione | Valore | Linux | Windows | Web |
| ------------ | ----------- | ------ | ----- | ------- | --- |
| language | imposta la lingua | true/false | ✅ | ✅ | ✅ 
| bot_file | imposta il file bot.php con i comandi | FILE_PATH | ✅ | ✅ | ✅ |
| readmsg | doppia spunta per i messaggi ricevuti | true/false | ✅ | ✅ | ✅ |
| send_errors | invia messaggi di errore in chat | true/false | ✅ | ✅ | ✅ |
| always_online | stato Telegram dell'userbot sempre online | true/false | ✅ | ✅ | ✅ |
| auto_reboot | auto riavvia se crasha | true/false | ✅ | ✅ | ❌ |
| madelineCli | abilita MadelineCli | true/false | ✅ | ❌ | ❌ |
| send_data | invia dati statistici `(https://tguserbot.peppelg.space/privacy.txt)` | true/false | ✅ | ✅ | ✅ |


`$madelineSettings` è un array con le [impostazioni di MadelineProto](https://docs.madelineproto.xyz/docs/SETTINGS.html)

### >>[Supporto](https://github.com/peppelg/TGUserbot/tree/master/docs/it/Help.md)<<
