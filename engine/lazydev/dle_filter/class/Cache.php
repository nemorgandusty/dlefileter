<?php
/**
 * Кэш
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;

class Cache
{
    static $dir = ENGINE_DIR . '/lazydev/dle_filter/cache';
    static $prefix = 'dle_filter_';

    /**
     * Берем кэш
     *
     * @param    string    $id
     * @return	 string|bool
     **/
    static function get($id)
    {
        global $dlefastcache, $member_id, $config, $mcache;

        $response = false;
        $id = self::$prefix . md5($id . $member_id['user_group'] . $config['skin']);

        if ((float)$config['version_id'] >= 14.2 && $config['cache_type'] && is_object($dlefastcache)) {
            if ($dlefastcache->connection > 0) {
                return $dlefastcache->get($id);
            }
        }

        if ($config['cache_type'] && is_object($mcache)) {
            if ($mcache->connection > 0) {
                return $mcache->get($id);
            }
        }

        $file = self::$dir . '/' . $id . '.json';
        if (file_exists($file)) {
            $response = file_get_contents($file);
            $fileDate = filemtime($file);
            $fileDate = time() - $fileDate;
            if ($fileDate > 10800) {
                $response = false;
                @unlink($file);
            }
        }

        return $response;
    }

    /**
     * Сохраняем кэш
     *
     * @param    string    $data
     * @param    string    $id
     **/
    static function set($data, $id)
    {
        global $dlefastcache, $member_id, $config, $mcache;
        $id = self::$prefix . md5($id . $member_id['user_group'] . $config['skin']);

        if ((float)$config['version_id'] >= 14.2 && $config['cache_type'] && is_object($dlefastcache)) {
            if ($dlefastcache->connection > 0) {
                $dlefastcache->set($id, $data);
                return true;
            }
        }

        if ($config['cache_type'] && is_object($mcache)) {
            if ($mcache->connection > 0) {
                $mcache->set($id, $data);
                return true;
            }
        }

        $file = self::$dir . '/' . $id . '.json';
        file_put_contents($file, $data, LOCK_EX);
        @chmod($file, 0666);
    }

    /**
     * Очищаем кэш
     *
     **/
    static function clear()
    {
        global $dlefastcache, $config, $mcache;

        if ((float)$config['version_id'] >= 14.2 && $config['cache_type'] && is_object($dlefastcache)) {
            if ($dlefastcache->connection > 0) {
                $dlefastcache->clear(self::$prefix);
                return true;
            }
        }

        if ($config['cache_type'] && is_object($mcache)) {
            if ($mcache->connection > 0) {
                $mcache->clear(self::$prefix);
                return true;
            }
        }

        $cacheDir = opendir(self::$dir);
        while ($file = readdir($cacheDir)) {
            if ($file != '.htaccess' && !is_dir(self::$dir . '/' . $file)) {
                @unlink(self::$dir . '/' . $file);
            }
        }
    }
}

