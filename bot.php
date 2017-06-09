<?php
if($msg == "/start") {
  @scrivendo($chatID); //stato sta scrivendo
  sm($chatID, "Ciao! Questo è un messaggio con risposta.", 1); //con 1 il bot risponderà al messaggio
  sm($chatID, "Ciao! Questo è un messaggio senza risposta.");
}

if($msg == "/info") {
  sm($chatID, "ChatID: $chatID \n UserID: $userID \n Tipo: $type");
}
