<?php

/** 
 * APILogWriter: Custom log writer for our application
 *
 * We must implement write(mixed $message, int $level)
*/

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
?>