<?php

  $c = "abc"; // charset
  $c = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";
  $maxlen = strlen($c);
  echo "maxlen: $maxlen\n";

  for ($l = 1; $l <= $maxlen; $l++) { // string length
    for ($t = '', $cl = strlen($c), $s = array_fill(0, $l, 0), $i = pow($cl, $l); $a = 0, $i--; ) {
      for ($t && $t .= ', '; $a < $l; $t .= $c[$s[$a++]]) ;
      for ( ; $a-- && ++$s[$a] == $cl; $s[$a] = 0) ;
    }
    echo $t . "\n";
  }
  
?>