<?php
/**
 * DB class
 * 
 * @package DB
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class DB extends PDO {
  const DB_TYPE = "sqlite";
  const DB_NAME = "db/escrape.sqlite";
  const DB_USER = null;
  const DB_PASS = null;
  const DB_CHARSET = "utf8";
  private $db;

  public function __construct() {
    try {
      $dbPath = dirname(self::DB_NAME);
      if ($dbPath) {
        if (!file_exists($dbPath)) {
          if (!mkdir($dbPath, 0777, true)) { # TODO: let everybody (developer) to write dir: DEBUG ONLY!
            throw new Exception("can't create folder $dirname");
          }
        }
      }
      $new = !file_exists(self::DB_NAME);
      $this->db = new PDO(
        self::DB_TYPE . ":" . self::DB_NAME,
        self::DB_USER,
        self::DB_PASS
        #, [ PDO::ATTR_PERSISTENT => TRUE ] # TODO: on update this causes: "General error: 1 no such column: key_site" ... ?
      );
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($new) { // db doesn't exist, create tables...
        $this->db->query("PRAGMA encoding='" . self::DB_CHARSET . "'"); // enforce charset
        $this->createTables();
      }
    } catch (Exception $e) {
      throw new Exception("__construct() error:" . $e);
    }
    if ($new) { # TODO: let everybody (developer) to write dir: DEBUG ONLY!
      chmod(self::DB_NAME, 0666); # TODO: let everybody (developer) to write db: DEBUG ONLY!
    }
  }

  public function createTables() {
    # TODO: always use text or varchar ... ?
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
         insert into user (username, password, email, role)
         select 'marco', '10b82717350f8d5408080b4900b665e8', 'marcosolari@gmail.com', 'admin' 
         where not exists(select 1 from user where id = 1)
        "
      );

      $this->db->exec(
        "create table if not exists person (
          id integer primary key autoincrement,
          key varchar(16),
          key_site varchar(16),
          name text,
          url text,
          timestamp integer,
          sex text,
          zone text,
          address text,
          description text,
          phone varchar(16),
          nationality varchar(2),
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
          path_full text,
          path_small text,
          sum varchar(32),
          timestamp_creation integer,
          signature varchar(256),
          showcase integer,
          thruthfulness integer
         );
        "
      );
 
    } catch (PDOException $e) {
      throw new Exception("createTables() error:" . $e);
    }
  }

  public function getAll($table) {
    $sql = "select * from $table";
    return $this->getViaSql($sql);                
  }

  public function getAllFiltered($table, $filters) {
throw new Exception("filters:" . var_export($filters, 0));
/*
    if ($filters && $filters["search"] && $filters["search"]["term"]) {
      $searchTerm = $filters["search"]["term"];
      $sql = "select * from $table where name like '%$searchTerm%'"; # TODO: prepare/bind, if possible...
    } else {
      $sql = "select * from $table";
    }
    return $this->getViaSql($sql);                
*/
  }

  public function getViaSQL($sql) { # TODO: is this safe?
    try {
      $statement = $this->db->query($sql);
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("getViaSQL() error:" . $e);
    }
  }

  public function get($table, $id) {
    try {
      $sql = "select * from $table where id = :id limit 1";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id", $id, PDO::PARAM_INT);       
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("get() error:" . $e);
    }
  }

  public function getByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "select * from $table where $fieldName = :$fieldName";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $results;
    } catch (PDOException $e) {
      throw new Exception("getByField() error:" . $e);
    }
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
    } catch (PDOException $e) {
      throw new Exception("getByFields() error:" . $e);
    }
  }

  public function getAverageFieldByPerson($table, $idPerson, $fieldName) {
    try {
      $sql = "select avg($fieldName) as avg from $table where id_person = :id_person";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . "id_person", $idPerson); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("getAverageFieldByPerson() error:" . $e);
    }
  }

  public function countByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "select count($fieldName) as count from $table where $fieldName = :fieldValue";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":fieldValue", $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result["count"];
    } catch (PDOException $e) {
      throw new Exception("countByField() error:" . $e);
    }
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
    } catch (PDOException $e) {
      throw new Exception("add() error:" . $e);
    }
  }

  public function set($table, $id, $array) {
    try {
      $set = "";
      foreach ($array as $key => $value) {
        $set .= ($set ? ", " : "") . $key . "=" . ":" . $key;
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
    } catch (PDOException $e) {
      throw new Exception("set() error:" . $e);
    }
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
    } catch (PDOException $e) {
      throw new Exception("delete() error:" . $e);
    }
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