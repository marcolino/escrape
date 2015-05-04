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

#  foreach (hash_algos() as $a) { 
#    $h = hash($a, $data, false);
#    if (strlen($h) == 32)
#      print $a . "\n";
#  }
#  exit;

  function gen_nos(&$set, &$results) {
  	global $data, $IDX, $algos32;

    for ($i = 0; $i < count($set); $i++) {
      $results[] = $set[$i];
      $tempset = $set;
      array_splice($tempset, $i, 1);
      $tempresults = array();
      gen_nos($tempset, $tempresults);
      foreach ($tempresults as $res) {
        $results[] = $set[$i] . $res;
        $salt = $set[$i] . $res;
        foreach ($algos32 as $algo) {
          $hash = hash($algo, $salt . $data, false);
          if ($algo === "md5") print "hash: [$hash], salt: [$salt]\n";
          if ($hash === $IDX) {
            print "\n\n !!! algo: [$algo], salt: [$salt] !!!\n\n";
            exit;
          }
        }
      }
    }
  }

  $results = array();
  #$set = [ "!", "\"", "#", "$", "%", "&", "'", "(", ")", "*", "+", ",", "-", ".", "/", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ":", ";", "<", "=", ">", "?", "@", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "[", "\\", "]", "^", "_", "`", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "{", "|", "}", "~" ];
  $set = [ "!", "#", "*", "+", ",", "-", ".", "/", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ":", ";", "=", "?", "@", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "[", "_", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "{", "|", "}", "~" ];
  $set = [ " ", "!", "#", "-", ".", ":", "=", "G", "F", "g", "f" ];
  gen_nos($set, $results);

#  foreach (hash_algos() as $a) { 
#    $h = hash($a, $data, false);
#    printf("%-12s %3d %s\n", $a, strlen($h), $h);
#    if (substr($h, 0, 3) === substr($IDX, 0, 3)) {
#      print "\n\n***** $a *****\n\n";
#    }
#  }
#  printf("\n%-12s %3d %s\n", "IDX from GF", strlen($IDX), $IDX); 
?> 