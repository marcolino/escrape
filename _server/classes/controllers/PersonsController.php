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
    /**
     * Persons file
     */
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
                "person-phone-unavailable" => "/In arrivo dopo le vacanze !!/s",
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
    ];
    private $patternEmail = "/^\S+@\S+\.\S+$/";
    #private $data = [];
    
    /**
     * Constructor
     */
    function __construct($app) {
        $this->app = $app;
        $this->setup();
    }

    private function setup() {
        $this->load();
    }
    
    /**
    * Sync persons
    *
    * @param  array $parameters
    * @return array
    */
    public function sync() {
        $this->app->log->info("sync()");

        $changed = false;
        foreach ($this->personsDefinition as $siteid => $site) {
            $url = $site["url"] . "/" . $site["path"];
#INCOGNITO# #DEBUG#
#$url = "http://localhost/escrape/server/debug/sgi.html";
#if ($siteid != "mor") continue;

            $this->app->log->info("url: [$url]");
            $page = $this->getUrlContents($url, $site["charset"]);

            if ($page === FALSE) {
                $this->app->log->error("can't get page contents on site [$siteid]");
                continue;
            }
/*
            if (preg_match($site["patterns"]["persons"], $page, $matches)) {
                $persons_page = $matches[1];
            } else {
                $this->app->log->error("persons pattern not found on site [$siteid] [" . $site["patterns"]["persons"] . "]");
                continue;
            }
*/
            $persons_page = $page;

            if (preg_match_all($site["patterns"]["person"], $persons_page, $matches)) {
                $person_cells = $matches[1];
            } else {
                $this->app->log->error("not any person pattern found on site [$siteid]");
                continue;
            }
            
            $n = 0;
            foreach ($person_cells as $person_cell) {
                $n++;
if ($n > 7) break;

                if (preg_match($site["patterns"]["person-id"], $person_cell, $matches) >= 1) {
                    $id = $matches[1];
                    $id = $siteid . "-" . $id;
$this->app->log->debug($id);
                } else {
                    $this->app->log->error("person $n id not found on site [$siteid]");
                    continue;
                }

                if (preg_match($site["patterns"]["person-details-url"], $person_cell, $matches) >= 1) {
                    $details_url = $site["url"] . "/" . $matches[1];
                } else {
                    $this->app->log->error("person $n details url not found on site [$siteid]");
                    continue;
                }

#INCOGNITO# #DEBUG#
#$details_url = "http://192.168.10.30/escrape/server/debug/adv4946.html";
                $this->app->log->debug($details_url);
                if (($page_details = $this->getUrlContents($details_url, $site["charset"])) === FALSE) {
                    $this->app->log->error("can't get person $n url contents on site [$siteid]");
                    continue;
                }
                $timestamp = time(); # current timestamp, we don't have page last modification date...
                $page_sum = md5($page_details);

                if (preg_match($site["patterns"]["person-name"], $page_details, $matches) >= 1) {
                    $name = $this->cleanName($matches[1]);
                } else {
                    $this->app->log->error("person $n name not found on site [$siteid]");
                    continue;
                }
                
                if (preg_match($site["patterns"]["person-sex"], $page_details, $matches) >= 1) {
                    $sex = $matches[1];
                } else {
                    #$this->app->log->warning("person $n sex not found on site [$siteid]");
                    $sex = "";
                    #continue;
                }

                if (preg_match($site["patterns"]["person-zone"], $page_details, $matches) >= 1) {
                    $zone = $matches[1];
                } else {
                    #$this->app->log->warning("person $n zone not found on site [$siteid]");
                    $zone = "";
                    #continue;
                }
                
                if (preg_match($site["patterns"]["person-description"], $page_details, $matches) >= 1) {
                    $description = $matches[1];
                } else {
                    #$this->app->log->warning("person $n description not found on site [$siteid]");
                    $description = "";
                    #continue;
                }
 
                if (preg_match($site["patterns"]["person-phone"], $page_details, $matches) >= 1) {
                    $phone = $this->normalizePhone($matches[1]);
                } else {
                    $this->app->log->error("person $n phone not found on site [$siteid]");
                    continue;
                }
                    
                if (preg_match_all($site["patterns"]["person-photo"], $page_details, $matches) >= 1) {
                    $photos = $matches[1];
                } else {
                    $this->app->log->error("photo pattern not found on site [$siteid]");
                    continue;
                }

                #$key = $siteid . "-" . $phone; # TODO: don't use phone, get a unique key from site...
                $person = [];
                #$person["key"] = $key;
                $person["id"] = $id;
                $person["name"] = $name;
                $person["site"] = $siteid;
                $person["url"] = $details_url;
                $person["timestamp"] = $timestamp;
                $person["sex"] = $sex;
                $person["zone"] = $zone;
                $person["description"] = $description;
                $person["phone"] = $phone;
                $person["page_sum"] = $page_sum;
                $person["photos"] = [];
                $person["age"] = null; # age
                $person["vote"] = null; # vote ([0-1])
if ($name == "KAREN") {
    $person["vote"] = .9;
}
                foreach ($photos as $photo) {
                    $person["photos"][] = $photo;
                }

                { # TODO: merge person...
                    $changed = true;
                    #$this->setPerson($key, $person);
                    $this->setPerson($id, $person);
                }
            }
        }
        if ($changed) {
            $this->store();
        }
        return true;
    }

    public function load() {
        if (!isset($this->db)) {
            $this->db = new Db();
        }
        $this->db->load("persons");
    }

    public function store() {
        if (!isset($this->db)) {
            throw new Exception("can't store: database is not loaded");
        }
        $this->db->store("persons");
    }

    public function getPersons() {
        return($this->db->data["persons"]);
    }

    public function get($id) {
        return $this->db->data["persons"][$id];
    }
    
    public function getPerson($id) {
        return $this->db->data["persons"][$id];
    }
    
    public function setPersons($persons) { # TODO: do we need this ?
        $this->db->data["persons"] = $persons;
    }

    public function setPerson($id, $person) {
        $this->db->data["persons"][$id] = $person;
    }

    public function update($id, $person) {
        foreach ($person as $field => $value) {
#print "field = $field, value = $value \n";
            $this->db->data["persons"][$id][$field] = $value;
        }
    }

    public function setPersonVote($id, $person, $vote) {
        if (!is_numeric($vote) || $vote < 0 || $vote > 1) {
            throw new Exception("can't set person vote: vote must be a number in range [0-1]");
        }
        $this->db->data["persons"][$id]["vote"] = $vote;
    }

    public function deletePersons() {
        $this->db->data["persons"] = [];
    }

    public function deletePerson($id) {
        unset($this->db->data["persons"][$id]); # note: unset() leaves empty indexes
    }

    /**
     * get persons list
     *
     * @param  array $filter
     * @return array
     */
    public function getList($filter) {
        $list = [];
        // filter persons by column names
#$n = 0;
#var_dump($this->getPersons()); exit;
        foreach ($this->getPersons() as $id => $value) {
if (!isset($value["site"])) { continue; } # TODO: skip fake records... remove-me...
            $list[$id] = [
                "id" => $id,
                "site" => $value["site"],
                "name" => $value["name"],
                "phone" => $value["phone"],
                "vote" => $value["vote"],
                "age" => $value["age"],
                "photo" => $this->personsDefinition[$value["site"]]["url"] . "/" . $value["photos"][0],
            ];
#if (++$n == 227) {var_dump($list[$id]); exit;}
#if (++$n >= 226) break;
#var_dump($value); exit;
        }

        // filter persons by column values
        return $this->filter($list, $filter);
/*
        return [
            "personsDefinition" => $this->getPersonsDefinition(),
            "personsList" => $this->filter($list, $filter),
        ];
*/
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
        $referer = "http://localhost/escrape";
        $user_agent = "Mozilla";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $output = curl_exec($ch);
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
            $result = preg_replace("/[^\d]*              /", "", $phone);
        }
        return $result;
*/
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

   /**
     * Destructor
     */
    function __destruct() {
    }

}