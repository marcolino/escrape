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

 ?>