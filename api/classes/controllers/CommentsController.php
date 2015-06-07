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
    $this->router->log("debug", "comments -> sync()");

    $persons = $this->db->getPersons();
    $timestampStart = time();
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
      #$browser->useProxy(Network::TOR_HOST . ":" . Network::TOR_PORT, null, null);
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
      if (preg_match_all($cd["patterns"]["comment-link"], $page, $matches)) {
        $searchResults = $matches[1];
        foreach ($searchResults as $url) {
          if (preg_match($cd["patterns"]["comment-link-tail"], $url, $matches)) {
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
        if (($comment_page = $this->network->getUrlContents($url)) === FALSE) {
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

          # parse author nick
          if (preg_match($cd["patterns"]["author-nick"], $comment_text, $matches)) {
            $author_nick = $this->cleanAuthor($matches[1]);
          } else {
            $author_nick = null;
            $this->router->log("error", "no author nick found for comment [$n] on url [$url] on comments definition provider [$commentDefinitionId]");
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
            $commentMaster = [];
            $timestamp = date_to_timestamp($date);
            $timestampNow = time(); // current timestamp, sources usually don't set page last modification date...
            $key = $timestamp . "-" . md5("topic:[$topic], author:[$author_nick], content:[$content]"); # a sortable, univoque index
            $commentMaster["phone"] = $phone;
            $commentMaster["topic"] = $topic;
            //$commentMaster["date"] = date("Y-m-d H:i:s", $timestamp);
            $commentMaster["timestamp"] = $timestamp;
            $commentMaster["timestamp_last_sync"] = $timestampNow;
            $commentMaster["author_nick"] = $author_nick;
            $commentMaster["author_karma"] = $author_karma;
            $commentMaster["author_posts"] = $author_posts;
            $commentMaster["content"] = $content;
            $commentMaster["url"] = $url;
            $commentDetail = [];
            $commentDetail["content_rating"] = null;
          } else {
            $this->router->log("info", "empty comment found on url [$url] on comments definition provider [$commentDefinitionId]");
            continue;
          }

          # check if comment is new or not ####################################################
          $commentId = null;
         #if (($comment = $this->db->getByField("comment", "key", $key))) { # old key
          if (($comment = $this->db->getCommentByField("key", $key))) { # old key
            $this->router->log("debug", "comment by key [$key] is old, updating");
            $commentId = $comment[0]["id"];
            $this->set($commentId, $commentMaster, $commentDetail, null);
          } else {
            $this->router->log("debug", "comment by key [$key] is new, inserting");
            $commentMaster["key"] = $key; // set univoque key only when adding person
            $commentMaster["timestamp_creation"] = $timestampNow; // set current timestamp as creation timestamp
            #$commentMaster["new"] = true; // set new flag to true (TODO: do we need this?)
            $commentId = $this->add($commentMaster, $commentDetail, null);
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
    return $this->db->getCommentByField("phone", $phone, $userId);
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