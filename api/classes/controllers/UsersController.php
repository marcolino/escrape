<?php
/**
 * Users controller
 * 
 * @package UsersController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class UsersController extends AbstractController {
  const PASSWORD_MINIMUM_LENGTH = 4;

  /**
   * Constructor
   */
  function __construct($router) {
    $this->router = $router;
    $this->db = $router->db;
  }

  public function register($username, $password) {
    $user = $this->db->getByField("user", "username", $username);
    if (count($user) === 1) {
      return [ "message" => "Sorry, this user name is already registered" ];
    }
    if (!$this->checkPasswordStrength($password)) {
      return [ "message" => "Password too weak, please choose a stronger one" ];
    }
    $this->db->add("user", [ "username" => $username, "password" => md5($password) ]);
    return [ "success" => true ];
  }

  public function login($username, $password) {
    $user = $this->db->getByField("user", "username", $username);
    if (count($user) === 1) {
      return [ "success" => true ];
    } else {
      return [ "message" => "Password too weak... Please choose a stronger one" ];
    }
  }

  public function remove($username) {
    $user = $this->db->getByField("user", "username", $username);
    if (!$user) {
      return [ "message" => "Sorry, this user is not registered" ];
    }
    $this->db->deletebyField("user", "username", $username);
    return [ "success" => true ];
  }

  private function checkPasswordStrength($password) {
    if (strlen($password) <= self::PASSWORD_MINIMUM_LENGTH) {
      return false;
    }
    # TODO: check more properties?
    return true;
  }

  /**
   * Destructor
   */
  function __destruct() {
  }

}