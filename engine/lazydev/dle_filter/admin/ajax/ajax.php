<?php
/**
* AJAX обработчик
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

@error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
@ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);

define('DATALIFEENGINE', true);
define('ROOT_DIR', substr(dirname(__FILE__), 0, -37));
define('ENGINE_DIR', ROOT_DIR . '/engine');

use LazyDev\Filter\Ajax;
use LazyDev\Filter\Helper;

include_once ENGINE_DIR . '/lazydev/dle_filter/loader.php';

header('Content-type: text/html; charset=' . $config['charset']);
date_default_timezone_set($config['date_adjust']);
setlocale(LC_NUMERIC, 'C');

require_once DLEPlugins::Check(ROOT_DIR . '/language/' . $config['langs'] . '/website.lng');
require_once DLEPlugins::Check(ENGINE_DIR . '/modules/functions.php');
dle_session();

$user_group = get_vars('usergroup');
if (!$user_group) {
	$user_group = [];
	$db->query('SELECT * FROM ' . USERPREFIX . '_usergroups ORDER BY id ASC');
	while ($row = $db->get_row()) {
		$user_group[$row['id']] = [];
		foreach ($row as $key => $value) {
			$user_group[$row['id']][$key] = stripslashes($value);
		}
	}
	set_vars('usergroup', $user_group);
	$db->free();
}

$cat_info = get_vars('category');
if (!$cat_info) {
	$cat_info = [];
	$db->query('SELECT * FROM ' . PREFIX . '_category ORDER BY posi ASC');
	while ($row = $db->get_row()) {
		$cat_info[$row['id']] = [];
		foreach ($row as $key => $value) {
			$cat_info[$row['id']][$key] = stripslashes($value);
		}
	}
	set_vars('category', $cat_info);
	$db->free();
}

if (file_exists(DLEPlugins::Check(ROOT_DIR . '/language/' . $config['lang_' . $config['skin']] . '/website.lng'))) {
    include_once(DLEPlugins::Check(ROOT_DIR . '/language/' . $config['lang_' . $config['skin']] . '/website.lng'));
} else {
    include_once(DLEPlugins::Check(ROOT_DIR . '/language/' . $config['langs'] . '/website.lng'));
}

$is_logged = false;

require_once DLEPlugins::Check(ENGINE_DIR . '/modules/sitelogin.php');
if ($member_id['user_group'] != 1) {
    echo Helper::json(['text' => $langDleFilter['admin']['ajax']['error'], 'error' => 'true']);
    exit;
}

$action = isset($_POST['action']) ? trim(strip_tags($_POST['action'])) : false;
$dle_hash = isset($_POST['dle_hash']) ? trim(strip_tags($_POST['dle_hash'])) : false;

if (!$dle_hash || $dle_hash != $dle_login_hash) {
	echo Helper::json(['text' => $langDleFilter['admin']['ajax']['error'], 'error' => 'true']);
	exit;
}

Ajax::ajaxAction($action);