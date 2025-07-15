<?php
/**
* Админ панель
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
	header('HTTP/1.1 403 Forbidden');
	header('Location: ../../');
	die('Hacking attempt!');
}

use LazyDev\Filter\Data;

include realpath(__DIR__ . '/..') . '/loader.php';

$jsAdminScript = [];
$additionalJsAdminScript = [];

$action = strip_tags($_GET['action']) ?: 'main';
$action = totranslit($action, true, false);

$configVar = Data::receive('config');

$speedbar = '<li><i class="fa fa-home position-left"></i>';

$speedbar .= $action == 'main' ? $langDleFilter['admin']['speedbar_main'] : '<a href="?mod=' . $modLName . '" style="color:#2c82c9">' . $langDleFilter['admin']['speedbar_main'] . '</a>';

$speedbar .= '</li>';

if ($action == 'page') {
    if ($_GET['add'] == 'yes') {
        $speedbar .= '<li><a href="?mod=' . $modLName . '&action=page" style="color:#2c82c9">' . $langDleFilter['admin']['speedbar_' . $action] . '</a></li>';
        if ($_GET['id'] > -1) {
            $speedbar .= '<li>' . $langDleFilter['admin']['speedbar_page_edit'] . '</li>';
        } else {
            $speedbar .= '<li>' . $langDleFilter['admin']['speedbar_page_add'] . '</li>';
        }
    } else {
        $speedbar .= '<li>' . $langDleFilter['admin']['speedbar_' . $action] . '</li>';
    }
} elseif ($action !== 'main') {
    $speedbar .= '<li>' . $langDleFilter['admin']['speedbar_' . $action] . '</li>';
}

include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/template/main.php';
if (file_exists(ENGINE_DIR . '/lazydev/' . $modLName . '/admin/' . $action . '.php')) {
    include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/' . $action . '.php';
}
include ENGINE_DIR . '/lazydev/' . $modLName . '/admin/template/footer.php';

?>