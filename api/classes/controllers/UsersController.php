<?php
/**
 * Users controller
 * 
 * @package UsersController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class UsersController {
  const USERNAME_MAXIMUM_LENGTH = 16;
  const PASSWORD_MINIMUM_LENGTH = 5;
  const MD5_SALT = "urYa8RnPs:4*K-Q";

  /**
   * Constructor
   */
  function __construct($router) {
    require_once "setup/users.php"; // users setup
    $this->router = $router;
    $this->db = $router->db;
  }

  public function register($username, $password) {
    if (strlen($username) > self::USERNAME_MAXIMUM_LENGTH) {
      return [ "message" => "Sorry, username maximum length is " . self::USERNAME_MAXIMUM_LENGTH . " bytes" ];
    }
    if (array_key_exists($username, $this->usersDefinitions["reservedUserNames"])) {
      return [ "message" => "Sorry, this username is reserved" ];
    }
    $user = $this->db->getByField("user", "username", $username);
    if (count($user) === 1) {
      return [ "message" => "Sorry, this username is already registered" ];
    }
    if (!$this->checkPasswordStrength($password)) {
      return [ "message" => "Password too weak, please choose a stronger one" ];
    }
    $user = [
      "username" => $username,
      "password" => $this->scramblePassword($password),
      "role" => "user",
    ];
    $this->db->add("user", $user);
    return [ "success" => true, "user" => $user ];
  }

  public function login($username, $password) {
    $user = $this->db->getByFields("user", [
      "username" => $username,
      "password" => $this->scramblePassword($password)
    ]);
    if (count($user) === 1) {
      return [ "success" => true, "user" => $user[0] ];
    } else {
      return [ "message" => "Wrong username/password, please try again" ];
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

  private function scramblePassword($password) {
    return md5(self::MD5_SALT . $password);
  }

  private function checkPasswordStrength($password) {
    if (strlen($password) < self::PASSWORD_MINIMUM_LENGTH) {
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