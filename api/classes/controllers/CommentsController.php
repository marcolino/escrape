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
  private $googleSearchLastTimestamp;
  private $googleSearchMinDelay;

  /**
   * Constructor
   */
  function __construct($app) {
    $this->app = $app;
    #print "Constructing " . $this->name . "<br>\n";
    $this->setup();
    $this->googleSearchLastTimestamp = 0;
    $this->googleSearchMinDelay = rand(24, 36);
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
    print "sync()\n";
    $this->load("persons");
#$commentsCount = $this->searchByPhone("3270079978");
print "size of persons: " . sizeof($this->db->data["persons"]) . "\n"; ob_flush(); flush();
    $n = 0;
    foreach ($this->db->data["persons"] as $person) {
      $id = $person["id"];
print "\n" . ++$n . "/" . sizeof($this->db->data["persons"]) . "\n\n"; ob_flush(); flush();
      $commentsCount = $this->searchByPhone($person["phone"]);
print $person["phone"] . " has " . $commentsCount . " comments\n\n"; ob_flush(); flush();
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
    print "searchByPhone($phone)\n"; ob_flush(); flush();

    $changed = false;
    $count = 0;
    foreach ($this->commentsDefinition as $commentDefinitionId => $commentDefinition) {
      setlocale(LC_ALL, $commentDefinition["locale"]);
      date_default_timezone_set($commentDefinition["timezone"]);
  
      $urls = [];
  
      # get md5 sum of (normalized) phone number
      $phoneMd5 = md5($this->normalizePhone($phone));
  
      # search pages containing md5 sum of phone with Google engine
      $commentPageUrls = $this->googleSearch($phoneMd5);

      # loop through all comment pages urls returned from google search
      foreach ($commentPageUrls as $url) {
  
        $this->app->log("info", "url: [$url]");

        # skip result with different domains
        if (parse_url($url)["host"] !== $commentDefinition["domain"]) {
          $this->app->log("info", "skipping url because " . parse_url($url)["host"] . " !== " . $commentDefinition["domain"]);
          continue;
        }

        # transform possible "next" pages to the "first" one
        if (preg_match("/^(.*)\/\d+\/?$/s", $url, $matches)) {
          $url = $matches[1];
        }
  
        next_comments_page:
        $url = preg_replace("/\/?$/", "", $url); # remove trailing slash
        $url = preg_replace("/\?PHPSESSID=.*/", "", $url); # remove SESSION ID
        #print "... reading url [$url] ...\n"; #DEBUG
        if (isset($urls[$url])) { # this url has been visited already, skip it
          $this->app->log("info", "skipping url because has been visited already");
          continue;
        }

        $urls[$url] = 1; # remember this url, to avoid future possible duplications
  
        $url .= "?nowap"; # on wap version we don't get some data (author? date?)
        #$comment_page = $this->getUrlContents($url);
        #if ($comment_page === FALSE) {
        if (($comment_page = $this->getUrlContents($url)) === FALSE) {
          $this->app->log("error", "can't get url [$url] contents on site [$commentDefinitionId]");
          continue;
        }

        # parse topic
        if (preg_match($commentDefinition["patterns"]["topic"], $comment_page, $matches)) {
          $topic = $matches[1];
        } else {
          $topic = null;
          $this->app->log("error", "no topic found for comment [$n] on url [$url]...");
          continue;
        }
  
        # all comments blocks
        if (preg_match_all($commentDefinition["patterns"]["block"], $comment_page, $matches)) {
          $comments_text = $matches[1];
        } else {
          $comments_text = null;
          $this->app->log("error", "not any comment found on url [$url]...");
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
            $this->app->log("error", "no author found for comment [$n] on url [$url]...");
            continue;
          }
      
          # parse date
          if (preg_match($commentDefinition["patterns"]["date"], $comment_text, $matches)) {
            $date = $this->cleanDate($matches[1]);
          } else {
            $date = null;
            $this->app->log("error", "no date found for comment [$n] on url [$url]...");
            continue;
          }
      
          # parse content
          if (preg_match($commentDefinition["patterns"]["content"], $comment_text, $matches)) {
            $content = $this->cleanContent($matches[1], $commentDefinitionId);
          } else {
            $content = null;
            $this->app->log("error", "no content found for comment [$n] on url [$url]...");
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
            $this->app->log("info", "empty comment found on url [$url]...");
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
          $this->app->log("debug", "doing a supplementary loop with next url [$url]...");
          goto next_comments_page; # do a supplementary loop with next url
        }
       
      }
    }

    if ($changed) {
#print_r($this->db->data["comments"]); print "\n"; #DEBUG
      $this->store("comments");
    }

    print "searchByPhone($phone) returning\n"; ob_flush(); flush();
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

/*
  private function googleSearch($query) {
    $max_results = 99;
    $result = array();
    $query_encoded = urlencode($query);
    
    # obtain the first html page with the formatted url
    # TODO: split and comment...
    $data = $this->getUrlContents("https://www.google.com/search?num=" . $max_results . "&filter=0" . "&" . "q=" . $query_encoded);
    if ($data === FALSE) {
      throw new Exception("can't fetch data from Google");
    }
    if (preg_match("/Our systems have detected unusual traffic from your computer network/", $data)) {
      #die("can't fetch data from Google (unusual traffic)");
      #require_once("debug/_comments.php"); return $_comments; # TODO: DEBUG-ONLY!
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
       * /
      $h3 = $g->find('h3.r', 0);
      $s = $g->find('div.s', 0);
      $a = $h3->find('a', 0);
      $link = $a->href;
      $link = preg_replace("/^\/url\?q=/", "", $link);
      $link = preg_replace("/&amp;sa=U.*                                        /", "", $link);
      $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
      $result[] = $link;
    }
     
    # clean up the memory 
    $html->clear();
    
    return $result;
  }
*/

  private function googleSearch($query) {
    $max_results = 99;
    $result = array();
    $query_encoded = urlencode($query);
    
print "googleSearch($query)\n"; ob_flush(); flush();

    # wait a minimum seconds delay before last query
    do {
      $timestamp = time(); 
    } while ($timestamp < $this->googleSearchLastTimestamp + $this->googleSearchMinDelay);
    
    # obtain the first html page with the formatted url
    # TODO: split and comment...
    $data = $this->getUrlContents("https://www.google.com/search?num=" . $max_results . "&filter=0" . "&" . "q=" . $query_encoded);
    if ($data === FALSE) {
      throw new Exception("can't fetch data from Google");
    }
    if (preg_match("/Our systems have detected unusual traffic from your computer network/", $data)) {
      #require_once("debug/_comments.php"); return $_comments; # TODO: DEBUG-ONLY!
      throw new Exception("can't fetch data from Google (unusual traffic)");
    }
    $this->googleSearchLastTimestamp = time();

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

print "googleSearch($query) returning\n"; ob_flush(); flush();
    return $result;
  }

  #$cx = '017714305635346072004:imlke-6wosa';

  private function getUrlContents($url) {
    $referer = "http://localhost/escrape";
    $user_agent = "Mozilla";
  
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl, error $errno");
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't execute curl to [$url], error $errno");
    }
    curl_close($ch);
    return $output;
  }

  /**
  * Converts a *localized* date from format
  * "Year MonthName day, hour:minute:second" to UNIX timestamp
  *
  * @param string $date   source date to be converted
  * @return string      UNIX timestamp conevrsion of the given date
  */
/* see in services/Utilities.php
  protected function date2Timestamp($date) {
    $timestamp = "0";
    for ($m = 1; $m <= 12; $m++) {
      $month_name = ucfirst(strftime("%B", mktime(0, 0, 0, $m)));
      if (strstr($date, $month_name)) {
        $date = str_replace($month_name, $m, $date);
        $date = preg_replace("/^(\d{4})\s+(\d{1,2})\s+(\d{1,2})/", "$1-$2-$3", $date);
        $timestamp = strtotime($date);
        break;
      }
    }
    return $timestamp;
  }
*/

  /**
   * Destructor
   */
  function __destruct() {
    #print "Destroying " . $this->name . "<br>\n";
  }

}