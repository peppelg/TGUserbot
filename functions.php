<?php
function sm($chatID, $text, $reply = 0, $parsemode = 'HTML') {
  global $update;
  global $MadelineProto;
  if (isset($chatID) and isset($text) and $reply == 0) return $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => $text, 'parse_mode' => $parsemode]);
  if (isset($chatID) and isset($text) and $reply == 1) return $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => $text, 'reply_to_msg_id' => $update['update']['message']['id'], 'parse_mode' => $parsemode]);
}

