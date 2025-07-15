<?php
/**
 * Логика фильтра
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;

setlocale(LC_NUMERIC, 'C');

use dle_template;

class Filter
{
	private static $instance = null;
	static $pageFilter = 0;
	static $filterData, $filterParam, $vars, $checks, $sqlWhere, $sortByOne, $globalTag, $seoData, $fieldsVar, $catVar, $seoPageArray, $exchange, $tempCopyFilterData;
	static $dleConfig, $dleDb, $dleCat, $dleMember, $dleXfields, $dleGroup, $modConfig;
	static $pageDLE, $orderBy, $urlFilter, $catId, $cleanUrl;
	static $innerTable = '';
	static $whereTable = '';
	static $seoView;
    static $tpl;
    static $setExclude = false;
    static $notLike = '';
    static $catArrayId;

	private static $reservedKeys = [
		'cat' => '',
        '!cat' => '',
		'o.cat' => '',
		'p.cat' => '',
		'sort' => '',
		'order' => ''
	];

	private static $orderByKeys = [
		'date' => 'date',
		'editdate' => 'e.editdate',
		'title' => 'title',
		'comm_num' => 'comm_num',
		'news_read' => 'e.news_read',
		'autor' => 'autor',
		'rating' => 'e.rating',
        'emote_one' => 'e.emoteOne',
        'emote_two' => 'e.emoteTwo',
        'emote_three' => 'e.emoteThree',
        'emote_four' => 'e.emoteFour',
        'emote_five' => 'e.emoteFive',
        'emote_six' => 'e.emoteSix',
	];

	static $dateSlider = [];

	/**
     * Конструктор
     *
	 * @return   Filter
     */
	static function construct()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
	
    /**
     * Старт модуля
     *
     * @param    array    $vars
	 * @return   Filter
     */
    static function load($vars = [])
    {
		global $config, $db, $cat_info, $member_id, $user_group;
		
		self::$dleConfig = $config;
		self::$dleDb = $db;
		self::$dleCat = $cat_info;
		self::$dleMember = $member_id;
		self::$dleXfields = xfieldsload();
		self::$modConfig = Data::receive('config');
		self::$fieldsVar = Data::receive('fields');
        self::$dleGroup = $user_group;

        self::$exchange = self::$catVar = self::$seoPageArray = self::$globalTag = [];

		self::$vars = $vars;
		self::$vars['data'] = $_GET['filter_data'] ?: self::$vars['data'];

		self::$seoView = new dle_template();
        self::$seoView->dir = TEMPLATE_DIR . '/lazydev/dle_filter';
        self::$seoView->load_template('seo.tpl');
		self::$sortByOne = false;

		if ($vars['crown']) {
            self::$globalTag['block'][] = '#\[dle-filter extra-page\](.*?)\[\/dle-filter\]#is';
            self::$globalTag['hide'][] = '#\[not-dle-filter extra-page\](.*?)\[\/not-dle-filter\]#is';
        }

		return self::$instance;
    }
	
	/**
     * Обработка данных фильтра
     *
	 * @return    Filter
     */
	static function getVar()
	{
		$tmp_Data = trim(strip_tags(str_ireplace(['<?', '?>', '$', '@'], '', self::$vars['data'])));
		$tmp_Data = Helper::cleanSlash($tmp_Data);
		
		$tmp_Data = explode(self::$vars['ajax'] === true ? '&' : '/', $tmp_Data);
		
		foreach ($tmp_Data as $value) {
			$tmpValue = explode('=', $value);
			if ($tmpValue[1] != '' && $tmpValue[1] != '&') {
				$tmpValue[0] = rawurldecode($tmpValue[0]);
				$tmpValue[1] = str_replace(['+', '%20'], ' ', rawurldecode($tmpValue[1]));
				if (self::$filterData[$tmpValue[0]]) {
					self::$filterData[$tmpValue[0]] .= ',' . $tmpValue[1];
				} else {
					self::$filterData[$tmpValue[0]] = $tmpValue[1];
				}
			}
		}
		
		return self::$instance;
	}

    /**
     * Получение всех подкатегорий родительской категории
     *
     * @param   int     $id
     */
    static function getAllCat($id)
    {
        foreach (self::$dleCat as $cats) {
            if ($cats['parentid'] == $id) {
                $cats['id'] = intval($cats['id']);
                self::$catArrayId[$cats['id']] = $cats['id'];
                self::getAllCat($cats['id']);
            }
        }
    }

	/**
     * Получаем страницу DataLife Engine
     *
	 * @return    Filter
     */
	static function getPage()
	{
		if (self::$modConfig['search_xfield'] == 1) {
            $letInXf = false;
			if (isset($_GET['xf']) || self::$vars['ajax'] && substr_count(self::$vars['url'], 'xfsearch/')) {
				self::$checks['xfsearch'] = true;

				$xf = $_GET['xf'] ?: self::$vars['url'];
				$xf = self::$dleConfig['version_id'] > 13.1 ? rawurldecode($xf) : urldecode($xf);
				$xf = Helper::cleanSlash($xf);
				$xf = explode('/', $xf);
				if (isset($_GET['xf']) && count($xf) == 2) {
					$xfName = totranslit(trim($xf[0]));
					$xfValue = htmlspecialchars(strip_tags(stripslashes(trim($xf[1]))), ENT_QUOTES, self::$dleConfig['charset']);
				} elseif (self::$vars['ajax'] && $xf[2] != '' && $xf[3] != self::$modConfig['filter_url'] && $xf[3] != '') {
					$xfName = totranslit(trim($xf[2]));
					$xfValue = htmlspecialchars(strip_tags(stripslashes(trim($xf[3]))), ENT_QUOTES, self::$dleConfig['charset']);
				}
				
				if ($xfName && $xfValue) {
					foreach (self::$dleXfields as $xfieldArray) {
						if ($xfieldArray[0] == $xfName && $xfieldArray[6] == 1) {
							$letInXf = true;
							break;
						}
					}

					if ($letInXf) {
						self::$seoPageArray['page-xf'] = 1;
                        self::$seoPageArray['xf.name'] = $xfName;
                        self::$seoPageArray['xf.value'] = str_replace(["&#039;", "&quot;"], ["'", '"'], $xfValue);

                        self::$globalTag['tag']['{dle-filter page-xf}'] = str_replace(["&#039;", "&quot;"], ["'", '"'], $xfValue);
                        self::$globalTag['block'][] = '#\[dle-filter page-xf\](.*?)\[\/dle-filter\]#is';
                        self::$globalTag['hide'][] = '#\[not-dle-filter page-xf\](.*?)\[\/not-dle-filter\]#is';

                        self::$seoView->set('{page-xf}', $xfValue);
                        self::$seoView->set_block("'\\[page-xf\\](.*?)\\[/page-xf\\]'si", '\\1');
						self::$seoView->set_block("'\\[not-page-xf\\](.*?)\\[/not-page-xf\\]'si", '');
						
						self::$pageDLE = 'xfsearch/' . $xfName . '/' . (self::$dleConfig['version_id'] > 13.1 ? rawurlencode(str_replace(["&#039;", "&quot;"], ["'", '"'], $xfValue)) : urlencode(str_replace("&#039;", "'", $xfValue))) . '/';
						$xfName = self::$dleDb->safesql($xfName);
						$xfValue = self::$dleDb->safesql($xfValue);

						self::$whereTable = " AND xf.tagname='{$xfName}' AND xf.tagvalue='{$xfValue}'";
						self::$innerTable = "INNER JOIN " . PREFIX . "_xfsearch xf ON (xf.news_id=d.id)";
					}
				}
			}

			if (!$letInXf) {
                self::$seoView->set_block("'\\[page-xf\\](.*?)\\[/page-xf\\]'si", '');
				self::$seoView->set_block("'\\[not-page-xf\\](.*?)\\[/not-page-xf\\]'si", '\\1');
            }
		}
		
		if (!self::$checks && self::$modConfig['search_tag'] == 1) {
			if (isset($_GET['tag']) || self::$vars['ajax'] && substr_count(self::$vars['url'], 'tags/')) {
				self::$checks['tag'] = true;
				
				if (isset($_GET['tag'])){
					$tag = $_GET['tag'];
				} elseif (self::$vars['url']) {
					$tagTemp = explode('/', self::$vars['url']);
					if ($tagTemp[2] != self::$modConfig['filter_url'] && $tagTemp[2] != '') {
						$tag = $tagTemp[2];
					}
				}
				
				if ($tag) {
					self::$seoPageArray['page-tag'] = 1;
                    self::$seoPageArray['tag.value'] = str_replace(["&#039;", "&quot;", "&amp;"], ["'", '"', "&"], $tag);

                    self::$globalTag['tag']['{dle-filter page-tag}'] = self::$dleConfig['version_id'] > 13.1 ? str_replace(["&#039;", "&quot;", "&amp;"], ["'", '"', "&"], $tag) : $tag;
                    self::$globalTag['block'][] = '#\[dle-filter page-tag\](.*?)\[\/dle-filter\]#is';
                    self::$globalTag['hide'][] = '#\[not-dle-filter page-tag\](.*?)\[\/not-dle-filter\]#is';
                    self::$seoView->set('{page-tag}', $tag);
                    self::$seoView->set_block("'\\[page-tag\\](.*?)\\[/page-tag\\]'si", '\\1');
					self::$seoView->set_block("'\\[not-page-tag\\](.*?)\\[/not-page-tag\\]'si", '');
					
					$tag = self::$dleConfig['version_id'] > 13.1 ? rawurldecode($tag) : urldecode($tag);
					$tag = Helper::cleanSlash($tag);
					$tag = htmlspecialchars(strip_tags(stripslashes(trim($tag))), ENT_COMPAT, self::$dleConfig['charset']);
					$urlTag = self::$dleConfig['version_id'] > 13.1 ? rawurlencode(str_replace(["&#039;", "&quot;", "&amp;"], ["'", '"', "&"], $tag)) : urlencode($tag);
					$tag = self::$dleDb->safesql($tag);
					
					self::$pageDLE = 'tags/' . $urlTag . '/';
					self::$whereTable = " AND t.tag='{$tag}'";
					self::$innerTable = "INNER JOIN " . PREFIX . "_tags t ON (t.news_id=d.id)";
				}
			}

			if (!$tag) {
                self::$seoView->set_block("'\\[page-tag\\](.*?)\\[/page-tag\\]'si", '');
				self::$seoView->set_block("'\\[not-page-tag\\](.*?)\\[/not-page-tag\\]'si", '\\1');
            }
		}
		
		if (!self::$checks && self::$modConfig['search_cat'] == 1) {
            $category_id = 0;
			if (isset($_GET['cat']) || self::$vars['ajax'] && self::$vars['url'] != '') {
				self::$checks['cat'] = true;
				$cat = $_GET['cat'] ?: self::$vars['url'];
				$cat = explode('/' . self::$modConfig['filter_url'] .'/', $cat);
				$cat = explode('/page', $cat[0])[0];
				$cat = Helper::cleanSlash($cat);
				$cat = explode('/', $cat);
				$cat = trim(end($cat));
				if ($cat != '') {
					$category_id = get_ID(self::$dleCat, $cat);

					if ($category_id > 0) {
						self::$seoPageArray['page-cat'] = 1;
                        self::$globalTag['tag']['{dle-filter page-cat}'] = self::$dleCat[$category_id]['name'];
                        self::$globalTag['block'][] = '#\[dle-filter page-cat\](.*?)\[\/dle-filter\]#is';
                        self::$globalTag['hide'][] = '#\[not-dle-filter page-cat\](.*?)\[\/not-dle-filter\]#is';

                        self::$seoPageArray['cat.id'] = $category_id;
                        self::$seoPageArray['cat.name'] = self::$dleCat[$category_id]['name'];
                        self::$seoView->set('{page-cat}', self::$dleCat[$category_id]['name']);
                        self::$seoView->set_block("'\\[page-cat\\](.*?)\\[/page-cat\\]'si", '\\1');
						self::$seoView->set_block("'\\[not-page-cat\\](.*?)\\[/not-page-cat\\]'si", '');

						self::$catId = $category_id;
						if (self::$modConfig['search_cat_all']) {
                            self::$catArrayId = [$category_id];

                            self::getAllCat(self::$catId);

                            if (self::$dleConfig['version_id'] > 13.1) {
                                $category_id = implode("','", self::$catArrayId);
                            } else {
                                $category_id = implode((self::$dleConfig['allow_multi_category'] ? '|' : "','"), self::$catArrayId);
                            }
                        }

						self::$pageDLE = get_url(self::$catId) . '/';
						if (self::$dleConfig['version_id'] > 13.1) {
							self::$whereTable = '';
							self::$innerTable = "INNER JOIN (SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . $category_id . "')) c ON (d.id=c.news_id)";
						} else {
						    self::$sqlWhere[] = self::$dleConfig['allow_multi_category'] ? "category REGEXP '([[:punct:]]|^)(" . $category_id . ")([[:punct:]]|$)'" : "category IN ('" . $category_id . "')";
						}
					}
				}
			}

			if (!$category_id) {
                self::$seoView->set_block("'\\[page-cat\\](.*?)\\[/page-cat\\]'si", '');
				self::$seoView->set_block("'\\[not-page-cat\\](.*?)\\[/not-page-cat\\]'si", '\\1');
            }
		}
		
		if (self::$pageDLE == '/') {
			self::$pageDLE = '';
		}
		
		return self::$instance;
	}
	
	/**
     * Разбор данных фильтра
     *
	 * @return    Filter
     */
	static function filterOptions()
	{
        self::setRelations();

		if (self::$filterData === null) {
			self::$filterData = [];
		}

        self::$filterParam = array_diff_key(self::$filterData, self::$reservedKeys);

		foreach (self::$filterParam as $key => $item) {
			$matchesTemp = $tempArray = [];
			$valueTemp = '';
			$originalKey = $key;

			self::$setExclude = false;
			self::$notLike = '';
            if ($key[0] == '!') {
                $key = substr($key, 1);
                self::$setExclude = true;
            }

			$andMod = false;
			if ($key[0] == 'n' && $key[1] == '.') {
				$key = str_replace('n.', '', $key);
				if ($key) {
					$andMod = true;
				} else {
					continue;
				}
			}

			$firstKey = $key[0];
			$secondKey = $key[1];
			
			self::$seoView->set_block("'\\[{$key}\\](.*?)\\[/{$key}\\]'si", '\\1');
			if (($firstKey == 'r' || $firstKey == 'c') && $secondKey == '.') {
				$tempArray = explode($firstKey == 'c' ? ',' : ';', $item);
				
				$tempArray[0] = self::typeNumber($tempArray[0]);

				if ($firstKey == 'r' && in_array($key, ['r.date', 'r.edit'])) {
                    $tempValueDate = Helper::jsDate($tempArray[0]);

                    self::$dateSlider[$key . '.from'] = $tempValueDate;

                    $news_date = $tempValueDate;
                    self::$seoView->copy_template = preg_replace_callback('#\{' . $key . '.from date=(.+?)\}#i', 'formdate', self::$seoView->copy_template);
                    self::$seoView->set('{' . $key . '.from}', date($tempValueDate, 'Y.m.d'));

                    self::$globalTag['tag']['{dle-filter ' . $key . '.from}'] = date($tempValueDate, 'Y.m.d');
                    self::$globalTag['block'][] = '#\[dle-filter ' . $key . '.from\](.*?)\[\/dle-filter\]#is';
                    self::$globalTag['hide'][] = '#\[not-dle-filter ' . $key . '.from\](.*?)\[\/not-dle-filter\]#is';
                    self::$globalTag['block'][] = '#\[dle-filter ' . $key . '\](.*?)\[\/dle-filter\]#is';
                    self::$globalTag['hide'][] = '#\[not-dle-filter ' . $key . '\](.*?)\[\/not-dle-filter\]#is';

                    if (isset($tempArray[1]) && $tempArray[1] > 0) {
                        $tempArray[1] = self::typeNumber($tempArray[1]);
                        $tempValueDate = Helper::jsDate($tempArray[1]);

                        self::$dateSlider[$key . '.to'] = $tempValueDate;

                        $news_date = $tempValueDate;
                        self::$seoView->copy_template = preg_replace_callback('#\{' . $key . '.to date=(.+?)\}#i', 'formdate', self::$seoView->copy_template);
                        self::$seoView->set('{' . $key . '.to}', date($tempValueDate, 'Y.m.d'));

                        self::$globalTag['tag']['{dle-filter ' . $key . '.to}'] = date($tempValueDate, 'Y.m.d');
                        self::$globalTag['block'][] = '#\[dle-filter ' . $key . '.to\](.*?)\[\/dle-filter\]#is';
                        self::$globalTag['hide'][] = '#\[not-dle-filter ' . $key . '.to\](.*?)\[\/not-dle-filter\]#is';
                    }
                } else {
                    self::$seoView->set('{' . $key . '.from}', $tempArray[0]);

                    self::$globalTag['tag']['{dle-filter ' . $key . '.from}'] = $tempArray[0];
                    self::$globalTag['block'][] = '#\[dle-filter ' . $key . '.from\](.*?)\[\/dle-filter\]#is';
                    self::$globalTag['hide'][] = '#\[not-dle-filter ' . $key . '.from\](.*?)\[\/not-dle-filter\]#is';

                    self::$globalTag['block'][] = '#\[dle-filter ' . $key . '\](.*?)\[\/dle-filter\]#is';
                    self::$globalTag['hide'][] = '#\[not-dle-filter ' . $key . '\](.*?)\[\/not-dle-filter\]#is';

                    if (isset($tempArray[1]) && $tempArray[1] > 0) {
                        $tempArray[1] = self::typeNumber($tempArray[1]);
                        self::$seoView->set('{' . $key . '.to}', $tempArray[1]);

                        self::$globalTag['tag']['{dle-filter ' . $key . '.to}'] = $tempArray[1];
                        self::$globalTag['block'][] = '#\[dle-filter ' . $key . '.to\](.*?)\[\/dle-filter\]#is';
                        self::$globalTag['hide'][] = '#\[not-dle-filter ' . $key . '.to\](.*?)\[\/not-dle-filter\]#is';
                    }
                }
				$tempArray = [];
			} else {
				self::$globalTag['tag']['{dle-filter ' . $originalKey . '}'] = str_replace(',', ', ', $item);
                self::$globalTag['block'][] = '#\[dle-filter ' . $originalKey . '\](.*?)\[\/dle-filter\]#is';
                self::$globalTag['hide'][] = '#\[not-dle-filter ' . $originalKey . '\](.*?)\[\/not-dle-filter\]#is';

                self::$seoView->set('{' . $originalKey . '}', str_replace(',', ', ', $item));
				if (strpos(self::$seoView->copy_template, '{' . $originalKey . ' limit=') !== false) {
				    $kPreg = preg_quote($originalKey);
                    self::$seoView->copy_template = preg_replace_callback("#\{".$kPreg." limit=(.+)\}#is", function($match) use($item) {
                        return self::pregTag($match, $item);
                    }, self::$seoView->copy_template);
                }
			}
			
			$item = explode(',', $item);
			if ($firstKey == 'l' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('l.', '', $key)));
                if (self::$setExclude) {
                    self::$notLike = ' NOT ';
                }
				if ($key) {
					foreach ($item as $value) {
						$value = self::$dleDb->safesql($value);
						$tempArray[] = "{$key}" . self::$notLike . " LIKE '%{$value}%'";
					}

					self::$sqlWhere[] =  '(' . implode($andMod ? ' AND ' : ' OR ', $tempArray) . ')';
				}
			} elseif ($firstKey == 'm' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('m.', '', $key)));
                if (self::$setExclude) {
                    self::$notLike = ' NOT ';
                }
				if ($key) {
					if ($andMod) {
						foreach ($item as $value) {
						    self::$sqlWhere[] = "{$key}" . self::$notLike . " REGEXP '([[:punct:]]|[[:space:]]|^)(" . self::$dleDb->safesql($value) . ")([[:punct:]]|[[:space:]]|$)'";
						}
					} else {
						$valueTemp = self::$dleDb->safesql(implode('|', $item));
						self::$sqlWhere[] = "{$key}" . self::$notLike . " REGEXP '([[:punct:]]|[[:space:]]|^)(" . $valueTemp . ")([[:punct:]]|[[:space:]]|$)'";
					}
				}
			} elseif ($firstKey == 's' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('s.', '', $key)));
				if ($key) {
                    if (self::$setExclude) {
                        self::$notLike = '!';
                    }
					foreach ($item as $value) {
						$value = self::$dleDb->safesql($value);
						$tempArray[] = "{$key}" . self::$notLike . "='{$value}'";
					}

					self::$sqlWhere[] =  '(' . implode($andMod ? ' AND ' : ' OR ', $tempArray) . ')';
				}
			} elseif ($firstKey == 'r' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('r.', '', $key)));
				if ($key) {
					$tempArray = explode(';', $item[0]);

					$tempArray[0] = self::$dleDb->safesql(self::typeNumber($tempArray[0]));
					
					if (isset($tempArray[1])) {
						$tempArray[1] = self::$dleDb->safesql(self::typeNumber($tempArray[1]));
					}
					
					if ($tempArray[1] > 0 && $tempArray[0] >= 0) {
						if ($key == 'prate') {
                            self::$sqlWhere[] = !self::$dleConfig['rating_type']
                                ?
                                "CEIL(e.rating / e.vote_num) >= {$tempArray[0]} AND CEIL(e.rating / e.vote_num) <= {$tempArray[1]}"
                                :
                                self::$sqlWhere[] = "e.rating >= {$tempArray[0]} AND e.rating <= {$tempArray[1]}";
						} elseif ($key == 'date') {
                            $tempArray[0] = Helper::jsDate($tempArray[0]);
                            $tempArray[1] = Helper::jsDate($tempArray[1]);

                            self::$sqlWhere[] = "UNIX_TIMESTAMP(date) >= {$tempArray[0]} AND UNIX_TIMESTAMP(date) <= {$tempArray[1]}";
                        } elseif ($key == 'edit') {
                            $tempArray[0] = Helper::jsDate($tempArray[0]);
                            $tempArray[1] = Helper::jsDate($tempArray[1]);

                            self::$sqlWhere[] = "e.editdate >= {$tempArray[0]} AND e.editdate <= {$tempArray[1]}";
                        } else {
                            $checkXf = array_filter(self::$dleXfields, function ($item) use($key) {
                                return $item[0] == $key;
                            });

                            if ($checkXf) {
                                self::$sqlWhere[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}` >= {$tempArray[0]} AND f.`xf_{$key}` <= {$tempArray[1]}" : self::$sqlWhere[] = "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) >= {$tempArray[0]} AND ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) <= {$tempArray[1]}";
                            } else {
                                self::$sqlWhere[] = "`{$key}` >= {$tempArray[0]} AND `{$key}` <= {$tempArray[1]}";
                            }
						}

						self::$seoData['r.' . $key . '.from'] = $tempArray[0];
						self::$seoData['r.' . $key . '.to'] = $tempArray[1];
					} elseif ($tempArray[0] >= 0) {
						if ($key == 'prate') {
						    self::$sqlWhere[] = !self::$dleConfig['rating_type'] ? "CEIL(e.rating / e.vote_num) >= {$tempArray[0]}" : "e.rating >= {$tempArray[0]}";
						} elseif ($key == 'pdate') {
                            $tempArray[0] = Helper::jsDate($tempArray[0]);
                            self::$sqlWhere[] = "UNIX_TIMESTAMP(date) >= {$tempArray[0]}";
                        } elseif ($key == 'pedit') {
                            $tempArray[0] = Helper::jsDate($tempArray[0]);
                            self::$sqlWhere[] = "e.editdate >= {$tempArray[0]}";
                        } else {
                            $checkXf = array_filter(self::$dleXfields, function ($item) use($key) {
                                return $item[0] == $key;
                            });

                            if ($checkXf) {
                                self::$sqlWhere[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}` >= {$tempArray[0]}" : "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) >= {$tempArray[0]}";
                            } else {
                                self::$sqlWhere[] = "`{$key}` >= {$tempArray[0]}";
                            }
						}

						self::$seoData['r.' . $key . '.from'] = $tempArray[0];
					} else {
						unset(self::$filterData[$originalKey]);
					}
				}
			} elseif ($firstKey == 'c' && $secondKey == '.') {
                $key = self::$dleDb->safesql(trim(str_replace('c.', '', $key)));
                if ($key) {
                    $tempArray = [];

                    $tempArray[0] = self::$dleDb->safesql(self::typeNumber($item[0]));

                    if (isset($item[1])) {
                        $tempArray[1] = self::$dleDb->safesql(self::typeNumber($item[1]));
                    } else {
                        unset(self::$filterData[$originalKey]);
                        continue;
                    }

                    if (!is_numeric($tempArray[0]) && !is_numeric($tempArray[1])) {
                        unset(self::$filterData[$originalKey]);
                        continue;
                    }

                    if ($key == 'prate') {
                        self::$sqlWhere[] = !self::$dleConfig['rating_type']
                        ?
                            "CEIL(e.rating / e.vote_num) >= {$tempArray[0]} AND CEIL(e.rating / e.vote_num) <= {$tempArray[1]}"
                        :
                            "e.rating >= {$tempArray[0]} AND e.rating <= {$tempArray[1]}";
                    } else {
                        self::$sqlWhere[] = self::$modConfig['new_search'] == 1
                            ?
                                "f.`xf_{$key}` >= {$tempArray[0]} AND f.`xf_{$key}` <= {$tempArray[1]}"
                            :
                                self::$sqlWhere[] = "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) >= {$tempArray[0]} AND ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) <= {$tempArray[1]}";
                    }

                    self::$seoData['c.' . $key . '.from'] = $tempArray[0];
                    self::$seoData['c.' . $key . '.to'] = $tempArray[1];
                }
            } elseif ($firstKey == 'j' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('j.', '', $key)));

				if ($key) {
                    if (self::$setExclude) {
                        self::$notLike = ' NOT ';
                    }
					$matchesTemp = explode(';', $key);
					foreach ($matchesTemp as $nameKey) {
						if (substr_count($nameKey, 'p.')) {
							$nameKey = self::$dleDb->safesql(str_replace('p.', '', $nameKey));
							$valueTemp = self::$dleDb->safesql($item[0]);
							$tempArray[] = $nameKey . " " . self::$notLike . "LIKE '%{$valueTemp}%'";
						} else {
							$nameKey = self::$dleDb->safesql(str_replace('x.', '', $nameKey));
							$valueTemp = self::typeXfield($nameKey, $item[0]);
							$tempArray[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$nameKey}` " . self::$notLike . "LIKE '%{$valueTemp}%'" : "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '" . $nameKey . "|', -1), '||', 1) " . self::$notLike . "LIKE '%{$valueTemp}%'";
						}
					}
					
					self::$sqlWhere[] = '(' . implode(' OR ', $tempArray) . ')';
				}
			} elseif ($firstKey == 'f' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('f.', '', $key)));
				if ($key) {
					if ($key != 'pdate' && $key != 'pedit') {
						$item[0] = self::$dleDb->safesql(self::typeNumber($item[0]));
					}
					
					if ($key == 'prate') {
					    self::$sqlWhere[] = !self::$dleConfig['rating_type'] ? "CEIL(e.rating / e.vote_num) >= {$item[0]}" : "e.rating >= {$item[0]}";
					} elseif ($key == 'pdate') {
						$item[0] = date('Y-m-d', strtotime($item[0]));
						if ($item[0] != '1970-01-01') {
							self::$sqlWhere[] = "DATE(date) >= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} elseif ($key == 'pedit') {
						$item[0] = strtotime($item[0]);
						if ($item[0]) {
							self::$sqlWhere[] = "e.editdate >= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} else {
					    self::$sqlWhere[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}` >= {$item[0]}" : "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) >= {$item[0]}";
					}
				}
			} elseif ($firstKey == 't' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('t.', '', $key)));
				if ($key) {
					if ($key != 'pdate' && $key != 'pedit') {
						$item[0] = self::$dleDb->safesql(self::typeNumber($item[0]));
					}
					
					if ($key == 'prate') {
					    self::$sqlWhere[] = !self::$dleConfig['rating_type'] ? "CEIL(e.rating / e.vote_num) <= {$item[0]}" : "e.rating <= {$item[0]}";
					} elseif ($key == 'pdate') {
						$item[0] = date('Y-m-d', strtotime($item[0]));
						if ($item[0] != '1970-01-01') {
							self::$sqlWhere[] = "DATE(date) <= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} elseif ($key == 'pedit') {
						$item[0] = strtotime($item[0]);
						if ($item[0]) {
							self::$sqlWhere[] = "e.editdate <= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} else {
					    self::$sqlWhere[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}` <= {$item[0]}" : "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) <= {$item[0]}";
					}
				}
			} elseif ($firstKey == 'g' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('g.', '', $key)));
				if ($key) {
                    self::$sqlWhere[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}`<>''" : "xfields LIKE '%{$key}|%'";
				}
			} elseif ($firstKey == 'v' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('v.', '', $key)));
				if ($key) {
					foreach ($item as $value) {
						$valueTemp = self::typeXfield($key, $value);
                        $tempArray[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}` NOT LIKE '%{$valueTemp}%'" : "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) NOT LIKE '%{$valueTemp}%'";
					}

					self::$sqlWhere[] = '(' . implode(' AND ', $tempArray) . ')';
				}
			} elseif ($firstKey == 'e' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('e.', '', $key)));
				if ($key) {
				    self::$sqlWhere[] = self::$modConfig['new_search'] == 1 ? "!f.`xf_{$key}`" : "xfields NOT LIKE '%{$key}|%'";
				}
			} else {
				if ($firstKey == 'b' && $secondKey == '.') {
					$key = self::$dleDb->safesql(trim(str_replace('b.', '', $key)));
					if ($key) {
                        if (self::$setExclude) {
                            self::$notLike = self::$modConfig['new_search'] == 1 ? '!' : ' NOT ';
                        }
						foreach ($item as $value) {
							$valueTemp = self::typeXfield($key, $value);
							$tempArray[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}` " . self::$notLike . "= '{$valueTemp}'" : "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) " . self::$notLike . "LIKE '{$valueTemp}'";
						}

						self::$sqlWhere[] =  '(' . implode(' OR ', $tempArray) . ')';
					}
				} else {
					$key = self::$dleDb->safesql($key);
                    if (self::$setExclude) {
                        self::$notLike = ' NOT ';
                    }
					foreach ($item as $value) {
						$valueTemp = self::typeXfield($key, $value);
						$tempArray[] = self::$modConfig['new_search'] == 1 ? "f.`xf_{$key}` " . self::$notLike . "LIKE '%{$valueTemp}%'" : "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) " . self::$notLike . "LIKE '%{$valueTemp}%'";
					}

					self::$sqlWhere[] =  '(' . implode($andMod ? ' AND ' : ' OR ', $tempArray) . ')';
				}
			}
			
			unset($matches);
			unset($tempArray);
		}
		
		// Страница фильтра
		if (substr_count(self::$vars['data'], '/page/') > 0) {
			$page = explode('/page/', self::$vars['data']);
			$page = intval(str_ireplace('/', '', $page[1]));
			if ($page > 0) {
                self::$seoPageArray['filter-page'] = self::$pageFilter = $page;
				self::$seoView->set_block("'\\[filter-page\\](.*?)\\[/filter-page\\]'si", '\\1');
				self::$seoView->set('{filter-page}', self::$pageFilter);

				self::$globalTag['tag']['{dle-filter page}'] = self::$pageFilter;
                self::$globalTag['block'][] = '#\[dle-filter page\](.*?)\[\/dle-filter\]#is';
                self::$globalTag['hide'][] = '#\[not-dle-filter page\](.*?)\[\/not-dle-filter\]#is';
			} else {
                self::$seoPageArray['filter-page'] = self::$pageFilter = 0;
				self::$seoView->set_block("'\\[filter-page\\](.*?)\\[/filter-page\\]'si", '');
			}
		}
		
		// Отдельные параметры
        if (self::$filterData['!cat']) {
            $paramCat = explode(',', self::$filterData['!cat']);
            $tempCatName = [];
            $catEx = [];

            foreach ($paramCat as $value) {
                if (($value = intval($value)) > 0 && self::$dleCat[$value] && self::$catId != $value) {
                    if (self::$modConfig['exclude_categories'] && in_array($value, self::$modConfig['exclude_categories'])) {
                        continue;
                    }

                    $catEx[] = self::$modConfig['exclude_categories'][] = $value;
                    $tempCatName[$value] = self::$dleCat[$value]['name'];
                }
            }

            if ($catEx) {
                self::$filterData['!cat'] = implode(',', $catEx);

                $tempCatName = implode(', ', $tempCatName);
                self::$globalTag['tag']['{dle-filter !cat}'] = $tempCatName;
                self::$globalTag['block'][] = '#\[dle-filter !cat\](.*?)\[\/dle-filter\]#is';
                self::$globalTag['hide'][] = '#\[not-dle-filter !cat\](.*?)\[\/not-dle-filter\]#is';
                self::$seoView->set('{!cat}', $tempCatName);
                if (strpos(self::$seoView->copy_template, '{!cat limit=') !== false) {
                    self::$seoView->copy_template = preg_replace_callback("#\{!cat limit=(.+)\}#is", function($match) use($tempCatName) {
                        return self::pregTag($match, $tempCatName);
                    }, self::$seoView->copy_template);
                }
                self::$seoView->set_block("'\\[!cat\\](.*?)\\[/!cat\\]'si", '\\1');
            } else {
                self::$seoView->set('{!cat}', '');
                self::$seoView->set_block("'\\[!cat\\](.*?)\\[/!cat\\]'si", '');
                unset(self::$filterData['!cat']);
            }

            unset($tempCatName);
        } else {
            self::$seoView->set_block("'\\[!cat\\](.*?)\\[/!cat\\]'si", '');
        }

        if (self::$filterData['cat']) {
            $paramCat = explode(',', self::$filterData['cat']);
            $tempCatName = [];

            foreach ($paramCat as $value) {
                if (($value = intval($value)) > 0 && self::$dleCat[$value] && self::$catId != $value) {
                    if (self::$modConfig['exclude_categories'] && in_array($value, self::$modConfig['exclude_categories'])) {
                        continue;
                    }

                    self::$catVar['cat'][$value] = $value;
                    $tempCatName[$value] = self::$dleCat[$value]['name'];
                }
            }

            if (self::$catVar['cat']) {
                self::$filterData['cat'] = implode(',', self::$catVar['cat']);

                $tempCatName = implode(', ', $tempCatName);
                self::$globalTag['tag']['{dle-filter cat}'] = $tempCatName;
                self::$globalTag['block'][] = '#\[dle-filter cat\](.*?)\[\/dle-filter\]#is';
                self::$globalTag['hide'][] = '#\[not-dle-filter cat\](.*?)\[\/not-dle-filter\]#is';
                self::$seoView->set('{cat}', $tempCatName);
                if (strpos(self::$seoView->copy_template, '{cat limit=') !== false) {
                    self::$seoView->copy_template = preg_replace_callback("#\{cat limit=(.+)\}#is", function($match) use($tempCatName) {
                        return self::pregTag($match, $tempCatName);
                    }, self::$seoView->copy_template);
                }
                self::$seoView->set_block("'\\[cat\\](.*?)\\[/cat\\]'si", '\\1');
            } else {
                self::$seoView->set('{cat}', '');
                self::$seoView->set_block("'\\[cat\\](.*?)\\[/cat\\]'si", '');
                unset(self::$filterData['cat']);
            }

            unset($tempCatName);
        } else {
            self::$seoView->set_block("'\\[cat\\](.*?)\\[/cat\\]'si", '');
        }

        if (self::$filterData['o.cat']) {
            $paramCat = explode(',', self::$filterData['o.cat']);
            $tempCatName = [];
            foreach ($paramCat as $value) {
                if (($value = intval($value)) > 0 && self::$dleCat[$value] && self::$catId != $value) {
                    if (self::$catVar['cat'] && self::$catVar['cat'][$value]) {
                        continue;
                    }
                    if (self::$modConfig['exclude_categories'] && in_array($value, self::$modConfig['exclude_categories'])) {
                        continue;
                    }
                    self::$catVar['o.cat'][$value] = $value;
                    $tempCatName[] = self::$dleCat[$value]['name'];
                }
            }

            if (self::$catVar['o.cat']) {
                self::$filterData['o.cat'] = implode(',', self::$catVar['o.cat']);

                $tempCatName = implode(', ', $tempCatName);
                self::$globalTag['tag']['{dle-filter o.cat}'] = $tempCatName;
                self::$globalTag['block'][] = '#\[dle-filter o.cat\](.*?)\[\/dle-filter\]#is';
                self::$globalTag['hide'][] = '#\[not-dle-filter o.cat\](.*?)\[\/not-dle-filter\]#is';
                self::$seoView->set('{o.cat}', $tempCatName);
                if (strpos(self::$seoView->copy_template, '{o.cat limit=') !== false) {
                    self::$seoView->copy_template = preg_replace_callback("#\{o.cat limit=(.+)\}#is", function($match) use($tempCatName) {
                        return self::pregTag($match, $tempCatName);
                    }, self::$seoView->copy_template);
                }
                self::$seoView->set_block("'\\[o.cat\\](.*?)\\[/o.cat\\]'si", '\\1');
            } else {
                unset(self::$filterData['o.cat']);
                self::$seoView->set('{o.cat}', '');
                self::$seoView->set_block("'\\[o.cat\\](.*?)\\[/o.cat\\]'si", '');
            }

            unset($tempCatName);
        } else {
            self::$seoView->set_block("'\\[o.cat\\](.*?)\\[/o.cat\\]'si", '');
        }

        $arrayPCat = [];
        if (self::$filterData['p.cat']) {
            $paramCat = explode(',', self::$filterData['p.cat']);
            $tempCatName = [];

            foreach ($paramCat as $value) {
                if (($value = intval($value)) > 0 && self::$dleCat[$value] && self::$catId != $value) {
                    if (self::$modConfig['exclude_categories'] && in_array($value, self::$modConfig['exclude_categories'])) {
                        continue;
                    }

                    $arrayPCat[] = Helper::getAllCats($value);
                }
            }

            $arrayPCat = implode('|', $arrayPCat);
            $arrayPCat = explode('|', $arrayPCat);

            foreach ($arrayPCat as $cats) {
                if (self::$catVar['cat'] && self::$catVar['cat'][$cats] || self::$catVar['o.cat'] && self::$catVar['o.cat'][$cats]) {
                    continue;
                }
                if (self::$modConfig['exclude_categories'] && in_array($cats, self::$modConfig['exclude_categories'])) {
                    continue;
                }

                if (self::$catId != $cats) {
                    self::$catVar['p.cat'][$cats] = intval($cats);
                    $tempCatName[$cats] = self::$dleCat[$cats]['name'];
                }
            }

            if (self::$catVar['p.cat']) {
                $tempCatName = implode(', ', $tempCatName);
                self::$globalTag['tag']['{dle-filter p.cat}'] = $tempCatName;
                self::$globalTag['block'][] = '#\[dle-filter p.cat\](.*?)\[\/dle-filter\]#is';
                self::$globalTag['hide'][] = '#\[not-dle-filter p.cat\](.*?)\[\/not-dle-filter\]#is';
                self::$seoView->set('{p.cat}', $tempCatName);
                if (strpos(self::$seoView->copy_template, '{p.cat limit=') !== false) {
                    self::$seoView->copy_template = preg_replace_callback("#\{p.cat limit=(.+)\}#is", function($match) use($tempCatName) {
                        return self::pregTag($match, $tempCatName);
                    }, self::$seoView->copy_template);
                }
                self::$seoView->set_block("'\\[p.cat\\](.*?)\\[/p.cat\\]'si", '\\1');
            } else {
                unset(self::$filterData['p.cat']);
                self::$seoView->set('{p.cat}', '');
                self::$seoView->set_block("'\\[p.cat\\](.*?)\\[/p.cat\\]'si", '');
            }

            unset($tempCatName);
        }  else {
            self::$seoView->set_block("'\\[p.cat\\](.*?)\\[/p.cat\\]'si", '');
        }

        self::workWithCat();

        if (self::$dleConfig['no_date'] && !self::$dleConfig['news_future']) {
            self::$sqlWhere[] = "date < '" . date ('Y-m-d H:i:s', time()) . "'";
        }

        if (self::$modConfig['allow_main']) {
            self::$sqlWhere[] = "allow_main=1";
        }

		if (isset(self::$modConfig['excludeNews'])) {
			$tempArray = [];

			foreach (self::$modConfig['excludeNews'] as $value) {
				if (($value = intval($value)) > 0) {
					$tempArray[] = $value;
				}
			}
			
			if ($tempArray) {
				self::$sqlWhere[] = "id NOT IN ('" . implode("','", $tempArray) . "')";
			}
			
			unset($tempArray);
		}
		
		self::$seoView->compile('seo');
		
		return self::$instance;
	}

	/**
	 * Работа с категориями в фильтре
	 *
	 */
	static function workWithCat()
    {
        $where = [];
        $categoryExclude = [];

        if (self::$modConfig['exclude_categories']) {
            foreach (self::$modConfig['exclude_categories'] as $value) {
                if (($value = intval($value)) > 0) {
                    $categoryExclude[] = $value;
                }
            }
        }

        if (!self::$dleGroup[self::$dleMember['user_group']]['allow_short']) {
            $tempArray = explode(',', self::$dleGroup[self::$dleMember['user_group']]['allow_short']);
            foreach ($tempArray as $value) {
                if (($value = intval($value)) > 0) {
                    $categoryExclude[] = $value;
                }
            }
        }

        if (self::$dleConfig['version_id'] > 13.1) {
            $sqlCat = [];

            if (self::$catVar['cat']) {
                $tempArraySqlCat = [];
                foreach (self::$catVar['cat'] as $v) {
                    $tempArraySqlCat[] = "FIND_IN_SET('" . $v . "', ci) > 0";
                }
                $sqlCat[] = '(' . implode(' AND ', $tempArraySqlCat) . ')';
            }

            if (self::$catVar['o.cat']) {
                if (self::$catVar['cat'] || self::$catVar['p.cat']) {
                    $tempArraySqlCat = [];
                    foreach (self::$catVar['o.cat'] as $v) {
                        $tempArraySqlCat[] = "FIND_IN_SET('" . $v . "', ci) > 0";
                    }
                    $sqlCat[] = '(' . implode(' OR ', $tempArraySqlCat) . ')';
                } else {
                    $sqlCat[] = "cat_id IN (" . implode(",", self::$catVar['o.cat']) . ")";
                }
            }

            if (self::$catVar['p.cat']) {
                if (self::$catVar['cat'] || self::$catVar['o.cat']) {
                    $tempArraySqlCat = [];
                    foreach (self::$catVar['p.cat'] as $v) {
                        $tempArraySqlCat[] = "FIND_IN_SET('" . $v . "', ci) > 0";
                    }
                    $sqlCat[] = '(' . implode(' OR ', $tempArraySqlCat) . ')';
                } else {
                    $sqlCat[] = "cat_id IN (" . implode(",", self::$catVar['p.cat']) . ")";
                }
            }

            if ($categoryExclude) {
                if (is_array(self::$catVar) && count(self::$catVar) == 1 && (self::$catVar['o.cat'] || self::$catVar['p.cat'])) {
                    $sqlCat[] = "cat_id NOT IN (" . implode(",", $categoryExclude) . ")";
                } else {
                    $tempArraySqlCat = [];
                    foreach ($categoryExclude as $v) {
                        $tempArraySqlCat[] = "NOT FIND_IN_SET('" . $v . "', ci) > 0";
                    }
                    $sqlCat[] = '(' . implode(' AND ', $tempArraySqlCat) . ')';
                }
                
                unset($categoryExclude);
            }

            if ($sqlCat) {
                if (is_array(self::$catVar) && count(self::$catVar) == 1 && (self::$catVar['o.cat'] || self::$catVar['p.cat'])) {
                    self::$innerTable .= " INNER JOIN (SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE " . implode(' AND ', $sqlCat) . ") z ON (d.id=z.news_id)";
                } else {
                    self::$innerTable .= " INNER JOIN (SELECT " . PREFIX . "_post_extras_cats.news_id, GROUP_CONCAT(" . PREFIX . "_post_extras_cats.cat_id) as ci FROM " . PREFIX . "_post_extras_cats GROUP BY news_id HAVING " . implode(' AND ', $sqlCat) . ") z ON (d.id=z.news_id)";
                }
            }
        } else {
            if ($categoryExclude) {
                $where[] = "category NOT REGEXP '([[:punct:]]|^)(" . implode('|', $categoryExclude) . ")([[:punct:]]|$)'";
                unset($categoryExclude);
            }

            if (self::$catVar['cat']) {
                foreach (self::$catVar['cat'] as $v) {
                    $where[] = "category REGEXP '([[:punct:]]|^)(" . $v . ")([[:punct:]]|$)'";
                }
            }

            if (self::$catVar['o.cat']) {
                $where[] = "category REGEXP '([[:punct:]]|^)(" . implode('|', self::$catVar['o.cat']) . ")([[:punct:]]|$)'";
            }

            if (self::$catVar['p.cat']) {
                $where[] = "category REGEXP '([[:punct:]]|^)(" . implode('|', self::$catVar['p.cat']) . ")([[:punct:]]|$)'";
            }
        }

        if ($where) {
            self::$sqlWhere[] = '(' . implode(' AND ', $where) . ')';
        }

    }

	/**
     * Сортировка новостей
     *
	 * @return    Filter
     */
	static function order()
	{
		self::$orderBy = 'date desc';
		$sort = [];

        $listOrder = [
            'date',
            'rating',
            'news_read',
            'comm_num',
            'title'
        ];

        if (!self::$dleConfig['allow_comments']) {
            unset($listOrder[3]);
        }

        if ($_SESSION['dle_sort_dle_filter'] && in_array($_SESSION['dle_sort_dle_filter'], $listOrder)) {
            $sort[0] = self::$filterData['sort'] = $_SESSION['dle_sort_dle_filter'];
            $sort[1] = self::$filterData['order'] = $_SESSION['dle_direction_dle_filter'];
        } elseif (self::$filterData['sort']) {
			if (substr_count(self::$filterData['sort'], ';')) {
				self::$sortByOne = true;
			}
			$sort = explode(';', self::$filterData['sort']);
			$sort[1] = $sort[1] ?: self::$filterData['order'];
		} else {
			$sort[0] = self::$modConfig['sort_field'];
			$sort[1] = self::$modConfig['order'];
		}
		
		if ($sort[0]) {
			$sort[0] = self::$dleDb->safesql(trim($sort[0]));
			$sort[1] = $sort[1] == 'asc' ? 'asc' : 'desc';
			
			if (self::$sortByOne) {
				self::$filterData['sort'] = implode(';', $sort);
			} else {
				self::$filterData['sort'] = $sort[0];
				self::$filterData['order'] = $sort[1];
			}
			
			self::$seoView->result['seo'] = str_replace('{sort}', $sort[0], self::$seoView->result['seo']);
			self::$seoView->result['seo'] = str_replace('{order}', $sort[1], self::$seoView->result['seo']);
			
			self::$globalTag['tag']['{dle-filter sort}'] = $sort[0];
			self::$globalTag['block'][] = '#\[dle-filter sort\](.*?)\[\/dle-filter\]#is';
			self::$globalTag['hide'][] = '#\[not-dle-filter sort\](.*?)\[\/not-dle-filter\]#is';
			
			self::$globalTag['tag']['{dle-filter order}'] = $sort[1];
			self::$globalTag['block'][] = '#\[dle-filter order\](.*?)\[\/dle-filter\]#is';
			self::$globalTag['hide'][] = '#\[not-dle-filter order\](.*?)\[\/not-dle-filter\]#is';

			if (isset(self::$orderByKeys[$sort[0]])) {
				if ($sort[0] == 'rating' && !self::$dleConfig['rating_type']) {
					self::$orderBy = 'CEIL(e.rating / e.vote_num)';
				} else {
					self::$orderBy = self::$orderByKeys[$sort[0]];
				}
			} else {
                $absOrder = false;
				if ($sort[0][0] == 'd' && $sort[0][1] == '.') {
					$sort[0] = str_replace('d.', '', $sort[0]);
                    $absOrder = true;
				}

                if (self::$modConfig['new_search'] == 1) {
                    self::$orderBy = "f.`xf_{$sort[0]}`";
                } else {
                    self::$orderBy = $absOrder ? "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(p.xfields, '{$sort[0]}|', -1), '||', 1))" : "SUBSTRING_INDEX(SUBSTRING_INDEX(p.xfields, '{$sort[0]}|', -1), '||', 1)";
                }
			}
			
			self::$orderBy .= ' ' . $sort[1];
		} else {
			self::$seoView->result['seo'] = str_replace('{sort}', 'date', self::$seoView->result['seo']);
			self::$seoView->result['seo'] = str_replace('{order}', 'desc', self::$seoView->result['seo']);

			self::$globalTag['tag']['{dle-filter sort}'] = 'date';
			self::$globalTag['block'][] = '#\[dle-filter sort\](.*?)\[\/dle-filter\]#is';
			self::$globalTag['hide'][] = '#\[not-dle-filter sort\](.*?)\[\/not-dle-filter\]#is';
			
			self::$globalTag['tag']['{dle-filter order}'] = 'desc';
			self::$globalTag['block'][] = '#\[dle-filter order\](.*?)\[\/dle-filter\]#is';
			self::$globalTag['hide'][] = '#\[not-dle-filter order\](.*?)\[\/not-dle-filter\]#is';
			
			self::$filterData['sort'] = 'date';
			self::$filterData['order'] = 'desc';
		}

        if (self::$tempCopyFilterData) {
            self::$tempCopyFilterData['sort'] = self::$filterData['sort'];
            self::$tempCopyFilterData['order'] = self::$filterData['order'];
        }

		self::$seoView->result['seo'] = preg_replace("'\\[sort\\](.*?)\\[/sort\\]'si", '\\1', self::$seoView->result['seo']);
		self::$seoView->result['seo'] = preg_replace("'\\[order\\](.*?)\\[/order\\]'si", '\\1', self::$seoView->result['seo']);

		if (self::$tempCopyFilterData) {
            self::$filterData = self::$tempCopyFilterData;
        }


        return self::$instance;
	}
	
	/**
     * Ссылка фильтра
     *
	 * @return    Filter
     */
	static function setUrl()
	{
		if (self::$vars['page']) {
            self::$urlFilter = self::$dleConfig['http_home_url'] . self::$vars['crown']['page_url'];
        } else {
			ksort(self::$filterData);
			$a = ['order' => self::$filterData['order'], 'sort' => self::$filterData['sort']];
			unset(self::$filterData['order'], self::$filterData['sort']);
			$tempArray = [];
			foreach (self::$filterData as $key => $value) {
				$tempArray[] = $key . '=' . $value;
			}

			if ($a['sort']) {
				$tempArray[] = 'sort=' . $a['sort'];
			}

			if ($a['order']) {
				$tempArray[] = 'order=' . $a['order'];
			}
			
			self::$cleanUrl = '/' . self::$pageDLE . self::$modConfig['filter_url'] . '/' . str_replace([' ', '%20'], '+', implode('/', $tempArray));
			self::$urlFilter = self::$dleConfig['http_home_url'] . self::$pageDLE . self::$modConfig['filter_url'] . '/' . str_replace([' ', '%20'], '+', implode('/', $tempArray));
			if (self::$seoData) {
				self::$filterData = self::$filterData + self::$seoData;
			}

			self::$filterData = self::$filterData + $a;
		}
		
		return self::$instance;
	}
	
	/**
     * Запись статистики
     *
	 * @param    array    $param
     */
	static function setStatistics($param = [])
	{
		global $_IP, $member_id, $microTimerFilter;

		$dateFilter = date('Y-m-d H:i:s', time());
		$memoryUsage = function_exists('memory_get_peak_usage') ? round(memory_get_peak_usage() / (1024*1024), 2) : 0;
		$nick = $member_id['name'] ?: '__GUEST__';
		$param['statistics'] = self::$dleDb->safesql($param['statistics']);

		$check = self::$dleDb->super_query("SELECT idFilter FROM " . PREFIX . "_dle_filter_statistics WHERE DATE(dateFilter)=DATE(NOW()) AND ip='{$_IP}' AND statistics='{$param['statistics']}'");
		if (!$check['idFilter']) {
			$allTime = $microTimerFilter->get();
			self::$dleDb->query("INSERT INTO " . PREFIX . "_dle_filter_statistics (dateFilter, foundNews, ip, queryNumber, nick, memoryUsage, mysqlTime, templateTime, statistics, sqlQuery, allTime) VALUES ('{$dateFilter}', '{$param['foundNews']}', '{$_IP}', '{$param['queryNumber']}', '{$nick}', '{$memoryUsage}', '{$param['mysqlTime']}', '{$param['templateTime']}', '{$param['statistics']}', '{$param['sqlQuery']}','{$allTime}')");
		}
	}
	
	/**
     * Правильный поиск данных в дополнительных полях
     *
	 * @param	string    $xf
	 * @param	string	  $value
	 * @return	string
     */
	static function typeXfield($xf, $value)
	{
		$temp = array_filter(self::$dleXfields, function($item) use($xf) {
			return $item[0] == $xf;
		});
		
		$temp = array_values($temp);
		
		if (!in_array($temp[0][3], ['select', 'image', 'file', 'htmljs']) && $temp[0][8] == 0 && $temp[0][6] == 0) {
			$value = str_replace('\\', '\\\\\\\\\\', self::$dleDb->safesql($value));
		} else {
			if ($temp[0][3] == 'htmljs') {
				$value = self::$dleDb->safesql($value);
			} else {
				$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
				$value = trim(htmlspecialchars(strip_tags(stripslashes($value)), ENT_QUOTES, 'UTF-8'));
				$value = self::$dleDb->safesql($value);
			}
		}
		
		return $value;
	}
	
	/**
     * Тип числа
     *
	 * @param	mixed	$number
	 * @return	int|float
     */
	static function typeNumber($number)
	{
        $number = preg_replace('/\s+/', '', str_replace(',', '.', $number));
        $number = is_float($number) ? floatval($number) : intval($number);
		return $number;
	}

    /**
     * Редирект на последнюю страницу
     *
     * @param	int	$count
     */
	static function redirect($count)
    {
        $endPages = ceil($count / self::$modConfig['news_number']);
        if (self::$pageFilter > $endPages) {
            $filterID = str_replace([' ', '%20'], '+', rawurldecode(self::$urlFilter . '/'));
            if ($endPages > 1) {
                $filterID .= 'page/' . $endPages;
            }
            @header("HTTP/1.0 301 Moved Permanently");
            @header("Location: {$filterID}");
            die("Redirect");
        }
    }

    /**
     * Навигация DLE 14.>0
     *
     * @return string
     */
    static function navigation()
    {
        global $tpl;

        if (!self::$vars['ajax']) {
            return $tpl->result['content'];
        }

        $tpl->result['content'] = str_replace(
            '{newsnavigation}',
            Data::get('nav_apart','config') == 1 ? '' : $tpl->result['navigation'],
            $tpl->result['content']
        );

        return $tpl->result['content'];
    }

    /**
     * Подсчет новостей
     *
     * @return string
     */
    static function sqlCount()
    {
        $filterSqlWhere = self::$sqlWhere ? ' AND ' . implode(' AND ', self::$sqlWhere) : '';

        $innerTableOut = $innerTable = '';
        if (self::$modConfig['new_search']) {
            $innerTable = " LEFT JOIN " . PREFIX . "_dle_filter_news f ON (d.id=f.newsId)";
            $innerTableOut = " LEFT JOIN " . PREFIX . "_dle_filter_news f ON (p.id=f.newsId)";
        }

        $limitIn = '';
        if (self::$modConfig['max_news'] > 0) {
            $limitIn = ' LIMIT ' . self::$modConfig['max_news'];
        }

        if (self::$vars['crown']['max_news'] > 0) {
            $limitIn = ' LIMIT ' . self::$vars['crown']['max_news'];
        }

        return "SELECT COUNT(*) as count FROM " . PREFIX . "_post p " . $innerTableOut . " JOIN (SELECT d.id FROM " . PREFIX . "_post d LEFT JOIN " . PREFIX . "_post_extras e ON (d.id=e.news_id) " . self::$innerTable . $innerTable . " WHERE approve=1 " . self::$whereTable . $filterSqlWhere . $limitIn . ") as l ON p.id=l.id";
    }

    /**
     * Выборка новостей
     *
     * @return string
     */
    static function sqlSelect()
    {
        global $cstart, $config;
		
        $filterSqlWhere = self::$sqlWhere ? ' AND ' . implode(' AND ', self::$sqlWhere) : '';
        if (self::$modConfig['fixed']) {
            self::$orderBy = 'fixed DESC,' . self::$orderBy;
        }

        $innerTableOut = $innerTable = '';
        if (self::$modConfig['new_search']) {
            $innerTable = " LEFT JOIN " . PREFIX . "_dle_filter_news f ON (d.id=f.newsId)";
            $innerTableOut = " LEFT JOIN " . PREFIX . "_dle_filter_news f ON (p.id=f.newsId)";
        }

        $limitIn = " LIMIT {$cstart},{$config['news_number']}";
        $limitOut = '';
        if (self::$modConfig['max_news'] > 0) {
            $limitIn = ' LIMIT ' . self::$modConfig['max_news'];
            $limitOut = " LIMIT {$cstart},{$config['news_number']}";
        }

        if (self::$vars['crown']['max_news'] > 0) {
            $limitIn = ' LIMIT ' . self::$vars['crown']['max_news'];
            $limitOut = " LIMIT {$cstart},{$config['news_number']}";
        }

        $userSelect = $userJoin = '';
        if ($config['user_in_news']) {
            $userSelect = ", u.email, u.name, u.user_id, u.news_num, u.comm_num as user_comm_num, u.user_group, u.lastdate, u.reg_date, u.banned, u.allow_mail, u.info, u.signature, u.foto, u.fullname, u.land, u.favorites, u.pm_all, u.pm_unread, u.time_limit, u.xfields as user_xfields ";
            $userJoin = "LEFT JOIN " . USERPREFIX . "_users u ON (e.user_id=u.user_id) ";
        }

		$temp = str_replace('p.', 'd.', self::$orderBy);
        return "SELECT p.id, p.autor, p.date, p.short_story, CHAR_LENGTH(p.full_story) as full_story, p.xfields, p.title, p.category, p.alt_name, p.comm_num, p.allow_comm, p.fixed, p.tags, e.news_read, e.allow_rate, e.rating, e.vote_num, e.votes, e.view_edit, e.editdate, e.editor, e.reason{$userSelect} FROM " . PREFIX . "_post p LEFT JOIN " . PREFIX . "_post_extras e ON (p.id=e.news_id) " . $userJoin . $innerTableOut . " JOIN (SELECT d.id FROM " . PREFIX . "_post d LEFT JOIN " . PREFIX . "_post_extras e ON (d.id=e.news_id) " . self::$innerTable . $innerTable . " WHERE approve=1 " . self::$whereTable . $filterSqlWhere . " ORDER BY " . $temp . $limitIn . ") as l ON p.id=l.id ORDER BY " . self::$orderBy . $limitOut;
    }

    /**
     * Meta Robots
     *
     * @return string
     */
    static function metaRobots()
    {
		global $config;
		
        $key = self::$pageFilter > 0 ? 'index_second' : 'index_filter';

		$indexTemp = [
			'index' => 'index,follow',
			'follow' => 'noindex,follow',
			'noindex' => 'noindex,nofollow'
		];
		$type = $indexTemp[self::$modConfig[$key]];
		if ($config['version_id'] >= 15.1) {
			return $type;
		}

		return "\">\n<meta name=\"robots\" content=\"{$type}";
    }

    /**
     * Тег количества найденых новостей
     *
     * @param  int $count
     */
    static function countTag($count)
    {
        if ($count) {
            self::$globalTag['tag']['{dle-filter count-news}'] = $count;
            self::$globalTag['block'][] = '#\[dle-filter count-news\](.*?)\[\/dle-filter\]#is';
            self::$globalTag['hide'][] = '#\[not-dle-filter count-news\](.*?)\[\/not-dle-filter\]#is';

            self::$seoView->result['seo'] = str_replace('{count-news}', $count, self::$seoView->result['seo']);
            self::$seoView->result['seo'] = preg_replace("'\\[count-news\\](.*?)\\[/count-news\\]'si", '\\1', self::$seoView->result['seo']);
            self::$seoView->result['seo'] = preg_replace("'\\[not-count-news\\](.*?)\\[/not-count-news\\]'si", '', self::$seoView->result['seo']);
        } else {
            self::$seoView->result['seo'] = str_replace('{count-news}', '', self::$seoView->result['seo']);
            self::$seoView->result['seo'] = preg_replace("'\\[count-news\\](.*?)\\[/count-news\\]'si", '', self::$seoView->result['seo']);
            self::$seoView->result['seo'] = preg_replace("'\\[not-count-news\\](.*?)\\[/not-count-news\\]'si", '\\1', self::$seoView->result['seo']);
        }
    }

    /**
     * Вывод данных с конца в тегах
     *
     * @param array $data
     * @param string $value
     *
     * @return string
     */
    static function pregTag($data, $value)
    {
        $data[1] = explode(';', $data[1]);
        $i = explode(',', $value);
        if (count($i) > $data[1][0]) {
            $a = array_slice($i, ($data[1][1] == 'end' ? -$data[1][0] : 0), $data[1][0]);
            return implode(', ', $a);
        }

        return $value;
    }

    /**
     * Связь шаблонов с параметрами
     *
     * @return string
     */
    static function setLinkTemplate()
    {
        $return = false;

        if (isset(self::$modConfig['link']['p']) && is_array(self::$modConfig['link']['p']) && count(self::$modConfig['link']['p'])) {
            foreach (self::$modConfig['link']['p'] as $key => $value) {
                $value = explode('=', $value);
                $value[1] = $value[1] ? explode(',', $value[1]) : '_all_';
                foreach (self::$filterData as $keys => $val) {
                    if ($keys == $value[0]) {
                        if (is_array($value[1])) {
                            $val = explode(',', $val);
                            if (array_intersect($value[1], $val)) {
                                $return = self::$modConfig['link']['a'][$key];
                                break;
                            }
                        } else {
                            $return = self::$modConfig['link']['a'][$key];
                            break;
                        }
                    }
                }
            }
        }

        self::$tpl = 'lazydev/dle_filter/' . ($return ?: 'news');

        return self::$tpl;
    }

    /**
     * Связь параметров
     *
     */
    static function setRelations()
    {
        $return = [];

        if (isset(self::$modConfig['exchange']['p']) && is_array(self::$modConfig['exchange']['p']) && count(self::$modConfig['exchange']['p'])) {
            self::$tempCopyFilterData = self::$filterData;

            $tempParamKey = $tempParam = [];

            foreach (self::$modConfig['exchange']['p'] as $key => $value) {
                $value = explode('=', $value);
                $value[0] = trim($value[0]);
                $value[1] = $value[1] ? explode(',', $value[1]) : '_all_';
                $tempParamKey[$value[0]][] = $key;
                $tempParam[$value[0]][] = $value[1];
            }

            foreach (self::$filterData as $key => $val) {
                $val = explode(',', $val);
                if ($tempParam[$key]) {
                    foreach ($tempParam[$key] as $index => $item) {
                        if (is_array($item)) {
                            if (array_intersect($item, $val)) {
                                $return[] = self::$modConfig['exchange']['a'][$tempParamKey[$key][$index]];
                            }
                        } else {
                            $return[] = self::$modConfig['exchange']['a'][$tempParamKey[$key][$index]];
                        }
                    }
                }
            }

            if ($return) {
                foreach ($return as $item) {
                    $item = explode('=', $item);
                    $item[0] = trim($item[0]);
                    if ($item[0] == 'sort' || $item[0] == 'order') {
                        self::$filterData[$item[0]] = $item[1];
                    } else {
                        if (self::$filterData[$item[0]]) {
                            self::$filterData[$item[0]] .= ',' . $item[1];
                        } else {
                            self::$filterData[$item[0]] = $item[1];
                        }
                    }
                }
            }
        }
    }

	private function __construct() {}
    private function __wakeup() {}
    private function __clone() {}
    private function __sleep() {}
}
