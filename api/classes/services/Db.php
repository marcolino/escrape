<?php

/*
$db = new DB();
$db->add("person", array("name" => "pippo", "key" => "abc", "age" => 27 ));
$p = $db->getAll("person");
print_r($p);
*/

class DB extends PDO {
  const DB_TYPE = "sqlite";
  const DB_PATH = "db/escrape.sqlite";
  private $db;

  public function __construct() {
    try {
      $new = !file_exists(self::DB_PATH);
      $this->db = new PDO(self::DB_TYPE . ":" . self::DB_PATH);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      chmod(self::DB_PATH, 0644); # TODO: debug only!
      if ($new) { // db doesn't exist, create tables...
        $this->createTables();
      }
    } catch (Exception $e) { /* caught by router */ }
  }

  public function createTables() {
    try {
      $this->db->exec(
        "create table if not exists user (
          id integer primary key autoincrement,
          username varchar(16),
          password varchar(32),
          email text,
          role text
         );
         create unique index if not exists username_idx on user (username);
        "
      );

      $this->db->exec(
        "create table if not exists person (
          id integer primary key autoincrement,
          key varchar(16),
          name varchar,
          site text,
          url text,
          timestamp integer,
          sex text,
          zone text,
          address text,
          description text,
          phone varchar(16),
          page_sum text,
          age text,
          vote integer
         );
         create unique index if not exists key_idx on person (key);
        "
      );

      $this->db->exec(
        "create table if not exists comment (
          id integer primary key autoincrement,
          id_person integer,
          key varchar(32),
          phone varchar(16),
          topic text,
          timestamp integer,
          author_nick text,
          author_karma varchar(16),
          author_posts integer,
          content text,
          content_valutation integer,
          url text
         );
         create unique index if not exists key_idx on comment (key);
         create unique index if not exists phone_idx on comment (phone);
         create index if not exists timestamp_idx on comment (timestamp);
         create index if not exists topic_idx on comment (topic);
        "
      );

      $this->db->exec(
        "create table if not exists photo (
          id integer primary key autoincrement,
          id_person integer,
          number integer,
          url text,
          timestamp_creation integer,
          sum varchar(32),
          signature varchar(256),
          showcase integer,
          thruthfulness integer
         );
        "
      );
 
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function getAll($table) {
    $sql = "select * from $table";
    return $this->getViaSql($sql);                
  }

  public function getViaSQL($sql) { # TODO: is this safe?
    try {
      $statement = $this->db->query($sql);
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function get($table, $id) {
    try {
      $sql = "select * from $table where id = :id limit 1";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id", $id, PDO::PARAM_INT);       
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function getByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "select * from $table where $fieldName = :$fieldName";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $results;
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function getByFields($table, $array) {
    try {
      $where = "";
      foreach ($array as $key => $value) {
        $where .= ($where ? " and " : "") . $key . " = " . ":" . $key;
      }
      $sql = "select * from $table where $where";
      $statement = $this->db->prepare($sql);
      foreach ($array as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $results;
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function getAverageFieldByPerson($table, $idPerson, $fieldName) {
    try {
      $sql = "select avg($fieldName) from $table where id_person = :id_person";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . "id_person", $idPerson); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function countByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "select * from $table where $fieldName = :$fieldName";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result ? count($result) : 0;
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function add($table, $array) {
    try {
      $fields = $values = "";
      foreach ($array as $key => $value) {
        $fields .= ($fields ? ", " : "") . $key;
        $values .= ($values ? ", " : "") . ":" . $key;
      }
      $sql = "insert into $table ($fields) values ($values)";
      $statement = $this->db->prepare($sql);
      foreach ($array as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      if ($statement->rowCount() != 1) {
        throw new Exception("insert into table $table did insert " . $statement->rowCount() . " records");
      }
      return $this->db->lastInsertId();
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function set($table, $id, $array) {
#print_r($table);
#print_r($id);
#print_r($array);
#exit;
    try {
      $set = "";
      foreach ($array as $key => $value) {
        $set .= ($set ? ", " : "") . $key . "=" . " " . ":" . $key;
      }
      $sql = "update $table set $set where id = :id";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);       
      foreach ($array as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($statement->rowCount() != 1) {
        throw new Exception("update into table $table fir id [$id] did update " . $statement->rowCount() . " records");
      }
      return $id;
    } catch (PDOException $e) { /* caught by router */ }
  }

  public function delete($table, $id) {
    try {
      $sql = "delete from $table where id = :id";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);       
      $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      $statement->execute();
      $count = $statement->rowCount();
      if ($statement->rowCount() != 1) {
        throw new Exception("delete from table $table did delete " . $statement->rowCount() . " records");
      }
      return true;
    } catch (PDOException $e) { /* caught by router */ }
  }

  private function profile($method) { # TODO: to be tested...
    $time_start = microtime(true);
    call($method);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    return $time;
  }

  function __destruct() {
    $this->db = null;
  }
}
?>