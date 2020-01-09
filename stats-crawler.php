<?php
/*
Plugin Name: Stats Crawler
Description: Crawl and get statistics of websites
Version: 1.0
Author: Globalia
@author: Jonathan Boilard-Leclerc
*/

namespace Globalia\StatsCrawler;

use \Globalia\StatsCrawler\AdminPage\Overview;
use Pimple\Container;
use Globalia\StatsCrawler\Helpers\View;

error_reporting(E_ERROR | E_WARNING | E_PARSE);

class StatsCrawler
{
    const PREFIX = 'stats_crawler_';

    protected $container;

    static public $social_status;

    public function __construct($container)
    {
        $this->container = $container;
        add_action('init', array($this, 'init'));
    }

    public function install()
    {
        global $wpdb, $stats_crawler_version;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table = $wpdb->prefix . StatsCrawler::PREFIX . 'domains';

        if ($wpdb->get_var("show tables like '$table'") != $table) {
            $create_table_query = "CREATE TABLE $table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255),  
            url_logic VARCHAR(255) NOT NULL,
            category_id int(11) DEFAULT NULL,
            obsolete BIT DEFAULT 0,
            dateCreated DATETIME NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";

            dbDelta($create_table_query);
        }

        $table = $wpdb->prefix . StatsCrawler::PREFIX . 'stats';

        if ($wpdb->get_var("show tables like '$table'") != $table) {
            $create_table_query = "CREATE TABLE $table (
            id int(11) NOT NULL AUTO_INCREMENT,
            domain_id int(11) NOT NULL,
            url VARCHAR(255) NOT NULL,
            title varchar(255),
            locale char(12) COLLATE utf8_unicode_ci NOT NULL,
            facebook_share_count int(10) DEFAULT NULL,
            facebook_reaction_count int(10) DEFAULT NULL,
            facebook_comment_count int(10) DEFAULT NULL,
            facebook_comment_plugin_count int(10) DEFAULT NULL,
            associated_post_id int(11) DEFAULT NULL,
            dateCreated datetime NOT NULL,
            dateUpdated datetime NOT NULL,
            uid char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
            PRIMARY KEY  (id)
            ) $charset_collate;";

            dbDelta($create_table_query);
        }

        $table = $wpdb->prefix . StatsCrawler::PREFIX . 'keywords';

        if ($wpdb->get_var("show tables like '$table'") != $table) {
            $create_table_query = "CREATE TABLE $table (
                id INT(11) NOT NULL AUTO_INCREMENT,
                keyword VARCHAR(255) NOT NULL,
                domain_id INT(11) NOT NULL,
                description VARCHAR(255) NULL DEFAULT NULL,
                created_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX domain_id (domain_id),
                INDEX created_time (created_time)
            ) $charset_collate;";

            dbDelta($create_table_query);
        }

        $table = $wpdb->prefix . StatsCrawler::PREFIX . 'link_tracking';

        if ($wpdb->get_var("show tables like '$table'") != $table) {
            $create_table_query = "CREATE TABLE $table (
                id INT(11) NOT NULL AUTO_INCREMENT,
                domain_id INT(11) NOT NULL,
                click_count INT(11) NOT NULL DEFAULT 0,
                last_clicked_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX domain_id (domain_id),
                INDEX last_clicked_time (last_clicked_time)
            ) $charset_collate;";

            dbDelta($create_table_query);
        }

        add_option('stats_crawler_version', $stats_crawler_version);
    }

    public function init()
    {
        $this->loadServices();
        add_action('admin_menu', array($this, 'loadAdminPages'));
        add_action('admin_enqueue_scripts', array($this, 'loadAssets'));
        $this->addPostActions();
        add_action('query_vars', [$this, 'addQueryVars']);

    }

    public function loadServices()
    {
        $languages = __DIR__ . '/configs/languages.php';
        $configs = __DIR__ . '/configs/general.php';
        $services_file = __DIR__ . '/configs/services.php';

        if (file_exists($languages) && file_exists($services_file)) {
            require_once($languages);
            require_once($configs);
            require_once($services_file);
        } else {
            //die('Missing configurations');
        }
    }

    public function loadAssets($hook)
    {
        if (strpos($hook, 'stats_crawler') !== false) {
            wp_enqueue_script('foundation-js', 'https://cdn.jsdelivr.net/foundation/6.2.4/foundation.min.js');
            wp_enqueue_script('foundation-tabs', 'https://cdnjs.cloudflare.com/ajax/libs/foundation/6.2.4/plugins/foundation.tabs.js');
            wp_enqueue_script('app', plugins_url('assets/js/app.js', __FILE__), array('jquery', 'foundation-js', 'foundation-tabs'));
            wp_enqueue_style('foundation-css', 'https://cdnjs.cloudflare.com/ajax/libs/foundation/6.2.4/foundation.css');
            wp_enqueue_style('styles-css', plugins_url('assets/css/style.css', __FILE__));
        }
    }

    public function loadAdminPages()
    {
        $admin_pages_file = __DIR__ . '/configs/adminPages.php';

        if (file_exists($admin_pages_file)) {
            $adminPages = require_once($admin_pages_file);

            foreach ($adminPages as $adminPage) {
                $position = (!empty($adminPage['position'])) ? $adminPage['position'] : null;

                add_menu_page($adminPage['page_title'], $adminPage['menu_title'], $adminPage['capability'], self::PREFIX . $adminPage['menu_slug'], $adminPage['function'], $adminPage['icon_url'], $position);
                if (isset($adminPage['sub_pages'])) {
                    $this->loadSubpages(self::PREFIX . $adminPage['menu_slug'], $adminPage['sub_pages']);
                }
            }
        }
    }

    protected function loadSubpages($parent_menu_slug, array $subPages)
    {
        foreach ($subPages as $subpage) {
            add_submenu_page($parent_menu_slug, $subpage['page_title'], $subpage['menu_title'], $subpage['capability'], self::PREFIX . $subpage['menu_slug'], $subpage['function']);
        }
    }

    protected function addPostActions()
    {

        add_action('admin_post_stats_crawler_add_domain', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::post_add_domain');
        add_action('admin_post_stats_crawler_disable_domain', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::post_disable_domain');
        add_action('admin_post_stats_crawler_enable_domain', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::post_enable_domain');
        add_action('admin_post_stats_crawler_add_keyword', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::post_add_keyword');
        add_action('admin_post_stats_crawler_remove_keyword', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::post_remove_keyword');

        add_action('wp_ajax_add_link_click_count', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::ajax_add_link_click_count');
        add_action('wp_ajax_nopriv_add_link_click_count', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::ajax_add_link_click_count');

        // action when changing filter selection by days
        add_action('admin_post_add_count_filter_selection', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::post_add_count_filter_selection');
//        add_action('wp_ajax_nopriv_add_count_filter_selection', '\Globalia\StatsCrawler\AdminPage\CrawlerOverview::ajax_add_count_filter_selection');
    }

    public function addQueryVars($vars)
    {
        $vars[] = 'domainId';
        $vars[] = 'domainLocale';
        $vars[] = 'currentPage';
        return $vars;
    }


}

require_once __DIR__ . '/vendor/autoload.php';

global $stats_crawler_version;
$stats_crawler_version = '1.0';

$container = new Container();
$statsCrawler = new StatsCrawler($container);

register_activation_hook(__FILE__, array($statsCrawler, 'install'));