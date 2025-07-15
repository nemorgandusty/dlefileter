<?php
/**
 * Условия
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;
setlocale(LC_NUMERIC, 'C');

class Conditions
{
    private static $stringLength = [];
    private static $instance = null;
    private static $row;
    private static $tag;

	/**
     * Одиночка
     *
     * @return   Conditions
     **/
    static function construct()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

	/**
     * Вызов обработки условий
     *
     * @param    string    	$template
	 * @param    array|bool	$row
     * @param    string    	$tag
     *
     * @return   string
     **/
    static function realize($template, $row = false, $tag = 'if')
    {
        self::$row = $row;
        self::$tag = $tag;

		if (!is_array($row) || is_array($row) && !count($row)) {
			return $template;
		}

        if (strpos($template, "[{$tag} ") !== false) {
            $template = preg_replace_callback("#\\[{$tag} (.+?)\\](.*?)\\[/{$tag}\\]#umis",
                [self::$instance, 'conditions'],
            $template);
        }

        return $template;
    }

	/**
     * Удаление оставшихся тегов
     *
     * @param    string    	$a
     * @return   string
     **/
	static function clean($a)
	{
	    $tag = self::$tag;
		if (strpos($a, '{' . self::$tag) !== false) {
			$a = preg_replace("#{{$tag}(.+?)}#is", '', $a);
		}
		
		if (strpos($a, "[{$tag}") !== false) {
			$a = preg_replace("#\[{$tag}(.+?)\](.*?)\[\/{$tag}\]#is", '', $a);
		}
		
		return $a;
	}

	/**
     * Удаление оставшихся тегов
     *
     * @param    array	$a
     * @return   array
     **/
	static function cleanArray($a)
	{
		foreach ($a as $k => $v) {
			if (strpos($a[$k], '[/') !== false) {
				$a[$k] = preg_replace('#\[(.+?)\](.*?)\[\/\\1\]#is', '', $a[$k]);
			}
			
			if (strpos($a[$k], '{') !== false) {
				$a[$k] = preg_replace('#{(.+?)}#is', '', $a[$k]);
			}
		}
		
		return $a;
	}

	/**
     * Проход по условиям
     *
     * @param    array	$pregArray
     * @return   string
     **/
    static function conditions($pregArray)
    {
        $tag = self::$tag;
        $globalKey = '';
        if (strpos($pregArray[0], "[{$tag} ") === false) {
            if (preg_match_all("#\[{$tag}([0-9]+)#is", $pregArray[0], $foundKey)) {
                $globalKey = $foundKey[1][0];
            }
        }

        if (strpos($pregArray[2], '[elif' . $globalKey) !== false) {
            $pregArray[2] = preg_replace("#\\[elif{$globalKey} (.+?)\\](.+?)\\[/elif{$globalKey}\\]#umis", '', $pregArray[2]);
        }

        if (strpos($pregArray[2], '[else') !== false) {
            if (strpos($pregArray[2], '[else' . $globalKey . ']')) {
                $pregArray[2] = $pregArray[2] . "[/{$tag}" . $globalKey . ']';
            }

            $pregArray[2] = preg_replace("#\\[else{$globalKey}\\](.+?)\\[/{$tag}{$globalKey}\\]#umis", '', $pregArray[2]);
        }

        $checkIf = self::conditionsRealize($pregArray[1], $pregArray[2]);

        if ($checkIf !== false) {
            return $checkIf;
        }

        if (strpos($pregArray[0], '[elif' . $globalKey) !== false) {
            preg_match_all("#\\[elif{$globalKey} (.+?)\\](.+?)\\[/elif{$globalKey}\\]#umis", $pregArray[0] , $pregElif);
            for ($i = 0; $i < count($pregElif); $i++) {
                $checkElif = self::conditionsRealize($pregElif[1][$i], $pregElif[2][$i]);
                if ($checkElif !== false) {
                    return $checkElif;
                }
            }
        }

        if (strpos($pregArray[0], '[else' . $globalKey) !== false) {
            preg_match_all("#\\[else{$globalKey}\\](.+?)\\[/{$tag}{$globalKey}\\]#umis", $pregArray[0], $pregElse);
            $pregElse[1][0] = self::matchNesting($pregElse[1][0]);

            return $pregElse[1][0];
        }

        return '';
    }

	/**
     * Проход по &&, ||
     *
     * @param    string		$condition
	 * @param    string		$return
     * @return   string|bool
     **/
    static function conditionsRealize($condition, $return)
    {
        $countCheck = 0;

        if (substr_count($condition, '||')) {
            $conditionOrArray = explode(' || ', $condition);
            for ($i = 0; $i < count($conditionOrArray); $i++) {

                if (substr_count($conditionOrArray[$i], '&&')) {
                    $conditionAndArray = explode(' && ', $conditionOrArray[$i]);

                    for ($j = 0; $j < count($conditionAndArray); $j++) {
                        if (self::conditionsMatching($conditionAndArray[$j])) {
                            $countCheck++;
                        }
                    }

                    if ($countCheck == count($conditionAndArray)) {
                        $return = self::matchNesting($return);
                        return $return;
                    } else {
                        $countCheck = 0;
                    }
                } elseif (self::conditionsMatching($conditionOrArray[$i])) {
                    $return = self::matchNesting($return);
                    return $return;
                }
            }
        } elseif (substr_count($condition, '&&')) {
            $conditionAndArray = explode(' && ', $condition);
            for ($i = 0; $i < count($conditionAndArray); $i++) {
                if (self::conditionsMatching($conditionAndArray[$i])) {
                    $countCheck++;
                } else {
                    return false;
                }
            }
            if ($countCheck == count($conditionAndArray)) {
                $return = self::matchNesting($return);
                return $return;
            }
        } elseif (self::conditionsMatching($condition)) {
            $return = self::matchNesting($return);
            return $return;
        }

        return false;
    }

	/**
     * Работа с проверкой условия
     *
     * @param    string		$condition
     * @return   bool
     **/
    static function conditionsMatching($condition)
    {
        preg_match("#(.+?)(>=|<=|<|>|!==|!=|==|=|!~|~)(.+?)$#uis", $condition, $conditionMatching);
		
		self::$stringLength = [];

		if (!$conditionMatching) {
			$conditionMatching[1] = $condition;
			$conditionMatching[2] = false;
			if (dle_strpos($conditionMatching[1], '!', 'UTF-8') === 0) {
				$conditionMatching[1] = str_replace('!', '', $conditionMatching[1]);
				$conditionMatching[3] = true;
			}
		}

		$conditionMatching[1] = self::$row[$conditionMatching[1]];

		if (!$conditionMatching[2]) {
			if ($conditionMatching[3]) {
				return empty($conditionMatching[1]) ? true : false;
			}
			return empty($conditionMatching[1]) ? false : true;
		}

		if (!is_numeric($conditionMatching[1]) && !$conditionMatching[1]) {
			return false;
		}

		
        if (self::$row[$conditionMatching[3]]) {
            $conditionMatching[1] = self::$row[$conditionMatching[3]];
        }

		$conditionMatching[1] = self::returnType($conditionMatching[1]);
		$conditionMatching[3] = self::returnType($conditionMatching[3]);

        switch ($conditionMatching[2]) {
            case '>':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] > $conditionMatching[3];
                break;
            case '>=':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] >= $conditionMatching[3];
                break;
            case '<':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] < $conditionMatching[3];
                break;
            case '<=':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] <= $conditionMatching[3];
                break;
            case '==':
            case '!==':
                $conditionMatching[1] = explode(',', $conditionMatching[1]);
                $conditionMatching[3] = explode(',', $conditionMatching[3]);
                $countMatch = 0;
                foreach ($conditionMatching[3] as $valMatch) {
                    if (in_array($valMatch, $conditionMatching[1])) {
                        $countMatch++;
                    }
                }

                if ($conditionMatching[2] == '==') {
                    return $countMatch == count($conditionMatching[3]);
                }

                return $countMatch == count($conditionMatching[3]) ? false : true;
                break;
            case '=':
                if (is_numeric($conditionMatching[1]) && substr_count($conditionMatching[1], ',') !== false || is_numeric($conditionMatching[3]) && substr_count($conditionMatching[3], ',') !== false) {
                    $conditionMatching[1] = explode(',', $conditionMatching[1]);
                    $conditionMatching[3] = explode(',', $conditionMatching[3]);

                    foreach ($conditionMatching[3] as $valMatch) {
                        if (in_array($valMatch, $conditionMatching[1])) {
                            return true;
                        }
                    }
                }

                return $conditionMatching[1] == $conditionMatching[3];
                break;
            case '!=':
                return $conditionMatching[1] != $conditionMatching[3];
                break;
            case '~':
                return dle_strpos($conditionMatching[1], $conditionMatching[3], 'UTF-8') === true;
                break;
            case '!~':
                return dle_strpos($conditionMatching[1], $conditionMatching[3], 'UTF-8') === false;
                break;
        }

        return false;
    }

	/**
     * Тип данных
     *
     * @param    mixed	$var
     * @return   mixed
     **/
    static function returnType($var)
    {
        if (is_numeric($var) && substr_count($var, ',') !== false) {
            return str_replace(' ', '', $var);
        } elseif (is_numeric($var)) {
            $var = is_int($var) ? intval($var) : floatval($var);
        } elseif (is_string($var)) {
            $var = trim($var);
            self::$stringLength[] = mb_strlen($var, 'UTF-8');
        }

        return $var;
    }


	/**
     * Вложенные условия
     *
     * @param    string		$condition
     * @return   string
     **/
    static function matchNesting($condition)
    {
        $tag = self::$tag;
        if (preg_match_all("#\[{$tag}([0-9]+)#is", $condition, $nestingIf)) {
            foreach ($nestingIf[1] as $key) {
                $condition = preg_replace_callback("#\\[{$tag}{$key} (.+?)\\](.*?)\\[/{$tag}{$key}\\]#umis",
                    [self::$instance, 'conditions'],
                $condition);
            }
        }

        return $condition;
    }

    private function __construct() {}
    private function __wakeup() {}
    private function __clone() {}
    private function __sleep() {}
}