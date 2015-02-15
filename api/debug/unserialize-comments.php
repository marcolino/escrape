<?php

  $filePath = '../db/comments.db';

  $data = unserialize(file_get_contents($filePath));

  var_dump($data);

?>
