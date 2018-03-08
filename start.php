#!/usr/bin/php
<?php
echo 'Loading settings...'.PHP_EOL;
require('settings.php');
$settings_default = ['language' => 'it', 'session' => 'sessions/default.madeline', 'cronjobs' => true, 'send_errors' => true, 'readmsg' => true, 'always_online' => false, 'old_chatinfo' => false, 'auto_reboot' => true, 'multithread' => false, 'auto_updates' => true, 'madeline' => ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e', 'lang_code' => $settings['language'], 'app_version' => '4.7.0'], 'logger' => ['logger' => 0], 'updates' => ['handle_old_updates' => 0]]];
if (isset($settings) and is_array($settings)) $settings = array_merge($settings_default, $settings); else $settings = $settings_default;
$strings = @json_decode(file_get_contents('strings_'.$settings['language'].'.json'), 1);
if (!file_exists('sessions')) mkdir('sessions');
if (!isset($settings['multithread'])) $settings['multithread'] = 0;
if (!is_array($strings)) {
  if (!file_exists('strings_it.json')) {
    echo 'downloading strings_it.json...'.PHP_EOL;
    file_put_contents('strings_it.json', file_get_contents('https://raw.githubusercontent.com/peppelg/TGUserbot/master/strings_it.json'));
  }
  $strings = json_decode(file_get_contents('strings_it.json'), 1);
}
if (isset($argv[1]) and $argv[1]) {
  if ($argv[1] == 'background') {
    shell_exec('screen -d -m php start.php');
    echo PHP_EOL.$strings['background'].PHP_EOL;
    exit;
  }
  if (isset($argv[2]) and $argv[2] == 'background') {
    shell_exec('screen -d -m php start.php '.escapeshellarg($argv[1]));
    echo PHP_EOL.$strings['background'].PHP_EOL;
    exit;
  }
  if ($argv[1] == 'update') {
    echo PHP_EOL.$strings['updating'].PHP_EOL;
    if(!rename('bot.php', 'bot.php_')) die('Error');
    rename('settings.php', 'settings.php_');
    rename('functions.php', 'functions.php_');
    shell_exec('git reset --hard HEAD');
    shell_exec('git pull');
    sleep(0.5);
    rename('bot.php_', 'bot.php');
    rename('settings.php_', 'settings.php');
    rename('functions.php_', 'functions.php');
    passthru('composer update');
    echo PHP_EOL.$strings['done'].PHP_EOL;
    exit;
  }
  $settings['session'] = $argv[1];
}
if ($settings['auto_updates']) {
  echo $strings['checking_updates'];
  if (trim(exec('git ls-remote git://github.com/peppelg/TGUserbot.git refs/heads/master | cut -f 1')) !== trim(file_get_contents('.git/refs/heads/master'))) {
    echo ' OK'.PHP_EOL;
    echo $strings['new_update'].PHP_EOL;
    sleep(10);
    echo 'Aggiornamento in corso...'.PHP_EOL;
    passthru('php start.php update');
    echo PHP_EOL.PHP_EOL.'Riavvio...'.PHP_EOL.PHP_EOL;
    pcntl_exec($_SERVER['_'], array('start.php', $settings['session']));
    exit;
  } else {
    echo ' OK'.PHP_EOL;
  }
}
echo $strings['loading'].PHP_EOL;
require('vendor/autoload.php');
include('functions.php');
if ($settings['multithread'] and !function_exists('pcntl_fork')) $settings['multithread'] = false;
if ($settings['auto_reboot'] and function_exists('pcntl_exec')) {
  register_shutdown_function(function () {
    global $settings;
    pcntl_exec($_SERVER['_'], array('start.php', $settings['session']));
  });
}
if (file_exists('plugins') and is_dir('plugins')) {
  $settings['plugins'] = true;
  echo $strings['loading_plugins'].PHP_EOL;
  class TGUserbotPlugin {
    public function onUpdate() {

    }
    public function onStart() {

    }
  }
  $pluginslist = array_values(array_diff(scandir('plugins'), ['..', '.']));
  $plugins = [];
  $pluginN = 0;
  foreach ($pluginslist as $plugin) {
    if (substr($plugin, -4) == '.php') {
      include('plugins/'.$plugin);
    }
  }
  foreach (get_declared_classes() as $class) {
    if (is_subclass_of($class, 'TGUserbotPlugin')) {
      $pluginN++;
      $plugin = new $class();
      if (method_exists($class, 'onStart')) {
        $plugins[$class] = $plugin;
      }
    }
  }
  echo $pluginN.' '.$strings['plugins_loaded'].PHP_EOL;
  if ($pluginN == 0) $settings['plugins'] = false;
} else {
  $settings['plugins'] = false;
}
if (!file_exists($settings['session'])) {
  $MadelineProto = new \danog\MadelineProto\API($settings['madeline']);
  echo $strings['loaded'].PHP_EOL;
  echo $strings['ask_phone_number'];
  $phoneNumber = trim(fgets(STDIN));
  if (strtolower($phoneNumber) === 'bot') {
    echo $strings['ask_bot_token'];
    $MadelineProto->bot_login(trim(fgets(STDIN)));
  } else {
    $sentCode = $MadelineProto->phone_login($phoneNumber);
    echo $strings['ask_login_code'];
    $code = trim(fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1));
    $authorization = $MadelineProto->complete_phone_login($code);
    if ($authorization['_'] === 'account.password') {
      echo $strings['ask_2fa_password'];
      $password = trim(fgets(STDIN));
      if ($password == '') $password = trim(fgets(STDIN));
      $authorization = $MadelineProto->complete_2fa_login($password);
    }
    if ($authorization['_'] === 'account.needSignup') {
      echo $strings['ask_name'];
      $name = trim(fgets(STDIN));
      if ($name == '') $name = trim(fgets(STDIN));
      if ($name == '') $name = 'TGUserbot';
      $authorization = $MadelineProto->complete_signup($name, '');
    }
  }
  $MadelineProto->session = $settings['session'];
  $MadelineProto->serialize($settings['session']);
} else {
  $MadelineProto = new \danog\MadelineProto\API($settings['session'], $settings['madeline']);
  echo $strings['loaded'].PHP_EOL;
}
echo $strings['session_loaded'].PHP_EOL;
if ($settings['plugins']) {
  foreach ($plugins as $plugin) {
    $plugin->onStart();
  }
}
if (isset($settings['cronjobs']) and $settings['cronjobs']) {
  function cronjobAdd($time, $id) {
    global $MadelineProto;
    if (!is_numeric($time) or strlen($time) !== 10) {
      $time = strtotime($time);
    }
    if (!is_numeric($time)) return false;
    if ($time < time()) return false;
    $MadelineProto->cronjobs[$time] = $id;
    return true;
  }
  function cronjobDel($id) {
    global $MadelineProto;
    $cronid = array_search($id, $MadelineProto->cronjobs);
    if ($cronid !== false) {
      unset($MadelineProto->cronjobs[$cronid]);
      return true;
    } else {
      return false;
    }
  }
  function cronjobReset() {
    global $MadelineProto;
    $MadelineProto->cronjobs = [];
    return true;
  }
  function cronrun() {
    global $MadelineProto;
    global $settings;
    global $strings;
    global $plugins;
    global $msg;
    global $msgid;
    global $type;
    global $cronjob;
    $now = date('d m Y H i');
    if (isset($MadelineProto->cronjobs) and !empty($MadelineProto->cronjobs)) {
      foreach ($MadelineProto->cronjobs as $time => $cronjob) {
        if (date('d m Y H i', $time) === $now) {
          cronjobDel($cronjob);
          if (is_string($cronjob)) echo 'CRONJOB >>> '.$cronjob.PHP_EOL;
          else echo 'CRONJOB >>> *array*'.PHP_EOL;
          $msg = 'cronjob';
          $msgid = 'cronjob';
          $type = 'cronjob';
          if ($settings['plugins']) {
            foreach ($plugins as $plugin) {
              $plugin->onUpdate();
            }
          }
          try {
            require('bot.php');
            $cronjob = NULL;
          } catch(Exception $e) {
            echo $strings['error'].$e.PHP_EOL;
          }
        }
      }
    }
  }
}
$offset = 0;
while (true) {
  if ($settings['always_online']) {
    if (date('s') == 30) {
      $MadelineProto->account->updateStatus(['offline' => 0]);
    }
  }
  if (isset($settings['cronjobs']) and $settings['cronjobs']) {
    $msg = NULL;
    $msgid = NULL;
    $type = NULL;
    $cronjob = NULL;
    cronrun();
  }
  try {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
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
      } else {
        if (isset($update['update']['message'])) {
          if (isset($update['update']['message']['to_id'])) $info['to'] = $MadelineProto->get_info($update['update']['message']['to_id']);
          if (isset($info['to']['bot_api_id'])) $chatID = $info['to']['bot_api_id'];
          if (isset($info['to']['type'])) $type = $info['to']['type'];
          if (isset($userID)) $info['from'] = $MadelineProto->get_info($userID);
          if (isset($info['to']['User']['self']) and isset($userID) and $info['to']['User']['self'] and $userID) $chatID = $userID;
          if (isset($type) and $type == 'chat') $type = 'group';
          if (isset($info['from']['User']['first_name'])) $name = $info['from']['User']['first_name']; else $name = NULL;
          if (isset($info['to']['Chat']['title'])) $title = $info['to']['Chat']['title']; else $title = NULL;
          if (isset($info['from']['User']['username'])) $username = $info['from']['User']['username']; else $username = NULL;
          if (isset($info['to']['Chat']['username'])) $chatusername = $info['to']['Chat']['username']; else $chatusername = NULL;
        }
      }
      if ($settings['readmsg'] and isset($chatID) and $chatID and isset($userID) and $userID and isset($msgid) and $msgid and isset($type) and $type) {
        try {
          if (in_array($type, ['user', 'bot', 'group'])) {
            $MadelineProto->messages->readHistory(['peer' => $chatID, 'max_id' => $msgid]);
          } elseif(in_array($type, ['channel', 'supergroup'])) {
            $MadelineProto->channels->readHistory(['channel' => $chatID, 'max_id' => $msgid]);
          }
        } catch(Exception $e) { }
      }
      if (isset($msg) and $msg) {
        if (isset($msg) and isset($chatID) and isset($type) and isset($userID) and $msg and $chatID and $type and $userID) {
          if ($type == 'user') {
            echo $name.' ('.$userID.') >>> '.$msg.PHP_EOL;
          } else {
            echo $name.' ('.$userID.') -> '.$title.' ('.$chatID.') >>> '.$msg.PHP_EOL;
          }
        }
      }
      if (!isset($msg)) $msg = NULL;
      if (!isset($chatID)) $chatID = NULL;
      if (!isset($userID)) $userID = NULL;
      if (!isset($msgid)) $msgid = NULL;
      if (!isset($type)) $type = NULL;
      if (!isset($name)) $name = NULL;
      if (!isset($username)) $username = NULL;
      if (!isset($chatusername)) $chatusername = NULL;
      if (!isset($title)) $title = NULL;
      if (!isset($info)) $info = NULL;
      $cronjob = NULL;
      if ($settings['plugins']) {
        foreach ($plugins as $plugin) {
          $plugin->onUpdate();
        }
      }
      if ($settings['multithread']) {
        $pid = pcntl_fork();
        if ($pid == -1) {
          die('could not fork');
        } else if ($pid) {
        } else {
          try {
            require('bot.php');
          } catch(Exception $e) {
            echo $strings['error'].$e.PHP_EOL;
            if (isset($chatID) and $settings['send_errors']) {
              try {
                $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
              } catch(Exception $e) { }
            }
          }
          posix_kill(posix_getpid(), SIGTERM);
        }
      } else {
        try {
          require('bot.php');
        } catch(Exception $e) {
          echo $strings['error'].$e.PHP_EOL;
          if (isset($chatID) and $settings['send_errors']) {
            try {
              $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
            } catch(Exception $e) { }
          }
        }
      }
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
    }
  } catch(Exception $e) {
    echo $strings['error'].$e.PHP_EOL;
    if (isset($chatID) and $settings['send_errors']) {
      try {
        $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
      } catch(Exception $e) { }
    }
  }
}
