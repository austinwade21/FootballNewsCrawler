<?php

namespace Globalia\StatsCrawler\AdminPage;

class CrawlerOverview extends AdminPage {

    public function index()
    {
        if ($_GET['domainId']) {
            $this->domainDetails();
        } else {
            $this->domainListing();
        }

    }

    private function domainDetails()
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}stats_crawler_stats
                  WHERE domain_id = %s
                  GROUP BY url
                  ORDER BY facebook_share_count DESC";

        $query = $wpdb->prepare($query, [$_GET['domainId']]);
        $results = $wpdb->get_results($query);

        $variables['crawledUrls'] = $results;

        $variables['content'] = 'domain';

        $this->render('layout', $variables);
    }

    private function domainListing()
    {
        global $wpdb;

        $query = "SELECT * 
                    FROM {$wpdb->prefix}stats_crawler_domains as domains
                  LEFT JOIN (SELECT COUNT(DISTINCT url) as link_count, domain_id FROM {$wpdb->prefix}stats_crawler_stats GROUP BY domain_id) as stats on domains.id = stats.domain_id
                  LEFT JOIN (SELECT domain_id, GROUP_CONCAT(DISTINCT keyword SEPARATOR ', ') as keywords FROM {$wpdb->prefix}stats_crawler_keywords GROUP BY domain_id) as keywords on domains.id = keywords.domain_id;";
        $result = $wpdb->get_results(
            $query
        );

        $variables['crawledDomains'] = $result;

        $variables['content'] = 'crawlerOverview';

        $this->render('layout', $variables);
    }

    public function create()
    {
        $variables['content'] = 'domain_create';

        $this->render('layout', $variables);
    }

    public function list_keywords(){
        global $wpdb;
        $query = "SELECT kw.id, kw.keyword, d.name FROM {$wpdb->prefix}stats_crawler_keywords AS kw LEFT JOIN {$wpdb->prefix}stats_crawler_domains d ON kw.domain_id = d.id";
        $result = $wpdb->get_results($query);

        $variables['content'] = 'list_keywords';
        $variables['keywords'] = $result;
        $this->render('layout', $variables);
    }

    public function add_keyword(){
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}stats_crawler_domains";
        $result = $wpdb->get_results($query);

        $variables['content'] = 'add_keyword';
        $variables['domains'] = $result;
        $this->render('layout', $variables);
    }

    public function link_tracking(){
        $day_id = $_REQUEST['day_id'];

        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}stats_crawler_link_tracking AS lt LEFT JOIN {$wpdb->prefix}stats_crawler_domains d ON lt.domain_id = d.id";
        $result = $wpdb->get_results($query);

        $variables['content'] = 'link_tracking';
//        $variables['link_tracking'] = $result;

        //for test
        $id_array = array(-2, 1, 0, 7, 30);
        if(!in_array(intval($day_id), $id_array)){
            $day_id = -2;
        }

        $temp_array = array();
        if($day_id == null || $day_id == -2){
            foreach ($result as $domain){
                $domain_id = $domain->domain_id;
                $click_count = intval($domain->click_count);
                $is_existing = false;
                foreach ($temp_array as $k => $temp){
                    if($domain_id == $temp['domain_id']){
                        $temp_array[$k]['click_count'] += $click_count;
                        $is_existing = true;
                    }
                }
                if(!$is_existing){
                    array_push($temp_array, array('domain_id'=> $domain_id,'name'=> $domain->name,'click_count'=> $click_count));
                }
            }
        }
        else{
            $timezone = new \DateTimeZone('America/Montreal');
            $now = new \DateTime('now', $timezone);
            $now_0 = new \DateTime('now', $timezone);
            $from_date = $now_0->sub(\DateInterval::createFromDateString(''.$day_id.' days'));

            $now = $now->format('Y-m-d')." 00:00:00";
            $from_date = $from_date->format('Y-m-d')." 00:00:00";

            foreach ($result as $domain){
                $domain_id = $domain->domain_id;
                $click_count = intval($domain->click_count);
                $last_clicked_time = $domain->last_clicked_time;
                if($from_date <= $last_clicked_time && $now >= $last_clicked_time){
                    $is_existing = false;
                    foreach ($temp_array as $k => $temp){
                        if($domain_id == $temp['domain_id']){
                            $temp_array[$k]['click_count'] += $click_count;
                            $is_existing = true;
                        }
                    }
                    if(!$is_existing){
                        array_push($temp_array, array('domain_id'=> $domain_id,'name'=> $domain->name,'click_count'=> $click_count));
                    }
                }
            }
        }
        $variables['link_tracking'] = $temp_array;
        $variables['filter_select_index'] = $day_id;

        $this->render('layout', $variables);
    }

    public static function post_add_domain()
    {
        global $wpdb;
        $domain_name = $_POST['domain_name'];
        $url_logic = $_POST['url_logic'];
        $category = $_POST['category'];

        $timezone = new \DateTimeZone('America/Montreal');
        $now = new \DateTime('now', $timezone);

        $categoryParams = array(
            'cat_name' => $category,
            'category_nicename' => $category,
            'taxonomy' => 'category' );

        $categoryId = wp_insert_category($categoryParams);

        $menuName = 'AllTrends - Categories';
        $menu = wp_get_nav_menu_object( $menuName );

        if (!$menu) {
            $menuId = wp_create_nav_menu($menuName);

            $locations = get_theme_mod( 'nav_menu_locations' );

            foreach ($locations as $locationId => $locationName) {
                switch ($locationName) {
                    case 'header-menu':
                    case 'top-menu':
                        $locations[$locationId] = $menuId;
                        break;
                    default:
                }
            }
            set_theme_mod('nav_menu_locations', $locations);

        } else {
            $menuId = $menu->term_id;
        }
        wp_update_nav_menu_item($menuId, 0, array('menu-item-title' => $category,
            'menu-item-object' => 'category',
            'menu-item-object-id' => $categoryId,
            'menu-item-type' => 'taxonomy',
            'menu-item-status' => 'publish'));

        if ($categoryId) {
            $query = "INSERT INTO {$wpdb->prefix}stats_crawler_domains
                  (name, url_logic, category_id, dateCreated)
                  VALUES (%s, %s, %s, %s)";

            $wpdb->query($wpdb->prepare($query, [
                $domain_name,
                $url_logic,
                $categoryId,
                $now->format('Y-m-d H:i:s')
            ]));
        } else {
            $query = "INSERT INTO {$wpdb->prefix}stats_crawler_domains
                  (name, url_logic, dateCreated)
                  VALUES (%s, %s, %s)";

            $wpdb->query($wpdb->prepare($query, [
                $domain_name,
                $url_logic,
                $now->format('Y-m-d H:i:s')
            ]));
        }

        wp_redirect(site_url().'/wp-admin/admin.php?page=stats_crawler_overview&id=' . $wpdb->insert_id);

    }

    public static function post_add_keyword()
    {
        global $wpdb;
        $domain_id = $_POST['domain_id'];
        $keyword = $_POST['keyword'];

        $timezone = new \DateTimeZone('America/Montreal');
        $now = new \DateTime('now', $timezone);

        $query = "INSERT INTO {$wpdb->prefix}stats_crawler_keywords
              (domain_id, keyword, created_time)
              VALUES (%s, %s, %s)";

        $wpdb->query($wpdb->prepare($query, [
            $domain_id,
            $keyword,
            $now->format('Y-m-d H:i:s')
        ]));

        wp_redirect(site_url().'/wp-admin/admin.php?page=stats_crawler_overview&id=' . $domain_id);

    }

    public static function post_remove_keyword()
    {
        global $wpdb;
        $keyword_id = $_POST['keyword_id'];

        $query = "DELETE FROM {$wpdb->prefix}stats_crawler_keywords
              WHERE id=%s";

        $wpdb->query($wpdb->prepare($query, [
            $keyword_id,
        ]));

        wp_redirect(site_url().'/wp-admin/admin.php?page=stats_crawler_list_keywords');

    }

    public static function post_add_count_filter_selection(){
        $day_id = $_REQUEST['day_id'];
        wp_redirect(site_url().'/wp-admin/admin.php?page=stats_crawler_link_tracking&day_id='.$day_id);
    }

    public static function ajax_add_link_click_count(){
        global $wpdb;
        $domain_id = $_REQUEST['data_id'];
        $timezone = new \DateTimeZone('America/Montreal');
        $now = new \DateTime('now', $timezone);

        $query = "UPDATE {$wpdb->prefix}stats_crawler_link_tracking
              SET click_count=click_count+1
              WHERE last_clicked_time=%s AND domain_id=%s";

        $count = $wpdb->query($wpdb->prepare($query, [
            $now->format('Y-m-d'),
            $domain_id,
        ]));
        if(!$count){
            $query = "INSERT INTO {$wpdb->prefix}stats_crawler_link_tracking
              (domain_id, click_count, last_clicked_time)
              VALUES (%s, %s, %s)";

            $wpdb->query($wpdb->prepare($query, [
                $domain_id,
                1,
                $now->format('Y-m-d')
            ]));
        }
    }

    public static function post_disable_domain()
    {
        global $wpdb;
        $domainId = $_POST['domainId'];

        $query = "UPDATE {$wpdb->prefix}stats_crawler_domains
                      SET obsolete = 1 
                      WHERE id = %s";

        $wpdb->query($wpdb->prepare($query, [
            $domainId
        ]));

        wp_redirect(site_url().'/wp-admin/admin.php?page=stats_crawler_overview');
    }

    public static function post_enable_domain()
    {
        global $wpdb;
        $domainId = $_POST['domainId'];

        $query = "UPDATE {$wpdb->prefix}stats_crawler_domains
                      SET obsolete = 0 
                      WHERE id = %s";

        $wpdb->query($wpdb->prepare($query, [
            $domainId
        ]));

        wp_redirect(site_url().'/wp-admin/admin.php?page=stats_crawler_overview');
    }
}
