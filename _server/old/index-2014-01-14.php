<?php

  $sites = array(
    "persons" => array(
      "sgi" => array(
        "url" => "http://www.sexyguidaitalia.com",
        "path" => "escort/torino",
        "patterns" => array(
          "persons" => "/<table .*?id=\"ctl00_content_tableFoto\".*?>(.*?)<\/table>/s",
          "person" => "/<DIV class=\"(?:top|thumbsUp)\".*?>(.*?)<\/DIV>/s",
          "person-details-url" => "/<div .*?class=\"wraptocenter\">.*?<a href=\"(.*?)\".*?>.*?<\/div>/s",
          "person-img-url" => "/<div .*?class=\"wraptocenter\">.*?<img .*?src=\"(.*?)\".*?\/>.*?<\/div>/s",
          "person-name" => "/<td id=\"ctl00_content_CellaNome\".*?><span.*?>(.*?)<\/span>.*?<\/td>/s",
          "person-sex" => "/<td id=\"ctl00_content_CellaSesso\".*?>(.*?)<\/td>/s",
          "person-zone" => "/<td id=\"ctl00_content_CellaZona\".*?>(.*?)<\/td>/s",
          "person-desc" => "/<td id=\"ctl00_content_CellaDescrizione\".*?>(.*?)(?:\s+)?(?:<br \/>.*?)?<\/td>/s",
          "person-phone" => "/<td id=\"ctl00_content_CellaTelefono\".*?><span.*?>(.*?)<\/span>.*?<\/td>/s",
          "person-photo" => "/<a rel='group' class='fancybox' href=(.*?)>.*?<\/a>/",
        ),
      ),
      "toe" => array(
        "url" => "http://www.torinoerotica.com",
        "path" => "annunci_Escort_singole_Piemonte_Torino.html",
        "patterns" => array(
          "persons" => "",
          "person" => "/<!-- Inizio Anteprima Escort -->(.*?)<!-- Fine Anteprima Escort -->/s",
          "person-details-url" => "/<a href=\"(.*?)\".*?>.*?<\/a>/s",
          "person-img-url" => "",
          "person-name" => "/<h\d class=\"nome\">\s*(.*?)\s*<\/h\d>/s",
          "person-sex" => "/<h\d class=\"sesso\">\s*(.*?)\s*&nbsp;.*?<\/h\d>/s",
          "person-zone" => "/(?:<a href=\"#quartiere\".*?>(.*?)<\/a>)/s",
          "person-desc" => "/<meta name=\"description\".*?content=\"(.*?)\".*?\/>/s",
          "person-phone" => "/<h\d class=\"phone\">\s*(?:<img.*? \/>)\s*(.*?)\s*<\/h\d>/s",
          "person-photo" => "/<a\s+style=\"cursor:pointer;\"\s+href=\"(.*?)\" rel=\"prettyPhoto\[galleria\]\".*?<\/a>/s",
        ),
      ),
    ),
    "comments" => array(
      "gnf" => array(
        "patterns" => array(
          "quotes" => "/
(?(DEFINE)
  (?<cut>
    ---\ Quote\ from:
    (?&content)*?
    ---\ End\ quote\ ---
  )

  (?<content>
    (?: (?!(?:---\ Quote\ from:|---\ End\ quote\ ---)) . )++
    | (?&cut)
  )
)

(?&cut)
/smx",
        ),
        "patterns-wap2" => array(
          "comment-block" => "/<p class=\"windowbg[\d]?\">(.*?)<\/p>/s",
          "next-comment-link" => "/<a href=\"(.*?)\".*?>Pagina successiva<\/a>/s",
          "comment" => "/<strong>(.*?)<\/strong>:\s*(?:<br \/>\s*)*(.*)/s",
        ),
      ),
    ),
  );

  include('simple_html_dom.php');

  #DEBUG#print_r(scrape($sites));
  get_comments_by_phone($sites, "331.9778327"/* Pam */);



  function get_comments_by_phone($sites, $phone) {
    $data = array();
    $urls = array();
#$nex = 0; #DEBUG

    # google search pages with phone md5
    $d = 1; #DEBUG
    #DEBUG#$comment_pages_urls = search_with_google_by_md5_phone($phone);
    #DEBUG#$comment_pages_urls = array("http://url-1/", "http://url-2/"); #DEBUG
    $comment_pages_urls = array("http://localhost/escrape/server/pi.html", "http://localhost/escrape/server/bs.html"); #DEBUG
$comment_pages_urls = array("http://localhost/escrape/server/bs.html"); #DEBUG

    # loop through all comment pages urls returned from google search
    foreach ($comment_pages_urls as $url) {
      # transform possible "next" pages to the "first" one
      if (preg_match("/^(.*)\/\d+\/?$/s", $url, $matches)) {
        $url = $matches[1];
      }

      next_comments_page:
print "reading from comments page url [$url] ...\n"; #DEBUG
      $urls[$url] = 1; # record this url, to avoid future possible duplications

      #DEBUG#$comment_page = file_get_contents("comments.html"); #DEBUG
      $comment_page = get_url_contents($url . "?nowap");
      # TODO: use "?nowap", to get dates, too... :-(

      # match next comments page link
      preg_match($sites["comments"]["gnf"]["patterns"]["next-comment-link"], $comment_page, $matches);
      if ($matches) {
        $comment_next_page_url = $matches[1];
        print("Next comment url registered: $comment_next_page_url\n"); #DEBUG
      }

#print("Comment page: "); print_r($comment_page); exit;

/*
      # strip quotes from comments
$sites["comments"]["gnf"]["patterns"]["quotes"] = "/<div class=\"quoteheader\"><div class=\"topslice_quote\">.*?<\/div><\/div><blockquote.*?>.*?<\/blockquote><div class=\"quotefooter\"><div class=\"botslice_quote\"><\/div><\/div>/s";
$comment_page_without_quotes = preg_replace($sites["comments"]["gnf"]["patterns"]["quotes"], "", $comment_page);      $comment_page_without_quotes = preg_replace($sites["comments"]["gnf"]["patterns"]["quotes"], '', $comment_page);
print_r($comment_page_without_quotes); exit; #DEBUG
*/
$comment_page_without_quotes = $comment_page;

$sites["comments"]["gnf"]["patterns"]["comment-block"] = "/<table\s+width=\"100%\"\s+cellpadding=\"5\"\s+cellspacing=\"0\"\s+border=\"1\"\s+border-color=\"#cccccc\"\s*>\s+(?:<tbody>)?<tr>(.*?)<\/tr>\s+(?:<\/tbody>)?<\/table>/s";
      if (preg_match_all($sites["comments"]["gnf"]["patterns"]["comment-block"], $comment_page_without_quotes, $matches) < 1) {
        error("not any comment found on url [$url]...");
        continue;
      }
      $comments = $matches[1];
#print("Comments: "); print_r($comments); exit; continue;

      $comment_next_page_url = "";
$c = 1;
      foreach ($comments as $comment) {
print("Comment: [" . $c++ ."]\n");
        # match comment
$sites["comments"]["gnf"]["patterns"]["comment"] = "/<span class=\"smalltext\">\s*(.*?)<br.*?>.*?<span class=\"smalltext\">«\s*<b>\s*on:<\/b>\s*(.*?)\s*»<\/span>.*?<div class=\"post\">(.*?)<\/div>/s";

$sites["comments"]["gnf"]["patterns"]["comment-author"] = "/<span class=\"smalltext\">\s*(.*?)<br.*?>/s";
        preg_match($sites["comments"]["gnf"]["patterns"]["comment-author"], $comment, $matches);
#print_r($matches);
        if ($matches) {
          $author = $matches[1];
          #$author = preg_replace("/^\s*(.*?)\s*$/s", "$1", $author); # (TODO: should be done by previos regex...) strip leading and trailing spaces
        }

$sites["comments"]["gnf"]["patterns"]["comment-date"] = "/<span class=\"smalltext\">«\s*<b>.*on:<\/b>\s*(.*?)\s*»<\/span>/s";
        preg_match($sites["comments"]["gnf"]["patterns"]["comment-date"], $comment, $matches);
#print_r($matches);
        if ($matches) {
          $date = $matches[1];
          $date = preg_replace("/ſ/s", "S", $date); # correct quirks in date...
          $timestamp = $date; #$timestamp = date2timestamp($date); # TODO... 2014 Settembre 08, 12:42:31 pm
        }

$sites["comments"]["gnf"]["patterns"]["comment-content"] = "/<div class=\"post\">(.*?)<\/div>/s";
        preg_match($sites["comments"]["gnf"]["patterns"]["comment-content"], $comment, $matches);
#print_r($matches);
        if ($matches) {
          $content = $matches[1];
        }

$sites["comments"]["gnf"]["patterns"]["quote-signature"] = "/^\s*<div class=\"quoteheader\">/s";
        if (preg_match($sites["comments"]["gnf"]["patterns"]["quote-signature"], $content)) {
#print "\nSKIPPING QUOTE COMMENT... *************\n";
          continue; # skip quotes...
        }
/*
        if ($matches) {
          #$url = $url;
          $author = $matches[1];
          $author = preg_replace("/^\s*(.*?)\s*$/s", "$1", $author); # strip leading and trailing spaces
          $date = $matches[2];
          $timestamp = $date; #$timestamp = YMDHMS2timestamp($date); # TODO...
          $content = $matches[3];
          $content = preg_replace("/^\s*(.*?)\s*$/s", "$1", $content); # strip leading and trailing spaces
          $key = $date . "-" . md5("author:[$author], content:[$content]"); # a sortable, univoque index

          $data[$key]["author"] = $author;
          $data[$key]["date"] = $date; # TODO: use only timestamp?
          #$data[$key]["timestamp"] = $timestamp;
          $data[$key]["content"] = $content;
          $data[$key]["url"] = $url; # TODO: do we need this?
        }
#print_r($data); #DEBUG
        # a comment with no author is not a comment...
        }
*/
        #if (check mandatory fields are present...) {
          $key = $date . "-" . md5("author:[$author], content:[$content]"); # a sortable, univoque index
          $data[$key]["author"] = $author;
          $data[$key]["date"] = $date; # TODO: use only timestamp?
          ##$data[$key]["timestamp"] = $timestamp;
          $data[$key]["content"] = $content;
          $data[$key]["url"] = $url; # TODO: do we need this?
        #}
      }
print_r($data); #DEBUG
/*
      $url = $comment_next_page_url;
      if ($url && !isset($urls[$url])) { # next comments page link was found and not visited already
        goto next_comments_page; # do a supplementary loop with next url...
      }
*/
    }
    return $data;
  }

  function scrape($sites) {
    $data = array();

    foreach ($sites["persons"] as $id => $site) {
#if ($id == "sgi") continue;

      $page = get_url_contents($site["url"] . "/" . $site["path"]);
      if ($page === FALSE) {
        error("can't get site $id contents");
        continue;
      }

      if ($site["patterns"]["persons"] != "") {
        if (preg_match($site["patterns"]["persons"], $page, $matches) < 1) {
          error("persons pattern not found on site $id [" . $site["patterns"]["persons"] . "]");
          continue;
        }
        $persons = $matches[1];
      } else $persons = $page;        
  
      if (preg_match_all($site["patterns"]["person"], $persons, $matches) < 1) {
        error("person pattern not found on site $id");
        continue;
      } else $persons = $matches[1];

      $n = 0;
      foreach ($persons as $person) {
        $n++;
        if (preg_match($site["patterns"]["person-details-url"], $person, $matches) < 1) {
          error("person $n details url not found on site $id");
          continue;
        } else {
          $details_url = $matches[1];
          $details_url = $site["url"] . "/" . $details_url;
        }

/*
        # check timestamp, to decide if page changed 
        $timestamp = get_url_timestamp($details_url);
        if ($timestamp === FALSE) {
          error("can't get site $id person $n timestamp");
          #continue;
        }
        if (isset($data[$details_url]) && ($timestamp <= $data[$details_url][$timestamp])) {
          info("site $id person $n timestamp lower than last one: skip this person page");
          continue;
        } else {
          info("site $id person $n timestamp greater than last one: get this person page");
        }
*/

        $page_details = get_url_contents($details_url);
        if ($page_details === FALSE) {
          error("can't get site $id person $n url contents");
          continue;
        }
        $page_sum = md5($page_details);

        if (preg_match($site["patterns"]["person-name"], $page_details, $matches) < 1) {
          error("person $n name not found on site $id");
          continue;
        } else $name = $matches[1];
        $name = preg_replace("/[()]/", "", $name);
        $name = preg_replace("/\s+/", " ", $name);
        $name = preg_replace("/\s+$/", "", $name);
        $name = preg_replace("/^\s+/", "", $name);
        $name = ucfirst($name);

        if (preg_match($site["patterns"]["person-sex"], $page_details, $matches) < 1) {
          error("person $n sex not found on site $id");
          #continue;
        } else $sex = $matches[1];

        if (preg_match($site["patterns"]["person-zone"], $page_details, $matches) < 1) {
          #error("person $n zone not found on site $id");
          #continue;
          $zone = "";
        } else $zone = $matches[1];
        
        if (preg_match($site["patterns"]["person-desc"], $page_details, $matches) < 1) {
          error("person $n description not found on site $id");
          #continue;
        } else $desc = $matches[1];

        if (preg_match($site["patterns"]["person-phone"], $page_details, $matches) < 1) {
          error("person $n phone not found on site $id");
          continue;
        } else $phone = $matches[1];

        #print "site: [$id] <br> name: <a href='$details_url'>[$name]</a> <br> sex: [$sex] <br> zone: [$zone] <br> desc: [$desc] <br> phone: [$phone] <br>\n";
        print "[$id] [$name]\n";

        if (preg_match_all($site["patterns"]["person-photo"], $page_details, $matches) < 1) {
          error("photo pattern not found on site $id");
          continue;
        } else $photos = $matches[1];

        $key = $id . "-" . preg_replace("/[^\d]/", "", $phone);

        $data[$key]["name"] = $name;
        $data[$key]["url"] = $details_url;
        #$data[$key]["timestamp"] = $timestamp;
        $data[$key]["sex"] = $sex;
        $data[$key]["zone"] = $zone;
        $data[$key]["desc"] = $desc;
        $data[$key]["phone"] = $phone;
        $data[$key]["phone"] = $phone;
        $data[$key]["page_sum"] = $page_sum;
        $data[$key]["photos"] = array();
        foreach ($photos as $photo) {
          $photo_url = $site["url"] . "/" . $photo;
          array_push($data[$key]["photos"], $photo_url);
        }
      }
    }
    return $data;
  }

  function search_with_google_by_md5_phone($phone) {
    $phone = preg_replace("/[^\d]*/", "", $phone);
    $phone_md5 = md5($phone);
    $result = search_with_google($phone_md5);
    return $result;
  }
  
  function search_with_google($query) {
    $max_results = 99;

    $result = array();
  
    $q = urlencode($query);
  
    # obtain the first html page with the formatted url
    $data = get_url_contents("https://www.google.com/search?num=" . $max_results . "&" . "q=" . $q);
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
      $result[] = $link;
    }
     
    # clean up the memory 
    $html->clear();
  
    return $result;
  }

  function get_url_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, "http://localhost/escrape");
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }

  function debug($msg) {
    print "Debug: " . $msg . "\n";
  }

  function info($msg) {
    print "Info: " . $msg . "\n";
  }

  function error($msg) {
    print "Error: " . $msg . "\n";
  }

?>
