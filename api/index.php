<?php

require_once 'Slim/Slim.php';
require_once 'classes/controllers/AbstractController.php';
require_once 'classes/controllers/PersonsController.php';
require_once 'classes/controllers/PhotosController.php';
require_once 'classes/controllers/CommentsController.php';
require_once 'classes/services/Db.php';
require_once 'classes/services/ImagesTools.php';

class Router {

  public function __construct() {
    $timezone = "Europe/Rome";
    $this->logger = new Slim_Logger('./logs', 4);
    $this->app = new Slim([
      'log.enable' => true,
      'log.logger' => $this->logger,
      'debug' => true,
      'mode' => 'development',
    ]);
    $this->db = new DB();
    $this->logs = [];
    date_default_timezone_set($timezone);
  }

  public function run() {
    # ======================
    # test
    # ======================
    $this->app->get('/persons/test', function() {
      try {
        $persons = new PersonsController($this);
        $this->success($persons->test());
      } catch (Exception $e) {
        $this->error($e);
      }
    });

    # ======================
    # persons
    # ======================
    $this->app->get('/persons/get', function() {
      try {
        $persons = new PersonsController($this);
        $this->success($persons->getList());
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->get('/persons/get/:id', function($id) {
      try {
        $persons = new PersonsController($this);
        $this->success($persons->get($id));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->get('/persons/sync', function() {
      try {
        $persons = new PersonsController($this);
        $this->success($persons->sync());
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->get('/persons/search/:query', function($query) {
      try {
        $persons = new PersonsController($this);
        #$query = json_decode($this->app->request()->getBody());
        $this->success($persons->searchByName($query));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->put('/persons/set/:id', function($id) {
      try {
        $persons = new PersonsController($this);
        $data = json_decode($this->app->request()->getBody());
        $this->success($persons->set($id, $data));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->post('/persons/insert', function() {
      try {
        $persons = new PersonsController($this);
        $data = json_decode($this->app->request()->getBody());
        $this->success($persons->insert($data));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->delete('/persons/delete/:id', function($id) {
      try {
        $persons = new PersonsController($this);
        $data = json_decode($this->app->request()->getBody());
        $this->success($persons->delete($id));
      } catch (Exception $e) {
        $this->error($e);
      }
    });

    # ======================
    # users
    # ======================
    $this->app->post('/users/register/', function() {
      try {
        $users = new UsersController($this);
        $data = json_decode($this->app->request()->getBody());
        $this->success($users->register($data));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->get('/users/login/:username/:password', function($username, $password) {
      try {
        $users = new UsersController($this);
        $this->success($users->login($username, $password));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->delete('/users/delete/:id', function($id) {
      try {
        $users = new UsersController($this);
        $this->success($users->delete($id));
      } catch (Exception $e) {
        $this->error($e);
      }      
    });

    # ======================
    # comments
    # ======================
    $this->app->get('/comments/get', function() {
      try {
        $comments = new CommentsController($this);
        $this->success($comments->getAll());
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->get('/comments/get/:id', function($id) {
      try {
        $comments = new CommentsController($this);
        $this->success($comments->get($id));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->get('/comments/getByPhone/:phone', function($phone) {
      try {
        $comments = new CommentsController($this);
        $this->success($comments->getByPhone($phone));
      } catch (Exception $e) {
        $this->error($e);
      }
    });
    # ======================
    $this->app->post('/comments/sync', function() {
      try {
        $comments = new CommentsController($this);
        $this->success($comments->sync());
      } catch (Exception $e) {
        $this->error($e);
      }
    });

    # DEBUG: only to allow *all* CORS requests... (grunt / apache)
    $this->app->options('/.+', function() { $this->success(null); });

    $this->app->error(function(Exception $e) { $this->error($e); }); # app->
    $this->app->notFound(function() { $this->unforeseen(); }); # app->

    $this->app->run();
  }

  /*
  private function getFilters($type, $params, $request) {
    $filters = [];   
    $paramslist = [];
    foreach ($params as $name) {
      $value = $request->params($name);
      if ($type == "range") {
        $rangesep = "-";
        if (strstr($value, $rangesep)) {
          list($filters[$type][$name]["min"], $filters[$type][$name]["max"]) = explode($rangesep, $value);
        }
      }
    }
    return $filters;
  }
  */

  public function log($level, $value) {
    switch ($level) {
      default:
      case "fatal": // FATAL
        $this->app->getLog()->fatal($value);
        break;
      case "error": // ERROR
        $this->app->getLog()->error($value);
        break;
      case "warn": // WARN
      case "warning": // WARN
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
    #print "LOG [$level]: " . $value . "\n"; # TODO: remove this line...
  }

  private function unforeseen() {
    $response = $this->app->response();
    $this->access_control_allow($response);
    $response->body(json_encode([
      'response' => null,
      'error' => [
        'message' => "Unforeseen route [" . $this->app->router()->getMatchedRoutes() . "]",
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
    #if (isset($_SERVER['HTTP_ORIGIN'])) {
    #  $response->header("Access-Control-Allow-Origin", "{$_SERVER['HTTP_ORIGIN']}");
    #  $response->header("Access-Control-Allow-Credentials", "true");
    #}
    $response->body(json_encode([
      'response' => null,
      'error' => [
        'message' => $error->getMessage(),
        'code' => $error->getCode(),
        'file' => $error->getFile(),
        'line' => $error->getLine(),
        'trace' => $error->getTrace(), #AsString(),
      ],
      'log' => $this->logs,
    ]));
  }

  private function access_control_allow($response) {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
      $response->header("Access-Control-Allow-Origin", "{$_SERVER['HTTP_ORIGIN']}");
      $response->header("Access-Control-Allow-Credentials", "true");
    }
  
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        $response->header("Access-Control-Allow-Methods", "GET, PUT, POST, OPTIONS");     
      }
      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        $response->header("Access-Control-Allow-Headers", "{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
      }
    }
  }

};

$router = new Router();
$router->run();

?>