<?php

  include_once 'lib/GoogleImageSearch.php';

  #$remoteUrl = "http://upload.wikimedia.org/wikipedia/commons/2/22/Turkish_Van_Cat.jpg";
  $remoteUrl = "http://content9.babesbang.com/playboy-plus.com/1609/02.jpg";
  $maxPages = 3;

  ini_set("display_errors", "On");
  error_reporting(E_ALL);

  $imageSearch = new GoogleImageSearch();
  
  print "Search by image URL [$remoteUrl]:\n";
  if ($results = $imageSearch->search($remoteUrl, $maxPages)) {
    if ($results["search_results"]) {
      if ($results["best_guess"]["text"]) {
        print "\nBest guess - text: {$results["best_guess"]["text"]}, href: {$results["best_guess"]["href"]}\n\n";
      }
      foreach ($results["search_results"] as $result) {
        print "text: " . $result["text"] . ", imgsrc: " . $result["imgsrc"] . ", href: " . $result["href"] . "\n";
      }
    } else {
      print "Nothing found\n";
    }
  }
?>