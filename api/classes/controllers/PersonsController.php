<?php
/**
 * Persons controller
 * 
 * @package PersonsController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class PersonsController {
  const PHOTOS_PATH = "db/photos/";
  const TIMEOUT_BETWEEN_DOWNLOADS = 60;
  const RETRIES_MAX_FOR_DOWNLOADS = 3;

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
   * @param  boolean $fullSync   if true sync fully all persons, do not skip photos for old persons (default: false)
   * @return boolean:            true if everything successful, false if any error occurred
   */
  public function sync($fullSync = false) {
    $this->router->log("info", "---------- sync ----------");
    $timestampStart = time();
    $error = false; // track errors while sync'ing for activity assertion

    foreach ($this->sourcesDefinitions as $sourceKey => $source) {
      $useTor = $source["accepts-tor"]; // use TOR proxy to sync
      # TODO: handle country / city / category (instead of a fixed path)
      $url = $source["url"] . "/" . $source["path"];

      // get person details page
      $page = $this->getUrlContents($url, $source["charset"], $useTor);
      if ($page === false) {
        $error = true;
        continue;
      }

      $persons_page = $page;
      if (preg_match_all($source["patterns"]["person"], $persons_page, $matches)) {
        $person_cells = $matches[1];
      } else {
        /*
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
        */
        $this->router->log("error", "not any person pattern found on source [$sourceKey], giving up with this source");
        $error = true;
        continue;
      }
      
      $persons = $person = [];
      $n = 0;
      foreach ($person_cells as $person_cell) {
        $n++;

        // get person id
        if (preg_match($source["patterns"]["person-id"], $person_cell, $matches) >= 1) {
          $id = $matches[1];
          $key = $sourceKey . "-" . $id;
        } else {
          $this->router->log("error", "person $n ($key) id not found on source [$sourceKey], giving up with this person");
          $error = true;
          continue;
        }

        // check if person key is new or not
        $personId = null;
        if (($persons = $this->db->getPersonsByField("key", $key))) { # old key
          $person = $persons[0];
          $personId = $person["id_person"];
          $this->router->log("debug", "old person: $key (id: $personId)");
        } else {
          $this->router->log("debug", "new person: $key");
        }

        // get person details url
        if (preg_match($source["patterns"]["person-details-url"], $person_cell, $matches) >= 1) {
          $detailsUrl = $source["url"] . "/" . $matches[1];
        } else {
          $this->router->log("error", "person $n ($key) details url not found, giving up with this person");
          $error = true;
          continue;
        }

        // get person details page
        $page_details = $this->getUrlContents($detailsUrl, $source["charset"], $useTor);
        if ($page_details === false) {
          $error = true;
          continue;
        }

        // calculate sum of page body
        if (preg_match($source["patterns"]["person-body"], $page_details, $matches) >= 1) {
          $body = $matches[1];
        } else {
          $body = $page_details;
        }
        // remove all patterns to be removed (they change on every load) from body before sum
        foreach ($source["patterns"]["person-body-patterns-to-remove-before-sum"] as $pattern) {
          $body = preg_replace($pattern, "", $body);
        }
        $pageSum = md5($body);

        // get person phone
        if (preg_match($source["patterns"]["person-phone"], $page_details, $matches) >= 1) {
          $phone = $this->normalizePhone($matches[1], $sourceKey);
        } else {
          $this->router->log("error", "person $n ($key) phone not found, giving up with this person");
          $error = true;
          continue;
        }

        // get person phone
        if (preg_match_all($source["patterns"]["person-photo"], $page_details, $matches) >= 1) {
          $photosUrls = $matches[1];
        } else {
          $this->router->log("error", "person $n ($key) photo pattern not found, giving up with this person");
          $error = true;
          continue;
        }

        // get person name
        if (preg_match($source["patterns"]["person-name"], $page_details, $matches) >= 1) {
          $name = $this->normalizeName($matches[1]);
        } else {
          #$this->router->log("warning", "person $n name not found on source [$sourceKey]");
          $name = "";
        }

        // get person sex
        if (preg_match($source["patterns"]["person-sex"], $page_details, $matches) >= 1) {
          $sex = $matches[1];
        } else {
          #$this->router->log("warning", "person $n sex not found on source [$sourceKey]");
          $sex = "";
        }

        // get person zone
        if (preg_match($source["patterns"]["person-zone"], $page_details, $matches) >= 1) {
          $zone = $matches[1];
        } else {
          #$this->router->log("info", "person $n zone not found on source [$sourceKey]");
          $zone = "";
        }
        
        // get person description
        if (preg_match($source["patterns"]["person-description"], $page_details, $matches) >= 1) {
          $description = $this->normalizeDescription($matches[1]);
        } else {
          #$this->router->log("warning", "person $n description not found on source [$sourceKey]");
          $description = "";
        }
 
        // get person nationality
        if (preg_match($source["patterns"]["person-nationality"], $page_details, $matches) >= 1) {
          $nationality = $this->normalizeNationality($matches[1]);
        } else {
          #$this->router->log("warning", "person $n nationality not found on source [$sourceKey]");
          #$nationality = "";
          $nationality = $this->detectNationality($name, $description, $this->router->cfg["sourcesCountryCode"]);
        }
        
        $streetAddress = null; # WAS ""... # TODO: add logic to grab this data from person's (or comments) page
        $age = null;
        $vote = null;
        $timestampNow = time(); // current timestamp, sources usually don't set page last modification date...

        $personMaster = [];
        $personMaster["source_key"] = $sourceKey;
        $personMaster["url"] = $detailsUrl;
        $personMaster["timestamp_last_sync"] = $timestampNow;
        $personMaster["page_sum"] = $pageSum;
        $personDetail = [];
        $personDetail["name"] = $name;
        $personDetail["sex"] = $sex;
        $personDetail["zone"] = $zone;
        $personDetail["street_address"] = $streetAddress;
        $personDetail["description"] = $description;
        #if ($phone !== null) { $personDetail["phone"] = $phone; } // if phone is null, do not set it
        $personDetail["phone"] = $phone; # TODO: TEST THIS: if phone is null we set it nonetheless...
        $personDetail["nationality"] = $nationality;
        $personDetail["age"] = $age;
        $personDetail["vote"] = $vote;
        $personDetail["uniq_prev"] = null;
        $personDetail["uniq_next"] = null;

        if ($personId) { # old key, update it
          $this->set($personId, $personMaster, $personDetail);

#######################################################################
# TODO: TEST IF *BODY* PAGE SUM DOES NOT CHANGE IF PAGE IS NOT UPDATED
#######################################################################
if ($personMaster["page_sum"] !== $person["page_sum"]) {
  $this->router->log("debug", "PersonsController::sync() - BODY SUM IS CHANGED :-(((");
} else {
  $this->router->log("debug", "PersonsController::sync() - BODY SUM IS THE SAME :-)))");
  continue; # TODO: use 'or' below, it's better...
}
#######################################################################

        } else { # new key, insert it
          $personMaster["key"] = $key; // set univoque key only when adding person
          $personMaster["timestamp_creation"] = $timestampNow; // set current timestamp as creation timestamp
          $personId = $this->add($personMaster, $personDetail);
        }

        if (
          !array_key_exists("id_person", $person) or // person is new
          $fullSync #or // a full sync was requested
         #($personMaster["page_sum"] !== $person["page_sum"]) // page sum did change
        ) { // add photos if person is new, or if full sync was requested, or if details page checksum did change
          foreach ($photosUrls as $photoUrl) { // add photos
            if (is_absolute_url($photoUrl)) { // absolute photo url
              $photoUrl = str_replace("../", "", $photoUrl); // 'normalize' relative urls
            } else { // relative photo url
              $photoUrl = $source["url"] . "/" . $photoUrl;
            }
            $this->photoAdd($personId, $photoUrl);
          }
        }
      }
    }

    // assert persons activity status
    $this->assertPersonsActivity($timestampStart, $sourceKey, $error);

    // assert persons uniqueness after sync completed
    $this->assertPersonsUniqueness();

    $this->router->log("info", "---------- /sync ----------");
    return !$error;
  }

  private function getUrlContents($url, $charset, $useTor) {
    $retry = 0;
    retry:
    try {
      #$this->router->log("debug", "getting url [$url], tor: " . ($useTor ? "1" : "0"));
      $data = $this->network->getUrlContents($url, $charset, null, false, $useTor);
      if (strpos($data, "La pagina che hai tentato di visualizzare non esiste") !== false) {
        $this->router->log("warning", "can't get page [$url]: " . "does not exist");
        return false;
      } else {
        # TODO: why does this sometimes happen?
        if (preg_match("/<link rel=\"stylesheet\" href=\"http:\/\/m\..*?\"/s", $data) >= 1) {
          $this->router->log("error", "url [$url] returned unexpected mobile page");
          return false;
        } else {
          if (
            (strpos($data, "Why do I have to complete a CAPTCHA?") !== false) OR
            (strpos($data, "has banned your access") !== false)
          ) {
            $this->router->log("warning", "can't get page [$url]: " . "access denied");
            if ($retry < self::RETRIES_MAX_FOR_DOWNLOADS) { // sleep a random number of seconds
              $sleep = self::TIMEOUT_BETWEEN_DOWNLOADS * $retry;
              $this->router->log("warning", "sleeping $sleep seconds before retrying...");
              sleep($sleep);
              $retry++;
              goto retry;
            } else {
              $this->router->log("error", "url [$url] retries exausted, giving up");
              return false;
            }
          }
        }
      }
    } catch(Exception $e) {
      $message = $e->getMessage();
      $this->router->log("error", "url [$url] contents not found");
      return false;
    }
    if (strlen($data) <= 0) {
      $this->router->log("error", "url [$url] contents is empty");
      return false;
    }
    return $data;
  }


  /**
   * Assert persons activity: compare person's last sync'd timestamp with a given timestamp
   *  ("timestampStart" parameter), the timestamp last (or this) sync started.
   */
  private function assertPersonsActivity($timestampStart, $sourceKey, $error) {
    $this->router->log("info", "asserting persons activity (setting active / inactive flag to persons based on timestamp_last_sync)");
    foreach ($this->db->getPersons(/* no sieve, */ /* no user: system user */) as $person) {
      $active = null;
      $activeFromSource = $this->isPhoneActive($person["phone"], $sourceKey);
      if (!$activeFromSource) {
        // this person was found as explicitly "inactive" from source page
        $active = false;
      } else {
        // set activity flag based on the time of last sync for this person, compared to the time of this full sync,
        // but only if there were no error during sync, to avoid marking all persons as not-active when a source is not available...
        if (!$error) { # TODO: try to find a better way to avoid marking all persons as not-active when a source is not available
          $timestampLastSyncPerson = $person["timestamp_last_sync"];
          $this->router->log("debug", " person " . $person["key"] . "(" . $person["name"] . ")" . " - last sync: $timestampLastSyncPerson, timestamp start: $timestampStart - active: " . ($active ? "1" : "0"));
          // set active flag to true if the time of last sync for this person is newer than
          // the time of this sync start
          $active = ($timestampLastSyncPerson >= $timestampStart);
        }
      }
      if ($active !== null) {
        $this->router->log("info", " person " . $person["key"] . "setting active field to " . "active: " . ($active ? "1" : "0"));
        $this->db->setPerson($person["id_person"], [ "active" => $active ? 1 : 0 ], []);
       #$this->db->setPerson($person["id_person"], [ "active" => $active ], []); # TODO: RE-TEST THIS
      }
    }
    $this->router->log("debug", "asserting persons activity finished");
  }

  /**
   * Assert persons uniqueness
   */
  public function assertPersonsUniqueness() {
    $this->router->log("info", "asserting persons uniqueness (checking for field matching for every couple of persons)");
    $persons = $this->db->getPersons();
    $photos = $this->db->getAll("photo");

    # build an array of persons indexed by id
    $persons_count = count($persons);
    $photos_count = count($photos);
    for ($i = 0; $i < $persons_count; $i++) { // build a persons-by-id array
      $personId = $persons[$i]["id_person"];
      $personsById[$personId] = $persons[$i];
      $personsById[$personId]["photos"] = [];
      for ($j = 0; $j < $photos_count; $j++) { // add person photos array to persons-by-id array
        if ($photos[$j]["id_person"] === $personId) {
          array_push($personsById[$personId]["photos"], $photos[$j]);
        }
      }
    }

    # check every couple of persons (avoiding permutations)
    for ($i = 0; $i < $persons_count - 1; $i++) {
      // check only persons photos from system user, which are new (TODO: CHECK THIS!)
      /*
      if (
        !(
          $persons[$j]["timestamp_creation"] >= $timestampStartSync...) and
          $persons[$j]["user_id"] === SYSTEM_USER_ID...)
        )
      ) { next; }
      */

$this->router->log("debug", " person n°: $i (userId: " . $persons[$i]["id_user"] . ")");

      for ($j = $i + 1; $j < $persons_count; $j++) {

        // check only persons photos from system user, which are new (TODO: CHECK THIS!)
        /*
        if (
          !(
            $persons[$j]["timestamp_creation"] >= $timestampStartSync...) and
            $persons[$j]["user_id"] === SYSTEM_USER_ID...)
          )
        ) { next; }
        */

        if (
          $this->personsCheckSamePhone($persons[$i], $persons[$j]) ||
          $this->personsCheckSamePhotos($personsById, $persons[$i]["id_person"], $persons[$j]["id_person"])
        ) { // these two persons are to be unified
          $id1 = $persons[$i]["id_person"];
          $id2 = $persons[$j]["id_person"];

          // next chain
          $id = $idNext = $id1;
$n = 0;
          while ($id) {
            $idNext = $id;
            if ($idNext === $id1) { // do not set the same id as uniq_next field (avoid infinite loops)
              break;
            }
            $id = $personsById[$id]["uniq_next"];
            if (!$id) { # $idLast contains last id in uniqueness next-chain for this person
              $this->db->setPerson($idNext, null, [ "uniq_next" => $id2 ]); // save uniq_next id to last person in next-chain
            }
if (++$n >= 10) {
  $this->router->log("error", " loop detected in next chain (id: $id, id1: $id1, id2: $id2, idNext: $idNext)");
  return;
}
          }

          // prev chain
          $id = $idPrev = $id2;
$n = 0;
          while ($id) {
            $idPrev = $id;
            if ($idPrev === $id2) { // do not set the same id as uniq_prev field (avoid infinite loops)
              break;
            }
            $id = $personsById[$id]["uniq_prev"];
            if (!$id) { # $idPrev contains first id in uniqueness prev-chain for this person
              $this->db->setPerson($idPrev, null, [ "uniq_prev" => $id1 ]); // save uniq_prev id to first person in prev-chain
            }
if (++$n >= 10) {
  $this->router->log("error", " loop detected in prev chain (id: $id, id1: $id1, id2: $id2, idPrev: $idPrev)");
  return;
}
          }
        }
      }
    }

    $this->router->log("debug", "asserting persons uniqueness finished");
  }

  /**
   * Check two persons uniqueness comparing their phone numbers
   *
   * @param  array $person1    the first person
   * @param  array $person2    the second person
   * @return integer: true     the persons are uniq (same phone)
   *                  false    the persons are not uniq
   */
  private function personsCheckSamePhone($person1, $person2) {
if (
  ($person1["phone"] && $person2["phone"]) &&
  ($person1["phone"] === $person2["phone"])
) {
    $this->router->log("debug", " " . $person1["name"] . " phone is the same as " . $person2["name"] . " phone");
}
    return (
      ($person1["phone"] && $person2["phone"]) &&
      ($person1["phone"] === $person2["phone"])
    );
  }

  /**
   * Check two persons uniqueness comparing their photos
   *
   * @param  array $person1    the first person
   * @param  array $person2    the second person
   * @return integer: true     the persons are uniq (some photo in common)
   *                  false    the persons are not uniq
   */
  private function personsCheckSamePhotos($personsById, $id1, $id2) {
    $person1 = $personsById[$id1];
    $person2 = $personsById[$id2];

    $photo = new Photo($this->router, [ "data" => [] ]);
    $minDistance = $photo->getOption("signatureDuplicationMinDistance");

    foreach ($person1["photos"] as $photo1) {
      foreach ($person2["photos"] as $photo2) {
        if ($photo1["sum"] === $photo2["sum"]) { // the checksum matches
          $this->router->log("debug", " " . $person1["name"] . " and " . $person2["name"] . " have EQUAL photos");
          return true;
        }
        $distance = $photo->compareSignatures($photo1["signature"], $photo2["signature"]);
        if ($distance <= $minDistance) { // duplicate found
          $this->router->log("debug", " " . $person1["name"] . " and " . $person2["name"] . " have SIMILAR photos");
          return true;
        }
      }
    }
    return false;
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
      $showcase = $this->photoGetByShowcase($personId, true);
      $showcase = (isset($showcase["path_small"])) ? $showcase["path_small"]: null;
      $result[$personId]["photo_path_small_showcase"] = $showcase;
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
    return $this->db->getPersonsByField("phone", $phone, $userId);
  }
  
  public function add($personMaster, $personDetail = null, $userId = null) {
    return $this->db->addPerson($personMaster, $personDetail, $userId);
  }

  public function set($id, $personMaster, $personDetail = null, $userId = null) {
    return $this->db->setPerson($id, $personMaster, $personDetail, $userId);
  }

  public function delete($id, $userId = null) {
    return $this->db->deletePerson($id, $userId);
  }
  
  public function getPhotoOccurrences($id, $imageUrl) {
    $person = $this->db->getPerson($id);
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
    $value = preg_replace("/[,.;!].*$/", "", $value); // ignore anything after a punctuation character and after
    $value = preg_replace("/[()]/", "", $value); // ignore not meaningful characters
    $value = preg_replace("/\s+/", " ", $value); // squeeze blanks to one space
    $value = preg_replace("/^\s+/", "", $value); // ignore leading blanks
    $value = preg_replace("/\s+$/", "", $value); // ignore trailing blanks
    $value = preg_replace("/\n+/", "\n", $value); // remove empty lines
    $value = ucwords(strtolower($value)); // only initials upper case
    return $value;
  }

  private function normalizeDescription($value) {
    $value = preg_replace("/<br(?: \/)?>/", "\n", $value); // convert <br>'s to newlines
    $value = strip_tags($value); // strip all other html tags from source
    return $value;
  }

  private function normalizePhone($phone, $sourceKey) {
    $source = $this->sourcesDefinitions[$sourceKey];
    if (preg_match($source["patterns"]["person-phone-vacation"], $phone)) {
      $phone = "";
    } else {
      if (preg_match($source["patterns"]["person-phone-unavailable"], $phone)) {
        $phone = "";
      } else {
        $phone = preg_replace("/[^\d]*/", "", $phone); // ignore not number characters
      }
    }
    return $phone;
  }

  private function isPhoneActive($phone, $sourceKey) {
    return $phone ? true : false;
  }

  private function normalizeNationality($nationality) {
    # TODO: do we get any nationality value from sources? If we do, which is the format?
    return $nationality;
  }

  private function detectNationality($name, $description, $languageCode) {
    $patterns = [
      "it" => [
        "countries-female-nationalities" => [
          "alban(ia|ese)" => "al",
          "argentina" => "ar",
          "australi(a|ana)" => "au",
          "barbados" => "bb",
          "belg(a|io)" => "be",
          "bolivi(a|ana)" => "bo",
          "bosni(a|aca)" => "ba",
          "brasil(e|iana)" => "br",
          "caraibi(ca)?" => "bs",
          "bulgar(a|ia)" => "bu",
          "canad(a|ese)" => "ca",
          "capo\s*verd(e|iana)" => "cv",
          "ch?il(e|ena)" => "cl",
          "ch?in(a|ese)" => "cn",
          "orient(e|ale)" => "cn",
          "colombi(a|ana)" => "co",
          "costa\s*ric(a|he..)" => "cr",
          "croa([tz]ia|ta)" => "hr",
          "cub(a|ana)" => "cu",
          "c(zech|eca)" => "cz",
          "dan(imarca|ese)" => "dk",
          "dominic(a|ana)" => "do",
          "ecuador(e..)?" => "ec",
          "eston(ia|e)" => "ee",
          "finland(ia|ese)" => "fi",
          "franc(ia|ese|esina)" => "fr",
          "(germania|tedesc(a|ina))" => "de",
          "(gran bretagna|ing(hilterra|les(e|ina)))" => "en",
          "grec(a|ia)" => "gr",
          "greanad(a|iana)" => "gd",
          "guatemal(a|teca)" => "gt",
          "hait(i|iana)" => "ht",
          "h?ondur(as|e(...)?)" => "hn",
          "ungher(ia|ese)" => "hu",
          "island(a|ese)" => "is",
          "indi(a|ana)" => "in",
          "indonesi(a|ana)" => "id",
          "irland(a|ese)" => "ie",
          "israel(e|iana)" => "ie",
          "italian(a|issima)" => "it",
          "(j|gi)amaic(a|ana)" => "jm",
          "(japan)|(giappon(e|ese))" => "jp",
          "ken[iy](a|ana)" => "ke",
          "core(a|ana)" => "kr",
          "lituan(a|ia)" => "lt",
          "liban(o|ese)" => "lb",
          "letton(ia|e)" => "lv",
          "lussemburg(o|hese)" => "lu",
          "macedon(ia|e)" => "mk",
          "malta" => "mt",
          "me(x|ss)ic(o|ana)" => "mx",
          "moldov(a|iana)" => "md",
          "monaco" => "mc",
          "mongol(ia|a)" => "mn",
          "montenegr(o|ina)" => "me",
          "m([ao]rocco)|(arocchina)" => "ma",
          "oland(a|ese)" => "nl",
          "(neo|nuova)[\s-]?zeland(a|ese)" => "nz",
          "nicaragu(a|e...)" => "ni",
          "niger" => "ne",
          "nigeri(a|ana)" => "ng",
          "norveg(ia|ese)" => "no",
          "pa(k|ch)istan(a)?" => "pk",
          "panam(a|ense)" => "pa",
          "paragua(y|iana)" => "py",
          "peru(viana)?" => "pe",
          "(ph|f)ilippin(e|a)" => "ph",
          "pol(onia|acca)" => "pl",
          "portoric(o|ana)" => "pr",
          "portog(allo|hese)" => "pt",
          "r(omania|(o|u)mena)" => "ro",
          "d[ae]ll[\s']est" => "ro",
          "russ(i)?a" => "ru",
          "san[\s-]?marin(o|ese)" => "sm",
          "arab(i)?a" => "sa",
          "senegal(ese)?" => "sn",
          "serb(i)?a" => "rs",
          "se[yi]chelles" => "sc",
          "sierra[\s-]?leone" => "sl",
          "singapore" => "sg",
          "slovacch(i)?a" => "sk",
          "sloven(i)?a" => "si",
          "somal(i)?a" => "so",
          "spagn(a|ola)" => "es",
          "sve(zia|dese)" => "se",
          "svizzera" => "ch",
          "s[yi]ria(na)?" => "sy",
          "taiwan(ese)?" => "tw",
          "t(h)?ai(land(ia|ese)?)?" => "th",
          "trinidad" => "tt",
          "tunisi(a|ina)" => "tn",
          "turc(hia|a)" => "tr",
          "u[kc]raina" => "ua",
          "urugua([yi])|(gia)|([yi]ana)" => "uy",
          "america(na)?" => "us",
          "venezuela(na)?" => "ve",
          "vietnam(ita)?" => "vn",
        ],
        "negative-lookbehind" => [
          "alla",
          "amica",
          "autostrada",
          "area",
          "belvedere",
          "borgata",
          "borgo",
          "calata",
          "campo",
          "carraia",
          "cascina",
          "circonvallazione",
          "circumvallazione",
          "contrada",
          "c\.so",
          "corso",
          "cso",
          "diramazione",
          "frazione",
          "isola",
          "largo",
          "lido",
          "litoranea",
          "loc\.",
          "località",
          "lungo",
          "masseria",
          "molo",
          "parallela",
          "parco",
          "passaggio",
          "passo",
          "p\.za",
          "p\.zza",
          "piazza",
          "piazzale",
          "piazzetta",
          "ponte",
          "quartiere",
          "regione",
          "rione",
          "rio",
          "riva",
          "riviera",
          "rondò",
          "rotonda",
          "salita",
          "scalinata",
          "sentiero",
          "sopraelevata",
          "sottopassaggio",
          "sottopasso",
          "spiazzo",
          "strada",
          "stradone",
          "stretto",
          "svincolo",
          "superstrada",
          "tangenziale",
          "traforo",
          "traversa",
          "v\.",
          "via",
          "viale",
          "vicolo",
          "viottolo",
          "zona",
        ],
      ],
    ];

    if (array_key_exists($languageCode, $patterns)) {
      foreach ([ null, $name, $description ] as $field) {
        if ($field) {
          foreach ($patterns[$languageCode]["countries-female-nationalities"] as $countryPattern => $countryCode) {
            $pattern = "/\b" . $countryPattern . "\b/si";
            if (preg_match($pattern, $field)) { // country pattern matched
              $falsePositive = false;
              // check it doesn't match with negative lookbehind
              foreach ($patterns[$languageCode]["negative-lookbehind" ] as $negativeLookbehind) {
                $pattern = "/\b" . $negativeLookbehind . "" . "\s+" . $countryPattern . "\b/si";
                if (preg_match($pattern, $field)) { // country pattern matched with a negative lookbehind
                  $falsePositive = true;
                  break;
                }
              }
              if (!$falsePositive) { // no negative lookbehind matched for this country pattern and for this field
                return $countryCode; // return on first match
              }
            }
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
    $countries = [];
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

  /**
   * Add a photo
   *
   * @param  integer $personId the id of the person's photo
   * @param  string $photoUrl  the url of the photo
   * @return integer: false    photo not added (duplication / similarity)
   *                  >= 0     photo added to filesystem and to database
   */
  public function photoAdd($personId, $photoUrl) {
    // build photo object from url
    try {
      $photo = new Photo($this->router, [ "url" => $photoUrl ]);
    } catch (Exception $e) {
      $this->router->log("error", "can't create new photo from url [$photoUrl]: " . $e->getMessage());
      return false;
    }

    $photos = $this->db->getByField("photo", "id_person", $personId);

#   CURRENTLY, NO SOURCE SITE GIVES A VALID LAST-MODIFICATION-TIMESTAMP FOR THE PHOTOS...
#   // check if photo url did not change from last download
#   if ($this->photoCheckLastModified($personId, $photo, $photos)) {
#     $this->router->log("debug", "photoAdd [$photoUrl] for person id " . $personId . " is not changed, ignoring");
#     return false; // same Last-Modified tag found
#   }

    // check if photo is an exact duplicate
    if ($this->photoCheckDuplication($photo, $photos)) {
      return false; // duplicate found
    }

    // check if photo has similarities
    $photo->signature();
    if ($this->photoCheckSimilarity($photo, $photos)) {
      return false; // similarity found
    }
    $this->router->log("debug", "photoAdd() [$photoUrl] for person id " . $personId . " SEEMS NEW, ADDING TO DB...");

    # TODO: test this new showcase strategy
    $showcase = (count($photos) === 0); // set showcase flag to true if this is the first image

    $photo->idPerson($personId);
    $photo->domain();
    $photo->sum();
    $photo->timestampCreation(time());
    $photo->thruthful("unknown"); // this is an offline-set property (it's very expensive to calculate)
   
    // store this photo
    if (($number = $this->photoStore($personId, $photo)) === false) {
      $this->router->log("error", "photo " . $photo->url() . " for person id " . $personId . " could not be stored locally");
      return false; // error storing photo locally
    }
    $photo->number($number);

    // add this photo to database
    $retval = $this->db->add("photo", $this->photo2Data($photo));

    // delete the photo object
    unset($photo);

    return $retval;
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
/*
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
*/
  /**
   * Check for photo similarity
   *
   * @param  Photo: $photo       the photo object to check for similarity
   * @param  array: $photos      the array of photos to be checkd against
   * @return boolean: true       if photo is similar to some else photo
   *                  false      if photo is not similar to some else photo
   */
/*
  private function photoCheckSimilarity($photo, $photos) {
#return false; # TODO: DEBUG_ONLY: CHECK IF assertPersonsUniqueness() SLOWNESS COMES FROM THIS FUNCTION...
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
*/

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
#$this->router->log("debug", "photoStore() - 1 - bitmapFull path: ", $pathnameFull);
    if ((file_put_contents($pathnameFull, $photo->bitmapFull())) === false) {
      $this->router->log("error", "can't save photo to file [$pathnameFull]");
      return false;
    }
#$this->router->log("debug", "photoStore() - 2 - bitmapSmall path: ", $pathnameSmall);
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

# TODO: ONLY TO DEBUG ###############################################
#require "classes/services/Network.php";
#$pc = new PersonsController(null);
#$name = "Amina russa";
#$description = "xyz...";
#$nationality = $pc->detectNationality($name, $description, "it");
#print("Nationality [$nationality] was detected for [$name], [$description]\n");
#exit;
#####################################################################

?>