<?php
/**
* Настройки модуля
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

use LazyDev\Filter\Data;
use LazyDev\Filter\Admin;

$allXfield = xfieldsload();
foreach ($allXfield as $value) {
    $xfieldArray[$value[0]] = $value[1];
}

$categories = CategoryNewsSelection((empty($configDleFilter['exclude_categories']) ? 0 : $configDleFilter['exclude_categories']));
$sortField = [
	'date' => $langDleFilter['admin']['settings']['p.date'],
	'editdate' => $langDleFilter['admin']['settings']['e.editdate'],
	'title' => $langDleFilter['admin']['settings']['p.title'],
	'autor' => $langDleFilter['admin']['settings']['p.autor'],
	'rating' => $langDleFilter['admin']['settings']['e.rating'],
	'comm_num' => $langDleFilter['admin']['settings']['p.comm_num'],
	'news_read' => $langDleFilter['admin']['settings']['e.news_read']
];
if ($xfieldArray) {
	$sortField = $sortField + $xfieldArray;
	$xfieldArray = ['-' => '-'] + $xfieldArray;
}
$order = [
	'desc' => $langDleFilter['admin']['settings']['desc'],
	'asc' => $langDleFilter['admin']['settings']['asc']
];
$indexFilter = [
	'noindex' => $langDleFilter['admin']['settings']['noindex'],
	'follow' => $langDleFilter['admin']['settings']['follow'],
	'index' => $langDleFilter['admin']['settings']['index'],
];
$codeFilter = [
    'default' => $langDleFilter['admin']['settings']['default'],
    '404' => $langDleFilter['admin']['settings']['404'],
];

$excludeNews = '';
if ($configDleFilter['excludeNews']) {
    $newsId = implode(',', $configDleFilter['excludeNews']);
    $db->query("SELECT id, title FROM " . PREFIX . "_post WHERE id IN({$newsId})");
    while ($row = $db->get_row()) {
        $row['title'] = str_replace("&quot;", '\"', $row['title']);
        $row['title'] = str_replace("&#039;", "'", $row['title']);
        $row['title'] = htmlspecialchars($row['title']);
        $excludeNews .= "<option value=\"{$row['id']}\" selected>" . $row['title'] . "</option>";
    }
}

echo <<<HTML
<form action="" method="post">
    <div class="panel panel-flat">
		<div class="navbar navbar-default navbar-component navbar-xs" style="margin-bottom: 0px;">
	        <ul class="nav navbar-nav visible-xs-block">
		        <li class="full-width text-center"><a data-toggle="collapse" data-target="#navbar-filter">
		            <i class="fa fa-bars"></i></a>
                </li>
	        </ul>
            <div class="navbar-collapse collapse" id="navbar-filter">
                <ul class="nav navbar-nav">
                    <li class="active">
						<a onclick="ChangeOption(this, 'block_1');" class="tip">
                        <i class="fa fa-cog"></i> {$langDleFilter['admin']['settings']['main_settings']}</a>
                    </li>
					<li>
						<a onclick="ChangeOption(this, 'block_3');" class="tip">
                        <i class="fa fa-jsfiddle"></i> {$langDleFilter['admin']['settings']['js_settings']}</a>
                    </li>
                    <li>
						<a onclick="ChangeOption(this, 'block_4');" class="tip">
                        <i class="fa fa-superpowers"></i> {$langDleFilter['admin']['settings']['seo_settings']}</a>
                    </li>
                    <li>
						<a onclick="ChangeOption(this, 'block_2');" class="tip">
                        <i class="fa fa-ellipsis-h"></i> {$langDleFilter['admin']['settings']['integration']}</a>
                    </li>
                    <li>
						<a onclick="ChangeOption(this, 'block_5');" class="tip">
                        <i class="fa fa-chain-broken"></i> {$langDleFilter['admin']['settings']['link']}</a>
                    </li>
                    <li>
						<a onclick="ChangeOption(this, 'block_6');" class="tip">
                        <i class="fa fa-exchange"></i> {$langDleFilter['admin']['settings']['exchange']}</a>
                    </li>
                </ul>
            </div>
        </div>
		<div id="block_1">
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['settings_descr']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;

Admin::row(
    $langDleFilter['admin']['settings']['use_new_search'],
    $langDleFilter['admin']['settings']['use_new_search_descr'],
	Admin::checkBox('new_search', $configDleFilter['new_search'], 'new_search')
);
Admin::row(
    $langDleFilter['admin']['settings']['cache_filter'],
    $langDleFilter['admin']['settings']['cache_filter_descr'],
    Admin::checkBox('cache_filter', $configDleFilter['cache_filter'], 'cache_filter'),
	$langDleFilter['admin']['settings']['cache_filter_helper']
);
Admin::row(
    $langDleFilter['admin']['settings']['statistics'],
    $langDleFilter['admin']['settings']['statistics_descr'],
    Admin::checkBox('statistics', $configDleFilter['statistics'], 'statistics')
);
Admin::row(
    $langDleFilter['admin']['settings']['clear_statistics'],
    $langDleFilter['admin']['settings']['clear_statistics_descr'],
    Admin::input(['clear_statistics', 'number', $configDleFilter['clear_statistics'] ?: 0, false, false, 0, 30])
);
Admin::row(
    $langDleFilter['admin']['settings']['exclude_categories'],
    $langDleFilter['admin']['settings']['exclude_categories_descr'],
    Admin::selectTag('exclude_categories[]', $categories, $langDleFilter['admin']['settings']['categories'])
);
Admin::row(
    $langDleFilter['admin']['settings']['exclude_news'],
    $langDleFilter['admin']['settings']['exclude_news_descr'],
    "<div id=\"searchVal\">
        <select class=\"excludeNews\" id=\"excludeNews\" name=\"excludeNews[]\" multiple>{$excludeNews}</select>
    </div>"
);
Admin::row(
    $langDleFilter['admin']['settings']['search_cat'],
    $langDleFilter['admin']['settings']['search_cat_descr'],
    Admin::checkBox('search_cat', $configDleFilter['search_cat'], 'search_cat'),
    $langDleFilter['admin']['settings']['search_cat_helper']
);
Admin::row(
    $langDleFilter['admin']['settings']['search_cat_all'],
    $langDleFilter['admin']['settings']['search_cat_all_descr'],
    Admin::checkBox('search_cat_all', $configDleFilter['search_cat_all'], 'search_cat_all')
);
Admin::row(
    $langDleFilter['admin']['settings']['search_tag'],
    $langDleFilter['admin']['settings']['search_tag_descr'],
    Admin::checkBox('search_tag', $configDleFilter['search_tag'], 'search_tag'),
    $langDleFilter['admin']['settings']['search_tag_helper']
);
Admin::row(
    $langDleFilter['admin']['settings']['search_xfield'],
    $langDleFilter['admin']['settings']['search_xfield_descr'],
    Admin::checkBox('search_xfield', $configDleFilter['search_xfield'], 'search_xfield'),
    $langDleFilter['admin']['settings']['search_xfield_helper']
);
Admin::row(
    $langDleFilter['admin']['settings']['news_number'],
    $langDleFilter['admin']['settings']['news_number_descr'],
    Admin::input(['news_number', 'number', $configDleFilter['news_number'] ?: $config['news_number'], false, false, 1, 100]),
	$langDleFilter['admin']['settings']['news_number_helper']
);
Admin::row(
    $langDleFilter['admin']['settings']['max_news'],
    $langDleFilter['admin']['settings']['max_news_descr'],
    Admin::input(['max_news', 'number', $configDleFilter['max_news'] ?: 0, false, false, 0, 99999])
);
Admin::row(
    $langDleFilter['admin']['settings']['allow_main'],
    $langDleFilter['admin']['settings']['allow_main_descr'],
    Admin::checkBox('allow_main', $configDleFilter['allow_main'], 'allow_main')
);
Admin::row(
    $langDleFilter['admin']['settings']['fixed'],
    $langDleFilter['admin']['settings']['fixed_descr'],
    Admin::checkBox('fixed', $configDleFilter['fixed'], 'fixed')
);
Admin::row(
    $langDleFilter['admin']['settings']['sort_field'],
    $langDleFilter['admin']['settings']['sort_field_descr'],
    Admin::select(['sort_field', $sortField, true, $configDleFilter['sort_field'], false, false], true),
	$langDleFilter['admin']['settings']['sort_field_helper_2']
);
Admin::row(
    $langDleFilter['admin']['settings']['order'],
    $langDleFilter['admin']['settings']['order_descr'],
    Admin::select(['order', $order, true, $configDleFilter['order'], false, false])
);
Admin::row(
    $langDleFilter['admin']['settings']['filter_url'],
    $langDleFilter['admin']['settings']['filter_url_descr'],
    Admin::input(['filter_url', 'text', $configDleFilter['filter_url'] ?: 'f']),
    $langDleFilter['admin']['settings']['filter_url_helper']
);
echo <<<HTML
				</table>
			</div>
		</div>
		
	    <div id="block_3" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['settings']['js_settings']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;
Admin::row(
    $langDleFilter['admin']['settings']['ion_slider'],
    $langDleFilter['admin']['settings']['ion_slider_descr'],
    Admin::checkBox('ion_slider', $configDleFilter['ion_slider'], 'ion_slider')
);
Admin::row(
    $langDleFilter['admin']['settings']['js_select'],
    $langDleFilter['admin']['settings']['js_select_descr'],
    Admin::select(['js_select', [
        0 => $langDleFilter['admin']['settings']['no_select'],
        1 => $langDleFilter['admin']['settings']['tail_select'],
        2 => $langDleFilter['admin']['settings']['chosen_select'],
        3 => $langDleFilter['admin']['settings']['nice_select']
    ], true, $configDleFilter['js_select'], false, false])
);
Admin::row(
    $langDleFilter['admin']['settings']['only_button'],
    $langDleFilter['admin']['settings']['only_button_descr'],
    Admin::checkBox('only_button', $configDleFilter['only_button'], 'only_button')
);
Admin::row(
    $langDleFilter['admin']['settings']['ajax_form'],
    $langDleFilter['admin']['settings']['ajax_form_descr'],
    Admin::checkBox('ajax_form', $configDleFilter['ajax_form'], 'ajax_form')
);
Admin::row(
    $langDleFilter['admin']['settings']['ajax_nav'],
    $langDleFilter['admin']['settings']['ajax_nav_descr'],
    Admin::checkBox('ajax_nav', $configDleFilter['ajax_nav'], 'ajax_nav')
);
Admin::row(
    $langDleFilter['admin']['settings']['ajax_nav_page'],
    $langDleFilter['admin']['settings']['ajax_nav_page_descr'],
    Admin::checkBox('ajax_nav_page', $configDleFilter['ajax_nav_page'], 'ajax_nav_page')
);
Admin::row(
    $langDleFilter['admin']['settings']['ajax_animation'],
    $langDleFilter['admin']['settings']['ajax_animation_descr'],
    Admin::checkBox('ajax_animation', $configDleFilter['ajax_animation'], 'ajax_animation')
);
Admin::row(
    $langDleFilter['admin']['settings']['hide_loading'],
    $langDleFilter['admin']['settings']['hide_loading_descr'],
    Admin::checkBox('hide_loading', $configDleFilter['hide_loading'], 'hide_loading')
);
Admin::row(
    $langDleFilter['admin']['settings']['not_ajax_url'],
    $langDleFilter['admin']['settings']['not_ajax_url_descr'],
    Admin::checkBox('not_ajax_url', $configDleFilter['not_ajax_url'], 'not_ajax_url')
);
Admin::row(
    $langDleFilter['admin']['settings']['nav_apart'],
    $langDleFilter['admin']['settings']['nav_apart_descr'],
    Admin::checkBox('nav_apart', $configDleFilter['nav_apart'], 'nav_apart')
);
echo <<<HTML
				</table>
			</div>
		</div>
		
        <div id="block_4" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['settings']['seo_settings']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;
Admin::row(
    $langDleFilter['admin']['settings']['index_filter'],
    $langDleFilter['admin']['settings']['index_filter_descr'],
    Admin::select(['index_filter', $indexFilter, true, $configDleFilter['index_filter'], false, false])
);
Admin::row(
    $langDleFilter['admin']['settings']['index_second'],
    $langDleFilter['admin']['settings']['index_second_descr'],
    Admin::select(['index_second', $indexFilter, true, $configDleFilter['index_second'], false, false])
);
Admin::row(
    $langDleFilter['admin']['settings']['code_filter'],
    $langDleFilter['admin']['settings']['code_filter_descr'],
    Admin::select(['code_filter', $codeFilter, true, $configDleFilter['code_filter'], false, false])
);
Admin::row(
    $langDleFilter['admin']['settings']['redirect_filter'],
    $langDleFilter['admin']['settings']['redirect_filter_descr'],
    Admin::checkBox('redirect_filter', $configDleFilter['redirect_filter'], 'redirect_filter')
);
echo <<<HTML
				</table>
			</div>
		</div>
		
		<div id="block_2" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['settings']['integration_text']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;
if (file_exists(ENGINE_DIR . '/lazydev/dle_youwatch/index.php')) {
    Admin::row(
        $langDleFilter['admin']['settings']['youwatch'],
        $langDleFilter['admin']['settings']['integration_install_descr'],
        "<i class=\"fa fa-check\" style=\"font-size: 20px;color: #26a65b;\"></i>"
    );
}

if (file_exists(ENGINE_DIR . '/lazydev/dle_emote_lite/index.php')) {
    Admin::row(
        $langDleFilter['admin']['settings']['emote'],
        $langDleFilter['admin']['settings']['integration_install_descr'],
        "<i class=\"fa fa-check\" style=\"font-size: 20px;color: #26a65b;\"></i>"
    );
}

if (file_exists(ENGINE_DIR . '/lazydev/dle_conditions/dle_conditions.php')) {
    Admin::row(
        $langDleFilter['admin']['settings']['conditions'],
        $langDleFilter['admin']['settings']['integration_install_descr'],
        "<i class=\"fa fa-check\" style=\"font-size: 20px;color: #26a65b;\"></i>"
    );
}

if (file_exists(ENGINE_DIR . '/mods/miniposter/loader.php')) {
    Admin::row(
        $langDleFilter['admin']['settings']['miniposter3'],
        $langDleFilter['admin']['settings']['integration_install_descr'],
        "<i class=\"fa fa-check\" style=\"font-size: 20px;color: #26a65b;\"></i>"
    );
}

if (file_exists(ENGINE_DIR . '/mods/favorites/index.php')) {
    Admin::row(
        $langDleFilter['admin']['settings']['fav_san'],
        $langDleFilter['admin']['settings']['integration_install_descr'],
        "<i class=\"fa fa-check\" style=\"font-size: 20px;color: #26a65b;\"></i>"
    );
}
echo <<<HTML
                    <tr>
                        <td colspan="2">
                            <div class="alert alert-info alert-styled-left alert-arrow-left alert-component">{$langDleFilter['admin']['settings']['integration_descr']}</div>
                        </td>
                    </tr>
				</table>
			</div>
		</div>

        <div id="block_5" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['settings']['link']}</div>
			<div class="table-responsive">
				<table class="table" id="tableTemplate">
				    <tr>
				        <th class="text-center">{$langDleFilter['admin']['settings']['param']} <i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right" data-rel="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="{$langDleFilter['admin']['settings']['param_descr']}" data-original-title="" title=""></i></th>
				        <th class="text-center">{$langDleFilter['admin']['settings']['template']} <i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right" data-rel="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="{$langDleFilter['admin']['settings']['template_descr']}" data-original-title="" title=""></i></th>
				        <th class="text-center"><i class="fa fa-trash"></i></th>
                    </tr>
HTML;

if ($configDleFilter['link']) {
    $indexList = 1;
    foreach ($configDleFilter['link']['p'] as $key => $value) {
echo <<<HTML
                    <tr>
                        <td>
                            <input type="text" class="form-control" name="link[p][{$indexList}]" placeholder="{$langDleFilter['admin']['settings']['param']}" value="{$value}">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="link[a][{$indexList}]" placeholder="{$langDleFilter['admin']['settings']['template']}" value="{$configDleFilter['link']['a'][$key]}">
                        </td>
                        <td class="text-center">
                            <a onclick="deleteRule(this); return false;" href="#" style="color: #D32F2F">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
HTML;
        $indexList++;
    }
} else {
echo <<<HTML
                    <tr>
                        <td>
                            <input type="text" class="form-control" name="link[p][1]" placeholder="{$langDleFilter['admin']['settings']['param']}">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="link[a][1]" placeholder="{$langDleFilter['admin']['settings']['template']}">
                        </td>
                        <td class="text-center">
                            <a onclick="deleteRule(this); return false;" href="#" style="color: #D32F2F">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
HTML;
}
echo <<<HTML
				</table>
			</div>
		</div>

        <div id="block_6" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['settings']['exchange']}</div>
			<div class="table-responsive">
				<table class="table" id="tableExchange">
				    <tr>
				        <th class="text-center">{$langDleFilter['admin']['settings']['param_in_filter']} <i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right" data-rel="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="{$langDleFilter['admin']['settings']['param_in_filter_descr']}" data-original-title="" title=""></i></th>
				        <th class="text-center">{$langDleFilter['admin']['settings']['add_param']} <i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right" data-rel="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="{$langDleFilter['admin']['settings']['add_param_descr']}" data-original-title="" title=""></i></th>
				        <th class="text-center"><i class="fa fa-trash"></i></th>
                    </tr>
HTML;

if ($configDleFilter['exchange']) {
    $indexList = 1;
    foreach ($configDleFilter['exchange']['p'] as $key => $value) {
        echo <<<HTML
                    <tr>
                        <td>
                            <input type="text" class="form-control" name="exchange[p][{$indexList}]" placeholder="{$langDleFilter['admin']['settings']['param_in_filter']}" value="{$value}">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="exchange[a][{$indexList}]" placeholder="{$langDleFilter['admin']['settings']['add_param']}" value="{$configDleFilter['exchange']['a'][$key]}">
                        </td>
                        <td class="text-center">
                            <a onclick="deleteRule(this); return false;" href="#" style="color: #D32F2F">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
HTML;
        $indexList++;
    }
} else {
    echo <<<HTML
                    <tr>
                        <td>
                            <input type="text" class="form-control" name="exchange[p][1]" placeholder="{$langDleFilter['admin']['settings']['param_in_filter']}">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="exchange[a][1]" placeholder="{$langDleFilter['admin']['settings']['add_param']}">
                        </td>
                        <td class="text-center">
                            <a onclick="deleteRule(this); return false;" href="#" style="color: #D32F2F">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
HTML;
}
echo <<<HTML
				</table>
			</div>
		</div>

		<div class="panel-footer">
			<button type="submit" class="btn bg-teal btn-raised position-left" style="background-color:#1e8bc3;">{$langDleFilter['admin']['save']}</button>
			<a href="#" onclick="addRule(); return false;" id="addTemplate" class="btn bg-teal btn-raised" style="display:none;background-color:#e08283;float: right;">{$langDleFilter['admin']['settings']['add_link']}</a>
			<a href="#" onclick="addExchange(); return false;" id="addExchange" class="btn bg-teal btn-raised" style="display:none;background-color:#e08283;float: right;">{$langDleFilter['admin']['settings']['add_param_button']}</a>
		</div>
    </div>
</form>
HTML;

$jsAdminScript[] = <<<HTML

function addRule() {
    var i = $("#tableTemplate tr").length;
    $("#tableTemplate").append('<tr><td><input type="text" class="form-control" placeholder="{$langDleFilter['admin']['settings']['param']}" name="link[p]['+i+']"></td><td><input type="text" class="form-control" placeholder="{$langDleFilter['admin']['settings']['template']}" name="link[a]['+i+']"></td><td class="text-center"><a onclick="deleteRule(this); return false;" href="#" style="color: #D32F2F"><i class="fa fa-trash"></i></a></td></tr>');
}
function addExchange() {
    var i = $("#tableExchange tr").length;
    $("#tableExchange").append('<tr><td><input type="text" class="form-control" placeholder="{$langDleFilter['admin']['settings']['param_in_filter']}" name="exchange[p]['+i+']"></td><td><input type="text" class="form-control" placeholder="{$langDleFilter['admin']['settings']['add_param']}" name="exchange[a]['+i+']"></td><td class="text-center"><a onclick="deleteRule(this); return false;" href="#" style="color: #D32F2F"><i class="fa fa-trash"></i></a></td></tr>');
}
function deleteRule(e) {
    $(e).parent().parent().remove();
}
$(function() {
    $('body').on('submit', 'form', function(e) {
        coreAdmin.ajaxSend($('form').serialize(), 'saveOptions', false);
		return false;
    });
});
function ChangeOption(obj, selectedOption) {
    $('#navbar-filter li').removeClass('active');
    $(obj).parent().addClass('active');
    $('[id*=block_]').hide();
    $('#' + selectedOption).show();

    selectedOption == 'block_5' ? $('#addTemplate').show() : $('#addTemplate').hide();
    selectedOption == 'block_6' ? $('#addExchange').show() : $('#addExchange').hide();    
    
    return false;
}

let excludeNews = tail.select('.excludeNews', {
    search: true,
    multiSelectAll: true,
    placeholder: "{$langDleFilter['admin']['seo']['enter']}",
    classNames: "default white",
    multiContainer: true,
    multiShowCount: false,
    locale: "{$_COOKIE['lang_dle_filter']}"
});

$('#searchVal .search-input').autocomplete({
    source: function(request, response) {
        let dataName = $('#searchVal .search-input').val();
        $.post('engine/lazydev/dle_filter/admin/ajax/ajax.php', {dle_hash: "{$dle_login_hash}", query: dataName, action: 'findNews'}, function(data) {
            data = jQuery.parseJSON(data);
            let newAddItem = {};

            data.forEach(function(item) {
                newAddItem[item.value] = { key: item.value, value: item.name, description: '' };
            });
            
            [].map.call(excludeNews.e.querySelectorAll("[data-select-option='add']"), function(item) {
                item.parentElement.removeChild(item);
            });
            [].map.call(excludeNews.e.querySelectorAll("[data-select-optgroup='add']"), function(item) {
                item.parentElement.removeChild(item);
            });
            
            let getOp = excludeNews.options.items['#'];
            $.each(getOp, function(index, value) {
                if (value.selected) {
                    newAddItem[value.key] = value;
                }
            });
            
            let options = new tail.select.options(excludeNews.e, excludeNews);
            options.add(newAddItem);
            
            let map = {};
            $(options.element).find('option').each(function() {
                if (map[this.value]) {
                    $(this).remove();
                }
                map[this.value] = true;
            });
            
            excludeNews.options = options;
            excludeNews.query(dataName);
        });
        
    }
});
HTML;

?>