<?php

include('simple_html_dom.php');

#$phone = "331.9778327"; # Pam
$phone = "366.2588498"; # Rox

$result = search_with_google_by_md5_phone($phone);
print_r($result);



function search_with_google_by_md5_phone($phone) {
  $phone = preg_replace("/[^\d]*/", "", $phone);
  $phone_md5 = md5($phone);
  $result = search_with_google($phone_md5);
  return $result;
}

function search_with_google($query) {
  $max_results = 99;
  $result = array();

  $q = urlencode($query);

  # obtain the first html page with the formated url
  $data = file_get_contents_curl("https://www.google.com/search?num=" . $max_results . "&" . "q=" . $q);
  if ($data === FALSE) {
  	print "Error fetching data from Google\n";
  	return $result;
  }

  $html = str_get_html($data);
   
  foreach($html->find('li.g') as $g) {
  	/*
  	 * each search results are in a list item with a class name "g"
  	 * we are seperating each of the elements within, into an array;
  	 * titles are stored within "<h3><a...>{title}</a></h3>";
  	 * links are in the href of the anchor contained in the "<h3>...</h3>";
  	 * summaries are stored in a div with a classname of "s"
  	 */
  	$h3 = $g->find('h3.r', 0);
  	$s = $g->find('div.s', 0);
  	$a = $h3->find('a', 0);
  	$link = $a->href;
  	$link = preg_replace("/^\/url\?q=/", "", $link);
  	$link = preg_replace("/&amp;sa=U.*/", "", $link);
  	$result[] = $link;
  }
   
  # clean up the memory 
  $html->clear();

  return $result;
}

function file_get_contents_curl($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_REFERER, "http://localhost/escraper");
  curl_setopt($ch, CURLOPT_USERAGENT, "Chrome");
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  $output = curl_exec($ch);
  curl_close($ch);
  return $output;
}
?>