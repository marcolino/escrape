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
      if ($new) { // db doesn't exist, create TABLEs...
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
        "CREATE TABLE IF NOT EXISTS user (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          username VARCHAR(16),
          password VARCHAR(32),
          email TEXT,
          role TEXT
         );
         CREATE UNIQUE INDEX IF NOT EXISTS username_idx ON user (username);
         INSERT INTO user (username, password, email, role)
         SELECT 'marco', '10b82717350f8d5408080b4900b665e8', 'marcosolari@gmail.com', 'admin'
         WHERE NOT EXISTS(SELECT 1 FROM user WHERE id = 1)
        "
      );
      $this->db->exec(
        "CREATE TABLE if not exists person (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          key VARCHAR(16),
          key_site VARCHAR(16),
          name TEXT,
          url TEXT,
          timestamp INTEGER,
          status VARCHAR(8),
          sex TEXT,
          zone TEXT,
          address TEXT,
          description TEXT,
          phone VARCHAR(16),
          nationality VARCHAR(2),
          page_sum TEXT,
          age TEXT,
          vote INTEGER
         );
         CREATE UNIQUE INDEX IF NOT EXISTS key_idx ON person (key);
        "
      );
      $this->db->exec(
        "CREATE TABLE IF NOT EXISTS comment (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          id_person INTEGER,
          key VARCHAR(32),
          phone VARCHAR(16),
          topic TEXT,
          timestamp INTEGER,
          author_nick TEXT,
          author_karma VARCHAR(16),
          author_posts INTEGER,
          content TEXT,
          content_valutation INTEGER,
          url TEXT
         );
         CREATE UNIQUE INDEX IF NOT EXISTS key_idx ON comment (key);
         CREATE UNIQUE INDEX IF NOT EXISTS phone_idx ON comment (phone);
         CREATE INDEX IF NOT EXISTS timestamp_idx ON comment (timestamp);
         CREATE INDEX IF NOT EXISTS topic_idx ON comment (topic);
        "
      );
      $this->db->exec(
        "CREATE TABLE IF NOT EXISTS photo (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          id_person INTEGER,
          number INTEGER,
          url TEXT,
          path_full TEXT,
          path_small TEXT,
          sum VARCHAR(32),
          timestamp_creation INTEGER,
          signature VARCHAR(256),
          showcase INTEGER,
          thruthfulness INTEGER
         );
        "
      );
    } catch (PDOException $e) {
      throw new Exception("createTables() error:" . $e);
    }
  }
/*
  public function getAll($TABLE) {
    $sql = "select * from $TABLE";
    return $this->getViaSql($sql);
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
*/
  public function getAllSieved($table, $sieves) {
    try {
      $sql = "SELECT * FROM '$table' WHERE 1 = 1";
      $statement = null;
      if (
        $sieves &&
        $sieves["search"] &&
        $sieves["search"]["term"]
      ) {
        $searchTerm = $sieves["search"]["term"];
        $sql .= " AND ";
        $sql .= "name LIKE '%' || :searchTerm || '%'";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(":searchTerm", $searchTerm, PDO::PARAM_STR);
      }
      if (
        $sieves &&
        $sieves["filters"] &&
        $sieves["filters"]["nationality"] &&
        $sieves["filters"]["nationality"]["countryCode"]
      ) {
        $countryCode = $sieves["filters"]["nationality"]["countryCode"];
        $sql .= " AND ";
        $sql .= "nationality = :countryCode";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(":countryCode", $countryCode, PDO::PARAM_STR);
      }
      if (
        $sieves &&
        $sieves["filters"] &&
        $sieves["filters"]["voteMin"]
      ) {
        $voteMin = $sieves["filters"]["voteMin"];
        $sql .= " AND ";
        $sql .= "vote >= :voteMin";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(":voteMin", $voteMin, PDO::PARAM_INT);
      }
      if (
        $sieves &&
        $sieves["filters"] &&
        $sieves["filters"]["commentsCountMin"]
      ) {
        $commentsCountMin = $sieves["filters"]["commentsCountMin"];
        $sql .= " AND ";
        $sql .= "(SELECT COUNT(*) FROM comment WHERE id_person = person.id) >= :commentsCountMin";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(":commentsCountMin", $commentsCountMin, PDO::PARAM_INT);
      }
#throw new Exception("sql: [$sql] - commentsCountMin: $commentsCountMin");
      if (!$statement) {
        $statement = $this->db->prepare($sql);
      }
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
#throw new Exception("result: " . json_encode($result));
      return $result;
    } catch (PDOException $e) {
      throw new Exception("error getting persons with filters:" . $e);
    }
  }

  public function get($table, $id) {
    try {
      $sql = "SELECT * FROM $table WHERE id = :id LIMIT 1";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id", $id, PDO::PARAM_INT);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("error getting person:" . $e);
    }
  }
  public function getByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "SELECT * FROM $table WHERE $fieldName = :$fieldName";
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
        $where .= ($where ? " AND " : "") . $key . " = " . ":" . $key;
      }
      $sql = "SELECT * FROM $table WHERE $where";
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
      $sql = "SELECT COUNT($fieldName) AS count FROM $table WHERE $fieldName = :fieldValue";
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
      $sql = "INSERT INTO $table ($fields) VALUES ($values)";
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
      $sql = "UPDATE $table SET $set WHERE id = :id";
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
      $sql = "DELETE FROM $table WHERE id = :id";
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