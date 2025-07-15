<?php
/**
 * Теги для страницы фильтра
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

use LazyDev\Filter\Data;
use LazyDev\Filter\Helper;
use LazyDev\Filter\Field;

include_once ENGINE_DIR . '/lazydev/dle_filter/loader.php';
include_once (DLEPlugins::Check(ENGINE_DIR . '/classes/htmlpurifier/HTMLPurifier.standalone.php'));
include_once (DLEPlugins::Check(ENGINE_DIR . '/classes/parse.class.php'));

if ($configDleFilter['new_search'] == 1) {
    Field::updateNews();
}

if ($Conditions instanceof LazyDev\Filter\Conditions && $Filter instanceof LazyDev\Filter\Filter) {
	if (is_array($allFilterData)) {
		$tpl->result['main'] = $Conditions::realize($tpl->result['main'], $allFilterData, 'dle-filter');
	}
	if (isset($Filter::$globalTag['tag'])) {
		$filterTags = array_keys($Filter::$globalTag['tag']);

		$tpl->result['main'] = preg_replace($Filter::$globalTag['block'], '\\1', $tpl->result['main']);
		$tpl->result['main'] = preg_replace($Filter::$globalTag['hide'], '', $tpl->result['main']);
	}
	$tpl->result['main'] = preg_replace('#\[not-dle-filter (.+?)\](.*?)\[\/not-dle-filter\]#is', '\\2', $tpl->result['main']);
	if (isset($Filter::$globalTag['tag'])) {
		$tpl->result['main'] = str_replace($filterTags, $Filter::$globalTag['tag'], $tpl->result['main']);
	}

	if ($Filter::$dateSlider) {
        foreach ($Filter::$dateSlider as $key => $val) {
            $news_date = $val;
            $tpl->result['main'] = preg_replace_callback('#\{dle-filter ' . $key . ' date=(.+?)\}#i', 'formdate', $tpl->result['main']);
        }
    }

    if (substr_count($tpl->result['main'], '[dle-filter declination')) {
        $tpl->result['main'] = preg_replace_callback('#\\[dle-filter declination=(.+?)\\](.*?)\\[/declination\\]#is', function ($m) {
            return Helper::declinationLazy([$m[1], $m[2]]);
        }, $tpl->result['main']);
		$tpl->result['main'] = preg_replace('#\\[dle-filter declination(.+?)\\](.*?)\\[/declination\\]#is', '', $tpl->result['main']);
    }

	$tpl->result['main'] = $Conditions::clean($tpl->result['main']);
} else {
	$tpl->result['main'] = preg_replace('#\[not-dle-filter (.+?)\](.*?)\[\/not-dle-filter\]#is', '', $tpl->result['main']);
	$tpl->result['main'] = preg_replace('#\[dle-filter(.+?)\](.*?)\[\/dle-filter\]#is', '', $tpl->result['main']);
	$tpl->result['main'] = preg_replace('#\{dle-filter(.+?)\}#is', '', $tpl->result['main']);
	$tpl->result['main'] = preg_replace('#\\[dle-filter declination(.+?)\\](.*?)\\[/declination\\]#is', '', $tpl->result['main']);
}