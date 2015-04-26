<?php

  /**
   * Returns a merged image from a collection of images,
   * arranged as a deck of cards.
   * Result size will be PNG format;
   * it's size will be 20% bigger than the biggest source image;
   * it's background will be transparent.
   *
   * @param  array $imageUrls         source images urls (or filenames)
   * @return null                     some error occurred 
   *         bitmap                   merged image bitbmap
   */
  public function photoGetCardDeck($imagesUrls) {
    $scaleFactor = 120 / 100; // scale factor of output image relative to the biggest of source images

    // Load images from urls (or filenames)
    $count = count($imagesUrls);
    $images = [];
    $widthMax = 0;
    $heightMax = 0;
    for ($n = 0; $n < $count; $n++) {
      $imageUrl = $imagesUrls[$n];
      switch (strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION))) {
        case 'jpeg':
        case 'jpg': // JPEG image
          $image = imagecreatefromjpeg($imageUrl);
          break;
        case 'png': // PNG image
          $image = imagecreatefrompng($imageUrl);
          break;
        case 'gif': // GIF image
          $image = imagecreatefromgif($imageUrl);
          break;
        default: // unforeseen type image, skip it
          contiue;
        break;
      }
      if (!$image) {
        #print "can't transform [$imageUrl] to image\n";
        contiue;
      }
      $images[$n] = [];
      $images[$n]["bitmap"] = $this->imageTransparent($image);
      $images[$n]["width"] = imagesx($image);
      $images[$n]["height"] = imagesy($image);
      $widthMax = max($widthMax, $images[$n]["width"]);
      $heightMax = max($heightMax, $images[$n]["height"]);
    }
    $count = count($images);
    if ($count <= 0) {
      #die("0 good images found\n");
      return false; // no suitable image found
    }

    // create the new image container with the new size
    $height = $heightMax * $scaleFactor;
    $width = $widthMax * $scaleFactor;

    $image = $this->imageTransparent(imagecreatetruecolor($width, $height));

    for ($n = 0; $n < $count; $n++) {
      // calculate the rotation degrees for this image
      $notSoFlatDegrees = 20; // a card deck is never 180 degrees wide, but some degree less...
      $degrees = ($notSoFlatDegrees / 2) + (((180 - $notSoFlatDegrees) / (1 + $count)) * (1 + $n));
      $degrees = - ($degrees - 90);

      # calculate small offsets to better dispose 'cards' in 'deck'
      $hUnit = $height / 36;
      $wUnit = ($width - $images[$n]["width"]) / 2;
      $top = ((1 * $hUnit) * sin(deg2rad($n * (180 / ($count - 1))))) - (1 * $hUnit);
      $sign = sign(cos(deg2rad($n * (180 / ($count - 1)))));
      $left = ($sign ? -$sign : 1) * $wUnit + -((1 * $wUnit) * (cos(deg2rad($n * (180 / ($count - 1)))) * (1)));

      // rotate image
      $imageBigger = $this->imageSurfaceResize($images[$n]["bitmap"], $width, $height);
      $verticalMargin = ($height - $images[$n]["height"]) / 2;
      $horizontalMargin = ($width - $images[$n]["width"]) / 2;
      $colorTransparent = imageColorAllocateAlpha($imageBigger, 0, 0, 0, 127);
      $imageRotated = $this->imageTransparent(imagerotate($imageBigger, $degrees, $colorTransparent));

      // merge the rotated image to the base image
      imagecopyresampled(
        $image, $imageRotated,
        $left, $top, 0, 0, # left, top, right, bottom
        $images[$n]["width"], $images[$n]["height"],
        $width, $height
      );
      imagedestroy($imageRotated);
    }
    // produce the new bitmap
    ob_start();
    if (!imagepng($image, NULL, 0)) { // no compression
      #die("can't create final image\n");
      return false;
    }
    $bitmap = ob_get_contents();
    ob_end_clean();
    imagedestroy($image);
    // return the new bitmap
    return $bitmap;
  }

  /**
   * Search by phone (was in PersonsController.php)
   *
   * @param  string $phone
   * @return boolean true: success / false: error
   */
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
      $this->store("comments");
    }

    return $count;
  }

  /**
   * Search text in a specific domain with Google (was in CommentsController.php)
   *
   * @param  string $query   query to search for
   * @param  string $domain  domain to search into
   * @return array           list of results
   */
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

?>