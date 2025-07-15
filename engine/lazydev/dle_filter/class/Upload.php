<?php
/**
 * DLE Filter
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 */

include_once (DLEPlugins::Check(ENGINE_DIR . '/lazydev/dle_filter/class/thumb.class.php'));

class UploadFileViaURL
{
	private $from = '';

    function saveFile($path, $filename, $prefix = true)
	{
        $file_prefix = '';

		if ($prefix) {
			$file_prefix = time() + rand(1, 100);
			$file_prefix .= '_';
		}

		$filename = totranslit($file_prefix . $filename);

        if (!@copy($this->from, $path . $filename)) {
            return false;
        }

        return $filename;
    }
	
    function getFileName()
	{
		global $config;

		$imageurl = trim(htmlspecialchars(strip_tags($_POST['imageurl']), ENT_QUOTES, $config['charset']));
		$imageurl = str_replace(chr(0), '', $imageurl);
		$imageurl = str_replace("\\", "/", $imageurl);

		$url = @parse_url($imageurl);

        if (!array_key_exists('host', $url)) {
            return '';
        }

		if ($url['scheme'] != 'http' AND $url['scheme'] != 'https') {
            return '';
		}

		if ($url['host'] == 'localhost' OR $url['host'] == '127.0.0.1') {
            return '';
		}

		if (stripos($url['host'], $_SERVER['HTTP_HOST']) !== false) {
			return '';
		}

		if (stripos($imageurl, ".php") !== false) {
			return '';
		}
		
		if (stripos($imageurl, ".phtm") !== false) {
			return '';
		}
		
		$this->from = $imageurl;

		$imageurl = explode('/', $imageurl);
		$imageurl = end($imageurl);

        return $imageurl;
    }
	
    function getFileSize()
	{
		$url = @parse_url($this->from);

		if ($url) {
			if ($url['scheme'] == 'https') {
				$port = 443;
			} else {
				$port = 80;
			}
			
			$fp = @fsockopen($url['host'], $port, $errno, $errstr, 10);

			if ($fp) {
				$x = '';
	
				fputs($fp,"HEAD {$url['path']} HTTP/1.0\nHOST: {$url['host']}\n\n");
				while(!feof($fp)) $x.=fgets($fp,128);
				fclose($fp);

				if (preg_match("#Content-Length: ([0-9]+)#i",$x,$size)) {
					return intval($size[1]);
				} else {
					return strlen(@file_get_contents($this->from));
				}
			}
		}
		
		return 0;
    }

    function getErrorCode()
	{
		return false;
    }
}

class UploadFileViaForm
{  

    function saveFile($path, $filename, $prefix = true)
	{
        $file_prefix = '';

		if ($prefix) {
			$file_prefix = time() + rand(1, 100);
			$file_prefix .= '_';
		}
		
		$filename = totranslit($file_prefix . $filename);

        if (!@move_uploaded_file($_FILES['qqfile']['tmp_name'], $path . $filename)) {
            return false;
        }

        return $filename;
    }
	
    function getFileName()
	{
		$path_parts = @pathinfo($_FILES['qqfile']['name']);
        return $path_parts['basename'];
    }
	
    function getFileSize()
	{
        return $_FILES['qqfile']['size'];
    }

    function getErrorCode()
	{
		$error_code = $_FILES['qqfile']['error'];

		if ($error_code !== UPLOAD_ERR_OK) {

		    switch ($error_code) { 
		        case UPLOAD_ERR_INI_SIZE: 
		            $error_code = 'PHP Error: The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
		        case UPLOAD_ERR_FORM_SIZE: 
		            $error_code = 'PHP Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
		        case UPLOAD_ERR_PARTIAL: 
		            $error_code = 'PHP Error: The uploaded file was only partially uploaded'; break;
		        case UPLOAD_ERR_NO_FILE: 
		            $error_code = 'PHP Error: No file was uploaded'; break;
		        case UPLOAD_ERR_NO_TMP_DIR: 
		            $error_code = 'PHP Error: Missing a PHP temporary folder'; break;
		        case UPLOAD_ERR_CANT_WRITE: 
		            $error_code = 'PHP Error: Failed to write file to disk'; break;
		        case UPLOAD_ERR_EXTENSION: 
		            $error_code = 'PHP Error: File upload stopped by extension'; break;
		        default: 
		            $error_code = 'Unknown upload error';  break;
		    } 
		} else {
			return false;
		}
		
        return $error_code;
    }
}

class FileUploader
{
	private $allowed_extensions = ['gif', 'jpg', 'jpeg', 'png', 'webp'];
	private $allowed_video = ['avi', 'mp4', 'wmv', 'mpg', 'flv', 'mp3', 'swf', 'm4v', 'm4a', 'mov', '3gp', 'f4v', 'mkv'];
	private $allowed_files = [];
	private $area = '';
	private $author = '';
	private $filterId = '';
	private $t_size = '';
	private $t_seite = 0;
	private $make_thumb = true;
	private $m_size = '';
	private $m_seite = 0;
	private $make_medium = false;
	private $make_watermark = true;
	private $use_prefix = true;

    function __construct ($area = '', $filterId = '', $author = '', $t_size = '', $t_seite = '', $make_thumb = true, $make_watermark = true, $m_size = 0, $m_seite = 0, $make_medium = false)
	{        
		global $config, $db, $member_id, $user_group;

        $this->area = totranslit($area);
	    $this->allowed_files = explode(',', strtolower($user_group[$member_id['user_group']]['files_type']));

        $this->author = $db->safesql($author);
        $this->filterId = intval($filterId);
        $this->t_size = $t_size;
        $this->t_seite = $t_seite;
        $this->make_thumb = $make_thumb;
        $this->m_size = $m_size;
        $this->m_seite = $m_seite;
        $this->make_medium = $make_medium;
        $this->make_watermark = $make_watermark;
      
        if (isset($_FILES['qqfile'])) {
            $this->file = new UploadFileViaForm();
        } elseif ($_POST['imageurl'] != '') {
            $this->file = new UploadFileViaURL();
        } else {
            $this->file = false;
        }

		define('FOLDER_PREFIX', date('Y-m') . '/');
    }

	function check_filename($filename)
	{
		global $config;
		if ($filename != '') {
			$filename = str_replace("\\", "/", $filename);
			$filename = preg_replace('#[.]+#i', '.', $filename);
			$filename = str_replace('/', '', $filename);
			$filename = str_ireplace('php', '', $filename);
			
			$filename_arr = explode('.', $filename);
			
			if (count($filename_arr) < 2) {
				return false;
			}
			
			$type = totranslit(end($filename_arr));
			
			if (!$type) {
				return false;
			}
			
			$curr_key = key($filename_arr);
			unset($filename_arr[$curr_key]);
 
			$filename = totranslit(implode('_', $filename_arr));
			
			if (!$filename) {
				$filename = time() + rand(1, 100);
			}

			$filename = $filename . '.' . $type;
		} else {
			return false;
		}
		
		$filename = preg_replace('#[.]+#i', '.', $filename);

		$array_block = ['.php', '.phtm', '.shtm', '.htaccess', '.cgi', '.htm', '.ini'];
		foreach ($array_block as $value) {
			if (stripos($filename, $value) !== false) {
				return false;
			}
		}
		
		if (stripos($filename, ".") === 0) {
			return false;
		}
		
		if (stripos($filename, ".") === false) {
			return false;
		}
		
		if (dle_strlen($filename, $config['charset']) > 170) {
			return false;
		}

		return $filename;
	}

	function msg_error($message, $code = 500)
	{
		global $config;
		return "{\"error\":\"{$message}\"}";
	}

	function FileUpload()
	{
		global $config, $db, $lang, $member_id, $user_group;

		$_IP = get_ip();
		$added_time = date("Y-m-d H:i:s");

		if (!is_dir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX)) {
			@mkdir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX, 0777);
			@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX, 0777);
			@mkdir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs', 0777);
			@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs', 0777);
		}

		if (!is_dir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'medium')) {
			@mkdir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'medium', 0777);
			@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'medium', 0777);
		}

		if (!is_dir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX)) {
			@mkdir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX, 0777);
			@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX, 0777);
		}

		if (!is_dir(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX)) {
			return $this->msg_error($lang['upload_error_0'] . ' /uploads/dle_filter/' . FOLDER_PREFIX, 403);
		}

		if (!is_writable(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX)) {
			return $this->msg_error($lang['upload_error_1'] . ' /uploads/dle_filter/' . FOLDER_PREFIX . ' ' . $lang['upload_error_2'], 403);
		}

		if (!is_writable(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs')) {
			return $this->msg_error($lang['upload_error_1'] . ' /uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs/ ' . $lang['upload_error_2'], 403);
		}

		if (!is_writable(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'medium' ) ) {
			return $this->msg_error($lang['upload_error_1'] . ' /uploads/dle_filter/' . FOLDER_PREFIX . 'medium/ ' . $lang['upload_error_2'], 403);
		}

		if (!$this->file){
			return $this->msg_error($lang['upload_error_3'], 405);
        }

		$filename = $this->check_filename($this->file->getFileName());

		if (!$filename) {
			return $this->msg_error($lang['upload_error_4'], 405);
        }

		$filename_arr = explode('.', $filename);
		$type = end($filename_arr);

		if (!$type) {
			return $this->msg_error($lang['upload_error_4'], 405);
        }

		$error_code = $this->file->getErrorCode();

		if ($error_code){
			return $this->msg_error($error_code, 405);
        }
		
		$size = $this->file->getFileSize();
		
        if (!$size) {
            return $this->msg_error($lang['upload_error_5'], 403);
        }

		if ($config['files_allow'] AND $user_group[$member_id['user_group']]['allow_file_upload'] AND in_array($type, $this->allowed_files)) {
			if (intval($user_group[$member_id['user_group']]['max_file_size']) AND $size > ($user_group[$member_id['user_group']]['max_file_size'] * 1024)) {
				return $this->msg_error($lang['files_too_big'], 500);
			}
			
			if ($user_group[$member_id['user_group']]['max_files']) {
				$row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_dle_filter_files WHERE type='1' AND author='{$this->author}' AND filterId='{$this->filterId}'");
				$count_files = $row['count'];
				if ($count_files AND $count_files >= $user_group[$member_id['user_group']]['max_files']) {
					return $this->msg_error($lang['error_max_files'], 403);
				}
			}

			$uploaded_filename = $this->file->saveFile(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX, $filename, $this->use_prefix);

			if ($uploaded_filename) {
				@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename, 0666);
				
				$added_time = date("Y-m-d H:i:s");
				$size = filesize(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);

				$db->query("INSERT INTO " . PREFIX . "_dle_filter_files (filterId, name, onserver, author, date, size, type) VALUES 
				('{$this->filterId}', '{$filename}', '". FOLDER_PREFIX ."{$uploaded_filename}', '{$this->author}', '{$added_time}', '{$size}', '1')");
				$id = $db->insert_id();
				if (in_array($type, $this->allowed_video)) {
					if($type == "mp3" ) {
						$file_link = $config['http_home_url'] . 'engine/skins/images/mp3_file.png';
						$data_url = $config['http_home_url'] . 'uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename;
						$file_play = "audio";
					} elseif ($type == "swf") {
						$file_link = $config['http_home_url'] . 'engine/skins/images/file_flash.png';
						$data_url = $config['http_home_url'] . 'uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename;
						$file_play = "flash";
					} else {
						$file_link = $config['http_home_url'] . 'engine/skins/images/video_file.png';
						$data_url = $config['http_home_url'] . 'uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename;
						$file_play = "video";
					}
				} else {
					$file_link = $config['http_home_url'] . 'engine/skins/images/all_file.png'; 
					$data_url = "#";
					$file_play = "";
				}
				$return_box = "<div class=\"uploadedfile\"><div class=\"info\">{$filename}</div><div class=\"uploadimage\"><a class=\"uploadfile\" href=\"{$data_url}\" data-src=\"{$id}:{$filename}\" data-type=\"file\" data-play=\"{$file_play}\"><img style=\"width:auto;height:auto;max-width:100px;max-height:90px;\" src=\"" . $file_link . "\" /></a></div><div class=\"info\"><input type=\"checkbox\" id=\"file\" name=\"files[]\" value=\"{$id}\" data-type=\"file\">&nbsp;".formatsize($size)."</div></div>";
			} else {
				return $this->msg_error($lang['images_uperr_3'], 403);
			}
		} elseif (in_array($type, $this->allowed_extensions) AND $user_group[$member_id['user_group']]['allow_image_upload']) {
			if (intval($config['max_up_size']) AND $size > ($config['max_up_size'] * 1024)) {
				return $this->msg_error($lang['images_big'], 500);
			}
			
			if ($user_group[$member_id['user_group']]['max_images']) {
				$row = $db->super_query("SELECT name FROM " . PREFIX . "_dle_filter_files WHERE type='0' AND author='{$this->author}' AND filterId='{$this->filterId}'");
				if ($row['name']) {
					$count_images = count(explode('|||', $row['name']));
				} else {
					$count_images = false;
				}
				
				if ($count_images AND $count_images >= $user_group[$member_id['user_group']]['max_images']) {
					return $this->msg_error($lang['error_max_images'], 403);
				}
			}
			
			$uploaded_filename = $this->file->saveFile(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX, $filename, $this->use_prefix);
			
			if ($uploaded_filename) {
				$added_time = date("Y-m-d H:i:s");

				@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename, 0666);

				$i_info = @getimagesize(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);
		
				if (!in_array($i_info[2], [1, 2, 3] )) {
					@unlink(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);
					return $this->msg_error($lang['upload_error_6'], 500);
				}

				$thumb = new thumbnail(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);
				
				$row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_dle_filter_files WHERE type='0' AND filterId='{$this->filterId}' AND author='{$this->author}'");
				if (!$row['count']) {
					$inserts = FOLDER_PREFIX . $uploaded_filename;
					$db->query( "INSERT INTO " . PREFIX . "_dle_filter_files (name, author, filterId, date) values ('{$inserts}', '{$this->author}', '{$this->filterId}', '{$added_time}')");
				} else {
					$row = $db->super_query("SELECT name FROM " . PREFIX . "_dle_filter_files WHERE type='0' AND filterId='{$this->filterId}' AND author='{$this->author}'");
					
					if ($row['name'] == '') {
						$listimages = [];
					} else {
						$listimages = explode('|||', $row['name']);
					}
					
					foreach ($listimages as $dataimages) {
						if ($dataimages == FOLDER_PREFIX . $uploaded_filename) {
							$error_image = "stop";
							break;
						}
					}
					
					if ($error_image != "stop") {
						$listimages[] = FOLDER_PREFIX . $uploaded_filename;
						$row['name'] = implode('|||', $listimages);
						
						if (dle_strlen($row['name'], $config['charset']) < 65000) {
							$db->query("UPDATE " . PREFIX . "_dle_filter_files SET name='{$row['name']}' WHERE type='0' AND filterId='{$this->filterId}' AND author='{$this->author}'");
						}
					}
				}
				
				if ($this->make_thumb) {
					if ($thumb->size_auto($this->t_size, $this->t_seite)) {
						$thumb->jpeg_quality($config['jpeg_quality']);
						
						if ($this->make_watermark){
							$thumb->insert_watermark($config['max_watermark']);
						}
						
						$thumb->save(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs/' . $uploaded_filename);
						@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs/' . $uploaded_filename, 0666);
					}
				}
				
				if ($this->make_medium) {
					$thumb = new thumbnail(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);

					if ($thumb->size_auto($this->m_size, $this->m_seite)) {
						$thumb->jpeg_quality($config['jpeg_quality']);
						
						if ($this->make_watermark) {
							$thumb->insert_watermark($config['max_watermark']);
						}
						
						$thumb->save(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'medium/' . $uploaded_filename);
						@chmod(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'medium/' . $uploaded_filename, 0666);
					}
				}

				if ($member_id['user_group'] == 1) {

					$thumb = new thumbnail(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);
					$thumb->jpeg_quality($config['jpeg_quality']);
					$re_save = $thumb->re_save;

					if ($this->make_watermark OR $config['max_up_side'] OR $re_save) {
						if (intval($config['max_up_side']) > 1 AND $thumb->size_auto($config['max_up_side'], $config['o_seite'])) {
							$re_save = true;
						}
						
						if ($this->make_watermark) {
							$thumb->insert_watermark($config['max_watermark']);
							$re_save = true;
						}
						
						if ($re_save) {
							$thumb->save(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);
						}
					}
				} else {
					$thumb = new thumbnail(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename );
					$thumb->jpeg_quality($config['jpeg_quality']);
				    if ($config['max_up_side']) {
						$thumb->size_auto($config['max_up_side'], $config['o_seite']);
					}
					
					if ($this->make_watermark) {
						$thumb->insert_watermark($config['max_watermark']);
					}
					
					$thumb->save(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);
				}

				if ($config['max_up_side']) {
					$i_info = @getimagesize(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename);
				}
				
				$img_url = $config['http_home_url'] . 'uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename;

				if (file_exists(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'medium/' . $uploaded_filename)) {
					$img_url = $config['http_home_url'] . 'uploads/dle_filter/' . FOLDER_PREFIX . 'medium/' . $uploaded_filename;
					$medium_data = 'yes';
					$tm_url = $img_url;
				} else {
					$medium_data = 'no';
				}

				if (file_exists(ROOT_DIR . '/uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs/' . $uploaded_filename)) {
					$img_url = $config['http_home_url'] . 'uploads/dle_filter/' . FOLDER_PREFIX . 'thumbs/' . $uploaded_filename;
					$thumb_data = 'yes';
					$th_url = $img_url;
				} else {
					$thumb_data = 'no';
				}

				$data_url = $config['http_home_url'] . 'uploads/dle_filter/' . FOLDER_PREFIX . $uploaded_filename;

				$link = $data_url;
				$flink =", \"flink\":\"{$link}\"";
				
				if ($medium_data == 'yes') {
					$link = $tm_url;
				} elseif ($thumb_data == 'yes') {
					$link = $th_url;
				} else {
					$flink = '';
				}
				
				if ($this->area == "xfieldsimage") {
					$return_box = "<div class=\"uploadedfile\"><div class=\"info\">{$filename}</div><div class=\"uploadimage\"><img style=\"width:auto;height:auto;max-width:100px;max-height:90px;\" src=\"" . $img_url . "\" /></div><div class=\"info\"><a href=\"#\" onclick=\"xfimagedelete('poster','".FOLDER_PREFIX . $uploaded_filename."');return false;\">{$lang['xfield_xfid']}</a></div></div>";
					$xfvalue = FOLDER_PREFIX . $uploaded_filename;
					$xfvalue = addcslashes($xfvalue, "\t\n\r\"\\/");
				} else {
					$return_box = "<div class=\"uploadedfile\"><div class=\"info\">{$filename}</div><div class=\"uploadimage\"><a class=\"uploadfile\" href=\"{$data_url}\" data-src=\"{$data_url}\" data-thumb=\"{$thumb_data}\" data-medium=\"{$medium_data}\" data-type=\"image\"><img style=\"width:auto;height:auto;max-width:100px;max-height:90px;\" src=\"" . $img_url . "\" /></a></div><div class=\"info\"><input type=\"checkbox\" name=\"images[" . FOLDER_PREFIX . $uploaded_filename . "]\" value=\"" . FOLDER_PREFIX . $uploaded_filename . "\" data-thumb=\"{$thumb_data}\" data-medium=\"{$medium_data}\" data-type=\"image\" data-src=\"{$data_url}\">&nbsp;{$i_info[0]}x{$i_info[1]}</div></div>";
				}
			} else {
				return $this->msg_error($lang['images_uperr_3'], 403);
			}
		} else {
			return $this->msg_error($lang['images_uperr_2'], 403);
		}
		
		$return_box = addcslashes($return_box, "\t\n\r\"\\/");
		return htmlspecialchars("{\"success\":true, \"returnbox\":\"{$return_box}\", \"xfvalue\":\"{$xfvalue}\", \"link\":\"{$link}\"{$flink}}", ENT_NOQUOTES, $config['charset']);
	}
}
?>