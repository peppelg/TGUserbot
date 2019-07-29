<?php
echo 'TGUserbot Account manager' . PHP_EOL . str_repeat('=', 25) . PHP_EOL; //x windows
$start = function () use ($TGUserbot, &$start) {
    $sessions = $TGUserbot->getSessions();
    array_unshift($sessions, '');
    unset($sessions[0]); //array starts at 1
    echo '0. ' . $TGUserbot->strings['add_account'] . PHP_EOL;
    foreach ($sessions as $id => $session) {
        echo $id . '. ' . $session . PHP_EOL;
    }
    echo count($sessions) + 1 . '. ' . $TGUserbot->strings['exit'] . PHP_EOL;
    while (true) {
        $selected_id = (int) readline('> ');
        if ($selected_id === 0) {
            $newAccount = readline($TGUserbot->strings['enter_session_name']);
            if ($newAccount) {
                $TGUserbot->start($newAccount);
                unset($start);
                exit;
            }
        }
        if ($selected_id === count($sessions) + 1) {
            exit;
        }
        if (isset($sessions[$selected_id])) {
            $title = $TGUserbot->strings['sessions'] . ' > ' . $session . '.madeline';
            echo $title . PHP_EOL . str_repeat('=', strlen($title)) . PHP_EOL;
            echo '1. ' . $TGUserbot->strings['start_session'] . PHP_EOL . '2. ' . $TGUserbot->strings['rename_session'] . PHP_EOL . '3. ' . $TGUserbot->strings['delete_session'] . PHP_EOL . '4. ' . $TGUserbot->strings['go_back'] . PHP_EOL;
            while (true) {
                $select = (int) readline('> ');
                if (in_array($select, [1, 2, 3, 4])) {
                    if ($select === 1) {
                        unset($start);
                        $TGUserbot->start($sessions[$selected_id]);
                        exit;
                    }
                    if ($select === 2) {
                        $newName = readline($TGUserbot->strings['enter_session_name']);
                        if ($newName) {
                            rename(DIR . 'sessions/' . $sessions[$selected_id] . '.madeline', DIR . 'sessions/' . $newName . '.madeline');
                            @unlink(DIR . 'sessions/' . $sessions[$selected_id] . '.madeline.lock');
                            echo $TGUserbot->strings['done'] . PHP_EOL . PHP_EOL;
                            $start();
                            exit;
                        }
                    }
                    if ($select === 3) {
                        if (readline($TGUserbot->strings['confirm_delete']) === $sessions[$selected_id]) {
                            unlink(DIR . 'sessions/' . $sessions[$selected_id] . '.madeline');
                            @unlink(DIR . 'sessions/' . $sessions[$selected_id] . '.madeline.lock');
                            echo $TGUserbot->strings['done'] . PHP_EOL . PHP_EOL;
                            $start();
                            exit;
                        }
                    }
                    if ($select === 4) {
                        echo PHP_EOL . PHP_EOL;
                        $start();
                        exit;
                    }
                }
            }
            break;
        }
    }
};

$start();
