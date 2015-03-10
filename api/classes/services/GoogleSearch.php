<?php

require_once __DIR__ . "/../../lib/simple_html_dom.php";

class GoogleSearch {

  private $searchRemoteImage = "https://www.google.com/searchbyimage?image_url=";
  private $searchLocalUrl = "https://www.google.com/searchbyimage/upload";
  private $googleDomain = "https://www.google.com";

  function __construct() {
    $this->charset = "utf-8";
  }

  /**
   * Get simplehtmldom object from url
   *
   * @param  string $url
   * @return bool   simple_html_dom
   */
  public function getUrlDom($url, $post = false) {
    $this->network = new Network();
    $data = $this->network->getUrlContents($url, $this->charset, $post);
    $dom = str_get_html($data);
    return $dom;
  }

  /**
   * Get simple_html_dom class for url and check if there's any redirect
   *
   * @param $url
   * @return bool|simple_html_dom
   * @throws Exception
   */
  public function getRemoteImageSearchDom($url) {
    $dom = $this->getUrlDom($url);
    if (stripos($dom->find("title", 0), "302 Moved") !== false) { // if "302 moved" page, follow link
      $a = $dom->find("a", 0)->href;
      $dom->clear();
      sleep(TIMEOUT_MOVED);
      $dom = $this->getUrlDom($a);
    }
    if (stripos($dom->find("title", 0), "sorry") !== false) { // google thinks we're bot
      throw new Exception("Error: Google thinks we're bot and won't process our requests");
    }
    return $dom;
  }

  /**
   * Upload local image to Google and get result page
   *
   * @param string   $fileName
   * @return simple_html_dom
   *
   * TODO: this doesn't work... :-(
   */
  public function getLocalImageSearchDom($fileName) {
    // get image size for fileName
    list($w, $h) = getimagesize($fileName);

    // get MIME type for fileName
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileName);
    finfo_close($finfo);

    $curlFile = curl_file_create($fileName, $mimeType, basename($fileName));
    $dom = $this->getUrlDom($this->searchLocalUrl, [
      "file" => $curlFile,
      "image_url" => "",
      "image_content" => "",
      "filename" => "",
      "h1" => "en",
      "bih" => $h,
      "biw" => $w
    ]);
    return $dom;
  }

  /**
   * Get best guess text
   *
   * @param simple_html_dom $dom
   * @return bool
   */
  public function getBestGuess(simple_html_dom $dom) {
    foreach ($dom->find("div[class=card-section] div") as $div) {
      if (stripos($div->innertext, "Best guess for this image") !== false) {
        $a = $div->find("a", 0);
        return [ "text" => $a->innertext, "href" => $this->googleDomain.$a->href ];
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
    $result = [];
    $c = count($dom->find("div.srg")) > 1 ? 1 : 0; // if this is first page, we have 2 divs, first with
                                                   // some irrelevant links, so skip the first page
    $d = $dom->find("div.srg", $c); // get second div (if this is first page), or first div
    if (!is_object($d)) {
      return $result; // main results div not found
    }

    # TODO: should merge these loops...

    // get img src from links
    $n = 0;
    foreach ($d->find("div.rc") as $rc) {
      foreach ($rc->find("img") as $img) {
        ++$n;
        $result[$n]["imgsrc"] = $img->src;
      }
    }

    // get text and href from links
    $n = 0;
    foreach ($d->find("div.rc h3.r") as $h3) {
      foreach ($h3->find("a") as $a) { // get links
        ++$n;
        $result[$n]["text"] = htmlspecialchars_decode($a->plaintext, ENT_QUOTES);
        $result[$n]["href"] = $a->href;
      }
    }

    return $result;
  }

  /**
   * Get best guess text and loop through pages to get links to images
   *
   * @param string  $imageUrl
   * @param integer $numPages - number of pages to scrape
   * @return [
   *   "best_guess" => string,
   *   "search_results" => [
   *     [ text, href, imgsrc ],
   *     [ text, href, imgsrc ],
   *     ...,
   *   ]
   * ]
   */
  public function searchImage($imageUrl, $numPages = 1) {
#return [ "best_guess" => "bestGuess", "search_results" => [ [ "text" => "TEXT", "imgsrc" => "https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcQbm5ipONSdcESkgazDxbUhFNWthW5cHzWPvF5Bv2nfRqXZNQvYJwSJ7Q", "href" => "https://www.google.it/url?sa=t&rct=j&q=&esrc=s&source=web&cd=11&cad=rja&uact=8&ved=0CEoQFjAK&url=http%3A%2F%2Fwww.perugiatoday.it%2Fcronaca%2Fstrage-gatti-perugia-orrore.html&ei=Q6j8VPa1I4bnyQPE94GIAQ&usg=AFQjCNHJyixc1C1PiBV1uDNIGUcKeF88Zg&sig2=qrSffEYjNkzlCImtA1W0cA" ], ] ];
    try {
      // get first page dom
      $dom = is_file($imageUrl) ? $this->getLocalImageSearchDom($imageUrl) : $this->getRemoteImageSearchDom($this->searchRemoteImage.$imageUrl);
      $bestGuess = $this->getBestGuess($dom); // get best guess from first page
      $searchResults = $this->getSearchResults($dom); // get search results from first page
      $nextPageA = $dom->find("#nav a.pn", 0); // check if we have "next page" link (if we don't - it's the only page)
      $dom->clear();
      for ($i = 1; $i < $numPages && $nextPageA; $i++) { // loop through pages [2 - $numPages]
        $dom = $this->getRemoteImageSearchDom($this->googleDomain.htmlspecialchars_decode($nextPageA->href));
        $searchResults = array_merge($searchResults, $this->getSearchResults($dom)); // get search results from page and merge with available results
        $nextPageA = $dom->find("#nav a.pn", 0); // check if we have "next page" link (if we don't it's last page)
        $dom->clear();
        sleep(1);
      }
      return [ "best_guess" => $bestGuess, "search_results" => $searchResults ];
    } catch (Exception $e) {
      throw new Exception("error searching image [$imageUrl]: " . $e->getMessage());
    }
  }

}

?>