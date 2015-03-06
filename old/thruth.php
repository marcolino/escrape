<?php


ini_set("display_errors", "On");
error_reporting(E_ALL);

require_once('lib/simple_html_dom.php');

$imageUrl = "www.investireoggi.it/attualita/files/2015/01/marcotravaglio.jpg";
print urlencode($imageUrl); exit;
$thruth = new Thruth();
$response = $thruth->photoGoogleSearch($imageUrl);
print "Response for this photo is:\n"; var_dump($response);

class Thruth {

  function __construct() {
    $this->domain = "www.liberoquotidiano.it";
  }

  public function photoGoogleSearch($imageUrl) {    
    $url =
      "http://www.google.com/searchbyimage" .
      "?image_url=" . $imageUrl .
      "&filter=" . "0"
    ;
    #$encoding = "utf-8";

    $response = [];
    $data = $this->getUrlContents($url/*, $encoding*/);
    $html = str_get_html($data); // the whole html
    foreach($html->find("div.srg") as $srg) { // each one of responses (sections both before and after real duplicate links)
      $response = []; // keep only last section responses, with real duplicate links
      foreach($srg->find("li.g") as $g) { // each one of responses (sectioms before and after real duplicate links)
        /*
         * each one of search responses are in a list item with a class name "g"
         * we are seperating each of the elements within, into an array;
         * titles are stored within "<h3 class='r'><a...>text</a></h3>";
         * links are in the href of the anchor contained in the "<h3>...</h3>";
         */
        $rc = $g->find("div.rc", 0);
        $r = $rc->find("h3.r", 0);
        $a = $r->find("a", 0);
        $imgsrc = "";
        foreach ($rc->find("img") as $el) {
          $imgsrc = $el->src;
        }
        $link = $a->href;
        $text = $a->innertext;
        $link = preg_replace("/^\/url\?q=/", "", $link);
        $link = preg_replace("/&amp;sa=U.*/", "", $link);
        $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
        $domain = parse_url($link)['host'];
        if ($domain !== $this->domain) { // consider only images from different domains
          $response[]["link"] = $link;
          $response[]["imgsrc"] = $imgsrc;
          $response[]["text"] = $text;
        }
      }
    }
     
    # clean up the memory 
    $html->clear();

    return $response;
  }

  private function getUrlContents($url/*, $charset*/) {
    $user_agent = "Mozilla/5.0 (Windows NT 6.0; rv:15.0) Gecko/20100101 Firefox/15.0";
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl, " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't execute curl to [$url], " . curl_strerror($errno));
    }
    curl_close($ch);
    #return ($charset == "utf-8") ? $output : iconv($charset, "utf-8", $output);
    return $output;
  }

};

?>