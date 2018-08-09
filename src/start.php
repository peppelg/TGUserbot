<?php
define('TGUSERBOT_VERSION', '4.0');
define('PID', getmypid());
if (!Phar::running()) {
    define('DIR', __DIR__ . '/');
} else {
    define('DIR', str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/');
}

foreach (['pcntl_exec', 'pcntl_fork', 'posix_getpgid', 'curl_init', 'file_get_contents', 'json_encode'] as $function) {
    if (!function_exists($function)) {
        $x_X = true;
        echo 'Please install ' . $function . '.' . PHP_EOL;
    }
}
if (isset($x_X)) {
    exit;
}

if (!file_exists(DIR . 'sessions')) {
    mkdir(DIR . 'sessions');
}
if (file_exists(DIR . 'functions.php')) {
    include_once DIR .'functions.php';
}
$data = [];
$MadelineProto = null;
$update = null;
$c = null;

class TGUserbot
{
    private $settings = [];
    public function __construct()
    {
        global $MadelineProto;
        global $update;
        global $c;
        require_once __DIR__ . '/vendor/autoload.php';
        $c = new Colors\Color();
        $settings_default = ['bot_file' => 'bot.php', 'language' => 'it', 'readmsg' => true, 'cronjobs' => true, 'send_errors' => true, 'always_online' => false, 'auto_reboot' => true, 'multithread' => false, 'send_data' => true, 'cli' => true, 'proxy' => [], 'madeline' => ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e', 'lang_code' => 'it', 'app_version' => '4.9.0', 'device_model' => 'Asus ASUS_Z00ED', 'system_version' => 'Android Nougat MR1 (25)'], 'logger' => ['logger' => 0], 'updates' => ['handle_old_updates' => false], 'secret_chats' => ['accept_chats' => false]]];
        if (!file_exists(DIR . 'settings.json')) {
            file_put_contents(DIR . 'settings.json', json_encode($settings_default, JSON_PRETTY_PRINT));
        }
        $this->settings = array_merge($settings_default, json_decode(file_get_contents(DIR . 'settings.json'), true));
        $this->settings['madeline']['app_info']['lang_code'] = $this->settings['language'];
        if (!file_exists(DIR.$this->settings['bot_file'])) {
            $this->settings['bot_file'] = false;
        }
        if (file_exists(__DIR__ . '/strings_' . $this->settings['language'] . '.json')) {
            $this->strings = json_decode(file_get_contents(__DIR__ . '/strings_' . $this->settings['language'] . '.json'), true);
        } else {
            $this->strings = json_decode(file_get_contents(__DIR__ . '/strings_it.json'), true);
        }
        $CliArgs = new CliArgs\CliArgs(['session' => 'S', 'background' => 'b', 'help' => 'h']);
        $args = $CliArgs->getArgs();
        if (isset($args['S'])) {
            $args['session'] = $args['S'];
        }
        if (array_key_exists('b', $args)) {
            $args['background'] = $args['b'];
        }
        if (isset($args['h'])) {
            $args['help'] = $args['h'];
        }
        if (!isset($args['session'])) {
            $args['session'] = 'default';
        }
        $this->settings['session'] = DIR . 'sessions/' . $args['session'] . '.madeline';
        if (array_key_exists('background', $args)) {
            shell_exec('screen -d -m ' . $_SERVER['_'] . ' ' . escapeshellarg($_SERVER['SCRIPT_NAME']) . ' --session=' . escapeshellarg($args['session']));
            echo $this->strings['background'] . PHP_EOL;
            exit;
        }
        unset($args);
        unset($CliArgs);
        echo $this->strings['loading'] . PHP_EOL;
        if (!file_exists(DIR . 'madeline.phar')) {
            $this->downloadMadelineProto();
        }
        require_once DIR . 'madeline.phar';
        if (is_string($this->settings['proxy']) and $this->settings['proxy'] === 'auto') {
            $proxy = $this->getProxy();
            if (isset($proxy['ip']) and isset($proxy['port']) and isset($proxy['type'])) {
                $this->settings['proxy'] = $proxy;
            }
            unset($proxy);
        }
        if (isset($this->settings['proxy']['type']) and isset($this->settings['proxy']['ip']) and isset($this->settings['proxy']['port'])) {
            $proxy = [];
            $proxy['address'] = $this->settings['proxy']['ip'];
            $proxy['port'] = $this->settings['proxy']['port'];
            if (isset($this->settings['proxy']['username'])) {
                $proxy['username'] = $this->settings['proxy']['username'];
            }
            if (isset($this->settings['proxy']['password'])) {
                $proxy['password'] = $this->settings['proxy']['password'];
            }
            if ($this->settings['proxy']['type'] === 'socks5') {
                $this->settings['madeline']['connection_settings']['all']['proxy'] = '\SocksProxy';
                $this->settings['madeline']['connection_settings']['all']['proxy_extra'] = $proxy;
            } elseif ($this->settings['proxy']['type'] === 'http') {
                $this->settings['madeline']['connection_settings']['all']['proxy'] = '\HttpProxy';
                $this->settings['madeline']['connection_settings']['all']['proxy_extra'] = $proxy;
            }
            echo 'Proxy: ' . $this->settings['proxy']['ip'] . ':' . $this->settings['proxy']['port'] . ' (' . $this->settings['proxy']['type'] . ')' . PHP_EOL;
        }
        if ($this->settings['auto_reboot']) {
            register_shutdown_function(function () {
                if (PID === getmypid()) {
                    pcntl_exec($_SERVER['_'], $GLOBALS['argv']);
                }
            });
        }
        $MadelineProto = new \danog\MadelineProto\API($this->settings['session'], $this->settings['madeline']);
        try {
            $MadelineProto->help->acceptTermsOfService(['id' => $MadelineProto->help->getTermsOfServiceUpdate() ['terms_of_service']['id']]);
        } catch (Exception $e) {
        }
        try {
            $update['me'] = $MadelineProto->get_self();
        } catch (Exception $e) {
            $update['me'] = false;
        }
        if (!$update['me']) {
            $this->login();
        }
        if (!isset($MadelineProto->sdt)) {
            $MadelineProto->sdt = 0;
        }
        if ($this->settings['send_data'] and (time() - $MadelineProto->sdt) >= 600 and function_exists('curl_version') and function_exists('shell_exec') and function_exists('json_encode')) {
            $MadelineProto->sdt = time();
            $data = ['settings' => $this->settings];
            unset($data['settings']['madeline']['app_info']);
            $data['uname'] = @shell_exec('uname -a');
            $data['php'] = phpversion();
            $data['tguserbot'] = TGUSERBOT_VERSION;
            $data['path'] = __FILE__;
            if (file_exists(DIR . 'sessions') and is_dir(DIR . 'sessions')) {
                $data['sessions'] = count(glob(DIR . 'sessions/*.madeline'));
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://tguserbot.peppelg.space/data');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['TGUSERBOTDATA: ' . json_encode($data) ]);
            curl_setopt($ch, CURLOPT_USERAGENT, 'TGUserbot data');
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_exec($ch);
            curl_close($ch);
            unset($data);
        }
        if (file_exists(DIR . 'plugins') and is_dir(DIR . 'plugins')) {
            echo $this->strings['loading_plugins'];
            $pluginN = $this->loadPlugins(DIR . 'plugins');
            echo ' ' . $c('OK: ' . $pluginN . ' ' . $this->strings['plugins_loaded'])->white->bold->bg_green . PHP_EOL;
        } else {
            $this->settings['plugins'] = false;
        }
        if ($this->settings['plugins']) {
            foreach ($this->plugins as $plugin) {
                $plugin->onStart();
            }
        }
        if ($this->settings['cli']) {
            $this->MadelineCli();
        }
        echo $c($this->strings['session_loaded'])->white->bold->bg_green . PHP_EOL;
        $this->start();
    }
    private function downloadMadelineproto()
    {
        global $data;
        $data['strings_downloading_madelineproto'] = $this->strings['downloading_madelineproto'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://raw.githubusercontent.com/danog/MadelineProtoPhar/master/madeline.phar');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) {
            global $data;
            if (!isset($data['madeline_download_percentage'])) {
                $data['madeline_download_percentage'] = 0;
            }
            if ($download_size > 0) {
                $percentage = round($downloaded / $download_size * 100, 1, PHP_ROUND_HALF_UP);
                if ($percentage > $data['madeline_download_percentage']) {
                    $data['madeline_download_percentage'] = $percentage;
                    echo $data['strings_downloading_madelineproto'] . '  ' . $percentage . "%     \r";
                }
            }
        });
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $phar = curl_exec($ch);
        curl_close($ch);
        unset($data['madeline_download_percentage']);
        unset($data['strings_downloading_madelineproto']);
        file_put_contents('madeline.phar', $phar);
        echo $this->strings['done'] . '                                        ' . PHP_EOL;
        unset($phar);
        return true;
    }
    private function getProxy()
    {
        $proxy = json_decode(@file_get_contents('https://api.getproxylist.com/proxy?protocol=socks5'), true);
        if (is_array($proxy) and isset($proxy['ip']) and isset($proxy['port']) and isset($proxy['protocol']) and $proxy['protocol'] === 'socks5') {
            return ['type' => 'socks5', 'ip' => $proxy['ip'], 'port' => $proxy['port']];
        } else {
            unset($proxy);
            $proxy = json_decode(@file_get_contents('http://pubproxy.com/api/proxy?type=socks5'), true);
            if (is_array($proxy) and isset($proxy['data'][0]['ip']) and isset($proxy['data'][0]['port']) and isset($proxy['data'][0]['type']) and $proxy['data'][0]['type'] === 'socks5') {
                return ['type' => 'socks5', 'ip' => $proxy['data'][0]['ip'], 'port' => $proxy['data'][0]['port']];
            } else {
                $dom = new DOMDocument(); //stackoverflow <3
                $html = @$dom->loadHTMLFile('https://www.socks-proxy.net');
                $dom->preserveWhiteSpace = false;
                $tables = $dom->getElementsByTagName('table');
                $rows = $tables->item(0)->getElementsByTagName('tr');
                $cols = $rows->item(0)->getElementsByTagName('th');
                $row_headers = null;
                foreach ($cols as $node) {
                    $row_headers[] = $node->nodeValue;
                }
                $table = [];
                $rows = $tables->item(0)->getElementsByTagName('tr');
                foreach ($rows as $row) {
                    $cols = $row->getElementsByTagName('td');
                    $row = [];
                    $i = 0;
                    foreach ($cols as $node) {
                        if ($row_headers == null) {
                            $row[] = $node->nodeValue;
                        } else {
                            $row[$row_headers[$i]] = $node->nodeValue;
                        }
                        $i++;
                    }
                    $table[] = $row;
                }
                foreach ($table as $proxy) {
                    if (isset($proxy['IP Address']) and isset($proxy['Port']) and isset($proxy['Version']) and $proxy['Version'] === 'Socks5') {
                        return ['type' => 'socks5', 'ip' => $proxy['IP Address'], 'port' => $proxy['Port']];
                    }
                }
                return []; //rip
            }
        }
    }
    private function loadPlugins($dir = 'plugins')
    {
        if (!file_exists($dir)) {
            $this->settings['plugins'] = false;
            return false;
        }
        $pluginslist = array_values(array_diff(scandir($dir), ['..', '.']));
        $plugins = [];
        $pluginN = 0;
        foreach ($pluginslist as $plugin) {
            if (substr($plugin, -4) == '.php') {
                include $dir . '/' . $plugin;
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
    private function login()
    {
        global $MadelineProto;
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
                if ($password == '') {
                    $password = trim(fgets(STDIN));
                }
                $authorization = $MadelineProto->complete_2fa_login($password);
            }
            if ($authorization['_'] === 'account.needSignup') {
                echo $this->strings['ask_name'];
                $name = trim(fgets(STDIN));
                if ($name == '') {
                    $name = trim(fgets(STDIN));
                }
                if ($name == '') {
                    $name = 'TGUserbot';
                }
                $authorization = $MadelineProto->complete_signup($name, '');
            }
        }
        $MadelineProto->session = $this->settings['session'];
        $MadelineProto->serialize($this->settings['session']);
        try {
            $MadelineProto->help->acceptTermsOfService(['id' => $MadelineProto->help->getTermsOfServiceUpdate() ['terms_of_service']['id']]);
        } catch (Exception $e) {
        }
        return $MadelineProto->get_self();
    }
    private function start()
    {
        global $MadelineProto;
        global $cron;
        $offset = - 1;
        while (true) {
            $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 500, 'timeout' => 0]);
            if ($this->settings['cronjobs']) {
                $cjb = $cron->run();
                if (is_array($cjb)) {
                    $this->handleUpdate($cjb);
                }
                unset($cjb);
            }
            if ($this->settings['always_online']) {
                if (in_array(date('s'), [0, 30, 31])) {
                    try {
                        $this->account->updateStatus(['offline' => 0], ['noResponse' => true]);
                    } catch (Exception $e) {
                    }
                }
            }
            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;
                if ($this->settings['multithread']) {
                    $pid = pcntl_fork();
                    if ($pid == - 1) {
                        die('Could not fork');
                    } elseif ($pid) {
                    } else {
                        $this->handleUpdate($this->parseUpdate($update['update']));
                        die();
                    }
                } else {
                    $this->handleUpdate($this->parseUpdate($update['update']));
                }
            }
        }
    }
    private function parseUpdate($update)
    {
        global $MadelineProto;
        $result = ['chatID' => null, 'userID' => null, 'msgid' => null, 'type' => null, 'name' => null, 'username' => null, 'chatusername' => null, 'title' => null, 'msg' => null, 'cronjob' => null, 'info' => null, 'update' => $update];
        try {
            if (isset($update['message'])) {
                if (isset($update['message']['from_id'])) {
                    $result['userID'] = $update['message']['from_id'];
                }
                if (isset($update['message']['id'])) {
                    $result['msgid'] = $update['message']['id'];
                }
                if (isset($update['message']['message'])) {
                    $result['msg'] = $update['message']['message'];
                }
                if (isset($update['message']['to_id'])) {
                    $result['info']['to'] = $MadelineProto->get_info($update['message']['to_id']);
                }
                if (isset($result['info']['to']['bot_api_id'])) {
                    $result['chatID'] = $result['info']['to']['bot_api_id'];
                }
                if (isset($result['info']['to']['type'])) {
                    $result['type'] = $result['info']['to']['type'];
                }
                if (isset($result['userID'])) {
                    $result['info']['from'] = $MadelineProto->get_info($result['userID']);
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
        } catch (Exception $e) {
            $this->error($e);
        }
        return $result;
    }
    private function handleUpdate($mUpdate)
    {
        global $MadelineProto;
        global $update;
        global $cron;
        global $data;
        global $c;
        $update = array_merge($update, $mUpdate);
        foreach ($update as $varname => $var) {
            if ($varname !== 'update') {
                $$varname = $var;
            }
        }
        if (isset($msg) and isset($chatID) and isset($type) and $msg) {
            if ($type == 'user') {
                echo $name . ' (' . $userID . ') >>> ' . $c($msg)->bold . PHP_EOL;
            } elseif ($type == 'cronjob') {
                if (is_string($cronjob)) {
                    echo 'CRONJOB >>> ' . $c($cronjob)->bold . PHP_EOL;
                } else {
                    echo 'CRONJOB >>> *array*' . PHP_EOL;
                }
            } elseif ($type == 'channel') {
                echo $title . ' (' . $chatID . ') >>> ' . $c($msg)->bold . PHP_EOL;
            } else {
                echo $name . ' (' . $userID . ') -> ' . $title . ' (' . $chatID . ') >>> ' . $c($msg)->bold . PHP_EOL;
            }
        }
        if ($this->settings['readmsg'] and isset($chatID) and isset($msgid) and $msgid and isset($type)) {
            try {
                if (in_array($type, ['user', 'bot', 'group'])) {
                    $MadelineProto->messages->readHistory(['peer' => $chatID, 'max_id' => $msgid], ['noResponse' => true]);
                } elseif (in_array($type, ['channel', 'supergroup'])) {
                    $MadelineProto->channels->readHistory(['channel' => $chatID, 'max_id' => $msgid], ['noResponse' => true]);
                }
            } catch (Exception $e) {
            }
        }
        if ($this->settings['plugins']) {
            foreach ($this->plugins as $plugin) {
                $plugin->onUpdate($update);
            }
        }
        if ($this->settings['bot_file'] !== false) {
            try {
                include DIR . $this->settings['bot_file'];
            } catch (Exception $e) {
                $this->error($e);
            } catch (Error $e) {
                $this->error($e);
            }
        }
    }
    public function MadelineCli()
    {
        if (function_exists('pcntl_fork') and function_exists('posix_getpgid')) {
            global $MadelineProto;
            $pid = pcntl_fork();
            if ($pid == - 1) {
                die('could not fork');
            } elseif ($pid) {
            } else {
                while (true) {
                    $command = explode(' ', fgets(STDIN), 2);
                    if (posix_getpgid(PID) == false) {
                        exit;
                    }
                    if (!isset($command[1])) {
                        $command[1] = '{}';
                    }
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
                        } elseif (isset($method[0])) {
                            try {
                                $response = $MadelineProto->{$method[0]}($r);
                            } catch (Exception $e) {
                                $this->error($e);
                            }
                        }
                        if (isset($response)) {
                            echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
                            unset($response);
                        }
                    }
                }
            }
        }
    }
    public function error($e)
    {
        global $MadelineProto;
        global $update;
        global $c;
        echo $c($this->strings['error'] . $e)->white->bold->bg_red . PHP_EOL;
        if (isset($update['chatID']) and $this->settings['send_errors']) {
            try {
                $MadelineProto->messages->sendMessage(['peer' => $update['chatID'], 'message' => '<b>' . $this->strings['error'] . '</b> <code>' . $e->getMessage() . '</code>', 'parse_mode' => 'HTML'], ['noResponse' => true]);
            } catch (Exception $e) {
            }
        }
    }
}

class TGUserbotCronjobs
{
    public function add($time, $id)
    {
        global $MadelineProto;
        if (!is_numeric($time) or strlen($time) !== 10) {
            $time = strtotime($time);
        }
        if (!is_numeric($time)) {
            return false;
        }
        if ($time < time()) {
            return false;
        }
        $MadelineProto->cronjobs[$time] = $id;
        return true;
    }
    public function delete($id)
    {
        global $MadelineProto;
        $cronid = array_search($id, $MadelineProto->cronjobs);
        if ($cronid !== false) {
            unset($MadelineProto->cronjobs[$cronid]);
            return true;
        } else {
            return false;
        }
    }
    public function reset()
    {
        global $MadelineProto;
        $MadelineProto->cronjobs = [];
        return true;
    }
    public function run()
    {
        global $MadelineProto;
        global $TGUserbot;
        $now = date('d m Y H i');
        if (isset($MadelineProto->cronjobs) and !empty($MadelineProto->cronjobs)) {
            foreach ($MadelineProto->cronjobs as $time => $cronjob) {
                if (date('d m Y H i', $time) === $now) {
                    $this->delete($cronjob);
                    return ['chatID' => 'cronjob', 'userID' => 'cronjob', 'msgid' => 'cronjob', 'type' => 'cronjob', 'name' => null, 'username' => null, 'chatusername' => null, 'title' => null, 'msg' => 'cronjob', 'cronjob' => $cronjob, 'info' => null, 'update' => null];
                }
            }
        }
    }
}

class TGUserbotPlugin
{
    public function onUpdate($update)
    {
    }
    public function onStart()
    {
    }
}

$cron = new TGUserbotCronjobs();
$TGUserbot = new TGUserbot();
