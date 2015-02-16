<?php

require_once 'Slim/Slim.php';
require_once 'classes/controllers/AbstractController.php';
require_once 'classes/controllers/PersonsController.php';
require_once 'classes/controllers/CommentsController.php';
require_once 'classes/services/Db.php';
#require_once 'classes/services/CompareImages.php';

class Router {
  #private $app;
  #private $logs;

  public function __construct() {
    $timezone = "Europe/Rome";
    $this->logger = new Slim_Logger('./logs', 4);
    $this->app = new Slim([
      'log.enable' => true,
      'log.logger' => $this->logger,
      'debug' => true,
      'mode' => 'development',
    ]);
    $this->logs = [];
    date_default_timezone_set($timezone);
  }

  public function run() {
    $this->app->get('/persons/get', function() { $this->getPersons(); });
    $this->app->get('/persons/get/:id', function($id) { $this->getPerson($id); });
    $this->app->get('/persons/sync', function() { $this->syncPersons(); });
    $this->app->get('/persons/search/:query', function($query) { $this->searchPersonByName($query); });
    $this->app->put('/persons/setproperty/:id', function($id) { $this->setProperty($id); });
    $this->app->get('/persons/insert', function() { $this->insertPerson(); });
    $this->app->get('/persons/delete/:id', function($id) { $this->deletePerson($id); });
    #$this->app->post('/persons',  function() { $this->insertPerson(); });
    #$this->app->put('/persons/:id', function($id) { $this->updatePerson($id); });
    #$this->app->delete('/persons/:id', function($id) { $this->deletePerson($id); });

    $this->app->get('/users/register/', function() { $this->registerUser(); });
    $this->app->get('/users/login/:username/:password', function($username, $password) { $this->loginUser($username, $password); });
    $this->app->get('/users/delete/:id', function($id) { $this->deleteUser($id); });

    $this->app->get('/comments/get', function() { $this->getComments(); });
    $this->app->get('/comments/get/:id', function($id) { $this->getComment($id); });
    $this->app->get('/comments/getByPhone/:phone', function($phone) { $this->getCommentsByPhone($phone); });
    $this->app->get('/comments/sync', function() { $this->syncComments(); });

    $this->app->options('/.+', function() { $this->success(null); }); # TODO: only to allow CORS requests... (grunt)

    $this->app->error(function(Exception $e) { $this->error($e); }); # app->
    $this->app->notFound(function() { $this->unforeseen(); }); # app->

    $this->app->run();
  }

  private function getPersons() {
    try {
      $filters = $this->getFilters("range", ["age", "vote"], $this->app->request());
      $persons = new PersonsController($this);
      $this->success($persons->getList($filters));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function getPerson($id) {
    try {
      $persons = new PersonsController($this);
      $this->success($persons->get($id));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function syncPersons() {
    try {
      $persons = new PersonsController($this);
      $this->success($persons->sync());
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function searchPersonByName() {
    try {
      $persons = new PersonsController($this);
      $query = json_decode($this->app->request()->getBody());
      $this->success($persons->searchByName($query));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function setProperty($id) {
    #$data = json_decode($this->app->request()->getBody()); $this->success($data);
    try {
      $persons = new PersonsController($this);
      $data = json_decode($this->app->request()->getBody());
      #var_dump($data);
      $this->success($persons->setProperty($id, $data));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function insertPerson() {
    try {
      $persons = new PersonsController($this);
      $data = json_decode($this->app->request()->getBody());
      #var_dump($data);
      $this->success($persons->insert($data));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function updatePerson($id) {
    try {
      $persons = new PersonsController($this);
      $data = json_decode($this->app->request()->getBody());
      #var_dump($data);
      $this->success($persons->set($id, $data));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function getComments() {
    try {
      $comments = new CommentsController($this);
      $this->success($comments->getAll());
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function getComment($id) {
    try {
      $comments = new CommentsController($this);
      $this->success($comments->get($id));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function getCommentsByPhone($phone) {
    try {
      $comments = new CommentsController($this);
      $this->success($comments->getByPhone($phone));
    } catch (Exception $e) {
      $this->error($e);
    }
  }

  private function syncComments() {
    try {
      $comments = new CommentsController($this);
      $this->success($comments->sync());
    } catch (Exception $e) {
      $this->error($e);
    }
  }

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

  // protexted ??
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
        #'trace' => $e->getTrace(), #AsString(),
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