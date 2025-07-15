<?php
/**
* Главная страница админ панель
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

use LazyDev\Filter\Admin;

echo <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">{$langDleFilter['admin']['other']['block_menu']}</div>
    <div class="list-bordered">
HTML;
echo Admin::menu([
    [
        'link' => '?mod=' . $modLName . '&action=settings',
        'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/settings.png',
        'title' => $langDleFilter['admin']['settings_title'],
        'descr' => $langDleFilter['admin']['settings_descr'],
    ],
	[
        'link' => '?mod=' . $modLName . '&action=statistics',
        'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/statistics.png',
        'title' => $langDleFilter['admin']['statistics_title'],
        'descr' => $langDleFilter['admin']['statistics_descr'],
    ],
	[
        'link' => '?mod=' . $modLName . '&action=fields',
        'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/fields.png',
        'title' => $langDleFilter['admin']['fields_title'],
        'descr' => $langDleFilter['admin']['fields_descr'],
    ],
    [
        'link' => '?mod=' . $modLName . '&action=page',
        'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/page.png',
        'title' => $langDleFilter['admin']['page_title'],
        'descr' => $langDleFilter['admin']['page_descr'],
    ]
]);
echo <<<HTML
    </div>
</div>
HTML;

?>