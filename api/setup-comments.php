<?php
  $this->commentsDefinitions = [
    "gforum" => [
      "domain" => "gforum.com",
      "charset" => "utf-8",
      "locale" => "en_US.UTF-8",
      "timezone" => "Europe/Rome",
      "url-login" => "http://gforum.com/esc/login/",
      "url-search" => "http://gforum.com/esc/search/",
      "username-field-name" => "user",
      "username" => "billidechid",
      "password-field-name" => "passwrd",
      "password" => "Billi123",
      "login-tag" => "Login",
      "search-tag" => "Search",
      "search-field-name" => "search",
      "patterns" => [
        "login-ok" => "/Ciao, <b>.*?<\/b>/s",
        "search-ok" => "/Risultati.../s", # TODO...
        "comment-link" => "/GET @HOME.../s", # TODO...
        "comment-link-tail" => "/GET @HOME.../s", # TODO...
        "topic" => "/TODO/s",
        "block" => "/TODO/s",
        "author" => "/TODO/s",
        "author-karma" => "/TODO/s",
        "author-posts" => "/TODO/s",
        "date" => "/TODO/s",
        "content" => "/TODO/s",
        "quote-signature" => "/TODO/s",
        "next-link" => "/TODO/s",
      ],
    ],
  ];
?>