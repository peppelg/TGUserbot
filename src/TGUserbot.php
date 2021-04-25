<?php
if (PHP_MAJOR_VERSION < 7) die('TGUserbot requires PHP 7 or higher');
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') define('RUNNING_WINDOWS', true);
else define('RUNNING_WINDOWS', false);
if (php_sapi_name() === 'cli') define('RUNNING_FROM', 'cli');
else define('RUNNING_FROM', 'web');
define('TGUSERBOT_VERSION', RUNNING_FROM . '-5.3');
define('TESTMODE', false);
define('INFO_URL', 'https://raw.githubusercontent.com/peppelg/TGUserbot/master/info.txt?cache=' . uniqid());
define('TGUSERBOTPHAR_URL', 'https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar?cache=' . uniqid());
if (!RUNNING_WINDOWS and RUNNING_FROM === 'cli' and shell_exec('command -v screen')) define('SCREEN_SUPPORT', true);
else define('SCREEN_SUPPORT', false);
if (!Phar::running()) {
    define('DIR', __DIR__ . '/');
} else {
    define('DIR', str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)) . '/');
}
require_once __DIR__ . '/vendor/autoload.php';

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

class TGUserbot extends \danog\MadelineProto\EventHandler
{
    private $default_settings = ['language' => 'en', 'bot_file' => 'bot.php', 'readmsg' => true, 'send_errors' => true, 'always_online' => false, 'auto_reboot' => true, 'madelineCli' => true, 'send_data' => true];
    private $default_madelineSettings = ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e', 'lang_code' => 'en'], 'logger' => ['logger' => \danog\MadelineProto\Logger::FILE_LOGGER], 'secret_chats' => ['accept_chats' => false]];
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
        if (!file_exists(DIR . 'settings.php')) {
            file_put_contents(DIR . 'settings.php', "<?php\n\n" . '$settings = ' . var_export($this->default_settings, true) . ";\n\n" . '$madelineSettings = ' . var_export($this->default_madelineSettings, true).';');
        }
        require DIR . 'settings.php';
        if (!isset($settings)) {
            $settings = $this->default_settings;
            echo 'Your settings.php is broken. Using default settings.' . PHP_EOL;
        }
        if (!isset($madelineSettings)) {
            $madelineSettings = $this->default_madelineSettings;
        }
        $settingsNew = array_merge($this->default_settings, $settings);
        $settingsNew['madeline'] = $madelineSettings;
        /* //vecchio settings.json
        if (!file_exists(DIR . 'settings.json')) file_put_contents(DIR . 'settings.json', json_encode($this->default_settings, JSON_PRETTY_PRINT));
        $settings = json_decode(file_get_contents(DIR . 'settings.json'), 1);
        $settingsNew = array_replace_recursive($this->default_settings, $settings);
        */
        $settingsNew['madeline']['app_info']['lang_code'] = $settingsNew['language'];
        if (RUNNING_FROM === 'web' and $settingsNew['madeline']['logger'] === $this->default_settings['madeline']['logger']) {
            $settingsNew['madeline']['logger']['param'] = DIR . 'MadelineProto.log';
        }
        //if ($settings !== $settingsNew) file_put_contents(DIR . 'settings.json', json_encode($settingsNew, JSON_PRETTY_PRINT));
        return $settingsNew;
    }
    private function filesCheck()
    {
        if (!file_exists(DIR . 'sessions')) mkdir(DIR . 'sessions');
        if (RUNNING_FROM === 'web' and !file_exists(DIR . 'sessions/index.php')) file_put_contents(DIR . 'sessions/index.php', '<?php //silencio');
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
                if (RUNNING_FROM === 'cli') {
                    echo "\e[1;37;41m" . $this->strings['error'] . $message . "\e[0m" . PHP_EOL;
                } else {
                    file_put_contents(DIR . 'log.txt', $this->strings['error'] . $message . PHP_EOL, FILE_APPEND);
                }
            } catch (\Throwable $e) { }
            return;
        }
        $string = vsprintf($this->strings[$message], $args);
        if ($type === 'readline') {
            if (RUNNING_FROM === 'web') return 0;
            while (true) {
                echo $string;
                $stdin = trim(fgets(STDIN));
                if ($stdin) {
                    return $stdin;
                }
            }
            return;
        }
        if (RUNNING_FROM === 'cli') {
            echo $string . PHP_EOL;
        } else {
            file_put_contents(DIR . 'log.txt', $string . PHP_EOL, FILE_APPEND);
        }
        return;
    }
    public function sendData()
    {
        if ($this->settings['send_data'] and function_exists('curl_version') and function_exists('json_encode')) { //https://tguserbot.peppelg.space/privacy.txt
            $data = ['settings' => $this->settings];
            unset($data['settings']['madeline']['app_info']); //remove private api_hash and api_id
            if (RUNNING_WINDOWS) $data['uname'] = @shell_exec('ver');
            else $data['uname'] = @shell_exec('uname -a');
            $data['php'] = phpversion();
            $data['tguserbot'] = TGUSERBOT_VERSION;
            $data['path'] = __FILE__;
            if (file_exists(DIR . 'sessions') and is_dir(DIR . 'sessions')) {
                $data['sessions'] = count(glob(DIR . 'sessions/*.madeline'));
            }
            $ch = curl_init();
            if (RUNNING_WINDOWS) {
                if (!file_exists(DIR . 'cacert.pem')) {
                    copy('http://curl.haxx.se/ca/cacert.pem', DIR . 'cacert.pem');
                }
                curl_setopt($ch, CURLOPT_CAINFO, DIR . 'cacert.pem');
                curl_setopt($ch, CURLOPT_CAPATH, DIR . 'cacert.pem');
            }
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
        $MadelineProto = new \danog\MadelineProto\API(DIR . 'sessions/' . $session . '.madeline', $this->settings['madeline']);
        $MadelineProto->startAndLoop(self::class);
    }

    private $callback = null;
    public function onStart()
    {
        $this->settings = $this->getSettings();
        $this->filesCheck();
        $this->log('', [], 'load');
        $me = yield $this->getSelf();
        if (isset($me['first_name'])) $this->log('logged_as', [$me['first_name']]);
        if (RUNNING_FROM === 'cli' and $this->settings['auto_reboot']) {
            register_shutdown_function(function () {
                $restart = escapeshellarg(PHP_BINARY);
                foreach ($GLOBALS['argv'] as $arg) {
                    $restart .= ' ' . escapeshellarg($arg);
                }
                passthru($restart);
            });
        }
        if ($this->settings['bot_file']) {
            require_once DIR . $this->settings['bot_file'];
        }
        $MadelineProto = $this;
        require __DIR__ . '/handleUpdate.php';
        $this->callback = $callback;
        if ($this->settings['bot_file'] and !isset($bot) or !is_callable($bot)) {
            $this->log('error_invalid_bot', [$this->settings['bot_file']]);
            $this->settings['bot_file'] = NULL;
            $bot = NULL;
        }
        if (!RUNNING_WINDOWS and RUNNING_FROM === 'cli' and $this->settings['madelineCli']) $this->callFork($MadelineCli());
        Amp\Loop::repeat($msInterval = 1000, $onLoop);
        if (RUNNING_FROM === 'web') {
            file_put_contents(DIR . 'status', 'started');
            \danog\MadelineProto\Shutdown::addCallback(static function () {
                file_put_contents(DIR . 'status', 'stopped');
            });
        }
        if (RUNNING_FROM === 'cli') $this->log('ok');
        else $this->log('ok_web');
    }
    public function onAny($update)
    {
        if ($this->callback) {
            return ($this->callback)($update);
        }
    }
}
