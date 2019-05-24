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
foreach ($sessions as $session) {
    if (substr($session, -9) === '.madeline') {
        shell_exec(PHP_BINARY.' '.escapeshellarg(DIR.$_SERVER['SCRIPT_NAME']).' --background --session='.escapeshellarg(str_replace('.madeline', '', $session)));
        echo $session.' ✅'.PHP_EOL;
    }
}
