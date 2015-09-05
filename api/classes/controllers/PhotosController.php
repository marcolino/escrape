<?php
/**
 * Photos controller
 * 
 * @package PhotosController
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 */

require_once(__DIR__ . "/../../classes/services/Utilities.php");

class PhotosController {

  /**
   * Constructor
   */
  function __construct($router) {
    $this->router = $router;
    $this->network = new Network();
    $this->db = $this->router->db;
  }

  public function set($id, $photoMaster = null, $photoDetail = null, $userId = null) {
    #$this->router->log("debug", "PhotosController::set: " . any2string($id, $photoMaster, $photoDetail, $userId));
    return $this->db->setPhoto($id, $photoMaster, $photoDetail, $userId);
  }

  /**
   * Destructor
   */
  function __destruct() {
  }

}