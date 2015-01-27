<?php

$quotes_recursive_pattern = "/
(?(DEFINE)
  (?<cut>
    ---\ Quote\ from:
    (?&content)*?
    ---\ End\ quote\ ---
  )

  (?<content>
    (?: (?!(?:---\ Quote\ from:|---\ End\ quote\ ---)) . )++
    | (?&cut)
  )
)

(?&cut)
/smx";

  # check timestamp, to decide if page changed 
  $timestamp = get_url_timestamp($details_url);
  if ($timestamp === FALSE) {
    error("can't get site $id person $n timestamp");
    #continue;
  }
  if (isset($data[$details_url]) && ($timestamp <= $data[$details_url][$timestamp])) {
    info("site $id person $n timestamp lower than last one: skip this person page");
    continue;
  } else {
    info("site $id person $n timestamp greater than last one: get this person page");
  }

  function get_url_timestamp($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FILETIME, true);
    $output = curl_exec($ch);
    if ($output === FALSE) {
      curl_close($ch);
      return FALSE;
    }
    $timestamp = curl_getinfo($ch, CURLINFO_FILETIME);
    if ($timestamp === FALSE) {
      curl_close($ch);
      return FALSE;
    }
    curl_close($ch);
    return $timestamp;
  }

?>