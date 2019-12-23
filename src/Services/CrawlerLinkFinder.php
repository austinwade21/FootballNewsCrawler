<?php


namespace Globalia\StatsCrawler\Services;


class CrawlerLinkFinder extends \PHPCrawlerLinkFinder
{
    public function __construct()
    {
        parent::__construct();
    }

    public function findLinksInHTMLChunk(&$html_source)
    {
        if(strstr($this->SourceUrl->url_rebuild, "https://www.narcity.com/_homepage.json")){
            $json_source = json_decode($html_source);
            foreach ($json_source->articles as $article){
                $this->addLinkToCache($article->path, $article->path);
            }
        }
        else{
            parent::findLinksInHTMLChunk($html_source);
        }
    }
}