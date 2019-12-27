<?php

namespace Globalia\StatsCrawler\Services;

use DOMDocument;
use DOMXPath;
use PHPCrawlerDocumentInfo;

include(dirname(__DIR__) . "/Libs/PHPCrawl_083/libs/PHPCrawler.class.php");

class Crawler extends \PHPCrawler
{
    private  $ogtype_filter = null;

    private $linkcode_filters = [
        "menu",
        "search",
        "contact",
        "privacy",
        "category",
        "a-propos",
        "qui-sommes-nous",
        "faq",
        "manifeste",
        "presse",
        "recrute",
        "annoncez",
        "conditions-dutilisation",
        "archives",
        "/tag/",
        "connexion",
        "inscription",
        "/partenaire/",
        "/shopping/",
        "login",
        "log-in"
    ];

    private $_keywords = null;

    private $_domDocument = false;
    private $_domMetaTags = false;
    private $_url = false;
    private $_newUrl = false;
    private $_newPostCount = 0;

    public function getProcessReport()
    {
        $report = parent::getProcessReport();
        $report->newPostCount = $this->_newPostCount;
        return $report;
    }

    public function addOgTypeFilter($filters){
        if($this->ogtype_filter == null){
            $this->ogtype_filter = array();
        }
        $this->ogtype_filter = array_merge($this->ogtype_filter, array_map('trim', explode(",", $filters)));
    }
    /**
     * Called by the Crawler. Performs all the steps required to make the document into a WordPress post if the document
     * is relevant
     * @param PHPCrawlerDocumentInfo $documentInfo Data received from the crawler.
     */
    function handleDocumentInfo(PHPCrawlerDocumentInfo $documentInfo)
    {
        $this->_newUrl = false;

        if ($this->isDocumentRelevant($documentInfo) && !empty($documentInfo->content)) {
            $statisticsEntryId = $this->createStatisticsEntry($documentInfo);
            if ($this->_newUrl) {
                $this->_newPostCount ++;
                $this->_domMetaTags = [];
                $postId = $this->createPostFromDocument($documentInfo);
                if ($postId !== false) {
                    $this->associatePostAndStatisticsEntry($postId, $statisticsEntryId);
                    $this->associateMetaTagsToPost($postId);
                }
            }
        }
        flush();
        return 1;
    }

    /**
     * Determines if current DocumentInfo instance represents an article page (relevant) or any other type of page such
     * as the homepage, searchpage, etc which are irrelevant.
     * @param PHPCrawlerDocumentInfo $documentInfo The DocumentInfo instance to determine relevancy on
     * @return bool Returns true if relevant, false otherwise.
     */
    private function isDocumentRelevant(PHPCrawlerDocumentInfo $documentInfo)
    {
        if($documentInfo->content_type != "text/html"){
            return false;
        }

        if(!empty($this->_keywords)){
            $this->_domDocument = new DOMDocument();
            libxml_use_internal_errors(true);
            $this->_domDocument->loadHTML($documentInfo->content);
            //$this->getFirstCharacters();

            $title = $this->getMetaTagValue("og:title");
            $description = $this->getFirstCharacters(300);

            foreach ($this->_keywords as $keyword){
                if(strpos(strip_tags($title), $keyword) !== false){
                    return $this->checkLinkCode($documentInfo);
                }
                if(strpos(strip_tags($description), $keyword) !== false){
                    return $this->checkLinkCode($documentInfo);
                }
            }
            return false;
        }

        if(!empty($this->ogtype_filter)){
            if(in_array($documentInfo->meta_attributes["og:type"], $this->ogtype_filter)){
                return $this->checkLinkCode($documentInfo);
            }
            else{
                return false;
            }
        }

        return $this->checkLinkCode($documentInfo);
    }

    private function checkLinkCode(PHPCrawlerDocumentInfo $documentInfo){
        // We've received something and the url is not the homepage
        if ($documentInfo->received && $documentInfo->referer_url) {
            // Apply custom filters
            foreach ($this->linkcode_filters as $linkcode_filter) {
                if (strpos($documentInfo->refering_linkcode, $linkcode_filter) !== false) {
                    return false;
                }
                foreach ($documentInfo->meta_attributes as $meta_attribute) {
                    if (strpos($meta_attribute, $linkcode_filter) !== false) {
                        return false;
                    }
                }
            }
            // Document relevant
            return true;
        }
        return false;
    }

    /**
     * Creates a new entry to hold the statistics on the page
     * @param PHPCrawlerDocumentInfo $documentInfo The DocumentInfo instance to create the entry from.
     */
    private function createStatisticsEntry(PHPCrawlerDocumentInfo $documentInfo)
    {
        global $wpdb, $current_fetch_lang, $current_fetch_domain;
        $this->_url = $documentInfo->url;

        $timezone = new \DateTimeZone('America/Montreal');
        $now = new \DateTime('now', $timezone);

        $query = "
              SELECT * FROM {$wpdb->prefix}stats_crawler_stats
              WHERE
                url = %s";
        $prepare = [$this->_url];
        $result = $wpdb->query(
            $wpdb->prepare($query, $prepare)
        );
        $wpdb->flush();

        // There is no entry for that url, let's create one
        if (! $result) {
            $query = "
                  INSERT INTO {$wpdb->prefix}stats_crawler_stats
                  (url, domain_id, locale, dateCreated, uid)
                  VALUES ( %s, %d, %s, %s, %s )";

            $prepare = [
                $this->_url,
                $current_fetch_domain->id,
                $current_fetch_lang,
                $now->format('Y-m-d H:i:s'),
                uniqid("{$wpdb->prefix}stats_crawler_stats")
            ];
            $wpdb->query(
                $wpdb->prepare($query, $prepare)
            );
            $wpdb->flush();
            $this->_newUrl = true;
            return $wpdb->insert_id;
        }
    }

    /**
     * Creates a new WordPress post from a DocumentInfo Instance
     * @param $documentInfo The DocumentInfo instance to create the post from
     * @return bool|int|\WP_Error Returns the postId on success, false on error.
     */
    private function createPostFromDocument(PHPCrawlerDocumentInfo $documentInfo)
    {
        global $current_fetch_domain;

        $this->_domDocument = new DOMDocument();
        libxml_use_internal_errors(true);
        $this->_domDocument->loadHTML($documentInfo->content);
        //$this->getFirstCharacters();

        $title = $this->getMetaTagValue("og:title");

        $post = array(
            'post_title' => wp_strip_all_tags($title),
            'post_content' => $this->getFirstCharacters(100),
            'post_status' => 'draft',
            'post_author' => 1,
            'post_category' => [$current_fetch_domain->category_id]
        );

        // Insert the post into the database
        if ($postId = wp_insert_post($post)) {
            $post['ID'] = $postId;
            // Use meta og:image as the featured image of the post
            $imageUrl = $this->getMetaTagValue("og:image");
            $hasFeaturedImage = false;
            if ($imageUrl) {
                $hasFeaturedImage = $this->generateFeaturedImage($imageUrl, $postId);
            } else {
                update_post_meta($postId, "featured_image_required", true);
            }

            // Was the featured image successfully retrieved?
            if ($hasFeaturedImage) {
                $post['post_status'] = 'publish';
                wp_update_post($post);
            }
            update_post_meta($postId, "alltrends_post-sticky_sidebar", true);
            update_post_meta($postId, "origin_url", $documentInfo->url);
            update_post_meta($postId, "post-on-social-medias-status", "published_on_site");
            return $postId;
        }
        return false;
    }

    /**
     * Retrieves the meta properties of the document
     * @param bool $noCache Forces update if true
     * @return array The Document's meta tags
     */
    private function retrieveDomMetaTags($noCache = false)
    {
        if (! $this->_domMetaTags || empty($this->_domMetaTags) || $noCache === true) {

            $xpath = new DOMXPath($this->_domDocument);
            $query = '//*/meta';
            $metas = $xpath->query($query);

            foreach ($metas as $meta) {
                $property = $meta->getAttribute('property');
                $content = $meta->getAttribute('content');
                $this->_domMetaTags[$property] = $content;
            }
        }
        return $this->_domMetaTags;
    }

    /**
     * Returns the meta property with the given key
     * @param $metaKey Key of the OpenGraph property
     * @return mixed Returns the value if the property is found, false otherwise.
     */
    private function getMetaTagValue($metaKey)
    {
        $metaTags = $this->retrieveDomMetaTags(true);
        if (! isset($metaTags[strtolower($metaKey)]) || empty($metaTags[strtolower($metaKey)])) {
            return false;
        } else {
            return $metaTags[strtolower($metaKey)];
        }
    }

    /**
     * Associates the _domMetaTags to a post id
     * @param $postId The post id to associate the tags to
     */
    private function associateMetaTagsToPost($postId)
    {
        foreach ($this->retrieveDomMetaTags() as $domMetaTagKey => $domMetaTagValue) {
            update_post_meta($postId, $domMetaTagKey, $domMetaTagValue);
        }
    }

    /**
     * Fetches <p> tags until $wordLength is reached.
     * @param int $length
     * @return string
     */
    private function getFirstCharacters($wordLength = 1000)
    {
        $content = "";

        $xpath = new DOMXPath($this->_domDocument);
        $query = "(//*[self::p and not(@class)][not(a) and string-length() > 100])";
        $nodes = $xpath->query($query);
        $wordCount = 0;

        for ($i = 0; $i < $nodes->length; $i++) {
            $currentNode = $nodes->item($i);
            $nodeValue = $currentNode->nodeValue;
            if (strpos($nodeValue, "script") !== false
                || strpos($nodeValue, "function") !== false
                || strpos($nodeValue, "robot de nouvelles") !== false
                || strpos($nodeValue, "enverra automatiquement") !== false) {
                continue;
            }
            $wordCount += substr_count($nodeValue, " ");
            $content .= "<p>";
            $content .= $nodeValue;
            $content .= "</p>";

            if ($wordCount > $wordLength) {
                break;
            }
        }
        return $content;
    }

    /**
     * Fetches the image at the given url and generates a featured image for the specified post
     * @param $image_url Url of the image to retrieve
     * @param $post_id Id of the post to attach the featured image to
     * @return bool returns true on success, false on failure
     */
    private function generateFeaturedImage($image_url, $post_id)
    {
        $downloadResult = $this->dowloadFile($image_url);

        if ($downloadResult === false) {
            return false;
        }

        // Create an entry in the media section
        $wp_filetype = wp_check_filetype($downloadResult['filename'], null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($downloadResult['filename']),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $downloadResult['file'], $post_id);

        // Attach the media file as the featured image of the post
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $downloadResult['file']);
        $res1 = wp_update_attachment_metadata($attach_id, $attach_data);
        return set_post_thumbnail($post_id, $attach_id);
    }

    private function getTitle($htmlString){
        $re = '/\<title\>(.*?)\<\/title\>/m';
        if(preg_match($re, $htmlString, $matches, PREG_SET_ORDER, 0)){
            return $matches[1];
        }
        return "";
    }

    private function dowloadFile($url)
    {
        $upload_dir = wp_upload_dir();
        $filename = basename($url);
        $urlInfo = parse_url($url);

        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        $fp = fopen ($file, 'w+');
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_REFERER, $urlInfo['scheme'].'://'.$urlInfo['host']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch,CURLOPT_FAILONERROR, true);
        curl_exec($ch);

        if (curl_error($ch)) {
            return false;
        }

        curl_close($ch);
        fclose($fp);

        return [
            "file" => $file,
            "filename" => $filename
        ];
    }

    private function associatePostAndStatisticsEntry($postId, $statisticsEntryId)
    {
        global $wpdb;
        update_post_meta($postId, "associated_statistics_entry_id", $statisticsEntryId);

        $timezone = new \DateTimeZone('America/Montreal');
        $now = new \DateTime('now', $timezone);

        $query = "UPDATE {$wpdb->prefix}stats_crawler_stats
                      SET associated_post_id = %s, dateUpdated = %s
                      WHERE id = %s";
        $wpdb->query($wpdb->prepare($query, [
            $postId,
            $now->format('Y-m-d H:i:s'),
            $statisticsEntryId
        ]));

    }

    /**
     * Initiates a new crawler.
     */
    public function __construct()
    {
        parent::__construct();
        $this->PageRequest = new CrawlerHTTPRequest();
        $this->PageRequest->setHeaderCheckCallbackFunction($this, "handleHeaderInfo");

    }

    public function setKeywords($keywords){
        if(!empty($keywords)){
            $this->_keywords = array_map(function ($keyword){
                return $keyword->keyword;
            }, $keywords);
        }
    }

}