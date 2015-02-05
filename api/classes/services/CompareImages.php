<?php

class CompareImages {
  private $pixelsPerSide = 10;

  /**
   * Class constructor
   */
  function __construct($pixelsPerSide = 0) {
    if ($pixelsPerSide) {
      $this->pixelsPerSide = $pixelsPerSide;
    }
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
   * Returns the string of bits representing the signature of an image (files or urls)
   */
  public function getSignature($image) {
    $i = $this->createImage($image);
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
}

/*
require 'CompareImages.php';

$ci = new CompareImages;

$img1 = "http://www.visitatorino.com/en/images/stories/palazzi/La_Mole.JPG";
$img2 = "http://www.aboutturin.com/immagini-torino/mole_antonelliana.jpg";

$sig1 = $ci->getSignature($img1);
$sig2 = $ci->getSignature($img2);
echo "$sig1 and $sig2 similarity index is " . $ci->compareSignatures($sig1, $sig2) . "\n";
*/

?>