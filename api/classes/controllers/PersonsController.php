<?php
/**
 * Persons controller
 * 
 * @package PersonsController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class PersonsController {
  private $sitesDefinitions = [
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
        "person-photo" => "/<a rel='group' class='fancybox' href=(.*?)>.*?<\/a>/",
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
    /*
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
    */
    /*
    "esf" => [
      "url" => "http://www.escortforumit.xxx/escorts/city_it_torino",
    ],
    */
    /*
    "doi" => [
      "url" => "http://www.dolciincontri.net/annunci-personali_torino.html",
      ],
    ],
*/
  ];
  const EMAIL_PATTERN = "/^\S+@\S+\.\S+$/";
  const PHOTOS_PATH = "db/photos/";

  /**
   * Constructor
   */
  function __construct($router) {
    $this->router = $router;
    $this->db = $router->db;
  }

  /**
  * Sync persons
  *
  * @param  array $parameters
  * @return boolean
  */
  public function sync() {
    $this->router->log("info", "sync()");

    $changed = false;
    foreach ($this->sitesDefinitions as $siteKey => $site) {
      $url = $site["url"] . "/" . $site["path"];

      $this->router->log("info", "url: [$url]");
      $page = getUrlContents($url, $site["charset"]);

      if ($page === FALSE) {
        $this->router->log("error", "can't get page contents on site [$siteKey]");
        continue;
      }
      $persons_page = $page;

      if (preg_match_all($site["patterns"]["person"], $persons_page, $matches)) {
        $person_cells = $matches[1];
      } else {
        $this->router->log("error", "not any person pattern found on site [$siteKey]");
        continue;
      }
      
      $n = 0;
      foreach ($person_cells as $person_cell) {
        $n++;

if ($n > 7) break; # TODO: DEBUG-ONLY

        if (preg_match($site["patterns"]["person-id"], $person_cell, $matches) >= 1) {
          $id = $matches[1];
          $key = $siteKey . "-" . $id;
        } else {
          $this->router->log("error", "person $n id not found on site [$siteKey]");
          continue;
        }

        if (preg_match($site["patterns"]["person-details-url"], $person_cell, $matches) >= 1) {
          $details_url = $site["url"] . "/" . $matches[1];
        } else {
          $this->router->log("error", "person $n details url not found on site [$siteKey]");
          continue;
        }

        $this->router->log("debug", $details_url);
        if (($page_details = getUrlContents($details_url, $site["charset"])) === FALSE) {
          $this->router->log("error", "can't get person $n url contents on site [$siteKey]");
          continue;
        }
        $timestamp = time(); # current timestamp, we don't have page last modification date...
        $page_sum = md5($page_details);

        if (preg_match($site["patterns"]["person-name"], $page_details, $matches) >= 1) {
          $name = $this->cleanName($matches[1]);
        } else {
          $this->router->log("error", "person $n name not found on site [$siteKey]");
          continue;
        }
        
        if (preg_match($site["patterns"]["person-sex"], $page_details, $matches) >= 1) {
          $sex = $matches[1];
        } else {
          #$this->router->log("warning", "person $n sex not found on site [$siteKey]");
          $sex = "";
          #continue;
        }

        if (preg_match($site["patterns"]["person-zone"], $page_details, $matches) >= 1) {
          $zone = $matches[1];
        } else {
          #$this->router->log("warning", "person $n zone not found on site [$siteKey]");
          $zone = "";
          #continue;
        }
        
        if (preg_match($site["patterns"]["person-description"], $page_details, $matches) >= 1) {
          $description = $matches[1];
        } else {
          #$this->router->log("warning", "person $n description not found on site [$siteKey]");
          $description = "";
          #continue;
        }
 
        if (preg_match($site["patterns"]["person-phone"], $page_details, $matches) >= 1) {
          $phone = $this->normalizePhone($matches[1]);
        } else {
          $this->router->log("error", "person $n phone not found on site [$siteKey]");
          continue;
        }
          
        if (preg_match($site["patterns"]["person-nationality"], $page_details, $matches) >= 1) {
          $nationality = $this->normalizeNationality($matches[1]);
        } else {
          $this->router->log("error", "person $n nationality not found on site [$siteKey]");
          $nationality = "ru";
          #continue;
        }
          
        if (preg_match_all($site["patterns"]["person-photo"], $page_details, $matches) >= 1) {
          $photosUrls = $matches[1];
        } else {
          $this->router->log("error", "photo pattern not found on site [$siteKey]");
          continue;
        }

        $person = [];
        $person["key"] = $key;
        $person["key_site"] = $siteKey;
        $person["name"] = $name;
        $person["url"] = $details_url;
        $person["timestamp_creation"] = $timestamp; # TODO: do not set if updating...
        $person["timestamp_last_sync"] = $timestamp; # TODO: do not set if updating...
        $person["sex"] = $sex;
        $person["zone"] = $zone;
        $person["address"] = null;
        $person["description"] = $description;
        $person["phone"] = $phone;
        $person["nationality"] = $nationality;
        $person["page_sum"] = $page_sum;
        $person["age"] = null; # age
        $person["vote"] = null; # vote ([0-9])

        if (($p = $this->db->getByField("person", "key", $key))) { # old key, update it
          $id = $p[0]["id"];
          $this->router->log("debug", " °°° updating person: $key °°°");
          $this->set($id, $person);
        } else { # new key, insert it
          $this->router->log("debug", " ^^^ inserting person: $key ^^^");
          $id = $this->add($person);
        }
        // add photos
        foreach ($photosUrls as $photoUrl) {
          $this->photoAdd($id, $site["url"] . "/" . $photoUrl);
        }
      }
    }
    return true;
  }

  public function get($id) {
#print "get($id)\n";
    $person = $this->db->get("person", $id);
    $photos = $this->db->getByField("photo", "id_person", $id);
    $person["photos"] = $photos;
#print " person: "; var_export($person);
    return $person;
  }
  
  public function add($person) {
    return $this->db->add("person", $person);
  }
  
  public function set($id, $person) {
    return $this->db->set("person", $id, $person);
  }

  public function delete($id) {
    return $this->db->delete("person", $id);
  }
  
  /**
   * get persons list
   *
   * @param  array $sieves
   * @return array
   */
  public function getList($sieves) {
    $list = [];
    $comments = new CommentsController($this->router);

    $this->router->log("debug", " *** getList() - sieves:" . var_export($sieves, true));
    #foreach ($this->db->getAll("person") as $personId => $person) {
    foreach ($this->db->getAllSieved("person", $sieves) as $personId => $person) {
      $list[$personId] = [
        "id" => $person["id"],
        "key" => $person["key"],
        "key_site" => $person["key_site"],
        "name" => $person["name"],
        "phone" => $person["phone"],
        "nationality" => $person["nationality"],
        "vote" => $person["vote"],
        "age" => $person["age"],
        "thruthfulness" => "false", # TODO: if at least one photo is !thrustful, person is !thrustful...
        "photo_path_small_showcase" => $this->photoGetByShowcase($person["id"], true)["path_small"],
        "comments_count" => $comments->countByPerson($person["id"]),
        "comments_average_valutation" => $comments->getAverageValutationByPerson($person["id"]),
      ];
    }
    return $list;
  }

  public function photoGetOccurrences($id, $imageUrl) {
    $person = $this->db->get("person", $id);
    $personDomain = $person["url"];

    $googleSearch = new GoogleSearch();
    $maxPages = 2;

    $response = [];
    if ($results = $googleSearch->searchImage($imageUrl, $maxPages)) {
      if ($results["best_guess"]) {
        $response["bestGuess"] = $results["best_guess"];
      }
      if ($results["search_results"]) {
        $response["searchResults"] = [];
        foreach ($results["search_results"] as $result) {
          if (parse_url($result["href"])["host"] !== parse_url($personDomain)["host"]) { // consider only images from different domains
            $response["searchResults"][] = $result;
          }
        }
      } else { // no occurrences found
      }
    }
    return $response;
  }

  private function cleanName($value) {
    $value = preg_replace("/[()]/", "", $value); // ignore not meaningful characters
    $value = preg_replace("/\s+/", " ", $value); // squeeze blanks to one space
    $value = preg_replace("/^\s+/", "", $value); // ignore leading blanks
    $value = preg_replace("/\s+$/", "", $value); // ignore trailing blanks
    #$value = strtoupper($value); // all upper case
    $value = ucfirst(strtolower($value)); // only initials upper case
    return $value;
  }

  private function normalizePhone($phone) {
/*
    if (preg_match($site["patterns"]["person-phone-unavailable"])) {
      $result = "PERSON_UNAVAILABLE"; # TODO: ...
    } else
    if (preg_match($patternEmail)) {
      $result = "EMAIL"; # TODO: ...
    } else {
      $result = preg_replace("/[^\d]*        /", "", $phone);
    }
    return $result;
*/
    $phone = preg_replace("/[^\d]*/", "", $phone); // ignore not number characters
    return $phone;
  }

  private function normalizeNationality($nationality) {
    # TODO: ...
    return "it";
  }

/*
  # TODO: ....
  private function ret($action, $success = true, $response = "") {
    echo json_encode([
      'action' => $action,
      'success' => $success,
      'response' => $response,
    ]);
    #exit;
  }
*/

/*
  private function log($level, $value) {
    $this->router->logs[$level][] = $value;
    print "[$level]: " . $value . "<br>\n"; # TODO: remove this line...
  }
*/

  /**
   * Add a photo
   *
   * @param  integer $idPerson the id of the person's photo
   * @param  string $photoUrl  the url of the photo
   * @return integer: false    photo not added (duplication / similarity)
   *                  >= 0     photo added to filesystem and to database
   */
  public function photoAdd($idPerson, $photoUrl) {
    // build photo object from url
    $photo = new Photo([ "url" => $photoUrl ]); # TODO: can we avoid download with curl "get header only", comparing modification timestamps?

    // check if photo is an exact duplicate
    if ($this->photoCheckDuplication($idPerson, $photo)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $idPerson . " is a duplicate, ignoring");
      return false; // duplicate found
    }

    // check if photo has similarities
    $photo->signature();
    if ($this->photoCheckSimilarity($idPerson, $photo)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $idPerson . " is a similarity, ignoring");
      return false; // similarity found
    }
    $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $idPerson . " SEEMS NEW, ADDING...");

    $showcase = true; # TODO: decide $showcase (flag to denote showcase photo) ...

    $photo->idPerson($idPerson);
    $photo->domain();
    $photo->sum();
    $photo->timestampCreation(time());
    $photo->thruthfulness("unknown"); // this is an offline-set property (it's very expensive to calculate)
    $photo->showcase($showcase);
   
    // store this photo
    if (($number = $this->photoStore($idPerson, $photo)) === false) {
      $this->router->log("error", "photo " . $photo->url() . " for person id " . $idPerson . " could not be stored locally");
      return false; // error storing photo locally
    }
    $photo->number($number);

    // add this photo to database
    #$this->router->log("debug", "photoAdd() - adding photo n° [$number] to db");
    return $this->db->add("photo", $this->photo2Data($photo));
  }

  /**
   * Extract from a Photo object all properties to be stored to database, as array
   *
   * @param  Photo: $photo       the photo object to check for duplication
   * @return array:              the array with all fields to be stored to database
   */
  private function photo2Data($photo) {
    $data = [];
    foreach($photo as $property => $value) {
      if (
        ($property === "id_person") ||
        ($property === "number") ||
        ($property === "url") ||
        ($property === "path_full") ||
        ($property === "path_small") ||
        ($property === "sum") ||
        ($property === "timestamp_creation") ||
        ($property === "signature") ||
        ($property === "showcase") ||
        ($property === "thruthfulness")
      )
      $data[$property] = $value;
    }
    return $data;
  }

  /**
   * Check for photo exact duplication
   *
   * @param  integer $idPerson:  the id of person to check for photo duplication
   * @param  Photo: $photo       the photo object to check for duplication
   * @return boolean: true       if photo is a duplicate
   *                  false      if photo is not a fuplicate
   */
  private function photoCheckDuplication($idPerson, $photo) {
    $photos = $this->db->getByField("photo", "id_person", $idPerson);
    if (is_array_multi($photos)) { // more than one result returned
      foreach ($photos as $p) {
        if ($p["sum"] === $photo->sum()) { // the checksum matches
          #$this->router->log("debug", "photoCheckDuplication(many) - photo " . $photo->url() . " sum is equal to  " . $p["url"] . ", it's duplicate...");
          return true;
        }
      }
    } else { // not more than one result returned
      if ($photos) { // exactly one result returned
        $p = $photos;
        if ($p["sum"] === $photo->sum()) { // the checksum matches
          #$this->router->log("debug", "photoCheckDuplication(one) - photo " . $photo->url() . " sum is equal to  " . $p["url"] . ", it's duplicate...");
          return true;
        }
      }
    }
    return false;
  }

  private function photoCheckSimilarity($idPerson, $photo) {
    $photos = $this->db->getByField("photo", "id_person", $idPerson);
    if (is_array_multi($photos)) { // more than one result returned
      foreach ($photos as $p) {
        $photo2 = new Photo([ "data" => $p ]);
        if ($photo->checkSimilarity($photo2)) {
          #$this->router->log("info", "photo signature " . $photo->url() . " is similar to " . $photo2->url() . ", it's probably a duplicate...");
          return true;
        }
      }
    } else { // not more than one result returned
      if ($photos) { // one result returned
        $photo2 = new Photo([ "data" => $photos ]);
        if ($photo->checkSimilarity($photo2)) {
          #$this->router->log("info", "photo signature " . $photo->url() . " is similar to " . $photo2->url() . ", it's probably a duplicate...");
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Store a photo on local file system
   *
   * @param  integer $idPerson   the id of the person for which to store photo
   * @param  Photo $photo        the photo to be stored
   * @return integer: >= 0       the progressive number of the photo
   */
  public function photoStore($idPerson, $photo) {
    $this->router->log("debug", "photoStore - storing photo");
    $keyPerson = $this->db->get("person", $idPerson)["key"];
    $personPhotosCount = $this->photoGetCount($idPerson);
    $number = $personPhotosCount + 1;
    $dirname = self::PHOTOS_PATH . $keyPerson . "/";
    $filename = sprintf("%03d", $number);
    $fileext = $photo->type();
    $dirnameFull = $dirname . "full" . "/";
    $dirnameSmall = $dirname . "small" . "/";
    $pathnameFull = $dirnameFull . $filename . "." . $fileext;
    $pathnameSmall = $dirnameSmall . $filename . "." . $fileext;

    // assure photos directories existence
    foreach ([ $dirnameFull, $dirnameSmall ] as $d) {
      if (!file_exists($d)) {
        if (!mkdir($d, 0777, true)) { # TODO: let everybody (developer) to write dir: DEBUG ONLY!
          throw new Exception("can't create folder $d");
        }
        $this->router->log("debug", "the directory $d has been created");
      } else {
        ; # directory already exists, not the first photo for this person
      }
    }

    // store the full and small bitmaps to file-system
    if ((file_put_contents($pathnameFull, $photo->bitmapFull())) === false) {
      $this->router->log("error", "can't save photo to file [$pathnameFull]");
      return false;
    }
    if ((file_put_contents($pathnameSmall, $photo->bitmapSmall())) === false) {
      $this->router->log("error", "can't save photo to file [$pathnameSmall]");
      return false;
    }

    // store paths in photo structure
    $photo->pathFull($pathnameFull);
    $photo->pathSmall($pathnameSmall);

    return $number;
  }

  /**
   * Get all photos of person
   *
   * @param  integer $idPerson the id of the person's photo
   * @return array[][]         if photos found
   *         null              if photos not found
   */
  private function photoGetAll($idPerson) {
    $photos = $this->db->get("photo", $idPerson);
    return $photos;
  }

  /**
   * Get a photo of person given it's number
   *
   * @param  integer $idPerson   the id of the person whose photo to load
   * @param  integer $number     the progressive number of the photo
   * @return array   the photo structure
   */
  private function photoGetByNumber($idPerson, $number) {
    $photos = $this->db->getByFields("photo", [ "id_person" => $idPerson, "number" => $number ]);
    return $photos[0];
  }

  /**
   * Get a photo truthfulness
   *
   * @param  string: photo url
   * @return string: "yes"       if photo is unique on the web
   *                 "no"        if photo is not uniqu on the web
   *                 "unknown"   if photo uniqueness on the web is unknown
   */
  public function photoCheckThruthfulness($idPerson, $number) {
    $photo = $this->photoGetByNumber($idPerson, $number);
    return $photo["thruthfulness"];
  }

  /**
   * Get photos of person given their showcase flag
   *
   * @param  integer $idPerson   the id of the person whose photo to load
   * @param  integer $showcase   showcase flag (true / false)
   * @return array   the photo structure
   */
  private function photoGetByShowcase($idPerson, $showcase) {
    $photos = $this->db->getByFields("photo", [ "id_person" => $idPerson, "showcase" => $showcase ]);
    return $photos[0];
  }

  /**
   * Get count of photos of person
   *
   * @param  integer $idPerson the id of the person's photo
   * @return integer           the number of photos of this person
   */
  public function photoGetCount($idPerson) {
    $count = $this->db->countByField("photo", "id_person", $idPerson);
    #$this->router->log("debug", "photoGetCount($idPerson): $count");
    return $count;
  }

  /**
   * Show a photo of person
   *
   * @param  integer $idPerson the id of the person's photo
   * @param  integer $number   the progressive number of the photo in the person's photos collection
   * @param  string $type      the type of the photo:
   *                             - "full"    shows the full version (default)
   *                             - "small"   shows the small version
   * @return void              outputs photo with MIME header
   *
   * TODO: do we need this function, or will always use "<img ns-src='{{person.photo_path}}'>" ?
   */
  public function photoShow($idPerson, $number, $type = "full") {
    $photo = $this->db->getByFields("photo", ["id_person" => $idPerson, "number" => $number ]);
    if (!empty($photo)) {
      header("Content-Type: " . $photo["mime"]);
      switch ($type) {
        default:
        case "full":
          print $photo["bitmap"];
          break;
        case "small":
          print $photo["bitmapSmall"];
          break;
      }
    } else {
      $this->photoShowEmpty();
    }
  }

  private function photoShowEmpty() {
    header("Content-Type: " . "image/jpeg");
    $image = imagecreate(128, 256);
    $background_color = imagecolorallocate($image, 0, 0, 0);
    $text_color = imagecolorallocate($image, 233, 14, 91);
    imagestring($im, 1, 5, 5, "Empty photo", $text_color);
    imagejpeg($image);
    imagedestroy($image);
  }

  /**
   * Destructor
   */
  function __destruct() {
  }



  public function test() {
    $photosUrls[0] = [
      "/scienzefanpage/wp-content/uploads/2013/12/samantha-cristoforetti-futura.jpg",
      "/scienzefanpage/wp-content/uploads/2012/07/donna-italiana-spazio1-300x225.jpg",
    ];
    $person[0] = [];
    $person[0]["key"] = "toe-123456";
    $person[0]["key_site"] = "toe";
    $person[0]["name"] = "Samantha";
    $person[0]["url"] = "http://static.fanpage.it";
    $person[0]["timestamp_creation"] = 1424248678;
    $person[0]["timestamp_last_sync"] = 1424248678;
    $person[0]["sex"] = "F";
    $person[0]["zone"] = "centro";
    $person[0]["address"] = "Via Roma, 3, Milano";
    $person[0]["description"] = "astronauta";
    $person[0]["phone"] = "3336480981";
    $person[0]["nationality"] = "it";
    $person[0]["page_sum"] = "0cc175b9c0f1b6a831c399e269772661";
    $person[0]["age"] = 31;
    $person[0]["vote"] = 8;
    ########################################################################################
    $photosUrls[1] = [
      "/wp-content/gallery/convegno/img_2484.jpg",
      "/wp-content/gallery/convegno/img_2477.jpg",
    ];
    $person[1] = [];
    $person[1]["key"] = "sgi-789012";
    $person[1]["key_site"] = "sgi";
    $person[1]["name"] = "Elena";
    $person[1]["url"] = "http://www.newshd.net";
    $person[1]["timestamp_creation"] = 1424248555;
    $person[0]["timestamp_last_sync"] = 1424248678;
    $person[1]["sex"] = "F";
    $person[1]["zone"] = "centro";
    $person[1]["address"] = "Via Garibaldi 12, Roma";
    $person[1]["description"] = "scienziata";
    $person[1]["phone"] = "3336480982";
    $person[1]["nationality"] = "it";
    $person[1]["page_sum"] = "0cc175b9c0f1b6a831c399e269772662";
    $person[1]["age"] = 42;
    $person[1]["vote"] = 9;
    ########################################################################################

    // add person
    for ($i = 0; $i < sizeof($person); $i++) {
      $key = $person[$i]["key"];
      if (($p = $this->db->getByField("person", "key", $key))) { # old key, update it
        $id = $p[0]["id"];
        $this->router->log("debug", " °°° updating person: $key °°°");
        $this->set($id, $person[$i]);
      } else { # new key, insert it
        $this->router->log("debug", " ^^^ inserting person: $key ^^^");
        $id = $this->add($person[$i]);
      }
  
      // add photos
      foreach ($photosUrls[$i] as $photoUrl) {
        $this->photoAdd($id, $person[$i]["url"] . $photoUrl);
      }
    }

    return true;
  }
}

/*
  # CLI class test
  require_once "classes/services/Network.php";
  require_once "classes/services/GoogleSearch.php";
  $pc = new PersonsController(null);
  $id = 1;
  $imageUrl = "http://www.meteoweb.eu/wp-content/uploads/2013/07/cristoforetti.jpg";
  $result = $pc->photoGetOccurrences($id, $imageUrl);
  var_dump($result);
*/

?>