<?php
return array(
    'overview'    =>  array(
        'page_title'    => __('Statistiques', 'stats-crawler'),
        'menu_title'    => __('Crawler', 'stats-crawler'),
        'capability'    => 'administrator',
        'menu_slug'     => 'overview',
        'function'      => array(new \Globalia\StatsCrawler\AdminPage\CrawlerOverview($this->container), 'index'),
        'icon_url'      => 'dashicons-networking',
        'position'      => 3,
        'sub_pages' => array(
            'domain_create' => [
                'page_title'    => __('Ajouter un domaine', 'stats-crawler'),
                'menu_title'    => __('Ajouter un domaine', 'stats-crawler'),
                'capability'    => 'administrator',
                'menu_slug'     => 'domain_create',
                'function'      => [new \Globalia\StatsCrawler\AdminPage\CrawlerOverview($this->container), 'create']
            ],
            'list_keywords' => [
                'page_title'    => __('Filtering Keywords', 'stats-crawler'),
                'menu_title'    => __('Filtering Keywords', 'stats-crawler'),
                'capability'    => 'administrator',
                'menu_slug'     => 'list_keywords',
                'function'      => [new \Globalia\StatsCrawler\AdminPage\CrawlerOverview($this->container), 'list_keywords']
            ],
            'add_keyword' => [
                'page_title'    => __('Add Keyword', 'stats-crawler'),
                'menu_title'    => __('Add Keyword', 'stats-crawler'),
                'capability'    => 'administrator',
                'menu_slug'     => 'add_keyword',
                'function'      => [new \Globalia\StatsCrawler\AdminPage\CrawlerOverview($this->container), 'add_keyword']
            ],
            'link_tracking' => [
                'page_title'    => __('Link Click Tracking', 'stats-crawler'),
                'menu_title'    => __('Link Click Tracking', 'stats-crawler'),
                'capability'    => 'administrator',
                'menu_slug'     => 'link_tracking',
                'function'      => [new \Globalia\StatsCrawler\AdminPage\CrawlerOverview($this->container), 'link_tracking']
            ]

        )
    ),
);