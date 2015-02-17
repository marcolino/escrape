<?php

class ImagesTools {
  const PIXELS_PER_SIDE = 10;
  const IMAGE_MIN_DISTANCE = 0.4; # TODO: tune-me

  /**
   * Class constructor
   */
  function __construct($pixelsPerSide = 0) {
    if ($pixelsPerSide) {
      $this->pixelsPerSide = $pixelsPerSide;
    }
  }

  /**
  * Check for image truthfulness
  *
  * @param  string: photo url
  * @return boolean: true    if photo is not duplicated on the web
  *                  false   if photo is duplicated on the web
  */
  public function checkImageThruthfulness($imageUrl) {
    $similarUrls = googleSearchImage($imageUrl);
    if ($thrutfulness <= PHOTO_MIN_DISTANCE) { // same image found
      return false;
    }
    return true;
  }

  public function googleSearchImage($imageUrl, $domain) {
    $max_results = 99;
    $result = array();
    $query_encoded = urlencode($query);
    
    # obtain the first html page with the formatted url
    $data = $this->getUrlContents(
      "https://www.google.it/searchbyimage" .
      "?site=" . "imghp" .
      "&image_url=" . $image_url
      "&num=" . $max_results . 
      "&filter=" . "0" .
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
      $h3 = $g->find('h3.r', 0);
      $s = $g->find('div.s', 0);
      $a = $h3->find('a', 0);
      $link = $a->href;
      $link = preg_replace("/^\/url\?q=/", "", $link);
      $link = preg_replace("/&amp;sa=U.*/", "", $link);
      $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
      if (parse_url($link)['host'] === $domain) {
        continue; // skip images in the same domain
      }
      $result[] = $link;
    }
     
    # clean up the memory 
    $html->clear();
    
    return $result;
  }

  /**
   * Checks if an image is a duplication of other images
   */
  public function checkImageDuplication($img, $images) {
    $sig1 = $this->getSignatureFromUrl($img);
    foreach ($images as $image) {
      $sig2 = $this->getSignatureFromUrl($image);
      $distance = $this->compareSignatures($sig1, $sig2);
      if ($distance <= IMAGE_MIN_DISTANCE) { // duplicate found
        return true;
      }
    }
    return false;
  }

  /**
   * Checks if an image signature is a duplication of other images signatures
   */
  public function checkImageSignatureDuplication($sig, $sigs) {
    foreach ($sigs as $sign) {
      $distance = $this->compareSignatures($sig, $sign);
      if ($distance <= IMAGE_MIN_DISTANCE) { // duplicate found
        return true;
      }
    }
    return false;
  }

  /**
   * Returns the hammering distance of two images (files or urls) signatures
   */
  public function compareImages($img1, $img2) {
    $sig1 = $this->getSignature($img1);
    $sig2 = $this->getSignature($img2);
    return $this->compareSignatures($sig1, $sig2);
  }

  /**
   * Returns the string of bits representing the signature of an image file or url
   */
  public function getSignaturefromUrl($image) {
    return getSignature($this->createImage($image));
  }

  /**
   * Returns the string of bits representing the signature of an image bitmap
   */
  public function getSignaturefromBitmap($bitmap) {
    return getSignature(imagecreatefromstring($bitmap));
  }

  /**
   * Returns the string of bits representing the signature of an image
   */
  public function getSignature($image) {
    if (!$i) {
      return false;
    }
    $i = $this->resizeImage($i, $image);
    imagefilter($i, IMG_FILTER_GRAYSCALE);
    $colorMean = $this->colorMeanValue($i);
    $bits = $this->bits($colorMean);
    return $bits;
  }

  /**
   * Returns the hammering distance of two images signatures
   */
  public function compareSignatures($sig1, $sig2) {
    $hammeringDistance = 0;
    $pixels = $this->pixelsPerSide * $this->pixelsPerSide;
    for ($a = 0; $a < $pixels; $a++) {
      if (substr($sig1, $a, 1) != substr($sig2, $a, 1)) {
        $hammeringDistance++;
      }
    }
    return ($pixels - $hammeringDistance) / $pixels; # returned value is in the range 0 -> 1
  }

  /**
   * Returns array with mime type and if its jpg or png, or false if it isn't jpg nor png
   */
  private function mimeType($i) {
    $mime = getimagesize($i);
    $return = array($mime[0], $mime[1]);
    
    switch ($mime['mime']) {
      case 'image/jpeg':
        $return[] = 'jpg';
        return $return;
      case 'image/png':
        $return[] = 'png';
        return $return;
      default:
        return false;
    }
  }  
  
  /**
   * Retuns image resource, or false if its not jpg nor png
   */
  private function createImage($i) {
    $mime = $this->mimeType($i);
    if ($mime[2] == 'jpg') {
      return imagecreatefromjpeg($i);
    } else if ($mime[2] == 'png') {
      return imagecreatefrompng($i);
    } else {
      return false; 
    } 
  }
  
  /**
   * Resizes the image to a square and returns as image resource
   */
  private function resizeImage($i, $source) {
    $mime = $this->mimeType($source);
    $t = imagecreatetruecolor($this->pixelsPerSide, $this->pixelsPerSide);
    $source = $this->createImage($source);
    imagecopyresized($t, $source, 0, 0, 0, 0, $this->pixelsPerSide, $this->pixelsPerSide, $mime[0], $mime[1]);
    return $t;
  }
  
  /**
   * Returns the mean value of the colors and the list of all pixel's colors
   */
  private function colorMeanValue($i) {
    $colorList = array();
    $colorSum = 0;
    for ($a = 0; $a < $this->pixelsPerSide; $a++) {
      for ($b = 0; $b < $this->pixelsPerSide; $b++) {
        $rgb = imagecolorat($i, $a, $b);
        $colorList[] = $rgb & 0xFF;
        $colorSum += $rgb & 0xFF;
      }
    }
    return array($colorSum / ($this->pixelsPerSide * $this->pixelsPerSide), $colorList);
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

  private function getUrlContents($url) {
    $user_agent = "Mozilla";
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      $this->router->log("error", "can't initialize curl, " . curl_strerror($errno));
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
      $this->router->log("error", "can't execute curl to [$url], " . curl_strerror($errno));
      throw new Exception("can't execute curl to [$url], " . curl_strerror($errno));
    }
    curl_close($ch);
    return $output;
  }

}

/*
$it = new ImagesTools;

$img1 = "http://www.visitatorino.com/en/images/stories/palazzi/La_Mole.JPG";
$img2 = "http://www.aboutturin.com/immagini-torino/mole_antonelliana.jpg";

$sig1 = $it->getSignature($img1);
$sig2 = $it->getSignature($img2);
echo "$sig1 and $sig2 similarity index is " . $it->compareSignatures($sig1, $sig2) . "\n";


$imgurl = "http://www.sexyguidaitalia.com/public/23383/copertina.jpg";
$list = $it->googleSearchImage($imgurl);

echo "$list of similar images on different sites is this:<br>\n" . $list;
*/

?>