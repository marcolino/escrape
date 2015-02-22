<?php

class ImagesTools {
  const SIGNATURE_PIXELS_PER_SIDE = 10;
  const IMAGE_MIN_DISTANCE = 0.4; # TODO: tune-me

  /**
   * Class constructor
   */
  function __construct($router, $options = []) {
    $this->router = $router;
    if (isset($options["signaturePixelsPerSide"])) {
      $this->signaturePixelsPerSide = $options["signaturePixelsPerSide"];
    } else {
      $this->signaturePixelsPerSide = self::SIGNATURE_PIXELS_PER_SIDE;
    }
  }

  public function googleSearchImage($imageUrl, $domain) {
    $maxResults = 99;
    $result = array();
    #$query_encoded = urlencode($query);
    
    $data = $this->getUrlContents(
      "https://www.google.it/searchbyimage" .
      "?site=" . "imghp" .
      "&image_url=" . $imageUrl .
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
print "link: [$link]\n";
      $link = preg_replace("/^\/url\?q=/", "", $link);
      $link = preg_replace("/&amp;sa=U.*/", "", $link);
      $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
      if (parse_url($link)['host'] !== $domain) {
        $result[] = $link; // consider only images from different domains
      }
    }
     
    # clean up the memory 
    $html->clear();
    
    return $result;
  }

  /**
   * Check for image duplication
   *
   * @param  array: image
   * @param  string: image sum
   * @return boolean: true    if image is a duplicate
   *                  false   if image is not a fuplicate
   */
  public function checkImageDuplication($images, $sum) {
    if (!$images) {
      return false;
    }
print "IMAGES:\n"; var_dump($images);
    foreach ($images as $image) {
      if ($image["sum"] === $sum) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check for image truthfulness
   *
   * @param  string: photo url
   * @return boolean: true    if photo is not duplicated on the web
   *                  false   if photo is duplicated on the web
   */
  public function checkImageThruthfulness($imageUrl) {
    $domain = parse_url($imageUrl)['host'];
    $similarUrls = $this->googleSearchImage($imageUrl, $domain);
    if (count($similarUrls) > 0) { // same image found
      $this->router->log("info", "found images similar to $imageUrl, it's probably non true...");
      return false;
    }
    $this->router->log("info", "found no images similar to $imageUrl, it's probably true...");
    return true;
  }

  /**
   * Checks if an image is a duplication of other images
   */
/*
  public function checkImageDuplication($img, $images) {
    $sig1 = $this->getSignatureFromUrl($img);
    foreach ($images as $image) {
      $sig2 = $this->getSignatureFromUrl($image);
      $distance = $this->compareSignatures($sig1, $sig2);
      if ($distance <= IMAGE_MIN_DISTANCE) { // duplicate found
        $this->router->log("info", "image $img is similar to $image, it's probably a duplicate...");
        return true;
      }
    }
    return false;
  }
*/
  /**
   * Checks if an image signature is a duplication of other images signatures
   */
/*
  public function checkImageSignatureDuplication($sig, $sigs) {
    foreach ($sigs as $sign) {
      $distance = $this->compareSignatures($sig, $sign);
      if ($distance <= IMAGE_MIN_DISTANCE) { // duplicate found
        $this->router->log("info", "image signature $sig is similar to $sign, it's probably a duplicate...");
        return true;
      }
    }
    return false;
  }
*/
  public function checkImageSimilarity($signature, $images) {
    if (!$images) {
      return false;
    }
    foreach ($images as $image) {
      $distance = $this->compareSignatures($signature, $image["signature"]);
      if ($distance <= IMAGE_MIN_DISTANCE) { // duplicate found
        $this->router->log("info", "image signature $signature is similar to " . $image["signature"] . " ($distance), it's probably a duplicate...");
        return true;
      }
    }
    return false;
  }

  /**
   * Returns the hammering distance of two images (files or urls) signatures
   */
  public function compareImages($imgUrl1, $imgUrl2) {
    $sig1 = $this->getSignatureFromUrl($imgUrl1);
    $sig2 = $this->getSignatureFromUrl($imgUrl2);
    return $this->compareSignatures($sig1, $sig2);
  }

  /**
   * Returns the string of bits representing the signature of an image file or url
   */
  public function getSignaturefromUrl($imageUrl) {
    return $this->getSignature($this->createImage($imageUrl), imageSize($imageUrl));
  }

  /**
   * Returns the string of bits representing the signature of an image bitmap
   */
  public function getSignaturefromBitmap($bitmap, $imageUrl) {
    return $this->getSignature(imagecreatefromstring($bitmap), $this->imageSize($imageUrl));
  }

  /**
   * Returns the string of bits representing the signature of an image
   */
  public function getSignature($i, $size) {
    if (!$i) {
      return false;
    }
    $i = $this->resizeImage($i, $size);
    imagefilter($i, IMG_FILTER_GRAYSCALE);
    $colorMean = $this->colorMeanValue($i);
    $bits = $this->bits($colorMean);
    return $bits;
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

  /**
   * Returns the mime type of the image url if its jpg or png, or false otherwise
   */
  private function mimeType($imageUrl) {
    $info = getimagesize($imageUrl);   
    switch ($info["mime"]) {
      case "image/jpeg":
        return "jpg";
      case "image/png":
        return "png";
      default:
        return false;
    }
  }  
  
  /**
   * Returns the size of the image url
   */
  private function imageSize($imageUrl) {
    $info = getimagesize($imageUrl);   
    return [ $info[0], $info[1] ];
  }  
  /**
   * Retuns image resource, or false if its not jpg nor png
   */
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
  
  /**
   * Resizes the image to a square and returns as image resource
   */
  private function resizeImage($i, $size) {
    #$mime = $this->mimeType($i);
    $t = imagecreatetruecolor($this->signaturePixelsPerSide, $this->signaturePixelsPerSide);
    #$i = $this->createImage($i);
    imagecopyresized($t, $i, 0, 0, 0, 0, $this->signaturePixelsPerSide, $this->signaturePixelsPerSide, $size[0], $size[1]);
    return $t;
  }
  
  /**
   * Returns the mean value of the colors and the list of all pixel's colors
   */
  private function colorMeanValue($i) {
    $colorList = array();
    $colorSum = 0;
    for ($a = 0; $a < $this->signaturePixelsPerSide; $a++) {
      for ($b = 0; $b < $this->signaturePixelsPerSide; $b++) {
        $rgb = imagecolorat($i, $a, $b);
        $colorList[] = $rgb & 0xFF;
        $colorSum += $rgb & 0xFF;
      }
    }
    return array($colorSum / ($this->signaturePixelsPerSide * $this->signaturePixelsPerSide), $colorList);
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