<?php

namespace Globalia\StatsCrawler\Services;

use Facebook\FacebookRequest;
use Globalia\StatsCrawler\Services\Service;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FacebookStats extends Service {

    private $facebookApp;
    private $configs;

    public function __construct($container, $configs) {
        parent::__construct($container);

        $this->configs = $configs;
    }

    public function updateUrlStats(InputInterface $input, OutputInterface $output)
    {
        global $wpdb;

        $urlCountQuery = "SELECT COUNT(*) from {$wpdb->prefix}stats_crawler_stats";
        $count = $wpdb->get_var($urlCountQuery);
        $pages = ceil($count / 50);

        for ($i = 0; $i < $pages; $i++) {
            $output->writeln(round(($i/$pages)*100) . "%\n");
            $query = "SELECT * from {$wpdb->prefix}stats_crawler_stats 
                  ORDER BY id ASC
                  LIMIT %d, 50";

            $urls = $wpdb->get_results($wpdb->prepare($query, [
                $i * 50
            ]));

            if (count($urls) == 0) {
                continue;
            }
            $requests = [];

            foreach ($urls as $url) {
                $requests[$url->id] = new FacebookRequest(
                    $this->getFacebookApp()->getApp(),
                    $this->getAdminAccessToken(),
                    'GET',
                    '/',
                    [
                        'id' => $url->url,
                        'fields' => 'engagement,og_object'
                    ]
                );
            }

            $batchResponses = $this->getFacebookApp()->sendBatchRequest($requests, $this->getAdminAccessToken());

            foreach ($batchResponses as $id => $batchResponse) {
                $graphNode = $batchResponse->getGraphNode();
                $shareInfo = $graphNode->getField('engagement');
                $urlInfo = $graphNode->getField('og_object');

                if ($urlInfo && $shareInfo) {
                    $timezone = new \DateTimeZone('America/Montreal');
                    $now = new \DateTime('now', $timezone);

                    $query = "UPDATE {$wpdb->prefix}stats_crawler_stats
                      SET title = %s, 
                          facebook_share_count = %s,
                          facebook_reaction_count = %s,
                          facebook_comment_count = %s,
                          facebook_comment_plugin_count = %s,
                          dateUpdated = %s
                      WHERE id = %s";

                    $wpdb->query($wpdb->prepare($query, [
                        $urlInfo->getField('title'),
                        $shareInfo->getField('share_count'),
                        $shareInfo->getField('reaction_count'),
                        $shareInfo->getField('comment_count'),
                        $shareInfo->getField('comment_plugin_count'),
                        $now->format('Y-m-d H:i:s'),
                        $id
                    ]));
                }
            }

        }

        $output->writeln("100%");


    }

    public function getFacebookApp()
    {
        if (! isset($this->facebookApp)) {
            $this->facebookApp = new \Facebook\Facebook([
                'app_id' => $this->configs['facebook_app_id'],
                'app_secret' => $this->configs['facebook_app_secret'],
                'default_graph_version' => 'v2.8',
            ]);
        }
        return $this->facebookApp;
    }

    public function getAdminAccessToken() {
        return $this->configs['facebookAdminAccessToken'];
    }

}