<?php
/**
 * Comments controller
 * 
 * @package escrape/server/api
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 * Call example for POST:
 *  $ curl -X POST -d 'phone=primo post' -d 'content=bella!' http://localhost/escrape/server/api/comments/
 */


include('../lib/simple_html_dom.php');

class CommentsController extends AbstractController {
    /**
     * Comments file
     *
     * @var variable type
     */
    protected $comments_file = './data/comments.json';
    protected $comments = array(
        "gnf" => array(
            "lc-time" => "it_IT.UTF-8",
            "timezone" => "Europe/Rome",
            "patterns" => array(
                "block" => "/<table\s+width=\"100%\"\s+cellpadding=\"5\"\s+cellspacing=\"0\"\s+border=\"1\"\s+border-color=\"#cccccc\"\s*>\s+(?:<tbody>)?<tr>(.*?)<\/tr>\s+(?:<\/tbody>)?<\/table>/s",
                "author" => "/<span class=\"smalltext\">\s*(.*?)<br.*?>/s",
                "date" => "/<span class=\"smalltext\">«\s*<b>.*on:<\/b>\s*(.*?)\s*»<\/span>/s",
                "content" => "/(<div class=\"post\">.*?\s*(?:<div class=\"post\">|$))/s",
                "quote-signature" => "/<div class=\"quoteheader\">.*<\/div>(.*)/s",
                "next-link" => "/<b>Pagine:<\/b>.*?\[<strong>\d+<\/strong>\] <a class=\"navPages\" href=\"(.*?)\">\d+<\/a>/s",
            ),
        ),
    );
    
    /**
     * GET method
     * 
     * @param  Request $request
     * @return string
     */
    public function get($request) {                     # TODO
/*
        $comments = $this->readComments();
        switch (count($request->url_elements)) {
            case 1:
                return $comments;
            break;
            case 2:
                $article_id = $request->url_elements[1];
                return $comments[$article_id];
            break;
        }
*/
        $controller = $request->url_elements[0]; # this controller name
        $action = $request->url_elements[1]; # requested action
        if (method_exists($this, $action)) { 
            $response_str = call_user_func_array(array($this, $action), array($request->parameters));
        }
        return $response_str;
    }
    
    /**
     * POST action
     *
     * @param  $request
     * @return null
     */
    public function post($request) {
        $controller = $request->url_elements[0]; # this controller name
        $action = $request->url_elements[1]; # requested action
        if (method_exists($this, $action)) { 
            $response_str = call_user_func_array(array($this, $action), array($request->parameters));
        }
#        header('HTTP/1.1 201 Created');
#        header('Location: /comments/' . $id);
        print $response_str;
        return null;
    }
/*
                // validation should go here
                $id = (count($comments) + 1);
                $comments = $this->readComments();
                $article = array(
                    'id' => $id,
                    'title' => $request->parameters['title'],
                    'content' => $request->parameters['content'],
                    'published' => date('c')
                );
                $comments[] = $article;
                $this->writeComments($comments);
                header('HTTP/1.1 201 Created');
                header('Location: /comments/' . $id);
                return null;
*/

    /**
     * Sync comments
     *
     * @param  array $parameters
     * @return array
     */
    protected function sync($parameters) {
        print "sync'ing...\n";
        var_dump($parameters);
        return array('a');
    }

    /**
     * Read comments
     *
     * @return array
     */
    protected function readComments() {
        $comments = unserialize(file_get_contents($this->comments_file));
        if (empty($comments)) {
            $comments = array(
                1 => array(
                    'id' => 1,
                    'title' => 'Test Article',
                    'content' => 'Welcome to your new API framework!',
                    'published' => date('c', mktime(18, 35, 48, 1, 13, 2012))
                )
            );
            $this->writeComments($comments);
        }
        return $comments;
    }
    
    /**
     * Write comments
     *
     * @param  string $comments
     * @return boolean
     */
    protected function writeComments($comments) {
        file_put_contents($this->comments_file, serialize($comments));
        return true;
    }

    /** 
     * Call a method dynamically 
     * 
     * @param string $method 
     * @param array $args 
     * @return mixed 
     */ 
    protected function call($method, $args) { 
        if (method_exists($this, $method)) { 
            return call_user_func_array(array($this, $method), $args); 
        } else { 
            throw new Exception(sprintf('The required method "%s" does not exist for %s', $method, get_class($this))); 
        } 
    } 


}