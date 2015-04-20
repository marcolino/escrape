<?php
  $this->commentsDefinition = [
    "gnoccaforum" => [
      "domain" => "gnoccaforum.com",
      "charset" => "utf-8",
      "locale" => "it_IT.UTF-8",
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
        "login-tag" => "/(Ciao) <b>.*?<\/b>/s",
        "comment-link" => "/<h5><a href=\".*?\">.*?<\/a> \/ <a href=\"(.*?)\">.*?<\/a><\/h5>/s", 
        "comment-link-tail" => "/^(.*?)\/msg[\d]+\/#msg[\d]+/s", 
        "topic" => "/<td.*?id=\"top_subject\">\s*Topic: (.*?)\s*\(Read \d+ times\)\s*<\/td>/s",
        "block" => "/<table\s+width=\"100%\"\s+cellpadding=\"5\"\s+cellspacing=\"0\"\s+border=\"1\"\s+border-color=\"#cccccc\"\s*>\s+(?:<tbody>)?<tr>(.*?)<\/tr>\s+(?:<\/tbody>)?<\/table>/s",
        "author" => "/<b>(?:<a href=\".*?\" title=\"View the profile of .*?\">)?(.*?)(?:<\/a>)?<\/b><br \/>\s*<span class=\"smalltext\">/s",
        "author-karma" => "/<br \/>\s*Karma:\s*(\+?\d*\/-\d*)\s*<br \/>/s",
        "author-posts" => "/<br \/>\s*Posts:\s*(\d*)\s*<br \/>/s",
        "date" => "/<span class=\"smalltext\">«\s*<b>.*on:<\/b>\s*(.*?)\s*»<\/span>/s",
        "content" => "/(<div class=\"post\">.*?\s*(?:<div class=\"post\">|$))/s",
        "quote-signature" => "/<div class=\"quoteheader\">.*<\/div>(.*)/s",
        "next-link" => "/<b>Pagine:<\/b>.*?\[<strong>\d+<\/strong>\] <a class=\"navPages\" href=\"(.*?)\">\d+<\/a>/s",
      ],
    ],
    /*
    "escort-advisor" => [
      "domain" => "www.escort-advisor.com" . "/Recensioni-Escort/Torino/3208368261",
      ...
    ],
    */
  ];
?>