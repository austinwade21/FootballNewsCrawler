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

    public function add_keyword(){
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}stats_crawler_domains";
        $result = $wpdb->get_results($query);

        $variables['content'] = 'add_keyword';
        $variables['domains'] = $result;
        $this->render('layout', $variables);
    }

    public function link_tracking(){
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}stats_crawler_link_tracking AS lt LEFT JOIN {$wpdb->prefix}stats_crawler_domains d ON lt.domain_id = d.id";
        $result = $wpdb->get_results($query);

        $variables['content'] = 'link_tracking';
        $variables['link_tracking'] = $result;
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

    public static function ajax_add_link_click_count(){
        global $wpdb;
        $domain_id = $_REQUEST['data_id'];
        $timezone = new \DateTimeZone('America/Montreal');
        $now = new \DateTime('now', $timezone);

        $query = "UPDATE {$wpdb->prefix}stats_crawler_link_tracking
              SET click_count=click_count+1, last_clicked_time=%s
              WHERE domain_id=%s";

        $count = $wpdb->query($wpdb->prepare($query, [
            $now->format('Y-m-d H:i:s'),
            $domain_id,
        ]));
        if(!$count){
            $query = "INSERT INTO {$wpdb->prefix}stats_crawler_link_tracking
              (domain_id, click_count, last_clicked_time)
              VALUES (%s, %s, %s)";

            $wpdb->query($wpdb->prepare($query, [
                $domain_id,
                1,
                $now->format('Y-m-d H:i:s')
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
