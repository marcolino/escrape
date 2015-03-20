<?php

$imagesUrls =  [
  #"http://192.168.10.30/escrape/app/images/referral-sites-TEST/esf.png",
  #"http://192.168.10.30/escrape/app/images/referral-sites-TEST/toe2.png",
  "http://192.168.10.30/escrape/app/images/referral-sites-TEST/google+.png",
  "http://192.168.10.30/escrape/app/images/referral-sites-TEST/sgi.png",
  "http://192.168.10.30/escrape/app/images/referral-sites-TEST/facebook.png",
];

if (($image = photosCardDeckMerge($imagesUrls)) === false) {
  die("Can't merge images as card deck\n");
}
header('Content-Type: image/png');
print $image;
exit;

  /**
   * Returns a merged image from a collection of images,
   * arranged as a deck of cards.
   * Result size will be PNG format;
   * it's size will be 20% bigger than the biggest source image;
   * it's background will be transparent.
   *
   * @param  array $photosUrls        source images urls (or filenames)
   * @return null                     some error occurred 
   *         bitmap                   merged image bitbmap
   */
  /*public*/ function photosCardDeckMerge($imagesUrls) {
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
      $images[$n]["bitmap"] = imageTransparent($image);
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

    $image = imageTransparent(imagecreatetruecolor($width, $height));

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
      $imageBigger = imageSurfaceResize($images[$n]["bitmap"], $width, $height);
      $verticalMargin = ($height - $images[$n]["height"]) / 2;
      $horizontalMargin = ($width - $images[$n]["width"]) / 2;
      $colorTransparent = imageColorAllocateAlpha($imageBigger, 0, 0, 0, 127);
      $imageRotated = imageTransparent(imagerotate($imageBigger, $degrees, $colorTransparent));

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

  /*public*/ function imageTransparent($image) {
    imagesavealpha($image, true);
    $colorTransparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $colorTransparent);
    return $image;
  }

  /**
   * Resizes the surface of an image, preserving transparency
   */
  /*public*/ function imageSurfaceResize($image, $width, $height) {
    $widthSource = imagesx($image);
    $heightSource = imagesy($image);
    $imageNew = imageTransparent(imagecreatetruecolor($width, $height));
    imagecopy(
      $imageNew, $image,
      ($width - $widthSource) / 2, ($height - $heightSource) / 2, 0, 0, # left, top, right, bottom
      $widthSource, $heightSource
    );
    return $imageNew;
  }

  /**
   * Returns the sign of a floating point number.
   * If the absolute value of the number is very low (even not exactly 0, 0 is returned.
   */
  function sign($number) {
    return abs($number) >= 0.00001 ? abs($number) / $number : 0;
  }


?>