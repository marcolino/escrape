<?php
  $sitesDefinitions = [
    "linkedin" => [
      "url" => "http://www.linkedin.com",
      "path" => "/",
      "charset" => "utf-8",
      "patterns" => [
        "person" => "/TODO/s",
        "person-id" => "/TODO/s",
        "person-details-url" => "/TODO/s",
        "person-img-url" => "/TODO/s",
        "person-name" => "/TODO/s",
        "person-sex" => "/TODO/s",
        "person-zone" => "/TODO/s",
        "person-description" => "/TODO/s",
        "person-phone" => "/TODO/s",
        "person-phone-vacation" => "/TODO/s",
        "person-phone-unavailable" => "/TODO/s",
        "person-nationality" => "/TODO/s",
        "person-photo" => "/TODO/s",
      ],
    ],
    "facebook" => [
      "url" => "http://www.facebook.com",
      "path" => "/",
      "charset" => "utf-8",
      "patterns" => [
        "person" => "/TODO/s",
        "person-id" => "/TODO/s",
        "person-details-url" => "/TODO/s",
        "person-img-url" => "/TODO/s",
        "person-name" => "/TODO/s",
        "person-sex" => "/TODO/s",
        "person-zone" => "/TODO/s",
        "person-description" => "/TODO/s",
        "person-phone" => "/TODO/s",
        "person-phone-vacation" => "/TODO/s",
        "person-phone-unavailable" => "/TODO/s",
        "person-nationality" => "/TODO/s",
        "person-photo" => "/TODO/s",
      ],
    ],
  ];

/*
  $sitesDefinitions = [
    "sgi" => [
      "url" => "http://www.sexyguidaitalia.com",
      "path" => "escort/torino",
      "charset" => "utf-8",
      "patterns" => [
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
    "toe" => [
      "url" => "http://www.torinoerotica.com",
      "charset" => "CP1252",
      "path" => "annunci_Escort_singole_Piemonte_Torino.html",
      "patterns" => [
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
    / *
    "mor" => [
      "url" => "http://www.moscarossa.biz",
      "charset" => "CP1252",
      "path" => "escort-torino-1.html",
      "patterns" => [
        "person" => "/(?:<td class='evidenzia_accompa_topr'|<tD align=center).*?>(.*?)<\/td>/s",
        "person-id" => "//s",
        "person-details-url" => "/<a class=prova .*?href='(.*?)'.*?>.*?<\/a>/s",
        "person-name" => "/<span class=dettagli1>Nome:<\/span>\s*(.*?)\s*</s",
        "person-sex" => "/nosex/",
        "person-zone" => "/<span class=dettagli1>Citt&agrave;:<\/span>\s*(.*?)\s*</s",
        "person-description" => "/<div .*?class=testo_annuncio2.*?>\s*(.*?)\s*<\/div>/s",
        "person-phone" => "/<span class=dettagli1>Telefono:<\/span>\s*<a.*?>\s*(.*?)\s*<\/a>/s",
        "person-phone-vacation" => "/In arrivo dopo le vacanze !!/s",
        "person-phone-unavailable" => "/In arrivo dopo le vacanze !!/s",
        "person-photo" => "/<a href=\"(fotooggi\/.*?)\">.*?<\/a>/s",
      ],
    ],
    * /
    / *
    "esf" => [
      "url" => "http://www.escortforumit.xxx/escorts/city_it_torino",
    ],
    * /
    / *
    "doi" => [
      "url" => "http://www.dolciincontri.net/annunci-personali_torino.html",
      ],
    ],
    * /
  ];
*/
?>