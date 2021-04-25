<?php
$data = [];
$error = function ($e, $chatID = NULL) use (&$MadelineProto) {
    $this->log($e, [], 'error');
    if (isset($chatID) and $this->settings['send_errors']) {
        try {
            $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>' . $this->strings['error'] . '</b><code>' . $e->getMessage() . '</code>', 'parse_mode' => 'HTML']);
        } catch (\Throwable $e) { }
    }
};

$parseUpdate = function ($update) use (&$MadelineProto, &$error) {
    $result = ['chatID' => null, 'userID' => null, 'msgid' => null, 'type' => null, 'name' => null, 'username' => null, 'chatusername' => null, 'title' => null, 'msg' => null, 'info' => null, 'update' => $update];
    try {
        if (isset($update['message'])) {
            if (isset($update['message']['from_id'])) {
                $result['userID'] = yield $MadelineProto->getId($update['message']['from_id']);
            }
            if (isset($update['message']['id'])) {
                $result['msgid'] = $update['message']['id'];
            }
            if (isset($update['message']['message'])) {
                $result['msg'] = $update['message']['message'];
            }
            if (isset($update['message']['to_id'])) {
                $result['info']['to'] = yield $MadelineProto->getInfo($update['message']['to_id']);
            }
            if (isset($result['info']['to']['bot_api_id'])) {
                $result['chatID'] = $result['info']['to']['bot_api_id'];
            }
            if (isset($result['info']['to']['type'])) {
                $result['type'] = $result['info']['to']['type'];
            }
            if (isset($result['userID'])) {
                $result['info']['from'] = yield $MadelineProto->getInfo($result['userID']);
            }
            if (isset($result['info']['to']['User']['self']) and isset($result['userID']) and $result['info']['to']['User']['self']) {
                $result['chatID'] = $result['userID'];
            }
            if (isset($result['type']) and $result['type'] == 'chat') {
                $result['type'] = 'group';
            }
            if (isset($result['info']['from']['User']['first_name'])) {
                $result['name'] = $result['info']['from']['User']['first_name'];
            }
            if (isset($result['info']['to']['Chat']['title'])) {
                $result['title'] = $result['info']['to']['Chat']['title'];
            }
            if (isset($result['info']['from']['User']['username'])) {
                $result['username'] = $result['info']['from']['User']['username'];
            }
            if (isset($result['info']['to']['Chat']['username'])) {
                $result['chatusername'] = $result['info']['to']['Chat']['username'];
            }
        }
    } catch (\Throwable $e) {
        $error($e);
    }
    return $result;
};

$printUpdate = function ($update) use (&$data) {
    if ($update['type'] === 'group' or $update['type'] === 'supergroup') {
        $this->log(RUNNING_FROM . '_template_group', [$update['name'], $update['userID'], $update['title'], $update['chatID'], $update['msg']]);
        $data['replyChatId'] = $update['chatID'];
    } elseif ($update['type'] === 'channel') {
        $this->log(RUNNING_FROM . '_template_user', [$update['title'], $update['chatID'], $update['msg']]);
    } else {
        $this->log(RUNNING_FROM . '_template_user', [$update['name'], $update['userID'], $update['msg']]);
        $data['replyChatId'] = $update['chatID'];
    }
};

$schedule = function ($time, $function) use (&$MadelineProto) {
    if (!is_numeric($time) or strlen($time) !== 10) {
        $time = strtotime($time);
    }
    if (!is_numeric($time)) {
        return false;
    }
    if ($time < time()) {
        return false;
    }
    if (!is_callable($function)) {
        return false;
    }
    $sleep = $time - time();
    yield $MadelineProto->sleep($sleep);
    yield $function();
};

$callback = function ($update) use (&$MadelineProto, &$error, &$parseUpdate, &$bot, &$printUpdate) {
    $u = yield $parseUpdate($update);
    if ($u['msg']) $printUpdate($u);
    if ($this->settings['readmsg'] and isset($u['chatID']) and isset($u['msgid'])) {
        try {
            if (in_array($u['type'], ['user', 'bot', 'group'])) {
                yield $MadelineProto->messages->readHistory(['peer' => $u['chatID'], 'max_id' => $u['msgid']]);
            } elseif (in_array($u['type'], ['channel', 'supergroup'])) {
                yield $MadelineProto->channels->readHistory(['channel' => $u['chatID'], 'max_id' => $u['msgid']]);
            }
        } catch (\Throwable $e) { }
    }
    if (isset($bot)) {
        try {
            yield $bot($u);
        } catch (\Throwable $e) {
            $error($e, $u['chatID']);
        }
    }
};

$onLoop = function ($watcherId) use (&$MadelineProto, &$error) {
    try {
        if ($this->settings['always_online'] and in_array(date('s'), [00, 30])) {
            yield $MadelineProto->account->updateStatus(['offline' => 0]);
        }
    } catch (\Throwable $e) {
        $error($e);
    }
    if (RUNNING_FROM === 'web') {
        file_put_contents(DIR . 'status', 'started');
        if (file_get_contents(DIR . 'a_status') === 'stop') {
            file_put_contents(DIR . 'status', 'stopped');
            exit;
        }
    }
};

$include = function($file, $variables) {
    if (!is_array($variables)) return false;
    $file = '$includeRun = function ($variables) { foreach ($variables as $key => $value) { $$key = $value; }' . str_replace('<?php', '', file_get_contents($file)) . '};';
    eval($file);
    return yield $includeRun($variables);
};

$MadelineCli = function () use (&$MadelineProto, &$error, &$MadelineCli, &$data) {
    $res = yield $MadelineProto->readLine();
    if ($res) {
        $command = explode(' ', $res, 2);
        if ($command[0] === '.r' and isset($command[1]) and isset($data['replyChatId'])) {
            try {
                $response = yield $MadelineProto->messages->sendMessage(['peer' => $data['replyChatId'], 'message' => $command[1], 'parse_mode' => 'HTML']);
                echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
            } catch (\Throwable $e) {
                $error($e);
            }
        } else {
            if (!isset($command[1])) {
                $command[1] = '{}';
            }
            $command[0] = trim($command[0]);
            if (isset($command[0]) and $command[0]) {
                $r = json_decode($command[1], true);
                $method = explode('.', $command[0], 2);
                if (isset($method[0]) and isset($method[1])) {
                    try {
                        $response = yield $MadelineProto->{$method[0]}->{$method[1]}($r);
                    } catch (\Throwable $e) {
                        $error($e);
                    }
                } elseif (isset($method[0])) {
                    try {
                        $response = yield $MadelineProto->{$method[0]}($r);
                    } catch (\Throwable $e) {
                        $error($e);
                    }
                }
                if (isset($response)) {
                    echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
                    unset($response);
                }
            }
        }
        yield $MadelineCli();
    }
};
