<?php
/**
 * Дополнительные страницы
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

use LazyDev\Filter\Data;
use LazyDev\Filter\Admin;
use LazyDev\Filter\Helper;
include_once (DLEPlugins::Check(ENGINE_DIR . '/classes/htmlpurifier/HTMLPurifier.standalone.php'));
include_once(DLEPlugins::Check(ENGINE_DIR . '/classes/parse.class.php'));
$parse = new ParseFilter();

global $lang;

$add = strip_tags($_GET['add']);
if ($add == 'yes') {
    $filterId = 0;
    $row = [
        'redirect' => 0,
        'sitemap' => 0,
		'max_news' => 0,
    ];

    $checkedApprove = ['checked', 'on'];
    if (intval($_GET['id']) > 0) {
        $filterId = intval($_GET['id']);
        $row = $db->super_query("SELECT * FROM " . PREFIX . "_dle_filter_pages WHERE id='{$filterId}'");
        $row['name'] = str_replace('&amp;', '&', $parse->decodeBBCodes($row['name'], false));
        $row['page_url'] = stripslashes($row['page_url']);
        $row['filter_url'] = stripslashes($row['filter_url']);
        $row['sitemap'] = intval($row['sitemap']);
        $row['redirect'] = intval($row['redirect']);
        $row['seo_title'] = str_replace('&amp;', '&', $parse->decodeBBCodes($row['seo_title'], false));
        $row['short_story'] = $parse->decodeBBCodes($row['seo_text'], (bool)$config['allow_admin_wysiwyg'], (bool)$config['allow_admin_wysiwyg']);

        $row['meta_title'] = stripslashes($row['meta_title']);
        $row['meta_descr'] = $parse->decodeBBCodes($row['meta_descr'], false);
        $row['meta_key'] = $parse->decodeBBCodes($row['meta_key'], false);

        $row['speedbar'] = str_replace('&amp;', '&', $parse->decodeBBCodes($row['speedbar'], false));

        $row['og_title'] = stripslashes($row['og_title']);
        $row['og_descr'] = $parse->decodeBBCodes($row['og_descr'], false);
        $row['og_image'] = stripslashes($row['og_image']);

		$langDleFilter['admin']['pages']['add_page'] = $langDleFilter['admin']['pages']['edit_page'] . '«' . $row['name'] . '»';

        $checkedApprove = ($row['approve']) ? ['checked', 'on'] : ['', ''];
    }

    $checkedRedirect = ($row['redirect']) ? ['checked', 'on'] : ['', ''];
    $checkedSitemap = ($row['sitemap']) ? ['checked', 'on'] : ['', ''];

    $additionalJsAdminScript[] = <<<HTML
<script>
media_upload = function (area, author, filterId, wysiwyg) {
    var shadow = 'none';

    $('#mediaupload').remove();
    $('body').append("<div id='mediaupload' title='"+dle_act_lang[4]+"' style='display:none'></div>");

    $('#mediaupload').dialog({
        autoOpen: true,
        width: 710,
        resizable: false,
        dialogClass: "modalfixed",
        open: function(event, ui) { 
            $("#mediaupload").html("<iframe name='mediauploadframe' id='mediauploadframe' width='100%' height='545' src='engine/lazydev/dle_filter/admin/ajax/upload.php?area=" + area + "&author=" + author + "&filterId={$filterId}&wysiwyg=" + wysiwyg + "&dle_theme=" + dle_theme + "' frameborder='0' marginwidth='0' marginheight='0' allowtransparency='true'></iframe>");
            $('.ui-dialog').draggable('option', 'containment', '');
        },
        dragStart: function(event, ui) {
            shadow = $('.modalfixed').css('box-shadow');
            $('.modalfixed').fadeTo(0, 0.7).css('box-shadow', 'none');
            $('#mediaupload').css('visibility', 'hidden');
        },
        dragStop: function(event, ui) {
            $('.modalfixed').fadeTo(0, 1).css('box-shadow', shadow);
            $('#mediaupload').css('visibility', 'visible');
        },
        beforeClose: function(event, ui) { 
            $('#mediaupload').html('');
        }
    });

    if ($(window).width() > 830 && $(window).height() > 530) {
        $('.modalfixed.ui-dialog').css({
            position: 'fixed'
        });
        
        $('#mediaupload').dialog('option', 'position', {
            my: 'center',
            at: 'center',
            of: window
        });
    }

    return false;
};
</script>
HTML;

    $additionalJsAdminScript[] = "<script src=\"engine/classes/uploads/html5/fileuploader.js\"></script>";
    if ($config['allow_admin_wysiwyg'] == 0) {
        $additionalJsAdminScript[] = "<script src=\"engine/classes/js/typograf.min.js\"></script>";
    } elseif ($config['allow_admin_wysiwyg'] == 1) {
        $additionalJsAdminScript[] = "<script src=\"engine/skins/codemirror/js/code.js\"></script>";
        $additionalJsAdminScript[] = "<script src=\"engine/editor/jscripts/froala/editor.js\"></script>";
        $additionalJsAdminScript[] = "<script src=\"engine/editor/jscripts/froala/languages/{$lang['wysiwyg_language']}.js\"></script>";
        $additionalJsAdminScript[] = "<link href=\"engine/editor/jscripts/froala/css/editor.css\" rel=\"stylesheet\" />";
    } elseif ($config['allow_admin_wysiwyg'] == 2) {
        $additionalJsAdminScript[] = '<script src="engine/editor/jscripts/tiny_mce/tinymce.min.js"></script>';
    }
echo <<<HTML
<style>
.editor-panel {
    max-width: 100%!important;
}
</style>
<form id="formPage" class="form-horizontal">
    <div class="panel panel-flat">
        <div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['pages']['add_page']}</div>
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
                        <i class="fa fa-cog"></i> {$langDleFilter['admin']['pages']['main']}</a>
                    </li>
                    <li>
						<a onclick="ChangeOption(this, 'block_3');" class="tip">
                        <i class="fa fa-compass"></i> {$langDleFilter['admin']['pages']['seo']}</a>
                    </li>
					<li>
						<a onclick="ChangeOption(this, 'block_2');" class="tip">
                        <i class="fa fa-jsfiddle"></i> {$langDleFilter['admin']['pages']['og']}</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="panel-body">
            <div id="block_1">
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['name']}</label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="name" value="{$row['name']}" maxlength="255">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['url']} <i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right" data-rel="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="{$langDleFilter['admin']['pages']['url_descr']}" data-original-title="" title=""></i></label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="page_url" value="{$row['page_url']}" maxlength="255">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['filter_url']} <i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right" data-rel="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="{$langDleFilter['admin']['pages']['filter_url_descr']}" data-original-title="" title=""></i></label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="filter_url" value="{$row['filter_url']}">
                    </div>
                </div>
          
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['title']}</label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="title" value="{$row['seo_title']}" maxlength="255">
                    </div>
                </div>
                
                <div class="form-group editor-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['description']}</label>
                    <div class="col-md-12"><br>
HTML;
        if ($config['allow_admin_wysiwyg'] == 2) {
            $config['bbimages_in_wysiwyg'] = true;
            include(DLEPlugins::Check(ENGINE_DIR . '/editor/shortnews.php'));
        } elseif ($config['allow_admin_wysiwyg'] == 1) {
            include ENGINE_DIR . '/lazydev/dle_filter/admin/lib/froala.php';
        } elseif ($config['allow_admin_wysiwyg'] == 0) {
            $bb_editor = true;
            include(DLEPlugins::Check(ENGINE_DIR . '/inc/include/inserttag.php'));
            echo "<div class=\"editor-panel\"><div class=\"shadow-depth1\">{$bb_code}<textarea class=\"editor\" style=\"width:100%;height:300px;\" onfocus=\"setFieldName(this.name)\" name=\"short_story\" id=\"short_story\">{$row['short_story']}</textarea></div></div><script>var selField  = \"short_story\";</script>";
        }
        echo <<<HTML
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['approve']}</label>
                    <div class="col-md-12" style="margin-top: 10px;">
                        <input class="checkBox" type="checkbox" id="approve" name="approve" value="1" {$checkedApprove[0]}>
                        <div class="br-toggle br-toggle-success ' . $checkedApprove[1] . '" data-id="approve">
                            <div class="br-toggle-switch"></div>
                        </div>
                    </div>
                    
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['max_news']} <i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right" data-rel="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="{$langDleFilter['admin']['pages']['max_news_helper']}" data-original-title="" title=""></i></label>
                    <div class="col-md-12" style="margin-top: 10px;">
                        <div class="quantity">
                            <input type="number" name="max_news" value="{$row['max_news']}" min="0" max="999999" autocomplete="off">
                        </div>
                    </div>
                </div>
                
            </div>
            <div id="block_3" style="display: none;">
                 <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['sitemap']}</label>
                    <div class="col-md-12" style="margin-top: 10px;">
                        <input class="checkBox" type="checkbox" id="sitemap" name="sitemap" value="1" {$checkedSitemap[0]}>
                        <div class="br-toggle br-toggle-success ' . $checkedSitemap[1] . '" data-id="sitemap">
                            <div class="br-toggle-switch"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['redirect']}</label>
                    <div class="col-md-12" style="margin-top: 10px;">
                        <input class="checkBox" type="checkbox" id="redirect" name="redirect" value="1" {$checkedRedirect[0]}>
                        <div class="br-toggle br-toggle-success ' . $checkedRedirect[1] . '" data-id="redirect">
                            <div class="br-toggle-switch"></div>
                        </div>
                    </div>
                </div>
            
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['meta_title']}</label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="meta_title" value="{$row['meta_title']}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['meta_descr']}</label>
                    <div class="col-md-12">
                        <textarea style="min-height:150px;min-width:100%;max-width:100%;" autocomplete="off" class="textLazy" name="meta_descr" maxlength="300">{$row['meta_descr']}</textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['meta_key']}</label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="meta_key" value="{$row['meta_key']}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['meta_speedbar']}</label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="meta_speedbar" value="{$row['speedbar']}">
                    </div>
                </div>
            </div>
            <div id="block_2" style="display: none;">
                            <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['og_title']}</label>
                    <div class="col-md-12">
                        <input type="text" class="inputLazy" name="og_title" value="{$row['og_title']}" maxlength="255">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-md-12">{$langDleFilter['admin']['pages']['og_descr']}</label>
                    <div class="col-md-12"><br>
                        <textarea style="min-height:150px;min-width:100%;max-width:100%;" autocomplete="off" class="textLazy" name="og_descr" maxlength="300">{$row['og_descr']}</textarea>
                    </div>
                </div>
                
                <div class="form-group">
					<label class="control-label col-md-12">{$langDleFilter['admin']['pages']['og_photo']}</label>
					<div class="col-md-12"><br>
						<div id="xfupload_photo"></div>
						<input type="hidden" id="photo" class="form-control" value="{$row['og_image']}" name="og_image">
HTML;
    $lang['wysiwyg_language'] = totranslit($lang['wysiwyg_language'], false, false);
    $p_name = urlencode($member_id['name']);
    if ($row['og_image']) {
        $img_url = 	$config['http_home_url'] . 'uploads/dle_filter/' . $row['og_image'];
        $filename = explode('_', $row['og_image']);
        unset($filename[0]);
        $filename = implode('_', $filename);
        $up_image = "<div class=\"uploadedfile\"><div class=\"info\">{$filename}</div><div class=\"uploadimage\"><img style=\"width:auto;height:auto;max-width:100px;max-height:90px;\" src=\"" . $img_url . "\" /></div><div class=\"info\"><a href=\"#\" onclick=\"xfimagedelete(\\'image\\',\\'".$row['og_image']."\\');return false;\">{$lang['xfield_xfid']}</a></div></div>";
    }

    echo <<<HTML
<script>
function xfimagedelete(xfname, xfvalue) {
    DLEconfirm('{$lang['image_delete']}', '{$lang['p_info']}', function() {
        ShowLoading('');
        $.post('engine/lazydev/dle_filter/admin/ajax/upload.php', {type: 'image', subaction: 'deluploads', user_hash: '{$dle_login_hash}', filterId: '{$filterId}', author: '{$p_name}', 'images[]' : xfvalue}, function(data) {
            HideLoading('');
            $('#uploadedfile_photo').html('');
            $('#photo').val('');
            $('#xfupload_photo .qq-upload-button, #xfupload_photo .qq-upload-button input').removeAttr('disabled');
        });
        
    });

    return false;
};

$(function() {
    new qq.FileUploader({
		element: document.getElementById('xfupload_photo'),
		action: 'engine/lazydev/dle_filter/admin/ajax/upload.php',
		maxConnections: 1,
		multiple: false,
		allowdrop: false,
		encoding: 'multipart',
        sizeLimit: {$config['max_up_size']} * 1024,
		allowedExtensions: ['gif', 'jpg', 'jpeg', 'png'],
	    params: {'subaction': 'upload', 'filterId': '{$filterId}', 'area': 'xfieldsimage', 'author': '{$p_name}', 'xfname': 'photo', 'user_hash': '{$dle_login_hash}'},
        template: '<div class="qq-uploader">' + 
                '<div id="uploadedfile_photo">{$up_image}</div>' +
                '<div class="qq-upload-button btn btn-green bg-teal btn-sm btn-raised" style="width: auto;">{$lang['xfield_xfim']}</div>' +
                '<ul class="qq-upload-list" style="display:none;"></ul>' + 
             '</div>',
		onSubmit: function(id, fileName) {
			$('<div id="uploadfile-'+id+'" class="file-box"><span class="qq-upload-file-status">{$lang['media_upload_st6']}</span><span class="qq-upload-file">&nbsp;'+fileName+'</span>&nbsp;<span class="qq-status"><span class="qq-upload-spinner"></span><span class="qq-upload-size"></span></span><div class="progress "><div class="progress-bar progress-blue" style="width: 0%"><span>0%</span></div></div></div>').appendTo('#xfupload_photo');
        },
		onProgress: function(id, fileName, loaded, total) {
			$('#uploadfile-'+id+' .qq-upload-size').text(DLEformatSize(loaded)+' {$lang['media_upload_st8']} '+DLEformatSize(total));
			var proc = Math.round(loaded / total * 100);
			$('#uploadfile-'+id+' .progress-bar').css("width", proc + '%');
			$('#uploadfile-'+id+' .qq-upload-spinner').css("display", "inline-block");
		},
		onComplete: function(id, fileName, response) {
			if (response.success) {
				var returnbox = response.returnbox;
				var returnval = response.xfvalue;

				returnbox = returnbox.replace(/&lt;/g, "<");
				returnbox = returnbox.replace(/&gt;/g, ">");
				returnbox = returnbox.replace(/&amp;/g, "&");

				$('#uploadfile-'+id+' .qq-status').html('{$lang['media_upload_st9']}');
				$('#uploadedfile_photo').html( returnbox );
				$('#photo').val(returnval);

				$('#xfupload_photo .qq-upload-button, #xfupload_photo .qq-upload-button input').attr("disabled","disabled");
				
				setTimeout(function() {
					$('#uploadfile-'+id).fadeOut('slow', function() { $(this).remove(); });
				}, 1000);
			} else {
				$('#uploadfile-'+id+' .qq-status').html('{$lang['media_upload_st10']}');
				if (response.error) {
				    $('#uploadfile-'+id+' .qq-status').append('<br /><span class="text-danger">' + response.error + '</span>');
                }
				setTimeout(function() {
					$('#uploadfile-'+id).fadeOut('slow');
				}, 4000);
			}
		},
        messages: {
            typeError: "{$lang['media_upload_st11']}",
            sizeError: "{$lang['media_upload_st12']}",
            emptyError: "{$lang['media_upload_st13']}"
        },
		debug: false
    });
	
	if ($('#photo').val() != '') {
		$('#xfupload_photo .qq-upload-button, #xfupload_photo .qq-upload-button input').attr("disabled","disabled");
	}
});
</script>
                    </div>
                </div>
                
            </div>
            
            
        </div>
        
        <div class="panel-footer">
			<button type="submit" class="btn bg-teal btn-raised position-left" style="background-color:#1e8bc3;">{$langDleFilter['admin']['save']}</button>
		</div>
    </div>
</form>
HTML;
$jsAdminScript[] = <<<HTML

$('body').on('submit', 'form#formPage', function(e) {
    e.preventDefault();
    
    let formData = $('form#formPage').serializeArray();
    formData.push({name: 'dle_hash', value: '{$dle_login_hash}'});
    formData.push({name: 'action', value: 'addPage'});
    formData.push({name: 'id', value: '{$filterId}'});
    
    if (!$('[name="name"]').val().toString().trim()) {
        Growl.error({text: '{$langDleFilter['admin']['pages']['not-name']}'});
        return;
    }
    
    if (!$('[name=page_url]').val().toString().trim()) {
        Growl.error({text: '{$langDleFilter['admin']['pages']['not-url']}'});
        return;
    }
    
    if (!$('[name=filter_url]').val().toString().trim()) {
        Growl.error({text: '{$langDleFilter['admin']['pages']['not-filter']}'});
        return;
    }
    
    $.ajax({
        type: 'POST',
        data: formData,
        url: 'engine/lazydev/dle_filter/admin/ajax/ajax.php',
        dataType: 'json',
        success: function (data) {
            if (data.error) {
                Growl.error({text: data.text});
                return;
            }
            
            if (data.type !== undefined && data.id !== undefined) {
                window.location.href = "{$PHP_SELF}?mod=dle_filter&action=page&info=" + data.type + "&id=" + data.id + "&index=" + data.index;
            }
        }
    });
});

function ChangeOption(obj, selectedOption) {
    $('#navbar-filter li').removeClass('active');
    $(obj).parent().addClass('active');
    $('[id*=block_]').hide();
    $('#' + selectedOption).show();

    return false;
}
HTML;
} else {
    $dataPerPage = 25;
    if (isset($_REQUEST['cstart']) && $_REQUEST['cstart']) {
        $cstart = intval($_REQUEST['cstart']);
    } else {
        if (!isset($cstart) || $cstart < 1) {
            $cstart = 0;
        } else {
            $cstart = ($cstart - 1) * $dataPerPage;
        }
    }
    $i = $cstart;
    $getPages = $db->query("SELECT * FROM " . PREFIX . "_dle_filter_pages ORDER BY id DESC LIMIT {$cstart},{$dataPerPage}");

    $countPages = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_dle_filter_pages")['count'];
    $countPages = $countPages ?: 0;
    echo <<<HTML
<div class="panel panel-flat">
	<div class="panel-default">
		<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langDleFilter['admin']['pages']['list']} ({$langDleFilter['admin']['pages']['all']}{$countPages})
			<div class="heading-elements">
				<a href="{$PHP_SELF}?mod=dle_filter&action=page&add=yes" style="background-color: #1e824c" class="btn bg-teal legitRipple"><i class="fa fa-plus position-left"></i>{$langDleFilter['admin']['pages']['add']}</a>
			</div>
		</div>
HTML;
    $jsonStat = [];
    if ($countPages > 0) {
echo <<<HTML
<table class="table table-hover table-condensed">
	<thead>
		<tr>
			<th>{$langDleFilter['admin']['pages']['name']}</th>
			<th>{$langDleFilter['admin']['pages']['url']}</th>
			<th class="text-center">{$langDleFilter['admin']['pages']['detail']}</th>
			<th class="text-center"><i class="fa fa-cogs"></i></th>
		</tr>
	</thead>
	<tbody id="listPages">
HTML;

    while ($row = $db->get_row($getPages)) {
        $i++;
        $row['name'] = $row['name'] ? str_replace("&amp;", "&", $parse->decodeBBCodes($row['name'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['seo_title'] = $row['seo_title'] ? str_replace("&amp;", "&", $parse->decodeBBCodes($row['seo_title'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['meta_title'] = $row['meta_title'] ? str_replace("&amp;", "&", $parse->decodeBBCodes($row['meta_title'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['meta_descr'] = $row['meta_descr'] ? str_replace("&amp;", "&", $parse->decodeBBCodes($row['meta_descr'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['meta_key'] = $row['meta_key'] ? str_replace("&amp;", "&", $parse->decodeBBCodes($row['meta_key'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['speedbar'] = $row['speedbar'] ? str_replace("&amp;", "&", $parse->decodeBBCodes($row['speedbar'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['seo_text'] = $row['seo_text'] ? '<i class="fa fa-check" style="color: green;"></i>' : '<i class="fa fa-close" style="color: red;"></i>';
        $row['og_title'] = $row['og_title'] ? str_replace('&amp;', '&', $parse->decodeBBCodes($row['og_title'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['og_descr'] = $row['og_descr'] ? str_replace('&amp;', '&', $parse->decodeBBCodes($row['og_descr'], false)) : '<i class="fa fa-close" style="color: red;"></i>';
        $row['og_image'] = $row['og_image'] ? '<i class="fa fa-check" style="color: green;"></i>' : '<i class="fa fa-close" style="color: red;"></i>';

$jsonStat[$row['id']] = <<<HTML
<tr><td>{$langDleFilter['admin']['pages']['name']}</td><td>{$row['name']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['url']}</td><td>/{$row['page_url']}/</td></tr><tr><td>{$langDleFilter['admin']['pages']['filter_url']}</td><td>{$row['filter_url']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['title']}</td><td>{$row['seo_title']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['description']}</td><td>{$row['seo_text']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['meta_title']}</td><td>{$row['meta_title']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['meta_descr']}</td><td>{$row['meta_descr']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['meta_key']}</td><td>{$row['meta_key']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['meta_speedbar']}</td><td>{$row['speedbar']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['og_title']}</td><td>{$row['og_title']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['og_descr']}</td><td>{$row['og_descr']}</td></tr><tr><td>{$langDleFilter['admin']['pages']['og_photo']}</td><td>{$row['og_image']}</td></tr>
HTML;
echo <<<HTML
<tr id="page_{$row['id']}" data-id="{$row['id']}">
    <td>{$row['name']}</td>
    <td><a href="/{$row['page_url']}/" target="_blank">/{$row['page_url']}/</a></td>
    <td class="text-center"><input type="button" class="btn btn-sm bg-blue-800" style="border-radius: unset;" value="{$langDleFilter['admin']['pages']['look_param']}" onclick="showData({$row['id']})"></td>
    <td class="text-center">
        <a href="{$PHP_SELF}?mod=dle_filter&action=page&add=yes&id={$row['id']}" class="btn btn-primary btn-dle-filter"><i style="top: -3px;" data-rel="popover" data-trigger="hover" data-placement="left" data-content="{$langDleFilter['admin']['pages']['edit']}" class="fa fa-pencil"></i></a>
        <a href="#" onclick="deletePage({$row['id']}); return false;" class="btn btn-danger btn-dle-filter"><i style="top: -3px;" data-rel="popover" data-trigger="hover" data-placement="left" data-content="{$langDleFilter['admin']['pages']['delete']}" class="fa fa-trash"></i></a>
    </td>
</tr>
HTML;
    }
echo <<<HTML
		</tbody>
	</table>
</div>
HTML;

$jsonStat = Helper::json($jsonStat);
$jsAdminScript[] = <<<HTML

function deletePage(id) {
    DLEconfirm('{$langDleFilter['admin']['pages']['delete_text']}', '{$langDleFilter['admin']['pages']['delete_title']}', function() {
        $.post('engine/lazydev/dle_filter/admin/ajax/ajax.php', {action: 'deletePage', id: id, dle_hash: dle_login_hash}, function(data) {
            data = jQuery.parseJSON(data);
            if (data.error) {
                Growl.error({
                    title: '{$langDleFilter['admin']['pages']['error']}',
                    text: data.text
                });
            } else {
                Growl.info({
                    title: '{$langDleFilter['admin']['pages']['successful']}',
                    text: data.text
                });
                
                $('#page_' + id).remove();
            }
        });
    });
}

jsonStat = {$jsonStat};
let showData = function(i) {
	$("#dlepopup").remove();
	let title = "{$langDleFilter['admin']['pages']['watch_dialog']}";
	if (jsonStat[i]) {
		$("body").append("<div id='dlepopup' class='dle-alert' title='"+ title + "' style='display:none'><div class='panel panel-flat'><div class='table-responsive'><table class='table'><thead><tr><th style='width:250px;'>{$langDleFilter['admin']['pages']['data']}</th><th>{$langDleFilter['admin']['pages']['value']}</th></tr></thead><tbody>"+jsonStat[i]+"</tbody></table></div></div></div>");

		$('#dlepopup').dialog({
			autoOpen: true,
			width: 800,
			resizable: false,
			dialogClass: "modalfixed dle-popup-alert",
			buttons: {
				"{$langDleFilter['admin']['pages']['close']}": function() { 
					$(this).dialog("close");
					$("#dlepopup").remove();							
				} 
			}
		});

		$('.modalfixed.ui-dialog').css({position:"fixed", maxHeight:"600px", overflow:"auto"});
		$('#dlepopup').dialog( "option", "position", { my: "center", at: "center", of: window } );
	}
};
HTML;
    } else {
echo <<<HTML
        <div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left">
            <h4>{$langDleFilter['admin']['pages']['attention']}</h4>
            {$langDleFilter['admin']['pages']['attention_text']}
        </div>
HTML;
    }
echo <<<HTML
</div>
HTML;
$navigation = '';
    if ($countPages > $dataPerPage) {

        if ($cstart > 0) {
            $previous = $cstart - $dataPerPage;
            $navigation .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=page&cstart={$previous}\" title=\"{$lang['edit_prev']}\"><i class=\"fa fa-backward\"></i></a></li>";
        }

        $enpages_count = @ceil($countPages / $dataPerPage);
        $enpages_start_from = 0;
        $enpages = '';

        if ($enpages_count <= 10) {
            for ($j = 1; $j <= $enpages_count; $j++) {
                if ($enpages_start_from != $cstart) {
                    $enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=page&cstart={$enpages_start_from}\">{$j}</a></li>";
                } else {
                    $enpages .= "<li class=\"active\"><span>{$j}</span></li>";
                }

                $enpages_start_from += $dataPerPage;
            }
            $navigation .= $enpages;
        } else {
            $start = 1;
            $end = 10;

            if ($cstart > 0) {
                if (($cstart / $dataPerPage) > 4) {
                    $start = @ceil($cstart / $dataPerPage) - 3;
                    $end = $start + 9;

                    if ($end > $enpages_count) {
                        $start = $enpages_count - 10;
                        $end = $enpages_count - 1;
                    }

                    $enpages_start_from = ($start - 1) * $dataPerPage;
                }
            }

            if ($start > 2) {
                $enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=page\">1</a></li> <li><span>...</span></li>";
            }

            for ($j = $start; $j <= $end; $j++) {
                if ($enpages_start_from != $cstart) {
                    $enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=page&cstart={$enpages_start_from}\">{$j}</a></li>";
                } else {
                    $enpages .= "<li class=\"active\"><span>{$j}</span></li>";
                }

                $enpages_start_from += $dataPerPage;
            }

            $enpages_start_from = ($enpages_count - 1) * $dataPerPage;
            $enpages .= "<li><span>...</span></li><li><a href=\"$PHP_SELF?mod=dle_filter&action=page&cstart={$enpages_start_from}\">{$enpages_count}</a></li>";

            $navigation .= $enpages;

        }

        if ($countPages > $i) {
            $how_next = $countPages - $i;
            if ($how_next > $dataPerPage) {
                $how_next = $dataPerPage;
            }

            $navigation .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=page&cstart={$i}\" title=\"{$lang['edit_next']}\"><i class=\"fa fa-forward\"></i></a></li>";
        }

        echo "<ul id=\"paginationshow\" class=\"pagination pagination-sm mb-20\">".$navigation."</ul>";
    }
}