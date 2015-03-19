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
  const DB_SYSTEM_USER_ID = "1"; # TODO: can we use a constant here?
  private $db;

  public function __construct($router) {
    $this->router = $router;
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
        #, [ PDO::ATTR_PERSISTENT => TRUE ] # TODO: on update this causes a "General error"...
      );
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if ($new) { // db doesn't exist, create TABLEs...
        $this->db->query("PRAGMA encoding='" . self::DB_CHARSET . "'"); // enforce charset
        $this->createTables();
      }
      $this->userIdSystem = self::DB_SYSTEM_USER_ID;
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
         -- populate with default system user --
         INSERT INTO user (username, password, email, role)
         SELECT '*', '-', '', 'system'
         WHERE NOT EXISTS(SELECT 1 FROM user WHERE username = '*');
         -- populate with default admin user --
         INSERT INTO user (username, password, email, role)
         SELECT 'marco', '10b82717350f8d5408080b4900b665e8', 'marcosolari@gmail.com', 'admin'
         WHERE NOT EXISTS(SELECT 1 FROM user WHERE username = 'marco');
        "
      );
      $this->db->exec(
        "CREATE TABLE if not exists person (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          key VARCHAR(32),
          site_key VARCHAR(16),
          url TEXT,
          timestamp_creation INTEGER,
          timestamp_last_sync INTEGER,
          page_sum TEXT
         );
         CREATE UNIQUE INDEX IF NOT EXISTS key_idx ON person (key);
        "
      );
      $this->db->exec(
        "CREATE TABLE if not exists person_detail (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          id_person INTEGER,
          id_user INTEGER,
          id_uniqcode INTEGER,
          active VARCHAR(8),
          name TEXT,
          sex TEXT,
          zone TEXT,
          address TEXT,
          description TEXT,
          notes TEXT,
          phone VARCHAR(16),
          nationality VARCHAR(2),
          age TEXT,
          vote INTEGER
         );
         CREATE UNIQUE INDEX IF NOT EXISTS phone_idx ON person_detail (phone);
        "
      );
      $this->db->exec(
        "CREATE TABLE if not exists person_uniqcode (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          id_user INTEGER,
          id_person_1 INTEGER,
          id_person_2 INTEGER,
          same INTEGER
         );
         CREATE INDEX IF NOT EXISTS id_person_1_idx ON person_uniqcode (id_person_1);
         CREATE INDEX IF NOT EXISTS id_person_2_idx ON person_uniqcode (id_person_2);
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
      throw new Exception("can't create tables: " . $e->getMessage());
    }
  }

  public function getPersonListSieved($sieves, $userId = self::DB_SYSTEM_USER_ID) { # TODO: select SYSTEM and USER fields...???!!!
    $tableMaster = "person";
    $tableDetail = "person" . "_" . "detail";
    $params = [];

    try {
      list($sql, $params) = $this->sieves2Sql($sieves);

      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.*
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.id_person
        WHERE (id_user = {$this->userIdSystem} OR id_user = {$userId})
      " . $sql;

      // order to get lower user id first (system is the lowest: 1)
      $sql .= " ORDER BY " .
        "{$tableDetail}.id_user ASC," .
        "{$tableDetail}.id_person ASC" # TODO: do we need this ordering?
      ;

      $statement = $this->db->prepare($sql);
      foreach ($params as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
#throw new Exception("sql: [$sql]");
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
#throw new Exception("sql: [$sql] => " . var_export($result, true));
      return $result;
    } catch (PDOException $e) {
      #throw new Exception("error getting persons with filters", 0, $e); # TODO: SHOULD USE THIS??? 
      throw new Exception("can't get $table with filters: " . $e->getMessage()); # TODO: USE THIS METHOD!!!
    }
  }

  public function getPerson($id, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "person";
    $tableDetail = "person" . "_" . "detail";
    try {
      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.*
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.id_person
        WHERE 1 = 1
      ";
      $sql .= " AND {$tableMaster}.id = :id";
      $sql .= " AND {$tableDetail}.id_user = :id_user";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id", $id, PDO::PARAM_INT);
      $statement->bindParam(":id_user", $userId, PDO::PARAM_INT);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get table $table: " . $e->getMessage());
    }
  }

  public function getPersonsUniqcode($id_person_1, $id_person_2, $userId = self::DB_SYSTEM_USER_ID) {
    $table = "person_uniqcode";
    try {
      $sql = "
        SELECT id, same
        FROM {$table}
        WHERE (id_user = {$this->userIdSystem} OR id_user = {$userId})
        AND (
          id_person_1 = :id_person_1 AND
          id_person_2 = :id_person_2
        ) OR (
          id_person_1 = :id_person_2 AND
          id_person_2 = :id_person_1
        )
        ORDER BY id_user DESC -- lower id_user (systems's) last
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id_person_1", $id_person_1, PDO::PARAM_INT);
      $statement->bindParam(":id_person_2", $id_person_2, PDO::PARAM_INT);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      if ($result) { // at least one record present
        return $result; // return only first (user's, if present) record
      } else { // no records present
        return null; // no uniqcode set for these persons
      }
    } catch (PDOException $e) {
      throw new Exception("can't get persons uniq code in table $table: " . $e->getMessage());
    }
  }

  public function setPersonsUniqcode($id_person_1, $id_person_2, $same, $userId = self::DB_SYSTEM_USER_ID) {
    $table = "person_uniqcode";
    try {
      $result = $this->getPersonsUniqcode($id_person_1, $id_person_2, $userId);
$this->router->log("debug", " setPersonsUniqCode - result:" . var_export($result, true));
      $count = count($result);
      if ($count > 0) { // a uniqcode already present for these persons, update it
        if ($result["same"] === $same) { // current value is equal to the value to be set
          ; // do nothing
        } else { // current value is different from the value to be set
          $id = $result["id"];
          $sql = "
            UPDATE {$table}
            SET same = :same
            WHERE (id = {$id})
          ";
          $statement = $this->db->prepare($sql);
          $statement->bindParam(":same", $same, PDO::PARAM_INT);
          $statement->execute();
          $count = $statement->rowCount();
          if ($count != 1) {
           throw new Exception("can't update persons [$id_person_1] and [$id_person_2] uniq code in table $table: " . $e->getMessage());
          }
        }
      } else { // a uniqcode not yet present for these persons, insert it
        $sql = "
          INSERT INTO {$table}
          (id_user, id_person_1, id_person_2, same)
          VALUES
          (:id_user, :id_person_1, :id_person_2, :same)
        ";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(":id_user", $userId, PDO::PARAM_INT);
        $statement->bindParam(":id_person_1", $id_person_1, PDO::PARAM_INT);
        $statement->bindParam(":id_person_2", $id_person_2, PDO::PARAM_INT);
        $statement->bindParam(":same", $same, PDO::PARAM_INT);
        $statement->execute();
        $count = $statement->rowCount();
        if ($count != 1) {
         throw new Exception("can't insert persons [$id_person_1] and [$id_person_2] uniq code in table $table: " . $e->getMessage());
        }
      }
      return $same;
    } catch (PDOException $e) {
      throw new Exception("can't set persons uniq code in table $table: " . $e->getMessage());
    }
  }



  /*
   * hereafter generic table functions
   */

  public function get($table, $id) {
    try {
      $sql = "
        SELECT *
        FROM {$table}
        WHERE id = :id
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id", $id, PDO::PARAM_INT);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get record from table $table: " . $e->getMessage());
    }
  }

  public function getByField($table, $fieldName, $fieldValue, $userId = self::DB_SYSTEM_USER_ID) {
    try {
      $sql = "
        SELECT *
        FROM {$table}
        WHERE $fieldName = :$fieldName
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $results;
    } catch (PDOException $e) {
      throw new Exception("can't get $table by field: " . $e->getMessage());
    }
  }

  public function getByFields($table, $array) {
    try {
      $where = "";
      foreach ($array as $key => $value) {
        $where .= ($where ? " AND " : "") . $key . " = " . ":" . $key;
      }
      $sql = "
        SELECT *
        FROM {$table}
        WHERE $where
      ";
      $statement = $this->db->prepare($sql);
      foreach ($array as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $results;
    } catch (PDOException $e) {
      throw new Exception("can't get $table by fields: " . $e->getMessage());
    }
  }

  public function getAverageFieldByPerson($table, $idPerson, $fieldName) {
    try {
      $sql = "
        SELECT AVG($fieldName) AS avg
        FROM {$table}
        WHERE id_person = :id_person
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . "id_person", $idPerson); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get average field by person from $table: " . $e->getMessage());
    }
  }

  public function countByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "
        SELECT COUNT($fieldName) AS count
        FROM {$table}
        WHERE $fieldName = :fieldValue
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":fieldValue", $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result["count"];
    } catch (PDOException $e) {
      throw new Exception("can't count $table by field: " . $e->getMessage());
    }
  }

  public function add($table, $arrayMaster, $arrayDetail = null, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = $table;

    try { // add master data
      $fields = $values = "";
      foreach ($arrayMaster as $key => $value) {
        $fields .= ($fields ? ", " : "") . $key;
        $values .= ($values ? ", " : "") . ":" . $key;
      }
      $sql = "
        INSERT INTO {$tableMaster}
        ($fields)
        VALUES
        ($values)
      ";
      $statement = $this->db->prepare($sql);
      foreach ($arrayMaster as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($count != 1) {
        throw new Exception("can't add record to table $tableMaster (added $count records)");
      }
      $lastInsertId = $this->db->lastInsertId();
    } catch (PDOException $e) {
      throw new Exception("can't add record to table $tableMaster: " . $e->getMessage());
    }

    if (!empty($arrayDetail)) {
      $tableDetail = $table . "_" . "detail";

      $arrayDetail["id_person"] = $lastInsertId; // add master person id to this detail record
      $arrayDetail["id_user"] = $userId; // add user id to this detail record
  
      try { // add details data
        $fields = $values = "";
        foreach ($arrayDetail as $key => $value) {
          $fields .= ($fields ? ", " : "") . $key;
          $values .= ($values ? ", " : "") . ":" . $key;
        }
  
        $sql = "
          INSERT INTO {$tableDetail}
          ($fields)
          VALUES
          ($values)
        ";
#throw new Exception("detail sql: [$sql]");
        $statement = $this->db->prepare($sql);
        foreach ($arrayDetail as $key => &$value) {
          $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
        }
        $statement->execute();
        $count = $statement->rowCount();
        if ($count != 1) {
          throw new Exception("can't add record to table $tableDetail (added $count records)");
        }
        $lastInsertId = $this->db->lastInsertId();        
        return $lastInsertId;
      } catch (PDOException $e) {
        throw new Exception("can't add record to table $tableDetail: " . $e->getMessage());
      }
    }
  }

  public function set($table, $id, $array) {
    try {
      $set = "";
      foreach ($array as $key => $value) {
        $set .= ($set ? ", " : "") . $key . "=" . ":" . $key;
      }
      $sql = "
        UPDATE
        {$table}
        SET
        $set
        WHERE id = :id
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);
      foreach ($array as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($statement->rowCount() != 1) {
        throw new Exception("update into table $table for id [$id] did update " . $statement->rowCount() . " records");
      }
      return $id;
    } catch (PDOException $e) {
      throw new Exception("can't set record to $table: " . $e->getMessage());
    }
  }

  public function delete($table, $id) {
    try {
      $sql = "
        DELETE
        FROM {$table}
        WHERE id = :id
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);
      $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      $statement->execute();
      $count = $statement->rowCount();
      if ($count != 1) {
        throw new Exception("delete from $table did not delete one record, but $count: " . $e->getMessage());
      }
      return true;
    } catch (PDOException $e) {
      throw new Exception("can't delete from $table: " . $e->getMessage());
    }
  }

  private function sieves2Sql($sieves = null) {
    $sql = $params = "";

    if (
      $sieves &&
      $sieves["search"] &&
      $sieves["search"]["term"]
    ) {
      $params["searchTerm"] = $sieves["search"]["term"];
      $sql .= " AND ";
      $sql .= "(
        name LIKE '%' || :searchTerm || '%' OR
        description LIKE '%' || :searchTerm || '%' OR
        phone LIKE '%' || :searchTerm || '%' OR
        zone LIKE '%' || :searchTerm || '%' OR
        address LIKE '%' || :searchTerm || '%'
      )";
    }
    if (
      $sieves &&
      $sieves["filters"] &&
      $sieves["filters"]["active"]
    ) {
      if ($sieves["filters"]["active"] !== "any") {
        $params["active"] = $sieves["filters"]["active"];
        $sql .= " AND ";
        $sql .= "active = :active";
      }
    }
    if (
      $sieves &&
      $sieves["filters"] &&
      $sieves["filters"]["nationality"]
    ) {
      $params["nationality"] = $sieves["filters"]["nationality"];
      $sql .= " AND ";
      $sql .= "nationality = :nationality";
    }
    if (
      $sieves &&
      $sieves["filters"] &&
      $sieves["filters"]["voteMin"]
    ) {
      $params["voteMin"] = $sieves["filters"]["voteMin"];
      $sql .= " AND ";
      $sql .= "vote >= :voteMin";
    }
    if (
      $sieves &&
      $sieves["filters"] &&
      $sieves["filters"]["commentsCountMin"]
    ) {
      $params["commentsCountMin"] = $sieves["filters"]["commentsCountMin"];
      $sql .= " AND ";
      $sql .= "(SELECT COUNT(*) FROM comment WHERE id_person = person.id) >= :commentsCountMin";
    }
    if (
      $sieves &&
      $sieves["filters"] &&
      $sieves["filters"]["age"] &&
      $sieves["filters"]["age"]["min"] &&
      $sieves["filters"]["age"]["max"]
    ) {
      $params["ageMin"] = $sieves["filters"]["age"]["min"];
      $params["ageMax"] = $sieves["filters"]["age"]["max"];
      $sql .= " AND ";
      $sql .= "((age IS NULL) OR (age >= :ageMin AND age <= :ageMax))";
    }
    return [ $sql, $params ];
  }

  private function profileForSpeed($method) { # TODO: to be tested...
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