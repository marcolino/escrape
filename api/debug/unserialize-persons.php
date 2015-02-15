<?php

  $filePath = 'api/db/persons.db';

  $data = unserialize(file_get_contents($filePath));

  var_dump($data);

?>