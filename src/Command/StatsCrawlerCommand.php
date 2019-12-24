<?php

namespace Globalia\StatsCrawler\Command;

use Globalia\StatsCrawler\Services\Crawler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class StatsCrawlerCommand extends Command
{
    const COMMANDS_DELAY = 1;
    protected $container;
    protected $languages;

    public function __construct($name = null, $container, $languages = null)
    {
        parent::__construct($name);
        $this->container = $container;
        $this->languages = $languages;
    }

    protected function configure()
    {
        $this
            ->setName('crawlstats')
            ->setDescription('Poster et reposter les articles sur les réseaux sociaux')
            ->addOption(
                'no-crawl',
                null,
                InputOption::VALUE_NONE,
                'Only retrieves data from facebook',
                null
            );;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $noCrawl = ($input->getOption('no-crawl') == 1) ? true : false;

        if (! $noCrawl) {
            $output->writeln("---- Crawling Domains ----\n");
            $this->fetchUrls($input, $output);
        }
        $output->writeln("---- Fetching Stats from Facebook ----\n");
        $this->fetchStats($input, $output);
        $output->writeln("---- Done ----");

        flush();
    }

    protected function fetchUrls(InputInterface $input, OutputInterface $output)
    {
        global $current_fetch_lang, $current_fetch_domain;
        date_default_timezone_set('UTC');
        $start_time = date("Ymd H:i:s");
        $domains = $this->getDomains();
        foreach ($domains as $domain) {
            $current_fetch_domain = $domain;
            $keywords = $this->getKeywords($domain->id);
            foreach ($this->languages as $language) {
                $current_fetch_lang = $language;
                $fetch_url = $domain->url_logic;

                $fetch_url = str_replace('[lang]', $language, $fetch_url);

                $output->writeln("---- Crawling " . $fetch_url . " ----");

                try {

                    $crawler = new Crawler();

                    if(!empty($keywords)){
                        $crawler->setKeywords($keywords);
                    }

                    $crawler->setStreamTimeout(15);

                    $crawler->setURL($fetch_url);

                    $crawler->setCrawlingDepthLimit(1);

//                     if(strstr($fetch_url, "topibuzz.me")){
// //                        $crawler->setCrawlingDepthLimit(2);
//                         $crawler->addOgTypeFilter("article");
//                     }
//                     elseif(strstr($fetch_url, "www.cafedeclic.com")){
//                         $crawler->addOgTypeFilter("article");
//                     }

                    $crawler->addContentTypeReceiveRule("#text/html#");
                    $crawler->addContentTypeReceiveRule("#application/json#");

                    $crawler->requestGzipContent(false);

                    $crawler->addLinkSearchContentType("#application/json#");
                    $crawler->excludeLinkSearchDocumentSections(\PHPCrawlerLinkSearchDocumentSections::JS_TRIGGERING_SECTIONS);

                    $crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png)$# i");

                    $crawler->enableCookieHandling(true);

                    $crawler->setHTTPProtocolVersion(1);

                    $crawler->go();

                    $report = $crawler->getProcessReport();

                    if (PHP_SAPI == "cli") $lb = "\n\r";
                    else $lb = "<br />";
                    $lb = "\n";

                    $report_str = "Summary: $fetch_url".$lb;
                    $report_str .= "Process Start Time: ".$start_time.$lb;
                    $report_str .= "Current Time: ".date("Ymd H:i:s").$lb;
                    $report_str .= "Links followed: ".$report->links_followed.$lb;
                    $report_str .= "Documents received: ".$report->files_received.$lb;
                    $report_str .= "New Post: ".$report->newPostCount.$lb;
                    $report_str .= "Bytes received: ".$report->bytes_received." bytes".$lb;
                    $report_str .= "Process runtime: ".$report->process_runtime." sec".$lb;
                    echo $report_str;
                    file_put_contents("log.txt", $report_str, FILE_APPEND);

                } catch (\Exception $e) {
                    var_dump($e->getMessage());

                }
            }
        }
    }

    protected function fetchStats(InputInterface $input, OutputInterface $output)
    {
        $facebookService = $this->container['facebookService'];
        $facebookService->updateUrlStats($input, $output);
    }

    protected function getDomains()
    {
        global $wpdb;
        $query = "
                  SELECT * FROM {$wpdb->prefix}stats_crawler_domains
                  WHERE obsolete = 0";
        $result = $wpdb->get_results(
            $query
        );
        return $result;
    }

    protected function getKeywords($domain_id)
    {
        global $wpdb;
        $query = "
                  SELECT * FROM {$wpdb->prefix}stats_crawler_keywords
                  WHERE domain_id = $domain_id";
        $result = $wpdb->get_results(
            $query
        );
        return $result;
    }
}
