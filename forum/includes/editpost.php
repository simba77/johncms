<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../incfiles/head.php');

if (!$user_id || !$id) {
    echo functions::display_error(_t('Wrong data'));
    require('../incfiles/end.php');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'm' " . ($rights >= 7 ? "" : " AND `close` != '1'"));

if ($req->rowCount()) {
    // Предварительные проверки
    $res = $req->fetch();

    $topic = $db->query("SELECT `refid`, `curators` FROM `forum` WHERE `id` = " . $res['refid'])->fetch();
    $curators = !empty($topic['curators']) ? unserialize($topic['curators']) : [];

    if (array_key_exists($user_id, $curators)) {
        $rights = 3;
    }

    $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '$id'" . ($rights < 7 ? " AND `close` != '1'" : ''))->fetchColumn() / $kmess);
    $posts = $db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `close` != '1'")->fetchColumn();
    $link = 'index.php?id=' . $res['refid'] . '&amp;page=' . $page;
    $error = false;

    if ($rights == 3 || $rights >= 6) {
        // Проверка для Администрации
        if ($res['user_id'] != $user_id) {
            $req_u = $db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

            if ($req_u->rowCount()) {
                $res_u = $req_u->fetch();

                if ($res_u['rights'] > $datauser['rights']) {
                    $error = _t('You cannot edit posts of higher administration') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
                }
            }
        }
    } else {
        // Проверка для обычных юзеров
        if ($res['user_id'] != $user_id) {
            $error = _t('You are trying to change another\'s post') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
        }

        if (!$error) {
            $section = $db->query("SELECT * FROM `forum` WHERE `id` = " . $topic['refid'])->fetch();
            $allow = !empty($section['edit']) ? intval($section['edit']) : 0;
            $check = true;

            if ($allow == 2) {
                $first = $db->query("SELECT * FROM `forum` WHERE `refid` = '" . $res['refid'] . "' ORDER BY `id` ASC LIMIT 1")->fetch();

                if ($first['user_id'] == $user_id && $first['id'] == $id) {
                    $check = false;
                }
            }

            if ($check) {
                $res_m = $db->query("SELECT * FROM `forum` WHERE `refid` = '" . $res['refid'] . "' ORDER BY `id` DESC LIMIT 1")->fetch();

                if ($res_m['user_id'] != $user_id) {
                    $error = _t('Your message not already latest, you cannot change it') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
                } elseif ($res['time'] < time() - 300) {
                    $error = _t('You cannot edit your posts after 5 minutes') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
                }
            }
        }
    }
} else {
    $error = _t('Message does not exists or has been deleted') . '<br /><a href="index.php">' . _t('Forum') . '</a>';
}

if (!$error) {
    switch ($do) {
        case 'restore':
            // Восстановление удаленного поста
            $req_u = $db->query("SELECT `postforum` FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

            if ($req_u->rowCount()) {
                // Добавляем один балл к счетчику постов юзера
                $res_u = $req_u->fetch();
                $db->exec("UPDATE `users` SET `postforum` = '" . ($res_u['postforum'] + 1) . "' WHERE `id` = '" . $res['user_id'] . "'");
            }

            $db->exec("UPDATE `forum` SET `close` = '0', `close_who` = " . $db->quote($login) . " WHERE `id` = '$id'");
            $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '$id' LIMIT 1");

            if ($req_f->rowCount()) {
                $db->exec("UPDATE `cms_forum_files` SET `del` = '0' WHERE `post` = '$id' LIMIT 1");
            }

            header('Location: ' . $link);
            break;

        case 'delete':
            // Удаление поста и прикрепленного файла
            if ($res['close'] != 1) {
                $req_u = $db->query("SELECT `postforum` FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

                if ($req_u->rowCount()) {
                    // Вычитаем один балл из счетчика постов юзера
                    $res_u = $req_u->fetch();
                    $postforum = $res_u['postforum'] > 0 ? $res_u['postforum'] - 1 : 0;
                    $db->exec("UPDATE `users` SET `postforum` = '" . $postforum . "' WHERE `id` = '" . $res['user_id'] . "'");
                }
            }

            if ($rights == 9 && !isset($_GET['hide'])) {
                // Удаление поста (для Супервизоров)
                $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '$id' LIMIT 1");

                if ($req_f->rowCount()) {
                    // Если есть прикрепленный файл, удаляем его
                    $res_f = $req_f->fetch();
                    unlink('../files/forum/attach/' . $res_f['filename']);
                    $db->exec("DELETE FROM `cms_forum_files` WHERE `post` = '$id' LIMIT 1");
                }

                // Формируем ссылку на нужную страницу темы
                $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">" : "<") . " '$id'")->fetchColumn() / $kmess);
                $db->exec("DELETE FROM `forum` WHERE `id` = '$id'");

                if ($posts < 2) {
                    // Пересылка на удаление всей темы
                    header('Location: index.php?act=deltema&id=' . $res['refid']);
                } else {
                    header('Location: index.php?id=' . $res['refid'] . '&page=' . $page);
                }
            } else {
                // Скрытие поста
                $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '$id' LIMIT 1");

                if ($req_f->rowCount()) {
                    // Если есть прикрепленный файл, скрываем его
                    $db->exec("UPDATE `cms_forum_files` SET `del` = '1' WHERE `post` = '$id' LIMIT 1");
                }

                if ($posts == 1) {
                    // Если это был последний пост темы, то скрываем саму тему
                    $res_l = $db->query("SELECT `refid` FROM `forum` WHERE `id` = '" . $res['refid'] . "'")->fetch();
                    $db->exec("UPDATE `forum` SET `close` = '1', `close_who` = '$login' WHERE `id` = '" . $res['refid'] . "' AND `type` = 't'");
                    header('Location: index.php?id=' . $res_l['refid']);
                } else {
                    $db->exec("UPDATE `forum` SET `close` = '1', `close_who` = '$login' WHERE `id` = '$id'");
                    header('Location: index.php?id=' . $res['refid'] . '&page=' . $page);
                }
            }
            break;

        case 'del':
            // Удаление поста, предварительное напоминание
            echo '<div class="phdr"><a href="' . $link . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Delete Message') . '</div>' .
                '<div class="rmenu"><p>';

            if ($posts == 1) {
                echo _t('WARNING!<br>This is last post of topic. By deleting this post topic will be deleted (or hidden) too') . '<br>';
            }

            echo _t('Do you really want to delete?') . '</p>' .
                '<p><a href="' . $link . '">' . _t('Cancel') . '</a> | <a href="index.php?act=editpost&amp;do=delete&amp;id=' . $id . '">' . _t('Delete') . '</a>';

            if ($rights == 9) {
                echo ' | <a href="index.php?act=editpost&amp;do=delete&amp;hide&amp;id=' . $id . '">' . _t('Hide') . '</a>';
            }

            echo '</p></div>';
            echo '<div class="phdr"><small>' . _t('After deleting, one point will be subtracted from the counter of forum posts') . '</small></div>';
            break;

        default:
            // Редактирование поста
            $msg = isset($_POST['msg']) ? functions::checkin(trim($_POST['msg'])) : '';

            if (isset($_POST['submit'])) {
                if (empty($_POST['msg'])) {
                    echo functions::display_error(_t('You have not entered the message'), '<a href="index.php?act=editpost&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
                    require('../incfiles/end.php');
                    exit;
                }

                $db->prepare('
                  UPDATE `forum` SET
                  `tedit` = ?,
                  `edit` = ?,
                  `kedit` = ?,
                  `text` = ?
                  WHERE `id` = ?
                ')->execute([
                    time(),
                    $login,
                    ($res['kedit'] + 1),
                    $msg,
                    $id,
                ]);

                header('Location: index.php?id=' . $res['refid'] . '&page=' . $page);
            } else {
                $msg_pre = functions::checkout($msg, 1, 1);

                if ($set_user['smileys']) {
                    $msg_pre = functions::smileys($msg_pre, $datauser['rights'] ? 1 : 0);
                }

                $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
                echo '<div class="phdr"><a href="' . $link . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Edit Message') . '</div>';

                if ($msg && !isset($_POST['submit'])) {
                    $user = $db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "' LIMIT 1")->fetch();
                    echo '<div class="list1">' . functions::display_user($user, ['iphide' => 1, 'header' => '<span class="gray">(' . functions::display_date($res['time']) . ')</span>', 'body' => $msg_pre]) . '</div>';
                }

                echo '<div class="rmenu"><form name="form" action="?act=editpost&amp;id=' . $id . '&amp;start=' . $start . '" method="post"><p>';
                echo bbcode::auto_bb('form', 'msg');
                echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . (empty($_POST['msg']) ? htmlentities($res['text'], ENT_QUOTES, 'UTF-8') : functions::checkout($_POST['msg'])) . '</textarea><br>';

                echo '</p><p><input type="submit" name="submit" value="' . _t('Save') . '" style="width: 107px; cursor: pointer;"/> ' .
                    ($set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer;"/>' : '') .
                    '</p></form></div>' .
                    '<div class="phdr"><a href="../help/?act=smileys">' . _t('Smilies') . '</a></div>' .
                    '<p><a href="' . $link . '">' . _t('Back') . '</a></p>';
            }
    }
} else {
    // Выводим сообщения об ошибках
    echo functions::display_error($error);
}
