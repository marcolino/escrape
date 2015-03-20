<?php
/**
 * Utilities functions
 * 
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 */

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
  * @param array $array   the array to be checked
  * @return boolean       true if the array is multi dimensional, false otherwise
  */
  function is_array_multi($array) {
    return (count($array, COUNT_RECURSIVE) > count($array));
  }

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

?>