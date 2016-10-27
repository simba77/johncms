<?php

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();
$config = $container->get('config')['johncms'];

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$textl = _t('Registration');
$headmod = 'registration';
require('../system/head.php');

// Если регистрация закрыта, выводим предупреждение
if (core::$deny_registration || !$config['mod_reg'] || core::$user_id) {
    echo '<p>' . _t('Registration is temporarily closed') . '</p>';
    require('../system/end.php');
    exit;
}

$captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : null;
$reg_nick = isset($_POST['nick']) ? trim($_POST['nick']) : '';
$lat_nick = functions::rus_lat(mb_strtolower($reg_nick));
$reg_pass = isset($_POST['password']) ? trim($_POST['password']) : '';
$reg_name = isset($_POST['imname']) ? trim($_POST['imname']) : '';
$reg_about = isset($_POST['about']) ? trim($_POST['about']) : '';
$reg_sex = isset($_POST['sex']) ? trim($_POST['sex']) : '';

echo '<div class="phdr"><b>' . _t('Registration') . '</b></div>';

if (isset($_POST['submit'])) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    // Принимаем переменные
    $error = [];

    // Проверка Логина
    if (empty($reg_nick)) {
        $error['login'][] = _t('You have not entered Nickname');
    } elseif (mb_strlen($reg_nick) < 2 || mb_strlen($reg_nick) > 20) {
        $error['login'][] = _t('Nickname wrong length');
    }

    if (preg_match('/[^\da-z\-\@\*\(\)\?\!\~\_\=\[\]]+/', $lat_nick)) {
        $error['login'][] = _t('Invalid characters');
    }

    // Проверка пароля
    if (empty($reg_pass)) {
        $error['password'][] = _t('You have not entered password');
    } elseif (mb_strlen($reg_pass) < 3) {
        $error['password'][] = _t('Invalid length');
    }

    // Проверка пола
    if ($reg_sex != 'm' && $reg_sex != 'zh') {
        $error['sex'] = _t('You have not selected genger');
    }

    // Проверка кода CAPTCHA
    if (!$captcha
        || !isset($_SESSION['code'])
        || mb_strlen($captcha) < 4
        || $captcha != $_SESSION['code']
    ) {
        $error['captcha'] = _t('The security code is not correct');
    }

    unset($_SESSION['code']);

    // Проверка переменных
    if (empty($error)) {
        $pass = md5(md5($reg_pass));
        $reg_name = htmlspecialchars(mb_substr($reg_name, 0, 50));
        $reg_about = htmlspecialchars(mb_substr($reg_about, 0, 1000));
        // Проверка, занят ли ник
        $stmt = $db->prepare('SELECT * FROM `users` WHERE `name_lat` = ?');
        $stmt->execute([$lat_nick]);

        if ($stmt->rowCount()) {
            $error['login'][] = _t('Selected Nickname is already in use');
        }
    }

    if (empty($error)) {
        /** @var Johncms\Environment $env */
        $env = $container->get('env');

        $preg = $config['mod_reg'] > 1 ? 1 : 0;
        $db->prepare('
          INSERT INTO `users` SET
          `name` = ?,
          `name_lat` = ?,
          `password` = ?,
          `imname` = ?,
          `about` = ?,
          `sex` = ?,
          `rights` = 0,
          `ip` = ?,
          `ip_via_proxy` = ?,
          `browser` = ?,
          `datereg` = ?,
          `lastdate` = ?,
          `sestime` = ?,
          `preg` = ?,
          `set_user` = \'\',
          `set_forum` = \'\',
          `set_mail` = \'\',
          `smileys` = \'\'
        ')->execute([
            $reg_nick,
            $lat_nick,
            $pass,
            $reg_name,
            $reg_about,
            $reg_sex,
            $env->getIp(),
            $env->getIpViaProxy(),
            $env->getUserAgent(),
            time(),
            time(),
            time(),
            $preg,
        ]);

        $usid = $db->lastInsertId();

        // Отправка системного сообщения
        $set_mail = unserialize($config['setting_mail']);

        if (!isset($set_mail['message_include'])) {
            $set_mail['message_include'] = 0;
        }

        if ($set_mail['message_include']) {
            $array = ['{LOGIN}', '{TIME}'];
            $array_replace = [$reg_nick, '{TIME=' . time() . '}'];

            if (empty($config['them_message'])) {
                $config['them_message'] = $lng_mail['them_message'];
            }

            if (empty($config['reg_message'])) {
                $config['reg_message'] = $lng['hi'] . ", {LOGIN}\r\n" . $lng_mail['pleased_see_you'] . "\r\n" . $lng_mail['come_my_site'] . "\r\n" . $lng_mail['respectfully_yours'];
            }

            $theme = str_replace($array, $array_replace, $config['them_message']);
            $system = str_replace($array, $array_replace, $config['reg_message']);

            $db->prepare('
              INSERT INTO `cms_mail` SET
              `user_id` = 0,
              `from_id` = ?,
              `text` = ?,
              `time` = ?,
              `sys` = 1,
              `them` = ?
            ')->execute([
                $usid,
                $system,
                time(),
                $theme,
            ]);
        }

        echo '<div class="menu"><p><h3>' . _t('Your registratiton data') . '</h3>'
            . _t('Your ID') . ': <b>' . $usid . '</b><br>'
            . _t('Your Username') . ': <b>' . $reg_nick . '</b><br>'
            . _t('Your Password') . ': <b>' . $reg_pass . '</b></p>';

        if ($config['mod_reg'] == 1) {
            echo '<p><span class="red"><b>' . _t('Please, wait until a moderator approves your registration') . '</b></span></p>';
        } else {
            $_SESSION['uid'] = $usid;
            $_SESSION['ups'] = md5(md5($reg_pass));
            echo '<p><a href="' . $config['homeurl'] . '">' . _t('Enter') . '</a></p>';
        }

        echo '</div>';
        require('../system/end.php');
        exit;
    }
}

// Форма регистрации
if ($config['mod_reg'] == 1) {
    echo '<div class="rmenu"><p>' . _t('You can get authorized on the site after confirmation of your registration.') . '</p></div>';
}

echo '<form action="index.php" method="post"><div class="gmenu">' .
    '<p><h3>' . _t('Choose Nickname') . '</h3>' .
    (isset($error['login']) ? '<span class="red"><small>' . implode('<br />',
            $error['login']) . '</small></span><br />' : '') .
    '<input type="text" name="nick" maxlength="15" value="' . htmlspecialchars($reg_nick) . '"' . (isset($error['login']) ? ' style="background-color: #FFCCCC"' : '') . '/><br />' .
    '<small>' . _t('Min. 2, Max. 20 characters.<br />Allowed letters of the russian and latin alphabets, numbers and symbols - = @ ! ? ~ _ ( ) [ ] . * (Except zero)') . '</small></p>' .
    '<p><h3>' . _t('Assign a password') . '</h3>' .
    (isset($error['password']) ? '<span class="red"><small>' . implode('<br />',
            $error['password']) . '</small></span><br />' : '') .
    '<input type="text" name="password" maxlength="20" value="' . htmlspecialchars($reg_pass) . '"' . (isset($error['password']) ? ' style="background-color: #FFCCCC"' : '') . '/><br>' .
    '<small>' . _t('Min. 3 characters') . '</small></p>' .
    '<p><h3>' . _t('Select Gender') . '</h3>' .
    (isset($error['sex']) ? '<span class="red"><small>' . $error['sex'] . '</small></span><br />' : '') .
    '<select name="sex"' . (isset($error['sex']) ? ' style="background-color: #FFCCCC"' : '') . '>' .
    '<option value="?">-?-</option>' .
    '<option value="m"' . ($reg_sex == 'm' ? ' selected="selected"' : '') . '>' . _t('Man') . '</option>' .
    '<option value="zh"' . ($reg_sex == 'zh' ? ' selected="selected"' : '') . '>' . _t('Woman') . '</option>' .
    '</select></p></div>' .
    '<div class="menu">' .
    '<p><h3>' . _t('Your name') . '</h3>' .
    '<input type="text" name="imname" maxlength="30" value="' . htmlspecialchars($reg_name) . '" /><br />' .
    '<small>' . _t('Max. 50 characters') . '</small></p>' .
    '<p><h3>' . _t('Tell us a little about yourself') . '</h3>' .
    '<textarea rows="3" name="about">' . htmlspecialchars($reg_about) . '</textarea><br />' .
    '<small>' . _t('Max. 1000 characters') . '</small></p></div>' .
    '<div class="gmenu"><p>' .
    '<h3>' . _t('Verification code') . '</h3>' .
    '<img src="../captcha.php?r=' . rand(1000, 9999) . '" alt="' . _t('Verification code') . '" border="1"/><br />' .
    (isset($error['captcha']) ? '<span class="red"><small>' . $error['captcha'] . '</small></span><br />' : '') .
    '<input type="text" size="5" maxlength="5"  name="captcha" ' . (isset($error['captcha']) ? ' style="background-color: #FFCCCC"' : '') . '/><br />' .
    '<small>' . _t('If you cannot see the image code, enable graphics in your browser and refresh this page') . '</small></p>' .
    '<p><input type="submit" name="submit" value="' . _t('Registration') . '"/></p></div></form>' .
    '<div class="phdr"><small>' . _t('Please, do not register names like 111, shhhh, uuuu, etc. They will be deleted. <br /> Also all the profiles registered via proxy servers will be deleted') . '</small></div>';

require('../system/end.php');
