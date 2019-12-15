<?php

use \Globalia\StatsCrawler\Services\Crawler;
use \Globalia\StatsCrawler\Services\FacebookStats;
use \Globalia\StatsCrawler\Services\FacebookPosts;
use \Globalia\StatsCrawler\Services\SocialPosts;

$this->container['facebookService'] = function ($c) use ($configs) {
    return new FacebookStats($c, $configs);
};