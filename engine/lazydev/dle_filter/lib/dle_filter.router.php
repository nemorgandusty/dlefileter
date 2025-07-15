<?php
/**
 * Роутер для дополнительных страниц фильтра
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/


if (!defined('DATALIFEENGINE')) {
    header('HTTP/1.1 403 Forbidden');
    die('Hacking attempt!');
}

if ($_REQUEST['do'] != 'search') {
	$dleFilterPage = include ENGINE_DIR . '/lazydev/dle_filter/data/pages.php';
	$cleanUrlFilter = explode('/page/', $_SERVER['REQUEST_URI'])[0];
	$checkPageFilter = false;
	$cleanUrlFilter = rtrim(ltrim($cleanUrlFilter, '/'), '/');
	if (is_array($dleFilterPage)) {
		$checkPageFilter = array_filter($dleFilterPage, function ($a) use ($cleanUrlFilter) {
			return $a['page'] == $cleanUrlFilter;
		});
	}

	if (is_array($checkPageFilter) && count($checkPageFilter)) {
		$checkPageFilter = array_values($checkPageFilter);
		$do = $dle_module = 'dle_filter';
		$category_id = false;
		$_GET['category'] = $category = '';
		$dleFilterVars['page'] = true;
		$checkPageFilter[0]['page'] = $db->safesql($checkPageFilter[0]['page']);
		$dleFilterVars['crown'] = $db->super_query("SELECT * FROM " . PREFIX . "_dle_filter_pages WHERE page_url='{$checkPageFilter[0]['page']}' AND approve");
		$dleFilterVars['data'] = $dleFilterVars['crown']['filter_url'];
		if ($cstart > 0) {
			$dleFilterVars['data'] .= 'page/' . $cstart . '/';
		}

		if (!$dleFilterVars['crown']) {
			unset($dleFilterVars);
			$do = $dle_module = 'cat';
			if (is_array($checkPageFilter) && isset($checkPageFilter[0]['page'])) {
				$_GET['category'] = $category = $checkPageFilter[0]['page'];
			}
		}
	}
}