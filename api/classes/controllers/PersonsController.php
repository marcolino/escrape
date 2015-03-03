<?php
/**
 * Persons controller
 * 
 * @package PersonsController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

# TODO:
#  - normalizePhone(), ... etc ...: in a common class...

class PersonsController {
  private $sitesDefinitions = [
    "sgi" => [
      "url" => "http://www.sexyguidaitalia.com",
      "path" => "escort/torino",
      "charset" => "utf-8",
      "patterns" => [
        /*"persons" => "/<table .*?id=\"ctl00_content_tableFoto\".*?>(.*?)<\/table>/s",*/
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
        "person-photo" => "/<a rel='group' class='fancybox' href=(.*?)>.*?<\/a>/",
      ],
    ],
    "toe" => [
      "url" => "http://www.torinoerotica.com",
      "charset" => "CP1252",
      "path" => "annunci_Escort_singole_Piemonte_Torino.html",
      "patterns" => [
        /*"persons" => "/(.*)/",*/
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
    #$this->db = new DB();
#    $this->setup();
  }

#  private function setup() {
#    $this->load("persons");
#  }
  
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
#INCOGNITO# #DEBUG#
#$url = "http://localhost/escrape/server/debug/sgi.html";
#if ($siteKey != "mor") continue;

      $this->router->log("info", "url: [$url]");
      $page = $this->getUrlContents($url, $site["charset"]);

      if ($page === FALSE) {
        $this->router->log("error", "can't get page contents on site [$siteKey]");
        continue;
      }
/*
      if (preg_match($site["patterns"]["persons"], $page, $matches)) {
        $persons_page = $matches[1];
      } else {
        $this->router->log("error", persons pattern not found on site [$siteKey] [" . $site["patterns"]["persons"] . "]");
        continue;
      }
*/
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

if ($n > 3) break; # TODO: DEBUG-ONLY

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
        if (($page_details = $this->getUrlContents($details_url, $site["charset"])) === FALSE) {
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
          
        if (preg_match_all($site["patterns"]["person-photo"], $page_details, $matches) >= 1) {
          $photosUrls = $matches[1];
        } else {
          $this->router->log("error", "photo pattern not found on site [$siteKey]");
          continue;
        }

        $person = [];
        $person["key"] = $key;
        $person["name"] = $name;
        $person["site_key"] = $siteKey;
        $person["url"] = $details_url;
        $person["timestamp"] = $timestamp;
        $person["sex"] = $sex;
        $person["zone"] = $zone;
        $person["address"] = null;
        $person["description"] = $description;
        $person["phone"] = $phone;
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
    return $this->db->get("person", $id);
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
   * @param  array $filter
   * @return array
   */
  public function getList() {
    $list = [];
    $comments = new CommentsController($this->router);

    foreach ($this->db->getAll("person") as $personId => $person) {
      $list[$personId] = [
        "id" => $person["id"],
        "key" => $person["key"],
        "site_key" => $person["site_key"],
        "name" => $person["name"],
        "phone" => $person["phone"],
        "vote" => $person["vote"],
        "age" => $person["age"],
        "photo_showcase" => $this->photoGetByShowcase($person["id"], true),
        "comments_count" => $comments->countByPerson($person["id"]),
        "comments_average_valutation" => $comments->getAverageValutationByPerson($person["id"]),
      ];
    }
    return $list;
  }

  private function getUrlContents($url, $charset) {
    $user_agent = "Mozilla"; 
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      $this->router->log("error", "can't initialize curl, " . curl_strerror($errno));
      throw new Exception("can't initialize curl, " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      $this->router->log("error", "can't execute curl to [$url], " . curl_strerror($errno));
      throw new Exception("can't execute curl to [$url], " . curl_strerror($errno));
    }
    curl_close($ch);
    return ($charset == "utf-8") ? $output : iconv($charset, "utf-8", $output);
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
    $photo = new Photo([ "url" => $photoUrl ]);

    // check if photo is an exact duplicate
    if ($this->photoCheckDuplication($idPerson, $photo)) {
      $this->router->log("debug", " --- photo " . $photo->url() . " for person id " . $idPerson . " is a duplicate, ignoring");
$this->router->log("debug", "RETURNING FALSE");
      return false; // duplicate found
    }
$this->router->log("debug", "AFTER RETURNING FALSE");
    #$this->router->log("debug", "photoAdd - not a duplicate");

    // check if photo has similarities
    $photo->signature();
    if ($this->photoCheckSimilarity($idPerson, $photo)) {
      $this->router->log("debug", " --- photo " . $photo->url() . " for person id " . $idPerson . " is a similarity, ignoring");
      return false; // duplicate found
    }
    #$this->router->log("debug", "photoAdd - not a similarity");

    $this->router->log("debug", "photoAdd - storing photo, it seems new...");

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
    $this->router->log("debug", "photoAdd() - adding photo n° [$number] to db");
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
    $photos = $this->db->get("photo", $idPerson);
    if (is_array_multi($photos)) { // more than one result returned
      foreach ($photos as $p) {
        if ($p["sum"] === $photo->sum()) { // the checksum matches
          #$this->router->log("debug", "photo " . $photo->url() . " sum is equal to  " . $p["url"] . ", it's duplicate...");
          return true;
        }
      }
    } else { // not more than one result returned
      if ($photos) { // one result returned
        if ($photos["sum"] === $photo->sum()) { // the checksum matches
          #$this->router->log("debug", "photo " . $photo->url() . " sum is equal to  " . $photos["url"] . ", it's duplicate...");
          return true;
        }
      }
    }
    return false;
  }

  private function photoCheckSimilarity($idPerson, $photo) {
    $photos = $this->db->get("photo", $idPerson);
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
   * Check for photo truthfulness
   *
   * @param  string: photo url
   * @return string: "yes"       if photo is unique on the web
   *                 "no"        if photo is not uniqu on the web
   *                 "unknown"   if photo uniqueness on the web is unknown
   *
   * TODO: DOESN'T WORK THIS WAY, CAN'T RELIABLY TELL IF A PHOTO IS THRUTHFUL;
   *       SHOULD JUST RETURN A LIST OF LINKS TO BE SHOWN TO THE USER...
   */
  public function photoCheckThruthfulness($photo) {
    return "unknown";
/*
    #$domain = $photo->domain();
    $similarUrls = $this->photoGoogleSearch();
    if (count($similarUrls) > 0) { // same image found
      return false;
    }
    return true;
*/
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
$this->router->log("debug", "photoStore - personPhotosCount:[$personPhotosCount]");
    $number = $personPhotosCount + 1;
$this->router->log("debug", "photoStore - idPerson:[$idPerson] - personPhotosCount:[$personPhotosCount], number:[$number]");
    $dirname = self::PHOTOS_PATH . $keyPerson . "/";
    $filename = sprintf("%03d", $number);
    $fileext = $photo->type();
    $pathnameFull = $dirname . "full" . "-" . $filename . $fileext;
    $pathnameSmall = $dirname . "small" . "-" . $filename . $fileext;

    // assure photos directory existence
    if (!file_exists($dirname)) {
      if (!mkdir($dirname, 0777, true)) { # TODO: let everybody (developer) to write dir: DEBUG ONLY!
        throw new Exception("can't create folder $dirname");
      }
      $this->router->log("debug", "the directory $dirname has been created");
    } else {
      ; # directory already exists, not the first photo for this person
    }

$this->router->log("debug", "photoStore - storing photo - bitmapFull to $pathnameFull");
    // store the full and small bitmaps to file-system
$this->router->log("debug", "photoStore - bitmapFull size: " . strlen($photo->bitmapFull()));
    if ((file_put_contents($pathnameFull, $photo->bitmapFull())) === false) {
      $this->router->log("error", "can't save photo to file [$pathnameFull]");
      return false;
    }
$this->router->log("debug", "photoStore - storing photo - bitmapSmall to $pathnameSmall");
    if ((file_put_contents($pathnameSmall, $photo->bitmapSmall())) === false) {
      $this->router->log("error", "can't save photo to file [$pathnameSmall]");
      return false;
    }

$this->router->log("debug", "photoStore - storing photo - (new number is $number)");
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

  private function photoGoogleSearch() {
    $maxResults = 9;
    $result = array();
    #$query_encoded = urlencode($query);
    
    $data = $this->getUrlContents(
      "https://www.google.com/searchbyimage" .
      "?site=" . "imghp" .
      "&image_url=" . $this->url .
      "&num=" . $maxResults . 
      "&filter=" . "0"
    );
    $html = str_get_html($data);
     
    foreach($html->find('li.g') as $g) {
      /*
       * each search results are in a list item with a class name "g"
       * we are seperating each of the elements within, into an array;
       * titles are stored within "<h3><a...>{title}</a></h3>";
       * links are in the href of the anchor contained in the "<h3>...</h3>";
       * summaries are stored in a div with a classname of "s"
       */
      $srg = $g->find('div.srg', 0);
      $a = $srg->find('a', 0);
      $link = $a->href;
      # TODO: accept only urls containing the photo...
      #print "link: [$link]\n";
      $link = preg_replace("/^\/url\?q=/", "", $link);
      $link = preg_replace("/&amp;sa=U.*/", "", $link);
      $link = preg_replace("/(?:%3Fwap2$)/", "", $link); # remove wap2 parameter, if any
      $domain = parse_url($link)['host'];
      if ($domain !== $this->domain) { // consider only images from different domains
        $result[] = $link;
      }
    }
     
    # clean up the memory 
    $html->clear();
    
    return $result;
  }

  /**
   * Destructor
   */
  function __destruct() {
  }

  public function test() {
   $photosUrls = [
      "/_Eb1ice0XQzI/S5_N0s_WcvI/AAAAAAAAB9A/P6k2sZ4BRwU/s400/Alice_nel_paese_delle_meraviglie_14.jpg",
      "/-utIwNqmRYMk/UHr9Fuc30LI/AAAAAAAACtg/HYruJverwBE/s1600/brucaliffo.jpg",
    ];
    $person = [];
    $person["key"] = "toe-123456";
    $person["name"] = "Alice";
    $person["site_key"] = "toe";
    $person["url"] = "http://2.bp.blogspot.com";
    $person["timestamp"] = 1424248678;
    $person["sex"] = "F";
    $person["zone"] = "centro";
    $person["address"] = "Via Roma, 0, Torino";
    $person["description"] = "super";
    $person["phone"] = "3336480983";
    $person["page_sum"] = "0cc175b9c0f1b6a831c399e269772661";
    $person["age"] = 27;
    $person["vote"] = 7;

    // add person
    $key = $person["key"];
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
      $this->photoAdd($id, $person["url"] . "/" . $photoUrl);
    }

    return true;
  }

}

/*
  public function putVote($params) { return $this->setVote($params); }
*/

/*
  /**
   * set person vote
   *
   * @param  array $filter
   * @return array
   * /
  public function setVote($params) {
    $id = $params['id'];
    $vote = $params['vote'];

    if (!isset($this->db->data["persons"][$id])) {
      throw new Exception("can't set vote: person id [$id] not found");
    }
    if (!is_numeric($vote) || $vote < 0 || $vote > 1) {
      throw new Exception("can't set vote: vote must be in range [0-1]");
    }

    $this->db->data["persons"][$id]["vote"] = $vote;

    $this->store();

    return [ 'result' => true ];
  }
*/

/*
  public function getSitesDefinitions() {
    $sitesDefinitions = [];
    foreach ($this->sitesDefinitions as $id1 => $value1) {
      foreach ($value1 as $id2 => $value2) {
        if ($id2 != "patterns") {
          $sitesDefinitions[$id1][$id2] = $value2;
        }
      }
    }
    return $sitesDefinitions;
  }
*/

/*
  public function getPersonsByPhone($phone) {
    return array_filter($this->getPersons(), function($item) use ($phone) {
       return $item['phone'] == $phone;
    });
  }

  public function getPersonsByUrl($url) {
    return array_filter($this->getPersons(), function($item) use ($url) {
       return $item['url'] == $url;
    });
  }

  public function getPersonsByDateSpan($dateFrom, $dateTo) {
    return array_filter($this->getPersons(), function($item) use ($dateFrom, $dateTo) {
       $notBefore = $dateFrom ? ($item['timestamp'] >= $dateFrom) : true;
       $notAfter = $dateTo ? ($item['timestamp'] <= $dateTo) : true;
       return $notBefore && $notAfter;
    });
  }

  public function getPersonsByPageSum($sum) {
    return array_filter($this->getPersons(), function($item) use ($sum) {
       return $item['pageSum'] == $sum;
    });
  }
*/

/*
  /**
   * filter list
   *
   * @return array
   * /
  public function filter($list, $filter) {
    if (isset($filter)) {
      if (isset($filter["range"])) {
        foreach ($filter["range"] as $name => $value) {
          $list = array_filter($list, function ($item) use ($name, $value) {
            return (
              !$item[$name] || (
                (!$value["min"] || ($item[$name] >= $value["min"])) &&
                (!$value["max"] || ($item[$name] <= $value["max"]))
              )
            );
          });
        }
      }
    }
    return $list;
  }
*/