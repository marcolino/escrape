<?php
/**
 * Persons controller
 * 
 * @package PersonsController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class PersonsController {

  const EMAIL_PATTERN = "/^\S+@\S+\.\S+$/";
  const PHOTOS_PATH = "db/photos/";

  /**
   * Constructor
   */
  function __construct($router) {
    require_once "setup/persons.php"; // persons setup
    $this->router = $router;
    $this->network = new Network();
    $this->db = $router->db;
  }

  /**
   * Get all defined countries in sites
   *
   * @return array: all countries defined
   */
  public function getSitesCountries() {
    $this->router->log("info", "getSitesCountries() ---");
    $countries = [];
    foreach ($this->sitesDefinitions as $siteKey => $site) {
      $url = $site["url"];
      #$this->router->log("debug", "site: " . $siteKey);
      $this->router->log("info", "getUrlContents($url)");
      $page = $this->network->getUrlContents($url, $site["charset"]);
      if ($page === FALSE) {
        $this->router->log("error", "can't get main page on site [$siteKey]");
        continue;
      }
      # TODO: grep countries from site's main page...
      $countries = [ # TODO: ...
        "it" => "Italy",
        "ch" => "Swizerland",
      ];
    }
    #$this->router->log("debug", "countries:" . var_export($countries, true));
    return $countries;
  }

  /**
   * Get all defined cities in sites for specified country code
   *
   * @return array: all cities defined for specified country code
   */
  public function getSitesCities($countryCode) {
    $this->router->log("info", "getSitesCities($countryCode) ---");
    $cities = [];
    foreach ($this->sitesDefinitions as $siteKey => $site) {
      $url = $site["url"];
      #$this->router->log("debug", "site: " . $siteKey);
      $this->router->log("info", "getUrlContents($url)");
      $page = $this->network->getUrlContents($url, $site["charset"]);
      if ($page === FALSE) {
        $this->router->log("error", "can't get main page on site [$siteKey]");
        continue;
      }
      # TODO: grep cities from site's main page...
      $cities[] = [
        "to" => [
          "name" => "Torino",
          "path" => "e...../torino",
        ],
        "mi" => [
          "name" => "Milano",
          "path" => "e...../milano",
        ],
      ]; #"torino" => "annunci_E....._s......_Piemonte_Torino.html",
    }
    #$this->router->log("debug", "cities:" . var_export($cities, true));
    return $cities;
  }

  /**
   * Sync persons
   *
   * @return boolean: true if everything successful, false otherwise
   */
  public function sync() {
    $this->router->log("info", "sync() ---------------------");
    $error = false; // track errors while sync'ing

    foreach ($this->sitesDefinitions as $siteKey => $site) {
      #$useTor = true; // use TOR proxy to sync
      $useTor = $site["accepts-tor"]; // use TOR proxy to sync
      $url = $site["url"] . "/" . $site["path"];
      $this->router->log("debug", "site: " . $siteKey);

      getUrlContents:
      $this->router->log("info", "getUrlContents($url) (TOR: " . ($useTor ? "true" : "false") . ")");
      $page = $this->network->getUrlContents($url, $site["charset"], null, false, $useTor);

      if ($page === FALSE) {
        $this->router->log("error", "can't get page contents on site [$siteKey]");
        continue;
      }
      $persons_page = $page;
      if (preg_match_all($site["patterns"]["person"], $persons_page, $matches)) {
        $person_cells = $matches[1];
      } else {
        if (preg_match($site["patterns"]["ban-text"], $persons_page, $matches) >= 1) {
          if ($useTor) {
            $this->router->log("info", "site [$siteKey] asks a security check; retrying without TOR...");
            $useTor = false;
            goto getUrlContents;
          } else {
            $this->router->log("error", "site [$siteKey] asks a security check even without TOR, giving up with this site");
            $error = true;
            continue;
          }
        }
        $this->router->log("error", "not any person pattern found on site [$siteKey], giving up with this site");
        $error = true;
        continue;
      }
      
      $n = 0;
      foreach ($person_cells as $person_cell) {
        $n++;
if ($n > 24) break; # TODO: DEBUG-ONLY

        if (preg_match($site["patterns"]["person-id"], $person_cell, $matches) >= 1) {
          $id = $matches[1];
          $key = $siteKey . "-" . $id;
        } else {
          $this->router->log("error", "person $n id not found on site [$siteKey], giving up with this person");
          $error = true;
          continue;
        }

        if (preg_match($site["patterns"]["person-details-url"], $person_cell, $matches) >= 1) {
          $details_url = $site["url"] . "/" . $matches[1];
        } else {
          $this->router->log("error", "person $n details url not found on site [$siteKey], giving up with this person");
          $error = true;
          continue;
        }

        $this->router->log("debug", $details_url);
        // TODO: currently $this->network->getUrlContents() does not returns FALSE on error, but throws an exception...
        if (($page_details = $this->network->getUrlContents($details_url, $site["charset"], null, false, $useTor)) === FALSE) {
          $this->router->log("error", "can't get person $n url contents on site [$siteKey], giving up with this person");
          $error = true;
          continue;
        }
        $timestamp = time(); # current timestamp, we don't have page last modification date...
        $page_sum = md5($page_details);

        if (preg_match($site["patterns"]["person-phone"], $page_details, $matches) >= 1) {
          $phone = $this->normalizePhone($matches[1]);
        } else {
          $this->router->log("error", "person $n phone not found on site [$siteKey], giving up with this person");
          $error = true;
          continue;
        }
          
        if (preg_match_all($site["patterns"]["person-photo"], $page_details, $matches) >= 1) {
          $photosUrls = $matches[1];
        } else {
          $this->router->log("error", "photo pattern not found on site [$siteKey], giving up with this person");
          $error = true;
          continue;
        }

        if (preg_match($site["patterns"]["person-name"], $page_details, $matches) >= 1) {
          $name = $this->cleanName($matches[1]);
        } else {
          #$this->router->log("warning", "person $n name not found on site [$siteKey]");
          $name = "";
        }
        
        if (preg_match($site["patterns"]["person-sex"], $page_details, $matches) >= 1) {
          $sex = $matches[1];
        } else {
          #$this->router->log("warning", "person $n sex not found on site [$siteKey]");
          $sex = "";
        }

        if (preg_match($site["patterns"]["person-zone"], $page_details, $matches) >= 1) {
          $zone = $matches[1];
        } else {
          #$this->router->log("info", "person $n zone not found on site [$siteKey]");
          $zone = "";
        }
        
        if (preg_match($site["patterns"]["person-description"], $page_details, $matches) >= 1) {
          $description = $matches[1];
        } else {
          #$this->router->log("warning", "person $n description not found on site [$siteKey]");
          $description = "";
        }
 
        if (preg_match($site["patterns"]["person-nationality"], $page_details, $matches) >= 1) {
          $nationality = $this->normalizeNationality($matches[1]);
        } else {
          #$this->router->log("warning", "person $n nationality not found on site [$siteKey]");
          $nationality = "";
        }
        
        # TODO: add logic to grab this data from person's (or comments) page
        $address = "Via Roma, 123, Torino";
        $age = 28;
        $vote = 7;
        # TODO: add logic to handle 'active' flag
        $active = "yes"; // 'yes' / 'no' / 'syncing' (?)

        $personMaster = [];
        $personMaster["key"] = $key;
        $personMaster["site_key"] = $siteKey;
        $personMaster["url"] = $details_url;
        $personMaster["timestamp_last_sync"] = $timestamp;
        $personMaster["page_sum"] = $page_sum;
        $personDetail = [];
        $personDetail["active"] = $active;
        $personDetail["name"] = $name;
        $personDetail["sex"] = $sex;
        $personDetail["zone"] = $zone;
        $personDetail["address"] = $address;
        $personDetail["description"] = $description;
        $personDetail["phone"] = $phone;
        $personDetail["nationality"] = $nationality;
        $personDetail["age"] = $age;
        $personDetail["vote"] = $vote; # vote ([0-9])

        $id = null;
        if (($p = $this->db->getByField("person", "key", $key))) { # old key, update it
          $id = $p[0]["id"];
          $this->router->log("debug", " °°° updating person: $key °°°");
          $this->set($id, $personMaster, $personDetail); # TODO...
        } else { # new key, insert it
          $this->router->log("debug", " ^^^ inserting person: $key ^^^");
          $personMaster["timestamp_creation"] = $timestamp;
          $id = $this->add($personMaster, $personDetail);
        }
        // add photos
        foreach ($photosUrls as $photoUrl) {
          $this->photoAdd($id, $site["url"] . "/" . $photoUrl);
#break;
        }
        $this->router->log("debug", " === person finished: $key ===");
#break;
      }
#break;
    }

    // sync persons uniqueness after sync completed
    $this->syncPersonsUniqueness();

    return !$error;
  }

  /**
   * Assert persons uniqueness
   */
  public function syncPersonsUniqueness() {
    $this->router->log("info", "assertUniqueness()");
    $persons = $this->db->getPersonListSieved(null);

    # check every couple of persons (avoiding permutations)
    $persons_count = count($persons);
    for ($i = 0; $i < $persons_count - 1; $i++) {
      for ($j = $i + 1; $j < $persons_count; $j++) {
        if (
          ($persons[$i]["name"] === $persons[$j]["name"]) ||
          ($persons[$i]["phone"] === $persons[$j]["phone"])
        ) {
          $this->db->setPersonsUniqcode($persons[$i]["id"], $persons[$j]["id"], true/*, null (?), SYSTEM user... */);
        }          
      }          
    }          
  }

  /**
   * Get persons uniqueness value
   *
   * @return null/boolean  null  if same value is not set (persons are probably not the same)
   *                       true  if same value is set (persons are not the same)
   *                       false if same value is set (persons are the same)
   */
  public function getPersonsUniqueness($personId1, $personId2, $userId = self::DB_SYSTEM_USER_ID) {
    $this->router->log("info", "getPersonsUniqueness()");
    $result = $this->db->getPersonsUniqcode($personId1, $personId2, $userId);
    if (!$result) {
      return null;
    } else {
      return $result["same"];
    }
  }

  /**
   * Set persons uniqueness value
   *
   * @return boolean  true    if value was set successfully
   *                  false   if some error occurred
   */
  public function setPersonsUniqueness($personId1, $personId2, $same, $userId = self::DB_SYSTEM_USER_ID) {
    $this->router->log("info", "setPersonsUniqueness()");
    return $this->db->setPersonsUniqcode($personId1, $personId2, $same, $userId);
  }

# TODO: pass $userId to db functions, in user functions (not in system ones...)

  public function get($id) {
#$this->router->log("info", " +++ get person [$id]: " . var_export($id, true));
#print "get($id)\n";
    $person = $this->db->get("person", $id);
    $photos = $this->db->getByField("photo", "id_person", $id);
    $person["photos"] = $photos;
#print " person: "; var_export($person);
#$this->router->log("info", " !!! person($id): " . var_export($person, true));
    return $person;
  }
  
  # TODO: add $userId...
  public function add($personMaster, $personDetail) {
    return $this->db->add("person", $personMaster, $personDetail);
  }

  public function set($id, $person) {
    return $this->db->set("person", $id, $person);
  }

  public function delete($id) {
    return $this->db->delete("person", $id);
  }
  
  /**
   * get all persons list, filtered with given sieves
   *
   * @param  array $sieves
   * @return array
   */
  public function getListSieved($sieves) {
    #$this->router->log("debug", "getListSieved(); sieves: " . var_export($sieves, true));
    $result = [];
    $comments = new CommentsController($this->router);

    $userId = 2; # TODO: get logged user id (from "authdata"?) ...
    foreach ($this->db->getPersonListSieved($sieves, $userId) as $person) {
      // N.B: here we (could) get multiple records for each person id
      $pid = $person["id_person"];
      if (!isset($result[$pid])) {
        $result[$pid] = []; // initialize this person array in results
      }

      // fields with only "master" table values
      foreach (
        [
          "id_person",
          "key",
          "site_key",
        ] as $field
      ) {
        $result[$pid][$field] = $person[$field];
      }

      // fields with only "detail" table values (they can be multiple)
      foreach (
        [
          "name",
          "phone",
          "nationality",
          "vote",
          "age",
          "thruthful",
        ] as $field
      ) {
        if (isset($person[$field])) {
          if (!isset($result[$pid][$field])) {
            $result[$pid][$field] = $person[$field];
          } else {
            $result[$pid][$field] .= ", " . $person[$field];
          }
          #$this->router->log("debug", "field: <$field> ");
          #$this->router->log("debug", "field: result[$pid][$field] = " . $person[$field]);
        }
      }
      #$this->router->log("debug", "result: " . var_export($result, true));

      // fields "calculated"
      $result[$pid]["thruthful"] = "unknown"; # TODO: if at least one photo is !thrustful, person is !thrustful...
      $result[$pid]["photo_path_small_showcase"] = $this->photoGetByShowcase($pid, true)["path_small"];
      $result[$pid]["comments_count"] = $comments->countByPerson($pid);
      $result[$pid]["comments_average_valutation"] = $comments->getAverageValutationByPerson($pid);
    }
    return $result;
  }

  public function photoGetOccurrences($id, $imageUrl) {
    $person = $this->db->get("person", $id);
    $personDomain = $person["url"];

    $googleSearch = new GoogleSearch();
    $numPages = 2;

    $response = [];
    $response["bestGuess"] = null;
    $response["searchResults"] = [];

    if ($results = $googleSearch->searchImage($imageUrl, $numPages)) {
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

  public function photoGetCardDeck($imageUrls) {
    $photo = new Photo([ "url" => null ]); # TODO: change classto support null url instantiation...
    if (($cardDeck = $photo->photoGetCardDeck($imageUrls)) === false) {
      # TODO: return default card deck, if can't build one
      $this->router->log("debug", " can't create photo card deck, returning default card deck...");
      #$cardDeck = file_get_contents("../app/images/referral-sites/card-deck-default.png"); # TODO: ...
throw new Exception("can't create photo card deck"); # TODO: JUST TO DEBUG!
    }
    return $cardDeck;
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
    // 'normalize' relative urls
    $photoUrl = str_replace("../", "", $photoUrl);

    // build photo object from url
    $photo = new Photo([ "url" => $photoUrl ]);

    $photos = $this->db->getByField("photo", "id_person", $idPerson);

    // check if photo url did not change from last download
    if ($this->photoCheckLastModified($idPerson, $photo, $photos)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $idPerson . " is not changed, ignoring");
      return false; // same Last-Modified tag found
    }

    // check if photo is an exact duplicate
    if ($this->photoCheckDuplication($idPerson, $photo, $photos)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $idPerson . " is a duplicate, ignoring");
      return false; // duplicate found
    }

    // check if photo has similarities
    $photo->signature();

    if ($this->photoCheckSimilarity($idPerson, $photo, $photos)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $idPerson . " is a similarity, ignoring");
      return false; // similarity found
    }
    $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $idPerson . " SEEMS NEW, ADDING...");

    $showcase = true; # TODO: decide $showcase (flag to denote showcase photo) ...

    $photo->idPerson($idPerson);
    $photo->domain();
    $photo->sum();
    $photo->timestampCreation(time());
    $photo->thruthful("unknown"); // this is an offline-set property (it's very expensive to calculate)
    $photo->showcase($showcase);
   
    // store this photo
    if (($number = $this->photoStore($idPerson, $photo)) === false) {
      $this->router->log("error", "photo " . $photo->url() . " for person id " . $idPerson . " could not be stored locally");
      return false; // error storing photo locally
    }
    $photo->number($number);

    // add this photo to database
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
        ($property === "timestamp_last_modification") ||
        ($property === "signature") ||
        ($property === "showcase") ||
        ($property === "thruthful")
      )
      $data[$property] = $value;
    }
    return $data;
  }

  /**
   * Check photo last modification timestamp
   *
   * @param  integer $idPerson:     the id of person to check for photo last modification
   * @param  Photo: $photo          the photo object to check for last modification
   * @param  Photo array: $photos   the photos array of this person
   * @return boolean: true          if photo has not been modified
   *                  false         if photo has been modified (and should be downloaded)
   */
  private function photoCheckLastModified($idPerson, $photo, $photos) {
    $photoLastModificationTimestamp = $photo->getLastModificationTimestamp();
    if ($photos !== []) {
      if (is_array_multi($photos)) { // more than one result returned
        foreach ($photos as $p) {
##$this->router->log("debug", "p[url]:" . $p["url"] . ", photo[url]:" . $photo->url());
##$this->router->log("debug", "p[t_l_p]: (" . $p["timestamp_last_modification"] . ") =?= (" . $photoLastModificationTimestamp . ")");
          if ($p["url"] === $photo->url()) {
##$this->router->log("debug", "p[url]:" . $p["url"] . " FOUNDDDDDDDDDD");
            if ($p["timestamp_last_modification"] && $photoLastModificationTimestamp) {
              if (intval($p["timestamp_last_modification"]) !== $photoLastModificationTimestamp) {
                // the last modification timestamp of existing photo is greater or equal to
                // the last modification timestamp of the photo to be downloaded
                $this->router->log("debug", "photoCheckLastModified: LastModificationTime CHANGED, RE-DOWNLOAD (??? !!!)");
#$this->router->log("debug", " - photoCheckLastModified() RETURNING TRUE");
                return true;
              } else {
                // the last modification timestamp of existing photo did not change
                $this->router->log("debug", "photoCheckLastModified: LastModificationTime did not change, SKIP it £££££");
              }
            } else {
              // the last modification timestamp of existing photo was not already set, new photo
              $this->router->log("debug", "photoCheckLastModified: LastModificationTime NOT SET, NEW PHOTO, DOWNLOAD");
            }
          } else {
            // this is not the photo we are checking
            #$this->router->log("debug", "photoCheckLastModified - photo->url(): " . $photo->url() . ": LastModificationTime not this url...");
          }
        }
      } else { // not more than one result returned
        # TODO: check if this is possible...
        throw new Exception("photoCheckLastModified(): returned one-level array: ".var_export($photos, true)." (SHOULD NOT BE POSSIBLE!!!)");
      }
    }
#$this->router->log("debug", " - photoCheckLastModified() RETURNING FALSE: [$photoLastModificationTimestamp] =?= " . var_export($photos, true));
    return false;
  }

  /**
   * Check for photo exact duplication
   *
   * @param  integer $idPerson:  the id of person to check for photo duplication
   * @param  Photo: $photo       the photo object to check for duplication
   * @return boolean: true       if photo is a duplicate
   *                  false      if photo is not a fuplicate
   */
  private function photoCheckDuplication($idPerson, $photo, $photos) {
    if ($photos !== []) {
      if (is_array_multi($photos)) { // more than one result returned
        foreach ($photos as $p) {
          if ($p["sum"] === $photo->sum()) { // the checksum matches
            #$this->router->log("debug", "photoCheckDuplication(many) - photo " . $photo->url() . " sum is equal to  " . $p["url"] . ", it's duplicate...");
            return true;
          }
        }
      } else { // not more than one result returned
        # TODO: check if this is possible...
        throw new Exception("photoCheckDuplication(): returned one-level array:" . var_export($photos, true));
/*  
        if ($photos) { // exactly one result returned
          $p = $photos;
          if ($p["sum"] === $photo->sum()) { // the checksum matches
            #$this->router->log("debug", "photoCheckDuplication(one) - photo " . $photo->url() . " sum is equal to  " . $p["url"] . ", it's duplicate...");
            return true;
          }
        }
*/  
      }
    }
    return false;
  }

  /**
   * Check for photo similarity
   *
   * @param  integer $idPerson:  the id of person to check for photo similarity
   * @param  Photo: $photo       the photo object to check for similarity
   * @return boolean: true       if photo is similar to some else photo
   *                  false      if photo is not similar to some else photo
   */
  private function photoCheckSimilarity($idPerson, $photo, $photos) {
    if ($photos !== []) {
      if (is_array_multi($photos)) { // more than one result returned
        foreach ($photos as $p) {
          $photo2 = new Photo([ "data" => $p ]);
          if ($photo->checkSimilarity($photo2)) {
            #$this->router->log("info", "photo signature " . $photo->url() . " is similar to " . $photo2->url() . ", it's probably a duplicate...");
            return true;
          }
        }
      } else { // not more than one result returned
        throw new Exception("photoCheckSimilarity(): returned one-level array!" . " SHOULD NOT HAPPEN!!!");
/*  
        if ($photos) { // one result returned
          $photo2 = new Photo([ "data" => $photos ]);
          if ($photo->checkSimilarity($photo2)) {
            #$this->router->log("info", "photo signature " . $photo->url() . " is similar to " . $photo2->url() . ", it's probably a duplicate...");
            return true;
          }
        }
*/  
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
    #$this->router->log("debug", "photoStore - storing photo");

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
        #$this->router->log("debug", "the directory $d has been created");
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
    return $photo["thruthful"];
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
$this->router->log("debug", "test START");
    $photosUrls[0] = [
      "/scienzefanpage/wp-content/uploads/2013/12/samantha-cristoforetti-futura.jpg",
      "/scienzefanpage/wp-content/uploads/2012/07/donna-italiana-spazio1-300x225.jpg",
    ];
    $personMaster[0] = [];
    $personMaster[0]["key"] = "linkedin-123456";
    $personMaster[0]["site_key"] = "linkedin";
    $personMaster[0]["url"] = "http://static.fanpage.it";
    $personMaster[0]["timestamp_last_sync"] = 1424248678;
    $personMaster[0]["page_sum"] = "0cc175b9c0f1b6a831c399e269772661";
    $personDetail[0] = [];
    $personDetail[0]["active"] = "no";
    $personDetail[0]["name"] = "Samantha";
    $personDetail[0]["sex"] = "F";
    $personDetail[0]["zone"] = "centro";
    $personDetail[0]["address"] = "Via Roma, 3, Milano";
    $personDetail[0]["description"] = "astronauta";
    $personDetail[0]["phone"] = "3336480981";
    $personDetail[0]["nationality"] = "it";
    $personDetail[0]["age"] = 31;
    $personDetail[0]["vote"] = 8;
    ########################################################################################
    $photosUrls[1] = [
      "/wp-content/gallery/convegno/img_2484.jpg",
      "/wp-content/gallery/convegno/img_2477.jpg",
    ];
    $personMaster[1] = [];
    $personMaster[1]["key"] = "twitter-789012";
    $personMaster[1]["site_key"] = "facebook";
    $personMaster[1]["url"] = "http://www.newshd.net";
    $personMaster[1]["timestamp_last_sync"] = 1424248678;
    $personMaster[1]["page_sum"] = "0cc175b9c0f1b6a831c399e269772662";
    $personDetail[1] = [];
    $personDetail[1]["active"] = "yes";
    $personDetail[1]["name"] = "Elena";
    $personDetail[1]["sex"] = "F";
    $personDetail[1]["zone"] = "centro";
    $personDetail[1]["address"] = "Via Garibaldi 12, Roma";
    $personDetail[1]["description"] = "scienziata";
    $personDetail[1]["phone"] = "3336480982";
    $personDetail[1]["nationality"] = "it";
    $personDetail[1]["age"] = 42;
    $personDetail[1]["vote"] = 9;
    ########################################################################################

    // add person
    for ($i = 0; $i < sizeof($personMaster); $i++) {
      $key = $personMaster[$i]["key"];
      if (($p = $this->db->getByField("person", "key", $key))) { # old key, update it
        $id = $p[0]["id"];
        $this->router->log("debug", " °°° updating person: $key °°°");
        $this->set($id, $personMaster[$i], $personDetail[$i]);
      } else { # new key, insert it
        $this->router->log("debug", " ^^^ inserting person: $key ^^^");
        $person["timestamp_creation"] = 1423000000;
        $id = $this->add($personMaster[$i], $personDetail[$i]);
      }
  
      // add photos
      foreach ($photosUrls[$i] as $photoUrl) {
        $this->photoAdd($id, $personMaster[$i]["url"] . $photoUrl);
      }
    }

$this->router->log("debug", "test OK");
    return true;
  }

  public function testuniqcode() {
    $userId = 2; # TODO...
    $p1 = 1;
    $p2 = 2;

    $this->router->log("debug", "getPersonsUniqcode($p1, $p2, $userId)");
    $result = $this->db->getPersonsUniqcode($p1, $p2, $userId);
    $this->router->log("debug", " Xresult: " . var_export($result, true));

    $this->router->log("debug", "setPersonsUniqcode($p1, $p2, true)");
    $result = $this->db->setPersonsUniqcode($p1, $p2, true, $userId);
    $this->router->log("debug", " Xresult: " . var_export($result, true));

    $this->router->log("debug", "getPersonsUniqcode($p1, $p2, $userId)");
    $result = $this->db->getPersonsUniqcode($p1, $p2, $userId);
    $this->router->log("debug", " Xresult: " . var_export($result, true));

    $this->router->log("debug", "setPersonsUniqcode($p1, $p2, false)");
    $result = $this->db->setPersonsUniqcode($p1, $p2, false, $userId);
    $this->router->log("debug", " Xresult: " . var_export($result, true));

    $this->router->log("debug", "getPersonsUniqcode($p1, $p2, $userId)");
    $result = $this->db->getPersonsUniqcode($p1, $p2, $userId);
    $this->router->log("debug", " Xresult: " . var_export($result, true));
    return true;
  }
}

?>