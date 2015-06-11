<?php

  $photo = [];
  $photo["url"] = "http://www.google.com/";
  print "Photo url [".$photo["url"]."] availability: " . (assertPhotoAvailability($photo) ? "YES" : "NO") . "\n";

  function assertPhotoAvailability($photo) {
    if (($headers = @get_headers($photo["url"], true)) === false) {
      return false;
    }
    $type = $headers["Content-Type"];
    if (is_array($type)) {
      $type = $type[0];
    }
    return (substr($type, 0, strlen('image')) === 'image');
  }

?>