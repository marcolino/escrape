<?php
  $this->sitesDefinitions = [
    "sexyguidaitalia" => [
      "url" => "http://www.sexyguidaitalia.com",
      "path" => "escort/torino", # TODO: get path from selected city...
      "charset" => "utf-8",
      "accepts-tor" => true,
      "patterns" => [
        "ban-text" => "/Please complete the security check to access/s",
        "person" => "/<DIV class=\"(?:top|thumbsUp)\".*?>(.*?)<\/DIV>/s",
        "person-id" => "/<div .*?class=\"wraptocenter\">.*?<a href=\".*?\/([^\/\"]+)\".*?>.*?<\/div>/s",
        "person-details-url" => "/<div .*?class=\"wraptocenter\">.*?<a href=\"(.*?)\".*?>.*?<\/div>/s",
        "person-img-url" => "/<div .*?class=\"wraptocenter\">.*?<img .*?src=\"(.*?)\".*?\/>.*?<\/div>/s",
        "person-name" => "/<td id=\"ctl00_content_CellaNome\".*?><span.*?>(.*?)<\/span>.*?<\/td>/s",
        "person-sex" => "/<td id=\"ctl00_content_CellaSesso\".*?>(.*?)<\/td>/s",
        "person-zone" => "/<td id=\"ctl00_content_CellaZona\".*?>(.*?)<\/td>/s",
        "person-description" => "/<td id=\"ctl00_content_CellaDescrizione\".*?>(.*?)(?:\s+)?(?:<br \/>.*?)?<\/td>/s",
        "person-phone" => "/<td id=\"ctl00_content_CellaTelefono\".*?><span.*?>(.*?)<\/span>.*?<\/td>/s",
        "person-phone-vacation" => "/In arrivo dopo le vacanze !!/s",
        "person-phone-unavailable" => "/unavailable/s", # TODO
        "person-nationality" => "/TODO NATIONALITY/s", # TODO
        "person-photo" => "/<a rel='group' class='fancybox' href=(.*?)>.*?<\/a>/s",
      ],
    ],
    "torinoerotica" => [
      "url" => "http://www.torinoerotica.com",
      "charset" => "CP1252",
      "path" => "annunci_Escort_singole_Piemonte_Torino.html", # TODO: get path from selected city...
      "accepts-tor" => true,
      "patterns" => [
        "ban-text" => "/TODO SECURITY CHECK/s", # TODO
        "person" => "/<!-- Inizio Anteprima ...... -->(.*?)<!-- Fine Anteprima ...... -->/s",
        "person-id" => "/<a href=\".*?([^\_]*?)\.html\".*?>.*?<\/a>/s",
        "person-details-url" => "/<a href=\"(.*?)\".*?>.*?<\/a>/s",
        "person-img-url" => "",
        "person-name" => "/<h\d class=\"nome\">\s*(.*?)\s*<\/h\d>/s",
        "person-sex" => "/<h\d class=\"sesso\">\s*(.*?)\s*&nbsp;.*?<\/h\d>/s",
        "person-zone" => "/(?:<a href=\"#quartiere\".*?>(.*?)<\/a>)/s",
        "person-description" => "/<meta name=\"description\".*?content=\"(.*?)\".*?\/>/s",
        "person-phone" => "/<h\d class=\"phone\">\s*(?:<img.*? \/>)\s*(.*?)\s*<\/h\d>/s",
        "person-phone-vacation" => "/TODO/s", # TODO
        "person-phone-unavailable" => "/Questa ...... ha disabilitato temporaneamente il suo annuncio/s",
        "person-nationality" => "/TODO NATIONALITY/s", # TODO
        "person-photo" => "/<a\s+style=\"cursor:pointer;\"\s+href=\"(.*?)\" rel=\"prettyPhoto\[galleria\]\".*?<\/a>/s",
      ],
    ],
    /*
    "escortforumit" => [
      "url" => "http://www.escortforumit.xxx/escorts/city_it_torino",
    ],
    */
    /*
    "dolciincontri" => [
      "url" => "http://www.dolciincontri.net/annunci-personali_torino.html",
    ],
    */
  ];
?>