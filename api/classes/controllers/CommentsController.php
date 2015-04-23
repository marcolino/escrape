<?php
/**
 * Comments controller
 * 
 * @package CommentsController
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 */

# TODO: use Utilities getUrlContents WITH charset !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

require_once(__DIR__ . "/../../lib/simple_html_dom.php");
require_once(__DIR__ . "/../../classes/services/Utilities.php");

class CommentsController {

  /**
   * Constructor
   */
  function __construct($router) {
    require_once "setup/comments.php"; // comments setup
    $this->router = $router;
    $this->network = new Network();
    $this->db = $this->router->db;
  }

  /**
   * Sync comments
   */
  public function sync() {
    $this->router->log("debug", "comments -> sync()");

    $persons = $this->db->getPersonList();
    $timestampStart = date();
    $phones = [];
    foreach ($persons as $person) {
      $phone = $person["phone"];
      if (in_array($phone, $phones)) { // skip already processed phones
        continue;
      }
      $commentsCount = $this->searchByPhone($phone);
      $this->router->log("debug", "person with phone [$phone] has $commentsCount comments");
      $phones[] = $phone;
    }
/*
    $phones = $this->db->getFieldDistinctValues("person_detail", "phone");
    foreach ($phones as $phone) {
      $commentsCount = $this->searchByPhone($phone);
      $this->router->log("debug", "person with phone [$phone] has $commentsCount comments");
    }
*/
    return true;
  }

  /**
   * Search comments by phone
   *
   * @param  string $phone     the phone number to be searched
   * @return integer $count:   number of comments found
   *         boolean false:    error
   */
  public function searchByPhone($phone) {
    require_once(__DIR__ . "/../../lib/simpletest/browser.php");

    $this->router->log("debug", "searchByPhone($phone) [$phone]");
    $this->syncUrls = []; # array to store already sync'ed urls
    $count = 0;
    foreach ($this->commentsDefinitions as $commentDefinitionId => $cd) {
      setlocale(LC_ALL, $cd["locale"]);
      date_default_timezone_set($cd["timezone"]);

/*
      $post = [
        $cd["usernameTag"] => $cd["username"],
        $cd["passwordTag"] => $cd["password"],
      ];
      $page = $this->network->getUrlContents($cd["urlLogin"], $cd["charset"], $post, false, true);
      if ($page === FALSE) {
        $this->router->log("error", "can't get login page contents on comments definition provider [$commentDefinitionId]");
        continue;
      }
*/
      $browser = &new SimpleBrowser();
      $useProxy(Network::TOR_HOST . ":" . Network::TOR_PORT, null, null);
      $browser->get($cd["url-login"]);
      $browser->setField($cd["username-field-name"], $cd["username"]);
      $browser->setField($cd["password-field-name"], $cd["password"]);
      $page = $browser->click($cd["login-tag"]);
      
      if (!preg_match($cd["patterns"]["login-ok"], $page)) {
        $this->router->log("error", "can't login on comments definition provider [$commentDefinitionId]");
        return false;
      }
      
      $page = $browser->get($cd["url-search"]);
      $browser->setField($cd["search-field-name"], $phone);
      $page = $browser->click($cd["search-tag"]);
      
      if (!preg_match($cd["patterns"]["search-ok"], $page)) {
        $this->router->log("error", "can't get search results on comments definition provider [$commentDefinitionId]");
        return false;
      }
      
      $searchResultsUrls = [];
      if (preg_match_all($commentDefinition["patterns"]["comment-link"], $page, $matches)) {
        $searchResults = $matches[1];
        foreach ($searchResults as $url) {
          if (preg_match($commentDefinition["patterns"]["comment-link-tail"], $url, $matches)) {
            $searchResultsUrls[] = $matches[1];
          }
        }
      }
      
      # loop through all comment pages urls returned
      foreach ($searchResultsUrls as $url) {
      #foreach ($commentPageUrls as $url) {
  
        $this->router->log("info", "url: [$url]");

        /*
        # TODO: remove this test if domain=$domain works fine...
        # skip result with different domains
        if (parse_url($url)["host"] !== $cd["domain"]) {
          $this->router->log("debug", "skipping url because " . parse_url($url)["host"] . " !== " . $cd["domain"]);
          continue;
        }
        */

        /*
        # transform possible "next" pages to the "first" one
        if (preg_match("/^(.*)\/\d+\/?$/s", $url, $matches)) {
          $url = $matches[1];
        }
        */
  
        next_comments_page:
        /*
        $url = preg_replace("/\/?$/", "", $url); # remove trailing slash
        $url = preg_replace("/\?PHPSESSID=.*    /", "", $url); # remove SESSION ID
        */
        if (isset($this->syncUrls[$url])) { # this url has been visited already, skip it
          $this->router->log("debug", "skipping already visited url [$url] on comments definition provider [$commentDefinitionId]");
          continue;
        }

        $this->syncUrls[$url] = 1; # remember this url, to avoid future possible duplications
  
        $url .= "?nowap"; # on wap version we don't get some data (author? date?)
        #$comment_page = $this->getUrlContents($url);
        #if ($comment_page === FALSE) {
        if (($comment_page = $this->getUrlContents($url)) === FALSE) {
          $this->router->log("error", "can't get url [$url] contents on comments definition provider [$commentDefinitionId]");
          continue;
        }

        # parse topic
        if (preg_match($cd["patterns"]["topic"], $comment_page, $matches)) {
          $topic = $matches[1];
        } else {
          $topic = null;
          $this->router->log("error", "no topic found on url [$url] on comments definition provider [$commentDefinitionId]");
          continue;
        }
  
        # all comments blocks
        if (preg_match_all($cd["patterns"]["block"], $comment_page, $matches)) {
          $comments_text = $matches[1];
        } else {
          $comments_text = null;
          $this->router->log("error", "not any comment found on url [$url] on comments definition provider [$commentDefinitionId]");
          return;
        }
  
        $n = 0;
        $comment_next_page_url = "";
        foreach ($comments_text as $comment_text) { # loop through each comment
          $n++;

          # parse author
          if (preg_match($cd["patterns"]["author"], $comment_text, $matches)) {
            $author = $this->cleanAuthor($matches[1]);
          } else {
            $author = null;
            $this->router->log("error", "no author found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
            continue;
          }
      
          # parse author karma
          if (preg_match($cd["patterns"]["author-karma"], $comment_text, $matches)) {
            $author_karma = $matches[1];
          } else {
            $author_karma = "?";
            $this->router->log("error", "no author karma found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
          }
      
          # parse author posts
          if (preg_match($cd["patterns"]["author-posts"], $comment_text, $matches)) {
            $author_posts = $matches[1];
          } else {
            $author_posts = "?";
            $this->router->log("error", "no author posts found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
          }

          # parse date
          if (preg_match($cd["patterns"]["date"], $comment_text, $matches)) {
            $date = $this->cleanDate($matches[1]);
          } else {
            $date = null;
            $this->router->log("error", "no date found for comment [$n] on url [$url]on comments definition provider [$commentDefinitionId]");
            continue;
          }
      
          # parse content
          if (preg_match($cd["patterns"]["content"], $comment_text, $matches)) {
            $content = $this->cleanContent($matches[1], $commentDefinitionId);
          } else {
            $content = null;
            $this->router->log("error", "no content found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
            continue;
          }
      
          if ($content) { # empty comments are not useful...
            $commentData = [];
            $timestamp = date_to_timestamp($date);
            $timestampNow = time(); // current timestamp, sources usually don't set page last modification date...
            $key = $timestamp . "-" . md5("topic:[$topic], author:[$author], content:[$content]"); # a sortable, univoque index
            $commentData["phoneMd5"] = $phoneMd5;
            $commentData["topic"] = $topic;
            $commentData["date"] = date("Y-m-d H:i:s", $timestamp);
            $commentData["timestamp"] = $timestamp;
            $commentData["timestamp_last_sync"] = $timestampNow;
            $commentData["author"] = $author;
            $commentData["author_karma"] = $author_karma;
            $commentData["author_posts"] = $author_posts;
            $commentData["content"] = $content;
            $commentData["content_rating"] = null; # TODO: handle content valutation...
            $commentData["url"] = $url;
          } else {
            $this->router->log("info", "empty comment found on url [$url] on comments definition provider [$commentDefinitionId]");
            continue;
          }

          # check if comment is new or not ####################################################
          $commentId = null;
          if (($comment = $this->db->getByField("comment", "key", $key))) { # old key
            $this->router->log("debug", "comment by key [$key] is old, updating");
            $commentId = $comment[0]["id"];
            $this->db->set("comment", $commentId, $commentData);
          } else {
            $this->router->log("debug", "comment by key [$key] is new, inserting");
            $commentData["key"] = $key; // set univoque key only when adding person
            $commentData["timestamp_creation"] = $timestampNow; // set current timestamp as creation timestamp
            $commentData["new"] = true; // set new flag to true (TODO: do we need this?)
            $commentId = $this->db->add("comment", $commentData);
            $count++;
          }
        }
  
        # match next comments page link
        preg_match($cd["patterns"]["next-link"], $comment_page, $matches);
        if ($matches) {
          $url = $matches[1];
          goto next_comments_page; # do a supplementary loop with next url
        }
       
      }
    }

    return $count;
  }

/*
  / **
   * Search by phone
   *
   * @param  string $phone
   * @return boolean true: success / false: error
   * /
  public function searchByPhoneOLD($phone) {
    $this->router->log("debug", "searchByPhone($phone) [$phone]");
    $changed = false;
    $count = 0;
    foreach ($this->commentsDefinitions as $commentDefinitionId => $commentDefinition) {
      setlocale(LC_ALL, $commentDefinition["locale"]);
      date_default_timezone_set($commentDefinition["timezone"]);
  
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
        $url = preg_replace("/\?PHPSESSID=.*     /", "", $url); # remove SESSION ID
        if (isset($this->syncUrls[$url])) { # this url has been visited already, skip it
          $this->router->log("debug", "skipping already visited url [$url]");
          continue;
        }

        $this->syncUrls[$url] = 1; # remember this url, to avoid future possible duplications
  
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
          $this->router->log("error", "no topic found on url [$url]...");
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
      
          # parse author karma
          if (preg_match($commentDefinition["patterns"]["author-karma"], $comment_text, $matches)) {
            $author_karma = $matches[1];
          } else {
            $author_karma = "?";
            $this->router->log("error", "no author karma found for comment [$n] on url [$url]...");
          }
      
          # parse author posts
          if (preg_match($commentDefinition["patterns"]["author-posts"], $comment_text, $matches)) {
            $author_posts = $matches[1];
          } else {
            $author_posts = "?";
            $this->router->log("error", "no author posts found for comment [$n] on url [$url]...");
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
            $timestamp = date_to_timestamp($date);
            $id = $timestamp . "-" . md5("topic:[$topic], author:[$author], content:[$content]"); # a sortable, univoque index
            $comment["phoneMd5"] = $phoneMd5;
            $comment["topic"] = $topic;
            $comment["date"] = date("Y-m-d H:i:s", $timestamp);
            $comment["timestamp"] = $timestamp;
            $comment["author"] = $author;
            $comment["author_karma"] = $author_karma;
            $comment["author_posts"] = $author_posts;
            $comment["content"] = $content;
            $comment["content_rating"] = 0; # TODO: handle content valutation...
            $comment["url"] = $url;
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
*/

  public function getAll() {
    return $this->db->getAll("comment");
  }

  public function get($id) {
    if (!$id) {
      throw new Exception("can't get comment: no id specified");
    }
    return $this->db->get("comment", $id);
  }
  
  public function getByPhone($phone) {
    if (!$phone) {
$this->router->log("debug", "getByPhone() - no phone!");
      //throw new Exception("can't get comments by phone: no phone specified");
      return [];
    }
$this->router->log("debug", "getByPhone() - phone: [$phone]");
    return $this->db->getByField("comment", "phone", $phone);
  }
  
  public function countByPerson($personId) {
    if (!$personId) {
      throw new Exception("can't get comments count by person: no person id specified");
    }
    return $this->db->countByField("comment", "id_person", $personId);
  }

/*
  public function getAverageValutationByPerson($personId) {
    if (!$personId) {
      throw new Exception("can't get comments average valutation by person: no person id specified");
    }
    return $this->db->getAverageFieldByPersonId("comment", $personId, "content_rating");
  }
*/

  /**
   * Get comments average rating for a person
   *
   * @param object $personId:   person id
   * @return integer $rating:   comments average rating ([0-9], null if no rating expressed)
   */
  public function getAverageRating($personId) {
    $comments = $this->db->getByField("comment", "id_person", $personId);
    $rating = null;
    $ratingSum = 0;
    $ratingCount = 0;
    foreach ($comments as $comment) {
      $comment_content_rating = $comment["content_rating"];
      if ($comment_content_rating !== null) {
        $ratingSum += $comment_content_rating;
        $ratingCount++;
      }
    }
    return ($ratingCount > 0) ? $ratingSum / $ratingCount : null;
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

    # strip quotes (TODO!!!)
    if (preg_match($this->commentsDefinitions[$definitionId]["patterns"]["quote-signature"], $content, $matches)) {
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
      #$this->router->log("debug", "googleSearch - googleSearchLastTimestamp: " . $this->googleSearchLastTimestamp);
      $minDelay = rand($this->googleSearchMinDelayRange[0], $this->googleSearchMinDelayRange[1]);
      $this->router->log("debug", "googleSearch - waiting for $minDelay seconds since last query");
      #$this->router->log("debug", "googleSearch - waiting until " . ($this->googleSearchLastTimestamp + $minDelay));
      #$this->router->log("debug", "googleSearch - now is " . time());
      if (time() < $this->googleSearchLastTimestamp + $minDelay) { 
        time_sleep_until($this->googleSearchLastTimestamp + $minDelay);
      }
      $this->router->log("debug", "googleSearch - finished waiting");
    }

    # obtain the first html page with the formatted url   
    retry:
    $data = $this->getUrlContents(
      "https://www.google.com/search" .
      "?num=" . $max_results .
      "&filter=" . "0" .
      "&as_sitesearch=" . $domain .
      "&q=" . $query_encoded,
      random_user_agent()
    );
    if ($data === FALSE) { // should not happen...
      $this->router->log("error", "can't fetch data from Google");
      throw new Exception("can't fetch data from Google");
    }
    $this->googleSearchLastTimestamp = time(); // remember timestamp of last Google search
    if (preg_match("/Our systems have detected unusual traffic from your computer network/", $data)) {
      $this->router->log("error", "can't fetch data from Google (unusual traffic)" . " !!!!!!!!!!!!!!!!!!!!!!!!!!!!!"); # TODO: remove !'s
      $this->router->log("debug", "googleSearch - waiting for " + $this->googleSearchDelayAfterUnusualTraffic + " seconds before next query !!!!!!!!!!!!!");
      sleep($this->googleSearchDelayAfterUnusualTraffic);
      $this->router->log("debug", "googleSearch - finished waiting for");
      goto retry;
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
      $link = preg_replace("/^https?:\/\/www.google\..*?\/url\?url=/", "", $link);
      $link = preg_replace("/^\/url\?q=/", "", $link);
      $link = preg_replace("/&amp;sa=U.*/", "", $link);
      $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
      $this->router->log("debug", "link found from Google results page: " . $link);
      $result[] = $link;
    }
     
    # clean up the memory 
    $html->clear();

    return $result;
  }

  private function getUrlContents($url, $ua = "Mozilla") {
    $retry = 0;
    $retryMax = 3;
    $this->router->log("debug", "getUrlContents($url, \$ua)");
    retry:
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl, " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      # handle timeouts with some retries
      if ($errno === CURLE_OPERATION_TIMEDOUT) { // timeouts can be probably recovered...
        $retry++;
        $this->router->log("warning", "timeout executing curl to [$url], retry n." . $retry);
        if ($retry <= $retryMax) {
          goto retry;
        }
      }
      $this->router->log("error", "can't execute curl to [$url], " . curl_strerror($errno));
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