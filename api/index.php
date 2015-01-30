<?php

require 'Slim/Slim.php';
require 'classes/controllers/AbstractController.php';
require 'classes/controllers/PersonsController.php';
#require 'classes/controllers/CommentsController.php';
require 'classes/services/Db.php';
#require 'classes/services/CompareImages.php';

class Router {
    private $app;
    public $logs;

    public function __construct() {
        $this->app = new Slim();
        $this->logs = [];
    }

    public function run() {

        $this->app->get('/persons/get', function() { $this->getPersons(); });
        //$this->app->options('/persons/get', function() { $this->getPersons(); });
        $this->app->get('/persons/get/:id', function($id) { $this->getPerson($id); });
        $this->app->get('/persons/sync', function() { $this->syncPersons(); });
        $this->app->get('/persons/search/:query', function($query) { $this->searchPersonByName($query); });
        $this->app->get('/persons/update/:id', function($id) { $this->updatePerson($id); });
        $this->app->get('/persons/insert', function() { $this->insertPerson(); });
        $this->app->get('/persons/delete/:id', function($id) { $this->deletePerson($id); });
        #$this->app->post('/persons',  function() { $this->addPerson(); };
        #$this->app->put('/persons/:id', function($id) { $this->updatePerson($id); });
        #$this->app->delete('/persons/:id', function($id) { $this->deletePerson($id); });
        $this->app->get('/users/register/', function() { $this->registerUser(); });
        $this->app->get('/users/login/:username/:password', function($username, $password) { $this->loginUser($username, $password); });
        $this->app->get('/users/delete/:id', function($id) { $this->deleteUser($id); });

        $this->app->options('/.+', function() { $this->options(); });
        $this->app->run();
    }

    public function log($level, $value) {
        $this->app->logs[$level][] = $value;
        print "[$level]: " . $value . "<br>\n"; # TODO: remove this line...
    }

    private function syncPersons() {
        try {
            $persons = new PersonsController($this);
            $this->success($persons->sync());
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    private function getPersons() {
/*
        try {
            throw new Exception("NOOO!");
        } catch (Exception $e) {
            $this->error($e);
        }
*/
        $this->success(
            [
                'sgi-1' => [ 'name' => 'n1', 'id' => 'sgi-1', 'vote' => 5 ],
                'sgi-2' => [ 'name' => 'n2', 'id' => 'sgi-2', 'vote' => 6 ],
            ]
        );
/*
        try {
            $filters = $this->getFilters("range", ["age", "vote"], $this->app->request());
            $persons = new PersonsController($this->app);
            $this->success($persons->getAll($filters));
        } catch (Exception $e) {
            $this->error($e);
        }
*/
    }

    private function getPerson($id) {
        try {
            $persons = new PersonsController($this->app);
            $this->success($persons->get($id));
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    private function updatePerson($id) {
        try {
            $persons = new PersonsController($this->app);
            $data = json_decode($this->app->request()->getBody());
            #var_dump($data);
            $this->success($persons->update($id, $data));
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    private function insertPerson() {
        try {
            $persons = new PersonsController($this->app);
            $data = json_decode($this->app->request()->getBody());
            #var_dump($data);
            $this->success($persons->insert($data));
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    private function searchPersonsByName() {
        try {
            $persons = new PersonsController($this->app);
            $query = json_decode($this->app->request()->getBody());
            $this->success($persons->searchByName($query));
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

    private function options() {
        $response = $this->app->response();
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $response->header("Access-Control-Allow-Origin", "{$_SERVER['HTTP_ORIGIN']}");
            $response->header('Access-Control-Allow-Credentials", "true');
        }
    
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            $response->header("Access-Control-Allow-Methods", "GET, POST, OPTIONS");         
        }
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            $response->header("Access-Control-Allow-Headers", "{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
    }

    private function success($value) {
        $response = $this->app->response();
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $response->header("Access-Control-Allow-Origin", "{$_SERVER['HTTP_ORIGIN']}");
            $response->header("Access-Control-Allow-Credentials", "true");
        }
        $response->body(json_encode($value));
    }
    
    private function error($error) {
        $response = $this->app->response();
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $response->header("Access-Control-Allow-Origin", "{$_SERVER['HTTP_ORIGIN']}");
            $response->header("Access-Control-Allow-Credentials", "true");
        }
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
};

/*
function getPersons() {
$persons = [
    [
        "id" => 1,
        "name" => "Malvasia",
        "grapes" => "red",
        "country" => "Italy",
        "region" => "Sicily",
        "year" => 2013,
        "description" => "fermo",
    ],
    [
        "id" => 2,
        "name" => "Champagne",
        "grapes" => "white",
        "country" => "France",
        "region" => "Champagne",
        "year" => 2014,
        "description" => "frizzante",
    ],
];

    echo json_encode($persons);
}

function getPerson($id) {
$persons = [
    [
        "id" => 1,
        "name" => "Malvasia",
        "grapes" => "red",
        "country" => "Italy",
        "region" => "Sicily",
        "year" => 2013,
        "description" => "fermo",
    ],
    [
        "id" => 2,
        "name" => "Champagne",
        "grapes" => "white",
        "country" => "France",
        "region" => "Champagne",
        "year" => 2014,
        "description" => "frizzante",
    ],
];

    echo json_encode($persons[$id-1]);
}

function addPerson() {
}

function updatePerson($id) {
var_dump("updatePerson");
$persons = [
    [
        "id" => 1,
        "name" => "Malvasia",
        "grapes" => "red",
        "country" => "Italy",
        "region" => "Sicily",
        "year" => 2013,
        "description" => "fermo",
    ],
    [
        "id" => 2,
        "name" => "Champagne",
        "grapes" => "white",
        "country" => "France",
        "region" => "Champagne",
        "year" => 2014,
        "description" => "frizzante",
    ],
];

    #var_dump(json_encode($id));
    $request = Slim::getInstance()->request();
    $body = $request->getBody();
    $person = json_decode($body);
    $persons[$id-1] = $person;
    echo json_encode(true);
}

function updatePersonVote($id) {
var_dump("updatePersonVote");
$persons = [
    [
        "id" => 1,
        "name" => "Malvasia",
        "grapes" => "red",
        "country" => "Italy",
        "region" => "Sicily",
        "year" => 2013,
        "description" => "fermo",
    ],
    [
        "id" => 2,
        "name" => "Champagne",
        "grapes" => "white",
        "country" => "France",
        "region" => "Champagne",
        "year" => 2014,
        "description" => "frizzante",
    ],
];

var_dump(json_encode($id));
    $request = Slim::getInstance()->request();
    $body = $request->getBody();
    $vote = json_decode($body);
var_dump(json_encode($vote));
    $persons[$id-1]["vote"] = $vote;
    echo json_encode(true);
}
*/

/*
class APILogWriter {
    private $logs = [];

    public function write($message, $level = \Slim\Log::DEBUG) {
        $app->logs[$level][] = $message;

        switch ($level) {
            case \Slim\Log::DEBUG: $tag = "debug"; break;
            case \Slim\Log::INFO: $tag = "info"; break;
            case \Slim\Log::NOTICE: $tag = "notice"; break;
            case \Slim\Log::WARN: $tag = "warning"; break;
            case \Slim\Log::ERROR: $tag = "error"; break;
            case \Slim\Log::CRITICAL: $tag = "critical"; break;
            case \Slim\Log::ALERT: $tag = "alert"; break;
            case \Slim\Log::EMERGENCY: $tag = "emergency"; break;
        }
        print "[$tag]: " . $message . "\n"; # TODO: remove this line...
    }
}

# load required classes
require 'vendor/autoload.php';
require 'classes/controllers/AbstractController.php';
require 'classes/controllers/PersonsController.php';
require 'classes/controllers/CommentsController.php';
require 'classes/services/Db.php';
require 'classes/services/CompareImages.php';

# Fire up an app
$app = new \Slim\Slim(
    [
        'mode' => 'development',
        'log.enabled' => true,
        'log.level' => \Slim\Log::DEBUG,
        'log.writer' => new APILogWriter(),
    ]
);
$app->contentType('application/json');
$app->logs = [];


$app->get('/persons/sync', function() use ($app) {
    try {
        $persons = new PersonsController($app);
        success($app, $persons->sync());
    } catch(Exception $e) {
        error($app, $e);
    }
});

$app->get("/persons", function () use ($app) {
    #$persons = [ 'person 1' => 'title of person 1', 'person 2' => 'title of person 2', 'person 3' => 'title of person 3' ];
    #success($app, $persons);
    try {
        $filters = getFilters("range", ["age", "vote"], $app->request);
        $persons = new PersonsController($app);
        success($app, $persons->getList($filters));
    } catch (Exception $e) {
        error($app, $e);
    }
});
$app->get("/persons/:id", function ($id) use ($app) {
    try {
        $persons = new PersonsController($app);
        success($app, $persons->get($id));
    } catch (Exception $e) {
        error($app, $e);
    }
});
$app->get("/persons/update/:id", function ($id) use ($app) {
success($app, null);
    $params = $app->request()->params();
    #var_dump($params);
    try {
        $persons = new PersonsController($app);
        $persons->update($id, $params);
        success($app, $persons->store());
    } catch (Exception $e) {
        error($app, $e);
    }
#    $params = $app->request()->put();
#    if (in_array($id , array(1, 2, 3))) {
#        success($app, "person $id updated successfully");
#    } else {
#        echo "person $id does not exist";
#    }
});
$app->delete("/persons/:id", function ($id) use ($app) {
    if (in_array($id , array(1, 2, 3))) {
        success($app, "person $id deleted successfully");
    } else {
        echo "person $id does not exist";
    }
});


/*
$app->put('/persons', function() use ($app) {
    try {
        $action = $app->request->params('action');
        switch ($action) {
            case 'putVote':
                $persons = new PersonsController($app);
                success($app, $persons->putVote($app->request->params()));
                break;
            case '':
                throw new Exception("action can't be empty");
                break;
            default:
                throw new Exception("unforeseen action [$action]");
                break;
        }
    } catch (Exception $e) {
        error($app, $e);
    }
});

$app->get('/persons', function() use ($app) {
    try {
        $action = $app->request->params('action');
        switch ($action) {
            case 'sync':
                $persons = new PersonsController($app);
                success($app, $persons->sync());
                break;
            case 'getList':
                $filters = getFilters("range", ["age", "vote"], $app->request);
                $persons = new PersonsController($app);
                success($app, $persons->getList($filters));
                break;
            case 'setVote':
                $persons = new PersonsController($app);
                success($app, $persons->setVote($app->request->params()));
                break;
            case '':
                throw new Exception("action can't be empty");
                break;
            default:
                throw new Exception("unforeseen action [$action]");
                break;
        }
    } catch (Exception $e) {
        error($app, $e);
    }
});

$app->get('/persons/sync', function() use ($app) {
    try {
        $persons = new PersonsController($app);
        success($app, $persons->sync());
    } catch(Exception $e) {
        error($app, $e);
    }
});

$app->get('/persons/list', function() use ($app) {
    try {
        $filters = getFilters("range", ["age", "vote"], $app->request);
        $persons = new PersonsController($app);
        success($app, $persons->getList($filters));
    } catch (Exception $e) {
        error($app, $e);
    }
});

$app->get('/persons/:id', function($id) use ($app) {
    try {
        $persons = new PersonsController($app);
        success($app, $persons->get($id));
    } catch (Exception $e) {
        error($app, $e);
    }
});

$app->get('/comments/search/phone/:phone', function($phone) use ($app) {
    try {
        $comments = new CommentsController($app);
        success($app, $comments->get($phone));
    } catch (Exception $e) {
        error($app, $e);
    }
});
* /

$app->run();

/**
 * get filters from request
 *
 * @param  string $type     accepted filter types ("range", ... )
 * @param  array $params    accepted params names ("vote", "age", ...)
 * @param  object $request  current session request
 * @return array            
 * /
function getFilters($type, $params, $request) {
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

function success($app, $value) {
    $response = $app->response();
    $response->header("Access-Control-Allow-Origin", "*"); # TODO: restrict this pragma...
    $response->header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); # TODO: restrict this pragma...
    #$response['X-Powered-By'] = 'escrape/server';
    #$response->status(200);
    $response->body(json_encode($value));
}

function error($app, $error) {
    $response = $app->response();
    $response->header("Access-Control-Allow-Origin", "*"); # TODO: restrict this pragma...
    $response->header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); # TODO: restrict this pragma...
    $response->body(json_encode([
        'response' => null,
        'error' => [
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            #'trace' => $e->getTrace(), #AsString(),
        ],
        'log' => $app->logs,
    ]));
}
*/

$router = new Router();
$router->run();

?>