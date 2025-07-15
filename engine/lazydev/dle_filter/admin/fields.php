<?php
/**
* Настройка дополнительных полей в фильтре
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

use LazyDev\Filter\Admin;
use LazyDev\Filter\Data;

echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component">
	{$langDleFilter['admin']['fields']['triggers_info']}
</div>
HTML;


$allXfield = xfieldsload();
$fieldsVar = Data::receive('fields');
$getTrigger = $db->super_query("SHOW TRIGGERS WHERE `Trigger` = 'filter_news_insert'");
$checkOk = '<span style="font-size: 0px;" class="badge badge-danger"><i class="fa fa-close"></i></span>';
$progress = $langDleFilter['admin']['fields']['not_start'];
if ($getTrigger['Trigger']) {
    $checkOk = '<span style="font-size: 0px;" class="badge badge-success"><i class="fa fa-check"></i></span>';
    $progress = $langDleFilter['admin']['fields']['end_progress'];
}
    if ($allXfield) {
        echo <<<HTML
<div class="panel panel-flat">
	<form action="" method="post" id="fieldSettings">
		<div class="panel-body" style="font-size:20px; font-weight:bold;border-bottom: 1px solid #ddd;">{$langDleFilter['admin']['fields_descr']}</div>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>{$langDleFilter['admin']['fields']['name']}</th>
						<th>{$langDleFilter['admin']['fields']['id']}</th>
						<th>{$langDleFilter['admin']['fields']['type']}</th>
						<th>{$langDleFilter['admin']['fields']['status']}</th>
					</tr>
				</thead>
				<tbody>
HTML;

        $fieldsSet = [];
        foreach ($allXfield as $value) {
            if (!$fieldsVar['status'][$value[0]]) {
                $fieldsSet[$value[0]]['off'] = 'checked';
            } else {
                $fieldsSet[$value[0]][$fieldsVar['status'][$value[0]]] = 'checked';
            }
            echo <<<HTML
                <tr>
                    <td>{$value[1]}</td>
                    <td>{$value[0]}</td>
                    <td>{$langDleFilter['admin']['fields']['xfields'][$value[3]]}</td>
                    <td style="width:450px">
                        <input class="statusn" id="number[{$value[0]}]" value="number" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['number']}>
                        <label class="status" for="number[{$value[0]}]">{$langDleFilter['admin']['fields']['number']}</label>
                        <input class="statusd" id="double[{$value[0]}]" value="double" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['double']}>
                        <label class="status" for="double[{$value[0]}]">{$langDleFilter['admin']['fields']['double']}</label>
                        <input class="statust" id="text[{$value[0]}]" value="text" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['text']}>
                        <label class="status" for="text[{$value[0]}]">{$langDleFilter['admin']['fields']['text']}</label>
                        <input class="statusf" id="off[{$value[0]}]" value="off" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['off']}>
                        <label class="status" for="off[{$value[0]}]">{$langDleFilter['admin']['fields']['off']}</label>
                    </td>
                </tr>
HTML;
        }
        echo <<<HTML
				</tbody>
			</table>
		</div>
	</form>
	<div class="panel-body" style="font-size:20px; font-weight:bold;border-bottom: 1px solid #ddd;">{$langDleFilter['admin']['fields']['head_news']}</div>
HTML;

        $getCountNews = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_post")['count'];
        $getCountAlready = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_dle_filter_news")['count'];
        echo <<<HTML
	<div class="panel-body">
{$langDleFilter['admin']['fields']['news_descr']}
</div>
<div style="margin-bottom: 0!important;" class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left text-size-small">{$langDleFilter['admin']['fields']['info_text']}</div>
	<div class="panel-body">
		<div class="progress">
			<div id="progressbar" class="progress-bar progress-blue" style="width:0%;"><span></span></div>
		</div>
	</div>
	<div class="panel-body">
		{$langDleFilter['admin']['fields']['news_count']} {$getCountNews}<br>
		{$langDleFilter['admin']['fields']['already_done']} {$getCountAlready}<br>
		{$langDleFilter['admin']['fields']['now_check']} <span class="text-danger"><span id="newscount">0</span></span><br>
		{$langDleFilter['admin']['fields']['result_check']} <span id="progress">{$progress}</span>
	</div>
	<div class="panel-body">
		{$langDleFilter['admin']['fields']['save_fields']}&nbsp;&nbsp;&nbsp;<span id="save_fields" style="margin-left: 2px;">{$checkOk}</span><br>
		{$langDleFilter['admin']['fields']['create_table']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id="create_table">{$checkOk}</span><br>
		{$langDleFilter['admin']['fields']['insert_data']}&nbsp;&nbsp;&nbsp;<span id="insert_data">{$checkOk}</span><br>
		{$langDleFilter['admin']['fields']['create_triggers']}&nbsp;<span id="create_triggers">{$checkOk}</span>
	</div>
	<div class="panel-footer">
		<button id="saveFieldsButt" type="submit" class="btn bg-teal btn-sm"><i class="fa fa-floppy-o position-left"></i>{$langDleFilter['admin']['save']}</button>
	</div>
	<input type="hidden" id="setOk" name="setOk" value="0">
</div>
HTML;

$jsAdminScript[] = <<<HTML

let totalNews = {$getCountNews};
let okStatus = '<span style="font-size: 0px;" class="badge badge-success"><i class="fa fa-check"></i></span>';
let badStatus = '<span style="font-size: 0px;" class="badge badge-danger"><i class="fa fa-close"></i></span>';

$(function() {
    $('body').on('click', '#saveFieldsButt', function(e) {
		e.preventDefault();
		let data = $('form#fieldSettings').serialize();
        $.post('engine/lazydev/' + coreAdmin.mod + '/admin/ajax/ajax.php', {data: data, action: 'saveFields', dle_hash: dle_login_hash}, function(info) {
            info = jQuery.parseJSON(info);
            if (info.status == 'ok') {
                coreAdmin.alert(info);
				Growl.info({text: '{$langDleFilter['admin']['fields']['setNews']}'});
				$('#save_fields').html(okStatus);
				$('#saveFieldsButt').attr('disabled', 'disabled');
				setTable();
            } else {
				$('#save_fields').html(badStatus);
				$('#saveFieldsButt').attr('disabled', false);
			}
        });
		return false;
    });
});

function setTable() {
	$.post('engine/lazydev/' + coreAdmin.mod + '/admin/ajax/ajax.php', {action: 'setTable', dle_hash: dle_login_hash}, function(info) {
		info = jQuery.parseJSON(info);
		if (info.status == 'ok') {
			coreAdmin.alert(info);
			$('#create_table').html(okStatus);
			setNewsData();
		} else if (info.status == 'off') {
			coreAdmin.alert(info);
			$('#create_table').html(okStatus);
			$('#saveFieldsButt').attr('disabled', false);
		} else {
			$('#create_table').html(badStatus);
			$('#saveFieldsButt').attr('disabled', false);
		}
	});

	return false;
}

function setNewsData() {
	$('#progress').html('{$langDleFilter['admin']['fields']['start_check']}');

	let startCount = $('#setOk').val();
	setNews(startCount);
	
	return false;
}

function setNews(startCount) {
	Growl.info({
		text: '{$langDleFilter['admin']['fields']['start_data']}'
	});
	$.post('engine/lazydev/' + coreAdmin.mod + '/admin/ajax/ajax.php', {startCount: startCount, action: 'setNews', dle_hash: dle_login_hash}, function(data) {
		if (data) {
			if (data.status == 'ok') {
				$('#newscount').html(data.newsData);
				$('#setOk').val(data.newsData);
				let proc = data.newsData == 0 ? 100 : Math.round((100 * data.newsData) / totalNews);
				if (proc > 100) {
					proc = 100;
				}
				
				$('#progressbar').css('width', proc + '%');

				if (data.newsData == 0 || data.newsData >= totalNews) {
					$('#progress').html('{$langDleFilter['admin']['fields']['ok_check']}');
					$('#insert_data').html(okStatus);
					coreAdmin.alert(data);
					$.post('engine/lazydev/' + coreAdmin.mod + '/admin/ajax/ajax.php', {action: 'setTriggers', dle_hash: dle_login_hash}, function(info) {
						info = jQuery.parseJSON(info);
						if (info.status == 'ok') {
							coreAdmin.alert(info);
							$('#saveFieldsButt').attr('disabled', false);
							$('#create_triggers').html(okStatus);
						} else {
							$('#saveFieldsButt').attr('disabled', false);
							$('#create_triggers').html(badStatus);
						}
					});
				} else { 
					setTimeout("setNews(" + data.newsData + ")", 1000);
				}
			}

		}
	}, 'json').fail(function() {
		$('#progress').html('{$langDleFilter['admin']['fields']['error_check']}');
		$('#saveFieldsButt').attr('disabled', false);
		$('#insert_data').html(badStatus);
	});

	return false;
}
HTML;
    } else {
        echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left text-size-small"><h4>{$langDleFilter['admin']['fields']['not_xfield']}</h4></div>
HTML;
    }
?>