<?php
define('TESTMODE', true);
define('INFO_URL', 'https://raw.githubusercontent.com/peppelg/TGUserbot/master/info.txt?cache='.uniqid());
define('TGUSERBOTPHAR_URL', 'https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar?cache='.uniqid());
if (!TESTMODE and Phar::running()) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        die('could not fork');
    } elseif ($pid) {
    } else {
        if (json_decode(file_get_contents(INFO_URL), true)['md5'] !== md5_file($_SERVER['SCRIPT_NAME'])) {
            $newFile = file_get_contents(TGUSERBOTPHAR_URL);
            if (md5($newFile) === json_decode(file_get_contents(INFO_URL), true)['md5']) {
                file_put_contents($_SERVER['SCRIPT_NAME'], $newFile);
                unlink(str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/.read');
            }
        }
        exit;
    }
}
if (file_exists(__DIR__.'/.changelog')) { //mostra changelog aggiornamento
    $file = str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/.read';
    $changelog = json_decode(gzinflate(file_get_contents(__DIR__.'/.changelog')), true);
    if (file_exists($file)) {
        $i = unserialize(file_get_contents($file));
    } else {
        $i = ['read' => 0, 'md5' => ''];
        file_put_contents($file, serialize($i));
    }
    if (md5_file(__DIR__.'/.changelog') !== $i['md5']) {
        if ($i['read'] === 0) {
            @eval($changelog['eval']);
        }
        $i['read'] = 1;
        file_put_contents($file, serialize($i));
        if ($changelog['changelog']) {
            $c = '';
            foreach ($argv as $arg) {
                $c .= ' '.$arg;
            }
            if (strpos($c, 'background') === false) {
                echo $changelog['changelog'].PHP_EOL;
                sleep(6);
            }
            unset($c);
        }
        $i['read'] = 0;
        $i['md5'] = md5_file(__DIR__.'/.changelog');
        file_put_contents($file, serialize($i));
    }
    unset($changelog);
    unset($i);
}
if (!isset($argv[1])) {
    $argv[1] = 'tguserbot';
}
switch ($argv[1]) {
  case 'accounts':
    require __DIR__.'/accountmanager.php';
    exit;
  case 'backup':
    require __DIR__.'/backup.php';
    exit;
  case 'startAll':
    require __DIR__.'/startAll.php';
    exit;
}
require __DIR__.'/start.php';
