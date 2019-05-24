<?php
if (!Phar::running()) {
    define('DIR', __DIR__ . '/');
} else {
    define('DIR', str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/');
}
if ($_SERVER['SCRIPT_NAME'] === 'accountmanager.php') {
    $_SERVER['SCRIPT_NAME'] = 'start.php';
}
$settings = json_decode(file_get_contents(DIR . 'settings.json'), true);
if (file_exists(__DIR__ . '/strings_' . $settings['language'] . '.json')) {
    $strings = json_decode(file_get_contents(__DIR__ . '/strings_' . $settings['language'] . '.json'), true);
} else {
    $strings = json_decode(file_get_contents(__DIR__ . '/strings_it.json'), true);
}
$sessions = array_diff(scandir(DIR.'sessions'), ['.', '..']);
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\CliMenuBuilder;

require_once 'vendor/autoload.php';
$menu = new CliMenuBuilder;
$addsession = function (CliMenu $menu) {
    global $strings;
    $sessionName = trim(readline($strings['new_session_name']));
    if ($sessionName != '') {
        $menu->close();
        passthru(PHP_BINARY.' '.escapeshellarg(DIR.$_SERVER['SCRIPT_NAME']).' --session='.escapeshellarg($sessionName));
        exit;
    } else {
        $menu->close();
        require(__FILE__);
    }
};
$startall = function (CliMenu $menu) {
    global $strings;
    global $sessions;
    //shell_exec('pkill -f '.escapeshellarg($_SERVER['SCRIPT_NAME']));
    foreach ($sessions as $session) {
        if (substr($session, -9) === '.madeline') {
            shell_exec(PHP_BINARY.' '.escapeshellarg(DIR.$_SERVER['SCRIPT_NAME']).' --background --session='.escapeshellarg(str_replace('.madeline', '', $session)));
            echo $session.' '.strtolower($strings['started']).PHP_EOL;
        }
    }
    $menu->flash($strings['done'])
    ->display();
};
$stopall = function (CliMenu $menu) {
    global $strings;
    global $sessions;
    shell_exec('pkill -f '.escapeshellarg($_SERVER['SCRIPT_NAME']));
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
          $session = str_replace('.madeline', '', $sessions[filter_var($menu->getSelectedItem()->getText(), FILTER_SANITIZE_NUMBER_INT)]);
          $menu->close();
          passthru(PHP_BINARY.' '.escapeshellarg(DIR.$_SERVER['SCRIPT_NAME']).' --session='.escapeshellarg($session));
          exit;
      })
      ->addItem($strings['start_background'].' ['.$sessionN.']', function (CliMenu $menu) {
          global $strings;
          global $sessions;
          $session = str_replace('.madeline', '', $sessions[filter_var($menu->getSelectedItem()->getText(), FILTER_SANITIZE_NUMBER_INT)]);
          shell_exec(PHP_BINARY.' '.escapeshellarg(DIR.$_SERVER['SCRIPT_NAME']).' --background --session='.escapeshellarg($session));
          $menu->flash($strings['started'])
          ->display();
          $menu->close();
          include(__FILE__);
      })
      ->addItem($strings['delete_session'].' ['.$sessionN.']', function (CliMenu $menu) {
          global $strings;
          global $sessions;
          $session = DIR.'sessions/'.$sessions[filter_var($menu->getSelectedItem()->getText(), FILTER_SANITIZE_NUMBER_INT)];
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
