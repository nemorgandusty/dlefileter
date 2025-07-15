<?php
/**
* Статистика фильтра
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

setlocale(LC_NUMERIC, 'C');

use LazyDev\Filter\Helper;

$allXfield = xfieldsload();
foreach ($allXfield as $value) {
    $xfieldArray[$value[0]] = $value[1];
}

$sortField = [
	'date' => $langDleFilter['admin']['settings']['p.date'],
	'editdate' => $langDleFilter['admin']['settings']['e.editdate'],
	'title' => $langDleFilter['admin']['settings']['p.title'],
	'autor' => $langDleFilter['admin']['settings']['p.autor'],
	'rating' => $langDleFilter['admin']['settings']['e.rating'],
	'comm_num' => $langDleFilter['admin']['settings']['p.comm_num'],
	'news_read' => $langDleFilter['admin']['settings']['e.news_read']
];

if ($xfieldArray) {
	$sortField = $sortField + $xfieldArray;
}

$order = [
	'desc' => $langDleFilter['admin']['settings']['desc'],
	'asc' => $langDleFilter['admin']['settings']['asc']
];

if ($configDleFilter['statistics']) {
	$allFilterData = $db->super_query("SELECT COUNT(*) as count FROM ". PREFIX . "_dle_filter_statistics")['count'];
	$dataPerPage = 25;
	if (isset($_REQUEST['cstart']) && $_REQUEST['cstart']) {
		$cstart = intval($_REQUEST['cstart']);
	} else {
		if (!isset($cstart) || $cstart < 1) {
			$cstart = 0;
		} else {
			$cstart = ($cstart - 1) * $dataPerPage;
		}
	}
	$i = $cstart;
	
	$sql = $db->query("SELECT * FROM " . PREFIX . "_dle_filter_statistics ORDER BY dateFilter DESC LIMIT {$cstart},{$dataPerPage}");
	if ($db->num_rows()) {
        $getPopularToday = $db->super_query("SELECT s.foundNews, s.statistics, g.count FROM " . PREFIX . "_dle_filter_statistics s INNER JOIN (
	SELECT COUNT(*) as count, idFilter, statistics FROM " . PREFIX . "_dle_filter_statistics WHERE DATE(dateFilter) = CURDATE() GROUP BY statistics
) g ON s.idFilter=g.idFilter ORDER BY g.count DESC LIMIT 5", true);
        $allPercentToday = 0;
        foreach ($getPopularToday as $rowToday) {
            $allPercentToday += $rowToday['count'];
        }

        $i = 1;
        $listContentToday = '';
        foreach ($getPopularToday as $rowToday) {
            $percent = ($rowToday['count'] / $allPercentToday) * 100;
            $percent = number_format($percent, 2, '.', '');
            $rowToday['statistics'] = stripslashes($rowToday['statistics']);
            $langDleFilter['admin']['statistics']['query'] = $rowToday['count'] . ' ' . Helper::declinationLazy([$rowToday['count'], 'запрос|а|ов']);
            $url = parse_url($rowToday['statistics']);
            if ($url['scheme'] && $url['host']) {
                $rowToday['statistics'] = str_ireplace($url['scheme'] . '://' . $url['host'] . '/', $config['http_home_url'], $rowToday['statistics']);
            }

$listContentToday .= <<<HTML
<div class="list-content-li">
    <div class="num-list">{$i}</div>
    <div class="list-content-details">
        <div class="list-content-title">
            <h6>{$rowToday['statistics']}</h6>
            <p class="list-content-count">{$percent}% ({$langDleFilter['admin']['statistics']['query']})</p>
        </div>
        <div class="list-stats">
            <div class="progress-dle-filter">
                <div class="progress-bar bg-gradient{$i}" role="progressbar" style="width: {$percent}%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
</div>
HTML;
$i++;
        }

        $getPopularAllTime = $db->super_query("SELECT s.foundNews, s.statistics, g.count FROM " . PREFIX . "_dle_filter_statistics s INNER JOIN (
	SELECT COUNT(*) as count, idFilter, statistics FROM " . PREFIX . "_dle_filter_statistics GROUP BY statistics
) g ON s.idFilter=g.idFilter ORDER BY g.count DESC LIMIT 5", true);
        $i = 1;
        $allPercentAll = 0;
        foreach ($getPopularAllTime as $rowAll) {
            $allPercentAll += $rowAll['count'];
        }
        $listContentAll = '';
        foreach ($getPopularAllTime as $rowAll) {
            $percent = ($rowAll['count'] / $allPercentAll) * 100;
            $percent = number_format($percent, 0, '.', '');
            $rowAll['statistics'] = stripslashes($rowAll['statistics']);

            $url = parse_url($rowAll['statistics']);
            if ($url['scheme'] && $url['host']) {
                $rowAll['statistics'] = str_ireplace($url['scheme'] . '://' . $url['host'] . '/', $config['http_home_url'], $rowAll['statistics']);
            }
            $langDleFilter['admin']['statistics']['query'] = $rowAll['count'] . ' ' . Helper::declinationLazy([$rowAll['count'], 'запрос|а|ов']);
            $listContentAll .= <<<HTML
<div class="list-content-li">
    <div class="num-list">{$i}</div>
    <div class="list-content-details">
        <div class="list-content-title">
            <h6>{$rowAll['statistics']}</h6>
            <p class="list-content-count">{$percent}% ({$langDleFilter['admin']['statistics']['query']})</p>
        </div>
        <div class="list-stats">
            <div class="progress-dle-filter">
                <div class="progress-bar bg-gradient{$i}" role="progressbar" style="width: {$percent}%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
</div>
HTML;
            $i++;
        }

        $i = 0;

if ($listContentToday) {
    $listContentToday = <<<HTML
<div style="float: left;padding: 0!important;display: inline-block;width: 49%;margin-right: 20px;">
    <div class="widget-four">
        <div class="widget-heading">
            <h5>{$langDleFilter['admin']['statistics']['top_today']}</h5>
        </div>
        <div class="widget-content">
            <div class="list-content">
                {$listContentToday}
            </div>
        </div>
    </div>
</div>
HTML;
}

if ($listContentAll) {
    $listContentAll = <<<HTML
<div style="float: unset!important;padding: 0!important;display: inline-block;width: 49%;">
    <div class="widget-four">
        <div class="widget-heading">
            <h5>{$langDleFilter['admin']['statistics']['top_all_time']}</h5>
        </div>
        <div class="widget-content">
            <div class="list-content">
                {$listContentAll}
            </div>
        </div>
    </div>
</div>
HTML;
}


echo <<<HTML
<div class="panel panel-flat">
    <div class="navbar navbar-default navbar-component navbar-xs" style="margin-bottom: 0px;">
        <ul class="nav navbar-nav visible-xs-block">
            <li class="full-width text-center"><a data-toggle="collapse" data-target="#navbar-filter">
                <i class="fa fa-bars"></i></a>
            </li>
        </ul>
        <div class="navbar-collapse collapse" id="navbar-filter">
            <ul class="nav navbar-nav">
                <li class="active">
                    <a onclick="ChangeOption(this, 'block_1');" class="tip">
                    <i class="fa fa-search"></i> {$langDleFilter['admin']['statistics']['main']}</a>
                </li>
                <li>
                    <a onclick="ChangeOption(this, 'block_2');" class="tip">
                    <i class="fa fa-line-chart"></i> {$langDleFilter['admin']['statistics']['top']}</a>
                </li>
            </ul>
        </div>
    </div>
    <div id="block_2" style="display: none">
        <div class="panel-body" style="font-size:20px; font-weight:bold;border-bottom: 1px solid #ddd;">
            {$langDleFilter['admin']['statistics']['top']}
        </div>
        {$listContentToday}
        {$listContentAll}
    </div>
    <div id="block_1">
        <div class="panel-body" style="font-size:20px; font-weight:bold;border-bottom: 1px solid #ddd;">
            {$langDleFilter['admin']['statistics_descr']}
            <span class="badge badge-primary" style="margin-top: -10px;position: absolute;font-size: 12px;padding-bottom: 2px;">{$langDleFilter['admin']['statistics']['all']} {$allFilterData}</span>
            <input type="button" onclick="clearStatistics();" class="btn bg-warning btn-sm" style="float: right;border-radius: unset;font-size: 13px;" value="{$langDleFilter['admin']['statistics']['clear']}">
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{$langDleFilter['admin']['statistics']['date']}</th>
                        <th>{$langDleFilter['admin']['statistics']['nick']}</th>
                        <th>{$langDleFilter['admin']['statistics']['ip']}</th>
                        <th>{$langDleFilter['admin']['statistics']['news']}</th>
                        <th>{$langDleFilter['admin']['statistics']['options']}</th>
                        <th>{$langDleFilter['admin']['statistics']['stat']}</th>
                    </tr>
                </thead>
                <tbody>

HTML;
$numRow = 0;
$statList = $paramList = [];
while ($row = $db->get_row($sql)) {
	$i++;
	if ($row['mysqlTime'] == -1) {
		$row['mysqlTime'] = $langDleFilter['admin']['statistics']['cache'];
	} else {
		$row['mysqlTime'] = round($row['mysqlTime'], 5) . ' ' . $langDleFilter['admin']['statistics']['sec'];
	}
	
	if ($row['templateTime'] == -1) {
		$row['templateTime'] = $langDleFilter['admin']['statistics']['cache'];
	} else {
		$row['templateTime'] = round($row['templateTime'], 5) . ' ' . $langDleFilter['admin']['statistics']['sec'];
	}
	$statList[$row['idFilter']] = <<<HTML
	<tr>
		<td>{$langDleFilter['admin']['statistics']['allTime']}</td>
		<td>{$row['allTime']} {$langDleFilter['admin']['statistics']['sec']}</td>
	</tr>
	<tr>
		<td>{$langDleFilter['admin']['statistics']['memory']}</td>
		<td>{$row['memoryUsage']} {$langDleFilter['admin']['statistics']['mb']}</td>
	</tr>
	<tr>
		<td>{$langDleFilter['admin']['statistics']['mysqlTime']}</td>
		<td>{$row['mysqlTime']}</td>
	</tr>
	<tr>
		<td>{$langDleFilter['admin']['statistics']['templateTime']}</td>
		<td>{$row['templateTime']}</td>
	</tr>
	<tr>
		<td>{$langDleFilter['admin']['statistics']['sqlQuery']}</td>
		<td><pre><code style="white-space: pre-wrap;">{$row['sqlQuery']}</code></pre></td>
	</tr>
HTML;
	
	
	$statisticsArray = explode('/' . $configDleFilter['filter_url'] . '/', $row['statistics']);
	
	$statisticsArray[0] = str_replace($config['http_home_url'], '', $statisticsArray[0]);
	if (substr_count($statisticsArray[0], 'xfsearch/')) {
		$xf = explode('/', $statisticsArray[0]);
		if (count($xf) == 2) {
			$xfName = totranslit(trim($xf[0]));
			$xfName = $xfieldArray[$xfName] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $xfName;
			$xfValue = htmlspecialchars(strip_tags(stripslashes(trim($xf[1]))), ENT_QUOTES, $config['charset']);
			$paramList[$row['idFilter']] .= "<tr><td>{$langDleFilter['admin']['statistics']['xf_page']} {$xfName}</td><td>{$xfValue}</td></tr>";
		}
	} elseif (substr_count($statisticsArray[0], 'tags/')) {
		$tagTemp = explode('/', $statisticsArray[0]);
		if (count($tagTemp) == 2) {
			$tagValue = htmlspecialchars(strip_tags(stripslashes(trim($tagTemp[1]))), ENT_QUOTES, $config['charset']);
			$paramList[$row['idFilter']] .= "<tr><td>{$langDleFilter['admin']['statistics']['tag_page']}</td><td>{$tagValue}</td></tr>";
		}
	} elseif ($statisticsArray[0] != '') {
		$cat = explode('/', $statisticsArray[0]);
		$cat = trim(end($cat));
		if ($cat != '') {
			$category_id = Helper::getCategoryId($cat_info, $cat);
			if ($category_id > 0) {
				$catValue = $cat_info[$category_id]['name'];
				$paramList[$row['idFilter']] .= "<tr><td>{$langDleFilter['admin']['statistics']['cat_page']}</td><td>{$catValue}</td></tr>";
			}
		}
	}
	
	$statisticsArray[1] = Helper::cleanSlash($statisticsArray[1]);
	$paramFilterArray = explode('/', $statisticsArray[1]);
	
	foreach ($paramFilterArray as $item) {
		$nameField = '';
		$tmp_Value = explode('=', $item);
		if (!isset($tmp_Value[1])) {
			continue;
		}
		
		if ($tmp_Value[0][0] == 'n' && $tmp_Value[0][1] == '.') {
			$tmp_Value[0] = str_replace('n.', '', $tmp_Value[0]);
		}

		$firstKey = $tmp_Value[0][0];
		$secondKey = $tmp_Value[0][1];

		if (($firstKey == 'r' || $firstKey == 'c') && $secondKey == '.') {
			$tmp_Value[0] = str_replace(['r.', 'c.'], '', $tmp_Value[0]);
			$tempArray = explode(($firstKey == 'r' ? ';' : ','), $tmp_Value[1]);
			
			if ($tmp_Value[0] == 'prate') {
				$nameField = $langDleFilter['admin']['statistics']['fields']['rating'];
			} elseif ($tmp_Value[0] == 'date') {
                $nameField = $langDleFilter['admin']['statistics']['fields']['rdate'];
            } elseif ($tmp_Value[0] == 'edit') {
                $nameField = $langDleFilter['admin']['statistics']['fields']['redit'];
            } else {
				$nameField = $xfieldArray[$tmp_Value[0]] ?: false;
			}
			
			if (!$nameField) {
				$nameField = $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			}

            if ($tmp_Value[0] == 'date' || $tmp_Value[0] == 'edit') {
                $tempArray[0] = date('Y.m.d', Helper::jsDate($tempArray[0]));

                if ($tempArray[1]) {
                    $tempArray[1] = date('Y.m.d', Helper::jsDate($tempArray[1]));
                }
            } else {
                $tempArray[0] = preg_replace('/\s+/', '', str_replace(',', '.', $tempArray[0]));
                $tempArray[0] = is_float($tempArray[0]) ? floatval($tempArray[0]) : intval($tempArray[0]);

                if ($tempArray[1]) {
                    $tempArray[1] = preg_replace('/\s+/', '', str_replace(',', '.', $tempArray[1]));
                    $tempArray[1] = is_float($tempArray[1]) ? floatval($tempArray[1]) : intval($tempArray[1]);
                }
            }
			if (isset($tempArray[1]) && $tempArray[1] > 0) {
				$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$langDleFilter['admin']['statistics']['from']}: {$tempArray[0]}<br>{$langDleFilter['admin']['statistics']['to']}: {$tempArray[1]}</td></tr>";
			} else {
				$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$langDleFilter['admin']['statistics']['from']}: {$tempArray[0]}</td></tr>";
			}
		} elseif (($firstKey == 'l' || $firstKey == 'm' || $firstKey == 's') && $secondKey == '.') {
			$tmp_Value[0] = str_replace(['l.', 'm.', 's.'], '', $tmp_Value[0]);
			$nameField = $langDleFilter['admin']['statistics']['fields'][$tmp_Value[0]] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			if ($nameField) {
				$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
			}
		} elseif ($firstKey == 'j' && $secondKey == '.') {
			$tmp_Value[0] = str_replace('j.', '', $tmp_Value[0]);
			$matchesTemp = explode(';', $tmp_Value[0]);
			$tAr = [];
			foreach ($matchesTemp as $nameKey) {
				if (substr_count($nameKey, 'p.')) {
					$nameKey = str_replace('p.', '', $nameKey);
					$tAr[] = $langDleFilter['admin']['statistics']['fields'][$nameKey] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $nameKey;
				} else {
					$nameKey = str_replace('x.', '', $nameKey);
					$tAr[] = $xfieldArray[$nameKey] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $nameKey;
				}
			}
			
			$nameField = implode(', ', $tAr);
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
		} elseif (in_array($firstKey, ['g', 'v', 'e', 'b']) && $secondKey == '.') {
			$tmp_Value[0] = str_replace(['g.', 'v.', 'e.', 'b.'], '', $tmp_Value[0]);
            $nameField = $xfieldArray[$tmp_Value[0]] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
		} elseif ($firstKey == 'f' && $secondKey == '.') {
            $tmp_Value[0] = str_replace('f.', '', $tmp_Value[0]);
            if (in_array($tmp_Value[0], ['prate', 'pdate', 'pedit'])) {
                $nameField = $langDleFilter['admin']['statistics']['from'] . ': ' . $langDleFilter['admin']['statistics']['f'][$tmp_Value[0]];
            } else {
                $nameField = $xfieldArray[$tmp_Value[0]] ? $langDleFilter['admin']['statistics']['from'] . ': ' . $xfieldArray[$tmp_Value[0]] : $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
            }
            $paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
        } elseif ($firstKey == 't' && $secondKey == '.') {
            $tmp_Value[0] = str_replace('t.', '', $tmp_Value[0]);
            if (in_array($tmp_Value[0], ['prate', 'pdate', 'pedit'])) {
                $nameField = $langDleFilter['admin']['statistics']['to'] . ': ' . $langDleFilter['admin']['statistics']['f'][$tmp_Value[0]];
            } else {
                $nameField = $xfieldArray[$tmp_Value[0]] ? $langDleFilter['admin']['statistics']['to'] . ': ' . $xfieldArray[$tmp_Value[0]] : $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
            }
            $paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
        } elseif ($tmp_Value[0] == 'cat' || $tmp_Value[0] == 'o.cat') {
			$paramCat = explode(',', $tmp_Value[1]);
			$nameField = $langDleFilter['admin']['statistics']['fields']['category'];
			$tAr = [];
			foreach ($paramCat as $value) {
				if (($value = intval($value)) > 0 && $cat_info[$value]) {
					$tAr[] = $cat_info[$value]['name'];
				}
			}
			$catName = implode(', ', $tAr);
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$catName}</td></tr>";
		} elseif ($tmp_Value[0] == 'p.cat') {
			$paramCat = explode(',', $tmp_Value[1]);
			$nameField = $langDleFilter['admin']['statistics']['fields']['category'];
			$tAr = [];
			foreach ($paramCat as $value) {
				if (($value = intval($value)) > 0 && $cat_info[$value]) {
                    $tAr[] = Helper::getAllCats($value);
				}
			}

            $tAr = implode('|', $tAr);
            $tAr = explode('|', $tAr);
            $tAZ = [];
            foreach ($tAr as $value) {
                $tAZ[] = $cat_info[$value]['name'];
            }

			$catName = implode(', ', $tAZ);
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$catName}</td></tr>";
		} elseif ($tmp_Value[0] == 'sort') {
			if (substr_count($tmp_Value[1], ';')) {
				$sortByOne = true;
			}
			$sort = explode(';', $tmp_Value[1]);
			$sort[0] = str_replace('d.', '', $sort[0]);
			
			$nameField = $sortField[$sort[0]] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $sort[0];
			if (isset($sort[1])) {
				$nameField .= ' ' . ($order[$sort[1]] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $sort[1]);
			}
			$paramList[$row['idFilter']] .= "<tr><td>{$langDleFilter['admin']['statistics']['sort']}</td><td>{$nameField}</td></tr>";
		} elseif ($tmp_Value[0] == 'order') {
			$nameField = ' ' . ($order[$tmp_Value[1]] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[1]);
			$paramList[$row['idFilter']] .= "<tr><td>{$langDleFilter['admin']['statistics']['order']}</td><td>{$nameField}</td></tr>";
		} else {
			$nameField = $xfieldArray[$tmp_Value[0]] ?: $langDleFilter['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
		}
	}
$row['nick'] = $row['nick'] == '__GUEST__' ? $langDleFilter['admin']['statistics']['guest'] : stripslashes($row['nick']);
$foundNews = $row['foundNews'] == 1 ? "<i style=\"color:green!important;\" class=\"fa fa-check\"></i>" : "<i style=\"color:red!important;\" class=\"fa fa-remove\"></i>";
echo <<<HTML
				<tr>
					<td>{$i}</td>
					<td>{$row['dateFilter']}</td>
					<td>{$row['nick']}</td>
					<td>{$row['ip']}</td>
					<td>{$foundNews}</td>
					<td><input type="button" class="btn bg-success btn-sm" style="border-radius: unset;" value="{$langDleFilter['admin']['statistics']['look_param']}" onclick="showDataFilter({$row['idFilter']}, 0)"></td>
					<td><input type="button" class="btn bg-info btn-sm" style="border-radius: unset;" value="{$langDleFilter['admin']['statistics']['look_stat']}" onclick="showDataFilter({$row['idFilter']}, 1)"></td>
				</tr>
HTML;
}

$jsParam = Helper::json($paramList);
$jsStat = Helper::json($statList);
echo <<<HTML

                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;
$navigation = '';
if ($allFilterData > $dataPerPage) {

	if ($cstart > 0) {
		$previous = $cstart - $dataPerPage;
		$navigation .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$previous}\" title=\"{$lang['edit_prev']}\"><i class=\"fa fa-backward\"></i></a></li>";
	}

	$enpages_count = @ceil($allFilterData / $dataPerPage);
	$enpages_start_from = 0;
	$enpages = '';

	if ($enpages_count <= 10) {
		for ($j = 1; $j <= $enpages_count; $j++) {
			if ($enpages_start_from != $cstart) {
				$enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$enpages_start_from}\">{$j}</a></li>";
			} else {
				$enpages .= "<li class=\"active\"><span>{$j}</span></li>";
			}

			$enpages_start_from += $dataPerPage;
		}
		$navigation .= $enpages;
	} else {
		$start = 1;
		$end = 10;

		if ($cstart > 0) {
			if (($cstart / $dataPerPage) > 4) {
				$start = @ceil($cstart / $dataPerPage) - 3;
				$end = $start + 9;

				if ($end > $enpages_count) {
					$start = $enpages_count - 10;
					$end = $enpages_count - 1;
				}

				$enpages_start_from = ($start - 1) * $dataPerPage;
			}
		}

		if ($start > 2) {
			$enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics\">1</a></li> <li><span>...</span></li>";
		}

		for ($j = $start; $j <= $end; $j++) {
			if ($enpages_start_from != $cstart) {
				$enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$enpages_start_from}\">{$j}</a></li>";
			} else {
				$enpages .= "<li class=\"active\"><span>{$j}</span></li>";
			}

			$enpages_start_from += $dataPerPage;
		}

		$enpages_start_from = ($enpages_count - 1) * $dataPerPage;
		$enpages .= "<li><span>...</span></li><li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$enpages_start_from}\">{$enpages_count}</a></li>";

		$navigation .= $enpages;

	}

	if ($allFilterData > $i) {
		$how_next = $allFilterData - $i;
		if ($how_next > $dataPerPage) {
			$how_next = $dataPerPage;
		}
		
		$navigation .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$i}\" title=\"{$lang['edit_next']}\"><i class=\"fa fa-forward\"></i></a></li>";
	}

	echo "<ul id=\"paginationshow\" class=\"pagination pagination-sm mb-20\">".$navigation."</ul>";
}

$jsAdminScript[] = <<<HTML

function ChangeOption(obj, selectedOption) {
    $('#navbar-filter li').removeClass('active');
    $(obj).parent().addClass('active');
    $('[id*=block_]').hide();
    $('#' + selectedOption).show();

    if ($('#paginationshow').length) {
        selectedOption == 'block_1' ? $('#paginationshow').show() : $('#addTemplate').hide();   
    }
    return false;
}

let jsonParam = {$jsParam};
let jsonStat = {$jsStat};
let showDataFilter = function(i, b) {
	$("#dlepopup").remove();
	
	let title = b == 1 ? "{$langDleFilter['admin']['statistics']['watch_stat']}" : "{$langDleFilter['admin']['statistics']['watch_param']}";
	let columnTitle = b == 1 ? "{$langDleFilter['admin']['statistics']['data']}" : "{$langDleFilter['admin']['statistics']['field']}";
	let contentFilter = b == 1 ? jsonStat[i] : jsonParam[i];
	if (contentFilter) {
		$("body").append("<div id='dlepopup' class='dle-alert' title='"+ title + i + "' style='display:none'><div class='panel panel-flat'><div class='table-responsive'><table class='table'><thead><tr><th style='width:250px;'>"+columnTitle+"</th><th>{$langDleFilter['admin']['statistics']['value']}</th></tr></thead><tbody>"+contentFilter+"</tbody></table></div></div></div>");

		$('#dlepopup').dialog({
			autoOpen: true,
			width: 800,
			resizable: false,
			dialogClass: "modalfixed dle-popup-alert",
			buttons: {
				"{$langDleFilter['admin']['statistics']['close']}": function() { 
					$(this).dialog("close");
					$("#dlepopup").remove();							
				} 
			}
		});

		$('.modalfixed.ui-dialog').css({position:"fixed", maxHeight:"600px", overflow:"auto"});
		$('#dlepopup').dialog( "option", "position", { my: "center", at: "center", of: window } );
	}
};

let clearStatistics = function() {
	DLEconfirm("{$langDleFilter['admin']['statistics']['accept_clear']}", "{$langDleFilter['admin']['try']}", function() {
		coreAdmin.ajaxSend(false, 'clearStatistics', false);
	});
	return false;
}
HTML;
	} else {
echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left">
	<h4>{$langDleFilter['admin']['statistics']['attention']}</h4>
	{$langDleFilter['admin']['statistics']['attention_text_2']}
</div>
HTML;
	}
} else {
echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left">
	<h4>{$langDleFilter['admin']['statistics']['attention']}</h4>
	{$langDleFilter['admin']['statistics']['attention_text']}
</div>
HTML;
}

?>