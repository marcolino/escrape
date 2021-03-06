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
require_once(__DIR__ . "/../../lib/simpletest/browser.php");

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
    $all = true; # TODO: get this flag as parameter
    $error = false;
    $this->router->log("info", "---------- comments sync ----------");

    $persons = $this->db->getPersons($all ? null : [ "filters" => [ "active" => "yes" ] ]);
    $personsCount = count($persons);
    $timestampStart = time();
    $phones = [];

    $this->syncUrls = []; # array to store already sync'ed urls
    $n = 0;
    foreach ($this->commentsDefinitions as $commentDefinitionId => $cd) {
      setlocale(LC_ALL, $cd["locale"]);
      date_default_timezone_set($cd["timezone"]);
  

# TODO: TEST THIS!!!
#print date_to_timestamp($this->cleanDate('2015 Giugno 12, 14:35:24'));
#print "\n";
#print date_to_timestamp($this->cleanDate('<strong>Today</strong> at 09:39:30'));
#print "\n";
#exit;


      #$browser = new SimpleBrowser();
      #$this->router->log("debug", "Instantiating NEW browser");
      $browser = &new SimpleBrowser();
      $browser->get($cd["url-login"]); # SLOW-OP
      $browser->setField($cd["username-field-name"], $cd["username"]);
      $browser->setField($cd["password-field-name"], $cd["password"]);
      $page = $browser->click($cd["login-tag"]); # SLOW-OP     
      if (!preg_match($cd["patterns"]["login-ok"], $page)) {
        $this->router->log("error", "can't login on comments definition provider [".$cd['domain']."]");
        return false;
      }
      $browser->get($cd["url-search"]); # SLOW-OP
  
      foreach ($persons as $person) {
        $phone = $person["phone"];
        $commentsCount = 0;
        if ($phone and !in_array($phone, $phones)
        ) { // process only non-empty or not-already processed phones
          $phones[] = $phone;
          if (($commentsCount = $this->searchByPhone($browser, $phone, $cd)) === false) {
            $commentsCount = 0;
            $error = true;
          }
        }
        $n++;
        if ($phone) {
          $this->router->log("debug", "[$n / $personsCount ] - person with phone [$phone] has $commentsCount new comments");
        }
      } // foreach persons end

      $this->searchByPhone(null, null, null); // force browser unset for next comment definition
    } // foreach commentsDefinitions end

    $this->router->log("info", "---------- /comments sync ----------");
    return !$error;
  }

  /**
   * Search comments by phone
   *
   * @param  string $phone     the phone number to be searched
   * @param  string $cd        the comment definition
   * @return integer $count:   number of comments found
   *         boolean false:    error
   */
  public function searchByPhone($browser, $phone, $cd) {
    if ($phone === null) { // unset browser, to force new log-in
      #$this->router->log("debug", "Cleaning browser");
      unset($browser);
      return true;
    }

    #$this->router->log("debug", "searchByPhone($phone) [$phone]");
    $browserCloned = clone $browser; // clone browser to keep this (login+search) status

    #$this->router->log("debug", "setting new search field on old browser");
    $browserCloned->setField($cd["search-field-name"], $phone);
    #$this->router->log("debug", "browser click START");
    $page = $browserCloned->click($cd["search-tag"]); # SLOW-OP
    if (!preg_match($cd["patterns"]["search-ok"], $page)) {
      $this->router->log("error", "can't get search results for phone [$phone] on comments definition provider [".$cd['domain']."]");
      return false;
    }
    #$this->router->log("debug", "browser click END");
    $searchResultsUrls = [];
    if (preg_match_all($cd["patterns"]["comment-link"], $page, $matches)) {
      $searchResults = $matches[1];
      foreach ($searchResults as $url) {
        if (preg_match($cd["patterns"]["comment-link-tail"], $url, $matches)) {
          $searchResultsUrls[] = $matches[1];
        }
      }
    }
    $searchResultsCount = count($searchResultsUrls);
    if ($searchResultsCount > 0) {
      #$this->router->log("debug", "searchResultsCount: [$searchResultsCount]");
    }

    // loop through all comment pages urls returned
    $count = 0;
    foreach ($searchResultsUrls as $url) { # TODO: what happens when $url is changed inside foreach loop???
  
      next_comments_page:
      if (isset($this->syncUrls[$url])) { # this url has been visited already, skip it
        #$this->router->log("debug", "skipping already visited url [$url] on comments definition provider [".$cd['domain']."]");
        continue;
      }
      $this->syncUrls[$url] = 1; // remember this url, to avoid future possible duplications
  
      if (!ends_with($url, "?nowap")) $url .= "?nowap"; # // wap version we don't get some data (author? date?)

      if (($comment_page = $this->network->getUrlContents($url)) === FALSE) {
        $this->router->log("warning", "can't get url [$url] contents on comments definition provider [".$cd['domain']."]");
        continue;
      }

      // parse topic
      if (preg_match($cd["patterns"]["topic"], $comment_page, $matches)) {
        $topic = $matches[1];
      } else {
        $topic = null;
        $this->router->log("warning", "no topic found on url [$url] on comments definition provider [".$cd['domain']."]");
        continue;
      }
  
      // parse section
      if (preg_match($cd["patterns"]["section"], $comment_page, $matches)) {
        $section = $matches[1];
      } else {
        $section = null;
        $this->router->log("warning", "no section found on url [$url] on comments definition provider [".$cd['domain']."]");
        continue;
      }

      // all comments blocks
      if (preg_match_all($cd["patterns"]["block"], $comment_page, $matches)) {
        $comments_text = $matches[1];
      } else {
        $comments_text = null;
        $this->router->log("error", "not any comment found on url [$url] on comments definition provider [".$cd['domain']."]");
        return 0;
      }
  
      $comment_next_page_url = "";
      $n = 0;
      foreach ($comments_text as $comment_text) { # loop through each comment
        // parse author nick
        if (preg_match($cd["patterns"]["author-nick"], $comment_text, $matches)) {
          $author_nick = $this->cleanAuthor($matches[1]);
        } else {
          $author_nick = null;
          $this->router->log("error", "no author nick found for comment [$n] on url [$url] on comments definition provider [".$cd['domain']."]");
          continue;
        }
      
        // parse author karma
        if (preg_match($cd["patterns"]["author-karma"], $comment_text, $matches)) {
          $author_karma = $matches[1];
        } else {
          $author_karma = "?";
          #$this->router->log("info", "no author karma found for comment [$n] on url [$url] on comments definition provider [".$cd['domain']."]");
        }
      
        // parse author posts
        if (preg_match($cd["patterns"]["author-posts"], $comment_text, $matches)) {
          $author_posts = $matches[1];
        } else {
          $author_posts = "?";
          #$this->router->log("info", "no author posts found for comment [$n] on url [$url] on comments definition provider [".$cd['domain']."]");
        }

        // parse date
        if (preg_match($cd["patterns"]["date"], $comment_text, $matches)) {
$ORIGINALdate = $matches[1];
          $date = $this->cleanDate($matches[1]);
        } else {
          $date = null;
          $this->router->log("error", "no date found for comment [$n] on url [$url]on comments definition provider [".$cd['domain']."]");
          continue;
        }
      
        // parse content
        if (preg_match($cd["patterns"]["content"], $comment_text, $matches)) {
          $content = $this->cleanContent($matches[1], $cd["patterns"]["quote-signature"]);
        } else {
          $content = null;
          $this->router->log("error", "no content found for comment [$n] on url [$url] on comments definition provider [".$cd['domain']."]");
          continue;
        }
      
        if ($content) { // empty comments are not useful
          $commentMaster = [];
          $timestamp = date_to_timestamp($date);
          $timestampNow = time(); // current timestamp, sources usually don't set page last modification date...
if ($timestamp == "0") {
  $this->router->log("error", "timestamp is [0], ORIGINAL date was [$ORIGINALdate], date was [$date] !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
}
          $key = $timestamp . "-" . md5($topic . $author_nick . $content); # a sortable, univoque index
          $commentMaster["phone"] = $phone;
          $commentMaster["topic"] = $topic;
          $commentMaster["section"] = $section;
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
          continue;
        }
        $n++;

        // check if comment is new or not /////////////////////////////////////////////////////
        $commentId = null;
        if (($comments = $this->db->getCommentsByField("key", $key))) { // old comment
          if ($n <= 1) { // on first old comment, try to skip to last page already scraped
            $commentsInTopic = $this->db->getCommentsByFields([ "phone" => $phone, "topic" => $topic ]);
            $length = count($commentsInTopic);
            if ($length > 0) {
              $lastUrl = $commentsInTopic[$length - 1]["url"];
              if ($lastUrl !== $url) {
                #$this->router->log("debug", "jumping to last comment for phone [$phone] and topic [$topic] we already have: [$url]");
                $url = $lastUrl;
                goto next_comments_page; # directly jump to last url for this topic
              }
            }
          }
        } else { // new comment
          $this->router->log("debug", "inserting new comment with topic [$topic] for phone [$phone], date: [$date]");
          $commentMaster["key"] = $key; // set univoque key only when adding person
          $commentMaster["timestamp_creation"] = $timestampNow; // set current timestamp as creation timestamp
          $commentId = $this->add($commentMaster, $commentDetail, null);
          $count++;
        }
      }
      // match next comments page link
      preg_match($cd["patterns"]["next-link"], $comment_page, $matches);
      if ($matches) {
        $url = $matches[1];
        #$this->router->log("debug", "£££ jumping to NEXT LINK: $url");
        goto next_comments_page; # do a supplementary loop with next url
      }
       
    }
    #$this->router->log("debug", "returning from searchByPhone..............");
    return $count;
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
    #$this->router->log("debug", "CommentsController::set: " . any2string([$id, $commentMaster, $commentDetail, $userId]));
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

  private function cleanContent($content, $quoteSignature) {
    # strip anything outside "post" class div
    $content_html = str_get_html($content);
    $content = "";
    foreach ($content_html->find('div') as $e) {
      if (isset($e->attr["class"]) && $e->attr["class"] == "post") {
        $content = $e->innertext;
      }
    }

    # strip quotes (TODO!!!)
    if (preg_match($quoteSignature, $content, $matches)) {
      $content = $matches[1]; # comment with quotes stripped off
    } else {
      ; # comment without quotes
    }

    # strip leading and trailing blanks
    $content = preg_replace("/^\s*(.*?)\s*$/", "$1", $content);

    return $content;
  }

/*
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
*/

  /**
   * Destructor
   */
  function __destruct() {
  }

}