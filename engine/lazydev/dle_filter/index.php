<?php

include_once __DIR__ . "/loader.php";
$microTimerFilter = new microTimer();
$Filter = LazyDev\Filter\Filter::construct()->load($dleFilterVars)->getVar()->getPage()->filterOptions()->order()->setUrl();
$url_page = $Filter::$urlFilter;
$clean_url = $Filter::$cleanUrl . "/";
$checkPageFilter = array_filter(array($pagesDleFilter), function ($a) use($clean_url) {
    return str_replace(array("+", "%20"), " ", rawurldecode($a["filter"])) == str_replace(array("+", "%20"), " ", $clean_url);
});
if ($checkPageFilter) {
    $checkPageFilter = array_values($checkPageFilter);
    if ($checkPageFilter[0]["redirect"] == 1) {
        if ($dleFilterVars["ajax"]) {
            $url_page = "/" . $checkPageFilter[0]["page"] . "/";
            $tpl->result["content"] = "redirect";
            return NULL;
        }
        @header("HTTP/1.0 301 Moved Permanently");
        @header("Location: /" . $checkPageFilter[0]["page"] . "/");
        exit("Redirect");
    }
}
if ($dleFilterVars["ajax"] && $configDleFilter["ajax_form"]) {
    return NULL;
}
$filterID = $Filter::$urlFilter . "/";
if (1 < $Filter::$pageFilter) {
    $filterID .= "page/" . $Filter::$pageFilter . "/";
}
if (!$dleFilterVars["ajax"]) {
    $homeUrl = LazyDev\Filter\Helper::cleanSlash($config["http_home_url"]);
    $_SERVER["REQUEST_URI"] = $homeUrl . rawurldecode($_SERVER["REQUEST_URI"]);
    $filterID = str_replace(array(" ", "%20"), "+", rawurldecode($filterID));
    if ($filterID != $_SERVER["REQUEST_URI"] || substr($_SERVER["REQUEST_URI"], -1, 1) != "/" || substr_count($_SERVER["REQUEST_URI"], "/page/1/")) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Location: " . $filterID);
        exit("Redirect");
    }
}
$Conditions = LazyDev\Filter\Conditions::construct();
$allFilterData = $Filter::$filterData;
if ($Filter::$seoPageArray) {
    $allFilterData += $Filter::$seoPageArray;
}
$category_id = 999999;
$cat_info[$category_id]["short_tpl"] = $Filter::setLinkTemplate();
$canonical = $Filter::$urlFilter . "/";
if (1 < $Filter::$pageFilter) {
    $canonical .= "page/" . $Filter::$pageFilter . "/";
}
if ($configDleFilter["cache_filter"]) {
    $cacheFilter = LazyDev\Filter\Cache::get($filterID . $cat_info[$category_id]["short_tpl"]);
    if ($cacheFilter) {
        $cacheFilter = json_decode($cacheFilter, true);
        if ($configDleFilter["redirect_filter"] && !$dleFilterVars["ajax"]) {
            $Filter::redirect($cacheFilter["countNews"]);
        }
        $tpl->result["content"] = $cacheFilter["content"];
        $tpl->result["navigation"] = $cacheFilter["navigation"];
        if (14 <= (double) $config["version_id"]) {
            $tpl->result["content"] = LazyDev\Filter\Filter::navigation();
        }
        if ($dleFilterVars["page"]) {
            $metatags["title"] = stripslashes($dleFilterVars["crown"]["meta_title"]) ?: $metatags["title"];
            $metatags["description"] = stripslashes($dleFilterVars["crown"]["meta_descr"]) ?: $metatags["description"];
            $metatags["keywords"] = stripslashes($dleFilterVars["crown"]["meta_key"]) ?: $metatags["keywords"];
            $metatags["speedbar"] = stripslashes($dleFilterVars["crown"]["speedbar"]) ?: "";
            $social_tags["site_name"] = $config["home_title"];
            $social_tags["type"] = "website";
            if ($dleFilterVars["crown"]["og_title"]) {
                $social_tags["title"] = str_replace("&amp;", "&", $dleFilterVars["crown"]["og_title"]);
            }
            if ($dleFilterVars["crown"]["og_descr"]) {
                $social_tags["description"] = str_replace("&amp;", "&", $dleFilterVars["crown"]["og_descr"]);
            }
            if ($dleFilterVars["crown"]["og_image"]) {
                $social_tags["image"] = $config["http_home_url"] . "uploads/dle_filter/" . $dleFilterVars["crown"]["og_image"];
            }
            $social_tags["url"] = $Filter::$urlFilter . "/";
            if ($dleFilterVars["crown"]["seo_title"]) {
                $Filter::$globalTag["tag"]["{dle-filter h1}"] = $dleFilterVars["crown"]["seo_title"];
                $Filter::$globalTag["block"][] = "#\\[dle-filter h1\\](.*?)\\[\\/dle-filter\\]#is";
                $Filter::$globalTag["hide"][] = "#\\[not-dle-filter h1\\](.*?)\\[\\/not-dle-filter\\]#is";
            }
            if ($dleFilterVars["crown"]["seo_text"]) {
                $Filter::$globalTag["tag"]["{dle-filter description}"] = stripslashes($dleFilterVars["crown"]["seo_text"]);
                $Filter::$globalTag["block"][] = "#\\[dle-filter description\\](.*?)\\[\\/dle-filter\\]#is";
                $Filter::$globalTag["hide"][] = "#\\[not-dle-filter description\\](.*?)\\[\\/not-dle-filter\\]#is";
            }
        } else {
            $metatags["title"] = $cacheFilter["seo"]["title"] ?: $metatags["title"];
            $metatags["description"] = $cacheFilter["seo"]["description"] ?: $metatags["description"];
            $metatags["keywords"] = $cacheFilter["seo"]["keywords"] ?: $metatags["keywords"];
        }
        $count_all = $cacheFilter["countNews"];
        $Filter::countTag($count_all);
        $tpl->result["speedbar"] = $cacheFilter["seo"]["speedbar"];
        if ($configDleFilter["statistics"] && !$dleFilterVars["page"] && $Filter::$pageFilter < 2) {
            $Filter::setStatistics(array("mysqlTime" => -1, "templateTime" => -1, "foundNews" => $cacheFilter["foundNews"], "queryNumber" => -1, "statistics" => $cacheFilter["id"], "sqlQuery" => $db->safesql($cacheFilter["sqlQuery"])));
        }
        $config["speedbar"] = false;
        return NULL;
    }
}
$filterSqlWhere = $Filter::$sqlWhere ? " AND " . implode(" AND ", $Filter::$sqlWhere) : "";
$allow_active_news = true;
$sql_count = $Filter::sqlCount();
$config["news_number"] = $configDleFilter["news_number"] ?: $config["news_number"];
$cstart = $Filter::$pageFilter;
if ($cstart) {
    $cstart = ($cstart - 1) * $config["news_number"];
}
$sql_select = $Filter::sqlSelect();
$tpl->is_custom = true;
include DLEPlugins::Check(ENGINE_DIR . "/modules/show.short.php");
if ($configDleFilter["redirect_filter"] && !$dleFilterVars["ajax"] && !$dleFilterVars["page"]) {
    $Filter::redirect($count_all);
}
if (!$news_found) {
    if ($configDleFilter["code_filter"] == 404) {
        @header("HTTP/1.0 404 Not Found");
    }
    $tpl->load_template("info.tpl");
    $tpl->set("{error}", $langDleFilter["site"]["not_found"]);
    $tpl->set("{title}", $langDleFilter["site"]["info"]);
    $tpl->compile("content");
    $tpl->clear();
} else {
    if ($config["files_allow"] && strpos($tpl->result["content"], "[attachment=") !== false) {
        $tpl->result["content"] = show_attach($tpl->result["content"], $attachments);
    }
}
if (14 <= (double) $config["version_id"]) {
    $tpl->result["content"] = LazyDev\Filter\Filter::navigation();
}
unset($cat_info[$category_id]);
$category_id = false;
$Filter::countTag($count_all);
if ($dleFilterVars["page"]) {
    $metatags["title"] = stripslashes($dleFilterVars["crown"]["meta_title"]) ?: $metatags["title"];
    $metatags["description"] = stripslashes($dleFilterVars["crown"]["meta_descr"]) ?: $metatags["description"];
    $metatags["keywords"] = stripslashes($dleFilterVars["crown"]["meta_key"]) ?: $metatags["keywords"];
    $metatags["speedbar"] = stripslashes($dleFilterVars["crown"]["speedbar"]) ?: "";
    $social_tags["site_name"] = $config["home_title"];
    $social_tags["type"] = "website";
    if ($dleFilterVars["crown"]["og_title"]) {
        $social_tags["title"] = str_replace("&amp;", "&", $dleFilterVars["crown"]["og_title"]);
    }
    if ($dleFilterVars["crown"]["og_descr"]) {
        $social_tags["description"] = str_replace("&amp;", "&", $dleFilterVars["crown"]["og_descr"]);
    }
    if ($dleFilterVars["crown"]["og_image"]) {
        $social_tags["image"] = $config["http_home_url"] . "uploads/dle_filter/" . $dleFilterVars["crown"]["og_image"];
    }
    $social_tags["url"] = $Filter::$urlFilter . "/";
    if ($dleFilterVars["crown"]["seo_title"]) {
        $Filter::$globalTag["tag"]["{dle-filter h1}"] = $dleFilterVars["crown"]["seo_title"];
        $Filter::$globalTag["block"][] = "#\\[dle-filter h1\\](.*?)\\[\\/dle-filter\\]#is";
        $Filter::$globalTag["hide"][] = "#\\[not-dle-filter h1\\](.*?)\\[\\/not-dle-filter\\]#is";
    }
    if ($dleFilterVars["crown"]["seo_text"]) {
        $Filter::$globalTag["tag"]["{dle-filter description}"] = stripslashes($dleFilterVars["crown"]["seo_text"]);
        $Filter::$globalTag["block"][] = "#\\[dle-filter description\\](.*?)\\[\\/dle-filter\\]#is";
        $Filter::$globalTag["hide"][] = "#\\[not-dle-filter description\\](.*?)\\[\\/not-dle-filter\\]#is";
    }
} else {
    $Filter::$seoView->result["seo"] = $Conditions::realize($Filter::$seoView->result["seo"], $allFilterData);
    if (substr_count($Filter::$seoView->result["seo"], "[dle-filter declination")) {
        $Filter::$seoView->result["seo"] = preg_replace_callback("#\\[dle-filter declination=(.+?)\\](.*?)\\[/declination\\]#is", function ($m) {
            return LazyDev\Filter\Helper::declinationLazy(array($m[1], $m[2]));
        }, $Filter::$seoView->result["seo"]);
        $Filter::$seoView->result["seo"] = preg_replace("#\\[dle-filter declination(.+?)\\](.*?)\\[/declination\\]#is", "", $Filter::$seoView->result["seo"]);
    }
    preg_match("'\\[meta-title\\](.*?)\\[/meta-title\\]'si", $Filter::$seoView->result["seo"], $metaTitle);
    if ($metaTitle[1] != "") {
        $metatags["title"] = $metaTitle[1];
    }
    preg_match("'\\[meta-description\\](.*?)\\[/meta-description\\]'si", $Filter::$seoView->result["seo"], $metaDescr);
    if ($metaDescr[1] != "") {
        $metatags["description"] = $metaDescr[1];
    }
    preg_match("'\\[meta-keywords\\](.*?)\\[/meta-keywords\\]'si", $Filter::$seoView->result["seo"], $metaKeys);
    if ($metaKeys[1] != "") {
        $metatags["keywords"] = $metaKeys[1];
    }
    $alreadyHaveRobotsDleFilter = false;
    preg_match("'\\[meta-robots\\](.*?)\\[/meta-robots\\]'si", $Filter::$seoView->result["seo"], $metaRobots);
    if ($metaRobots[1] != "") {
        $metatags["keywords"] = $metatags["keywords"] . "\">\n<meta name=\"robots\" content=\"" . $metaRobots[1];
        $alreadyHaveRobotsDleFilter = true;
    } else {
        $metatags["keywords"] .= $Filter::metaRobots();
    }
    preg_match("'\\[meta-speedbar\\](.*?)\\[/meta-speedbar\\]'si", $Filter::$seoView->result["seo"], $metaBread);
    if ($metaBread[1] != "") {
        $metatags["speedbar"] = $metaBread[1];
    }
    $metatags = $Conditions::cleanArray($metatags);
}
if ($config["speedbar"] && $metatags["speedbar"]) {
    $tpl->load_template("lazydev/dle_filter/speedbar.tpl");
    $tpl->set("{site-name}", $config["short_title"]);
    $tpl->set("{site-url}", $config["http_home_url"]);
    $tpl->set("{separator}", $config["speedbar_separator"]);
    $tpl->set("{filter-name}", $metatags["speedbar"]);
    $tpl->set("{filter-url}", $url_page . "/");
    $tpl->set("{page-descr}", $lang["news_site"]);
    $tpl->set("{page}", $Filter::$pageFilter);
    if (1 < $Filter::$pageFilter) {
        $tpl->set_block("'\\[second\\](.*?)\\[/second\\]'si", "\\1");
        $tpl->set_block("'\\[first\\](.*?)\\[/first\\]'si", "");
    } else {
        $tpl->set_block("'\\[second\\](.*?)\\[/second\\]'si", "");
        $tpl->set_block("'\\[first\\](.*?)\\[/first\\]'si", "\\1");
    }
    $tpl->compile("speedbar");
    $tpl->clear();
    $config["speedbar"] = false;
}
if ($configDleFilter["cache_filter"]) {
    $cacheArray = array("content" => $tpl->result["content"], "seo" => array("title" => $metatags["title"], "description" => $metatags["description"], "keywords" => $metatags["keywords"], "speedbar" => $tpl->result["speedbar"]), "navigation" => $tpl->result["navigation"], "url" => $Filter::$urlFilter, "id" => $filterID, "sqlQuery" => $sql_select, "foundNews" => intval($news_found), "countNews" => $count_all, "haveRobots" => $alreadyHaveRobotsDleFilter);
    $cacheArray = LazyDev\Filter\Helper::json($cacheArray);
    LazyDev\Filter\Cache::set($cacheArray, $filterID . $cat_info[$category_id]["short_tpl"]);
}
if ($configDleFilter["statistics"] && !$dleFilterVars["page"] && $Filter::$pageFilter < 2) {
    $Filter::setStatistics(array("mysqlTime" => $db->MySQL_time_taken, "templateTime" => $tpl->template_parse_time, "foundNews" => intval($news_found), "queryNumber" => $db->query_num, "statistics" => $filterID, "sqlQuery" => $db->safesql($sql_select)));
}

?>