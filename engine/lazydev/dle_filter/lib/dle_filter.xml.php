<?php
/**
 * Sitemap для дополнительных страниц фильтра
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

global $db, $config;

if ((double)$config['version_id'] > 15.1) {
    $this->priority = '0.8';
    $this->changefreq = 'weekly';

    $this->db_result = $db->query("SELECT page_url, dateEdit, date FROM " . PREFIX . "_dle_filter_pages WHERE sitemap AND approve");

    $this->sitemap->links('pages.xml', function($map) {
        global $db;

        while ($row = $db->get_row($this->db_result)) {
            $loc = $row['page_url'] . '/';
            $date = $row['dateEdit'] ? $row['dateEdit'] : ($row['date'] ?: date('c'));
            $date = date('c', strtotime($date));

            $map->loc($loc)->freq($this->changefreq)->lastMod($date)->priority($this->priority);
        }
    });
} else {
    $this->priority = '0.8';
    $xml = '';
    $db->query("SELECT page_url, dateEdit, date FROM " . PREFIX . "_dle_filter_pages WHERE sitemap AND approve");
    while ($row = $db->get_row()) {
        $url = $this->home . $row['page_url'] . '/';
        $date = $row['dateEdit'] ? $row['dateEdit'] : ($row['date'] ?: date('Y-m-d', time()));
        $date = date('Y-m-d', strtotime($date));
        $xml .= $this->get_xml($url, $date);
    }

    $map .= $xml;
}