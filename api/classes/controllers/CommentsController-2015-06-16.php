<?php
/**
 * Comments controller
 * 
 * @package CommentsController
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 */

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
    $this->router->log("info", "---------- comments sync ----------");

    $persons = $this->db->getPersons();
    $timestampStart = time();
    $phones = [];
    $nn = 0;
    $tott = count($persons);
    foreach ($persons as $person) {
      $phone = $person["phone"];
      if (!$phone or in_array($phone, $phones)) { // skip empty or already processed phones
        continue;
      }
      $phones[] = $phone;
$commentsCount = $this->searchByPhone($phone, $nn, $tott);
      $nn++;
    }

#    $commentsCount = $this->searchByPhone($phones);

    $this->router->log("info", "---------- /comments sync ----------");
    return true;
  }

#private function microtime_float() {
#  list($usec, $sec) = explode(" ", microtime());
#  return ((float)$usec + (float)$sec);
#}

  /**
   * Search comments by phone
   *
   * @param  string $phone     the phone number to be searched
   * @return integer $count:   number of comments found
   *         boolean false:    error
   */
  public function searchByPhone($phone/*s*/, $nn, $tott) {
    require_once(__DIR__ . "/../../lib/simpletest/browser.php");

    #$this->router->log("debug", "searchByPhone($phone) [$phone]");

$memory_limit = ini_get('memory_limit');
$this->router->log("debug", "sBP {0} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
    $this->syncUrls = []; # array to store already sync'ed urls
    #$tot = count($phones);
    $tot = 1;
    $n = 0;
#$time_start = $this->microtime_float();
    foreach ($this->commentsDefinitions as $commentDefinitionId => $cd) {
#$time_end = $this->microtime_float(); $time = $time_end - $time_start;
#$this->router->log("debug", "sBP {1} [$time]");
#$this->router->log("debug", "sBP {1} [".memory_get_usage()."]");
      setlocale(LC_ALL, $cd["locale"]);
      date_default_timezone_set($cd["timezone"]);
  
      $browser = &new SimpleBrowser();
      $browser->get($cd["url-login"]); # SLOW-OP
      $browser->setField($cd["username-field-name"], $cd["username"]);
      $browser->setField($cd["password-field-name"], $cd["password"]);
      $page = $browser->click($cd["login-tag"]); # SLOW-OP
      
      if (!preg_match($cd["patterns"]["login-ok"], $page)) {
        $this->router->log("error", "can't login on comments definition provider [$commentDefinitionId]");
        return false;
      }
        
      $browserClean = clone $browser;
      #foreach ($phones as $phone) { // loop through all phones
$this->router->log("debug", "sBP {0a} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        unset($browser);
$this->router->log("debug", "sBP {0b} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        $browser = $browserClean;
$this->router->log("debug", "sBP {0c} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        $page = $browser->get($cd["url-search"]); # SLOW-OP
$this->router->log("debug", "sBP {0d} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        $browser->setField($cd["search-field-name"], $phone);
$this->router->log("debug", "sBP {0e} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        $page = $browser->click($cd["search-tag"]); # SLOW-OP
$this->router->log("debug", "sBP {0f} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        if (!preg_match($cd["patterns"]["search-ok"], $page)) {
          $this->router->log("error", "can't get search results on comments definition provider [$commentDefinitionId]");
          return false;
        }
$this->router->log("debug", "sBP {1} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");

        $searchResultsUrls = [];
        if (preg_match_all($cd["patterns"]["comment-link"], $page, $matches)) {
          $searchResults = $matches[1];
          foreach ($searchResults as $url) {
            if (preg_match($cd["patterns"]["comment-link-tail"], $url, $matches)) {
              $searchResultsUrls[] = $matches[1];
            }
          }
        }       
#$this->router->log("debug", "sBP {2} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");

        // loop through all comment pages urls returned
        $count = 0;
        foreach ($searchResultsUrls as $url) {
    
          #$this->router->log("info", "url: [$url]");
  
          next_comments_page:
          if (isset($this->syncUrls[$url])) { # this url has been visited already, skip it
            #$this->router->log("debug", "skipping already visited url [$url] on comments definition provider [$commentDefinitionId]");
            continue;
          }
  
          $this->syncUrls[$url] = 1; // remember this url, to avoid future possible duplications
    
          if (!ends_with($url, "?nowap")) $url .= "?nowap"; # // wap version we don't get some data (author? date?)
  
#$this->router->log("debug", "sBP {3} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
          if (($comment_page = $this->network->getUrlContents($url)) === FALSE) {
            $this->router->log("error", "can't get url [$url] contents on comments definition provider [$commentDefinitionId]");
            continue;
          }
#$this->router->log("debug", "sBP {4} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
  
          // parse topic
          if (preg_match($cd["patterns"]["topic"], $comment_page, $matches)) {
            $topic = $matches[1];
          } else {
            $topic = null;
            $this->router->log("error", "no topic found on url [$url] on comments definition provider [$commentDefinitionId]");
            continue;
          }
    
          // all comments blocks
          if (preg_match_all($cd["patterns"]["block"], $comment_page, $matches)) {
            $comments_text = $matches[1];
          } else {
            $comments_text = null;
            $this->router->log("error", "not any comment found on url [$url] on comments definition provider [$commentDefinitionId]");
            return;
          }
    
          $comment_next_page_url = "";
          foreach ($comments_text as $comment_text) { # loop through each comment
#$this->router->log("debug", "sBP {5} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");

            // parse author nick
            if (preg_match($cd["patterns"]["author-nick"], $comment_text, $matches)) {
              $author_nick = $this->cleanAuthor($matches[1]);
            } else {
              $author_nick = null;
              $this->router->log("error", "no author nick found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
              continue;
            }
        
            // parse author karma
            if (preg_match($cd["patterns"]["author-karma"], $comment_text, $matches)) {
              $author_karma = $matches[1];
            } else {
              $author_karma = "?";
              #$this->router->log("warning", "no author karma found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
            }
        
            // parse author posts
            if (preg_match($cd["patterns"]["author-posts"], $comment_text, $matches)) {
              $author_posts = $matches[1];
            } else {
              $author_posts = "?";
              #$this->router->log("warning", "no author posts found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
            }
  
            // parse date
            if (preg_match($cd["patterns"]["date"], $comment_text, $matches)) {
              $date = $this->cleanDate($matches[1]);
            } else {
              $date = null;
              $this->router->log("error", "no date found for comment [$n] on url [$url]on comments definition provider [$commentDefinitionId]");
              continue;
            }
        
            // parse content
            if (preg_match($cd["patterns"]["content"], $comment_text, $matches)) {
              $content = $this->cleanContent($matches[1], $commentDefinitionId);
            } else {
              $content = null;
              $this->router->log("error", "no content found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
              continue;
            }
        
#$this->router->log("debug", "sBP {6} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
            if ($content) { // empty comments are not useful
              $commentMaster = [];
              $timestamp = date_to_timestamp($date);
              $timestampNow = time(); // current timestamp, sources usually don't set page last modification date...
              $key = $timestamp . "-" . md5($topic . $author_nick . $content); # a sortable, univoque index
              $commentMaster["phone"] = $phone;
              $commentMaster["topic"] = $topic;
              $commentMaster["timestamp"] = $timestamp;
              $commentMaster["timestamp_last_sync"] = $timestampNow;
              $commentMaster["author_nick"] = $author_nick;
              $commentMaster["author_karma"] = $author_karma;
              $commentMaster["author_posts"] = $author_posts;
              $commentMaster["content"] = $content;
              $commentMaster["url"] = $url;
              $commentDetail = [];
              $commentDetail["content_rating"] = null;
            } else { // empty comment
              #$this->router->log("info", "empty comment found on url [$url] on comments definition provider [$commentDefinitionId]");
              continue;
            }
#$this->router->log("debug", "sBP {7} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
  
            // check if comment is new or not /////////////////////////////////////////////////////
            $commentId = null;
            if (($comments = $this->db->getCommentsByField("key", $key))) { // old comment
              #$this->router->log("debug", "£ comment by key [$key] is old, not saving to database");
              if ($n <= 1) { // on first old comment, try to skip to last page already scraped
                #$this->router->log("debug", "££ on first old comment, trying to skip to last page already scraped");
                $commentsInTopic = $this->db->getCommentsByFields([ "phone" => $phone, "topic" => $topic ]);
                $length = count($commentsInTopic);
                #$this->router->log("debug", "£££ [$length] comments found for phone [$phone] and topic [$topic]");
                /*
                $urlToSkipTo = "";
                for ($i = 0; $i < $length; $i++) { // scan comments with current topic
                  $comment = $commentsInTopic[$i];
                  # TODO: don't use "!==", but a function to match thread part of url...
                  if ($commentsInTopic[$i]["url"] !== $url) { // if url is from a distinct thread (same topic for distinct threads), skip this comment
                    continue;
                  }
                  $urlToSkipTo = $commentsInTopic[$i]["url"];
                }
                if ($urlToSkipTo) {
                  $this->router->log("debug", "££££ FOUND URL TO SKIP TO: [$urlToSkipTo] !!!");
                  $url = $urlToSkipTo;
                  goto next_comments_page; # directly jump to found url
                }
                */
                if ($length > 0) {
                  $url = $commentsInTopic[$length - 1]["url"];
                  #$this->router->log("debug", "££££ jumping to last comment for phone [$phone] and topic [$topic] we already have: [$url]");
                  goto next_comments_page; # directly jump to last url for this topic
                }
              }
#$this->router->log("debug", "sBP {8} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
            } else { // new comment
              $this->router->log("debug", "inserting new comment with topic [$topic] for phone [$phone], date: [$date]");
              $commentMaster["key"] = $key; // set univoque key only when adding person
              $commentMaster["timestamp_creation"] = $timestampNow; // set current timestamp as creation timestamp
              $commentId = $this->add($commentMaster, $commentDetail, null);
              $count++;
            }
          }
#$this->router->log("debug", "sBP {9} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");

          // match next comments page link
          preg_match($cd["patterns"]["next-link"], $comment_page, $matches);
          if ($matches) {
            $url = $matches[1];
            goto next_comments_page; # do a supplementary loop with next url
          }
         
#$this->router->log("debug", "sBP {10} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        }

#$this->router->log("debug", "sBP {11} [MEM: ".(memory_get_usage(true)/1024/1024)." MB, MEMPEAK: ".(memory_get_peak_usage(true)/1024/1024)." MB, MEMMAX: ".$memory_limit." MB]");
        $n++;
        #$this->router->log("debug", "[$n / $tot ] - person with phone [$phone] has $count new comments");
        $this->router->log("debug", "[$nn / $tott ] - person with phone [$phone] has $count new comments");
      #}
    }
    $this->router->log("debug", "returning from searchByPhone..............");
  }

  public function getAll($userId = null) {
    return $this->db->getComments($userId);
  }

  public function get($id) {
    if (!$id) {
      throw new Exception("can't get comment: no id specified");
    }
    return $this->db->get("comment", $id);
  }
  
  public function getByPhone($phone, $userId = null) {
    if (!$phone) {
#$this->router->log("debug", "getByPhone() - no phone!");
      //throw new Exception("can't get comments by phone: no phone specified");
      return [];
    }
#$this->router->log("debug", "getByPhone() - phone: [$phone]");
    return $this->db->getCommentsByField("phone", $phone, $userId);
  }
  
  public function countByPhone($phone) {
    if (!$phone) {
      return 0;
    }
    return $this->db->countByField("comment", "phone", $phone);
  }

  /**
   * Get comments average rating for a person
   *
   * @param object $personId:   person id
   * @return integer $rating:   comments average rating ([0-9], null if no rating expressed)
   */
  public function getAverageRating($personId) {
    # TODO: eleborate this simple median logic (really?...)
    $comments = $this->db->getByField("comment_detail", "id_person", $personId);
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

  public function add($commentMaster = null, $commentDetail = null, $userId = null) {
    return $this->db->addComment($commentMaster, $commentDetail, $userId = null);
  }

  public function set($id, $commentMaster = null, $commentDetail = null, $userId = null) {
$this->router->log("debug", "CommentsController::set: " . any2string([$id, $commentMaster, $commentDetail, $userId]));
    # TODO: we have a "id_user" field both in $commentDetail and in $userId... Do we nee both of them? Nuuu...
    return $this->db->setComment($id, $commentMaster, $commentDetail, $userId);
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

  /**
   * Destructor
   */
  function __destruct() {
  }

}