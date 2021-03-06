<?php
/**
 * Utilities functions
 * 
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 */

 /**
  * Converts a *http* date from format
  * "Weewkday, Year MonthName day hour:minute:second TIMEZONE" to UNIX timestamp
  *
  * @param string $date   source date to be converted
  * @return string        UNIX timestamp conevrsion of the given date
  */
  function http_date_to_timestamp($date) {
    # "Fri, 13 Mar 2015 11:36:24 GMT";
    list($wday, $date) = explode(",", $date);
    $date = trim($date);
    list($day, $monthname, $year, $hms, $tz) = explode(" ", $date);
    $month = date("m", strtotime($monthname));
    list($hour, $minute, $second) = explode(":", $hms);
    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
    return $timestamp;
  }

 /**
  * Converts a *localized* date from format
  * "Year MonthName day, hour:minute:second" to UNIX timestamp
  *
  * @param string $date   source date to be converted
  * @return string        UNIX timestamp conevrsion of the given date
  */
  function date_to_timestamp($date) {
    # "2015 Marzo 27 12:24:55";
    $timestamp = "0";
    $date1 = $date;
    for ($m = 1; $m <= 12; $m++) {
      $month_name = ucfirst(strftime("%B", mktime(0, 0, 0, $m)));
      if (strstr($date, $month_name)) {
        $date1 = str_replace($month_name, $m, $date1);
        $date1 = preg_replace("/^(\d{4})\s+(\d{1,2})\s+(\d{1,2})/", "$1-$2-$3", $date1);
        $timestamp = strtotime($date1);
        break;
      }
    }
    return $timestamp;
  }

/* TODO: we don't use this anymore: backup and remove this function
 / **
  * Checks if an array is multi dimensional
  *
  * @param array $array   the array to be checked
  * @return boolean       true  if the array is multi dimensional
  *                       false otherwise
  * /
  function is_array_multi($array) {
    return (count($array, COUNT_RECURSIVE) > count($array));
  }
*/

  /**
   * Returns the sign of a number;
   * if the absolute value of the number is very low (even not exactly 0, 0 is returned.
   *
   * @param number $number   the number
   * @return integer          1  if the number is positive
   *                         -1  if the number is negative
   *                          0  if the number is (close to) zero
   */
  function sign($number) {
    return abs($number) >= 0.00001 ? abs($number) / $number : 0;
  }

  /**
   * Converts any kind of object/variable to it's string representation.
   *
   * @param mixed $mixed      the object/variable
   * @return string           the object/variable's string representation
   */
  function any2string ($mixed) {
    return var_export($mixed, true);
  }

/**
  * Checks if a string represents an absolute url (http / https)
  *
  * @param string $string   the string to be checked
  * @return boolean         true  if the string represents an absolute url (http / https),
  *                         false otherwise 
  */
  function is_absolute_url($string) {
    return preg_match("/^https?:\/\//", $string);
  }

/**
  * Checks if a string ends with another on
  *
  * @param string $string   the whole string
  * @param ending $string   the string the whole string should be ending with
  * @return boolean         true  if the whole string ends with the ending string
  *                         false otherwise 
  */
  function ends_with($string, $ending) {
    $strlen = strlen($string);
    $endinglen = strlen($ending);
    if ($endinglen > $strlen) {
      return false;
    }
    return substr_compare($string, $ending, $strlen - $endinglen, $endinglen) === 0;
  }

/**
  * Gets server current external IP address
  *
  * @return string         current IP address
  *                        null if some error occurred (i.e.: no connectivity present)
  */
  function external_ip() {
    $externalContent = @file_get_contents("http://checkip.dyndns.com/");
    preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $matches);
    $externalIp = null;
    if (isset($matches[1])) {
      $externalIp = $matches[1];
    }
    return $externalIp;
  }

/**
 * Iterative binary search
 *
 * @param  array $list     the sorted array
 * @param  int   $target   the target integer to search
 * @return int             the index of the target key if found, otherwise -1 
 */
  function binary_search($list, $target) {
    $left = 0;
    $right = count($list) - 1;
  
    while ($left <= $right) {
      $mid = ($left + $right) / 2;
      
      if ($list[$mid] == $target) {
        return $mid;
      } elseif ($list[$mid] > $target) {
        $right = $mid - 1;
      } elseif ($list[$mid] < $target) {
        $left = $mid + 1;
      }
    }
  
    return -1;
  }

?>