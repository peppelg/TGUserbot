<?php
define('WEB_VERSION', '1.2');
ob_start();
if (isset($_GET['ping'])) {
    header('Content-Type: application/json');
    echo json_encode(array('TGUserbot_web' => WEB_VERSION));
    exit;
}
if (isset($_POST['login_password'])) {
    setcookie('password', $_POST['login_password']);
    $_COOKIE['password'] = $_POST['login_password'];
}
function autoUpdate($conf)
{
    $newFile = file_get_contents('https://raw.githubusercontent.com/peppelg/TGUserbot/master/web/index.php?cache=' . uniqid());
    if (file_get_contents(__FILE__) !== $newFile) {
        file_put_contents(__FILE__, $newFile);
    }
    if (defined('INFO_URL') and defined('TGUSERBOTPHAR_URL')) {
        if (json_decode(file_get_contents(INFO_URL), true)['md5'] !== md5_file(__DIR__ . '/' . $conf['dir'] . '/TGUserbot.phar')) {
            $newFile = file_get_contents(TGUSERBOTPHAR_URL);
            if (md5($newFile) === json_decode(file_get_contents(INFO_URL), true)['md5']) {
                file_put_contents(__DIR__ . '/' . $conf['dir'] . '/TGUserbot.phar', $newFile);
            }
        }
    }
}
function saveSettings($conf)
{
    file_put_contents(__DIR__ . '/.conf.php', '<?php $conf = ' . var_export($conf, true) . ';');
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>TGUserbotWeb</title>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <span class="navbar-brand">TGUserbotWeb</span>
    </nav>
    <br><br>
    <div class="container">
        <?php
        $setup_template = <<<EOT
    <form method="post">
  <div class="form-group">
    <label for="register_password">New TGUserbot password</label>
    <input type="password" class="form-control" id="register_password" name="register_password" placeholder="Password">
  </div>
  <button type="submit" class="btn btn-primary">Submit</button>
</form>
EOT;
        $login_template = <<<EOT
<form method="post">
<div class="form-group">
<label for="login_password">Enter your TGUserbot password</label>
<input type="password" class="form-control" id="login_password" name="login_password" placeholder="Password">
</div>
<button type="submit" class="btn btn-primary">Submit</button>
</form>
EOT;
        $template = <<<EOT
    <div id="status"></div>
    <button type="button" class="btn btn-primary" onclick="start();" id="start_button">Start</button>
    <button type="button" class="btn btn-danger" onclick="stop();" id="stop_button">Stop</button>
    <button type="button" class="btn btn-secondary" onclick="location.href = '?p=other';" id="other_button">Other</button>
    <br><br>
    <div id="consoleContainer" style='background-color:#000;border-radius:3px;resize:none;padding:20px;height:400px;width:100%;color:#000;font-family:-apple-system,BlinkMacSystemFont,"SegoeUI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"HelveticaNeue",sans-serif;line-height:18px;'>
    <div id="console" style='width:100%;height:100%;background-color:inherit;overflow-y:scroll;overflow-x:hidden;word-wrap:break-word;'></div>
    </div>
  </div>
EOT;
        $other_template = <<<EOT
<div class="jumbotron">
<button type="button" class="btn btn-primary" onclick='window.location = window.location.href.split("?")[0];'>Go back</button>
</div>
<div class="jumbotron">
<h4>Change password</h4>
<form action="?p=changePassword" method="post">
<div class="form-group">
<input type="password" class="form-control" id="old_password" name="old_password" placeholder="Old TGUserbot password">
</div>
<div class="form-group">
<input type="password" class="form-control" id="new_password" name="new_password" placeholder="New TGUserbot password">
</div>
<button type="submit" class="btn btn-primary">Submit</button>
</form>
</div>
EOT;

        if (!file_exists(__DIR__ . '/.conf.php')) { //SETUP
            if (isset($_POST['register_password']) and $_POST['register_password']) {
                if (PHP_MAJOR_VERSION < 7) {
                    echo <<<EOT
          <div class="alert alert-danger" role="alert">
          ERROR: Please use PHP 7 or higher.
        </div>
EOT;
                } else {
                    $passwordhash = password_hash($_POST['register_password'], PASSWORD_DEFAULT);
                    $phar = file_get_contents('https://github.com/peppelg/TGUserbot/raw/master/TGUserbot.phar');
                    $botphp = file_get_contents('https://raw.githubusercontent.com/peppelg/TGUserbot/master/bot.php');
                    if ($phar) {
                        $install_dir = uniqid();
                        mkdir(__DIR__ . '/' . $install_dir);
                        file_put_contents(__DIR__ . '/' . $install_dir . '/TGUserbot.phar', $phar);
                        file_put_contents(__DIR__ . '/' . $install_dir . '/bot.php', $botphp);
                        $conf = ['dir' => $install_dir, 'password' => $passwordhash, 'session' => uniqid(), 'v' => WEB_VERSION];
                        saveSettings($conf);
                        echo <<<EOT
            <div class="alert alert-success" role="alert">
            Successfully installed TGUserbot.
            </div>
            <button type="button" class="btn btn-primary" onclick="location.reload(1);">Refresh</button>
EOT;
                    } else {
                        echo <<<EOT
            <div class="alert alert-danger" role="alert">
            ERROR: Please enable S2S.
          </div>
EOT;
                    }
                }
            } else {
                echo $setup_template;
            }
        } else {
            require __DIR__ . '/.conf.php';
            if (isset($conf['dir']) and isset($conf['password'])) {
                if (isset($_COOKIE['password'])) {
                    if (password_verify($_COOKIE['password'], $conf['password'])) { //LOGIN OK
                        error_reporting(0);
                        chdir(__DIR__ . '/' . $conf['dir']);
                        require __DIR__ . '/' . $conf['dir'] . '/TGUserbot.phar';
                        if (isset($_GET['p'])) { //'''API'''
                            if ($_GET['p'] === 'newSession') {
                                $r = $TGUserbot->start($conf['session']);
                                if ($r === 'Done') {
                                    echo '<script>window.location = window.location.href.split("?")[0];</script>';
                                }
                            }
                            if ($_GET['p'] === 'other') {
                                echo $other_template;
                            }
                            if ($_GET['p'] === 'changePassword') {
                                if (isset($_POST['new_password']) and isset($_POST['old_password']) and $_POST['new_password'] and password_verify($_POST['old_password'], $conf['password'])) {
                                    $conf['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                                    saveSettings($conf);
                                    echo <<<EOT
                                    <div class="alert alert-success" role="alert">
                                    Your password has been changed.
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick='window.location = window.location.href.split("?")[0];'>Refresh</button>
EOT;
                                } else {
                                    echo <<<EOT
                                    <div class="alert alert-danger" role="alert">
                                    Invalid password.
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="location.href = '?p=other';">Go back</button>
EOT;
                                }
                            }
                            if ($_GET['p'] === 'start') {
                                file_put_contents(__DIR__ . '/' . $conf['dir'] . '/a_status', 'start');
                                $TGUserbot->start($conf['session']);
                            }
                            if ($_GET['p'] === 'stop') {
                                file_put_contents(__DIR__ . '/' . $conf['dir'] . '/a_status', 'stop');
                                file_put_contents(__DIR__ . '/' . $conf['dir'] . '/status', 'stopped');
                            }
                            if ($_GET['p'] === 'status') {
                                ob_end_clean();
                                if (file_exists(__DIR__ . '/' . $conf['dir'] . '/status')) {
                                    $status = file_get_contents(__DIR__ . '/' . $conf['dir'] . '/status');
                                } else {
                                    $status = 'stopped';
                                }
                                echo $status;
                                exit;
                            }
                            if ($_GET['p'] === 'getLog') {
                                ob_end_clean();
                                $file = new SplFileObject(__DIR__ . '/' . $conf['dir'] . '/log.txt', 'r');
                                $file->seek(PHP_INT_MAX);
                                $last_line = $file->key();
                                if ($last_line < 50) $getLines = $last_line;
                                else $getLines = 50;
                                $lines = new LimitIterator($file, $last_line - $getLines, $last_line);
                                $result = [];
                                foreach (iterator_to_array($lines) as $line) {
                                    array_push($result, $line);
                                }
                                header('Content-Type: application/json');
                                echo json_encode($result);
                                exit;
                            }
                        } else { //Cose dopo login

                            //Migrazione
                            if (!isset($conf['session'])) { //v 1.1 -> 1.2
                                $conf['session'] = uniqid();
                                $conf['v'] = WEB_VERSION;
                                saveSettings($conf); //salva new conf
                                if (file_exists(__DIR__ . '/' . $conf['dir'] . '/sessions/session.madeline')) {
                                    rename(__DIR__ . '/' . $conf['dir'] . '/sessions/session.madeline', __DIR__ . '/' . $conf['dir'] . '/sessions/' . $conf['session'] . '.madeline');
                                    @unlink(__DIR__ . '/' . $conf['dir'] . '/sessions/session.madeline.lock');
                                }
                            }
                            //Fine migrazione
                            if (!file_exists(__DIR__ . '/' . $conf['dir'] . '/sessions/' . $conf['session'] . '.madeline')) {
                                echo <<<EOT
                <button type="button" class="btn btn-primary" onclick="location.href = '?p=newSession';">New session</button>
EOT;
                            } else {
                                autoUpdate($conf);
                                echo $template;
                            }
                        }
                    } else {
                        echo <<<EOT
            <div class="alert alert-danger" role="alert">
            Invalid password.
          </div>
EOT;
                        echo $login_template;
                    }
                } else {
                    echo $login_template;
                }
            } else {
                echo <<<EOT
        <div class="alert alert-danger" role="alert">
        Unknown error, please reinstall TGUserbot. 
      </div>
EOT;
            }
        }
        ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script>
        var console_text = [];

        function start() {
            $('#start_button').attr('disabled', 1);
            $.get(window.location.href + '?p=start', function(data) {});
        }

        function stop() {
            $('#stop_button').attr('disabled', 1);
            $.get(window.location.href + '?p=stop', function(data) {});
        }

        var entityMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        };

        function escapeHtml(string) {
            return String(string).replace(/[&<>"'`=\/]/g, function(s) {
                return entityMap[s];
            });
        }

        function toConsole(text) { //grz a pato05
            consoleElem = $('#console');
            consoleElem.append('<span style="color:white">' + escapeHtml(text) + '</span><br>');
            consoleElem.scrollTop(consoleElem[0].scrollHeight - consoleElem.height());
        }
        $(document).ready(function() {
            if ($('#status').length) {
                setInterval(function() {
                    $.get(window.location.href + '?p=status', function(data) {
                        data = data.trim();
                        $('#status').html('Status: <b>' + data + '</b');
                        if (data == 'started') {
                            $('#start_button').prop('disabled', 1);
                            $('#stop_button').prop('disabled', 0);
                        }
                        if (data == 'stopped') {
                            $('#start_button').prop('disabled', 0);
                            $('#stop_button').prop('disabled', 1);
                        }
                    });
                }, 1500);
                setInterval(function() {
                    $.get(window.location.href + '?p=getLog', function(data) {
                        for (index = 0; index < data.length; ++index) {
                            if (console_text.includes(data[index]) == false) {
                                toConsole(data[index]);
                            }
                        }
                        console_text = data;
                    });
                }, 1000);
            }
        });
    </script>
</body>

</html>