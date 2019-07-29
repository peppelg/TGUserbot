<?php
if (PHP_MAJOR_VERSION < 7) die('TGUserbot requires PHP 7 or higher');
define('TGUSERBOT_VERSION', 'cli-5.0');
define('TESTMODE', false);
define('INFO_URL', 'https://raw.githubusercontent.com/peppelg/TGUserbot/master/info.txt?cache='.uniqid());
define('TGUSERBOTPHAR_URL', 'https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar?cache='.uniqid());
if (shell_exec('command -v screen')) define('SCREEN_SUPPORT', true); else define('SCREEN_SUPPORT', false);
if (!Phar::running()) {
    define('DIR', __DIR__ . '/');
} else {
    define('DIR', str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/');
}
require_once __DIR__ . '/vendor/autoload.php';

class TGUserbot
{
    private $default_settings = ['language' => 'en', 'bot_file' => 'bot.php', 'readmsg' => true, 'send_errors' => true, 'always_online' => false, 'madelineCli' => true, 'send_data' => true, 'madelinePhar' => 'madeline.php', 'madeline' => ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e', 'lang_code' => 'en', 'app_version' => '5.9.0', 'device_model' => 'Asus ASUS_Z00ED', 'system_version' => 'Android Nougat MR1 (25)'], 'logger' => ['logger' => 0], 'secret_chats' => ['accept_chats' => false]]];
    public $settings = NULL;
    public $strings = NULL;

    public function __construct()
    {
        $this->settings = $this->getSettings();
        $this->filesCheck();
        $this->log('', [], 'load');
    }
    public function getSettings()
    {
        if (!file_exists(DIR . 'settings.json')) file_put_contents(DIR . 'settings.json', json_encode($this->default_settings, JSON_PRETTY_PRINT));
        $settings = json_decode(file_get_contents(DIR . 'settings.json'), 1);
        $settingsNew = array_merge($this->default_settings, $settings);
        $settingsNew['madeline']['app_info']['lang_code'] = $settingsNew['language'];
        if ($settings !== $settingsNew) file_put_contents(DIR . 'settings.json', json_encode($settingsNew, JSON_PRETTY_PRINT));
        return $settingsNew;
    }
    private function filesCheck()
    {
        if (!file_exists(DIR . 'sessions')) mkdir(DIR . 'sessions');
        if (!file_exists(DIR . 'madeline.php') and $this->settings['madelinePhar'] === 'madeline.php') copy('https://phar.madelineproto.xyz/madeline.php', DIR . 'madeline.php');
        if (!file_exists(DIR . $this->settings['bot_file'])) $this->settings['bot_file'] = NULL;
    }
    public function getSessions()
    {
        $result = [];
        $sessions = glob(DIR . 'sessions/*.madeline');
        foreach ($sessions as $session) {
            $name = basename($session, '.madeline');
            if ($name) {
                array_push($result, $name);
            }
        }
        return $result;
    }
    public function startInBackground($sessions)
    {
        if (SCREEN_SUPPORT) {
            foreach ($sessions as $session) {
                shell_exec('screen -d -m -S ' . escapeshellarg('TGUserbot_' . $session) . ' ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($_SERVER['SCRIPT_NAME']) . ' --session=' . escapeshellarg($session));
                $this->log('started_background', [$session]);
            }
        } else {
            $this->log('error_install_package', ['screen']);
        }
    }
    public function killSession($sessions)
    {
        if (SCREEN_SUPPORT) {
            foreach ($sessions as $session) {
                shell_exec('screen -X -S ' . escapeshellarg('TGUserbot_' . $session) . ' quit');
                $this->log('session_stopped', [$session]);
            }
        } else {
            $this->log('error_install_package', ['screen']);
        }
    }
    public function log($message, $args = [], $type = NULL)
    {
        if ($this->strings === NULL) {
            if (file_exists(__DIR__ . '/strings_' . $this->settings['language'] . '.json')) {
                $this->strings = json_decode(file_get_contents(__DIR__ . '/strings_' . $this->settings['language'] . '.json'), 1);
            } else {
                $this->strings = json_decode(file_get_contents('https://raw.githubusercontent.com/peppelg/TGUserbot/master/src/strings_en.json'), 1);
                $this->log('language_error', [$this->settings['language']]);
            }
        }
        if ($type === 'load') {
            return true;
        }
        if ($type === 'error') {
            try {
                echo "\e[1;37;41m" . $this->strings['error'] . $message . "\e[0m" . PHP_EOL;
            } catch (\Throwable $e) { }
            return;
        }
        $string = vsprintf($this->strings[$message], $args);
        if ($type === 'readline') {
            while (true) {
                echo $string;
                $stdin = trim(fgets(STDIN));
                if ($stdin) {
                    return $stdin;
                }
            }
            return;
        }
        echo $string . PHP_EOL;
        return;
    }
    public function sendData()
    {
        if ($this->settings['send_data'] and function_exists('curl_version') and function_exists('json_encode')) { //https://tguserbot.peppelg.space/privacy.txt
            $data = ['settings' => $this->settings];
            unset($data['settings']['madeline']['app_info']); //remove private api_hash and api_id
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
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['TGUSERBOTDATA: ' . json_encode($data)]);
            curl_setopt($ch, CURLOPT_USERAGENT, 'TGUserbot data');
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_exec($ch);
            curl_close($ch);
            return $data;
        } else {
            return false;
        }
    }
    public function start($session = 'session')
    {
        $this->log('loading_madeline');
        $this->sendData();
        require_once DIR . $this->settings['madelinePhar'];
        $MadelineProto = new \danog\MadelineProto\API(DIR . 'sessions/' . $session . '.madeline', $this->settings['madeline']);
        try {
            $me = $MadelineProto->get_self();
        } catch (\Throwable $e) {
            $me = false;
            $this->log($e, [], 'error');
        }
        if (!$me) {
            //LOGIN
            $phone = $this->log('enter_phone', [], 'readline');
            if (strtolower($phone) === 'bot') {
                $authorization = $MadelineProto->bot_login($this->log('enter_token', [], 'readline'));
            } else {
                $MadelineProto->phone_login($phone);
                $authorization = $MadelineProto->complete_phone_login($this->log('enter_code', [], 'readline'));
                if ($authorization['_'] === 'account.password') {
                    if (!isset($authorization['hint'])) $authorization['hint'] = '*no hint*';
                    $authorization = $MadelineProto->complete_2fa_login($this->log('enter_2fa', [$authorization['hint']], 'readline'));
                }
                if ($authorization['_'] === 'account.needSignup') {
                    $authorization = $MadelineProto->complete_signup($this->log('enter_account_name', [], 'readline'), '');
                }
            }
            try {
                $MadelineProto->help->acceptTermsOfService(['id' => $MadelineProto->help->getTermsOfServiceUpdate()['terms_of_service']['id']]);
            } catch (\Throwable $e) { }
            $me = $MadelineProto->get_self();
            unset($authorization);
        }
        if ($this->settings['bot_file']) {
            require_once DIR . $this->settings['bot_file'];
        }
        require __DIR__ . '/handleUpdate.php';
        $MadelineProto->async(true);
        $MadelineProto->setCallback($callback);
        if ($this->settings['bot_file'] and !isset($bot) or !is_callable($bot)) {
            $this->log('error_invalid_bot', [$this->settings['bot_file']]);
            $this->settings['bot_file'] = NULL;
            $bot = NULL;
        }
        if ($this->settings['madelineCli']) $MadelineProto->callFork($MadelineCli());
        Amp\Loop::repeat($msInterval = 1000, $onLoop);
        $this->log('ok');
        $MadelineProto->loop();
    }
}
