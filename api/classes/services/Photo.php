<?php
/**
 * Photo class
 * 
 * @package Photo
 * @author  Marco Solari <marcosolari@gmail.com>
 */

######################################################################################################################
#require "Network.php";
#$source = [];
#$source["url"] = "http://192.168.10.30/escrape/tobetested/001-sgi.jpg";
#$p1 = new Photo(null, $source, null);
#$source["url"] = "http://192.168.10.30/escrape/tobetested/003-toe.jpg"; # similar
##$source["url"] = "http://192.168.10.30/escrape/tobetested/user-0.jpg"; # different
#$p2 = new Photo(null, $source, null);
#$similar = $p1->checkSimilarity($p2);
#print "the images are " . ($similar ? "similar" : "different") . "\n";
#exit;
######################################################################################################################
#require "Network.php";
#$source = [];
#$source["url"] = "http://biografieonline.it/img/bio/Raffaella_Fico_1.jpg";
#$p = new Photo(null, $source, null);
#$text = "Image does not exist";
#$mime = "image/jpeg";
#$img = $p->createImageFromText($text, $mime);
#header("Content-Type: " . $mime);
#print $img;
#exit;
######################################################################################################################

class Photo {
  const INTERNAL_TYPE = "jpg"; // internal type of bitmaps
  const SMALL_HEIGHT = 96; // small photo height (pixels)
  const SIGNATURE_DUPLICATION_MIN_DISTANCE = 0.12; // minimum % distance for similarity duplication # TODO: tune-me
  const SIGNATURE_PIXELS_PER_SIDE = 10; // signature side (pixels)
  const TIMEOUT_BETWEEN_DOWNLOADS = 60;
  const RETRIES_MAX_FOR_DOWNLOADS = 3;

  public function __construct($router, $source, $options = []) {
    $this->router = $router;
    $this->options = $options;
    if (!isset($this->options["internalType"])) {
      $this->options["internalType"] = self::INTERNAL_TYPE;
    }
    if (!isset($this->options["smallHeight"])) {
      $this->options["smallHeight"] = self::SMALL_HEIGHT;
    }
    if (!isset($this->options["signatureDuplicationMinDistance"])) {
      $this->options["signatureDuplicationMinDistance"] = self::SIGNATURE_DUPLICATION_MIN_DISTANCE;
    }
    if (!isset($this->options["signaturePixelsPerSide"])) {
      $this->options["signaturePixelsPerSide"] = self::SIGNATURE_PIXELS_PER_SIDE;
    }

    $this->network = new Network();

    // initialize photo
    if ($source && isset($source["url"])) { // from url
      $this->fromUrl($source["url"]);
    } else {
      if ($source && isset($source["data"])) { // from data
        $this->fromData($source["data"]);
      } else {
        throw new Exception("Can't create photo: no source [ 'url' / 'data' ] specified");
      }
    }
  }

  /**
  * Create a photo from URL
  *
  * @param  string: photo URL
  * @return object: new photo if url is a valid photo
  *                 false     if url is not a valid photo
  */
  public function fromUrl($url) {
    $this->url = $url;
  }

  /**
   * Get the bitmap of the original image
   */
  public function bitmap() {
    if (isset($this->bitmap)) {
      return $this->bitmap;
    }
    $this->load();
    return $this->bitmap;
  }

  /**
   * Get the bitmap of the internal image, full size
   */
  public function bitmapFull() {
    if (isset($this->bitmapFull)) {
      return $this->bitmapFull;
    }
    $this->load();
    $bitmapFull = $this->bitmap;
    $this->bitmapFull = $this->convertBitmapToInternalType($bitmapFull);
    return $this->bitmapFull;
  }

  /**
   * Get the bitmap of the internal image, small size
   */
  public function bitmapSmall() {
    if (isset($this->bitmapSmall)) {
      return $this->bitmapSmall;
    }
    $this->load();
    $bitmapSmall = $this->photoScaleByHeight($this->bitmap, $this->options["smallHeight"]);
    $this->bitmapSmall = $this->convertBitmapToInternalType($bitmapSmall);
    return $this->bitmapSmall;
  }

  /**
   * Get the url of the original image
   */
  public function url() {
    if (isset($this->url)) {
      return $this->url;
    }
    $this->router->log("error", "url is not set");
  }
  
  /**
   * Get the MIME type of the original image
   */
  public function mime() {
    if (isset($this->mime)) {
      return $this->mime;
    }
    $this->load();
    return $this->mime;
  }

  /**
   * Get the type of the original image
   */
  public function type() {
    if (isset($this->type)) {
      return $this->type;
    }
    $this->load();
    return $this->type = $this->mime2Type($this->mime());
  }

  /**
   * Get the image resource of the original bitmap
   */
  public function image() {
    if (isset($this->image)) {
      return $this->image;
    }
    $this->load();
    try {
      return $this->image = imagecreatefromstring($this->bitmap());
    } catch(Exception $e) {
      $this->router->log("error", "bitmap is not in image recognized format (" . any2string($this->bitmap) . ")");
      throw new Exception("bitmap is not in image recognized format");
    }
  }

  /**
   * Get the image resource of the full bitmap
   */
  public function imageFull() {
    if (isset($this->imageFull)) {
      return $this->imageFull;
    }
    $this->load();
    try {
      return $this->imageFull = imagecreatefromstring($this->bitmapFull());
    } catch(Exception $e) {
      $this->router->log("error", "bitmap full is not in image recognized format");
      throw new Exception("bitmap full is not in image recognized format");
    }
  }

  /**
   * Get the image resource of the small bitmap
   */
  public function imageSmall() {
    if (isset($this->imageSmall)) {
      return $this->imageSmall;
    }
    $this->load();
    try {
      return $this->imageSmall = imagecreatefromstring($this->bitmapSmall());
    } catch(Exception $e) {
      $this->router->log("error", "bitmap is not in image recognized format");
      throw new Exception("bitmap small is not in image recognized format");
      return false;
    }
  }

  public function width() {
    if (isset($this->width)) {
      return $this->width;
    }
    $this->load();
    return $this->width = imagesx($this->image());
  }

  public function height() {
    if (isset($this->height)) {
      return $this->height;
    }
    $this->load();
    return $this->height = imagesy($this->image());
  }

  public function signature() {
    if (isset($this->signature)) {
      return $this->signature;
    }
    $this->load();
    return $this->signature = $this->signatureBuild();
  }

  public function sum() {
    if (isset($this->sum)) {
      return $this->sum;
    }
    $this->load();
    return $this->sum = md5($this->bitmap);
  }

  public function domain() {
    if (isset($this->domain)) {
      return $this->domain;
    }
    $this->load();
    $parsed_url = parse_url($this->url());
    return $this->domain = $parsed_url["scheme"] . "://" . $parsed_url["host"];
  }

  public function idPerson($value = null) {
    if (!$value) {
      if (isset($this->id_person)) {
        return $this->id_person;
      }
    }
    return $this->id_person = $value;
  }

  public function timestampCreation($value = null) {
    if (!$value) {
      if (isset($this->timestamp_creation)) {
        return $this->timestamp_creation;
      }
    }
    return $this->timestamp_creation = $value;
  }

  public function thruthful($value = null) {
    if (!$value) {
      if (isset($this->thruthful)) {
        return $this->thruthful;
      }
    }
    return $this->thruthful = $value;
  }

  public function showcase($value = null) {
    if (!$value) {
      if (isset($this->showcase)) {
        return $this->showcase;
      }
    }
    return $this->showcase = $value;
  }

  public function number($value = null) {
    if (!$value) {
      if (isset($this->number)) {
        return $this->number;
      }
    }
    return $this->number = $value;
  }

  public function pathFull($value = null) {
    if (!$value) {
      if (isset($this->path_full)) {
        return $this->path_full;
      }
    }
    return $this->path_full = $value;
  }

  public function pathSmall($value = null) {
    if (!$value) {
      if (isset($this->path_small)) {
        return $this->path_small;
      }
    }
    return $this->path_small = $value;
  }

  /**
   * Create a photo from data
   *
   * @param  data $array:   photo data structure
   * @return object:        photo object
   */
  public function fromData($data) {
    foreach ($data as $property => $value) {
      $this->$property = $value;
    }
    if (!array_key_exists("bitmap", $data)) {
      $this->bitmap = ""; // we don't need the url field, set a fake value
    }
    if (!array_key_exists("mime", $data)) {
      $this->mime = ""; // we don't need the mime field, set a fake value
    }
/*    
    if (!array_key_exists("bitmap", $data)) {
      // no bitmap: load it from db photos cache
      if (!array_key_exists("path_full", $data)) {
        $this->router->log("error", "Can't create photo from data: no 'path_full' specified");
        throw new Exception("Can't create photo from data: no 'path_full' specified");
      }
      try {
        $url = "../api/" . $this->path_full;
        $this->bitmap = file_get_contents($url);
        $this->mime = "image/jpeg"; // internal mime type (TODO: should be a class constant...)
        $this->bitmapFull = $this->bitmapFull();
        $this->bitmapSmall = $this->bitmapSmall();
      } catch(Exception $e) {
        $this->router->log("error", "can't get image [$url] contents: " . $e->getMessage());
        throw new Exception("can't get image [$url] contents: " . $e->getMessage());
      }
    }
*/
  }

  /**
   * Checks for photos similarities: check if one photo signature is close to the other photo signature
   */
  public function checkSimilarity($photo) {
    if (!$photo) {
      return false;
    }
    #$this->router->log("debug", "\$this->bitmap length: " . strlen($this->bitmap));
    #$this->router->log("debug", "\$this->url: " . any2string($this->url));

    $this->load();
    $distance = $this->compareSignatures($this->signature(), $photo->signature());
    if ($distance <= $this->options["signatureDuplicationMinDistance"]) { // duplicate found
      return true;
    }
    return false;
  }

  private function load() {
    if (isset($this->bitmap) && isset($this->mime)) { // already loaded
      return;
    }
    if (!isset($this->url)) {
      $this->router->log("error", "can't load photo: no url specified");
      return;
    }
    if (!isset($this->mime)) {
      $this->mime = null;
    }

    $this->router->log("info", "loading photo from network °°°°°°");

    $retry = 0;
    retry:
    try {
      $this->bitmap = $this->network->getImageFromUrl($this->url, $this->mime);
      # TODO: IMAGES HAVE LAST MODIFIED FIELD: DO A getLastModifiedTimestampFromUrl() before downloading... !!!

      if (!preg_match("/^image\//s", $this->mime)) {
        $this->logWrite("warning: curl, while getting image url [$url], returned mime type " . $mimeType);
        if (
          (strpos($message, "La pagina che hai tentato di visualizzare non esiste") !== false)
        ) {
          $this->router->log("warning", "can't get image [$this->url]: " . "does not exist");
          $this->bitmap = $this->createImageFromText("Image does not exist"); // return an error image to avoid returning here forever...
        } else {
          if (
            (strpos($message, "Why do I have to complete a CAPTCHA?") !== false) OR
            (strpos($message, "has banned your access") !== false)
          ) {
            $this->router->log("warning", "can't get image [$this->url]: " . "site denies access");
            # TODO: why sync execution stops here??? (and not true / false is returned?)
            if ($retry < RETRIES_MAX_FOR_DOWNLOADS) { // sleep a random number of seconds to avoid being banned...
              $retry++;
              $this->router->log("warning", "sleeping " . self::TIMEOUT_BETWEEN_DOWNLOADS * $retry . " seconds before retrying...");
              sleep(self::TIMEOUT_BETWEEN_DOWNLOADS * $retry);
              goto retry;
            } else {
              $this->router->log("error", "all " . self::TIMEOUT_BETWEEN_DOWNLOADS . " retries exausted, giving up");
              throw new Exception("all " . self::TIMEOUT_BETWEEN_DOWNLOADS . " retries exausted, giving up");
            }
          } else {
            $this->router->log("warning", "can't get image [$this->url] contents: " . $message);
            # TODO: do not throw exception, ignore this (networking) error... (?), but handle it someway in bitmap...() functions...
            #throw new Exception("can't get image [$this->url] contents: " . $message);
            $this->bitmap = $this->createImageFromText("Image is wrong"); // return an error image to avoid returning here forever...
          }
        }
      } else { // mime type contains "image"
        $this->router->log("debug", "bitmap loaded successfully from " . $this->url);
      }
    } catch(Exception $e) {
      $message = $e->getMessage();
      $this->router->log("warning", "can't get image [$this->url] contents: " . $message);
      # TODO: do not throw exception, ignore this (networking) error... (?), but handle it someway in bitmap...() functions...
      #throw new Exception("can't get image [$this->url] contents: " . $message);
      $this->bitmap = $this->createImageFromText("Image could not be loaded"); // return an error image to avoid returning here forever...
    }
  }

  /**
   * Creates an image from text
   */
  public function createImageFromText($text, $mime) {
    $font = "../../../api/fonts/OpenSans-Bold.ttf"; // the font path
    $fontSize = 24; // the font size
    $fontAngle = 0; // the font rotatioon angle
    $leftOffset = 10; // the text left offset
    $topOffset = 30; // the text left offset
    $width = 400; // the image width (TODO: auto-calculate from text and font?)
    $height = 50; // the image height (TODO: auto-calculate from text and font?)

    // turn on output buffering
    ob_start();

    // set the content-type
    #header("Content-Type: image/png");
    header("Content-Type: " . $mime);
    
    // create the image
    $im = imagecreatetruecolor($width, $height);
    
    // create some colors
    $white = imagecolorallocate($im, 255, 255, 255);
    $grey = imagecolorallocate($im, 200, 200, 200);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $white);
    
    // add the text shadow
    imagettftext($im, $fontSize, $fontAngle, $leftOffset + 1, $topOffset + 1, $grey, $font, $text);
    
    // add the text
    imagettftext($im, $fontSize, $fontAngle, $leftOffset, $topOffset, $black, $font, $text);
    
    // create the image
    switch ($mime) {
      case 'image/gif':
        imagegif($im);
        break;
      case 'image/jpeg':
        imagejpeg($im);
        break;
      case 'image/png':        
        imagepng($im);
        break;
      default:
        break;
    }
    
    // save image contents
    $image = ob_get_contents();
    
    // destroy the image
    imagedestroy($im);
    
    // turn off output buffering
    ob_end_clean();

    return $image;
  }

  /**
   * Gets photo last modification timestamp
   */
  public function getLastModificationTimestamp() {
    if (isset($this->timestamp_last_modification)) {
#throw new Exception("OLD LMT !!!");
      return $this->timestamp_last_modification;
    }
    $this->timestamp_last_modification = $this->network->getLastModificationTimestampFromUrl($this->url());
#throw new Exception("NEW LMT: " . $this->timestamp_last_modification);
    return $this->timestamp_last_modification;
  }

  /**
   * Converts a bitmap to internal type bitmap
   */
  private function convertBitmapToInternalType($bitmap) {
    if ($this->type() === $this->options["internalType"]) {
      // the bitmap is already of the requested type
      return $bitmap;
    }
    $image = imagecreatefromstring($bitmap);

    // produce the new bitmap
    ob_start();
    switch ($this->type()) {
      case "gif":
        if (!imagegif($image)) {
          return false;
        }
        break;
      case "jpg":
        if (!imagejpeg($image, NULL, 100)) { // 100% quality
          return false;
        }
        break;
      case "png":
        if (!imagepng($image, NULL, 0)) { // no compression
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
   * Returns a scaled version of a bitmap, given an height
   */
  private function photoScaleByHeight($bitmap, $height) {
    // calculate the new width to keep the same proportions
    $width = (($this->width() * $height) / $this->height());
    // create the image from the bitmap
    $image = imagecreatefromstring($bitmap);
    // generate the new photo container with the new size
    $imageScaled = imagecreatetruecolor($width, $height);
    // create the new photo
    imagecopyresampled(
      $imageScaled, $image,
      0, 0, 0, 0,
      $width, $height,
      $this->width(), $this->height()
    );
    // produce the new bitmap
    ob_start();
    switch ($this->options["internalType"]) {
      case "gif":
        if (!imagegif($imageScaled)) {
          return false;
        }
        break;
      case "jpg":
        if (!imagejpeg($imageScaled, NULL, 100)) { // 100% quality
          return false;
        }
        break;
      case "png":
        if (!imagepng($imageScaled, NULL, 0)) { // no compression
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
   * Returns the string of bits representing the signature of a photo
   */
  private function signatureBuild() {
    $imageResized = imagecreatetruecolor($this->options["signaturePixelsPerSide"], $this->options["signaturePixelsPerSide"]);
    imagecopyresized($imageResized, $this->image(), 0, 0, 0, 0, $this->options["signaturePixelsPerSide"], $this->options["signaturePixelsPerSide"], $this->width(), $this->height());
    imagefilter($imageResized, IMG_FILTER_GRAYSCALE);
    $colorMean = $this->colorMeanValue($imageResized);
    return $this->bits($colorMean);
  }

  /**
   * Returns the hammering distance of two photos signatures
   */
  private function compareSignatures($signature1, $signature2) {
    $hammingDistance = 0;
    $pixels = $this->options["signaturePixelsPerSide"] * $this->options["signaturePixelsPerSide"];
    for ($p = 0; $p < $pixels; $p++) {
      if (substr($signature1, $p, 1) != substr($signature2, $p, 1)) {
        $hammingDistance++;
      }
    }
    #return ($pixels - $hammingDistance) / $pixels; # returned value is in the range 0 -> 1
    return $hammingDistance / $pixels; # returned value is in the range 0 -> 1
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
        $type = 'gif';
        break;
      case 'image/jpeg':
        $type = 'jpg';
        break;
      case 'image/png':        
        $type = 'png';
        break;
      default:
        $type = "";
        break;
    }
    return $type;
  }

  /**
   * Adds transparency to an image
   */
  public function imageTransparent($image) {
    imagesavealpha($image, true);
    $colorTransparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $colorTransparent);
    return $image;
  }

/*
  / **
   * Resizes the surface of an image, preserving transparency
   * /
  public function imageSurfaceResize($image, $width, $height) {
    $widthSource = imagesx($image);
    $heightSource = imagesy($image);
    $imageNew = $this->imageTransparent(imagecreatetruecolor($width, $height));
    imagecopy(
      $imageNew, $image,
      ($width - $widthSource) / 2, ($height - $heightSource) / 2, 0, 0, # left, top, right, bottom
      $widthSource, $heightSource
    );
    return $imageNew;
  }
*/

  /**
   * Destructor
   */
  function __destruct() {
  }

}