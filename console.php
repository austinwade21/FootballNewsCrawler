<?php

namespace Globalia\StatsCrawler\Command;

use Globalia\StatsCrawler\Command\StatsCrawlerCommand;
use Symfony\Component\Console\Application;

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/configs/languages.php');
if (!defined('ABSPATH')) {

    if (file_exists(dirname(dirname(dirname(__DIR__))) . '/wp/wp-load.php')) { //for bedrock
        require_once(dirname(dirname(dirname(__DIR__))) . '/wp/wp-load.php');
    } elseif (file_exists(dirname(dirname(dirname(__DIR__))) . '/wp-load.php')) { //for classic wordpress
        require_once(dirname(dirname(dirname(__DIR__))) . '/wp-load.php');
    }
}

$application = new Application();
$application->add(new StatsCrawlerCommand(null, $container, $languages));
$application->run();