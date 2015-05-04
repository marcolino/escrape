<?php
/**
 * Persons controller
 * 
 * @package PersonsController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

# TODO: ONLY TO DEBUG ###############################################
# $pc = new PersonsController(null);
# $description = "una ragazza francesina molto carina";
# $nationality = $pc->detectNationality($description, "it");
# die("Nationality [$nationality] was detected for [$description]");
#####################################################################

class PersonsController {
  #const EMAIL_PATTERN = "/^\S+@\S+\.\S+$/";
  const PHOTOS_PATH = "db/photos/";
  const TIMEOUT_BETWEEN_DOWNLOADS_MIN = 1;
  const TIMEOUT_BETWEEN_DOWNLOADS_MAX = 3;

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
   * Sync persons
   *
   * @param  boolean $fullSync   if true sync all persons, do not skip old persons (default: false)
   * @return boolean:            true if everything successful, false otherwise
   */
  public function sync($fullSync = false) {
    $this->router->log("info", "sync() ---------------------");
    $timestampStart = time();
    $error = false; // track errors while sync'ing

    foreach ($this->sourcesDefinitions as $sourceKey => $source) {
#if ($sourceKey === "sexyguidaitalia") continue; # currently almost offline
      #$useTor = true; // use TOR proxy to sync
      $useTor = $source["accepts-tor"]; // use TOR proxy to sync
      # TODO: handle country / city / category (instead of a fixed path)
      $url = $source["url"] . "/" . $source["path"];
      $this->router->log("debug", "source: " . $sourceKey);

      getUrlContents:
      $this->router->log("info", "getUrlContents($url) (TOR: " . ($useTor ? "true" : "false") . ")");
      # TODO: try ... catch ... (here and for all Network public methods...)
      $page = $this->network->getUrlContents($url, $source["charset"], null, false, $useTor);
      if ($page === FALSE) {
        $this->router->log("error", "can't get page contents on source [$sourceKey]");
        continue;
      }
      $persons_page = $page;
      if (preg_match_all($source["patterns"]["person"], $persons_page, $matches)) {
        $person_cells = $matches[1];
      } else {
        if (preg_match($source["patterns"]["ban-text"], $persons_page, $matches) >= 1) {
          if ($useTor) {
            $this->router->log("info", "source [$sourceKey] asks a security check; retrying without TOR...");
            $useTor = false;
            goto getUrlContents;
          } else {
            $this->router->log("error", "source [$sourceKey] asks a security check even without TOR, giving up with this source");
            $error = true;
            continue;
          }
        }
        $this->router->log("error", "not any person pattern found on source [$sourceKey], giving up with this source");
        $error = true;
        continue;
      }
      
      $n = 0;
      foreach ($person_cells as $person_cell) {
        $n++;
#if ($n > 32) break; # TODO: DEBUG-ONLY

        if (preg_match($source["patterns"]["person-id"], $person_cell, $matches) >= 1) {
          $id = $matches[1];
          $key = $sourceKey . "-" . $id;
#if ($id !== "adv2945") {$this->router->log("info", "[$id] !== adv2945"); continue; } #################
#if ($id === "adv2945") {$this->router->log("info", "[$id] === adv2945, FOUND !!!"); } #############
        } else {
          $this->router->log("error", "person $n ($key) id not found on source [$sourceKey], giving up with this person");
          $error = true;
          continue;
        }

        # check if key is new or not ####################################################
        $personId = null;
        if (($person = $this->db->getByField("person", "key", $key))) { # old key
          $personId = $person[0]["id"];
          $this->router->log("debug", "old person: $key (id: $personId)");
          if (!$fullSync) { // requested to sync only new keys, skip this old key
            continue;
          }
        } else {
          $this->router->log("debug", "new person: $key (id: $id)");
        }

        if (preg_match($source["patterns"]["person-details-url"], $person_cell, $matches) >= 1) {
          $detailsUrl = $source["url"] . "/" . $matches[1];
        } else {
          $this->router->log("error", "person $n ($key) details url not found, giving up with this person");
          $error = true;
          continue;
        }

        #$this->router->log("debug", "person details url: [$detailsUrl]");
        # TODO: currently $this->network->getUrlContents() does not returns FALSE on error, but throws an exception...
        # TODO: try ... catch ...
        if (($page_details = $this->network->getUrlContents($detailsUrl, $source["charset"], null, false, $useTor)) === FALSE) {
          $this->router->log("error", "person $n ($key) url contents not found, giving up with this person");
          $error = true;
          continue;
        }

        if (strlen($page_details) <= 0) {
          $this->router->log("error", "person $n ($key) details page is empty, giving up with this person");
          $error = true;
          continue;
        }

        $pageSum = md5($page_details);

        if (preg_match($source["patterns"]["person-phone"], $page_details, $matches) >= 1) {
          list($phone, $activeLabel) = $this->normalizePhone($matches[1], $sourceKey);
        } else {
          $this->router->log("error", "person $n ($key) phone not found, giving up with this person");
          $error = true;
          continue;
        }
          
        if (preg_match_all($source["patterns"]["person-photo"], $page_details, $matches) >= 1) {
          $photosUrls = $matches[1];
        } else {
          $this->router->log("error", "person $n ($key) photo pattern not found, giving up with this person");
          $error = true;
          continue;
        }

        if (preg_match($source["patterns"]["person-name"], $page_details, $matches) >= 1) {
          $name = $this->normalizeName($matches[1]);
        } else {
          #$this->router->log("warning", "person $n name not found on source [$sourceKey]");
          $name = "";
        }
        
        if (preg_match($source["patterns"]["person-sex"], $page_details, $matches) >= 1) {
          $sex = $matches[1];
        } else {
          #$this->router->log("warning", "person $n sex not found on source [$sourceKey]");
          $sex = "";
        }

        if (preg_match($source["patterns"]["person-zone"], $page_details, $matches) >= 1) {
          $zone = $matches[1];
        } else {
          #$this->router->log("info", "person $n zone not found on source [$sourceKey]");
          $zone = "";
        }
        
        if (preg_match($source["patterns"]["person-description"], $page_details, $matches) >= 1) {
          $description = $matches[1];
        } else {
          #$this->router->log("warning", "person $n description not found on source [$sourceKey]");
          $description = "";
        }
 
        if (preg_match($source["patterns"]["person-nationality"], $page_details, $matches) >= 1) {
          $nationality = $this->normalizeNationality($matches[1]);
        } else {
          #$this->router->log("warning", "person $n nationality not found on source [$sourceKey]");
          #$nationality = "";
          $nationality = $this->detectNationality($name, $description, $this->router->cfg["sourcesCountryCode"]);
        }
        
        # TODO: add logic to grab this data from person's (or comments) page
        $address = "";
        $age = null;
        $vote = null;
        $timestampNow = time(); // current timestamp, sources usually don't set page last modification date...

        $personMaster = [];
        #$personMaster["key"] = $key;
        $personMaster["source_key"] = $sourceKey;
        $personMaster["url"] = $detailsUrl;
        $personMaster["timestamp_last_sync"] = $timestampNow;
        $personMaster["page_sum"] = $pageSum;
        if ($activeLabel !== null) { $personMaster["active_label"] = $activeLabel; } // if active flag is null, do not set it
        $personDetail = [];
        $personDetail["name"] = $name;
        $personDetail["sex"] = $sex;
        $personDetail["zone"] = $zone;
        $personDetail["address"] = $address;
        $personDetail["description"] = $description;
        if ($phone !== null) { $personDetail["phone"] = $phone; } // if phone is null, do not set it
        $personDetail["nationality"] = $nationality;
        $personDetail["age"] = $age;
        $personDetail["vote"] = $vote;
        $personDetail["uniq_prev"] = null;
        $personDetail["uniq_next"] = null;

        if ($personId) { # old key, update it
          #$this->router->log("debug", "UPDATING ");        
          # TODO: remember old values someway (how???), before updating (old zone, old phone, ...)?
          $this->set($personId, $personMaster, $personDetail);
        } else { # new key, insert it
          #$this->router->log("debug", "INSERTING ");
          $personMaster["key"] = $key; // set univoque key only when adding person
          $personMaster["timestamp_creation"] = $timestampNow; // set current timestamp as creation timestamp
          $personId = $this->add($personMaster, $personDetail);

          foreach ($photosUrls as $photoUrl) { // add photos
            if (is_absolute_url($photoUrl)) { // absolute photo url
              $photoUrl = str_replace("../", "", $photoUrl); // 'normalize' relative urls
#$this->router->log("debug", "PersonsController::sync() - ABSOLUTE PHOTO URL: " . $photoUrl);
            } else { // relative photo url
              $photoUrl = $source["url"] . "/" . $photoUrl;
#$this->router->log("debug", "PersonsController::sync() - RELATIVE PHOTO URL: " . $photoUrl);
            }
            $this->photoAdd($personId, $photoUrl);
          }
        }
        # TODO: REALLY DO NOT ADD PHOTOS FOR OLD KEYS/PERSONS ???
        #       ADDING PHOTOS ONLY FOR NEW KEYS/PERSONSWE IS WAY FASTER,
        #       BUT WE COULD MISS SOME NEW / CHANGED / REMOVED PHOTO...
        #       (NO, REMOVED PHOTOS ARE HOWEVER (CORRECTLY) KEPT IN DATABASE).
        # IDEA: WE COULD CHECK DETAILS PAGE MD5: IF NOT CHANGED, *PROBABLY*
        #       PHOTOS DIDN'T CHANGE (FOR SURE NO PHOTO WAS ADDED, BUT IT REMOTELY
        #       COULD BE CHANGED...).
        #// add photos
        #foreach ($photosUrls as $photoUrl) {
        #  $this->photoAdd($id, $source["url"] . "/" . $photoUrl);
        #}
        $this->router->log("debug", "---");
#break;
      }
#break;
    }

    if ($fullSync) { // full sync was requested
      # TODO: DO WE NEED THIS GLOBAL VARIABLE, OR WE JUST USE $timestampStart IN FOLLOWING assertPersonsActivity()?
      #// full sync done, set start timestamp to global table field 'last_sync_full'
      #$this->db->setByField("global", "last_sync_full", $timestampStart);

      // assert persons activity after full sync completed, if we get no error
      //  (to avoid marking all persons as not-active when a source is not available...)
      if (!$error) {
        $this->assertPersonsActivity($timestampStart);
      }
    }

    // assert persons uniqueness after sync completed
    $this->assertPersonsUniqueness();

    return !$error;
  }

  /**
   * Assert persons activity: compare person's last sync'd timestamp with a given timestamp
   *  ("timestampStart" parameter), the timestamp last (or this) sync started.
   */
  private function assertPersonsActivity($timestampStart) {
    $this->router->log("info", "asserting persons activity (setting active / inactive flag to persons based on timestamp_last_sync)");
    #$this->router->log("info", "timestamp of last sync start: " . date("c", $timestampStart));
    foreach ($this->db->getPersons(/* no sieve, */ /* no user: system user */) as $person) {
      #$this->router->log("info", " person " . $person["key"] . " - active_label: [" . $person["active_label"] . "]");
      if ($person["active_label"] === "0") {
        $active = false;
        #$this->router->log("info", " person " . $person["key"] . "(" . $person["name"] . ")" . " - active_label = false, forcing active status: active: " . ($active ? "1" : "0"));
      } else {
        // set activity flag based on the time of last sync for this person, compared to the time of last full sync (just done)
        $timestampLastSyncPerson = $person["timestamp_last_sync"];
        $active = ($timestampLastSyncPerson >= $timestampStart);
        #$this->router->log("info", " person " . $person["key"] . "(" . $person["name"] . ")" . " - last sync: $timestampLastSyncPerson - active: " . ($active ? "1" : "0"));
      }
      $this->db->setPerson($person["id"], [ "active" => $active ? 1 : 0 ]);
    }
  }

  /**
   * Assert persons uniqueness
   */
  public function assertPersonsUniqueness() {
    $this->router->log("info", "asserting persons uniqueness (checking for field matching for every couple of persons)");
    $persons = $this->db->getPersons(null);

    # build an array of persons indexed by id, instead then by a progressive counter
    $persons_count = count($persons);
    for ($i = 0; $i < $persons_count; $i++) { // build a persons-by-id array
      $personsById[$persons[$i]["id"]] = $persons[$i];
    }

    # check every couple of persons (avoiding permutations)
    for ($i = 0; $i < $persons_count - 1; $i++) {
      #$this->router->log("info", "i: [$i]");
      for ($j = $i + 1; $j < $persons_count; $j++) {
        #$this->router->log("info", "j: [$j]");
        if (
          personsCheckUniquenessByPhone($persons[$i], $persons[$j]) ||
          personsCheckUniquenessByPhotos($persons[$i], $persons[$j])
        ) { // these two persons are unique
          $id1 = $persons[$i]["id_person"];
          $id2 = $persons[$j]["id_person"];

          $this->router->log("info", "assertPersonsUniqueness() - found 'uniq' persons: " . $persons[$i]['key'] . " and " . $persons[$j]['key']);
          #$this->router->log("info", "- phone(id1): " . $persons[$i]["phone"]);
          #$this->router->log("info", "- phone(id2): " . $persons[$j]["phone"]);

          // follow next unique chain, and add the 2nd person id as additional unique (next) person
          # TODO: do we need double quotes for numeric ndices in arrays?
          $idNext = $personsById[$id1]["uniq_next"];
          while ($idNext && $personsById[$idNext]["uniq_next"]) {
            $idNext = $personsById[$idNext]["uniq_next"]; // TODO: avoid possibly infinite loops...
            #$this->router->log("info", " w1 - idNext: [$idNext] - phone: " . $persons[$idNext]["phone"]);
          }
          $id = $idNext ? $idNext : $id1;
          if ($personsById[$id]["uniq_next"] !== $id2) {
            $personsById[$id]["uniq_next"] = $id2;
            $this->db->setPerson($id, null, [ "uniq_next" => $id2 ]); // save uniq_next id to person
          }

          // follow prev unique chain, and add the 1nd person id as additional unique (prev) person
          $idPrev = $personsById[$id2]["uniq_prev"];
          while ($idPrev && $personsById[$idPrev]["uniq_prev"]) {
            $idPrev = $personsById[$idPrev]["uniq_prev"]; // TODO: avoid possibly infinite loops...
            #$this->router->log("info", " w2 - idPrev: [$idPrev] - phone: " . $persons[$idPrev]["phone"]);
          }
          $id = $idPrev ? $idPrev : $id2;
          if ($personsById[$id]["uniq_next"] != $id1) {
            $personsById[$id]["uniq_prev"] = $id1;
            $this->db->setPerson($id, null, [ "uniq_prev" => $id1 ]); // save uniq_prev id to person
          }
        }
      }
    }
  }

  /**
   * get all persons, filtered with given data sieves
   *
   * @param  array $data
   * @return array
   */
  public function getAll($sieves = null, $userId = null) {
    $result = [];
    $comments = new CommentsController($this->router);

    $persons = $this->db->getPersons($sieves, $userId);
    foreach ($persons as $person) {
      // N.B: here we (could) get multiple records for each person id
      $personId = $person["id_person"];
      if (!isset($result[$personId])) {
        $result[$personId] = []; // initialize this person array in results
      } else { # TODO: should never happen, we select grouping by id_person...
        throw new Exception("Assertion failed: getAll(): (isset(\$result[\$personId]), personId: $personId, " . any2string($result[$personId])); # TODO: JUST TO DEBUG!
      }
      
      // store each person by it's person id ("id" field is relative to details table)
      $result[$personId] = $person;

      // fields "calculated"
      //$result[$personId]["thruthful"] = "unknown"; # TODO: if at least one photo is !thrustful, person is !thrustful...
      $result[$personId]["photo_path_small_showcase"] = $this->photoGetByShowcase($personId, true)["path_small"];
      $result[$personId]["comments_count"] = $comments->countByPhone($person["phone"]);
      $result[$personId]["comments_average_rating"] = $comments->getAverageRating($personId);
    }
    return $result;
  }

  public function get($id, $userId = null) {
    $person = $this->db->getPerson($id, $userId);
    $photos = $this->db->getByField("photo", "id_person", $id, $userId);
    $person["photos"] = $photos;
    return $person;
  }
  
  public function getByPhone($phone, $userId = null) {
    if (!$phone) {
      return [];
    }
    return $this->db->getPersonByField("phone", $phone, $userId);
  }
  
  public function add($personMaster, $personDetail = null, $userId = null) {
    # TODO: how to avoid this stupid test? (i.e.: how not to pass a parameter to a function, to let it use it's default?)
    if ($userId) {
      return $this->db->addPerson($personMaster, $personDetail, $userId);
    } else {
      return $this->db->addPerson($personMaster, $personDetail);
    }
  }

  public function set($id, $personMaster, $personDetail = null, $userId = null) {
    # TODO: how to avoid this stupid test? (i.e.: how not to pass a parameter to a function, to let it use it's default?)
    if ($userId) {
      return $this->db->setPerson($id, $personMaster, $personDetail, $userId);
    } else {
      return $this->db->setPerson($id, $personMaster, $personDetail);
    }
  }

  public function delete($id, $userId = null) {
    return $this->db->deletePerson($id, $userId);
  }
  
  public function getPhotoOccurrences($id, $imageUrl) {
#$this->router->log("debug", "getPhotoOccurrences --- id: $id");
    $person = $this->db->getPerson($id);
    $personDomain = $person["url"];
#$this->router->log("debug", "getPhotoOccurrences --- person: " . any2string($person));

    $googleSearch = new GoogleSearch();
    $numPages = 3;

    $response = [];
    $response["bestGuess"] = null;
    $response["searchResults"] = [];

    if ($results = $googleSearch->searchImage($imageUrl, $numPages)) {
      if ($results["best_guess"]) {
        $response["bestGuess"] = $results["best_guess"];
      }
      if ($results["search_results"]) {
        #$response["searchResults"] = [];
        foreach ($results["search_results"] as $result) {
          if (parse_url($result["href"])["host"] !== parse_url($personDomain)["host"]) { // consider only images from different domains
            $response["searchResults"][] = $result;
          }
        }
      } else { // no occurrences found
      }
    }
    unset($googleSearch);
    return $response;
  }

  private function normalizeName($value) {
    // if name starts with all upperchars, keep only upperchars
    if (
      ctype_upper(substr($value, 0, 1)) &&
      ctype_upper(substr($value, 1, 1))
    ) {
      $value = preg_replace("/^([A-Z0-9'-]*).*/", "$1", $value); // ignore characters after upperchars
    }
    $value = preg_replace("/[()]/", "", $value); // ignore not meaningful characters
    $value = preg_replace("/\s+/", " ", $value); // squeeze blanks to one space
    $value = preg_replace("/^\s+/", "", $value); // ignore leading blanks
    $value = preg_replace("/\s+$/", "", $value); // ignore trailing blanks
    //$value = ucfirst(strtolower($value)); // only initial upper case
    $value = ucwords(strtolower($value)); // only initials upper case
    return $value;
  }

  private function normalizePhone($phone, $sourceKey) {
    $source = $this->sourcesDefinitions[$sourceKey];
    if (preg_match($source["patterns"]["person-phone-vacation"], $phone)) {
      $phone = "";
      $activeLabel = "0";
    } else {
      if (preg_match($source["patterns"]["person-phone-unavailable"], $phone)) {
        $phone = "";
        $activeLabel = "0";
      } else {
        $phone = preg_replace("/[^\d]*/", "", $phone); // ignore not number characters
        $activeLabel = "1";
      }
    }
    return [ $phone, $activeLabel ];
  }

  private function normalizeNationality($nationality) {
    # TODO: ...
    #return "it";
    return $nationality;
  }

  # TODO: PRIVATE @PRODUCTION
  private function detectNationality($name, $description, $languageCode) {
    $patterns = [
      "it" => [
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )alban(ia|ese)\b/si" => "al",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )argentina\b/si" => "ar",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )australi(a|ana)\b/si" => "au",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )barbados\b/si" => "bb",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )belg(a|io)\b/si" => "be",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )bolivi(a|ana)\b/si" => "bo",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )bosni(a|aca)\b/si" => "ba",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )brasil(e|iana)\b/si" => "br",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )bulgar(a|ia)\b/si" => "bu",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )canad(a|ese)\b/si" => "ca",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )capo\s*verd(e|iana)\b/si" => "cv",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )ch?il(e|ena)\b/si" => "cl",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )ch?in(e|ese)\b/si" => "cn",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )colombi(a|ana)\b/si" => "co",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )costa\s*ric(a|he..)\b/si" => "cr",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )croa([tz]ia|ta)\b/si" => "hr",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )cub(a|ana)\b/si" => "cu",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )c(zech|eca)\b/si" => "cz",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )dan(imarca|ese)\b/si" => "dk",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )dominic(a|ana)\b/si" => "do",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )ecuador(e..)?\b/si" => "ec",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )eston(ia|e)\b/si" => "ee",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )finland(ia|ese)\b/si" => "fi",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )franc(ia|ese|esina)\b/si" => "fr",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )(germania|tedesc(a|ina)|(amica)|(alla) )\b/si" => "de",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )(gran bretagna|ing(hilterra|les(e|ina)|(amica)|(alla) ))\b/si" => "en",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )grec(a|ia)\b/si" => "gr",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )greanad(a|iana)\b/si" => "gd",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )guatemal(a|teca)\b/si" => "gt",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )hait(i|iana)\b/si" => "ht",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )h?ondur(as|e(...)?)\b/si" => "hn",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )ungher(ia|ese)\b/si" => "hu",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )island(a|ese)\b/si" => "is",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )indi(a|ana)\b/si" => "in",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )indonesi(a|ana)\b/si" => "id",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )irland(a|ese)\b/si" => "ie",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )israel(e|iana)\b/si" => "ie",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )italian(a|issima)\b/si" => "it",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )(j|gi)amaic(a|ana)\b/si" => "jm",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )(japan)|(giappon(e|ese))\b/si" => "jp",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )ken[iy](a|ana)\b/si" => "ke",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )core(a|ana)\b/si" => "kr",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )lituan(a|ia)\b/si" => "lt",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )liban(o|ese)\b/si" => "lb",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )letton(ia|e)\b/si" => "lv",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )lussemburg(o|hese)\b/si" => "lu",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )macedon(ia|e)\b/si" => "mk",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )malta\b/si" => "mt",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )me(x|ss)ic(o|ana)\b/si" => "mx",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )moldov(a|iana)\b/si" => "md",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )monaco\b/si" => "mc",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )mongol(ia|a)\b/si" => "mn",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )montenegr(o|ina)\b/si" => "me",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )morocc(o|ina)\b/si" => "ma",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )oland(a|ese)\b/si" => "nl",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )(neo|nuova)[\s-]?zeland(a|ese)\b/si" => "nz",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )nicaragu(a|e...)\b/si" => "ni",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )niger\b/si" => "ne",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )nigeri(a|ana)\b/si" => "ng",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )norveg(ia|ese)\b/si" => "no",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )pa(k|ch)istan(a)?\b/si" => "pk",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )panam(a|ense)\b/si" => "pa",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )paragua(y|iana)\b/si" => "py",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )peru(viana)?\b/si" => "pe",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )(ph|f)ilippin(e|a)\b/si" => "ph",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )pol(onia|acca)\b/si" => "pl",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )portog(allo|hese)\b/si" => "pt",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )r(omania|(o|u)mena)\b/si" => "ro",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )russ(i)?a\b/si" => "ru",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )san[\s-]?marin(o|ese)\b/si" => "sm",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )arab(i)?a\b/si" => "sa",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )senegal(ese)?\b/si" => "sn",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )serb(i)?a\b/si" => "rs",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )seychelles\b/si" => "sc",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )sierra[\s-]?leone\b/si" => "sl",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )singapore\b/si" => "sg",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )slovacch(i)?a\b/si" => "sk",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )sloven(i)?a\b/si" => "si",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )somal(i)?a\b/si" => "so",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )spagn(a|ola)\b/si" => "es",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )sve(zia|dese)\b/si" => "se",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )svizzera\b/si" => "ch",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )s[yi]ria(na)?\b/si" => "sy",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )taiwan(ese)?\b/si" => "tw",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )t(h)?ai(land(ia|ese)?)?\b/si" => "th",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )trinidad\b/si" => "tt",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )tunisi(a|ina)\b/si" => "tn",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )turc(hia|a)\b/si" => "tr",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )u[kc]raina\b/si" => "ua",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )urugua([yi]gia)\b/si" => "uy",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )america(na)?\b/si" => "us",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )venezuela(na)?\b/si" => "ve",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )vietnam(ita)?\b/si" => "vn",
        "/\b(<!(corso)!(c.so)|(cso)|(via)|(piazza)|(p.za)|(p.zza)|(amica)|(alla) )orient(e|ale)\b/si" => "cn",
      ],
    ];
// TODO: PortoRico...

    if (array_key_exists($languageCode, $patterns)) {
      foreach ($patterns[$languageCode] as $key => $value) {
        if ($name) {
          if (preg_match($key, $name)) {
            return $patterns[$languageCode][$key];
          }
        }
        if ($description) {
          if (preg_match($key, $description)) {
            return $patterns[$languageCode][$key];
          }
        }
      }
    }

    return null;
  }

  /**
   * Get all defined countries in sources
   *
   * @return array: all countries defined
   */
  public function getSourcesCountries() {
    #$this->router->log("info", "getSourcesCountries() ---");
    $countries = [];
/*
    foreach ($this->sourcesDefinitions as $sourceKey => $source) {
      $url = $source["url"];
      #$this->router->log("debug", "source: " . $sourceKey);
      $this->router->log("info", "getUrlContents($url)");
      $page = $this->network->getUrlContents($url, $source["charset"]);
      if ($page === FALSE) {
        $this->router->log("error", "can't get main page on source [$sourceKey]");
        continue;
      }
      # TODO: grep countries from source's main page...
    }
*/
    # TODO: save to db "global" table, to avoid re-scraping on every request...
    $countries = [ # TODO: ...
      "it" => [
        "name" => "Italy",
        "path" => "e...../italy",
        "cityCodeDefault" => "to",
      ],
      "ch" => [
        "name" => "Swizerland",
        "path" => "e...../switzerland",
        "cityCodeDefault" => "zh",
      ],
    ];
    return $countries;
  }

  /**
   * Get all defined cities in sources for specified country code
   *
   * @return array: all cities defined for specified country code
   */
  public function getSourcesCities($countryCode) {
    $cities = [];
/*
    if ($countryCode) {
      $this->router->log("info", "getSourcesCities($countryCode) ---");
    }
*/
/*
    foreach ($this->sourcesDefinitions as $sourceKey => $source) {
      $url = $source["url"];
      #$this->router->log("debug", "source: " . $sourceKey);
      $this->router->log("info", "getUrlContents($url)");
      $page = $this->network->getUrlContents($url, $source["charset"]);
      if ($page === FALSE) {
        $this->router->log("error", "can't get main page on source [$sourceKey]");
        continue;
      }
      # TODO: grep cities from source's main page...
    }
*/
    #$this->router->log("debug", "cities:" . var_export($cities, true));
    # TODO: save to db "global" table, to avoid re-scraping on every request...
    if ($countryCode === "it") {
      $cities = [
        "to" => [
          "name" => "Torino",
          "path" => "e...../torino",
        ],
        "mi" => [
          "name" => "Milano",
          "path" => "e...../milano",
        ],
      ];
    }
    if ($countryCode === "ch") {
      $cities = [
        "zh" => [
          "name" => "Zurich",
          "path" => "e...../zurich",
        ],
        "ge" => [
          "name" => "Genevre",
          "path" => "e...../genevre",
        ],
      ];
    }
    return $cities;
  }

  /**
   * Get all countries defined on persons
   *
   * @param integer $userId   user id
   * @return array            all countries defined
   */
  public function getActiveCountries($userId = null) {
    $result = $this->db->getFieldDistinctValues("person_detail", "nationality", $userId);
    return $result;
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
   * Check two persons uniqueness comparyng their phone numbers
   *
   * @param  array $person1    the first person
   * @param  array $person2    the second person
   * @return integer: true     the persons are uniq (same phone)
   *                  false    the persons are not uniq
   */
  private function personsCheckUniquenessByPhone($personId1, $personId2) {
    return (
      ($person1["phone"] && $person2["phone"]) &&
      #($person1["phone"] !== "0" && $person2["phone"] !== "0") && # TODO: do we need this check, or do we always get null or a phone number?
      ($person1["phone"] === $person2["phone"])
    );
  }

  /**
   * Check two persons uniqueness comparyng their photos
   *
   * @param  array $person1    the first person
   * @param  array $person2    the second person
   * @return integer: true     the persons are uniq (some photo in common)
   *                  false    the persons are not uniq
   */
  public function personsCheckUniquenessByPhotos($person1, $person2) {
    $id1 = $person1["id_person"];
    $id2 = $person2["id_person"];
    $photos1 = $this->db->getByField("photo", "id_person", $id1);
    $photos2 = $this->db->getByField("photo", "id_person", $id2);

    foreach($photos1 as $photo1) {
      // check if photo is an exact duplicate
      if ($this->photoCheckDuplication($photo1, $photos2)) {
        $this->router->log("debug", "personsCheckUniquenessByPhotos($id1, $id2) - photo n. " . $photo1['number'] . ", person with id $id1, has a duplicate with a photo of person with id $id2");
        return true; // duplicate found
      }
  
      if ($this->photoCheckSimilarity($photo1, $photos2)) {
        $this->router->log("debug", "personsCheckUniquenessByPhotos($id1, $id2) - photo n. " . $photo1['number'] . ", person with id $id1, has a similarity with a photo of person with id $id2");
        return true; // similarity found
      }
    }
    return false;
  }

  /**
   * Add a photo
   *
   * @param  integer $personId the id of the person's photo
   * @param  string $photoUrl  the url of the photo
   * @return integer: false    photo not added (duplication / similarity)
   *                  >= 0     photo added to filesystem and to database
   */
  public function photoAdd($personId, $photoUrl) {
#$this->router->log("debug", "PersonsController::photoAdd() - photoUrl = $photoUrl");
#    if (is_absolute_url($photoUrl)) { // absolute photo url
#      $photoUrl = $source["url"] . "/" . $photoUrl;
#   } else { // relative photo url
#      $photoUrl = str_replace("../", "", $photoUrl); // 'normalize' relative urls
#    }

    // build photo object from url
    try {
      $photo = new Photo($this->router, [ "url" => $photoUrl ]);
    } catch (Exception $e) {
      $this->router->log("error", "can't create new photo from url: " . $e->getMessage());
      return false;
    }

    $photos = $this->db->getByField("photo", "id_person", $personId);

/*
    CURRENTLY, NO SOURCE SITE GIVES A VALID LAST-MODIFICATION-TIMESTAMP FOR THE PHOTOS...

    // check if photo url did not change from last download
    if ($this->photoCheckLastModified($personId, $photo, $photos)) {
      $this->router->log("debug", "photoAdd [$photoUrl] for person id " . $personId . " is not changed, ignoring");
      return false; // same Last-Modified tag found
    }
*/

    // check if photo is an exact duplicate
    if ($this->photoCheckDuplication($photo, $photos)) {
      $this->router->log("debug", "photoAdd [$photoUrl] for person id " . $personId . " is a duplicate, ignoring");
      return false; // duplicate found
    }

    // check if photo has similarities
    $photo->signature();

    if ($this->photoCheckSimilarity($photo, $photos)) {
      $this->router->log("debug", "photoAdd [$photoUrl] for person id " . $personId . " is a similarity, ignoring");
      return false; // similarity found
    }
    $this->router->log("debug", "photoAdd() [$photoUrl] for person id " . $personId . " SEEMS NEW, ADDING...");

    $showcase = true; # TODO: decide $showcase (flag to denote showcase photo) ...

    $photo->idPerson($personId);
    $photo->domain();
    $photo->sum();
    $photo->timestampCreation(time());
    $photo->thruthful("unknown"); // this is an offline-set property (it's very expensive to calculate)
    $photo->showcase($showcase);
   
    // store this photo
    if (($number = $this->photoStore($personId, $photo)) === false) {
      $this->router->log("error", "photo " . $photo->url() . " for person id " . $personId . " could not be stored locally");
      return false; // error storing photo locally
    }
    $photo->number($number);

    // add this photo to database
    $retval = $this->db->add("photo", $this->photo2Data($photo));
    unset($photo);

    return $retval;
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

/*
    CURRENTLY, NO SOURCE SITE GIVES A VALID LAST-MODIFICATION-TIMESTAMP FOR THE PHOTOS...
  / **
   * Check photo last modification timestamp
   *
   * @param  integer $personId:     the id of person to check for photo last modification
   * @param  Photo: $photo          the photo object to check for last modification
   * @param  Photo array: $photos   the photos array of this person
   * @return boolean: true          if photo has not been modified
   *                  false         if photo has been modified (and should be downloaded)
   * /
  private function photoCheckLastModified($personId, $photo, $photos) {
    $photoLastModificationTimestamp = $photo->getLastModificationTimestamp();
    if ($photos !== []) {
      #if (is_array_multi($photos)) { // more than one result returned
        foreach ($photos as $p) {
##$this->router->log("debug", "p[url]:" . $p["url"] . ", photo[url]:" . $photo->url());
##$this->router->log("debug", "p[t_l_p]: (" . $p["timestamp_last_modification"] . ") =?= (" . $photoLastModificationTimestamp . ")");
          if ($p["url"] === $photo->url()) {
##$this->router->log("debug", "p[url]:" . $p["url"] . " FOUNDDDDDDDDDD");
            if ($p["timestamp_last_modification"] && $photoLastModificationTimestamp) {
              if ($p["timestamp_last_modification"] != $photoLastModificationTimestamp) {
                // the last modification timestamp of existing photo is greater or equal to
                // the last modification timestamp of the photo to be downloaded
                #$this->router->log("debug", "photoCheckLastModified: LastModificationTime CHANGED, RE-DOWNLOAD (??? !!!)");
                #$this->router->log("debug", "- photoCheckLastModified() RETURNING TRUE");
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
      #} else { // not more than one result returned
      #  # TODO: check if this is possible...
      #  throw new Exception("photoCheckLastModified(): returned one-level array: ".var_export($photos, true)." (SHOULD NOT BE POSSIBLE!!!)");
      #}
    }
    #$this->router->log("debug", "- photoCheckLastModified() RETURNING FALSE: photoLastModificationTimestamp !!!");
    return false;
  }
*/

  /**
   * Check for photo exact duplication
   *
   * @param  Photo: $photo       the photo object to check for duplication
   * @return boolean: true       if photo is a duplicate
   *                  false      if photo is not a fuplicate
   */
  private function photoCheckDuplication($photo, $photos) {
    if ($photos !== []) {
      foreach ($photos as $p) {
        if ($p["sum"] === $photo->sum()) { // the checksum matches
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Check for photo similarity
   *
   * @param  Photo: $photo       the photo object to check for similarity
   * @return boolean: true       if photo is similar to some else photo
   *                  false      if photo is not similar to some else photo
   */
  private function photoCheckSimilarity($photo, $photos) {
    $retval = false;
    if ($photos !== []) {
      foreach ($photos as $p) {
        try {
          $photo2 = new Photo($this->router, [ "data" => $p ]);
        } catch (Exception $e) {
          $this->router->log("error", "can't create new photo from data: " . $e->getMessage());
          return false;
        }
        if ($photo->checkSimilarity($photo2)) {
          $retval = true;
          break;
        }
      }
    }
    unset($photo2);
    return $retval;
  }

  /**
   * Store a photo on local file system
   *
   * @param  integer $personId   the id of the person for which to store photo
   * @param  Photo $photo        the photo to be stored
   * @return integer: >= 0       the progressive number of the photo
   */
  public function photoStore($personId, $photo) {
    $keyPerson = $this->db->get("person", $personId)["key"];
    $personPhotosCount = $this->photoGetCount($personId);
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

/*
  / **
   * Get all photos of person
   *
   * @param  integer $personId the person id of the photo
   * @return array[][]         if photos found
   *         null              if photos not found
   * /
  private function photoGetAll($personId) {
    $photos = $this->db->get("photo", $personId);

# DO WE NEED UNIQUENESS MERGE HERE ??? ##################################################
    $userId = 2; # TODO: get logged user id (from "authdata"?) ...
    $uniqcodes = $this->db->getPersonsUniqcodes($userId);
    foreach ($uniqcodes as $uniqcode) { // scan all uniqcodes
      if ($personId == $uniqcode["id_person_1"]) {
        // append photos fron the other 'uniq' person
        / *
        $result = $photos;
        $photos = $result + $this->db->get("photo", $uniqcode["id_person_2"]);
        * /
        $photos += $this->db->get("photo", $uniqcode["id_person_2"]);
      }
    }
#########################################################################################
    return $photos;
  }
*/

  /**
   * Get a photo of person given it's number
   *
   * @param  integer $personId   the id of the person whose photo to load
   * @param  integer $number     the progressive number of the photo
   * @return array   the photo structure
   */
  private function photoGetByNumber($personId, $number) {
    $photos = $this->db->getByFields("photo", [ "id_person" => $personId, "number" => $number ]);
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
  public function photoCheckThruthfulness($personId, $number) {
    $photo = $this->photoGetByNumber($personId, $number);
    return $photo["thruthful"];
  }

  /**
   * Get photos of person given their showcase flag
   *
   * @param  integer $personId   the id of the person whose photo to load
   * @param  integer $showcase   showcase flag (true / false)
   * @return array   the photo structure
   */
  private function photoGetByShowcase($personId, $showcase) {
    $photos = $this->db->getByFields("photo", [ "id_person" => $personId, "showcase" => $showcase ]);
    return (is_array($photos) && count($photos) > 0) ? $photos[0] : [];
  }

  /**
   * Get count of photos of person
   *
   * @param  integer $personId the id of the person's photo
   * @return integer           the number of photos of this person
   */
  public function photoGetCount($personId) {
    $count = $this->db->countByField("photo", "id_person", $personId);
    #$this->router->log("debug", "photoGetCount($personId): $count");
    return $count;
  }

  /**
   * Show a photo of person
   *
   * @param  integer $personId the id of the person's photo
   * @param  integer $number   the progressive number of the photo in the person's photos collection
   * @param  string $type      the type of the photo:
   *                             - "full"    shows the full version (default)
   *                             - "small"   shows the small version
   * @return void              outputs photo with MIME header
   *
   * TODO: do we need this function, or will always use "<img ns-src='{{person.photo_path}}'>" ?
   */
  public function photoShow($personId, $number, $type = "full") {
    $photo = $this->db->getByFields("photo", ["id_person" => $personId, "number" => $number ]);
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

}

?>