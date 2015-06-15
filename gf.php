<?php

  $loginUrl = "http://gnoccaforum.com/escort/login/";
  $user = "trap";
  $password = "nada-trip-cats";
  $loginFieldNameUser = "user";
  $loginFieldNamePassword = "passwrd";

  $ch = curl_init('http://example.com/form_action.php');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, [
    $loginFieldNameUser => $user,
    $loginFieldNamePassword => $password,
  ]);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);

  echo $response;
?>