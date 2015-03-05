<?php
/**
 * Utilities functions
 * 
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 */

require_once('lib/random_user_agent.php');

 /**
  * Fetches header from url
  *
  * @param string $url  url whose header has to be fetched
  * @return string      url header
  */
  function getUrlHeader($url) {
    # TODO: test it and use it...
    $retry = 0;
    $retryMax = 3;
    $timeout = 12; // timeout for curl execution (seconds)
    $userAgent = random_user_agent();

    retry:
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl: " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      # handle timeouts with some retries
      if ($errno === CURLE_OPERATION_TIMEDOUT) { // timeouts can be probably recovered...
        $retry++;
        #$this->router->log("warning", "timeout executing curl to [$url], retry n." . $retry);
        if ($retry <= $retryMax) {
          goto retry;
        }
      }
      #$this->router->log("error", "can't execute curl to [$url]: " . curl_strerror($errno));
    }
    curl_close($ch);
    return $output;
  }

 /**
  * Fetches contents from url
  *
  * @param string $url  url whose contents have to be fetched
  * @return string      url contents
  */
  function getUrlContents($url) {
    $retry = 0;
    $retryMax = 3;
    $timeout = 120; // timeout for curl execution (seconds)
    $userAgent = random_user_agent();
    #$this->router->log("debug", "getUrlContents($url)");

    retry:
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      throw new Exception("can't initialize curl: " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      # handle timeouts with some retries
      if ($errno === CURLE_OPERATION_TIMEDOUT) { // timeouts can be probably recovered...
        $retry++;
        #$this->router->log("warning", "timeout executing curl to [$url], retry n." . $retry);
        if ($retry <= $retryMax) {
          goto retry;
        }
      }
      #$this->router->log("error", "can't execute curl to [$url]: " . curl_strerror($errno));
    }
    curl_close($ch);
    return $output;
  }

 /**
  * Converts a *localized* date from format
  * "Year MonthName day, hour:minute:second" to UNIX timestamp
  *
  * @param string $date   source date to be converted
  * @return string      UNIX timestamp conevrsion of the given date
  */
  function date_to_timestamp($date) {
    $timestamp = "0";
    for ($m = 1; $m <= 12; $m++) {
      $month_name = ucfirst(strftime("%B", mktime(0, 0, 0, $m)));
      if (strstr($date, $month_name)) {
        $date = str_replace($month_name, $m, $date);
        $date = preg_replace("/^(\d{4})\s+(\d{1,2})\s+(\d{1,2})/", "$1-$2-$3", $date);
        $timestamp = strtotime($date);
        break;
      }
    }
    return $timestamp;
  }

 /**
  * Checks if an array is multi dimensional
  *
  * @param array $array   the arry to be checked
  * @return boolean       true if the array is multi dimensional, false otherwise
  */
  function is_array_multi($array) {
    return (count($array, COUNT_RECURSIVE) > count($array));
  }


?>