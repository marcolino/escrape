<?php
/**
 * Persons controller
 * 
 * @package escrape/server/api
 * @author  Marco Solari <marcosolari@gmail.com>
 */
class PersonsController extends AbstractController {
    /**
     * Persons file
     *
     * @var variable type
     */
    protected $persons_file = './data/persons.json';
    
    /**
     * GET method
     * 
     * @param  Request $request
     * @return string
     */
    public function get($request) {
        $persons = $this->readPersons();
        switch (count($request->url_elements)) {
            case 1:
                return $persons;
            break;
            case 2:
                $person_id = $request->url_elements[1];
                return $persons[$person_id];
            break;
        }
    }
    
    /**
     * POST action
     *
     * @param  $request
     * @return null
     */
    public function post($request) {
        switch (count($request->url_elements)) {
            case 1:
                // validation should go here
                $id = (count($persons) + 1);
                $persons = $this->readPersons();
                $person = array(
                    'id' => $id,
                    'title' => $request->parameters['title'],
                    'content' => $request->parameters['content'],
                    'published' => date('c')
                );
                $persons[] = $person;
                $this->writePersons($persons);
                header('HTTP/1.1 201 Created');
                header('Location: /persons/' . $id);
                return null;
            break;
        }
    }

    /**
     * Read persons
     *
     * @return array
     */
    protected function readPersons() {
        $persons = unserialize(file_get_contents($this->persons_file));
        if (empty($persons)) {
            $persons = array(
                1 => array(
                    'id' => 1,
                    'title' => 'Test Person',
                    'content' => 'Welcome to your new API framework!',
                    'published' => date('c', mktime(18, 35, 48, 1, 13, 2012))
                )
            );
            $this->writePersons($persons);
        }
        return $persons;
    }
    
    /**
     * Write persons
     *
     * @param  string $persons
     * @return boolean
     */
    protected function writePersons($persons) {
        file_put_contents($this->persons_file, serialize($persons));
        return true;
    }
}