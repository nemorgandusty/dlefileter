<?php
/**
 * Поля фильтра
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;
setlocale(LC_NUMERIC, 'C');
use ParseFilter;



class Field
{
	private static $instance = null;
	
	private static $dropTableNews = 'DROP TABLE IF EXISTS `{prefix}_dle_filter_news`';
	private static $alterTableNews = 'ALTER TABLE `{prefix}_dle_filter_news`';
	private static $tableNews = 'CREATE TABLE IF NOT EXISTS `{prefix}_dle_filter_news` (
		`filterId` int(11) NOT NULL AUTO_INCREMENT,
		`newsId` int(11) NOT NULL,
		UNIQUE KEY `newsId` (`newsId`),
		PRIMARY KEY `filterId` (`filterId`)
	) ENGINE={engine} DEFAULT CHARSET={charset};';
	
	private static $dropTableTemp = 'DROP TABLE IF EXISTS `{prefix}_dle_filter_news_temp`';
	private static $tableTemp = 'CREATE TABLE IF NOT EXISTS `{prefix}_dle_filter_news_temp` (
		`tempId` int(11) NOT NULL AUTO_INCREMENT,
		`newsId` int(11) NOT NULL,
		`xfieldNew` mediumtext NOT NULL,
		`allow_br` tinyint(1) NOT NULL DEFAULT 1,
		UNIQUE KEY `tempId` (`tempId`)
	) ENGINE={engine} DEFAULT CHARSET={charset};';
	
	private static $column = [
		'number' => 'ADD COLUMN `{column}` INT(11) NOT NULL',
		'double' => 'ADD COLUMN `{column}` DOUBLE NOT NULL',
		'text' => 'ADD COLUMN `{column}` TEXT NOT NULL'
	];
	
	private static $deleteTrigger = 'CREATE TRIGGER filter_news_delete AFTER DELETE ON {prefix}_post
FOR EACH ROW
BEGIN
	DELETE FROM {prefix}_dle_filter_news WHERE {prefix}_dle_filter_news.newsId = old.id;
END';
	
	private static $updateTrigger = 'CREATE TRIGGER filter_news_update AFTER UPDATE ON {prefix}_post
FOR EACH ROW
BEGIN
	IF NEW.xfields <> OLD.xfields THEN 
		INSERT INTO {prefix}_dle_filter_news_temp (newsId, xfieldNew, allow_br) VALUES(NEW.id, NEW.xfields, NEW.allow_br);
	END IF;
END';
	
	private static $insertTrigger = 'CREATE TRIGGER filter_news_insert AFTER INSERT ON {prefix}_post
FOR EACH ROW
BEGIN
	INSERT INTO {prefix}_dle_filter_news_temp (newsId, xfieldNew, allow_br) VALUES(NEW.id, NEW.xfields, NEW.allow_br);
END';
	
	private static $dropTriggerDelete = 'DROP TRIGGER IF EXISTS filter_news_delete';
	private static $dropTriggerUpdate = 'DROP TRIGGER IF EXISTS filter_news_update';
	private static $dropTriggerInsert = 'DROP TRIGGER IF EXISTS filter_news_insert';
	
	private static $perAjax = 20;
	
	/**
     * Конструктор
     *
	 * @return   Field
     */
	static function construct()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
	
	/**
     * Сохраняем поля
     *
     **/
    static function saveFields()
    {
        $arrayConfig = Helper::unserializeJs($_POST['data']);
        $handler = fopen(ENGINE_DIR . '/lazydev/' . Helper::$modName . '/data/fields.php', 'w');
        fwrite($handler, "<?php\n\n//DLE Filter by LazyDev\n\nreturn ");
        fwrite($handler, var_export($arrayConfig, true));
        fwrite($handler, ";\n");
        fclose($handler);
        sleep(1);
		echo Helper::json(['text' => Data::get(['admin', 'ajax', 'fields_save'], 'lang'), 'status' => 'ok']);
    }
	
	/**
     * Создаем таблицы
     *
     **/
	static function setTable()
	{
		global $db, $config;

		$engine = 'InnoDB';
		if (version_compare($db->mysql_version, '5.6.4', '<')) {
			$engine = 'MyISAM';
		}
		$charset = 'utf8mb4';
		
		$db->query(str_replace('{prefix}', PREFIX, self::$dropTriggerDelete));
		$db->query(str_replace('{prefix}', PREFIX, self::$dropTriggerUpdate));
		$db->query(str_replace('{prefix}', PREFIX, self::$dropTriggerInsert));
		$db->query(str_replace('{prefix}', PREFIX, self::$dropTableNews));
		$db->query(str_replace('{prefix}', PREFIX, self::$dropTableTemp));
		
		$db->query(str_replace(['{prefix}', '{engine}', '{charset}'], [PREFIX, $engine, $charset], self::$tableNews));
		$db->query(str_replace(['{prefix}', '{engine}', '{charset}'], [PREFIX, $engine, $charset], self::$tableTemp));
		
		$fieldsVar = Data::receive('fields');
		$xfType = [];
		foreach ($fieldsVar['status'] as $xf => $type) {
			if ($type != 'off') {
				$xfType[] = str_replace('{column}', 'xf_' . $xf, self::$column[$type]);
			}
		}
		
		if ($xfType) {
			$addColumn = implode(', ', $xfType);
			$db->query(str_replace('{prefix}', PREFIX, self::$alterTableNews) . $addColumn);
			echo Helper::json(['text' => Data::get(['admin', 'ajax', 'table_create'], 'lang'), 'status' => 'ok']);
		} else {
			echo Helper::json(['text' => Data::get(['admin', 'ajax', 'all_off'], 'lang'), 'status' => 'off']);
		}
	}
	
	/**
     * Переобход всех новостей
     *
     **/
	static function setNews()
	{
		global $db, $config;
		
		$startCount = intval($_POST['startCount']);
		$stepCount = 0;
		
		$parse = new ParseFilter();
		$parse->edit_mode = false;
		if ($config['allow_admin_wysiwyg']) {
			$parse->allow_code = false;
		}
		
		$fieldsVar = Data::receive('fields');
		$xfields = xfieldsload();

		$result = $db->query("SELECT id, xfields, allow_br FROM " . PREFIX . "_post LIMIT " . $startCount . ", " . self::$perAjax);
		while ($row = $db->get_row($result)) {
			if ($row['xfields']) {
				$newsXfields = xfieldsdataload($row['xfields']);
				$addXfields = [];
				
				if (!empty($newsXfields)) {
					foreach ($xfields as $name => $value) {
						if ($fieldsVar['status'][$value[0]] !== 'off' && isset($fieldsVar['status'][$value[0]])) {
							if ($fieldsVar['status'][$value[0]] == 'double') {
								$newsXfields[$value[0]] = floatval(preg_replace('/\s+/', '', str_replace(',', '.', $newsXfields[$value[0]])));
							} elseif ($fieldsVar['status'][$value[0]] == 'number') {
                                $newsXfields[$value[0]] = intval(preg_replace('/\s+/', '', $newsXfields[$value[0]]));
                            }

							if ($value[3] != 'select' && $value[3] != 'image' && $value[3] != 'file' && $value[3] != 'htmljs' && $value[8] == 0 && $value[6] == 0 && isset($newsXfields[$value[0]])) {
								if ($config['allow_admin_wysiwyg'] || $row['allow_br'] != 1) {
									$newsXfields[$value[0]] = $parse->decodeBBCodes($newsXfields[$value[0]], true, true);					
									$addXfields[$value[0]] = $parse->BB_Parse($parse->process($newsXfields[$value[0]]));
								} else {
									$newsXfields[$value[0]] = $parse->decodeBBCodes($newsXfields[$value[0]], false);
									$addXfields[$value[0]] = $parse->BB_Parse($parse->process($newsXfields[$value[0]]), false);
								}
							} elseif (isset($newsXfields[$value[0]])) {
								if ($value[3] == 'htmljs') {
									$addXfields[$value[0]] = $newsXfields[$value[0]];
								} else {
									$newsXfields[$value[0]] = html_entity_decode($newsXfields[$value[0]], ENT_QUOTES, $config['charset']);
									$addXfields[$value[0]] = trim(htmlspecialchars(strip_tags(stripslashes($newsXfields[$value[0]])), ENT_QUOTES, $config['charset']));
								}
							}
							
						}
					}
					
					$column = [];
					$valueColumn = [];

					if ($addXfields) {
						foreach ($addXfields as $xfName => $xfValue) {
							$column[] = '`xf_' . $xfName . '`';
							$valueColumn[] = $db->safesql($xfValue);
						}
					}
					
					if ($column && count($column) == count($valueColumn)) {
						$column[] = '`newsId`';
						$valueColumn[] = $row['id'];
						$db->query("INSERT INTO " . PREFIX . "_dle_filter_news (" . implode(', ', $column) . ") VALUES ('" . implode("', '", $valueColumn) . "');");
					}
				}
			}

			$stepCount++;
		}
		
		$newsData = $startCount + $stepCount;
		echo Helper::json(['status' => 'ok', 'newsData' => $newsData, 'text' => Data::get(['admin', 'fields', 'end_data'], 'lang')]);
	}
	
	/**
     * Создание триггеров
     *
     **/
	static function setTriggers()
	{
		global $db;
		
		$db->query(str_replace('{prefix}', PREFIX, self::$deleteTrigger));
		$db->query(str_replace('{prefix}', PREFIX, self::$updateTrigger));
		$db->query(str_replace('{prefix}', PREFIX, self::$insertTrigger));
		
		echo Helper::json(['text' => Data::get(['admin', 'fields', 'end_triggers'], 'lang'), 'status' => 'ok']);
	}
	
	/**
     * Запись/обновление данных по триггеру
     *
     **/
	static function updateNews()
	{
		global $db, $config;
		
		$parse = new ParseFilter();
		$parse->edit_mode = false;
		if ($config['allow_admin_wysiwyg']) {
			$parse->allow_code = false;
		}

		$fieldsVar = Data::receive('fields');
		$xfieldsVarDle = xfieldsload();
		$getIdTemp = [];

		$getFilterTemp = $db->query("SELECT * FROM " . PREFIX . "_dle_filter_news_temp ORDER BY tempId ASC LIMIT 5");
		while ($tempFilter = $db->get_row($getFilterTemp)) {
			if ($tempFilter['xfieldNew']) {
				$newsXfields = xfieldsdataload($tempFilter['xfieldNew']);
				$addXfields = [];
				
				if (!empty($newsXfields)) {
					foreach ($xfieldsVarDle as $name => $value) {
						if ($fieldsVar['status'][$value[0]] !== 'off' && isset($fieldsVar['status'][$value[0]])) {
							if ($fieldsVar['status'][$value[0]] == 'double') {
								$newsXfields[$value[0]] = floatval(preg_replace('/\s+/', '', str_replace(',', '.', $newsXfields[$value[0]])));
							} elseif ($fieldsVar['status'][$value[0]] == 'number') {
                                $newsXfields[$value[0]] = intval(preg_replace('/\s+/', '', $newsXfields[$value[0]]));
                            }
							
							if ($value[3] != 'select' && $value[3] != 'image' && $value[3] != 'file' && $value[3] != 'htmljs' && $value[8] == 0 && $value[6] == 0 && isset($newsXfields[$value[0]])) {
								if ($config['allow_admin_wysiwyg'] || $tempFilter['allow_br'] != 1) {
									$newsXfields[$value[0]] = $parse->decodeBBCodes($newsXfields[$value[0]], true, true);					
									$addXfields[$value[0]] = $parse->BB_Parse($parse->process($newsXfields[$value[0]]));
								} else {
									$newsXfields[$value[0]] = $parse->decodeBBCodes($newsXfields[$value[0]], false);
									$addXfields[$value[0]] = $parse->BB_Parse($parse->process($newsXfields[$value[0]]), false);
								}
							} elseif (isset($newsXfields[$value[0]])) {
								if ($value[3] == 'htmljs') {
									$addXfields[$value[0]] = $newsXfields[$value[0]];
								} else {
									$newsXfields[$value[0]] = html_entity_decode($newsXfields[$value[0]], ENT_QUOTES, $config['charset']);
									$addXfields[$value[0]] = trim(htmlspecialchars(strip_tags(stripslashes($newsXfields[$value[0]])), ENT_QUOTES, $config['charset']));
								}
							}
						}
					}
					
					$column = $valueColumn = $duplicate = [];
					if ($addXfields) {
						foreach ($addXfields as $xfName => $xfValue) {
							$xfValue = $db->safesql($xfValue);

							$duplicate[] = '`xf_' . $xfName . '`=VALUES(`xf_' . $xfName . '`)';
                            $column[] = '`xf_' . $xfName . '`';
							$valueColumn[] = $xfValue;
						}
					}
					
					if ($column && count($column) == count($valueColumn)) {
					    $column[] = '`newsId`';
					    $valueColumn[] = $tempFilter['newsId'];
					    $db->query("INSERT INTO " . PREFIX . "_dle_filter_news (" . implode(', ', $column) . ") VALUES ('" . implode("', '", $valueColumn) . "') ON DUPLICATE KEY UPDATE " . implode(', ', $duplicate) . ";");
					}
				}
			}
			
			$getIdTemp[] = $tempFilter['tempId'];
		}

		if ($getIdTemp) {
			$db->query("DELETE FROM " . PREFIX . "_dle_filter_news_temp WHERE tempId IN('" . implode("', '", $getIdTemp) . "')");
		}
	}

    /**
     * Удаление тригеров при выключении опции «Новый поиск»
     *
     * @param int $status
     **/
    static function triggersStatus($status)
    {
        global $db;

        if (!$status) {
            $db->query(str_replace('{prefix}', PREFIX, self::$dropTriggerDelete));
            $db->query(str_replace('{prefix}', PREFIX, self::$dropTriggerUpdate));
            $db->query(str_replace('{prefix}', PREFIX, self::$dropTriggerInsert));
        }
    }

	private function __construct() {}
    private function __wakeup() {}
    private function __clone() {}
    private function __sleep() {}
}
