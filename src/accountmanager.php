<?php

use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\Action\ExitAction;
use PhpSchool\CliMenu\Action\GoBackAction;

$sessions = $TGUserbot->getSessions();
$start = function ($session, $mode = NULL) use (&$TGUserbot) {
    if ($mode === 'background') {
        $TGUserbot->startInBackground([$session]);
    } else {
        $TGUserbot->start($session);
    }
};

$addNew = function (CliMenu $menu) use ($TGUserbot, $start, $sessions) {
    $result = $menu->askText()
        ->setPromptText($TGUserbot->strings['enter_session_name'])
        ->setValidationFailedText($TGUserbot->strings['enter_session_name'])
        ->ask();
    $session = $result->fetch();
    $menu->close();
    $start($session);
    exit;
};
$startAll = function (CliMenu $menu) use ($TGUserbot, $sessions) {
    $TGUserbot->startInBackground($sessions);
    $flash = $menu->flash($TGUserbot->strings['done']);
    $flash->display();
};
$stopAll = function (CliMenu $menu) use ($TGUserbot, $sessions) { //force stop
    shell_exec('screen -ls | awk -vFS=\'\\t|[.]\' \'/TGUserbot/ {system("screen -S "$2" -X quit")}\'');
    $flash = $menu->flash($TGUserbot->strings['done']);
    $flash->display();
};

$sessionMenu = function (CliMenu $menu) use ($TGUserbot) {
    echo $menu->getSelectedItem()->getText();
    $flash = $menu->flash($TGUserbot->strings['done']);
    $flash->display();
};

$menu = (new CliMenuBuilder)
    ->setTitle('TGUserbot account manager')
    ->disableDefaultItems()
    ->addItem($TGUserbot->strings['add_account'], $addNew);
if (SCREEN_SUPPORT) {
    $menu = $menu->addItem($TGUserbot->strings['start_all'], $startAll)
        ->addItem($TGUserbot->strings['stop_all'], $stopAll);
}
$menu = $menu->addLineBreak(' ');
foreach ($sessions as $session) {
    $menu = $menu->addSubMenu($session, function (CliMenuBuilder $b) use ($session, $TGUserbot, $start) {
        $b->setTitle($TGUserbot->strings['sessions'] . ' > ' . $session . '.madeline')
        ->disableDefaultItems()
            ->addItem($TGUserbot->strings['start_session'], function (CliMenu $menu) use ($session, $TGUserbot, $start) {
                $menu->close();
                $start($session);
            });
        if (SCREEN_SUPPORT) {
            $b = $b->addItem($TGUserbot->strings['start_session_background'], function (CliMenu $menu) use ($session, $TGUserbot, $start) {
                $start($session, 'background');
                $flash = $menu->flash($TGUserbot->strings['done']);
                $flash->display();
            })
                ->addItem($TGUserbot->strings['stop_session'], function (CliMenu $menu) use ($session, $TGUserbot) {
                    $TGUserbot->killSession([$session]);
                    $flash = $menu->flash($TGUserbot->strings['done']);
                    $flash->display();
                });
        }
        $b = $b->addItem($TGUserbot->strings['rename_session'], function (CliMenu $menu) use ($session, $TGUserbot) {
            $result = $menu->askText()
                ->setPromptText($TGUserbot->strings['enter_session_name'])
                ->setValidationFailedText($TGUserbot->strings['enter_session_name'])
                ->ask();
            $result = $result->fetch();
            if ($result) {
                $TGUserbot->killSession([$session]);
                rename(DIR . 'sessions/' . $session . '.madeline', DIR . 'sessions/' . $result . '.madeline');
                @unlink(DIR . 'sessions/' . $session . '.madeline.lock');
                $flash = $menu->flash($TGUserbot->strings['done']);
                $flash->display();
                $menu->close();
                require __FILE__;
            }
        })
        ->addItem($TGUserbot->strings['delete_session'], function (CliMenu $menu) use ($session, $TGUserbot) {
            $result = $menu->askText()
                ->setPromptText($TGUserbot->strings['confirm_delete'])
                ->setValidationFailedText($TGUserbot->strings['confirm_delete'])
                ->ask();
            if ($result->fetch() === $session) {
                $TGUserbot->killSession([$session]);
                unlink(DIR . 'sessions/' . $session . '.madeline');
                @unlink(DIR . 'sessions/' . $session . '.madeline.lock');
                $flash = $menu->flash($TGUserbot->strings['done']);
                $flash->display();
                $menu->close();
                require __FILE__;
            }
        })
        ->addLineBreak(' ')
        ->addItem($TGUserbot->strings['go_back'], new GoBackAction);
    });
}
$menu = $menu->addLineBreak(' ')
    ->setBorder(1, 2, 'yellow')
    ->setPadding(2, 4)
    ->setMarginAuto()
    ->addItem($TGUserbot->strings['exit'], new ExitAction)
    ->build();
$menu->open();
//->setGoBackButtonText($TGUserbot->strings['go_back']); + togliere exit submenu
