<?php

require "vendor/autoload.php";
require "classes/controllers/UsersController.php";
require "classes/controllers/PersonsController.php";
require "classes/controllers/CommentsController.php";
require "classes/services/Network.php";
require "classes/services/Photo.php";
require "classes/services/Db.php";
require "classes/services/GoogleSearch.php";

class Router {

  public function __construct() {
    require_once "setup/cfg.php"; // global configuration setup
    $logPath = "./logs/"; // logs path
    $logNameFormat = "Y-m-d"; // logs name format (passed to date())

    date_default_timezone_set($this->cfg["timezone"]);
    $app = new \Slim\Slim();
    $debugMode = $this->cfg["debugMode"];
    $app->configureMode("development", function () use ($app, $logPath, $logNameFormat, $debugMode) {
      $app->config([
        "debug" => $debugMode,
      ]);
      $log = $app->getLog();
      $log->setEnabled(true);
      $log->setLevel($debugMode ? \Slim\Log::DEBUG : \Slim\Log::ERROR);
      $log->setWriter(new \Slim\Extras\Log\DateTimeFileWriter([
        "path" => $logPath,
        "name_format" => $logNameFormat,
        "message_format" => "%date% - %label% - %message%",
      ]));
    });
    $this->app = $app;
    $this->db = new DB($this);
    $this->logs = [];
  }

  public function run() {
    # === persons group ====================================================
    $this->app->group("/persons", function () {
      $this->app->get("/test", function() { # ============================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->test());
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/testuniqcode", function() { # ============================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->testuniqcode());
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->post("/get", function() { # ==================
        try {
          $persons = new PersonsController($this);
          $data = json_decode($this->app->request()->getBody(), true); // second parameter uses associative arrays instead of stdClass
          $this->success($persons->getList($data));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/get/:id", function($id) { # ======================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->get($id));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/sync", function() { # =============================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->sync());
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/sync/new", function() { # =========================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->sync(true));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->post("/photo/get/occurrences", function() { # ===========
        try {
          $persons = new PersonsController($this);
          $data = json_decode($this->app->request()->getBody()); // content-type: application/json
          #$this->success([ [ "text" => json_encode([$data->id, $data->url]), "imgsrc" => "https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcQbm5ipONSdcESkgazDxbUhFNWthW5cHzWPvF5Bv2nfRqXZNQvYJwSJ7Q", "href" => "https://www.google.it/url?sa=t&rct=j&q=&esrc=s&source=web&cd=11&cad=rja&uact=8&ved=0CEoQFjAK&url=http%3A%2F%2Fwww.perugiatoday.it%2Fcronaca%2Fstrage-gatti-perugia-orrore.html&ei=Q6j8VPa1I4bnyQPE94GIAQ&usg=AFQjCNHJyixc1C1PiBV1uDNIGUcKeF88Zg&sig2=qrSffEYjNkzlCImtA1W0cA" ] ]);
          $this->success($persons->photoGetOccurrences($data->id, $data->url));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->post("/photo/get/carddeck", function() { # ===========
        try {
          $persons = new PersonsController($this);
          $data = json_decode($this->app->request()->getBody()); // content-type: application/json
          $this->success($persons->photoGetCardDeck($data->urls));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/search/:query", function($query) { # ==============
        try {
          $persons = new PersonsController($this);
          $this->success($persons->searchByName($query));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->put("/set/:id", function($id) { # =======================
        try {
          $persons = new PersonsController($this);
          $data = json_decode($this->app->request()->getBody());
          $id = $this->app->request()->params("id");
          $data = $this->app->request()->params("data");
          $this->success($persons->set($id, $data));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->post("/insert", function() { # ==========================
        try {
          $persons = new PersonsController($this);
          $data = $this->app->request()->params("data");
          $this->success($persons->insert($data));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->delete("/delete/:id", function($id) { # =================
        try {
          $persons = new PersonsController($this);
          //$data = json_decode($this->app->request()->getBody());
          $this->success($persons->delete($id));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/getSourcesCountries", function() { # =======================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->getSourcesCountries());
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/getSourcesCities/:countryCode", function($countryCode) { # ==
        try {
          $persons = new PersonsController($this);
          $this->success($persons->getSourcesCities($countryCode));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
/*
      $this->app->get("/getUniqIds/:id", function($id) { # ==========================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->getUniqIds($id));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
*/
    }); # ===================================================================

    # === users group =======================================================
    $this->app->group("/users", function () {
      $this->app->post("/register", function() { # ========================
        try {
          $users = new UsersController($this);
          $data = json_decode($this->app->request()->getBody());
          $this->success($users->register($data->username, $data->password));
          #$username = $this->app->request()->params("username");
          #$password = $this->app->request()->params("password");
          #$this->success($users->register($username, $password));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->post("/login", function() { # ===========================
        try {
          $users = new UsersController($this);
          $data = json_decode($this->app->request()->getBody());
          $this->success($users->login($data->username, $data->password));
          #$username = $this->app->request()->params("username");
          #$password = $this->app->request()->params("password");
          #$this->success($users->login($username, $password));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->delete("/delete/:id", function($id) { # ==================
        try {
          $users = new UsersController($this);
          $this->success($users->delete($id));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
    }); # ===================================================================

    # comments group ========================================================
    $this->app->group("/comments", function () {
      $this->app->get("/get", function() { # ==============================
        try {
          $comments = new CommentsController($this);
          $this->success($comments->getAll());
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/get/:id", function($id) { # =======================
        try {
          $comments = new CommentsController($this);
          $this->success($comments->get($id));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->get("/getByPhone/:phone", function($phone) { # ==========
        try {
          $comments = new CommentsController($this);
          $this->success($comments->getByPhone($phone));
        } catch (Exception $e) {
          $this->error($e);
        }
      });
      $this->app->post("/sync", function() { # ============================
        # TODO: why POST???? GET!!!
        try {
          $comments = new CommentsController($this);
          $this->success($comments->sync());
        } catch (Exception $e) {
          $this->error($e);
        }
      });
    }); # ===================================================================

    $this->app->options("/.+", function() { $this->success(null); }); # DEBUG: only to allow *all* CORS requests... (grunt / apache)
    $this->app->error(function(Exception $e) { $this->error($e); }); # app->
    $this->app->notFound(function() { $this->unforeseen(); }); # app->
    $this->app->run();
  }

  public function log($level, $value) {
    switch ($level) {
      default:
      case "fatal": // FATAL
        $this->app->getLog()->fatal($value);
        break;
      case "error": // ERROR
        $this->app->getLog()->error($value);
        break;
      #case "warn": // WARN
      case "warning": // WARNING
        $this->app->getLog()->warn($value);
        break;
      case "info": // INFO
        $this->app->getLog()->info($value);
        break;
      case "debug": // DEBUG
        $this->app->getLog()->debug($value);
        break;
    }
    #$this->app->logs[$level][] = $value;
  }

  private function unforeseen() {
    $response = $this->app->response();
    $this->access_control_allow($response);
    $response->body(json_encode([
      "contents" => null,
      "error" => [
        "message" => "Unforeseen route [" . $this->app->router()->getCurrentRoute() . "]",
      ],
    ]));
  }

  private function success($value) {
#print "success:"; var_export($value);
    $response = $this->app->response();
    $this->access_control_allow($response);
#print "success:"; var_dump($value);
#print "=================\n";
#$value['description'] = null;
#foreach ($value as $k => $v) {
#  print "[$k] => [$v]\n";
#}
#exit;
#print json_encode($value); exit;
    #$response->body(json_encode([ "contents" => $value ]));
    $response->body(json_encode($value));
# TODO: use "contents" ... 
  }
  
  private function error($error) {
    $response = $this->app->response();
    $this->access_control_allow($response);
    #if (isset($_SERVER["HTTP_ORIGIN"])) {
    #  $response->header("Access-Control-Allow-Origin", "{$_SERVER["HTTP_ORIGIN"]}");
    #  $response->header("Access-Control-Allow-Credentials", "true");
    #}
    $response->body(json_encode([
      #"contents" => null,
      "error" => [
        "message" => $error->getMessage(),
        "code" => $error->getCode(),
        "file" => $error->getFile(),
        "line" => $error->getLine(),
        "trace" => $error->getTrace(), #AsString(),
        //"html" => $this->exception2HTML($error), // TODO: use it?
      ],
      "log" => $this->logs,
    ]));
  }

  private function exception2HTML($err) {
    $trace = "";
    foreach ($err->getTrace() as $a => $b) {
      $aval = strval($a);
      foreach ($b as $c => $d) {
        if ($c === "args") {
          foreach ($d as $e => $f) {
            #$trace .= "<tr><td><b>$aval#</b></td><td align='right'><u>args:</u></td> <td><u>$e</u>:</td><td><i>$f</i></td></tr>";
            $trace .= "<tr><td><b>$aval#</b></td>";
            $trace .= "<td align='right'><u>args:</u></td> <td><u>$e</u>:</td>";
            $trace .= "<td><i>" . var_export($f, true) . "</i></td></tr>";
          }
        } else {
          $trace .= "<tr><td><b>$aval#</b></td><td align='right'><u>$c</u>:</td><td></td><td><i>$d</i></td>";
        }
      }
    }
    $code = strval($err->getCode());
    $message = $err->getMessage();
    $file = $err->getFile();
    $line = strval($err->getLine());
    $trace = "
      <font face='Monospace'>
        <center>
          <fieldset style='width: 66%; border: 4px solid white; background: black;'>
            <legend><b>[</b>PDO Error<b>]</b></legend>
            <table border='0'>
              <tr>
                <td align='right'>
                  <b><u>Message:</u></b></td><td><i>$message</i>
                </td>
              </tr>
              <tr>
                <td align='right'>
                  <b><u>Code:</u></b></td><td><i>$code()</i>
                </td>
              </tr>
              <tr>
                <td align='right'>
                  <b><u>File:</u></b></td><td><i>$file</i>
                </td>
              </tr>
              <tr>
                <td align='right'>
                  <b><u>Line:</u></b>
                </td>
                <td>
                  <i>$line</i>
                </td>
              </tr>
              <tr>
                <td align='right'>
                  <b><u>Trace:</u></b>
                </td>
                <td>
                  <br /><br />
                  <table border='0'>
                    $trace
                  </table>
                </td>
              </tr>
            </table>
          </fieldset>
        </center>
      </font>
    ";
    return $trace;
  }

  private function access_control_allow($response) {
    if (isset($_SERVER["HTTP_ORIGIN"])) {
      $response->header("Access-Control-Allow-Origin", "{$_SERVER['HTTP_ORIGIN']}");
      $response->header("Access-Control-Allow-Credentials", "true");
    }
  
    if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
      if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
        $response->header("Access-Control-Allow-Methods", "GET, PUT, POST, OPTIONS");     
      }
      if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
        $response->header("Access-Control-Allow-Headers", "{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
      }
    }
  }

};

ini_set("display_errors", "On");
error_reporting(E_ALL);
$router = new Router();
$router->run();

?>