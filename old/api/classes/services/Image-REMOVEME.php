<?php
/**
 * Image class
 * 
 * @package Image
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class Image {
  const SIGNATURE_SIDE = 10; // signature side (pixels)
  const SIGNATURE_DUPLICATION_MIN_DISTANCE = 0.4; // minimum % distance for similarity duplication # TODO: tune-me
  const THUMBNAIL_HEIGHT = 72; // thumbnail "small" height (pixels)
  const INTERNAL_TYPE = "jpeg"; // internal type of bitmaps

  public function __construct($options = []) {
    $this->options = $options;
    if (!isset($this->options["signaturePixelsPerSide"])) {
      $this->options["signaturePixelsPerSide"] = self::SIGNATURE_SIDE;
    }
    if (!isset($this->options["signatureDuplicationMinDistance"])) {
      $this->options["signatureDuplicationMinDistance"] = self::SIGNATURE_DUPLICATION_MIN_DISTANCE;
    }
    if (!isset($this->options["thumbnailHeight"])) {
      $this->options["thumbnailHeight"] = self::THUMBNAIL_HEIGHT;
    }
    if (!isset($this->options["internalType"])) {
      $this->options["internalType"] = self::INTERNAL_TYPE;
    }
  }

  /**
  * Create an image from URL
  *
  * @param  string: image URL
  * @return object: new image if url is a valid image
  *                 false     if url is not a valid image
  */
  public function fromUrl($url) {
    $this->url = $url;
    list($this->bitmap, $this->mime) = $this->getUrlContents($url); // download image
    $this->type = $this->mime2Type($this->mime);
    $this->img = imagecreatefromstring($this->bitmap);
    $this->width = imagesx($this->img);
    $this->height = imagesy($this->img);
    #$info = getimagesize($url);
    #$this->width = $info[0];
    #$this->height = $info[1];
    #$this->type = $info[2];
    #$this->mime = $info["mime"];
    $this->signature = $this->signature();
    $this->bitmapFull = $this->convertToType($this->options["internalType"]); // convert image to internal type
    $this->imgFull = imagecreatefromstring($this->bitmapFull);
    $this->bitmapSmall = $this->scaleByHeight($this->options["thumbnailHeight"]);
    $this->imgSmall = imagecreatefromstring($this->bitmapFull);
    $this->sum = md5($this->bitmap);
  }

  /**
   * Create an image from array
   *
   * @param  array $array:   image array
   * @return object:         image object
   */
  public function fromArray($array) {
    $this->url = $array["url"];
    $this->bitmap = $array["bitmap"];
    $this->type = $array["type"];
    $this->img = $array["img"];
    $this->width = $array["width"];
    $this->height = $array["height"];
    $this->mime = $array["mime"];
    $this->signature = $array["signature"];
    $this->bitmapFull = $array["bitmapFull"];
    $this->imgFull = $array["imgFull"];
    $this->bitmapSmall = $array["bitmapSmall"];
    $this->imgSmall = $array["imgSmall"];
    $this->sum = $array["sum"];
  }

  /**
   * Return an image as array
   *
   * @return array:   image array
   */
  public function toArray() {
    $array = [];
    $array["url"] = $this->url;
    #$array["bitmap"] = $this->bitmap;
    #$array["width"] = $this->width;
    #$array["height"] = $this->height;
    $array["type"] = $this->type;
    $array["mime"] = $this->mime;
    $array["signature"] = $this->signature;
    #$array["bitmapFull"] = $this->bitmapFull;
    #$array["bitmapSmall"] = $this->bitmapSmall;
    $array["sum"] = $this->sum;
    return $array;
  }

  /**
   * Checks for images similarities: check if one image signature is close to the other image signature
   */
  public function checkSimilarity($image) {
    if (!$image) {
      return false;
    }
    $distance = $this->compareSignatures($this->signature, $image->signature);
    if ($distance <= $this->options["signatureDuplicationMinDistance"]) { // duplicate found
      return true;
    }
    return false;
  }

  /**
   * Converts the image to a given type
   */
  private function convertToType($type) {
    if ($this->type === $type) { // the image bitmap is already of the requested type
      return $this->bitmap;
    }
    #// create the image from the bitmap
    #$img = imagecreatefromstring($this->bitmap);
    // produce the new bitmap
    ob_start();
    switch ($type) {
      case "gif":
        if (!imagegif($this->img)) {
          return false;
        }
        break;
      case "jpeg":
        if (!imagejpeg($this->img, NULL, 100)) { // 100% quality
          return false;
        }
        break;
      case "png":
        if (!imagepng($this->img, NULL, 0)) { // no compression
          return false;
        }
        break;
      default:
        return false;
    }
    $bitmap = ob_get_contents();
    ob_end_clean();
    return $bitmap;
  }

  /**
   * Returns a scaled version of the image, given an height
   */
  private function scaleByHeight($height) {
    #// create the image from the bitmap
    #$img = imagecreatefromstring($this->bitmapFull);
    // calculate the new width to keep the same proportions
    $width = (($this->width * $height) / $this->height);
    // generate the new image container with the new size
    $imgScaled = imagecreatetruecolor($width, $height);
    // create the new image
    imagecopyresized(
      $imgScaled, $this->imgFull,
      0, 0, 0, 0,
      $width, $height,
      $this->width, $this->height
    );
    // produce the new bitmap
    ob_start();
    switch ($this->options["internalType"]) {
      case "gif":
        if (!imagegif($imgScaled)) {
          return false;
        }
        break;
      case "jpeg":
        if (!imagejpeg($imgScaled, NULL, 100)) { // 100% quality
          return false;
        }
        break;
      case "png":
        if (!imagepng($imgScaled, NULL, 0)) { // no compression
          return false;
        }
        break;
      default:
        return false;
    }
    $bitmap = ob_get_contents();
    ob_end_clean();
    return $bitmap;
  }

  /**
   * Returns the hammering distance of two images signatures
   */
  private function compareSignatures($signature1, $signature2) {
    $hammingDistance = 0;
    $pixels = $this->options["signaturePixelsPerSide"] * $this->options["signaturePixelsPerSide"];
    for ($p = 0; $p < $pixels; $p++) {
      if (substr($signature1, $p, 1) != substr($signature2, $p, 1)) {
        $hammingDistance++;
      }
    }
    return ($pixels - $hammingDistance) / $pixels; # returned value is in the range 0 -> 1
  }

  /**
   * Returns the string of bits representing the signature of an image
   */
  private function signature() {
    #$img = imagecreatefromstring($this->bitmap);
    $imgResized = imagecreatetruecolor($this->options["signaturePixelsPerSide"], $this->options["signaturePixelsPerSide"]);
    imagecopyresized($imgResized, $this->img, 0, 0, 0, 0, $this->options["signaturePixelsPerSide"], $this->options["signaturePixelsPerSide"], $this->width, $this->height);
    imagefilter($imgResized, IMG_FILTER_GRAYSCALE);
    $colorMean = $this->colorMeanValue($imgResized);
    $bits = $this->bits($colorMean);
    return $bits;
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

  private function mime2Type($mime) {
    switch ($mime) {
      case 'image/gif':
        $type = '.gif';
        break;
      case 'image/jpeg':
        $type = '.jpg';
        break;
      case 'image/png':        
        $type = '.png';
        break;
      default:
        $type = "";
        break;
    }
    return $type;
  }

  private function getUrlContents($url) {
    $user_agent = "Mozilla";
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl: " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't execute curl to [$url]: " . curl_strerror($errno));
    }
    $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [ $output, $mime ];
  }

  /**
   * Destructor
   */
  function __destruct() {
  }

}