<?php

#define("_COOKIE_FILE", __DIR__ . "/../api/logs/curl.cookie");
define("_COOKIE_FILE", "/var/run/tor/control.authcookie");

/**
 * switch TOR to a new identity - TODO: THIS DOESN'T WORK...
 */
function tor_new_identity($tor_ip = '127.0.0.1', $control_port = '9050', $auth_code = '') {
  $fp = fsockopen($tor_ip, $control_port, $errno, $errstr, 30);
  if (!$fp) return false; // can't connect to the control port

#print_r($auth_code); exit;
  fputs($fp, "AUTHENTICATE $auth_code\r\n");
exit;
  $response = fread($fp, 1024);
print_r($response); exit;
  list($code, $text) = explode(' ', $response, 2);
  if ($code != '250') return false; // authentication failed
   
  // send the request to for new identity
  fputs($fp, "signal NEWNYM\r\n");
  $response = fread($fp, 1024);
  list($code, $text) = explode(' ', $response, 2);
  if ($code != '250') return false; // signal failed
   
  fclose($fp);
  return true;
}

/**
 * load the TOR's "magic cookie" from a file and encode it in hexadecimal
 */
function tor_get_cookie($filename) {
  $cookie = file_get_contents($filename);
  // convert the cookie to hexadecimal
  $hex = '';
  for ($i = 0; $i < strlen($cookie); $i++) {
    $h = dechex(ord($cookie[$i]));
    $hex .= str_pad($h, 2, '0', STR_PAD_LEFT);
  }
  return strtoupper($hex);
}

#print _COOKIE_FILE;
#print tor_get_cookie(_COOKIE_FILE);
if (tor_new_identity("127.0.0.1", "9050", tor_get_cookie(_COOKIE_FILE))) {
  echo "Identity switched!";
}

?>
