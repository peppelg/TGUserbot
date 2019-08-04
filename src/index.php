<?php
require_once __DIR__ . '/TGUserbot.php';
//auto updater
if (!TESTMODE and RUNNING_FROM === 'cli' and Phar::running()) {
    if (json_decode(file_get_contents(INFO_URL), true)['md5'] !== md5_file($_SERVER['SCRIPT_NAME'])) {
        $newFile = file_get_contents(TGUSERBOTPHAR_URL);
        if (md5($newFile) === json_decode(file_get_contents(INFO_URL), true)['md5']) {
            file_put_contents($_SERVER['SCRIPT_NAME'], $newFile);
            unlink(str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/.read');
        }
    }
}
if (RUNNING_FROM === 'cli' and file_exists(__DIR__ . '/.changelog')) { //mostra changelog aggiornamento
    $file = str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/.read';
    $changelog = json_decode(gzinflate(file_get_contents(__DIR__ . '/.changelog')), true);
    if (file_exists($file)) {
        $i = unserialize(file_get_contents($file));
    } else {
        $i = ['read' => 0, 'md5' => ''];
        file_put_contents($file, serialize($i));
    }
    if (md5_file(__DIR__ . '/.changelog') !== $i['md5']) {
        if ($i['read'] === 0) {
            @eval($changelog['eval']);
        }
        $i['read'] = 1;
        file_put_contents($file, serialize($i));
        if ($changelog['changelog']) {
            $c = '';
            foreach ($argv as $arg) {
                $c .= ' ' . $arg;
            }
            if (strpos($c, 'background') === false) {
                echo $changelog['changelog'] . PHP_EOL;
                sleep(6);
            }
            unset($c);
        }
        $i['read'] = 0;
        $i['md5'] = md5_file(__DIR__ . '/.changelog');
        file_put_contents($file, serialize($i));
    }
    unset($changelog);
    unset($i);
}

$TGUserbot = new TGUserbot();
if (RUNNING_FROM === 'cli') {
    $CliArgs = new CliArgs\CliArgs(['session' => 'S', 'background' => 'b', 'kill' => 'k', 'startAll' => 'A', 'killAll' => 'kA', 'forceKillAll' => 'fK']);
    $session = $CliArgs->getArg('session');
    if (!$session) $session = 'session'; //default session

    if ($CliArgs->isFlagExist('background')) {
        $TGUserbot->startInBackground([$session]);
        exit;
    }
    if ($CliArgs->isFlagExist('kill')) {
        $TGUserbot->killSession([$session]);
        exit;
    }
    if ($CliArgs->isFlagExist('startAll')) {
        $TGUserbot->startInBackground($TGUserbot->getSessions());
        exit;
    }
    if ($CliArgs->isFlagExist('killAll')) {
        $TGUserbot->killSession($TGUserbot->getSessions());
        exit;
    }
    if ($CliArgs->isFlagExist('forceKillAll')) {
        shell_exec('screen -ls | awk -vFS=\'\\t|[.]\' \'/TGUserbot/ {system("screen -S "$2" -X quit")}\'');
        echo 'Force killed.' . PHP_EOL;
        exit;
    }

    if (isset($argv[1])) {
        if ($argv[1] === 'accounts') {
            if (RUNNING_WINDOWS) {
                require __DIR__ . '/simpleaccountmanager.php';
            } else {
                require __DIR__ . '/accountmanager.php';
            }
            exit;
        }
        if ($argv[1] === 'data') {
            var_dump($TGUserbot->sendData());
            exit;
        }
    }
    $TGUserbot->start($session);
}
