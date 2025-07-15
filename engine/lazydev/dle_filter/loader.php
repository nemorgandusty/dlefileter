<?php
/**
* AJAX обработчик
*
* Traduit par : DarkLane
* @link https://www.templatedlefr.fr/
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

defined("DATALIFEENGINE") or exit;
$modLName = "dle_filter";
if (file_exists(ENGINE_DIR . "/classes/plugins.class.php")) {
    include_once ENGINE_DIR . "/classes/plugins.class.php";
} else {
    @ini_set("pcre.recursion_limit", 10000000);
    @ini_set("pcre.backtrack_limit", 10000000);
    @ini_set("pcre.jit", false);
    include_once ENGINE_DIR . "/data/config.php";
    require_once ENGINE_DIR . "/classes/mysql.php";
    require_once ENGINE_DIR . "/data/dbconfig.php";
    if (!class_exists("DLEPlugins")) {
        abstract class DLEPlugins
        {
            public static function Check($source = "")
            {
                return $source;
            }
        }
    }
}
spl_autoload_register(function ($class) {
    $prefix = "LazyDev\\Filter\\";
    $baseDir = __DIR__ . "/class/";
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return NULL;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace("\\", "/", $relativeClass) . ".php";
    if (file_exists($file)) {
        require_once $file;
    }
});
LazyDev\Filter\Data::load();
$langDleFilter = LazyDev\Filter\Data::receive("lang");
$configDleFilter = LazyDev\Filter\Data::receive("config");

?>