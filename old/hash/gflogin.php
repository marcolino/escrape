<?php

  $phone = "3319278664";
  searchByPhone("gnoccaforum", $phone);

  function searchByPhone($source, $phone) {
    #require_once('lib/simpletest/browser.php');
    require_once('simpletest/browser.php');
  
    switch ($source) {
      case "gnoccaforum":
      default:
        $urlLogin = "http://gnoccaforum.com/escort/login/";
        $urlSearch = "http://gnoccaforum.com/escort/search/";
        $usernameTag = "user";
        $username = "billidechid";
        $password = "Billi123";
        $passwordTag = "passwrd";
        $loginTag = "Login";
        $searchTag = "Search";   
        $commentDefinition = $this->commentsDefinition[$source];
    
        $browser = &new SimpleBrowser();
        $browser->get($urlLogin);
        $browser->setField($usernameTag, $username);
        $browser->setField($passwordTag, $password);
        $page = $browser->click($loginTag);
      
        if (!preg_match($commentDefinition["patterns"]["login-tag"], $page)) {
          $this->router->log("error", "search comments by phone on source [$source]: could not log in");
          return false;
        }
      
        $page = $browser->get($urlSearch);
        $browser->setField('search', $phone);
        $page = $browser->click($searchTag);
      
        $searchResultsUrls = [];
        if (preg_match_all($commentDefinition["patterns"]["comment-link"], $page, $matches)) {
          $searchResults = $matches[1];
          foreach ($searchResults as $url) {
            if (preg_match($commentDefinition["patterns"]["comment-link-tail"], $url, $matches)) {
              $searchResultsUrls[] = $matches[1];
            }
          }
        }
      
        foreach ($searchResultsUrls as $url) {
          print "url: [$url]\n";
          #$changed = false;
          $count = 0;
          setlocale(LC_ALL, $commentDefinition["locale"]);
          date_default_timezone_set($commentDefinition["timezone"]);
    
          $url .= "?nowap"; # on wap version we don't get some data (author? date?)
          if (($comment_page = $this->getUrlContents($url)) === false) {
            $this->router->log("error", "can't get url [$url] contents on site [$source]");
            continue;
          }
    
          # parse topic
          if (preg_match($commentDefinition["patterns"]["topic"], $comment_page, $matches)) {
            $topic = $matches[1];
          } else {
            $topic = null;
            $this->router->log("error", "no topic found on url [$url]");
            continue;
          }
      
          # all comments blocks
          if (preg_match_all($commentDefinition["patterns"]["block"], $comment_page, $matches)) {
            $comments_text = $matches[1];
          } else {
            #$comments_text = null;
            $this->router->log("error", "not any comment found on url [$url]...");
            continue;
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
              $this->router->log("error", "no author found for comment [$n] on url [$url]");
              continue;
            }
        
            # parse author karma
            if (preg_match($commentDefinition["patterns"]["author-karma"], $comment_text, $matches)) {
              $author_karma = $matches[1];
            } else {
              $author_karma = "?";
              $this->router->log("error", "no author karma found for comment [$n] on url [$url]");
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
              $comment["content_valutation"] = 0; # TODO: handle content valutation...
              $comment["url"] = $url;
              $count++;
            } else {
              #$this->router->log("info", "empty comment found on url [$url]...");
              continue;
            }
    
            ## TODO: ...
            ##if (!array_key_exists($id, $this->db->data["comments"])) { # new comment id
            #  #print_r($comment); print "\n"; #DEBUG
            #  $this->db->data["comments"][$id] = $comment;
            #  $changed = true;
            ##}
    
            #if (...) {
              $this->db->add("comments", $comment);
            #}
          }
        }
    }

    #if ($changed) {
    #  $this->db->store("comments", $comments);
    #}

    return $count;
  }

?>