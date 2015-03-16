<?php
  $this->commentsDefinition = [
    "gnf" => [
      "domain" => "twitter.com",
      "charset" => "utf-8",
      "locale" => "it_IT.UTF-8",
      "timezone" => "Europe/Rome",
      "patterns" => [
        "topic" => "/TODO TOPIC/s",
        "block" => "/TODO BLOCK/s",
        "author" => "/TODO AUTHOR/s",
        "author-karma" => "/TODO AUTHOR_KARMA/s",
        "author-posts" => "/TODO AUTHOR_POSTS/s",
        "date" => "/TODO DATE/s",
        "content" => "/TODO CONTENT/s",
        "quote-signature" => "/TODO QUOTE_SIGNATURE/s",
        "next-link" => "/TODO NEXT_LINK/s",
      ],
    ],
  ];

  // TODO: we don't need these anymore, using Network.php's methods...
  $googleSearchMinDelayRange = [ 30, 75 ]; // range for random delay
  $googleSearchDelayAfterUnusualTraffic = 7200; // 120 minutes
  $googleSearchLastTimestamp = 0; // last query timestamp
?>