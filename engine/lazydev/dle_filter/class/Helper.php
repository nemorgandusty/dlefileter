<?php
/**
* Вспомогательный класс с набором функций
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

namespace LazyDev\Filter;

setlocale(LC_NUMERIC, 'C');

class Helper
{
    static $modName = 'dle_filter';

    /**
    * Склонение слов
    *
    * @param    array    $a    [0 => count, 1 => новост|ь|и|ей]
    * @return   string
    **/
    static function declinationLazy($a = [])
    {
        $a[0] = strip_tags($a[0]);
        $a[0] = str_replace(' ', '', $a[0]);

        $a[0] = intval($a[0]);
        $words = explode('|', trim($a[1]));
        $parts_word = [];

        switch (count($words)) {
            case 1:
                $parts_word[0] = $words[0];
                $parts_word[1] = $words[0];
                $parts_word[2] = $words[0];
                break;
            case 2:
                $parts_word[0] = $words[0];
                $parts_word[1] = $words[0] . $words[1];
                $parts_word[2] = $words[0] . $words[1];
                break;
            case 3: 
                $parts_word[0] = $words[0];
                $parts_word[1] = $words[0] . $words[1];
                $parts_word[2] = $words[0] . $words[2];
                break;
            case 4: 
                $parts_word[0] = $words[0] . $words[1];
                $parts_word[1] = $words[0] . $words[2];
                $parts_word[2] = $words[0] . $words[3];
                break;
        }

        $word = $a[0] % 10 == 1 && $a[0] % 100 != 11 ? $parts_word[0] : ($a[0] % 10 >= 2 && $a[0] % 10 <= 4 && ($a[0] % 100 < 10 || $a[0] % 100 >= 20) ? $parts_word[1] : $parts_word[2]);

        return $word;
    }

    /**
    * Разбор serialize строки
    *
    * @param    string   $data_form
    * @return   array
    **/
    static function unserializeJs($data_form)
    {
        $new_array = [];
        if ($data_form) {
            parse_str($data_form, $array_post);
            $new_array = self::loop($array_post);
        }

        return $new_array;
    }

    static function loop($array) {
        foreach($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::loop($array[$key]);
            }

            if (!is_array($value)) {
                $array[$key] = self::typeValue($value);
            }
        }

        return $array;
    }

    /**
     * Уникализация данных в массиве
     *
     * @param    array   $a
     * @return   array
     **/
    static function uniqueArray($a)
    {
        $b = [];
        foreach ($a as $k => $v){
            if (!in_array($v, $b)) {
                $b[$k] = $v;
            }
        }

        return $b;
    }

    /**
    * Типизация данных
    *
    * @param    mixed   $v
    * @return   float|int|string
    **/
    static function typeValue($v)
    {
        if (is_numeric($v)) {
            $v = is_float($v) ? floatval($v) : intval($v);
        } else {
            $v = strip_tags(stripslashes($v));
        }
        
        return $v;
    }
    
    /**
    * Json для js
    *
    * @param    array   $v
    * @return   string
    **/
    static function json($v)
    {
        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }
    
    /**
    * Получить данные с массива по массиву ключей
    *
    * @param    array   $a
    * @param    array   $k
    * @param    int     $c
    * @return   string
    **/
    static public function multiArray($a, $k, $c)
    {
        return ($c > 1) ? self::multiArray($a[$k[count($k) - $c]], $k, ($c - 1)) : $a[$k[(count($k) - 1)]];
    }

	/**
     * Очистить строку от последнего слэша
     *
     * @param    string   $v
     * @return   string
     **/
    static function cleanSlash($v)
    {
		if (substr($v, -1, 1) == '/') {
			$v = substr($v, 0, -1);
		}
		
        return $v;
    }
	
	/**
     * Получаем ID категории
     *
     * @param    array     $cat_info
	 * @param    string    $category
     * @return   int|bool
     **/
	static function getCategoryId($cat_info, $category)
	{
		foreach ($cat_info as $cats) {
			if ($cats['alt_name'] == $category) {
				return $cats['id'];
			}
		}
		
		return false;
	}

    /**
     * Проверяем протокол сайта
     *
     * @return bool
     */
	static function ssl()
    {
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)
            || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
            || (isset($_SERVER['CF_VISITOR']) && $_SERVER['CF_VISITOR'] == '{"scheme":"https"}')
            || (isset($_SERVER['HTTP_CF_VISITOR']) && $_SERVER['HTTP_CF_VISITOR'] == '{"scheme":"https"}')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Проверяем дату с JS
     *
     * @param  int $date
     *
     * @return int
     */
    static function jsDate($date)
    {
        if (mb_strlen((string)$date) > 10) {
            $date = substr((string)$date, 0, -3);
        }

        return (int)$date;
    }

    /**
     * Все подкатегории родительской категории
     *
     * @param int $id
     * @param mixed $subCategory
     *
     * @return string
     */
    static function getAllCats($id, $subCategory = '')
    {
        global $cat_info;

        $subFound = [];

        if (!$subCategory) {
            $subCategory = $id;
        }

        foreach ($cat_info as $cats) {
            if ($cats['parentid'] == $id) {
                $subFound[] = $cats['id'];
            }
        }

        foreach ($subFound as $parentId) {
            $subCategory .= '|' . $parentId;
            $subCategory = self::getAllCats($parentId, $subCategory);
        }

        return $subCategory;
    }

    /**
     * Конвертация юрл
     *
     * @param $var
     *
     * @return string
     */
    static function urlConvert($var)
    {
        global $langtranslit;

        if (is_array($var)) {
            return '';
        }

        $var = str_replace(chr(0), '', $var);

        $var = trim(strip_tags($var));
        $var = preg_replace("/\s+/u", '-', $var);

        if (is_array($langtranslit) && count($langtranslit)) {
            $var = strtr($var, $langtranslit);
        }

        $var = preg_replace("/[^a-z0-9\_\-\/]+/mi", '', $var);

        $var = preg_replace('#[\-]+#i', '-', $var);
        $var = preg_replace('#[.]+#i', '.', $var);

        $var = strtolower($var);

        $var = str_ireplace('.php', '', $var);
        $var = str_ireplace('.php', '.ppp', $var);

        return $var;
    }
}
