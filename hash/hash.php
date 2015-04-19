<?php
  @set_time_limit(0); //This might be useful. Searching might take too long!

  require_once("brute_force.class.php");
  
  #$set = [ "!", "\"", "#", "$", "%", "&", "'", "(", ")", "*", "+", ",", "-", ".", "/", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ":", ";", "<", "=", ">", "?", "@", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "[", "\\", "]", "^", "_", "`", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "{", "|", "}", "~" ];
  #$set = [ "!", "#", "*", "+", ",", "-", ".", "/", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ":", ";", "=", "?", "@", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "[", "_", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "{", "|", "}", "~" ];
  $set = [ " ", "!", "#", "-", ".", ":", "=", "G", "F", "g", "f" ];

  #$data = "3274658342";
  #$IDX = "2fc683bad1bf84c487b82fc898e24edb";
  
  $data = "3511319576";
  $IDX = "d03dc86f4f03bffe396816b5e9a6140b";
  
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

  foreach ([
"IDX",
"idx",
"IDX ",
"idx ",
"IDX-",
"idx-",
"IDX:",
"idx:",
"IDX+",
"idx+",
"IDX.",
"idx.",
"INDEX",
"index",
"INDEX ",
"index ",
"INDEX-",
"index-",
"INDEX:",
"index:",
"INDEX+",
"index+",
"INDEX.",
"index.",
""
    ] as $salt) {
    $first = true;
    foreach (hash_algos() as $algo) {
      $hash = hash($algo, $salt . $data, false);
      if ($first) {
        print "IDX: [" . substr($IDX, 0, 5) . "], hash: [" . substr($hash, 0, 5) . "], salt: [$salt]\n";
      }
      if (substr($hash, 0, 5) === substr($IDX, 0, 5)) {
        print "\n\nalgo: [$algo], salt: [$salt] !!!\n\n";
        return true; // terminate brute force
      }
      $hash = hash($algo, $salt . (intval($data) * intval($data)), false);
      if ($first) {
        print "IDX: [" . substr($IDX, 0, 5) . "], hash: [" . substr($hash, 0, 5) . "], salt: [$salt]\n";
      }
      if (substr($hash, 0, 5) === substr($IDX, 0, 5)) {
        print "\n\nalgo: [$algo], salt: [$salt] !!!\n\n";
      }
      $first = false;
    }
  }

  exit;



  function callback($arg1, $i) {
    global $data, $IDX, $algos32;

    #echo $i . ": " . $arg1 . "\n";
    $first = true;
    foreach ($algos32 as $algo) {
      $hash = hash($algo, $arg1 . $data, false);
      if ($first) {
        #print "hash: [$hash], salt: [$arg1]\n";
        print "salt: [$arg1]\n";
      }
      if ($hash === $IDX) {
        print "\n\nalgo: [$algo], salt: [$arg1] !!!\n\n";
        return true; // terminate brute force
      }
      $first = false;
    }
    return false; // continue with brute force
  }
  
  $brute_force = new brute_force("callback", 1, count($set), $set);
  if (!$brute_force->errormsg()) {
    /*
      false indicates that searching would not be terminated by the callback function;
      true indicates that process will terminate whenever callback function returns true
    */
    $brute_force->callback_break = true;
    $brute_force->search();
  } else {
    echo "Brute Force error: " . $brute_force->errormsg() . "\n";
  }
?>