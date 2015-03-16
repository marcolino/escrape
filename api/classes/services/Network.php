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
$net = new Network();
$url = "http://www.torinoerotica.com/wt_foto!annunci!124646!Anteprime!700x525!top1_8.jpeg";
#$url = "http://whatismyip.org";
#$maxPages = 2;
#$results = $net->searchImage($imageUrl, $maxPages);
print "starting...\n"; flush();
try {
  $results = $net->getUrlContents($url, "utf-8", false, false);
} catch (Exception $e) {
  die("error getting url [$url] with curl: " . $e->getMessage());
}
print "results:\n";
var_dump ($results);
exit;
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
    ];
#    if ($encoding) { // add encoding option
#      $curlOptions = $curlOptions + [
#        CURLOPT_ENCODING => $encoding, // set requested encoding
#      ];
#    }
    if (!$header) { // add header options
      $curlOptions = $curlOptions + [
        CURLOPT_HEADER => 0, // do not return header
        CURLOPT_RETURNTRANSFER => 1, // return contents
      ];
    } else {
      $curlOptions = $curlOptions + [
        CURLOPT_HEADER => 1, // return header
        CURLOPT_RETURNTRANSFER => 0, // do not return contents
      ];
    }

    if ($tor) { // add TOR options
      if (defined("TOR_HOST") && defined("TOR_PORT")) {
        #if ($this->ping(TOR_HOST, TOR_PORT, 1)) { // check TOR server is available
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
      fwrite($log, date("c") . ": " . "starting curl for url [$url]\n");
      $ch = curl_init(); // initialize curl operation
      if (($errno = curl_errno($ch))) {
        throw new Exception("can't initialize curl: " . curl_strerror($errno));
      }
      curl_setopt_array($ch, $curlOptions);

      $data = curl_exec($ch); // start curl operation

      if (($errno = curl_errno($ch))) { // handle timeouts with some retries
        if ($errno === CURLE_OPERATION_TIMEDOUT) { // timeouts can be probably recovered...
          # TODO: ensure timeouts can be recovered, otherwise remove this retries stuff...
          fwrite($log, "\n");
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
    } catch (Exception $e) {
      throw new Exception("error getting url [$url] with curl: " . $e->getMessage());
    }
    curl_close($ch);
    if ($log) {
      fwrite($log, "\n");
      fclose($log);
    }
    return ($charset == "utf-8") ? $data : iconv($charset, "utf-8", $data);
  }

  public function ping($host, $port = 80, $timeout = 3) {
    $fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
    return ($fsock) ? true : false;
  }

}

?>