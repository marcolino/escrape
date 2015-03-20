<?php
/**
 * Photo class
 * 
 * @package Photo
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class Photo {
  const INTERNAL_TYPE = "jpg"; // internal type of bitmaps
  const SMALL_HEIGHT = 72; // small photo height (pixels)
  const SIGNATURE_DUPLICATION_MIN_DISTANCE = 0.1; // minimum % distance for similarity duplication # TODO: tune-me
  const SIGNATURE_PIXELS_PER_SIDE = 10; // signature side (pixels)

  public function __construct($source, $options = []) {
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
    throw new Exception("Can't get photo url: no url source specified");
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
    return $this->image = imagecreatefromstring($this->bitmap());
  }

  /**
   * Get the image resource of the full bitmap
   */
  public function imageFull() {
    if (isset($this->imageFull)) {
      return $this->imageFull;
    }
    $this->load();
    return $this->imageFull = imagecreatefromstring($this->bitmapFull());
  }

  /**
   * Get the image resource of the small bitmap
   */
  public function imageSmall() {
    if (isset($this->imageSmall)) {
      return $this->imageSmall;
    }
    $this->load();
    return $this->imageSmall = imagecreatefromstring($this->bitmapSmall());
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

  public function thruthfulness($value = null) {
    if (!$value) {
      if (isset($this->thruthfulness)) {
        return $this->thruthfulness;
      }
    }
    return $this->thruthfulness = $value;
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
  }

  /**
   * Checks for photos similarities: check if one photo signature is close to the other photo signature
   */
  public function checkSimilarity($photo) {
    if (!$photo) {
      return false;
    }
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
      throw new Exception("Can't load photo: no url source specified");
    }
    list($this->bitmap, $this->mime) = $this->getUrlContents($this->url); // download photo
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
    return ($pixels - $hammingDistance) / $pixels; # returned value is in the range 0 -> 1
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
   * Returns a merged image from a collection of images,
   * arranged as a deck of cards.
   * Result size will be PNG format;
   * it's size will be 20% bigger than the biggest source image;
   * it's background will be transparent.
   *
   * @param  array $imageUrls         source images urls (or filenames)
   * @return null                     some error occurred 
   *         bitmap                   merged image bitbmap
   */
  public function photoGetCardDeck($imagesUrls) {
    $scaleFactor = 120 / 100; // scale factor of output image relative to the biggest of source images

    // Load images from urls (or filenames)
    $count = count($imagesUrls);
    $images = [];
    $widthMax = 0;
    $heightMax = 0;
    for ($n = 0; $n < $count; $n++) {
      $imageUrl = $imagesUrls[$n];
      switch (strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION))) {
        case 'jpeg':
        case 'jpg': // JPEG image
          $image = imagecreatefromjpeg($imageUrl);
          break;
        case 'png': // PNG image
          $image = imagecreatefrompng($imageUrl);
          break;
        case 'gif': // GIF image
          $image = imagecreatefromgif($imageUrl);
          break;
        default: // unforeseen type image, skip it
          contiue;
        break;
      }
      if (!$image) {
        #print "can't transform [$imageUrl] to image\n";
        contiue;
      }
      $images[$n] = [];
      $images[$n]["bitmap"] = $this->imageTransparent($image);
      $images[$n]["width"] = imagesx($image);
      $images[$n]["height"] = imagesy($image);
      $widthMax = max($widthMax, $images[$n]["width"]);
      $heightMax = max($heightMax, $images[$n]["height"]);
    }
    $count = count($images);
    if ($count <= 0) {
      #die("0 good images found\n");
      return false; // no suitable image found
    }

    // create the new image container with the new size
    $height = $heightMax * $scaleFactor;
    $width = $widthMax * $scaleFactor;

    $image = $this->imageTransparent(imagecreatetruecolor($width, $height));

    for ($n = 0; $n < $count; $n++) {
      // calculate the rotation degrees for this image
      $notSoFlatDegrees = 20; // a card deck is never 180 degrees wide, but some degree less...
      $degrees = ($notSoFlatDegrees / 2) + (((180 - $notSoFlatDegrees) / (1 + $count)) * (1 + $n));
      $degrees = - ($degrees - 90);

      # calculate small offsets to better dispose 'cards' in 'deck'
      $hUnit = $height / 36;
      $wUnit = ($width - $images[$n]["width"]) / 2;
      $top = ((1 * $hUnit) * sin(deg2rad($n * (180 / ($count - 1))))) - (1 * $hUnit);
      $sign = sign(cos(deg2rad($n * (180 / ($count - 1)))));
      $left = ($sign ? -$sign : 1) * $wUnit + -((1 * $wUnit) * (cos(deg2rad($n * (180 / ($count - 1)))) * (1)));

      // rotate image
      $imageBigger = $this->imageSurfaceResize($images[$n]["bitmap"], $width, $height);
      $verticalMargin = ($height - $images[$n]["height"]) / 2;
      $horizontalMargin = ($width - $images[$n]["width"]) / 2;
      $colorTransparent = imageColorAllocateAlpha($imageBigger, 0, 0, 0, 127);
      $imageRotated = $this->imageTransparent(imagerotate($imageBigger, $degrees, $colorTransparent));

      // merge the rotated image to the base image
      imagecopyresampled(
        $image, $imageRotated,
        $left, $top, 0, 0, # left, top, right, bottom
        $images[$n]["width"], $images[$n]["height"],
        $width, $height
      );
      imagedestroy($imageRotated);
    }
    // produce the new bitmap
    ob_start();
    if (!imagepng($image, NULL, 0)) { // no compression
      #die("can't create final image\n");
      return false;
    }
    $bitmap = ob_get_contents();
    ob_end_clean();
    imagedestroy($image);
    // return the new bitmap
    return $bitmap;
  }

  public function imageTransparent($image) {
    imagesavealpha($image, true);
    $colorTransparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $colorTransparent);
    return $image;
  }

  /**
   * Resizes the surface of an image, preserving transparency
   */
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

  /**
   * Downloads an url
   *
   * @param  string $url:             source url
   * @return array [ output, mime ]   an array; first element is url content, second is mime type
   */
  private function USE_NETWOR_VERSION_getUrlContents($url) {
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