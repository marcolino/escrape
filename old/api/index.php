<?php

  $sites = array(
    "sgi" => array(
      "url" => "http://www.sexyguidaitalia.com",
      "path" => "escort/torino",
      "pattern" => array(
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
      "pattern" => array(
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
  );

  include('simple_html_dom.php');



  #print_r(scrape($sites));
  print json_encode(scrape($sites));

  #print_r(get_comments_by_phone("331.9778327"/* Pam */));


  function get_comments_by_phone($phone) {
    $comment_pages_urls = search_with_google_by_md5_phone($phone);
    foreach ($comment_pages_urls as $url) {
      $comment_page = get_url_contents($url . "?wap2");
      #$comment_page = preg_replace("/--- Quote from: .*? End quote ---/s", "", $comment_page);
#print $comment_page;
      if (preg_match_all("/<p class=\"windowbg[\d]?\">(.*?)<\/p>/s", $comment_page, $matches) < 1) {
        error("comment not found...");
        exit;
      }
      $comments = $matches[1];
#print_r($comments);
      foreach ($comments as $comment) {
        #print("Comment: [$comment]\n");
        preg_match("/<strong>(.*?)<\/strong>:\s*(?:<br \/>\s*)*(.*)/s", $comment, $matches);
        $author = $matches[1];
        $text = $matches[2];
        print("Author: [$author]\n");
        print("Text: [$text]\n");
#break;
      }

#break;
    }
  }

  function scrape($sites) {
    $data = array();

    foreach ($sites as $id => $site) {
#if ($id == "sgi") continue;

      $page = get_url_contents($site["url"] . "/" . $site["path"]);
      if ($page === FALSE) {
        error("can't get site $id contents");
        continue;
      }

      if ($site["pattern"]["persons"] != "") {
        if (preg_match($site["pattern"]["persons"], $page, $matches) < 1) {
          error("persons pattern not found on site $id [" . $site["pattern"]["persons"] . "]");
          continue;
        }
        $persons = $matches[1];
      } else $persons = $page;        
  
      if (preg_match_all($site["pattern"]["person"], $persons, $matches) < 1) {
        error("person pattern not found on site $id");
        continue;
      } else $persons = $matches[1];

      $n = 0;
      foreach ($persons as $person) {
        $n++;
        if (preg_match($site["pattern"]["person-details-url"], $person, $matches) < 1) {
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

        if (preg_match($site["pattern"]["person-name"], $page_details, $matches) < 1) {
          error("person $n name not found on site $id");
          continue;
        } else $name = $matches[1];
        $name = preg_replace("/[()]/", "", $name);
        $name = preg_replace("/\s+/", " ", $name);
        $name = preg_replace("/\s+$/", "", $name);
        $name = preg_replace("/^\s+/", "", $name);
        $name = ucfirst($name);

        if (preg_match($site["pattern"]["person-sex"], $page_details, $matches) < 1) {
          error("person $n sex not found on site $id");
          #continue;
        } else $sex = $matches[1];

        if (preg_match($site["pattern"]["person-zone"], $page_details, $matches) < 1) {
          #error("person $n zone not found on site $id");
          #continue;
          $zone = "";
        } else $zone = $matches[1];
        
        if (preg_match($site["pattern"]["person-desc"], $page_details, $matches) < 1) {
          error("person $n description not found on site $id");
          #continue;
        } else $desc = $matches[1];

        if (preg_match($site["pattern"]["person-phone"], $page_details, $matches) < 1) {
          error("person $n phone not found on site $id");
          continue;
        } else $phone = $matches[1];

        #print "site: [$id] <br> name: <a href='$details_url'>[$name]</a> <br> sex: [$sex] <br> zone: [$zone] <br> desc: [$desc] <br> phone: [$phone] <br>\n";
        print "[$id] [$name]\n";

        if (preg_match_all($site["pattern"]["person-photo"], $page_details, $matches) < 1) {
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
        print json_encode($data);
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

/*
  function get_url_timestamp($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FILETIME, true);
    $output = curl_exec($ch);
    if ($output === FALSE) {
      curl_close($ch);
      return FALSE;
    }
    $timestamp = curl_getinfo($ch, CURLINFO_FILETIME);
    if ($timestamp === FALSE) {
      curl_close($ch);
      return FALSE;
    }
    curl_close($ch);
    return $timestamp;
  }
*/
  
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