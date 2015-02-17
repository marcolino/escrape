<?php
/**
 * Persons controller
 * 
 * @package PersonsController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

# TODO:
#  - normalizePhone(), ... etc ...: in a common class...

class PersonsController extends AbstractController {

  private $personsDefinition = [
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
        "person" => "/<!-- Inizio Anteprima Escort -->(.*?)<!-- Fine Anteprima Escort -->/s",
        "person-id" => "/<a href=\".*?([^\_]*?)\.html\".*?>.*?<\/a>/s",
        "person-details-url" => "/<a href=\"(.*?)\".*?>.*?<\/a>/s",
        "person-img-url" => "",
        "person-name" => "/<h\d class=\"nome\">\s*(.*?)\s*<\/h\d>/s",
        "person-sex" => "/<h\d class=\"sesso\">\s*(.*?)\s*&nbsp;.*?<\/h\d>/s",
        "person-zone" => "/(?:<a href=\"#quartiere\".*?>(.*?)<\/a>)/s",
        "person-description" => "/<meta name=\"description\".*?content=\"(.*?)\".*?\/>/s",
        "person-phone" => "/<h\d class=\"phone\">\s*(?:<img.*? \/>)\s*(.*?)\s*<\/h\d>/s",
        "person-phone-vacation" => "/In arrivo dopo le vacanze !!/s",
        "person-phone-unavailable" => "/In arrivo dopo le vacanze !!/s", # TODO
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
    ],
    */
  ];
  private $patternEmail = "/^\S+@\S+\.\S+$/";
  
  /**
   * Constructor
   */
  function __construct($router) {
    $this->router = $router;
    $this->db = new DB();
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
    foreach ($this->personsDefinition as $siteId => $site) {
      $url = $site["url"] . "/" . $site["path"];
#INCOGNITO# #DEBUG#
#$url = "http://localhost/escrape/server/debug/sgi.html";
#if ($siteId != "mor") continue;

      $this->router->log("info", "url: [$url]");
      $page = $this->getUrlContents($url, $site["charset"]);

      if ($page === FALSE) {
        $this->router->log("error", "can't get page contents on site [$siteId]");
        continue;
      }
/*
      if (preg_match($site["patterns"]["persons"], $page, $matches)) {
        $persons_page = $matches[1];
      } else {
        $this->router->log("error", persons pattern not found on site [$siteId] [" . $site["patterns"]["persons"] . "]");
        continue;
      }
*/
      $persons_page = $page;

      if (preg_match_all($site["patterns"]["person"], $persons_page, $matches)) {
        $person_cells = $matches[1];
      } else {
        $this->router->log("error", "not any person pattern found on site [$siteId]");
        continue;
      }
      
      $n = 0;
      foreach ($person_cells as $person_cell) {
        $n++;
#if ($n > 3) break;

        if (preg_match($site["patterns"]["person-id"], $person_cell, $matches) >= 1) {
          $id = $matches[1];
          $key = $siteId . "-" . $id;
        } else {
          $this->router->log("error", "person $n id not found on site [$siteId]");
          continue;
        }

        if (preg_match($site["patterns"]["person-details-url"], $person_cell, $matches) >= 1) {
          $details_url = $site["url"] . "/" . $matches[1];
        } else {
          $this->router->log("error", "person $n details url not found on site [$siteId]");
          continue;
        }

#INCOGNITO# #DEBUG#
#$details_url = "http://192.168.10.30/escrape/server/debug/adv4946.html";
        $this->router->log("debug", $details_url);
        if (($page_details = $this->getUrlContents($details_url, $site["charset"])) === FALSE) {
          $this->router->log("error", "can't get person $n url contents on site [$siteId]");
          continue;
        }
        $timestamp = time(); # current timestamp, we don't have page last modification date...
        $page_sum = md5($page_details);

        if (preg_match($site["patterns"]["person-name"], $page_details, $matches) >= 1) {
          $name = $this->cleanName($matches[1]);
        } else {
          $this->router->log("error", "person $n name not found on site [$siteId]");
          continue;
        }
        
        if (preg_match($site["patterns"]["person-sex"], $page_details, $matches) >= 1) {
          $sex = $matches[1];
        } else {
          #$this->router->log("warning", "person $n sex not found on site [$siteId]");
          $sex = "";
          #continue;
        }

        if (preg_match($site["patterns"]["person-zone"], $page_details, $matches) >= 1) {
          $zone = $matches[1];
        } else {
          #$this->router->log("warning", "person $n zone not found on site [$siteId]");
          $zone = "";
          #continue;
        }
        
        if (preg_match($site["patterns"]["person-description"], $page_details, $matches) >= 1) {
          $description = $matches[1];
        } else {
          #$this->router->log("warning", "person $n description not found on site [$siteId]");
          $description = "";
          #continue;
        }
 
        if (preg_match($site["patterns"]["person-phone"], $page_details, $matches) >= 1) {
          $phone = $this->normalizePhone($matches[1]);
        } else {
          $this->router->log("error", "person $n phone not found on site [$siteId]");
          continue;
        }
          
        if (preg_match_all($site["patterns"]["person-photo"], $page_details, $matches) >= 1) {
          $photosUrl = $matches[1];
        } else {
          $this->router->log("error", "photo pattern not found on site [$siteId]");
          continue;
        }

        $person = [];
        $person["key"] = $key;
        $person["name"] = $name;
        $person["site"] = $siteId;
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

        #foreach ($photos as $photo) {
        #  $person["photos"][] = $photo;
        #}

        if (($id = $this->db->getByKey("person", $key))) { # old key, update it
          $this->router->log("debug", "updating person: $key °°°");
          $this->set($id, $person); # error handling?
          $photos = new PhotosController();
          foreach ($photosUrl as $photoUrl) {
            $photo = [];
            $photo["id_person"] = $id;
            $photo["url"] = $photoUrl;
            $photos->set($photo);
          }
          #$this->db->data["persons"][$id] = $person;
        } else { # new key, insert it
          $this->router->log("debug", "inserting person: $key °°°");
          $id = $this->add($person);
          $num = 0;
          foreach ($photosUrl as $photoUrl) {
            $photo = [];
            $photo["id_person"] = $id;
            $photo["showcase"] = ($num === 0);
            $photo["url"] = $photoUrl;
            $photos->add($photo);
            $num++;
          }
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
  public function getList($filter) {
    $list = [];
    $comments = new CommentsController();
    $photos = new PhotosController();

    foreach ($this->db->getAll("person") as $person_id => $person) {
      #$comments_count = $this->db->countByField("comment", "phone", $value["phone"]);
      #$comments_count = $comments->countByPhone($value["phone"]);
      $list[$id] = [
        "id" => $person_id,
        "key" => $person["key"],
        "site" => $person["site"],
        "name" => $person["name"],
        "phone" => $person["phone"],
        "vote" => $person["vote"],
        "age" => $person["age"],
        "photo" => $photos->getPhotoShowcase($person_id),
        "comments_count" => $comments->countByPerson($person_id),
        "comments_average_valutation" => $comments->getAverageValutationByPerson($person_id),
      ];
    }

    // filter persons by column values
    return $list;
  }

public function putVote($params) { return $this->setVote($params); }
  /**
   * set person vote
   *
   * @param  array $filter
   * @return array
   */
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

  public function getPersonsDefinition() {
    $personsDefinition = [];
    foreach ($this->personsDefinition as $id1 => $value1) {
      foreach ($value1 as $id2 => $value2) {
        if ($id2 != "patterns") {
          $personsDefinition[$id1][$id2] = $value2;
        }
      }
    }
    return $personsDefinition;
  }

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

  /**
   * filter list
   *
   * @return array
   */
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
    $value = preg_replace("/[()]/", "", $value);
    $value = preg_replace("/\s+/", " ", $value);
    $value = preg_replace("/\s+$/", "", $value);
    $value = preg_replace("/^\s+/", "", $value);
    $value = ucfirst($value);
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
    $phone = preg_replace("/[^\d]*/", "", $phone);
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
   * Destructor
   */
  function __destruct() {
  }

}