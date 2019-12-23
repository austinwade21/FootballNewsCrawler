<?php


namespace Globalia\StatsCrawler\Services;

use PHPCrawlerDocumentInfo;
use PHPCrawlerEncodingUtils;
use PHPCrawlerResponseHeader;
use PHPCrawlerURLDescriptor;
use PHPCrawlerUtils;

class CrawlerHTTPRequest extends \PHPCrawlerHTTPRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->LinkFinder = new CrawlerLinkFinder();
    }

    /**
     * Sends the HTTP-request and receives the page/file.
     *
     * @return PHPCrawlerDocumentInfo object containing all information about the received page/file
     * @throws \Exception
     */
    public function sendRequest()
    {
        $pageInfo = parent::sendRequest();
        if($pageInfo->error_code !== 0){
            $pageInfo = $this->sendRequestCURL();
        }
        if($pageInfo->url === "https://www.narcity.com"){
            $loadMoreUrl = new PHPCrawlerURLDescriptor("https://www.narcity.com/_homepage.json?page=1", null, null, null, null, 0);
        }
        elseif (preg_match('/https\:\/\/www\.narcity\.com\/\_homepage\.json\?page\=[\d]+/m', $pageInfo->url)){
            $json_content = json_decode($pageInfo->content);
//            if(!empty($json_content->articles)){
            parse_str(trim($pageInfo->query, "?"), $params);
            if((int)$params["page"] < 20){
                $loadMoreUrl = new PHPCrawlerURLDescriptor("https://www.narcity.com/_homepage.json?page=".($params["page"] + 1), null, null, null, null, 0);
            }
            else{
                return $pageInfo;
            }
        }
        elseif (preg_match('/https\:\/\/www\.cafedeclic\.com\/\?p\=[\d]+/m', $pageInfo->url)){
            parse_str(trim($pageInfo->query, "?"), $params);
            if((int)$params["p"] < 20){
                $loadMoreUrl = new PHPCrawlerURLDescriptor("https://www.cafedeclic.com/?p=".($params["p"] + 1), null, null, null, null, 0);
            }
            else{
                return $pageInfo;
            }
        }
        else{
            return $pageInfo;
        }

        echo $pageInfo->url."\n";

        $pageInfo->links_found[] = $loadMoreUrl;
        $pageInfo->links_found_url_descriptors[] = $loadMoreUrl;
        return $pageInfo;

    }

    public function sendRequestCURL(){
        // Prepare LinkFinder
        $this->LinkFinder->resetLinkCache();
        $this->LinkFinder->setSourceUrl($this->UrlDescriptor);

        // Initiate the Response-object and pass base-infos
        $PageInfo = new PHPCrawlerDocumentInfo();
        $PageInfo->url = $this->UrlDescriptor->url_rebuild;
        $PageInfo->protocol = $this->url_parts["protocol"];
        $PageInfo->host = $this->url_parts["host"];
        $PageInfo->path = $this->url_parts["path"];
        $PageInfo->file = $this->url_parts["file"];
        $PageInfo->query = $this->url_parts["query"];
        $PageInfo->port = $this->url_parts["port"];
        $PageInfo->url_link_depth = $this->UrlDescriptor->url_link_depth;

        // Create header to send
        $request_header_lines = $this->buildRequestHeader();
        $header_string = trim(implode("", $request_header_lines));
        $PageInfo->header_send = $header_string;

        $PageInfo->server_connect_time = $this->server_connect_time;

        // Send request
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $PageInfo->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $request_header_lines,
        ));

        $response = curl_exec($curl);
        $PageInfo->error_code = curl_errno($curl);
        $PageInfo->error_string = curl_error($curl);

        // Read response-header
        $this->header_bytes_received = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $this->content_bytes_received = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $response_header = substr($response, 0, $this->header_bytes_received);
        $response_content = substr($response, $this->header_bytes_received);

        curl_close($curl);

        $this->LinkFinder->processHTTPHeader($response_header);

        $PageInfo->server_response_time = $this->server_response_time;

        // If error occured
        if ($PageInfo->error_code != null)
        {
            $PageInfo->error_occured = true;
            return $PageInfo;
        }

        // Set header-infos
        $this->lastResponseHeader = new PHPCrawlerResponseHeader($response_header, $this->UrlDescriptor->url_rebuild);
        $PageInfo->responseHeader = $this->lastResponseHeader;
        $PageInfo->header = $this->lastResponseHeader->header_raw;
        $PageInfo->http_status_code = $this->lastResponseHeader->http_status_code;
        $PageInfo->content_type = $this->lastResponseHeader->content_type;
        $PageInfo->cookies = $this->lastResponseHeader->cookies;

        // Referer-Infos
        if ($this->UrlDescriptor->refering_url != null)
        {
            $PageInfo->referer_url = $this->UrlDescriptor->refering_url;
            $PageInfo->refering_linkcode = $this->UrlDescriptor->linkcode;
            $PageInfo->refering_link_raw = $this->UrlDescriptor->link_raw;
            $PageInfo->refering_linktext = $this->UrlDescriptor->linktext;
        }

        // Check if content should be received
        $receive = $this->decideRecevieContent($this->lastResponseHeader);

        if ($receive == false)
        {
            @fclose($this->socket);
            $PageInfo->received = false;
            $PageInfo->links_found_url_descriptors = $this->LinkFinder->getAllURLs(); // Maybe found a link/redirect in the header
            $PageInfo->meta_attributes = $this->LinkFinder->getAllMetaAttributes();
            return $PageInfo;
        }
        else
        {
            $PageInfo->received = true;
        }

        // Check if content should be streamd to file
        $stream_to_file = $this->decideStreamToFile($response_header);

        // Read content
        $gzip_encoded_content = null;
        if ($gzip_encoded_content === null)
        {
            if (PHPCrawlerEncodingUtils::isGzipEncoded($response_content))
                $gzip_encoded_content = true;
            else
                $gzip_encoded_content = false;
        }
        if ($gzip_encoded_content == true)
            $response_content = PHPCrawlerEncodingUtils::decodeGZipContent($response_content);
        if ($gzip_encoded_content == false && $stream_to_file == false)
        {
            if (PHPCrawlerUtils::checkStringAgainstRegexArray($this->lastResponseHeader->content_type, $this->linksearch_content_types))
            {
                $this->LinkFinder->findLinksInHTMLChunk($response_content);
            }
        }

        // Complete ResponseObject
        $PageInfo->content = $response_content;
        $PageInfo->source = &$PageInfo->content;
        $PageInfo->received_completely = true;

        if ($stream_to_file == true)
        {
            $PageInfo->received_to_file = true;
            $PageInfo->content_tmp_file = $this->tmpFile;
            file_put_contents($this->tmpFile, $response_content);
        }
        else $PageInfo->received_to_memory = true;

        $PageInfo->links_found_url_descriptors = $this->LinkFinder->getAllURLs();
        $PageInfo->meta_attributes = $this->LinkFinder->getAllMetaAttributes();

        // Info about received bytes
        $PageInfo->bytes_received = $this->content_bytes_received;
        $PageInfo->header_bytes_received = $this->header_bytes_received;

        $PageInfo->setLinksFoundArray();

        $this->LinkFinder->resetLinkCache();

        return $PageInfo;

    }
}