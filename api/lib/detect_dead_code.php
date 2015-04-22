<?php
  $functions = array();
  $path = "/var/www/html/escrape/api/classes";
  define_dir($path, $functions);
  reference_dir($path, $functions);
  echo
    "<table style='font-family:courier'>" .
      "<tr>" .
        "<th>Name</th>" .
        "<th>Defined</th>" .
        "<th>Referenced</th>" .
        "<th>Warning</th>" .
      "</tr>";
  foreach ($functions as $name => $value) {
    echo
      "<tr>" . 
        "<td>" . htmlentities($name) . "</td>" .
        "<td>" . (isset($value[0]) ? count($value[0]) : "-") . "</td>" .
        "<td>" . (isset($value[1]) ? count($value[1]) : "-") . "</td>" .
        "<td>" . (isset($value[0]) && isset($value[1]) && count($value[1]) < count($value[0]) ? "!" : "ok") . "</td>" .
      "</tr>";
  }
  echo "</table>";
  function define_dir($path, &$functions) {
    if ($dir = opendir($path)) {
      while (($file = readdir($dir)) !== false) {
        if (substr($file, 0, 1) == ".") continue;
        if (is_dir($path . "/" . $file)) {
          define_dir($path . "/" . $file, $functions);
        } else {
          if (substr($file, - 4, 4) != ".php") continue;
          define_file($path . "/" . $file, $functions);
        }
      }
    }    
  }
  function define_file($path, &$functions) {
    $tokens = token_get_all(file_get_contents($path));
    for ($i = 0; $i < count($tokens); $i++) {
      $token = $tokens[$i];
      if (is_array($token)) {
        if ($token[0] != T_FUNCTION) continue;
        $i++;
        $token = $tokens[$i];
        if ($token[0] != T_WHITESPACE) die("T_WHITESPACE");
        $i++;
        $token = $tokens[$i];
        if ($token[0] != T_STRING) die("T_STRING");
        $functions[$token[1]][0][] = array($path, $token[2]);
      }
    }
  }
  function reference_dir($path, &$functions) {
    if ($dir = opendir($path)) {
      while (($file = readdir($dir)) !== false) {
        if (substr($file, 0, 1) == ".") continue;
        if (is_dir($path . "/" . $file)) {
          reference_dir($path . "/" . $file, $functions);
        } else {
          if (substr($file, - 4, 4) != ".php") continue;
          reference_file($path . "/" . $file, $functions);
        }
      }
    }    
  }
  function reference_file($path, &$functions) {
    $tokens = token_get_all(file_get_contents($path));
    for ($i = 0; $i < count($tokens); $i++) {
      $token = $tokens[$i];
      if (is_array($token)) {
        if ($token[0] != T_STRING) continue;
        if ($tokens[$i + 1] != "(") continue;
        $functions[$token[1]][1][] = array($path, $token[2]);
      }
    }
  }
?>