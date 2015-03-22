<?php

require_once __DIR__ . "/../../lib/random_user_agent.php";

define("COOKIE_FILE",      __DIR__ . "/../../logs/curl.cookie");
define("LOG_FILE",         __DIR__ . "/../../logs/curl.log"); # TODO: use only while developing...
define("TOR_HOST",         "127.0.0.1"); // TOR host
define("TOR_PORT",         "9050"); // TOR port
define("TIMEOUT_CONNECT",  10); # TODO: increase on production... (?)
define("TIMEOUT_RESPONSE", 10); # TODO: increase on production... (?)
define("TIMEOUT_MOVED",     3);
define("RETRIES_MAX",       3); # TODO: increase on production... (?)
define("REDIRECTS_MAX",     9);

/*
# DEBUG: testing! ####################################################
$url_sg = "http://www.sexyguidaitalia.com/public/23836/copertina.jpg?t=635625002387860017";
$url_te = "http://www.torinoerotica.com/wt_foto!annunci!114646!Anteprime!700x525!mmmmmmmmmmmmm.jpeg";

$n = new Network();

$timestamp = $n->getLastModificationTimestampFromUrl($url_sg);
print "Last modification timestamp from [$url_sg]: [$timestamp]" . "\n";

$timestamp = $n->getLastModificationTimestampFromUrl($url_te);
print "Last modification timestamp from [$url_te]: [$timestamp]" . "\n";

exit;
######################################################################
*/

class Network {

  function __construct() {
    if (defined(COOKIE_FILE)) {
      @unlink(COOKIE_FILE); // clear cookies before we start
    }
    if (defined(LOG_FILE)) {
      @unlink(LOG_FILE); // clear curl log
    }
  }

  /**
   * Get url contents
   *
   * @param  string $url        url to be retrieved
   * @param  string $charset    charset of url content
   * @param  array $post        post data array, if needed (otherwise a GET is issued)
   * @param  boolean $header    flag to get only header from url
   * @param  boolean $tor       flag to use TOR proxy
   * @return string             returned content
   */
  public function getUrlContents($url, $charset = null, $post = null, $header = false, $tor = true) {
    $log = fopen(LOG_FILE, "a+"); // curl session log file
    $curlOptions = [
      CURLOPT_ENCODING => "gzip,deflate", // automatically decode the response if it's gzipped
      CURLOPT_AUTOREFERER => 1, // automatically update the referer header
      CURLOPT_CONNECTTIMEOUT => TIMEOUT_CONNECT, // timeout on connect
      CURLOPT_TIMEOUT => TIMEOUT_RESPONSE, // timeout on response
      CURLOPT_URL => $url, // the url to get
      CURLOPT_SSL_VERIFYPEER => 0, // do not try to verify SSL peer
      CURLOPT_SSL_VERIFYHOST => 0, // do not try to verify SSL host
      CURLOPT_FOLLOWLOCATION => 1, // follow location if "503 Moved" is returned
      CURLOPT_MAXREDIRS => REDIRECTS_MAX, // maximum number of redirects
      CURLOPT_USERAGENT => random_user_agent(), // use a random user agent
      CURLOPT_COOKIEFILE => COOKIE_FILE, // set cookie file
      CURLOPT_COOKIEJAR => COOKIE_FILE, // set cookie jar
      CURLOPT_STDERR => $log, // log session
      CURLOPT_VERBOSE => 1, // produce a verbose log
      CURLINFO_HEADER_OUT => 1, // get info about the transfer
      CURLOPT_RETURNTRANSFER => 1, // return contents
    ];

    if (!$header) { // add header options
      $curlOptions = $curlOptions + [
        CURLOPT_HEADER => 0, // do not return header
        #CURLOPT_RETURNTRANSFER => 1, // return contents # TODO: ????????????????????
        CURLOPT_NOBODY => 0,
      ];
    } else {
      $curlOptions = $curlOptions + [
        CURLOPT_HEADER => 1, // return header
        #CURLOPT_RETURNTRANSFER => 0, // do not return contents # TODO: ????????????????????
        CURLOPT_NOBODY => 1,
      ];
    }

    if ($tor) { // add TOR options
      if (defined("TOR_HOST") && defined("TOR_PORT")) {
        #if ($this->ping(TOR_HOST, TOR_PORT)) { // check TOR server is available
          // TOR server is requested, defined and available, let's use it
          $curlOptions += [
            CURLOPT_PROXY => "http://" . TOR_HOST . ":" . TOR_PORT . "/", // use TOR proxy
            CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5, // TOR is a SOCKS5 type proxy
          ];
        #}
      }
    }

    if ($post) { // add post options
      $curlOptions = $curlOptions + [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
      ];
    }

    $retry = 0;
    retry:
    try {
      fwrite($log, date("c") . ": " . "starting curl for url [$url] (tor: " . ($tor ? "TRUE" : "FALSE") . ")\n");
      $ch = curl_init(); // initialize curl operation
      if (($errno = curl_errno($ch))) {
        throw new Exception("can't initialize curl: " . curl_strerror($errno));
      }
      curl_setopt_array($ch, $curlOptions);

      $data = curl_exec($ch); // start curl operation

      if (($errno = curl_errno($ch))) { // handle timeouts with some retries
        if ($errno === CURLE_OPERATION_TIMEDOUT) { // timeouts can be probably recovered...
          # TODO: ensure timeouts can be recovered, otherwise remove this retries stuff...
          $retry++;
          fwrite($log, date("c") . ": " . "timeout executing curl to [$url], retry n. $retry\n");
          if ($retry < RETRIES_MAX) {
            goto retry;
          } else {
            throw new Exception("timeout retries exhausted executing curl to [$url]");
          }
        }
        fwrite($log, date("c") . ": " . "can't execute curl to [$url]: " . curl_strerror($errno) . "\n");
        throw new Exception("can't  execute curl to [$url]: " . curl_strerror($errno));
      }
      curl_close($ch);
      if ($log) {
        fwrite($log, "---\n");
        fclose($log);
      }
    } catch (Exception $e) {
      throw new Exception("error getting url [$url] with curl: " . $e->getMessage());
    }
    return (!$charset || $charset === "utf-8") ? $data : iconv($charset, "utf-8", $data);
  }

  /**
   * Get image contents (issue a getUrlContents() without TOR)
   *
   * @param  string $url        url to be retrieved
   * @return string             image contents
   */
  public function getImageFromUrl($url) {
    $retval = $this->getUrlContents($url, null, null, false, false);
    if (!$retval) { # TODO: check content of image... (HTML, for example...)
      $type = "empty";
      throw new Exception("error getting image url [$url] with curl: " . "image content is " . $type);
    }
    return $retval;
  }

  /**
   * Get image 'Last-Modified' header value
   *
   * @param  string $url        url to be retrieved
   * @return null               no last modification timestamp in header
   *         integer            last modification timestamp
   */
  public function getLastModificationTimestampFromUrl($url) {
    $headers = $this->getUrlContents($url, null, null, true, false);
    $headers = explode("\n", trim($headers));
    $lastModifiedDate = null;
    $timestamp = null;
    foreach ($headers as $line) {
      if (strtok($line, ":") === "Last-Modified") {
        $parts = explode(":", $line);
        array_shift($parts);
        $lastModifiedDate = trim(implode(":", $parts));
      }
    }
    if ($lastModifiedDate) {
      $lastModifiedDate = trim($lastModifiedDate);
      $timestamp = http_date_to_timestamp($lastModifiedDate);
    }
    return $timestamp;
  }

  /**
   * Get mime type of url content
   *
   * @param  string $url        url to be retrieved
   * @return string             mime type
   */
  public function getMimeFromUrl($url) {
    $headers = $this->getUrlContents($url, null, null, true, false);
    $headers = explode("\n", trim($headers));
    $mime = null;
    foreach ($headers as $line) {
      if (strtok($line, ":") === "Content-Type") {
        $parts = explode(":", $line);
        $mime = trim($parts[1]);
        break;
      }
    }
    return $mime;
  }

  /**
   * Pings a host:port to check for it's presence
   *
   * @param  string $host       host name to be pinged
   * @param  integer $port      port to be pinged (default: 80)
   * @param  integer $timeout   maximum time to wait before failing, in seconds (default: 3)
   * @return booleal            true    if host:port did answer
   *                            false   if host:port did not answer
   */
  public function ping($host, $port = 80, $timeout = 3) {
    $fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
    return ($fsock) ? true : false;
  }

}

?>