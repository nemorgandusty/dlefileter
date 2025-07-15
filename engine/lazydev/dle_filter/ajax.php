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
define('ROOT_DIR', substr(dirname(__FILE__), 0, -26));
define('ENGINE_DIR', ROOT_DIR . '/engine');

use LazyDev\Filter\Helper;
use LazyDev\Filter\Data;

include_once ENGINE_DIR . '/lazydev/dle_filter/loader.php';

header('Content-type: text/html; charset=' . $config['charset']);
date_default_timezone_set($config['date_adjust']);
setlocale(LC_NUMERIC, 'C');

require_once DLEPlugins::Check(ENGINE_DIR . '/modules/functions.php');

if ($_REQUEST['skin']) {
    $_REQUEST['skin'] = $_REQUEST['dle_skin'] = trim(totranslit($_REQUEST['skin'], false, false));
}

if ($_REQUEST['dle_skin']) {
    $_REQUEST['dle_skin'] = trim(totranslit($_REQUEST['dle_skin'], false, false));
    if ($_REQUEST['dle_skin'] && @is_dir(ROOT_DIR . '/templates/' . $_REQUEST['dle_skin'])) {
        $config['skin'] = $_REQUEST['dle_skin'];
    } else {
        $_REQUEST['dle_skin'] = $_REQUEST['skin'] = $config['skin'];
    }
} elseif ($_COOKIE['dle_skin']) {
    $_COOKIE['dle_skin'] = trim(totranslit((string)$_COOKIE['dle_skin'], false, false));

    if ($_COOKIE['dle_skin'] && is_dir(ROOT_DIR . '/templates/' . $_COOKIE['dle_skin'])) {
        $config['skin'] = $_COOKIE['dle_skin'];
    }
}

if ($config['lang_' . $config['skin']] && file_exists( DLEPlugins::Check(ROOT_DIR . '/language/' . $config['lang_' . $config['skin']] . '/website.lng'))) {
    include_once (DLEPlugins::Check(ROOT_DIR . '/language/' . $config['lang_' . $config['skin']] . '/website.lng'));
} else {
    include_once (DLEPlugins::Check(ROOT_DIR . '/language/' . $config['langs'] . '/website.lng'));
}

if (!$config['http_home_url']) {
    $config['http_home_url'] = explode('engine/lazydev/dle_filter/ajax.php', $_SERVER['PHP_SELF']);
    $config['http_home_url'] = reset($config['http_home_url']);
}

$isSSL = Helper::ssl();

if (strpos($config['http_home_url'], $_SERVER['HTTP_HOST']) === false) {
    if (strpos($config['http_home_url'], '//') === 0) {
        $config['http_home_url'] = '//' . $_SERVER['HTTP_HOST'];
    } elseif (strpos($config['http_home_url'], '/') === 0) {
        $config['http_home_url'] = '/' . $_SERVER['HTTP_HOST'];
    } elseif($isSSL && stripos($config['http_home_url'], 'http://') !== false || !$isSSL) {
        $config['http_home_url'] = 'http://' . $_SERVER['HTTP_HOST'];
    } elseif($isSSL) {
        $config['http_home_url'] = 'https://' . $_SERVER['HTTP_HOST'];
    }

    $config['http_home_url'] .= '/';
}

if (strpos($config['http_home_url'], '//') === 0) {
    $config['http_home_url'] = $isSSL ? $config['http_home_url'] = 'https:' . $config['http_home_url'] : $config['http_home_url'] = 'http:' . $config['http_home_url'];
} elseif (strpos($config['http_home_url'], '/') === 0) {
    $config['http_home_url'] = $isSSL ? $config['http_home_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $config['http_home_url'] : 'http://' . $_SERVER['HTTP_HOST'] . $config['http_home_url'];
} elseif($isSSL && stripos($config['http_home_url'], 'http://') !== false) {
    $config['http_home_url'] = str_replace( 'http://', 'https://', $config['http_home_url']);
}

if (substr($config['http_home_url'], -1, 1) != '/') {
    $config['http_home_url'] .= '/';
}

require_once DLEPlugins::Check(ENGINE_DIR . '/classes/templates.class.php');
dle_session();

$tpl = new dle_template();
if (($config['allow_smartphone'] && !$_SESSION['mobile_disable'] && $tpl->smartphone) || $_SESSION['mobile_enable']) {
    if (@is_dir(ROOT_DIR . '/templates/smartphone')) {
        $config['skin'] = 'smartphone';
        $smartphone_detected = true;
        if ($config['allow_comments_wysiwyg'] > 0) {
            $config['allow_comments_wysiwyg'] = 0;
        }
    }
}

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

define('TEMPLATE_DIR', ROOT_DIR . '/templates/' . $config['skin']);

$PHP_SELF = $config['http_home_url'] . 'index.php';

$is_logged = false;
require_once DLEPlugins::Check(ENGINE_DIR . '/modules/sitelogin.php');
if (!$is_logged) {
	$member_id['user_group'] = 5;
}

$dleFilterVars = ['data' => $_POST['data'], 'url' => $_POST['url']];

$dleFilterVars['dle_hash'] = trim(strip_tags($_POST['dle_hash']));
$dleFilterVars['ajax'] = true;

if (!$config['allow_registration']) {
    $dle_login_hash = sha1(SECURE_AUTH_KEY . $_IP);
}

if ($dleFilterVars['dle_hash'] != $dle_login_hash) {
	echo Helper::json(['text' => $langDleFilter['admin']['ajax']['error'], 'error' => 'true']);
	exit;
}

$tpl->dir = TEMPLATE_DIR;

$banner_in_news = $banners = [];
if ($config['allow_banner']) {
    include_once(DLEPlugins::Check(ENGINE_DIR . '/modules/banners.php'));
}

include ENGINE_DIR . '/lazydev/dle_filter/index.php';
$url_page = Helper::cleanSlash($url_page) . '/';
$infoArray = [];
if ($tpl->result['content']) {
    if (file_exists(ENGINE_DIR . '/mods/miniposter/loader.php')) {
        require_once ENGINE_DIR . '/mods/miniposter/loader.php';
        (new Miniposter())->build($tpl->result['content']);
    }

    if (file_exists(ENGINE_DIR . '/mods/favorites/index.php')) {
        require_once ENGINE_DIR . '/mods/favorites/class.favorites.php';
        $favmod = new Sandev\Favorites;
        $favmod->setContent($tpl->result['content']);
    }

    if (file_exists(ENGINE_DIR . '/lazydev/dle_youwatch/index.php')) {
        require_once ENGINE_DIR . '/lazydev/dle_youwatch/loader.php';
        $tpl->result['content'] = LazyDev\YouWatch\Watch::tags($tpl->result['content']);
    }

    if (empty($_POST['dleFilterJSData']['highslide'])) {
		if ($config['version_id'] < 16.0) {
			$gallerySrc = <<<HTML
<script src="/engine/classes/highslide/highslide.js"></script>
HTML;
			$tpl->result['content'] = $gallerySrc . $tpl->result['content'];

			$dimming = '';
			if ($config['thumb_dimming']) {
				$dimming = "hs.dimmingOpacity = 0.60;";
			}

			$gallery = '';
			if ($config['thumb_gallery']) {
				$gallery = "hs.slideshowGroup='fullnews'; hs.addSlideshow({slideshowGroup: 'fullnews', interval: 4000, repeat: false, useControls: true, fixedControls: 'fit', overlayOptions: { opacity: .75, position: 'bottom center', hideOnMouseOut: true } });";
			}

			switch ($config['outlinetype']) {
				case 1:
					$type = "hs.wrapperClassName = 'wide-border';";
					break;
				case 2:
					$type = "hs.wrapperClassName = 'borderless';";
					break;
				case 3:
					$type = "hs.wrapperClassName = 'less';\nhs.outlineType = null;";
					break;
				default:
					$type = "hs.wrapperClassName = 'rounded-white';\nhs.outlineType = 'rounded-white';";
					break;
			}

			$onload_scripts[] = <<<HTML
hs.graphicsDir = '{$config['http_home_url']}engine/classes/highslide/graphics/';
{$type}
hs.numberOfImagesToPreload = 0;
hs.captionEval = 'this.thumb.alt';
hs.showCredits = false;
hs.align = 'center';
hs.transitions = ['expand', 'crossfade'];
{$dimming}
hs.lang = { loadingText : '{$lang['loading']}', playTitle : '{$lang['thumb_playtitle']}', pauseTitle:'{$lang['thumb_pausetitle']}', previousTitle : '{$lang['thumb_previoustitle']}', nextTitle :'{$lang['thumb_nexttitle']}',moveTitle :'{$lang['thumb_movetitle']}', closeTitle :'{$lang['thumb_closetitle']}',fullExpandTitle:'{$lang['thumb_expandtitle']}',restoreTitle:'{$lang['thumb_restore']}',focusTitle:'{$lang['thumb_focustitle']}',loadingTitle:'{$lang['thumb_cancel']}'
};
{$gallery}
HTML;
		}
    }

    if (empty($_POST['dleFilterJSData']['pre'])) {
        $preSrc = <<<HTML
<script src="/engine/classes/highlight/highlight.code.js"></script>
HTML;
        $tpl->result['content'] = $preSrc . $tpl->result['content'];
    }

    if (empty($_POST['dleFilterJSData']['plyr_player']) && empty($_POST['dleFilterJSData']['dle_player']) && $config['version_id'] >= 14.0) {
        if ($config['version_id'] >= 14.1 && empty($_POST['dleFilterJSData']['hls']) && (strpos($tpl->result['content'], '.m3u8') !== false || strpos($tpl->copy_template, '.m3u8') !== false)) {
            $hlsSrc = <<<HTML
<script src="{$config['http_home_url']}engine/classes/html5player/hls.js"></script>
HTML;
            $tpl->result['content'] = $hlsSrc . $tpl->result['content'];
        }
        $plyrSrc = <<<HTML
<link href="{$config['http_home_url']}engine/classes/html5player/plyr.css" type="text/css" rel="stylesheet">	
<script src="{$config['http_home_url']}engine/classes/html5player/plyr.js"></script>
HTML;
        $tpl->result['content'] = $plyrSrc . $tpl->result['content'];
        $infoArray['player'] = 'plyr';
    } elseif ($config['version_id'] < 14.0 && empty($_POST['dleFilterJSData']['dle_player'])) {
        $plyrSrc = <<<HTML
<link href="{$config['http_home_url']}engine/classes/html5player/player.css" type="text/css" rel="stylesheet">	
<script src="{$config['http_home_url']}engine/classes/html5player/player.js"></script>
HTML;
        $tpl->result['content'] = $plyrSrc . $tpl->result['content'];
        $infoArray['player'] = 'html';
    }

    $tpl->result['content'] = preg_replace_callback("#slideshowGroup\: '(.+?)'#",
        function ($matches) {
            global $onload_scripts;
            $matches[1] = totranslit(trim($matches[1]));
            $onload_scripts[$matches[1]] = "hs.addSlideshow({slideshowGroup: '{$matches[1]}', interval: 4000, repeat: false, useControls: true, fixedControls: 'fit', overlayOptions: { opacity: .75, position: 'bottom center', hideOnMouseOut: true } });";
            return $matches[0];
        },
        $tpl->result['content']);

    $tpl->copy_template = preg_replace_callback("#slideshowGroup\: '(.+?)'#",
        function ($matches) {
            global $onload_scripts;
            $matches[1] = totranslit(trim($matches[1]));
            $onload_scripts[$matches[1]] = "hs.addSlideshow({slideshowGroup: '{$matches[1]}', interval: 4000, repeat: false, useControls: true, fixedControls: 'fit', overlayOptions: { opacity: .75, position: 'bottom center', hideOnMouseOut: true } });";
            return $matches[0];
        },
        $tpl->copy_template);

    if (is_array($onload_scripts) && count($onload_scripts)) {
        $onload_scripts = implode("\n", $onload_scripts);
        $tpl->result['content'] .= <<<HTML
<script>
    jQuery(function($) {
        {$onload_scripts}
    });
</script>
HTML;
    }
}

if (!$infoArray['player']) {
    if ((empty($_POST['dleFilterJSData']['plyr_player']) && empty($_POST['dleFilterJSData']['dle_player']) || !empty($_POST['dleFilterJSData']['plyr_player'])) && $config['version_id'] >= 14.0) {
        $infoArray['player'] = 'plyr';
    } elseif ($config['version_id'] < 14.0) {
        $infoArray['player'] = 'html';
    }
}

$tpl->result['content'] = str_ireplace('{THEME}', $config['http_home_url'] . 'templates/' . $config['skin'], $tpl->result['content']);

if ($is_logged && stripos($tpl->result['content'], '-favorites-') !== false) {
    $fav_arr = explode(',', $member_id['favorites']);

    foreach ($fav_arr as $fav_id) {
        $tpl->result['content'] = str_replace("{-favorites-{$fav_id}}", "<a id=\"fav-id-{$fav_id}\" class=\"favorite-link del-favorite\" href=\"{$PHP_SELF}?do=favorites&amp;doaction=del&amp;id={$fav_id}\"><img src=\"{$config['http_home_url']}templates/{$config['skin']}/dleimages/minus_fav.gif\" onclick=\"doFavorites('{$fav_id}', 'minus', 0); return false;\" title=\"{$lang['news_minfav']}\" alt=\"\"></a>", $tpl->result['content']);
        $tpl->result['content'] = str_replace("[del-favorites-{$fav_id}]", "<a id=\"fav-id-{$fav_id}\" onclick=\"doFavorites('{$fav_id}', 'minus', 1); return false;\" href=\"{$PHP_SELF}?do=favorites&amp;doaction=del&amp;id={$fav_id}\">", $tpl->result['content']);
        $tpl->result['content'] = str_replace ("[/del-favorites-{$fav_id}]", "</a>", $tpl->result['content']);
        $tpl->result['content'] = preg_replace("'\\[add-favorites-{$fav_id}\\](.*?)\\[/add-favorites-{$fav_id}\\]'is", '', $tpl->result['content']);
    }

    $tpl->result['content'] = preg_replace("'\\{-favorites-(\d+)\\}'i", "<a id=\"fav-id-\\1\" class=\"favorite-link add-favorite\" href=\"{$PHP_SELF}?do=favorites&amp;doaction=add&amp;id=\\1\"><img src=\"{$config['http_home_url']}templates/{$config['skin']}/dleimages/plus_fav.gif\" onclick=\"doFavorites('\\1', 'plus', 0); return false;\" title=\"{$lang['news_addfav']}\" alt=\"\"></a>", $tpl->result['content']);
    $tpl->result['content'] = preg_replace("'\\[add-favorites-(\d+)\\]'i", "<a id=\"fav-id-\\1\" onclick=\"doFavorites('\\1', 'plus', 1); return false;\" href=\"{$PHP_SELF}?do=favorites&amp;doaction=add&amp;id=\\1\">", $tpl->result['content']);
    $tpl->result['content'] = preg_replace("'\\[/add-favorites-(\d+)\\]'i", "</a>", $tpl->result['content']);
    $tpl->result['content'] = preg_replace("'\\[del-favorites-(\d+)\\](.*?)\\[/del-favorites-(\d+)\\]'si", "", $tpl->result['content']);
}

if (is_array($banners) && count($banners) && $config['allow_banner']) {
    foreach ($banners as $name => $value) {
        $tpl->result['content'] = str_replace ('{banner_' . $name . '}', $value, $tpl->result['content']);
        if ($value) {
            $tpl->result['content'] = str_replace('[banner_' . $name . ']', '', $tpl->result['content']);
            $tpl->result['content'] = str_replace('[/banner_' . $name . ']', '', $tpl->result['content']);
        }
    }
}

$tpl->result['content'] = preg_replace("'{banner_(.*?)}'si", '', $tpl->result['content']);
$tpl->result['content'] = preg_replace("'\\[banner_(.*?)\\](.*?)\\[/banner_(.*?)\\]'si", '', $tpl->result['content']);
$tpl->result['navigation'] = $tpl->result['navigation'] == '<!--ENGINE_NAVIGATION--><!--/ENGINE_NAVIGATION-->' ? '' : $tpl->result['navigation'];
$response = [
    'content' => $tpl->result['content'],
    'nav' => $tpl->result['navigation'],
    'url' => $clean_url,
    'title' => $metatags['title'],
    'speedbar' => $tpl->result['speedbar'],
    'clean' => $clean_url,
];
$response = $response + $infoArray;

echo Helper::json($response);