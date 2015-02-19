<?php
/**
 * Image class
 * 
 * @package Image
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class Image {
  const SIGNATURE_PIXELS_PER_SIDE = 10;
  const SIGNATURE_DUPLICATION_MIN_DISTANCE = 0.4; # TODO: tune-me

  public function __construct($options = []) {
    $this->options = $options;
    if (!isset($this->options["signaturePixelsPerSide"])) {
      $this->options["signaturePixelsPerSide"] = self::SIGNATURE_PIXELS_PER_SIDE;
    }
  }

  /**
  * Create an image from URL
  *
  * @param  string: image URL
  * @return object: new image if url is a valid image
  *                 false     if url is not a valid image
  */
  public function createFromUrl($url) {
    $this->url = $url;
    $this->domain = parse_url($this->url)['host'];
    $this->bitmap = $this->getUrlContents($this->url); # download image
    $this->sum = md5($this->bitmap);
    $this->size = $this->imageSize();
    $this->signature = $this->signatureFromBitmap();
    $this->type = $this->imageType();
    $this->mime = $this->imageMime();
    $this->name = $this->sum; # TODO: how to define "name" ?
    $this->thruthfulness = null; # thruthfulness flag must be set separately
  }

 /**
  * Magic getter
  */
  public function __get($property) {
    if (property_exists($this->image, $property)) {
      return $this->$property;
    }
  }

  /**
   * Returns the hammering distance of two images signatures
   */
  public function compareSignatures($sig1, $sig2) {
    $hammingDistance = 0;
    $pixels = $this->pixelsPerSide * $this->pixelsPerSide;
    for ($a = 0; $a < $pixels; $a++) {
      if (substr($sig1, $a, 1) != substr($sig2, $a, 1)) {
        $hammingDistance++;
      }
    }
    return ($pixels - $hammingDistance) / $pixels; # returned value is in the range 0 -> 1
  }

  public function setImageThruthfulness() {
    if ($this->checkImageThruthfulness($this->url)) {
      $this->thruthfulness = true;
    } else {
      $this->thruthfulness = false;
    }
  }

  public function setShowcase($showcase) {
    $this->showcase = $showcase;
  }

  /**
   * Returns the size of the image url
   *
   * @param  string: image URL
   * @return array: array with image width, image height
   */
  private function imageSize() {
    $info = getimagesize($this->url);   
    return [ $info[0], $info[1] ];
  }

  /**
   * Returns the type of the image, or false if image has not url set, or it is not an image
   */
  private function imageType() {
    $type = exif_imagetype($this->url); # for example: "IMAGETYPE_GIF"
    return $type;
  }  

  /**
   * Returns the MIME type of the image, or false if image has not url set, or it is not an image
   */
  private function imageMime() {
    if ($this->type) {
      $type = $this->type;
    } else {
      $type = $this->imageType();
    }
    $mime = image_type_to_mime_type($type); # for example: "image/gif"
    return $mime;
  }  

  /**
   * Returns the string of bits representing the signature of an image bitmap
   */
  private function signaturefromBitmap() {
    return $this->signature(imagecreatefromstring($this->bitmap), $this->size);
  }

  /**
   * Returns the string of bits representing the signature of an image
   */
  private function signature($img, $size) {
    if (!$img) {
      return false;
    }
    $img = $this->resizeImageForSignature($img, $size);
    imagefilter($img, IMG_FILTER_GRAYSCALE);
    $colorMean = $this->colorMeanValue($img);
    $bits = $this->bits($colorMean);
    return $bits;
  }

  /**
   * Retuns image resource, or false if its not jpg nor png
   */
/*
  private function createImage($imageUrl) {
    $type = $this->mimeType($imageUrl);
    if ($type === "jpg") {
      return imagecreatefromjpeg($imageUrl);
    } else if ($type === "png") {
      return imagecreatefrompng($imageUrl);
    } else {
      return false; 
    }
  }
*/

  /**
   * Resizes the image to a square and returns as image resource (for signature calculation)
   */
  private function resizeImageForSignature($img, $size) {
    $imgResized = imagecreatetruecolor($this->options["signaturePixelsPerSide"], $this->options["signaturePixelsPerSide"]);
    imagecopyresized($imgResized, $img, 0, 0, 0, 0, $this->options["signaturePixelsPerSide"], $this->options["signaturePixelsPerSide"], $size[0], $size[1]);
    return $imgResized;
  }
  
  /**
   * Check for image truthfulness
   *
   * @param  string: photo url
   * @return boolean: true    if photo is not duplicated on the web
   *                  false   if photo is duplicated on the web
   */
  public function checkImageThruthfulness($imageUrl) {
    $domain = parse_url($this->url)['host'];
    $similarUrls = $this->googleSearchImage();
    if (count($similarUrls) > 0) { // same image found
      return false;
    }
    return true;
  }

  /**
   * Returns the mean value of the colors and the list of all pixel's colors
   */
  private function colorMeanValue($i) {
    $colorList = array();
    $colorSum = 0;
    for ($a = 0; $a < $this->options["signaturePixelsPerSide"]; $a++) {
      for ($b = 0; $b < $this->options["signaturePixelsPerSide"]; $b++) {
        $rgb = imagecolorat($i, $a, $b);
        $colorList[] = $rgb & 0xFF;
        $colorSum += $rgb & 0xFF;
      }
    }
    return array($colorSum / ($this->options["signaturePixelsPerSide"] * $this->options["signaturePixelsPerSide"]), $colorList);
  }
  
  /**
   * Returns a string value with 1 and zeros;
   * if a color is bigger than the mean value of colors the corresponding bit is 1
   */
  private function bits($colorMean) {
    $bits = "";
    foreach ($colorMean[1] as $color) {
      $bits .= ($color >= $colorMean[0]) ? "1" : "0";
    }
    return $bits;
  }

  public function googleSearchImage() {
    $maxResults = 99;
    $result = array();
    #$query_encoded = urlencode($query);
    
    $data = $this->getUrlContents(
      "https://www.google.it/searchbyimage" .
      "?site=" . "imghp" .
      "&image_url=" . $this->url .
      "&num=" . $maxResults . 
      "&filter=" . "0"
    );
    $html = str_get_html($data);
     
    foreach($html->find('li.g') as $g) {
      /*
       * each search results are in a list item with a class name "g"
       * we are seperating each of the elements within, into an array;
       * titles are stored within "<h3><a...>{title}</a></h3>";
       * links are in the href of the anchor contained in the "<h3>...</h3>";
       * summaries are stored in a div with a classname of "s"
       */
      $srg = $g->find('div.srg', 0);
      $a = $srg->find('a', 0);
      $link = $a->href;
      #print "link: [$link]\n";
      $link = preg_replace("/^\/url\?q=/", "", $link);
      $link = preg_replace("/&amp;sa=U.*/", "", $link);
      $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
      $domain = parse_url($link)['host'];
      if ($domain !== $this->domain) { // consider only images from different domains
        $result[] = $link;
      }
    }
     
    # clean up the memory 
    $html->clear();
    
    return $result;
  }

  private function getUrlContents($url) {
    $user_agent = "Mozilla";
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl, " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't execute curl to [$url], " . curl_strerror($errno));
    }
    curl_close($ch);
    return $output;
  }

  /**
   * Destructor
   */
  function __destruct() {
  }

}