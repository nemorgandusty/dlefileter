<?php
/**
 * Froala editor
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

$image_q_upload = $image_upload = '';
if ($user_group[$member_id['user_group']]['allow_image_upload'] || $user_group[$member_id['user_group']]['allow_file_upload']) {
    $image_upload = "'dleupload',";
    $image_q_upload = ", 'imageUpload'";
}

$implugin = 'insertImage';
if ($config['bbimages_in_wysiwyg']) {
    $implugin = 'dleimg';
}

$lang['wysiwyg_language'] = totranslit($lang['wysiwyg_language'], false, false);
$p_name = urlencode($member_id['name']);

echo <<<HTML
<script>
jQuery(function($){
    $('.wysiwygeditor').froalaEditor({
        dle_root: '',
        dle_upload_area : "short_story",
        dle_upload_user : "{$p_name}",
        dle_upload_news : "0",
        width: '100%',
        height: '350',
        language: '{$lang['wysiwyg_language']}',
        imageAllowedTypes: ['jpeg', 'jpg', 'png', 'gif'],
        imageDefaultWidth: 0,
        imageInsertButtons: ['imageBack', '|', 'imageByURL'{$image_q_upload}],
        imageUploadURL: 'engine/lazydev/dle_filter/upload.php',
        imageUploadParam: 'qqfile',
        imageUploadParams: { "subaction" : "upload", "filterId" : "{$filterId}", "area" : "short_story", "author" : "{$p_name}", "mode" : "quickload", "user_hash" : "{$dle_login_hash}"},
        imageMaxSize: {$config['max_up_size']} * 1024,
		
        toolbarButtonsXS: ['bold', 'italic', 'underline', 'strikeThrough', 'align', 'color', 'insertLink', '{$implugin}', {$image_upload}'insertVideo', 'paragraphFormat', 'paragraphStyle', 'dlehide', 'dlequote', 'dlespoiler', 'html'],

        toolbarButtonsSM: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'color', 'insertLink', '|', '{$implugin}',{$image_upload}'insertVideo', 'dleaudio', '|', 'paragraphFormat', 'paragraphStyle', '|', 'formatOL', 'formatUL', '|', 'dlehide', 'dlequote', 'dlespoiler', 'html'],

        toolbarButtonsMD: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'indent', 'outdent', '|', 'subscript', 'superscript', '|', 'insertTable', 'formatOL', 'formatUL', 'insertHR', '|', 'undo', 'redo', 'dletypo', 'clearFormatting', 'selectAll', '|', 'fullscreen', '-', 
                         'fontFamily', 'fontSize', '|', 'color', 'paragraphFormat', 'paragraphStyle', '|', 'insertLink', 'dleleech', '|', 'emoticons', '{$implugin}',{$image_upload}'|', 'insertVideo', 'dleaudio', 'dlemedia' ,'|', 'dlehide', 'dlequote', 'dlespoiler','dlecode','page_dropdown', 'html'],

        toolbarButtons: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'indent', 'outdent', '|', 'subscript', 'superscript', '|', 'insertTable', 'formatOL', 'formatUL', 'insertHR', '|', 'undo', 'redo', 'dletypo', 'clearFormatting', 'selectAll', '|', 'fullscreen', '-', 
                         'fontFamily', 'fontSize', '|', 'color', 'paragraphFormat', 'paragraphStyle', '|', 'insertLink', 'dleleech', '|', 'emoticons', '{$implugin}',{$image_upload}'|', 'insertVideo', 'dleaudio', 'dlemedia', '|', 'dlehide', 'dlequote', 'dlespoiler','dlecode','page_dropdown', 'html']

    }).on('froalaEditor.image.inserted froalaEditor.image.replaced', function (e, editor, \$img, response) {
        if (response) {
            response = JSON.parse(response);
            \$img.removeAttr("data-returnbox").removeAttr("data-success").removeAttr("data-xfvalue").removeAttr("data-flink");
            if (response.flink) {
                if (\$img.parent().hasClass("highslide")) {
                    \$img.parent().attr('href', response.flink);
                } else {
                    \$img.wrap( '<a href="'+response.flink+'" class="highslide"></a>' );
                }
            }
        }	
    });

});
</script>
<div class="editor-panel"><textarea id="short_story" name="short_story" class="wysiwygeditor" style="width:98%;height:300px;">{$row['short_story']}</textarea></div>
HTML;

?>