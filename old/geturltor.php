<?php

include_once "lib/simple_html_dom.php";
define("COOKIE_FILE", __DIR__ . "/cookie.txt");
@unlink(COOKIE_FILE); //clear cookies before we start

$url = "http://whatismyip.org";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, "http://127.0.0.1:9050/");
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
$output = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);
print_r($output);
print_r($curl_error);
exit;

$url = "http://dreamatico.com/data_images/kitten/kitten-3.jpg";
$pages = 1;

search($url, $pages);
exit;



/**
 * Get simplehtmldom object from url
 *
 * @param $url
 * @return bool|simple_html_dom
 */
function getSimpleHtmlDom($url) {
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 9,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_HEADER => 0,
    CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36",
    CURLOPT_COOKIEFILE => COOKIE_FILE,
    CURLOPT_COOKIEJAR => COOKIE_FILE
  ));
  $dom = str_get_html(curl_exec($curl));
  curl_close($curl);
  return $dom;
}

/**
 * Get best guess text
 *
 * @param simple_html_dom $dom
 * @return bool
 */
public function getBestGuess(simple_html_dom $dom) {
  foreach ($dom->find('div[class=card-section] div') as $div) {
    if (stripos($div->innertext, 'Best guess for this image') !== false) {
      $a = $div->find('a', 0);
      return array($a->innertext, $this->googleDomain.$a->href);
    }
  }
  return false;
}

/**
 * Get search results from current page
 *
 * @param simple_html_dom $dom
 * @return array
 */
public function getSearchResults(simple_html_dom $dom) {
  $result = array();
  $c = count($dom->find('div.srg')) > 1 ? 1 : 0; // if this is first page, we have 2 divs, first with
                                                 // some irrelevant links, so skip the first page

  $d = $dom->find('div.srg', $c); // get second div(if this is first page), or first div
  foreach ($d->find('div.rc h3.r') as $h3) {
    foreach ($h3->find('a') as $a) { // get links
      $result[] = [ htmlspecialchars_decode($a->plaintext, ENT_QUOTES), $a->href ];
    }
  }
  return $result;
}

/**
 * Get best guess text and loop through pages to get links to images
 *
 * @param $imageUrl
 * @param int $numPages - number of pages to scrape
 * @return [
 *   "best_guess" => string,
 *   "search_results" => [
 *     [ name, url ],
 *     [ name, url ],
 *     ...,
 *   ],
 * ]
 */
public function search($imageUrl, $numPages = 1) {
  try {
    $dom = $this->getSimpleHtmlDom($this->searchUrl.$imageUrl); // get first page dom
    $bestGuess = $this->getBestGuess($dom); // get best guess from first page
    $searchResults = $this->getSearchResults($dom); // get search results from first page
    $nextPageA = $dom->find("#nav a.pn", 0); // check if we have 'next page' link (if we don't - it's the only page)
    $dom->clear();
    for ($i = 1; $i < $numPages && $nextPageA; $i++) { // loop through pages [2 - $numPages]
      $dom = $this->getImageSearchDom($this->searchUrl.$imageUrl . "&start=" . ($i * 10));
      $searchResults = array_merge($searchResults, $this->getSearchResults($dom)); // get search results from page and merge with available results
      $nextPageA = $dom->find("#nav a.pn", 0); // check if we have 'next page' link (if we don't - it's last page)
      $dom->clear();
      sleep(1);
    }
    return array("best_guess" => $bestGuess, "search_results" => $searchResults);
  } catch (Exception $e) {
    echo "Exception for url ', $imageUrl, ": ", $e->getMessage();
    return false;
  }
}

?>