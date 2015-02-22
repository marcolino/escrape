<?php

class SearchImage {
  private $searchUrl = "https://www.google.it/searchbyimage?site=imghp&image_url=%s";

   /**
   * Constructor
   */
  function __construct($app) {
    $this->app = $app;
  }

  /**
   * Sync comments
   *
   * @param  array $parameters
   * @return array
   */
  public function googleSearchImage($imageUrl) {
    print " * searchImage($imageUrl)<br>\n";

    $max_results = 99;
  
    $result = array();
    
    $query_encoded = urlencode($query);
    
    # obtain the first html page with the formatted url
    # TODO: split and comment...
    $data = $this->getUrlContents("https://www.google.com/search?num=" . $max_results . "&filter=0" . "&" . "q=" . $query_encoded);
    if ($data === FALSE) {
      print "Error fetching data from Google\n";
      return $result;
    }
    
    $html = str_get_html($data);
     
    foreach($html->find('li.g') as $g) {
      /*
       * each search results are in a list item with a class name "g"
       * we are seperating each of the elements within, into an array;
       * titles are stored within "<h3><a...>{title}</a></h3>";
       * links are in the href of the anchor contained in the "<h3>...</h3>";
       * summaries are stored in a div with a classname of "s"
       */
      $h3 = $g->find('h3.r', 0);
      $s = $g->find('div.s', 0);
      $a = $h3->find('a', 0);
      $link = $a->href;
      $link = preg_replace("/^\/url\?q=/", "", $link);
      $link = preg_replace("/&amp;sa=U.*/", "", $link);
      $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
      $result[] = $link;
    }
     
    # clean up the memory 
    $html->clear();
    
    return $result;
/*
    setlocale(LC_TIME, $this->comments["gnf"]["lc-time"]);
    date_default_timezone_set($this->comments["gnf"]["timezone"]);

    $data = [];
    $urls = [];

    # get md5 sum of (normalized) phone number
    $phoneMd5 = md5($this->normalizePhone($phone));

    # search pages containing md5 sum of phone with Google engine
    $commentPageUrls = $this->googleSearch($phoneMd5);

    # loop through all comment pages urls returned from google search
    foreach ($commentPageUrls as $url) {

      # transform possible "next" pages to the "first" one
      if (preg_match("/^(.*)\/\d+\/?$/s", $url, $matches)) {
        $url = $matches[1];
      }

      next_comments_page:
      $url = preg_replace("/\/?$/", "", $url); # remove trailing slash
      $url = preg_replace("/\?PHPSESSID=.*££££££££££££££££££££££££££££££££££££££/", "", $url); # remove SESSION ID
      #print "... reading url [$url] ...\n"; #DEBUG
      if (isset($urls[$url])) { # this url has been visited already, skip it
        continue;
      }

      $urls[$url] = 1; # remember this url, to avoid future possible duplications

      $url .= "?nowap";
      if (($comment_page = $this->getUrlContents($url) === FALSE)) {
        $this->app->log->error("can't get url [$url] contents on site [$id]");
        continue;
      }

      # all comments blocks
      if (preg_match_all($this->comments["gnf"]["patterns"]["block"], $comment_page, $matches)) {
        $comments = $matches[1];
      } else {
        $this->app->log->error("not any comment found on url [$url]...");
        continue;
      }

      $comment_next_page_url = "";
      foreach ($comments as $comment) { # loop through each comment
        # parse author
        if (preg_match($this->comments["gnf"]["patterns"]["author"], $comment, $matches)) {
        $author = $this->cleanAuthor($matches[1]);
        } else {
        $this->app->log->error("no author found for comment [$n] on url [$url]...");
        continue;
        }
    
        # parse date
        if (preg_match($this->comments["gnf"]["patterns"]["date"], $comment, $matches)) {
        $date = $this->cleanDate($matches[1]);
        } else {
        $this->app->log->error("no date found for comment [$n] on url [$url]...");
        continue;
        }
    
        # parse content
        if (preg_match($this->comments["gnf"]["patterns"]["content"], $comment, $matches)) {
        $content = $this->cleanContent($matches[1]);
        } else {
        $this->app->log->error("no content found for comment [$n] on url [$url]...");
        continue;
        }
    
        if ($content) { # empty comments are not useful...
        $timestamp = $this->date2Timestamp($date); # TODO: put in common class...
        $key = $timestamp . "-" . md5("author:[$author], content:[$content]"); # a sortable, univoque index
        $data[$key]["author"] = $author;
        #$data[$key]["date"] = $date; # TODO: use only timestamp
        $data[$key]["timestamp"] = $timestamp;
        $data[$key]["content"] = $content;
        $data[$key]["url"] = $url; # TODO: do we need this?
        } else {
        $this->app->log->info("empty comment found on url [$url]...");
        continue;
        }
      }
    
      # match next comments page link
      preg_match($this->comments["gnf"]["patterns"]["next-link"], $comment_page, $matches);
      if ($matches) {
        $url = $matches[1];
        $this->app->log->debug("doing a supplementary loop with next url [$url]...");
        goto next_comments_page; # do a supplementary loop with next url
      }
     
    }
#print_r($data); print "<br>\n"; #DEBUG
    return $data;
*/
  }

  private function getUrlContents($url) {
    $referer = "http://localhost/escrape";
    $user_agent = "Mozilla";
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }
}



/**/
#require 'SearchImage.php';

$si = new SearchImage;
$imgurl = "http://www.sexyguidaitalia.com/public/23383/copertina.jpg";
$list = $si->googleSearchImage($imgurl);

echo "$list of similar images on different sites is this:<br>\n" . $list;
/**/

?>