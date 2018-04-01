#!/usr/bin/php
<?php
define('PID', getmypid());
chdir(__DIR__);
require_once('vendor/autoload.php');
include_once('functions.php');
$c = new Colors\Color();
$MadelineProto = NULL;
$update = NULL;
class TGUserbotPlugin {
  public function onUpdate($update) { }
  public function onStart() { }
}

class TGUserbot {
  public $settings = NULL;
  protected $plugins = NULL;
  public $strings = NULL;
  public $me = NULL;
  public function __construct() {
    require_once('settings.php');
    $settings_default = ['language' => 'it', 'session' => 'sessions/default.madeline', 'cronjobs' => true, 'send_errors' => true, 'readmsg' => true, 'always_online' => false, 'auto_reboot' => true, 'multithread' => false, 'auto_updates' => true, 'send_data' => true, 'plugins_dir' => 'plugins', 'plugins' => false, 'cli' => true, 'madeline' => ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e', 'lang_code' => $settings['language'], 'app_version' => '4.7.0'], 'logger' => ['logger' => 0], 'updates' => ['handle_old_updates' => 0]]];
    if (isset($settings) and is_array($settings)) $settings = array_merge($settings_default, $settings); else $settings = $settings_default;
    if ($settings['multithread'] and !function_exists('pcntl_fork')) $settings['multithread'] = false;
    if (isset($GLOBALS['argv'][1]) and $GLOBALS['argv'][1] != 'background') $settings['session'] = $GLOBALS['argv'][1];
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
    $this->settings = $settings;
    $this->strings = $strings;
    if (file_exists($this->settings['plugins_dir'])) {
      echo $this->strings['loading_plugins'];
      $pluginN = $this->load_plugins($this->settings['plugins_dir']);
      echo ' '.$GLOBALS['c']('OK: '.$pluginN.' '.$this->strings['plugins_loaded'])->white->bold->bg_green.PHP_EOL;
    }
    return true;
  }
  private function load_plugins($dir = 'plugins') {
    if (!file_exists($dir)) {
      $this->settings['plugins'] = false;
      return false;
    }
    $pluginslist = array_values(array_diff(scandir($dir), ['..', '.']));
    $plugins = [];
    $pluginN = 0;
    foreach ($pluginslist as $plugin) {
      if (substr($plugin, -4) == '.php') {
        include($dir.'/'.$plugin);
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
    if ($pluginN == 0) {
      $this->settings['plugins'] = false;
      return false;
    } else {
      $this->settings['plugins'] = true;
      $this->plugins = $plugins;
      return $pluginN;
    }
  }
  public function update() {
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
  }
  public function check_updates() {
    if ($this->settings['auto_updates']) {
      echo $this->strings['checking_updates'];
      if (trim(exec('git ls-remote git://github.com/peppelg/TGUserbot.git refs/heads/master | cut -f 1')) !== trim(file_get_contents('.git/refs/heads/master'))) {
        echo ' '.$GLOBALS['c']('OK')->white->bold->bg_green.PHP_EOL;
        echo $this->strings['new_update'].PHP_EOL;
        sleep(10);
        echo $this->strings['updating'].PHP_EOL;
        $this->update();
        echo PHP_EOL.PHP_EOL.$this->strings['rebooting'].PHP_EOL.PHP_EOL;
        passthru('php '.escapeshellarg(__FILE__).' '.$this->settings['session']);
        exit;
      } else {
        echo ' '.$GLOBALS['c']('OK')->white->bold->bg_green.PHP_EOL;
      }
    }
  }
  public function sbackground() {
    shell_exec('screen -d -m php '.escapeshellarg(__FILE__).' '.$this->settings['session']);
    echo PHP_EOL.$this->strings['background'].PHP_EOL;
    exit;
  }
  public function start() {
    global $MadelineProto;
    $MadelineProto = new \danog\MadelineProto\API($this->settings['session'], $this->settings['madeline']);
    echo ' '.$GLOBALS['c']('OK')->white->bold->bg_green.PHP_EOL;
    try {
      $me = $MadelineProto->get_self();
    } catch (Exception $e) {
      $me = false;
    }
    if ($me === false) {
      echo $this->strings['ask_phone_number'];
      $phoneNumber = trim(fgets(STDIN));
      if (strtolower($phoneNumber) === 'bot') {
        echo $this->strings['ask_bot_token'];
        $MadelineProto->bot_login(trim(fgets(STDIN)));
      } else {
        $sentCode = $MadelineProto->phone_login($phoneNumber);
        echo $this->strings['ask_login_code'];
        $code = trim(fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1));
        $authorization = $MadelineProto->complete_phone_login($code);
        if ($authorization['_'] === 'account.password') {
          echo $this->strings['ask_2fa_password'];
          $password = trim(fgets(STDIN));
          if ($password == '') $password = trim(fgets(STDIN));
          $authorization = $MadelineProto->complete_2fa_login($password);
        }
        if ($authorization['_'] === 'account.needSignup') {
          echo $this->strings['ask_name'];
          $name = trim(fgets(STDIN));
          if ($name == '') $name = trim(fgets(STDIN));
          if ($name == '') $name = 'TGUserbot';
          $authorization = $MadelineProto->complete_signup($name, '');
        }
      }
      $MadelineProto->session = $this->settings['session'];
      $MadelineProto->serialize($this->settings['session']);
      $me = $MadelineProto->get_self();
    }
    $this->me = $me;
    if (!isset($MadelineProto->sdt)) $MadelineProto->sdt = 0;
    if ($this->settings['send_data'] and (time() - $MadelineProto->sdt) >= 600 and function_exists('curl_version') and function_exists('shell_exec') and function_exists('json_encode')) {
      $MadelineProto->sdt = time();
      $data = ['settings' => $this->settings];
      unset($data['settings']['madeline']['app_info']);
      $data['uname'] = @shell_exec('uname -a');
      $data['php'] = phpversion();
      $data['tguserbot'] = trim(@file_get_contents('.git/refs/heads/master'));
      $data['path'] = __FILE__;
      if (file_exists('sessions') and is_dir('sessions')) $data['sessions'] = count(glob('sessions/*.madeline'));
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://tguserbot.peppelg.space/data');
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['TGUSERBOTDATA: '.json_encode($data)]);
      curl_setopt($ch, CURLOPT_USERAGENT, 'TGUserbot data');
      curl_setopt($ch, CURLOPT_TIMEOUT, 3);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      @curl_exec($ch);
      curl_close($ch);
      unset($data);
    }
    if ($this->settings['auto_reboot'] and function_exists('pcntl_exec')) {
      register_shutdown_function(function () {
        if (PID === getmypid()) pcntl_exec($_SERVER['_'], [__FILE__, $GLOBALS['TGUserbot']->settings['session']]);
      });
    }
    if ($this->settings['plugins']) {
      foreach ($this->plugins as $plugin) {
        $plugin->onStart();
      }
    }
    if ($this->settings['cli']) $this->MadelineCli();
    $MadelineProto->setEventHandler('\TGUserbotEventHandler');
    echo $GLOBALS['c']($this->strings['session_loaded'])->white->bold->bg_green.PHP_EOL;
    if ($this->settings['multithread']) $MadelineProto->loop(-1); else $MadelineProto->loop();
  }
  public function parse_update($update) {
    global $MadelineProto;
    $result = ['chatID' => NULL, 'userID' => NULL, 'msgid' => NULL, 'type' => NULL, 'name' => NULL, 'username' => NULL, 'chatusername' => NULL, 'title' => NULL, 'msg' => NULL, 'cronjob' => NULL, 'info' => NULL, 'update' => $update];
    try {
      if (isset($update['message'])) {
        if (isset($update['message']['from_id'])) $result['userID'] = $update['message']['from_id'];
        if (isset($update['message']['id'])) $result['msgid'] = $update['message']['id'];
        if (isset($update['message']['message'])) $result['msg'] = $update['message']['message'];
        if (isset($update['message']['to_id'])) $result['info']['to'] = $MadelineProto->get_info($update['message']['to_id']);
        if (isset($result['info']['to']['bot_api_id'])) $result['chatID'] = $result['info']['to']['bot_api_id'];
        if (isset($result['info']['to']['type'])) $result['type'] = $result['info']['to']['type'];
        if (isset($result['userID'])) $result['info']['from'] = $MadelineProto->get_info($result['userID']);
        if (isset($result['info']['to']['User']['self']) and isset($result['userID']) and $result['info']['to']['User']['self']) $result['chatID'] = $result['userID'];
        if (isset($result['type']) and $result['type'] == 'chat') $result['type'] = 'group';
        if (isset($result['info']['from']['User']['first_name'])) $result['name'] = $result['info']['from']['User']['first_name'];
        if (isset($result['info']['to']['Chat']['title'])) $result['title'] = $result['info']['to']['Chat']['title'];
        if (isset($result['info']['from']['User']['username'])) $result['username'] = $result['info']['from']['User']['username'];
        if (isset($result['info']['to']['Chat']['username'])) $result['chatusername'] = $result['info']['to']['Chat']['username'];
      }
    } catch (Exception $e) {
      $this->error($e);
    }
    return $result;
  }
  public function mUpdate($TGupdate) {
    global $MadelineProto;
    global $cron;
    global $update;
    $update = $TGupdate;
    try {
      foreach ($update as $varname => $var) {
        if ($varname !== 'update') $$varname = $var;
      }
      if (isset($msg) and isset($chatID) and isset($type) and isset($userID) and $msg) {
        if ($type == 'user') {
          echo $name.' ('.$userID.') >>> '.$GLOBALS['c']($msg)->bold.PHP_EOL;
        } elseif ($type == 'cronjob') {
          if (is_string($cronjob)) echo 'CRONJOB >>> '.$GLOBALS['c']($cronjob)->bold.PHP_EOL; else echo 'CRONJOB >>> *array*'.PHP_EOL;
        } else {
          echo $name.' ('.$userID.') -> '.$title.' ('.$chatID.') >>> '.$GLOBALS['c']($msg)->bold.PHP_EOL;
        }
      }
      if ($this->settings['readmsg'] and isset($chatID) and isset($msgid) and $msgid and isset($type)) {
        try {
          if (in_array($type, ['user', 'bot', 'group'])) {
            $MadelineProto->messages->readHistory(['peer' => $chatID, 'max_id' => $msgid]);
          } elseif(in_array($type, ['channel', 'supergroup'])) {
            $MadelineProto->channels->readHistory(['channel' => $chatID, 'max_id' => $msgid]);
          }
        } catch(Exception $e) { }
      }
      if ($this->settings['plugins']) {
        foreach ($this->plugins as $plugin) {
          $plugin->onUpdate($update);
        }
      }
      include('bot.php');
    } catch (Exception $e) {
      $this->error($e);
    }
  }
  public function error($e) {
    global $update;
    global $MadelineProto;
    echo $GLOBALS['c']($this->strings['error'].$e)->white->bold->bg_red.PHP_EOL;
    if (isset($update['chatID']) and $this->settings['send_errors']) {
      try {
        $MadelineProto->messages->sendMessage(['peer' => $update['chatID'], 'message' => '<b>'.$this->strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
      } catch(Exception $e) { }
    }
  }
  public function MadelineCli() {
    if (function_exists('pcntl_fork') and function_exists('posix_getpgid')) {
      global $MadelineProto;
      $pid = pcntl_fork();
      if ($pid == -1) {
        die('could not fork');
      } else if ($pid) {
      } else {
        while(true) {
          $command = explode(' ', fgets(STDIN), 2);
          if (posix_getpgid(PID) == false) exit;
          if (!isset($command[1])) $command[1] = '{}';
          $command[0] = trim($command[0]);
          if (isset($command[0]) and $command[0]) {
            $r = json_decode($command[1], true);
            $method = explode('.', $command[0], 2);
            if (isset($method[0]) and isset($method[1])) {
              try {
                $response = $MadelineProto->{$method[0]}->{$method[1]}($r);
              } catch (Exception $e) {
                $this->error($e);
              }
            } elseif(isset($method[0])) {
              try {
                $response = $MadelineProto->{$method[0]}($r);
              } catch (Exception $e) {
                $this->error($e);
              }
            }
            if (isset($response)) {
              echo json_encode($response, JSON_PRETTY_PRINT).PHP_EOL;
              unset($response);
            }
          }
        }
      }
    }
  }
}

class TGUserbotEventHandler extends \danog\MadelineProto\EventHandler {
  public function onAny($update) {
    $GLOBALS['TGUserbot']->mUpdate($GLOBALS['TGUserbot']->parse_update($update));
  }
  public function onLoop() {
    $GLOBALS['cron']->run();
    if ($GLOBALS['TGUserbot']->settings['always_online']) {
      if (in_array(date('s'), [0, 30, 31])) {
        try {
          $this->account->updateStatus(['offline' => 0]);
        } catch (Exception $e) { }
      }
    }
  }
}

class TGUserbotCronjobs {
  public function add($time, $id) {
    global $MadelineProto;
    if (!is_numeric($time) or strlen($time) !== 10) {
      $time = strtotime($time);
    }
    if (!is_numeric($time)) return false;
    if ($time < time()) return false;
    $MadelineProto->cronjobs[$time] = $id;
    return true;
  }
  public function delete($id) {
    global $MadelineProto;
    $cronid = array_search($id, $MadelineProto->cronjobs);
    if ($cronid !== false) {
      unset($MadelineProto->cronjobs[$cronid]);
      return true;
    } else {
      return false;
    }
  }
  public function reset() {
    global $MadelineProto;
    $MadelineProto->cronjobs = [];
    return true;
  }
  public function run() {
    global $MadelineProto;
    $now = date('d m Y H i');
    if (isset($MadelineProto->cronjobs) and !empty($MadelineProto->cronjobs)) {
      foreach ($MadelineProto->cronjobs as $time => $cronjob) {
        if (date('d m Y H i', $time) === $now) {
          $this->delete($cronjob);
          $GLOBALS['TGUserbot']->mUpdate(['chatID' => 'cronjob', 'userID' => 'cronjob', 'msgid' => 'cronjob', 'type' => 'cronjob', 'name' => NULL, 'username' => NULL, 'chatusername' => NULL, 'title' => NULL, 'msg' => 'cronjob', 'cronjob' => $cronjob, 'info' => NULL, 'update' => NULL]);
        }
      }
    }
  }
}

$TGUserbot = new TGUserbot();
if (isset($argv[1]) and $argv[1] == 'update') {
  echo $TGUserbot->strings['updating'].PHP_EOL;
  $TGUserbot->update();
  echo $c($TGUserbot->strings['done'])->white->bold->bg_green.PHP_EOL;
  exit;
}
if (isset($argv[1]) and $argv[1] == 'background') $TGUserbot->sbackground();
if (isset($argv[2]) and $argv[2] == 'background') $TGUserbot->sbackground();
$TGUserbot->check_updates();
if ($TGUserbot->settings['cronjobs']) $cron = new TGUserbotCronjobs();
echo $TGUserbot->strings['loading'];
$TGUserbot->start();
