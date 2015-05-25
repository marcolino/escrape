<?php

require_once __DIR__ . "/../../lib/random_user_agent.php";

define("_COOKIE_FILE", __DIR__ . "/../../logs/curl.cookie");
define("_LOG_FILE", __DIR__ . "/../../logs/curl.log");

# DEBUG: testing! ####################################################
/*
date_default_timezone_set("Europe/Rome");

$url = "http://www.motorcycle.com/images/babes/babes_justene_00.jpg";
$n = new Network();
$bitmap = $n->getImageFromUrl($url);
print $bitmap;
*/
/*
$timestamp = $n->getLastModificationTimestampFromUrl($url_sg);
print "Last modification timestamp from [$url_sg]: [$timestamp]" . "\n";

$timestamp = $n->getLastModificationTimestampFromUrl($url_te);
print "Last modification timestamp from [$url_te]: [$timestamp]" . "\n";
*/
/*
exit;
*/
######################################################################

class Network {

  const COOKIE_FILE =      _COOKIE_FILE;
  const LOG_FILE =         _LOG_FILE; # TODO: use only while developing...
  const TOR_HOST =         "127.0.0.1"; // TOR host
  const TOR_PORT =         "9050"; // TOR port
  const TIMEOUT_CONNECT =  10; # TODO: increase on production... (?)
  const TIMEOUT_RESPONSE = 10; # TODO: increase on production... (?)
  const TIMEOUT_MOVED =     3;
  const RETRIES_MAX =       3; # TODO: increase on production... (?)
  const REDIRECTS_MAX =     9;

  function __construct() {
    if (defined(self::COOKIE_FILE)) {
      @unlink(self::COOKIE_FILE); // clear cookies before we start
    }
    if (defined(self::LOG_FILE)) {
      @unlink(self::LOG_FILE); // clear curl log
    }
    $this->log = null;
    #$this->logInit();
    # TODO: HOW DO EXCEPTIONS PROPAGATE UP, FROM THIS CLASS?
    #       IF THEY DO NOT PROPAGATE WELL, REQUEST A router HERE, TO LOG AS OTHER CLASSES...
  }

  /**
   * Get url contents
   *
   * @param  string $url            url to be retrieved
   * @param  string $charset        charset of url content
   * @param  array $post            post data array, if needed (otherwise a GET is issued)
   * @param  boolean $header        flag to get only header from url
   * @param  boolean $tor           flag to use TOR proxy
   * @param  string &$contentType   content type of url content (reference);
   * @return string                 returned content
   */
  public function getUrlContents($url, $charset = null, $post = null, $header = false, $tor = true, &$contentType = null) {
    $curlOptions = [
      CURLOPT_ENCODING => "gzip,deflate", // automatically decode the response if it's gzipped
      CURLOPT_AUTOREFERER => 1, // automatically update the referer header
      CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_CONNECT, // timeout on connect
      CURLOPT_TIMEOUT => self::TIMEOUT_RESPONSE, // timeout on response
      CURLOPT_URL => $url, // the url to get
      CURLOPT_SSL_VERIFYPEER => 0, // do not try to verify SSL peer
      CURLOPT_SSL_VERIFYHOST => 0, // do not try to verify SSL host
      CURLOPT_FOLLOWLOCATION => 1, // follow location if "503 Moved" is returned
      CURLOPT_MAXREDIRS => self::REDIRECTS_MAX, // maximum number of redirects
      CURLOPT_USERAGENT => random_user_agent(), // use a random user agent
      CURLOPT_COOKIEFILE => self::COOKIE_FILE, // set cookie file
      CURLOPT_COOKIEJAR => self::COOKIE_FILE, // set cookie jar
      CURLOPT_VERBOSE => 1, // produce a verbose log
      CURLINFO_HEADER_OUT => 1, // get info about the transfer
      CURLOPT_RETURNTRANSFER => 1, // return contents
    ];

    if (!$header) { // add header options
      $curlOptions = $curlOptions + [
        CURLOPT_HEADER => 0, // do not return header
        CURLOPT_NOBODY => 0, // do return header
      ];
    } else {
      $curlOptions = $curlOptions + [
        CURLOPT_HEADER => 1, // return header
        CURLOPT_NOBODY => 1, // do not return header
      ];
    }

    if ($tor) { // add TOR options
      if (defined(self::TOR_HOST) && defined(self::TOR_PORT)) {
        #if ($this->ping(self::TOR_HOST, self::TOR_PORT)) { // check TOR server is available
          // TOR server is requested, defined and available, let's use it
          $curlOptions += [
            CURLOPT_PROXY => "http://" . self::TOR_HOST . ":" . self::TOR_PORT . "/", // use TOR proxy
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
      #$this->logWrite("curl to [$url]" . ($tor ? " (TOR)" : ""));
      $ch = curl_init(); // initialize curl operation
      if (($errno = curl_errno($ch))) {
        #$this->logWrite("can't initialize curl: " . curl_strerror($errno));
        throw new Exception("can't initialize curl: " . curl_strerror($errno));
      }
      curl_setopt_array($ch, $curlOptions);

      $data = curl_exec($ch); // start curl operation

      if (($errno = curl_errno($ch))) { // handle timeouts with some retries
        if ($errno === CURLE_OPERATION_TIMEDOUT) { // timeouts can be probably recovered...
          # TODO: ensure timeouts can be recovered, otherwise remove this retries stuff...
          $retry++;
          if ($retry < self::RETRIES_MAX) {
            #$this->logWrite("timeout executing curl to [$url], retry n. $retry");
            goto retry;
          } else {
            #$this->logWrite("timeout executing curl to [$url], retry n. $retry, throwing exception");
            throw new Exception("timeout retries exhausted executing curl to [$url]");
          }
        }
        #$this->logWrite("can't execute curl to [$url]: " . curl_strerror($errno));
        throw new Exception("can't execute curl to [$url]: " . curl_strerror($errno));
      }
      $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      curl_close($ch);
      if ($charset && $charset !== "utf-8") {
        $data = iconv($charset, "utf-8", $data);
      }
      return $data;
    } catch (Exception $e) {
      throw new Exception("error getting url [$url] with curl: " . $e->getMessage());
    }
  }

  /**
   * Get image contents (issue a getUrlContents() without TOR)
   *
   * @param  string $url        url to be retrieved
   * @return string             image contents
   */
  public function getImageFromUrl($url, &$contentType = null) {
    $contentType = "?"; // not null, to force it's valorization
    $retval = $this->getUrlContents($url, null, null, false, false, $contentType);
    $mimeType = null;
    if (!$retval) {
      $mimeType = "empty";
      #$this->logWrite("error getting image url [$url] with curl: " . "image content is " . $mimeType);
      throw new Exception("error getting image url [$url] with curl: " . "image content is " . $mimeType);
    }
    $mimeType = $contentType;
    # TODO: remove mime type checking from here, and keep in the caller... (to check for 404 errors, for example...)
    #if (!preg_match("/^image\//s", $mimeType)) {
    #  $this->logWrite("error getting image url [$url] with curl: " . "image content is " . $mimeType);
    #  throw new Exception("error getting image url [$url] with curl: " . "image content is " . $mimeType . ", content is: \"$retval\"");
    #}
    $this->mime = $mimeType; // set object mime with detected mime type
    return $retval;
  }

/*
  # TODO: THIS FUNCTION SHOULD BE USELESS...
  / **
   * Get image 'Last-Modified' header value
   *
   * @param  string $url        url to be retrieved
   * @return null               no last modification timestamp in header
   *         integer            last modification timestamp
   * /
  public function getLastModificationTimestampFromUrl($url) {
    #$this->logWrite("getLastModificationTimestampFromUrl()");
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
*/

/*
  # TODO: THIS FUNCTION SHOULD BE USELESS...
  / **
   * Get mime type of url content
   *
   * @param  string $url        url to be retrieved
   * @return string             mime type
   * /
  public function getMimeFromUrl($url) {
    #$this->logWrite("getMimeFromUrl()");
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
*/
  
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

/*
  private function logInit() {
    if (!$this->log) { // check it's not already initialized
      if (null !== self::LOG_FILE) {
        if (($log = @fopen(self::LOG_FILE, "a+")) !== false) { // curl session log file
          $this->log = $log;
#die("LOG FILE: " . self::LOG_FILE . " OPENED");
          return true;
        }
      }
      #$this->log = @fopen("/dev/null", "a+"); // curl session log file to null device
    }
    return false;
  }

  private function logWrite() {
    if ($this->log) {
      $num_args = func_num_args(); // we accept a variable number of arguments, of any type
      $args = func_get_args();
      $msg = "";
      for ($n = 0; $n < $num_args; $n++) {
        $arg = array_shift($args);
        switch (gettype($arg)) {
          case "string":
            $msg .= $arg;
            break;
          case "integer":
          case "double":
            $msg .= "$arg";
            break;
          case "NULL":
            $msg .= "NULL";
            break;
          default:
            $msg .= var_export($arg, true);
            break;
        }
      }
      fwrite($this->log, date("c") . ": " . $msg . "\n");
    }
  }

  private function logClose() {
    if ($this->log) {
      fclose($this->log);
    }
  }
*/

  /**
   * Destructor
   */
  function __destruct() {
    #$this->logClose();
  }

}

?>