<?php
/**
 * Persons controller
 * 
 * @package PersonsController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class PersonsController {

function unused() { ; }

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
   * Sync persons
   *
   * @param  boolean $newKeysOnly   if true sync only new persons (default: false)
   * @return boolean:               true if everything successful, false otherwise
   */
  public function sync($newKeysOnly = false) {
    $this->router->log("info", "sync() ---------------------");
    $timestampStart = time();
    $error = false; // track errors while sync'ing

    foreach ($this->sourcesDefinitions as $sourceKey => $source) {
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
          $this->router->log("debug", " old person: $key (id: $personId)");
          if ($newKeysOnly) { // requested to sync only new keys, skip this old key
            continue;
          }
        } else {
          $this->router->log("debug", " new person: $key (id: $id)");
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
          $name = $this->cleanName($matches[1]);
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
          $nationality = "";
        }
        
        # TODO: add logic to grab this data from person's (or comments) page
        $address = "Via Roma, 123, Torino";
        $age = 28;
        $vote = 7; # TODO: JUST TO TEST BUTTONS WIDTH
        $timestampNow = time(); // current timestamp, sources usually don't set page last modification date...
        $new = false;

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
        $personDetail["new"] = $new;
        $personDetail["uniq_prev"] = null;
        $personDetail["uniq_next"] = null;

        if ($personId) { # old key, update it
          #$this->router->log("debug", " UPDATING ");        
          # TODO: remember old values someway (how???), before updating (old zone, old phone, ...)?
          $this->set($personId, $personMaster, $personDetail);
        } else { # new key, insert it
          #$this->router->log("debug", " INSERTING ");
          $personMaster["key"] = $key; // set univoque key only when adding person
          $personMaster["timestamp_creation"] = $timestampNow; // set current timestamp as creation timestamp
          $personDetail["new"] = true; // set new flag to true
          $personId = $this->add($personMaster, $personDetail);

#$this->router->log("debug", " PERSON: " . any2string($personMaster) . any2string($personDetail));

          foreach ($photosUrls as $photoUrl) { // add photos
            #if (preg_match("/^https?:\/\//", $photoUrl)) { // absolute photo url
            if (is_absolute_url($photoUrl)) { // absolute photo url
              $this->photoAdd($personId, $photoUrl);
            } else { // relative photo url
#$this->router->log("debug", " RELATIVE PHOTO URL: " . $source["url"] . "@/@" . $photoUrl);
              $this->photoAdd($personId, $source["url"] . "/" . $photoUrl);
            }
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

    if (!$newKeysOnly) { // full sync was requested
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
    #$this->router->log("info", " timestamp of last sync start: " . date("c", $timestampStart));
    foreach ($this->db->getPersonList(/* no sieve, */ /* no user: system user */) as $person) {
      #$this->router->log("info", "  person " . $person["key"] . " - active_label: [" . $person["active_label"] . "]");
      if ($person["active_label"] === "0") {
        $active = false;
        #$this->router->log("info", "  person " . $person["key"] . "(" . $person["name"] . ")" . " - active_label = false, forcing active status: active: " . ($active ? "1" : "0"));
      } else {
        // set activity flag based on the time of last sync for this person, compared to the time of last full sync (just done)
        $timestampLastSyncPerson = $person["timestamp_last_sync"];
        $active = ($timestampLastSyncPerson >= $timestampStart);
        #$this->router->log("info", "  person " . $person["key"] . "(" . $person["name"] . ")" . " - last sync: $timestampLastSyncPerson - active: " . ($active ? "1" : "0"));
      }
      $this->db->setPerson($person["id"], [ "active" => $active ? 1 : 0 ]);
    }
  }

  /**
   * Assert persons uniqueness
   */
  public function assertPersonsUniqueness() {
    $this->router->log("info", "asserting persons uniqueness (checking for field matching for every couple of persons)");
    $persons = $this->db->getPersonList(null);

    # build an array of persons indexed by id, instead then by a progressive counter
    $persons_count = count($persons);
    for ($i = 0; $i < $persons_count; $i++) { // build a persons-by-id array
      $personsById[$persons[$i]["id"]] = $persons[$i];
    }

    # check every couple of persons (avoiding permutations)
    for ($i = 0; $i < $persons_count - 1; $i++) {
      #$this->router->log("info", " i: [$i]");
      for ($j = $i + 1; $j < $persons_count; $j++) {
        #$this->router->log("info", " j: [$j]");
        if (
          //($persons[$i]["name"] === $persons[$j]["name"]) ||
          (($persons[$i]["phone"] !== "0" && $persons[$j]["phone"]) && ($persons[$i]["phone"] === $persons[$j]["phone"]))
        ) { // these two persons are unique
          $id1 = $persons[$i]["id"];
          $id2 = $persons[$j]["id"];
          $this->router->log("info", "assertPersonsUniqueness() - found 'uniq' persons: " . $persons[$i]['key'] . " and " . $persons[$j]['key']);
          #$this->router->log("info", " - phone(id1): " . $persons[$i]["phone"]);
          #$this->router->log("info", " - phone(id2): " . $persons[$j]["phone"]);

          // follow next unique chain, and add the 2nd person id as additional unique (next) person
          $idNext = $personsById["$id1"]["uniq_next"];
          while ($idNext && $personsById["$idNext"]["uniq_next"]) {
            $idNext = $personsById["$idNext"]["uniq_next"]; // TODO: avoid possibly infinite loops...
            #$this->router->log("info", "  w1 - idNext: [$idNext] - phone: " . $persons["$idNext"]["phone"]);
          }
          $id = $idNext ? $idNext : $id1;
          if ($personsById["$id"]["uniq_next"] !== $id2) {
            $personsById["$id"]["uniq_next"] = $id2;
            $this->db->setPerson($id, null, [ "uniq_next" => $id2 ]); // save uniq_next id to person
          }

          // follow prev unique chain, and add the 1nd person id as additional unique (prev) person
          $idPrev = $personsById["$id2"]["uniq_prev"];
          while ($idPrev && $personsById["$idPrev"]["uniq_prev"]) {
            $idPrev = $personsById["$idPrev"]["uniq_prev"]; // TODO: avoid possibly infinite loops...
            #$this->router->log("info", "  w2 - idPrev: [$idPrev] - phone: " . $persons["$idPrev"]["phone"]);
          }
          $id = $idPrev ? $idPrev : $id2;
          if ($personsById["$id"]["uniq_next"] != $id1) {
            $personsById["$id"]["uniq_prev"] = $id1;
            $this->db->setPerson($id, null, [ "uniq_prev" => $id1 ]); // save uniq_prev id to person
          }
        }
      }
    }
  }

  /**
   * Get all defined countries in sources
   *
   * @return array: all countries defined
   */
  public function getSourcesCountries() {
    $this->router->log("info", "getSourcesCountries() ---");
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
  / **
   * Get two persons uniqueness value (are they assumed to be the same person)
   *
   * @return null/boolean  null  if same value is not set (persons are probably not the same)
   *                       true  if same value is set (persons are not the same)
   *                       false if same value is set (persons are the same)
   * /
  public function getPersonsUniqueness($personId1, $personId2, $userId = null) {
    $this->router->log("info", "getPersonsUniqueness()");
    # TODO: check null in $userId causes default value (DB_SYSTEM_USER_ID) set in $this->db->getPersonsUniqcode()... !!!
    $result = $this->db->getPersonsUniqcode($personId1, $personId2, $userId);
    if (!$result) {
      return null;
    } else {
      return $result["same"];
    }
  }

  / **
   * Set persons uniqueness value (they are assumed to be the same person)
   *
   * @return boolean  true    if value was set successfully
   *                  false   if some error occurred
   * /
  public function setPersonsUniqueness($personId1, $personId2, $same, $userId = null) {
    $this->router->log("info", "setPersonsUniqueness($personId1, $personId2)");
    # TODO: check null in $userId causes default value (DB_SYSTEM_USER_ID) set in $this->db->getPersonsUniqcode()... !!!
    return $this->db->setPersonsUniqcode($personId1, $personId2, $same, $userId);
  }
*/

# TODO: pass $userId to db functions, in user functions (not in system ones...)

  public function get($id) {
#$this->router->log("info", " +++ get person [$id]: " . var_export($id, true));
#print "get($id)\n";
    $person = $this->db->getPerson($id);
    $photos = $this->db->getByField("photo", "id_person", $id);
    #$person["photos"] = $photos;
    ###################################################################################
/*
    $userId = 2; # TODO: get logged user id (from "authdata"?) ...
    $uniqcodes = $this->db->getPersonsUniqcodes($userId);
    if (is_array($uniqcodes)) {
      foreach ($uniqcodes as $uniqcode) { // scan all uniqcodes
# TODO: DEBUG.LOG...
        if ($id == $uniqcode["id_person_1"]) {
          // append photos fron the other 'uniq' person
          $result = $photos;
          $photos = $result + $this->db->get("photo", $uniqcode["id_person_2"]);
        }
      }
    }
*/
    ###################################################################################
    $person["photos"] = $photos;

#print " person: "; var_export($person);
#$this->router->log("info", " !!! person($id): " . var_export($person, true));
    return $person;
  }
  
  public function getByPhone($phone) {
    if (!$phone) {
      return [];
    }
    return $this->db->getByField("person_detail", "phone", $phone);
  }
  
  # TODO: add $userId...
  public function addPerson($personMaster, $personDetail = null) { # TODO: $userId !!!
    return $this->db->addPerson($personMaster, $personDetail);
  }

  public function set($id, $personMaster, $personDetail = null, $userId = null) {
    return $this->db->setPerson($id, $personMaster, $personDetail, $userId);
  }

  public function deletePerson($id) { # TODO: $userId !!!
    return $this->db->deletePerson($id);
  }
  
  /**
   * get all persons list, filtered with given data sieves
   *
   * @param  array $data
   * @return array
   */
  public function getList($data) {
    $result = [];
    $userId = $data && $data["user"] ? $data["user"]["id"] : null;
    $comments = new CommentsController($this->router);

    $persons = $this->db->getPersonList($data, $userId);
$this->router->log("debug", "persons length: " . count($persons));
    foreach ($persons as $person) {
      // N.B: here we (could) get multiple records for each person id
      $personId = $person["id_person"];
      if (!isset($result[$personId])) {
        $result[$personId] = []; // initialize this person array in results
      } else { # TODO: should never happen...
        throw new Exception("Assertion failed: getList(): (isset(\$result[\$personId])"); # TODO: JUST TO DEBUG!
      }

/*
      // show a null phone
      if ($person["phone"] === "0") { # TODO: remove this statement, and store 'null' for empty phones, not '0'...
        $person["phone"] = null;
      }
*/
      
      // store each person by it's person id ("id" field is relative to details table)
      $result[$personId] = $person;
/*
      // fields with only "master" table values
      foreach (
        [
          "id_person",
          "key",
          "source_key",
          "new",
          "timestamp_creation",
          "timestamp_last_sync",
        ] as $field
      ) {
        $result[$personId][$field] = $person[$field];
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
        if (isset($person[$field])) { // merge master and detail fields in result
          if (!isset($result[$personId][$field])) {
            $result[$personId][$field] = $person[$field];
          } else {
            $result[$personId][$field] .= ", " . $person[$field];
          }
          #$this->router->log("debug", "field: <$field> ");
          #$this->router->log("debug", "field: result[$personId][$field] = " . $person[$field]);
        }
      }
      #$this->router->log("debug", "result: " . var_export($result, true));
*/
      // fields "calculated"
      //$result[$personId]["thruthful"] = "unknown"; # TODO: if at least one photo is !thrustful, person is !thrustful...
      $result[$personId]["photo_path_small_showcase"] = $this->photoGetByShowcase($personId, true)["path_small"];
      $result[$personId]["comments_count"] = $comments->countByPerson($personId);
      $result[$personId]["comments_average_rating"] = $comments->getAverageRating($personId);
    }
#$this->router->log("debug", "getList() - result: " . var_export($result, true));

/*
    # uniquify persons
    $this->personsUniquify($result);
*/
    return $result;
  }

/*
  / **
   * uniquify all persons
   *
   * @param reference to array of array   $persons           the persons array, by reference
   * @result boolean                      true               success (the two elements have been merged)
   *                                      exception thrown   some error occurred
   * /
  private function personsUniquify(&$persons) {
    $userId = 2; # TODO: get logged user id (from "authdata"?) ...

    // check uniqcodes table, to possibly merge persons with more than one source
    $uniqcodes = $this->db->getPersonsUniqcodes($userId);
      / *
        $uniqcodes = [
          [
            'id' => '1',
            'id_user' => '1',
            'id_person_1' => '1',
            'id_person_2' => '2',
                'same' => '1',
          ],
          [
            'id' => '2',
            'id_user' => '1',
            'id_person_1' => '7',
            'id_person_2' => '123',
            'same' => '1',
          ],
          ...
        ]
      * /
    if (is_array($uniqcodes)) {
      foreach ($uniqcodes as $uniqcode) { // scan all uniqcodes
        foreach ($persons as $personId => $person) { // scan all persons in result
          if ($personId == $uniqcode["id_person_1"]) {
            #$this->mergeList($persons, $uniqcode["id_person_1"], $uniqcode["id_person_2"]);
            $persons[$uniqcode["id_person_1"], $uniqcode["id_person_2"]);
          }
        }
      }
    }

    return true;
  }
*/

/*
  / **
   * merge two persons
   *
   * @param reference to array of array   $persons           the persons array, by reference
   * @result boolean                      true               success (the two elements have been merged)
   *                                      exception thrown   some error occurred
   * /
  private function mergeList(&$persons, $id1, $id2) {
    #$sep = "\x01";
    $sep = ",";
    $fields = [ "key", "name", "sex", "zone", "address", "description", "notes", "phone", "nationality", "age", "vote", "showcase", "thruthful", "new" ];
    foreach ($fields as $field) {
      # TODO: analyze each field, and verify that consumers will always be satisfied, after the merge...
      switch ($field) {
        case "key":
          #$persons[$id1][$field] .= ($sep . $persons[$id2][$field]);
          break;
        case "phone":
          $persons[$id1][$field] .= ($sep . $persons[$id2][$field]);
          break;
        default:
$this->router->log("debug", "PERSON $id1 DATA: " . any2string($persons[$id1]));
$this->router->log("debug", " * merging keys - field: " . $field);
          if (isset($persons[$id1][$field])) {
            if (isset($persons[$id2][$field])) {
              $persons[$id1][$field] .= ($sep . $persons[$id2][$field]); // both of $id1 and $id2 persons field set
            } else {
              ; // only $id1 person field set
            }
          } else {
            if (isset($persons[$id2][$field])) {
              $persons[$id1][$field] = $persons[$id2][$field]; // only $id2 person field set
            } else {
              ; // none of $id1 and $id2 persons field set
            }            
          }
          break;
      }
    }
    unset($persons[$id2]);
    return true;
  }
*/

  public function photoGetOccurrences($id, $imageUrl) {
    $person = $this->db->get("person", $id);
    $personDomain = $person["url"];

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
        $response["searchResults"] = [];
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

  public function photoGetCardDeck($imageUrls) {
    $photo = new Photo([ "url" => null ]); # TODO: change classto support null url instantiation...
    if (($cardDeck = $photo->photoGetCardDeck($imageUrls)) === false) {
      # TODO: return default card deck, if can't build one
      $this->router->log("debug", " can't create photo card deck, returning default card deck...");
      #$cardDeck = file_get_contents("../app/images/referral-sources/card-deck-default.png"); # TODO: ...
throw new Exception("can't create photo card deck"); # TODO: JUST TO DEBUG!
    }
    unset($photo);
    return $cardDeck;
  }

/*
  public function getUniqIds($id) {
    if (($uniqIds = $this->db->getUniqIds($id))) {
$this->router->log("debug", "+++ getUniqIds: " . any2String($uniqIds));
      return $uniqIds;
    } else {
$this->router->log("debug", "+++ getUniqIds: EMPTY!!!");
      return [];
    }
  }
*/

  private function cleanName($value) {
    $value = preg_replace("/[()]/", "", $value); // ignore not meaningful characters
    $value = preg_replace("/\s+/", " ", $value); // squeeze blanks to one space
    $value = preg_replace("/^\s+/", "", $value); // ignore leading blanks
    $value = preg_replace("/\s+$/", "", $value); // ignore trailing blanks
    #$value = strtoupper($value); // all upper case
    $value = ucfirst(strtolower($value)); // only initials upper case
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
   * @param  integer $personId the id of the person's photo
   * @param  string $photoUrl  the url of the photo
   * @return integer: false    photo not added (duplication / similarity)
   *                  >= 0     photo added to filesystem and to database
   */
  public function photoAdd($personId, $photoUrl) {
    // 'normalize' relative urls
    $photoUrl = str_replace("../", "", $photoUrl);

    // build photo object from url
    $photo = new Photo([ "url" => $photoUrl ]);

    $photos = $this->db->getByField("photo", "id_person", $personId);

/*
    CURRENTLY, NO SOURCE SITE GIVES A VALID LAST-MODIFICATION-TIMESTAMP FOR THE PHOTOS...

    // check if photo url did not change from last download
    if ($this->photoCheckLastModified($personId, $photo, $photos)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $personId . " is not changed, ignoring");
      return false; // same Last-Modified tag found
    }
*/

    // check if photo is an exact duplicate
    if ($this->photoCheckDuplication($personId, $photo, $photos)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $personId . " is a duplicate, ignoring");
      return false; // duplicate found
    }

    // check if photo has similarities
    $photo->signature();

    if ($this->photoCheckSimilarity($personId, $photo, $photos)) {
      $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $personId . " is a similarity, ignoring");
      return false; // similarity found
    }
    $this->router->log("debug", " photoAdd [$photoUrl] for person id " . $personId . " SEEMS NEW, ADDING...");

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
      if (is_array_multi($photos)) { // more than one result returned
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
    #$this->router->log("debug", " - photoCheckLastModified() RETURNING FALSE: photoLastModificationTimestamp !!!");
    return false;
  }
*/

  /**
   * Check for photo exact duplication
   *
   * @param  integer $personId:  the id of person to check for photo duplication
   * @param  Photo: $photo       the photo object to check for duplication
   * @return boolean: true       if photo is a duplicate
   *                  false      if photo is not a fuplicate
   */
  private function photoCheckDuplication($personId, $photo, $photos) {
    if ($photos !== []) {
#      if (is_array_multi($photos)) { // more than one result returned
        foreach ($photos as $p) {
          if ($p["sum"] === $photo->sum()) { // the checksum matches
            #$this->router->log("debug", "photoCheckDuplication(many) - photo " . $photo->url() . " sum is equal to  " . $p["url"] . ", it's duplicate...");
            return true;
          }
        }
#      } else { // not more than one result returned
#        # TODO: check if this is possible...
#        throw new Exception("photoCheckDuplication(): returned one-level array:" . var_export($photos, true));
#/*  
#        if ($photos) { // exactly one result returned
#          $p = $photos;
#          if ($p["sum"] === $photo->sum()) { // the checksum matches
#            #$this->router->log("debug", "photoCheckDuplication(one) - photo " . $photo->url() . " sum is equal to  " . $p["url"] . ", it's duplicate...");
#            return true;
#          }
#        }
#*/  
#      }
    }
    return false;
  }

  /**
   * Check for photo similarity
   *
   * @param  integer $personId:  the id of person to check for photo similarity
   * @param  Photo: $photo       the photo object to check for similarity
   * @return boolean: true       if photo is similar to some else photo
   *                  false      if photo is not similar to some else photo
   */
  private function photoCheckSimilarity($personId, $photo, $photos) {
    $retval = false;
    if ($photos !== []) {
#      if (is_array_multi($photos)) { // more than one result returned
        foreach ($photos as $p) {
          $photo2 = new Photo([ "data" => $p ]);
          if ($photo->checkSimilarity($photo2)) {
            #$this->router->log("info", "photo signature " . $photo->url() . " is similar to " . $photo2->url() . ", it's probably a duplicate...");
            $retval = true;
            break;
          }
        }
#      } else { // not more than one result returned
#        throw new Exception("photoCheckSimilarity(): returned one-level array!" . " SHOULD NOT HAPPEN!!!");
#/*  
#        if ($photos) { // one result returned
#          $photo2 = new Photo([ "data" => $photos ]);
#          if ($photo->checkSimilarity($photo2)) {
#            #$this->router->log("info", "photo signature " . $photo->url() . " is similar to " . $photo2->url() . ", it's probably a duplicate...");
#            return true;
#          }
#        }
#*/  
#      }
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
    #$this->router->log("debug", "photoStore - storing photo");

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

  /**
   * Get all photos of person
   *
   * @param  integer $personId the person id of the photo
   * @return array[][]         if photos found
   *         null              if photos not found
   */
  private function photoGetAll($personId) {
$this->router->log("debug", "photoGetAll($personId)");
    $photos = $this->db->get("photo", $personId);

# DO WE NEED UNIQUENESS MERGE HERE ??? ##################################################
    $userId = 2; # TODO: get logged user id (from "authdata"?) ...
    $uniqcodes = $this->db->getPersonsUniqcodes($userId);
    foreach ($uniqcodes as $uniqcode) { // scan all uniqcodes
      if ($personId == $uniqcode["id_person_1"]) {
        // append photos fron the other 'uniq' person
        $result = $photos;
        $photos = $result + $this->db->get("photo", $uniqcode["id_person_2"]);
      }
    }
#########################################################################################
    return $photos;
  }

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



/*
  # TODO: REMOVE-ME (can use sync even @OFFICE, now...)
  # TODO: DEBUG-ONLY
  public function test() {
$this->router->log("debug", "test START");
    $photosUrls[0] = [
      "/scienzefanpage/wp-content/uploads/2013/12/samantha-cristoforetti-futura.jpg",
      "/scienzefanpage/wp-content/uploads/2012/07/donna-italiana-spazio1-300x225.jpg",
    ];
    $personMaster[0] = [];
    $personMaster[0]["key"] = "linkedin-123456";
    $personMaster[0]["source_key"] = "linkedin";
    $personMaster[0]["url"] = "http://static.fanpage.it";
    $personMaster[0]["timestamp_last_sync"] = 1424248678;
    $personMaster[0]["page_sum"] = "0cc175b9c0f1b6a831c399e269772661";
    $personMaster[0]["active"] = false;
    $personDetail[0] = [];
    $personDetail[0]["name"] = "Samantha";
    $personDetail[0]["sex"] = "F";
    $personDetail[0]["zone"] = "centro";
    $personDetail[0]["address"] = "Via Roma, 3, Milano";
    $personDetail[0]["description"] = "astronauta";
    $personDetail[0]["phone"] = "3336480981";
    $personDetail[0]["nationality"] = "it";
    $personDetail[0]["age"] = 31;
    $personDetail[0]["vote"] = 8;
    $personDetail[0]["new"] = true;
    ########################################################################################
    $photosUrls[1] = [
      "/wp-content/gallery/convegno/img_2484.jpg",
      "/wp-content/gallery/convegno/img_2477.jpg",
    ];
    $personMaster[1] = [];
    $personMaster[1]["key"] = "twitter-789012";
    $personMaster[1]["source_key"] = "facebook";
    $personMaster[1]["url"] = "http://www.newshd.net";
    $personMaster[1]["timestamp_last_sync"] = 1424248678;
    $personMaster[1]["page_sum"] = "0cc175b9c0f1b6a831c399e269772662";
    $personMaster[1]["active"] = true;
    $personDetail[1] = [];
    $personDetail[1]["name"] = "Elena";
    $personDetail[1]["sex"] = "F";
    $personDetail[1]["zone"] = "centro";
    $personDetail[1]["address"] = "Via Garibaldi 12, Roma";
    $personDetail[1]["description"] = "scienziata";
    $personDetail[1]["phone"] = "3336480982";
    $personDetail[1]["nationality"] = "it";
    $personDetail[1]["age"] = 42;
    $personDetail[1]["vote"] = 9;
    $personDetail[1]["new"] = false;
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

  # TODO: DEBUG-ONLY
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
*/
}

?>