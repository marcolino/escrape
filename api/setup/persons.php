<?php
  $this->sourcesDefinitions = [
/**/
    "facebook" => [
      "url" => "http://localhost/escrape/api/simulations",
      "path" => "facebook/list.html",
      "charset" => "utf-8",
      "accepts-tor" => false,
      "patterns" => [
        "ban-text" => "/YOU HAVE BEEN BANNED/s",
        "person" => "/<person>(.*?)<\/person>/s",
        "person-id" => "/<span id='(.*?)'>.*?<\/span>/s",
        "person-details-url" => "/<span details-url='(.*?)'><\/span>/s",
        "person-name" => "/<span name='(.*?)'><\/span>/s",
        "person-sex" => "/<span sex='(.*?)'><\/span>/s",
        "person-zone" => "/<span zone='(.*?)'><\/span>/s",
        "person-description" => "/<span description='(.*?)'><\/span>/s",
        "person-phone" => "/<span phone='(.*?)'><\/span>/s",
        "person-phone-vacation" => "/<span phone-vacation='(.*?)'><\/span>/s",
        "person-phone-unavailable" => "/<span phone-unavailable='(.*?)'><\/span>/s",
        "person-nationality" => "/<span nationality='(.*?)'><\/span>/s",
        "person-photo" => "/<span photo='(.*?)'><\/span>/s",
      ],
    ],
/**/
    "twitter" => [
      "url" => "http://localhost/escrape/api/simulations",
      "path" => "twitter/list.html",
      "charset" => "utf-8",
      "accepts-tor" => false,
      "patterns" => [
        "ban-text" => "/YOU HAVE BEEN BANNED/s",
        "person" => "/<person>(.*?)<\/person>/s",
        "person-id" => "/<span id='(.*?)'>.*?<\/span>/s",
        "person-details-url" => "/<span details-url='(.*?)'><\/span>/s",
        "person-name" => "/<span name='(.*?)'><\/span>/s",
        "person-sex" => "/<span sex='(.*?)'><\/span>/s",
        "person-zone" => "/<span zone='(.*?)'><\/span>/s",
        "person-description" => "/<span description='(.*?)'><\/span>/s",
        "person-phone" => "/<span phone='(.*?)'><\/span>/s",
        "person-phone-vacation" => "/<span phone-vacation='(.*?)'><\/span>/s",
        "person-phone-unavailable" => "/<span phone-unavailable='(.*?)'><\/span>/s",
        "person-nationality" => "/<span nationality='(.*?)'><\/span>/s",
        "person-photo" => "/<span photo='(.*?)'><\/span>/s",
      ],
    ],
  ];
?>