<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights >= 7 && $rights > $user['rights']) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    // Сброс настроек пользователя
    $textl = htmlspecialchars($user['name']) . ': ' . _td('Edit Profile');
    require('../incfiles/head.php');

    $db->query("UPDATE `users` SET `set_user` = '', `set_forum` = '' WHERE `id` = " . $user['id']);

    echo '<div class="gmenu"><p>' . sprintf(_td('For user %s default settings were set.'), $user['name'])
        . '<br />'
        . '<a href="?user=' . $user['id'] . '">' . _td('Profile') . '</a></p></div>';
    require_once('../incfiles/end.php');
}
