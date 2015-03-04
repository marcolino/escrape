<?php

  require_once('lib/simple_html_dom.php');
  require_once('classes/services/Utilities.php');

  $query = "aaadcf3c68dbd0d03bb47c915823c16c";
  $gs = new GS();
  for ($i = 0; $i < 500; $i++) {
    $result = $gs->googleSearch($query);
    if ($i == 0) var_dump($result);
    print "$i\n"; ob_flush(); flush();
  }

  class GS {
    private $googleSearchLastTimestamp = 0;
    private $googleSearchMinDelay = 10;

	  public function googleSearch($query) {
      $max_results = 99;
      $result = array();
      $query_encoded = urlencode($query);
      
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
      
      return $result;
    }

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

  }
?>