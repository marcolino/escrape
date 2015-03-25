<?php
  $this->commentsDefinition = [
    "facebook" => [
      "domain" => "facebook.com",
      "charset" => "utf-8",
      "locale" => "en_US.UTF-8",
      "timezone" => "Europe/Rome",
      "patterns" => [
        "topic" => "/TODO/s",
        "block" => "/TODO/s",
        "author" => "/TODO/s",
        "author-karma" => "/TODO/s",
        "author-posts" => "/TODO/s",
        "date" => "/TODO/s",
        "content" => "/TODO/s",
        "quote-signature" => "/TODO/s",
        "next-link" => "/TODO/s",
      ],
    ],
  ];
/*
  // TODO: we don't need these anymore, using Network.php's methods...
  $googleSearchMinDelayRange = [ 30, 75 ]; // range for random delay
  $googleSearchDelayAfterUnusualTraffic = 7200; // 120 minutes
  $googleSearchLastTimestamp = 0; // last query timestamp
*/
?>