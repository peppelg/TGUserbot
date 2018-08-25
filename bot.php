<?php
if (isset($update['update']['message']['out']) and $update['update']['message']['out'] == true) return 0;
if (!isset($msg)) return 0;

if ($msg == '/info') {
  sm($chatID, "<b>Info chat:</b>\nID: $chatID\nTitolo: $title\nUsername chat: @$chatusername\nTipo: $type\n\n<b>Informazioni utente:</b>\nID: $userID\nNome: $name\nUsername: @$username", 1);
}
if ($msg == '/promisetest') {
  $MadelineProto->messages->sendMessage(['chat_id' => $chatID, 'message' => 'Testing TGUserbot.,.,'], function($response) use($MadelineProto, $chatID) {
    $MadelineProto->messages->sendMessage(['chat_id' => $chatID, 'message' => 'Response:'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT)]);
    sleep(5);
    $MadelineProto->messages->sendMessage(['chat_id' => $chatID, 'message' => '1OO% async (+ -)']);
  });
}
