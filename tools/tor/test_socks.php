<?php
  include("socks5.lib.php");
  
  $server_name = "www.google.com";
  $server_port = 80;
  $socks = new socks5("127.0.0.1", 3128)
  
  if ($socks->connect($server_name, $server_port)) {
    $headers[] = "GET / HTTP/1.1";
    $headers[] = "Host: $server_name:$server_port";
    $packet = join("\r\n", $headers)."\r\n\r\n";
    $response = $socks->send($packet);
    echo $response;
  }

  exit;
?>