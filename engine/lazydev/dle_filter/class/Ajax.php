<?php
/**
* Класс AJAX обработки админ панели
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

namespace LazyDev\Filter;

use ParseFilter;
use DLEPlugins;
include_once (DLEPlugins::Check(ENGINE_DIR . '/classes/htmlpurifier/HTMLPurifier.standalone.php'));
include_once (DLEPlugins::Check(ENGINE_DIR . '/classes/parse.class.php'));

class Ajax
{
    /**
     * Определение AJAX действия
     *
     * @param    string    $action
     *
     **/
    static function ajaxAction($action)
    {
        in_array($action, get_class_methods(self::class)) && self::$action();
    }

    /**
     * Сохранение полей
     *
     **/
    static function saveFields()
    {
        Field::saveFields();
    }

    /**
     * Создание таблицы
     *
     **/
    static function setTable()
    {
        Field::setTable();
    }

    /**
     * Обработка новостей
     *
     **/
    static function setNews()
    {
        Field::setNews();
    }

    /**
     * Создание триггеров
     *
     **/
    static function setTriggers()
    {
        Field::setTriggers();
    }

    /**
     * Сохранение настроек
     *
     **/
    static function saveOptions()
    {
		global $db;
        $arrayConfig = Helper::unserializeJs($_POST['data']);

        $arrayConfig['link'] = self::clearConfig($arrayConfig['link']);
        $arrayConfig['exchange'] = self::clearConfig($arrayConfig['exchange']);

        $arrayConfig['filter_url'] = totranslit($arrayConfig['filter_url'], true, false);

        $handler = fopen(ENGINE_DIR . '/lazydev/' . Helper::$modName . '/data/config.php', 'w');
        fwrite($handler, "<?php\n\n//DLE Filter by LazyDev\n\nreturn ");
        fwrite($handler, var_export($arrayConfig, true));
        fwrite($handler, ";\n");
        fclose($handler);
        echo Helper::json(['text' => Data::get(['admin', 'ajax', 'options_save'], 'lang')]);
    }

    /**
     * Очистка связей от пустых полей
     *
     * @param $a array
     * @return array
     */
    static function clearConfig($a)
    {
        $t = $a['p'];
        foreach ($t as $k => $v) {
            if (trim($v) && trim($a['a'][$k])) {
                continue;
            }

            unset($a['a'][$k], $a['p'][$k]);
        }

        $a = !$a['a'] && !$a['p'] ? [] : $a;

        return $a;
    }

	/**
     * Очистка статистики
     *
     **/
    static function clearStatistics()
    {
        global $db;
		
		$db->query("TRUNCATE " . PREFIX . "_dle_filter_statistics");
		echo Helper::json(['text' => Data::get(['admin', 'ajax', 'clear_statistics'], 'lang')]);
    }
	
	/**
     * Очистка кэша
     *
     **/
    static function clearCache()
    {
		Cache::clear();
		echo Helper::json(['text' => Data::get(['admin', 'ajax', 'clear_cache'], 'lang')]);
    }

    /**
     * Поиск новостей
     *
     **/
    static function findNews()
    {
        global $db, $config;

        if (preg_match("/[\||\<|\>|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\+]/", $_POST['query']) || !$_POST['query']) {
            exit;
        }

        $query = $db->safesql(htmlspecialchars(strip_tags(stripslashes(trim($_POST['query']))), ENT_QUOTES, $config['charset']));
        $db->query("SELECT id, title as name FROM " . PREFIX . "_post WHERE `title` LIKE '%{$query}%' AND approve ORDER BY date DESC LIMIT 15");

        $search = [];

        while ($row = $db->get_row()) {
            $row['name'] = str_replace("&quot;", '\"', $row['name']);
            $row['name'] = str_replace("&#039;", "'", $row['name']);
            $row['name'] = htmlspecialchars($row['name']);

            $search[] = ['value' => $row['id'], 'name' => $row['name']];
        }

        echo json_encode($search);
    }

    /**
     * Включение/Выключение тёмной темы
     *
     */
    static function setDark()
    {
        if (isset($_COOKIE['admin_filter_dark'])) {
            set_cookie('admin_filter_dark', '', -1);
            $_COOKIE['admin_filter_dark'] = null;
        } else {
            set_cookie('admin_filter_dark', 'yes', 300);
            $_COOKIE['admin_filter_dark'] = 'yes';
        }

        echo 'yes';
    }
	
	/**
     * Сохранение страниц фильтра
     *
     */
    static function addPage()
    {
        global $langDleFilter;

        @header('X-XSS-Protection: 0;');

        global $config, $db, $member_id;

        $fastquotes = ['\x22', '\x60', '\t', '\n', '\r', '"', '\r', '\n', '$', '{', '}', '[', ']', '<', '>', "\\"];

        $parse = new ParseFilter();

        $pagesArray = Data::receive('pages');
        $indexEdit = intval($_POST['id']);

        $name = $db->safesql($parse->process(trim(strip_tags($_POST['name']))));

        if (!$name) {
            echo Helper::json(['text' => $langDleFilter['admin']['pages']['not-name'], 'error' => 'true']);
            exit;
        }

        $page_url = Helper::urlConvert(stripslashes($_POST['page_url']));
        if (dle_strlen($page_url, $config['charset'] ) > 190) {
            $page_url = dle_substr($page_url, 0, 190, $config['charset']);
        }

        if (!$page_url) {
            echo Helper::json(['text' => $langDleFilter['admin']['pages']['not-url'], 'error' => 'true']);
            exit;
        }

        $page_url_copy = $page_url;
        $page_url = $db->safesql($page_url);
        $filter_url_copy = $filter_url = trim(strip_tags($_POST['filter_url']));
        $filter_url = $db->safesql($filter_url);
        if (!$filter_url) {
            echo Helper::json(['text' => $langDleFilter['admin']['pages']['not-filter'], 'error' => 'true']);
            exit;
        }

        $sitemap = intval($_POST['sitemap']) > 0 ? intval($_POST['sitemap']) : 0;
        $redirect = intval($_POST['redirect']) > 0 ? intval($_POST['redirect']) : 0;
        $approve = intval($_POST['approve']) > 0 ? intval($_POST['approve']) : 0;
        $maxnews = intval($_POST['max_news']) > 0 ? intval($_POST['max_news']) : 0;

        $title = $db->safesql($parse->process(trim(strip_tags($_POST['title']))));
        $text = $parse->process($_POST['short_story']);
        $text = $parse->BB_Parse($text, (bool)$config['allow_admin_wysiwyg']);

        $meta_title = trim(htmlspecialchars(strip_tags(stripslashes($_POST['meta_title'])), ENT_COMPAT, $config['charset']));
        $meta_title = $db->safesql(preg_replace('/\s+/u', ' ', str_replace($fastquotes, '', $meta_title)));

        $meta_descr = trim(strip_tags(stripslashes($_POST['meta_descr'])));
        if (dle_strlen($meta_descr, $config['charset']) > 300) {
            $meta_descr = dle_substr($meta_descr, 0, 300, $config['charset']);
            if (($temp_dmax = dle_strrpos($meta_descr, ' ', $config['charset']))) {
                $meta_descr = dle_substr($meta_descr, 0, $temp_dmax, $config['charset']);
            }
        }
        $meta_descr = $db->safesql(preg_replace('/\s+/u', ' ', str_replace($fastquotes, '', $meta_descr)));

		$arr = explode(',', $_POST['meta_key']);
        $tempArray = [];
        foreach ($arr as $word) {
            $tempArray[] = trim($word);
        }

        $meta_key = implode(', ', $tempArray);
        $meta_key = $db->safesql(preg_replace('/\s+/u', ' ', str_replace($fastquotes, ' ', strip_tags(stripslashes($meta_key)))));

        $meta_speedbar = $db->safesql($parse->process(trim(strip_tags($_POST['meta_speedbar']))));

        $og_title = trim(htmlspecialchars(strip_tags(stripslashes($_POST['og_title'])), ENT_COMPAT, $config['charset']));
        $og_title = $db->safesql(preg_replace('/\s+/u', ' ', str_replace($fastquotes, '', $og_title)));

        $og_descr = trim(strip_tags(stripslashes($_POST['og_descr'])));
        if (dle_strlen($og_descr, $config['charset']) > 300) {
            $og_descr = dle_substr($og_descr, 0, 300, $config['charset']);
            if (($temp_dmax = dle_strrpos($og_descr, ' ', $config['charset']))) {
                $og_descr = dle_substr($og_descr, 0, $temp_dmax, $config['charset']);
            }
        }

        $og_descr = $db->safesql(preg_replace('/\s+/u', ' ', str_replace($fastquotes, '', $og_descr)));

        $og_image = $db->safesql(trim(strip_tags(stripslashes($_POST['og_image']))));
        $_Date = date('Y-m-d H:i:s', time());
        if ($indexEdit > 0) {
            $db->query("UPDATE " . PREFIX . "_dle_filter_pages SET `name`='{$name}',`page_url`='{$page_url}',`filter_url`='{$filter_url}',`sitemap`='{$sitemap}',`redirect`='{$redirect}',`seo_title`='{$title}',`seo_text`='{$text}',`meta_title`='{$meta_title}',`meta_descr`='{$meta_descr}',`meta_key`='{$meta_key}',`speedbar`='{$meta_speedbar}',`og_title`='{$og_title}',`og_descr`='{$og_descr}',`og_image`='{$og_image}', `approve`='{$approve}', `max_news`='{$maxnews}', dateEdit='{$_Date}' WHERE id='{$indexEdit}'");
        } else {
            $db->query("INSERT INTO " . PREFIX . "_dle_filter_pages (`name`, `page_url`, `filter_url`, `sitemap`, `redirect`, `seo_title`, `seo_text`, `meta_title`, `meta_descr`, `meta_key`, `speedbar`, `og_title`, `og_descr`, `og_image`, `approve`, `max_news`, `date`) VALUES ('{$name}', '{$page_url}', '{$filter_url}', '{$sitemap}', '{$redirect}', '{$title}', '{$text}', '{$meta_title}', '{$meta_descr}', '{$meta_key}', '{$meta_speedbar}', '{$og_title}', '{$og_descr}', '{$og_image}', '{$approve}', '{$maxnews}', '{$_Date}')");
            $indexEdit = $db->insert_id();
        }

        $db->query("UPDATE " . PREFIX . "_dle_filter_files SET filterId='{$indexEdit}' WHERE filterId=0 AND author='{$member_id['name']}'");

        $pagesArray[$indexEdit] = ['page' => $page_url_copy, 'filter' => $filter_url_copy, 'redirect' => $redirect];
        $handler = fopen(ENGINE_DIR . '/lazydev/' . Helper::$modName . '/data/pages.php', 'w');
        fwrite($handler, "<?php\n\n//DLE Filter by LazyDev\n\nreturn ");
        fwrite($handler, var_export($pagesArray, true));
        fwrite($handler, ";\n");
        fclose($handler);

        echo Helper::json(['type' => 'rule', 'id' => $indexEdit]);
    }

    /**
     * Удаление страницы
     *
     */
    static function deletePage()
    {
        global $db;

        $id = intval($_POST['id']);

        $db->query("DELETE FROM " . PREFIX . "_dle_filter_pages WHERE id='{$id}'");

        $row = $db->super_query("SELECT name  FROM " . PREFIX . "_dle_filter_files WHERE filterId='{$id}' AND type='0'");
        $listimages = explode('|||', $row['name']);

        foreach ($listimages as $dataimages) {
            $url_image = explode('/', $dataimages);

            if (count($url_image) == 2) {
                $folder_prefix = $url_image[0] . '/';
                $dataimages = $url_image[1];
            } else {
                $folder_prefix = '';
                $dataimages = $url_image[0];
            }

            @unlink(ROOT_DIR . '/uploads/dle_filter/' . $folder_prefix . $dataimages);
            @unlink(ROOT_DIR . '/uploads/dle_filter/' . $folder_prefix . 'thumbs/' . $dataimages);
            @unlink(ROOT_DIR . '/uploads/dle_filter/' . $folder_prefix . 'medium/' . $dataimages);
        }
        $db->query("DELETE FROM " . PREFIX . "_dle_filter_files WHERE filterId='{$id}' AND type='0'");

        $db->query("SELECT id, onserver FROM " . PREFIX . "_dle_filter_files WHERE filterId='{$id}' AND type='1'");
        while ($row = $db->get_row()) {
            $url = explode('/', $row['onserver']);

            $folder_prefix = '';
            $file = $url[0];
            if (count($url) == 2) {
                $folder_prefix = $url[0] . '/';
                $file = $url[1];
            }

            $file = totranslit($file, false);

            if (trim($file) == '.htaccess') {
                continue;
            }

            @unlink(ROOT_DIR . '/uploads/dle_filter/' . $folder_prefix . $file);
        }
        $db->query("DELETE FROM " . PREFIX . "_dle_filter_files WHERE filterId='{$id}' AND type='1'");

        $pages = Data::receive('pages');

        unset($pages[$id]);

        $handler = fopen(ENGINE_DIR . '/lazydev/' . Helper::$modName . '/data/pages.php', 'w');
        fwrite($handler, "<?php\n\n//DLE Filter by LazyDev\n\nreturn ");
        fwrite($handler, var_export($pages, true));
        fwrite($handler, ";\n");
        fclose($handler);

        echo Helper::json(['text' => Data::get(['admin', 'page', 'delete_page'], 'lang')]);
    }


    /**
     * Изменение языка админ панели
     *
     */
    static function setLang()
    {
        if (in_array($_POST['lang'], ['ru', 'en', 'ua'])) {
            $_POST['lang'] = trim(strip_tags(stripslashes($_POST['lang'])));
            set_cookie('lang_dle_filter', $_POST['lang'], 300);
            $_COOKIE['lang_dle_filter'] = $_POST['lang'];
        }

        echo 'yes';
    }
}
