<?php

function getUrlContents($url) {
  $user_agent = "Mozilla";

  $ch = curl_init();
  if (($errno = curl_errno($ch))) {
    throw new Exception("can't initialize curl, error $errno");
  }
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  $output = curl_exec($ch);
  if (($errno = curl_errno($ch))) {
    throw new Exception("can't execute curl to [$url], error $errno");
  }
  curl_close($ch);
  return $output;
}

function googleSearch($query) {
  $key = "AIzaSyDRAOL4tLtgya4BwcOCRSf0sYuYRbt2L4s";
  $cseNumber = "017714305635346072004:imlke-6wosa";

  $result = getUrlContents(
    "https://www.googleapis.com/customsearch/v1" .
    "?q=" . $query .
    "&cx=" . $cseNumber .
    "&key=" . $key .
    "&filter=" . "0" .
    "&siteSearch=" . "gnoccaforum.com" .
    "&start=" . "1" .
    "&num=" . "1"
  );
  return $result;
}

#echo googleSearch("38640cb366a690556d0e3e33a6925805");
#echo googleSearch("6a7f2abd4adb93727357304538c60807");
echo googleSearch("appuntamento");
?>
}
