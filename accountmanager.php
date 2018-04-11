<?php
if (!file_exists('sessions')) mkdir('sessions');
require('settings.php');
$strings = @json_decode(file_get_contents('strings_'.$settings['language'].'.json'), 1);
if (!is_array($strings)) {
  if (!file_exists('strings_it.json')) {
    echo 'downloading strings_it.json...'.PHP_EOL;
    file_put_contents('strings_it.json', file_get_contents('https://raw.githubusercontent.com/peppelg/TGUserbot/master/strings_it.json'));
  }
  $strings = json_decode(file_get_contents('strings_it.json'), 1);
}
$sessions = array_diff(scandir('sessions'), ['.', '..']);
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\CliMenuBuilder;
require_once('vendor/autoload.php');
$menu = new CliMenuBuilder;
$addsession = function (CliMenu $menu) {
  global $strings;
  $sessionName = trim(readline($strings['new_session_name']));
  if ($sessionName != '') {
    $menu->close();
    passthru('php start.php '.'sessions/'.$sessionName.'.madeline');
    exit;
  } else {
    $menu->close();
    require(__FILE__);
  }
};
$startall = function (CliMenu $menu) {
  global $strings;
  global $sessions;
  shell_exec('pkill -f start.php');
  foreach ($sessions as $session) {
    if (substr($session, -9) === '.madeline') {
      shell_exec('php start.php '.'sessions/'.escapeshellarg($session).' background');
      echo $session.' '.$strings['started'].PHP_EOL;
    }
  }
  $menu->flash($strings['done'])
    ->display();
};
$stopall = function (CliMenu $menu) {
  global $strings;
  global $sessions;
  shell_exec('pkill -f start.php');
  $menu->flash($strings['done'])
    ->display();
};
$menu->setTitle('TGUserbot account manager')
  ->addItem($strings['add_account'], $addsession)
  ->addItem($strings['start_all'], $startall)
  ->addItem($strings['stop_all'], $stopall)
  ->addLineBreak(' ');
  foreach ($sessions as $sessionN => $session) {
    if (substr($session, -9) === '.madeline') {
      $sname = str_replace('.madeline', '', $session);
      $menu->addSubMenu($sname)
      ->setTitle($strings['sessions'].' > '.$session)
      ->addLineBreak(' ')
      ->addItem($strings['start'].' ['.$sessionN.']', function (CliMenu $menu) {
          global $sessions;
          $session = 'sessions/'.$sessions[filter_var($menu->getSelectedItem()->getText(), FILTER_SANITIZE_NUMBER_INT)];
          $menu->close();
          if (function_exists('pcntl_exec')) {
            pcntl_exec($_SERVER['_'], [__DIR__.'/start.php', $session]);
          } else {
            passthru('php start.php '.escapeshellarg($session));
          }
          exit;
      })
      ->addItem($strings['start_background'].' ['.$sessionN.']', function (CliMenu $menu) {
          global $strings;
          global $sessions;
          $session = 'sessions/'.$sessions[filter_var($menu->getSelectedItem()->getText(), FILTER_SANITIZE_NUMBER_INT)];
          shell_exec('php start.php '.escapeshellarg($session).' background');
          $menu->flash($strings['started'])
          ->display();
          $menu->close();
          include(__FILE__);
      })
      ->addItem($strings['delete_session'].' ['.$sessionN.']', function (CliMenu $menu) {
          global $strings;
          global $sessions;
          $session = 'sessions/'.$sessions[filter_var($menu->getSelectedItem()->getText(), FILTER_SANITIZE_NUMBER_INT)];
          @unlink($session);
          @unlink($session.'.lock');
          $menu->flash($strings['session_deleted'])
          ->display();
          $menu->close();
          include(__FILE__);
          exit;
      })
      ->addLineBreak(' ')
      ->setGoBackButtonText($strings['go_back'])
      ->setExitButtonText($strings['exit'])
      ->end();
    }
  }
  $menu->addLineBreak(' ')
  ->setExitButtonText($strings['exit'])
  ->build()
  ->open();
