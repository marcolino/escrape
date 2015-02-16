<?php

  $inPath = 'api/db/';
  $outPath = 'api/db.json/';
  
  $dh = opendir($inPath);
  while (false !== ($inFile = readdir($dh))) {
  	if ($inFile == ".") continue;
  	if ($inFile == "..") continue;
    $data = unserialize(file_get_contents($inPath . $inFile)) or die("Can't read input");
    $outFile = preg_replace("/s$/", "", $inFile);
    file_put_contents($outPath . $outFile, json_encode($data)) or die("Can't write output");
    echo "written " . $outPath . $outFile . "\n";
  }

?>