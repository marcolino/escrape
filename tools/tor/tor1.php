<?php
  $sock = fsockopen( 'unix://control' );
  fwrite( $sock, "AUTHENTICATE\n" );
  echo fread( $sock, 128 );
  fwrite( $sock, "SIGNAL NEWNYM\n" );
  echo fread( $sock, 128 );
?>