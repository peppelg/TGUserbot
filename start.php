<?php
echo 'Loading settings...'.PHP_EOL;
require('settings.php');
$strings = @json_decode(file_get_contents('strings_'.$settings['language'].'.json'), 1);
if (!isset($settings['multithread'])) $settings['multithread'] = 0;
if ($settings['multithread'] and function_exists('pcntl_fork') == 0) $settings['multithread'] = 0;
if (!is_array($strings)) {
  if (!file_exists('strings_it.json')) {
    echo 'downloading strings_it.json...'.PHP_EOL;
    file_put_contents('strings_it.json', file_get_contents('https://raw.githubusercontent.com/peppelg/TGUserbot/master/strings_it.json'));
  }
  $strings = json_decode(file_get_contents('strings_it.json'), 1);
}
if (isset($argv[1]) and $argv[1] == 'background') {
  shell_exec('screen -d -m php start.php');
  echo PHP_EOL.$strings['background'].PHP_EOL;
  exit;
}
if (isset($argv[1]) and $argv[1] == 'update') {
  echo PHP_EOL.$strings['updating'].PHP_EOL;
  $bot = file_get_contents('bot.php');
  $settings = file_get_contents('settings.php');
  shell_exec('git reset --hard HEAD');
  shell_exec('git pull');
  passthru('composer update');
  file_put_contents('bot.php', $bot);
  file_put_contents('settings.php', $settings);
  echo PHP_EOL.$strings['done'].PHP_EOL;
  exit;
}
if ($settings['auto_reboot'] and function_exists('pcntl_exec')) {
  register_shutdown_function(function () {
    pcntl_exec($_SERVER['_'], array("start.php", 0));
  });
}
echo $strings['loading'].PHP_EOL;
require('vendor/autoload.php');
include('functions.php');
if ($settings['multithread']) {
  $m = readline($strings['shitty_multithread_warning']);
  if ($m != 'y') exit;
}
$MadelineProto = new \danog\MadelineProto\API(['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e', 'lang_code' => $settings['language']], 'logger' => ['logger' => 0], 'updates' => ['handle_old_updates' => 0]]);
echo $strings['loaded'].PHP_EOL;
set_error_handler(
  function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
  }
);
if (!file_exists($settings['session'])) {
  echo $strings['ask_phone_number'];
  $phoneNumber = fgets(STDIN);
  $sentCode = $MadelineProto->phone_login($phoneNumber);
  echo $strings['ask_login_code'];
  $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
  $authorization = $MadelineProto->complete_phone_login($code);
  if ($authorization['_'] === 'account.password') {
    $authorization = $MadelineProto->complete_2fa_login(readline($strings['ask_2fa_password']));
  }
  if ($authorization['_'] === 'account.needSignup') {
    /*echo $strings['ask_name'];
    $name = fgets(STDIN);
    if ($name == "") {
      $name = 'TGUserbot';
    }
    $authorization = $MadelineProto->complete_signup($name, '');
  }*/
  echo PHP_EOL . 'Registra il tuo account prima, o sarà bannato da Telegram all\'istante.' . PHP_EOL;
  $MadelineProto->serialize($settings['session']);
} else {
  $MadelineProto = \danog\MadelineProto\Serialization::deserialize($settings['session']);
}
echo $strings['session_loaded'].PHP_EOL;
$offset = 0;
while (true) {
  if ($settings['always_online']) {
    if (date('s') == 30) {
      $MadelineProto->account->updateStatus(['offline' => 0]);
    }
  }
  try {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    foreach ($updates as $update) {
      $offset = $update['update_id'] + 1;
      if (isset($update['update']['message']['from_id'])) $userID = $update['update']['message']['from_id'];
      if (isset($update['update']['message']['id'])) $msgid = $update['update']['message']['id'];
      if (isset($update['update']['message']['message'])) $msg = $update['update']['message']['message'];
      if ($settings['old_chatinfo']) {
        if (isset($update['update']['message']['to_id']['channel_id'])) {
          $chatID = '-100'.$update['update']['message']['to_id']['channel_id'];
          $type = 'supergroup';
        }
        if (isset($update['update']['message']['to_id']['chat_id'])) {
          $chatID = '-'.$update['update']['message']['to_id']['chat_id'];
          $type = 'group';
        }
        if (isset($update['update']['message']['to_id']['user_id'])) {
          $chatID = $update['update']['message']['from_id'];
          $type = 'user';
        }
        $name = NULL;
        $title = NULL;
        $username = NULL;
        $chatusername = NULL;
      } else {
        if (isset($update['update']['message'])) {
          $info['to'] = $MadelineProto->get_info($update['update']['message']['to_id']);
          if (isset($info['to']['bot_api_id'])) $chatID = $info['to']['bot_api_id'];
          if (isset($info['to']['type'])) $type = $info['to']['type'];
          if (isset($userID)) $info['from'] = $MadelineProto->get_info($userID);
          if (isset($info['to']['User']['self']) and $info['to']['User']['self']) $chatID = $userID;
          if (isset($type) and $type == 'chat') $type = 'group';
          if (isset($info['from']['User']['first_name'])) $name = $info['from']['User']['first_name']; else $name = NULL;
          if (isset($info['to']['Chat']['title'])) $title = $info['to']['Chat']['title']; else $title = NULL;
          if (isset($info['from']['User']['username'])) $username = $info['from']['User']['username']; else $username = NULL;
          if (isset($info['to']['Chat']['username'])) $chatusername = $info['to']['Chat']['username']; else $chatusername = NULL;
        }
      }
      if (isset($msg) and $msg) {
        if ($settings['readmsg'] and isset($type) and isset($msgid) and isset($chatID) and $type == 'user' and $msgid and $chatID) $MadelineProto->messages->readHistory(['peer' => $chatID, 'max_id' => $msgid]);
        if (isset($msg) and isset($chatID) and isset($type) and $msg and $chatID and $type) {
          if ($type == 'user') {
            echo $name.' ('.$userID.') >>> '.$msg.PHP_EOL;
          } else {
            echo $name.' ('.$userID.') -> '.$title.' ('.$chatID.') >>> '.$msg.PHP_EOL;
          }
        }
      }
      if ($settings['multithread']) {
        if (!isset($tmsgid)) $tmsgid = 1;
        if (isset($msg) and isset($chatID) and isset($userID) and isset($msgid) and isset($tmsgid) and $msg and $chatID and $userID and $msgid != $tmsgid) {
          $pid = pcntl_fork();
          if ($pid == -1) {
            die('could not fork');
          } elseif ($pid) {
          } else {
            $MadelineProto->reset_session(1, 1);
            require('bot.php');
          }
        } elseif(isset($tmsgid) and isset($msgid) and $tmsgid != $msgid) {
          require('bot.php');
        }
      } elseif(isset($msg) and isset($chatID) and $msg) {
        try {
          require('bot.php');
        } catch(Exception $e) {
          echo $strings['error'].$e->getMessage().PHP_EOL;
          if (isset($chatID) and $settings['send_errors']) {
            try {
              $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
              $MadelineProto->serialize($settings['session']);
            } catch(Exception $e) { }
          }
        }
      }
      if ($settings['multithread'] and isset($msgid) and $msgid) $tmsgid = $msgid;
      if (isset($msg)) unset($msg);
      if (isset($chatID)) unset($chatID);
      if (isset($userID)) unset($userID);
      if (isset($type)) unset($type);
      if (isset($msgid)) unset($msgid);
      if (isset($name)) unset($name);
      if (isset($username)) unset($username);
      if (isset($chatusername)) unset($chatusername);
      if (isset($title)) unset($title);
      if (isset($info)) $info = [];
      $MadelineProto->serialize($settings['session']);
    }
  } catch(Exception $e) {
    echo $strings['error'].$e->getMessage().PHP_EOL;
    if (isset($chatID) and $settings['send_errors']) {
      try {
        $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
        $MadelineProto->serialize($settings['session']);
      } catch(Exception $e) { }
    }
  }
}
