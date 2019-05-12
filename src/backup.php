<?php
if (!Phar::running()) {
    define('DIR', __DIR__ . '/');
} else {
    define('DIR', str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/');
}
$settings = json_decode(file_get_contents(DIR . 'settings.json'), true);
if (file_exists(__DIR__ . '/strings_' . $settings['language'] . '.json')) {
    $strings = json_decode(file_get_contents(__DIR__ . '/strings_' . $settings['language'] . '.json'), true);
} else {
    $strings = json_decode(file_get_contents(__DIR__ . '/strings_it.json'), true);
}
require_once __DIR__.'/vendor/autoload.php';
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
function backup($sessions, $backupFile, $madelineSettings) {
    if (empty($sessions)) {
        die('No session.'.PHP_EOL);
    }
    $zip = new ZipArchive();
    $zip->open(DIR.$backupFile.'.tgs', ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
    echo PHP_EOL;
    foreach ($sessions as $session) {
        try {
            $Sname = pathinfo($session, PATHINFO_FILENAME);
            $tgs = ['sockets' => []];
            $MadelineProto = new \danog\MadelineProto\API($session, $madelineSettings);
            if (!$MadelineProto->API->authorized) {
                throw new Exception('not logged');
            }
            $tgs['test_mode'] = $MadelineProto->settings['connection_settings']['all']['test_mode'];
            foreach ($MadelineProto->API->datacenter->sockets as $dc_id => $socket) {
                $tgs['sockets'][$dc_id] = ['auth_key' => $socket->auth_key];
            }
            unset($MadelineProto);
            $zip->addFromString($Sname, gzdeflate(serialize($tgs), 9));
            unset($tgs);
            echo PHP_EOL.$Sname.' ✅';
        } catch (Exception $e) {
            echo PHP_EOL.$Sname.' ❌';
        }
    }
    $zip->close();
    unset($zip);
    $file = base64_encode(file_get_contents(DIR.$backupFile.'.tgs'));
    unlink(DIR.$backupFile.'.tgs');
    file_put_contents(DIR.$backupFile.'.tgs', gzdeflate(json_encode(['hash' => md5($file), 'file' => $file]), 9));
    echo PHP_EOL.PHP_EOL;
}
$climate = new League\CLImate\CLImate;


$response = $climate->input($strings['backup_welcome'].PHP_EOL.'> ')->accept(['1', '2', '3'])->prompt();
if ($response === '3') {
    exit;
}

if ($response === '1') {
    $response = $climate->input(PHP_EOL.$strings['new_backup'].PHP_EOL.'> ')->accept(['1', '2', '3'])->prompt();
    if ($response === '3') {
        exit;
    }
    echo PHP_EOL;
    while (true) {
        $backupFile = $climate->input($strings['new_backup_name'].PHP_EOL.'> ')->prompt();
        if ($backupFile) {
            break;
        }
    }
    if ($response === '1') {
        echo $strings['backup_started'];
        backup(glob(DIR.'sessions/*.madeline'), $backupFile, $settings['madeline']);
        echo $strings['done'].PHP_EOL;
        exit;
    }
    if ($response === '2') {
        $sessions = [];
        foreach (glob(DIR.'sessions/*.madeline') as $session) {
            $sessions[$session] = pathinfo($session, PATHINFO_FILENAME);
        }
        $response = $climate->checkboxes(PHP_EOL.$strings['select_sessions'], $sessions)->prompt();
        echo $strings['backup_started'];
        backup($response, $backupFile, $settings['madeline']);
        echo $strings['done'].PHP_EOL;
        exit;
    }
}

if ($response === '2') {
    $files = glob(DIR.'*.tgs');
    $table = [0 => ['ID', 'File']];
    if (empty($files)) {
        die($strings['no_backup'].PHP_EOL);
    }
    echo $strings['select_backup'].PHP_EOL;
    foreach ($files as $key => $file) {
        $bName = pathinfo($file, PATHINFO_FILENAME);
        array_push($table, [$key, $bName]);
    }
    $climate->table($table);
    $response = $climate->input(PHP_EOL.'ID > ')->prompt();
    if (!isset($files[$response])) {
        die($strings['backup_not_found'].PHP_EOL);
    }
    $file = $json = json_decode(gzinflate(file_get_contents($files[$response])), true);
    if (!is_array($file)) {
        die($strings['invalid_backup'].PHP_EOL);
    }
    if (md5($file['file']) !== $file['hash']) {
        die($strings['invalid_backup'].PHP_EOL);
    }
    file_put_contents($files[$response].'.temp', base64_decode($file['file']));
    unset($file);
    $zip = new ZipArchive();
    $zip->open($files[$response].'.temp');
    $sessions = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        array_push($sessions, $filename);
    }
    if (empty($sessions)) {
        $zip->close();
        unlink($files[$response].'.temp');
        die($strings['invalid_backup'].PHP_EOL);
    }
    $end = end($sessions);
    echo $strings['backup_sessions'];
    foreach ($sessions as $session) {
        echo $session;
        if ($session !== $end) {
            echo ', ';
        }
    }
    echo PHP_EOL;
    $confirm = $climate->input($strings['backup_restore_confirm'])->accept(['Y', 'n'], true)->prompt();
    if (strtolower($confirm) === 'y') {
        foreach ($sessions as $session) {
            try {
                $tgs = unserialize(gzinflate($zip->getFromName($session)));
                if (!is_array($tgs)) {
                    throw new Exception('invalid session');
                }
                $MadelineProto = new \danog\MadelineProto\API(DIR.'sessions/'.$session.'.madeline', $settings['madeline']);
                $MadelineProto->settings['connection_settings']['all']['test_mode'] = $tgs['test_mode'];
                foreach ($tgs['sockets'] as $dc_id => $socket) {
                    $MadelineProto->API->datacenter->sockets[$dc_id]->auth_key = $socket['auth_key'];
                    $MadelineProto->API->datacenter->sockets[$dc_id]->temp_auth_key = null;
                    $MadelineProto->API->datacenter->sockets[$dc_id]->authorized = true;
                    $MadelineProto->API->datacenter->sockets[$dc_id]->session_id = $MadelineProto->random(8);
                    $MadelineProto->API->datacenter->sockets[$dc_id]->session_in_seq_no = 0;
                    $MadelineProto->API->datacenter->sockets[$dc_id]->session_out_seq_no = 0;
                    $MadelineProto->API->datacenter->sockets[$dc_id]->incoming_messages = [];
                    $MadelineProto->API->datacenter->sockets[$dc_id]->outgoing_messages = [];
                    $MadelineProto->API->datacenter->sockets[$dc_id]->new_outgoing = [];
                    $MadelineProto->API->datacenter->sockets[$dc_id]->incoming = [];
                    $MadelineProto->API->authorized = danog\MadelineProto\MTProto::LOGGED_IN;
                    $MadelineProto->API->init_authorization();
                }
                echo PHP_EOL.$session.' ✅';
            } catch (Exception $e) {
                echo PHP_EOL.$session.' ❌';
            }
        }
        unlink($files[$response].'.temp');
        echo PHP_EOL.PHP_EOL.$strings['done'].PHP_EOL;
    } else {
        unlink($files[$response].'.temp');
        exit;
    }
}
