<?php

  $data = "3274658342";
  $IDX = "2fc683bad1bf84c487b82fc898e24edb";
  $algos32 = [
    "md2",
    "md4",
    "md5",
    "ripemd128",
    "tiger128,3",
    "tiger128,4",
    "haval128,3",
    "haval128,4",
    "haval128,5",
  ];

  // function to generate and print all N! permutations of $str. (N = strlen($str))
  function permute($str, $i, $n) {
    global $data, $IDX, $algos32;

    if ($i == $n) {
      #print "$str\n";
      $first = true;
      foreach ($algos32 as $algo) {
        $hash = hash($algo, $str . $data, false);
        if ($first) {
          #print "hash: [$hash], salt: [$str]\n";
          print "salt: [$str]\n";
        }
        if ($hash === $IDX) {
          print "\n\nalgo: [$algo], salt: [$str] !!!\n\n";
          exit;
        }
        $first = false;
      }

    } else {
      for ($j = $i; $j < $n; $j++) {
        swap($str, $i, $j);
        permute($str, $i + 1, $n);
        swap($str, $i, $j); // backtrack
      }
    }
  }

  // function to swap the char at pos $i and $j of $str
  function swap(&$str, $i, $j) {
    $temp = $str[$i];
    $str[$i] = $str[$j];
    $str[$j] = $temp;
  }   

  #$charset = " !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";
  $charset = " !#*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz|";
  $maxlen = strlen($charset);
  for ($len = 1; $len <= $maxlen; $len++) {
    $c = substr($charset, 0, $len);
    permute($c, 0, $len);
  }
?>