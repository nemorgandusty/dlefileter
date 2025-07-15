<?php
/**
 * Конфиг и языковый файл
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;

class Data
{
    static private $data = [];

    /**
     * Загрузить конфиг и языковый пакет
     */
    static function load()
    {
		$path = realpath(__DIR__ . '/..');

		if (isset($_COOKIE['lang_dle_filter'])) {
			$_COOKIE['lang_dle_filter'] = in_array($_COOKIE['lang_dle_filter'], ['ua', 'en', 'ru', 'fr']) ? $_COOKIE['lang_dle_filter'] : 'fr';
		} else {
			$_COOKIE['lang_dle_filter'] = 'fr';
		}

		self::$data['config']   = include $path . '/data/config.php';
        self::$data['lang']     = include $path . '/lang/lang_' . $_COOKIE['lang_dle_filter'] . '.lng';
		self::$data['fields']   = include $path . '/data/fields.php';
        self::$data['pages']    = include $path . '/data/pages.php';

		if (!trim(self::$data['config']['filter_url'])) {
			self::$data['config']['filter_url'] = 'f';
		}
    }

    /**
     * Вернуть массив данных
     *
     * @param   string  $key
     * @return  array
     */
    static function receive($key)
    {
        return self::$data[$key];
    }

    /**
     * Получить данные с массива по ключу
     *
     * @param    string|array   $key
     * @param    string         $type
     * @return   mixed
     */
    static public function get($key, $type)
    {
        if (is_array($key) && !empty(self::$data[$type])) {
            return Helper::multiArray(self::$data[$type], $key, count($key));
        }
		
		if (isset(self::$data[$type][$key]) && !empty(self::$data[$type][$key])) {
			return self::$data[$type][$key];
		}
		
		return false;
    }

}