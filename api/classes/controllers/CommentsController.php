<?php
/**
 * Comments controller
 * 
 * @package CommentsController
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 * Call example for POST:
 *  $ curl -X POST -d 'phone=primo post, content=bello' http://localhost/escrape/api/comments/
 */

require_once('lib/simple_html_dom.php');
require_once('lib/random_user_agent.php');
require_once('classes/services/Utilities.php');

class CommentsController extends AbstractController {

  private $commentsDefinition = array(
    "gnf" => array(
      "domain" => "gnoccaforum.com",
      "charset" => "utf-8",
      "locale" => "it_IT.UTF-8",
      "timezone" => "Europe/Rome",
      "patterns" => array(
        "topic" => "/<td.*?id=\"top_subject\">\s*Topic: (.*?)\s*\(Read \d+ times\)\s*<\/td>/s",
        "block" => "/<table\s+width=\"100%\"\s+cellpadding=\"5\"\s+cellspacing=\"0\"\s+border=\"1\"\s+border-color=\"#cccccc\"\s*>\s+(?:<tbody>)?<tr>(.*?)<\/tr>\s+(?:<\/tbody>)?<\/table>/s",
        "author" => "/<b>(?:<a href=\".*?\" title=\"View the profile of .*?\">)?(.*?)(?:<\/a>)?<\/b><br \/>\s*<span class=\"smalltext\">/s",
        #"author" => "/<b>(.*?)<\/b><br />\s*<span class=\"smalltext\">/s",
        "date" => "/<span class=\"smalltext\">«\s*<b>.*on:<\/b>\s*(.*?)\s*»<\/span>/s",
        "content" => "/(<div class=\"post\">.*?\s*(?:<div class=\"post\">|$))/s",
        "quote-signature" => "/<div class=\"quoteheader\">.*<\/div>(.*)/s",
        "next-link" => "/<b>Pagine:<\/b>.*?\[<strong>\d+<\/strong>\] <a class=\"navPages\" href=\"(.*?)\">\d+<\/a>/s",
      ),
    ),
  );
  private $googleSearchMinDelayRange = [ 15, 90 ];
  private $googleSearchLastTimestamp = 0;

  /**
   * Constructor
   */
  function __construct($router) {
    $this->router = $router;
    $this->setup();
  }

  private function setup() {
    $this->load("comments");
  }

  public function getAll() {
    return($this->db->data["comments"]);
  }

  public function get($id) {
    if (!$id) {
      throw new Exception("can't get comment: no id specified");
    }
    if (!isset($this->db->data["comments"][$id])) {
      throw new Exception("can't get comment: id [$id] not present");
    }
    return $this->db->data["comments"][$id];
  }
  
  public function getByPhone($phone) {
    if (!$phone) {
      throw new Exception("can't get comments by phone: no phone specified");
    }
    $phoneMd5 = md5($phone);
    $comments = [];
    foreach ($this->db->data["comments"] as $comment) {
      if ($comment["phoneMd5"] === $phoneMd5) {
        $comments[] = $comment;
      }
    }
    return $comments;
  }
  
  /**
   * Sync comments
   */
  public function sync() {
    $this->router->log("debug", "sync()");
    $this->load("persons");
    $this->router->log("debug", "size of persons: " . sizeof($this->db->data["persons"]));
    $n = 0;
$phones = []; # TODO: temporarily using this array to skip duplicate phones...
    foreach ($this->db->data["persons"] as $person) {
if (array_key_exists($person["phone"], $phones)) {
  $this->router->log("debug", ++$n . "/" . sizeof($this->db->data["persons"]) . " " . "***** skipping");
  continue; // skip already visited phone
} else {
  $phones[$person["phone"]] = 1;
}
      $id = $person["id"];
      $this->router->log("debug", ++$n . "/" . sizeof($this->db->data["persons"]) . " " . "*****");
      $commentsCount = $this->searchByPhone($person["phone"]);
      $this->router->log("debug", $person["phone"] . " has " . $commentsCount . " comments\n");
      $this->db->data["persons"][$id]["comments_count"] = $commentsCount;
    }
    $this->store("persons");
  }

  /**
   * Sync comments
   *
   * @param  string $phone
   * @return boolean true: success / false: error
   */
  public function searchByPhone($phone) {
    $this->router->log("debug", "searchByPhone($phone)");
    $changed = false;
    $count = 0;
    foreach ($this->commentsDefinition as $commentDefinitionId => $commentDefinition) {
      setlocale(LC_ALL, $commentDefinition["locale"]);
      date_default_timezone_set($commentDefinition["timezone"]);
  
      $urls = [];
  
      # get md5 sum of (normalized) phone number
      $phoneMd5 = md5($this->normalizePhone($phone));
  
      # search pages containing md5 sum of phone with Google engine
      $commentPageUrls = $this->googleSearch($phoneMd5, $commentDefinition["domain"]);

      # loop through all comment pages urls returned from google search
      foreach ($commentPageUrls as $url) {
  
        $this->router->log("info", "url: [$url]");

        # TODO: remove this test if domain=$domain works fine...
        # skip result with different domains
        if (parse_url($url)["host"] !== $commentDefinition["domain"]) {
          $this->router->log("debug", "skipping url because " . parse_url($url)["host"] . " !== " . $commentDefinition["domain"]);
          continue;
        }

        # transform possible "next" pages to the "first" one
        if (preg_match("/^(.*)\/\d+\/?$/s", $url, $matches)) {
          $url = $matches[1];
        }
  
        next_comments_page:
        $url = preg_replace("/\/?$/", "", $url); # remove trailing slash
        $url = preg_replace("/\?PHPSESSID=.*/", "", $url); # remove SESSION ID
        if (isset($urls[$url])) { # this url has been visited already, skip it
          $this->router->log("debug", "skipping already visited url");
          continue;
        }

        $urls[$url] = 1; # remember this url, to avoid future possible duplications
  
        $url .= "?nowap"; # on wap version we don't get some data (author? date?)
        #$comment_page = $this->getUrlContents($url);
        #if ($comment_page === FALSE) {
        if (($comment_page = $this->getUrlContents($url)) === FALSE) {
          $this->router->log("error", "can't get url [$url] contents on site [$commentDefinitionId]");
          continue;
        }

        # parse topic
        if (preg_match($commentDefinition["patterns"]["topic"], $comment_page, $matches)) {
          $topic = $matches[1];
        } else {
          $topic = null;
          $this->router->log("error", "no topic found for comment [$n] on url [$url]...");
          continue;
        }
  
        # all comments blocks
        if (preg_match_all($commentDefinition["patterns"]["block"], $comment_page, $matches)) {
          $comments_text = $matches[1];
        } else {
          $comments_text = null;
          $this->router->log("error", "not any comment found on url [$url]...");
          return;
        }
  
        $n = 0;
        $comment_next_page_url = "";
        foreach ($comments_text as $comment_text) { # loop through each comment
          $n++;

          # parse author
          if (preg_match($commentDefinition["patterns"]["author"], $comment_text, $matches)) {
            $author = $this->cleanAuthor($matches[1]);
          } else {
            $author = null;
            $this->router->log("error", "no author found for comment [$n] on url [$url]...");
            continue;
          }
      
          # parse date
          if (preg_match($commentDefinition["patterns"]["date"], $comment_text, $matches)) {
            $date = $this->cleanDate($matches[1]);
          } else {
            $date = null;
            $this->router->log("error", "no date found for comment [$n] on url [$url]...");
            continue;
          }
      
          # parse content
          if (preg_match($commentDefinition["patterns"]["content"], $comment_text, $matches)) {
            $content = $this->cleanContent($matches[1], $commentDefinitionId);
          } else {
            $content = null;
            $this->router->log("error", "no content found for comment [$n] on url [$url]...");
            continue;
          }
      
          if ($content) { # empty comments are not useful...
            $comment = [];
            $timestamp = date2Timestamp($date);
            $id = $timestamp . "-" . md5("author:[$author], content:[$content]"); # a sortable, univoque index
            $comment["phoneMd5"] = $phoneMd5;
            $comment["topic"] = $topic;
            $comment["date"] = date("Y-m-d H:i:s", $timestamp);
            $comment["timestamp"] = $timestamp;
            $comment["author"] = $author;
            $comment["content"] = $content;
            $comment["url"] = $url; # TODO: do we need this?
            $count++;
          } else {
            #$this->router->log("info", "empty comment found on url [$url]...");
            continue;
          }

          # TODO: ...
          #if (!array_key_exists($id, $this->db->data["comments"])) { # new comment id
            #print_r($comment); print "\n"; #DEBUG
            $this->db->data["comments"][$id] = $comment;
            $changed = true;
          #}

        }
  
        # match next comments page link
        preg_match($commentDefinition["patterns"]["next-link"], $comment_page, $matches);
        if ($matches) {
          $url = $matches[1];
          #$this->router->log("debug", "doing a supplementary loop with next url [$url]...");
          goto next_comments_page; # do a supplementary loop with next url
        }
       
      }
    }

    if ($changed) {
#print_r($this->db->data["comments"]); print "\n"; #DEBUG
      $this->store("comments");
    }

    return $count;
  }

  public function load($table) {
    if (!isset($this->db)) {
      $this->db = new Db();
    }
    $this->db->load($table);
  }

  public function store($table) {
    if (!isset($this->db)) {
      throw new Exception("can't store: database is not loaded");
    }
    $this->db->store($table);
  }

  private function normalizePhone($phone) {
    $result = preg_replace("/[^\d]*/", "", $phone);
    return $result;
  }
  
  private function cleanAuthor($author) {
    return $author;
  }

  private function cleanDate($date) {
    # correct quirks in date...
    $date = preg_replace("/ſ/s", "S", $date);
    $date = preg_replace("/ [ap]m$/s", "", $date);
    if (preg_match("/<strong>Today<\/strong> at (\d\d:\d\d:\d\d)/s", $date, $matches)) {
      $date = ucwords(strftime("%Y %B %d")) . ", " . $matches[1];
    }
    return $date;
  }

  private function cleanContent($content, $definitionId) {
    # strip anything outside "post" class div
    $content_html = str_get_html($content);
    $content = "";
    foreach ($content_html->find('div') as $e) {
      if (isset($e->attr["class"]) && $e->attr["class"] == "post") {
        $content = $e->innertext;
      }
    }

    # strip quotes  # TODO!!!
    if (preg_match($this->commentsDefinition[$definitionId]["patterns"]["quote-signature"], $content, $matches)) {
      $content = $matches[1]; # comment with quotes stripped off
    } else {
      ; # comment without quotes
    }

    # strip leading and trailing blanks
    $content = preg_replace("/^\s*(.*?)\s*$/", "$1", $content);

    return $content;
  }

  private function googleSearch($query, $domain) {
    $max_results = 99;
    $result = array();
    $query_encoded = urlencode($query);
    
    $this->router->log("debug", "googleSearch($query)");

    # wait a minimum seconds delay before repeating a query
    if ($this->googleSearchLastTimestamp) {
      $minDelay = rand($this->googleSearchMinDelayRange[0], $this->googleSearchMinDelayRange[1]);
      $this->router->log("debug", "googleSearch - waiting for $minDelay seconds since last query");
      time_sleep_until($this->googleSearchLastTimestamp + $minDelay);
#      while (time() < ($this->googleSearchLastTimestamp + $minDelay)) {
#        sleep(1);
#        $this->router->log("debug", "googleSearch - time: " . time());
#      }
      $this->router->log("debug", "googleSearch - finished waiting");
    }

    # obtain the first html page with the formatted url   
    $data = $this->getUrlContents(
      "https://www.google.com/search" .
      "?num=" . $max_results .
      "&filter=" . "0" .
      "&domain=" . $domain .
      "&q=" . $query_encoded,
      random_user_agent()
    );
    if ($data === FALSE) { // should not happen...
      throw new Exception("can't fetch data from Google");
    }
    $this->googleSearchLastTimestamp = time(); // remember timestamp of last Google search
    if (preg_match("/Our systems have detected unusual traffic from your computer network/", $data)) {
      throw new Exception("can't fetch data from Google (unusual traffic)");
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
  }

  private function getUrlContents($url, $ua = "Mozilla") {
    $this->router->log("debug", "getUrlContents($url, $ua)");
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl, error $errno");
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't execute curl to [$url], error $errno");
    }
    curl_close($ch);
    return $output;
  }

  /**
   * Destructor
   */
  function __destruct() {
  }

}