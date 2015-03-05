<?php

require "vendor/autoload.php"; // Slim #require "Slim/Slim.php";
require "classes/controllers/UsersController.php";
require "classes/controllers/PersonsController.php";
require "classes/controllers/CommentsController.php";
require "classes/services/Photo.php";
require "classes/services/Db.php";

class Router {

  public function __construct() {
    $timezone = "Europe/Rome"; // default timezone
    $logFile = "./logs/slim.log"; // null to disable log
    $mode = "development"; // mode tag
    #$debug = true; // debug flag

    date_default_timezone_set($timezone);
    $logWriter = new \Slim\LogWriter(fopen($logFile, "a"));
    $this->app = new \Slim\Slim([
      "log.enable" => ($logFile != null),
      "log.writer" => $logWriter,
      "debug" => false, // use custom error handler (?)
      "mode" => "development",
    ]);
    $this->db = new DB();
    $this->logs = [];
  }

  public function run() {
    # === persons group =============================================
    $this->app->group("/persons", function () {
      $this->app->get("/test", function() { # =====================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->test());
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->get("/get", function() { # ======================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->getList());
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->get("/get/:id", function($id) { # ===============
        try {
          $persons = new PersonsController($this);
          $this->success($persons->get($id));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->get("/sync", function() { # ======================
        try {
          $persons = new PersonsController($this);
          $this->success($persons->sync());
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->post("/photo/get/occurrences/", function() { # ====
        try {
          $persons = new PersonsController($this);
          #$url = $this->app->request()->get('url');
          $data = json_decode($this->app->request()->getBody());
          $this->success($persons->photoGetOccurrences($data->url));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->get("/search/:query", function($query) { # =======
        try {
          $persons = new PersonsController($this);
          #$query = json_decode($this->app->request()->getBody());
          $this->success($persons->searchByName($query));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->put("/set/:id", function($id) { # ================
        try {
          $persons = new PersonsController($this);
          $data = json_decode($this->app->request()->getBody());
          $this->success($persons->set($id, $data));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->post("/insert", function() { # ===================
        try {
          $persons = new PersonsController($this);
          $data = json_decode($this->app->request()->getBody());
          $this->success($persons->insert($data));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->delete("/delete/:id", function($id) { # ==========
        try {
          $persons = new PersonsController($this);
          //$data = json_decode($this->app->request()->getBody());
          $this->success($persons->delete($id));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
    }); # ============================================================

    # === users group ================================================
    $this->app->group("/users", function () {
      $this->app->post("/register", function() { # =================
        try {
          $users = new UsersController($this);
          $data = json_decode($this->app->request()->getBody());
          $this->success($users->register($data->username, $data->password));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->post("/login", function() { # ====================
        $data = json_decode($this->app->request()->getBody());
        try {
          $users = new UsersController($this);
          $this->success($users->login($data->username, $data->password));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->delete("/delete/:id", function($id) { # ===========
        try {
          $users = new UsersController($this);
          $this->success($users->delete($id));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
    }); # ============================================================

    # comments group =================================================
    $this->app->group("/comments", function () {
      $this->app->get("/get", function() { # =======================
        try {
          $comments = new CommentsController($this);
          $this->success($comments->getAll());
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->get("/get/:id", function($id) { # ================
        try {
          $comments = new CommentsController($this);
          $this->success($comments->get($id));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->get("/getByPhone/:phone", function($phone) { # ===
        try {
          $comments = new CommentsController($this);
          $this->success($comments->getByPhone($phone));
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
      $this->app->post("/sync", function() { # =====================
        # TODO: why POST???? GET!!!
        try {
          $comments = new CommentsController($this);
          $this->success($comments->sync());
        } catch (Exception $e) {
          $this->error($e);
        } catch (PDOException $e) {
          $this->error($e);
        }
      });
    }); # ============================================================

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
    $this->app->logs[$level][] = $value;
  }

  private function unforeseen() {
    $response = $this->app->response();
    $this->access_control_allow($response);
    $response->body(json_encode([
      "response" => null,
      "error" => [
        "message" => "Unforeseen route [" . $this->app->router()->getMatchedRoutes() . "]",
      ],
    ]));
  }

  private function success($value) {
    $response = $this->app->response();
    $this->access_control_allow($response);
    if ($value) { $response->body(json_encode($value)); }
  }
  
  private function error($error) {
    $response = $this->app->response();
    $this->access_control_allow($response);
    #if (isset($_SERVER["HTTP_ORIGIN"])) {
    #  $response->header("Access-Control-Allow-Origin", "{$_SERVER["HTTP_ORIGIN"]}");
    #  $response->header("Access-Control-Allow-Credentials", "true");
    #}
    $response->body(json_encode([
      "response" => null,
      "error" => [
        "message" => $error->getMessage(),
        "code" => $error->getCode(),
        "file" => $error->getFile(),
        "line" => $error->getLine(),
        //"trace" => $error->getTrace(), #AsString(),
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