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
  const DB_SYSTEM_USER_ID = "1";
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
      ###$new = true; # FORCE CREATION OF TABLES EVEN IF DB EXISTS (TO BE USED ONCE, IF NEEDED!)
      if ($new) { // db doesn't exist, create TABLEs...
        $this->db->query("PRAGMA encoding='" . self::DB_CHARSET . "'"); // enforce charset
        $this->createTables();
      }
      $this->userIdSystem = intval(self::DB_SYSTEM_USER_ID); # TODO: use this variable... (?)
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
        "CREATE TABLE IF NOT EXISTS global (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          field_name TEXT,
          field_value TEXT
         );
         CREATE UNIQUE INDEX IF NOT EXISTS field_name_idx ON global (field_name);
         -- populate with default values --
         INSERT INTO global (field_name, field_value)
         VALUES ('last_sync_full', '');
         --INSERT INTO global (field_name, field_value)
         --VALUES ('source_city_code', 'to')
        "
      );
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
         VALUES ('*', '-', '', 'system');
         -- populate with default admin user --
         INSERT INTO user (username, password, email, role)
         VALUES ('marco', '10b82717350f8d5408080b4900b665e8', 'marcosolari@gmail.com', 'admin');
        "
      );
      $this->db->exec(
        "CREATE TABLE if not exists person (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          key VARCHAR(32),
          source_country_code VARCHAR(2), -- TODO: use it!
          source_city_code VARCHAR(2), -- TODO: use it!
          source_key VARCHAR(16),
          url TEXT,
          timestamp_creation INTEGER,
          timestamp_last_sync INTEGER,
          page_sum TEXT,
          active INTEGER,
          active_label INTEGER
         );
         CREATE UNIQUE INDEX IF NOT EXISTS key_idx ON person (key);
        "
      );
      $this->db->exec(
        "CREATE TABLE if not exists person_detail (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          id_person INTEGER,
          id_user INTEGER,
          --id_uniqcode INTEGER,
          name TEXT,
          sex TEXT,
          zone TEXT,
          address TEXT,
          description TEXT,
          notes TEXT,
          phone VARCHAR(16),
          nationality VARCHAR(2),
          age INTEGER,
          vote INTEGER,
          rating INTEGER,
          showcase INTEGER,
          thruthful INTEGER,
          uniq_prev TEXT,
          uniq_next TEXT
         );
         CREATE INDEX IF NOT EXISTS phone_idx ON person_detail (phone);
        "
      );
      $this->db->exec(
        "CREATE TABLE IF NOT EXISTS comment (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          key VARCHAR(32),
          phone VARCHAR(16),
          topic TEXT,
          timestamp INTEGER,
          timestamp_creation INTEGER,
          timestamp_last_sync INTEGER,
          author_nick TEXT,
          author_karma VARCHAR(16),
          author_posts INTEGER,
          content TEXT,
          url TEXT
         );
         CREATE UNIQUE INDEX IF NOT EXISTS key_idx ON comment (key);
         CREATE UNIQUE INDEX IF NOT EXISTS phone_idx ON comment (phone);
         CREATE INDEX IF NOT EXISTS timestamp_idx ON comment (timestamp);
         CREATE INDEX IF NOT EXISTS topic_idx ON comment (topic);
        "
      );
      $this->db->exec(
        "CREATE TABLE IF NOT EXISTS comment_detail (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          id_comment INTEGER,
          id_user INTEGER,
          id_person INTEGER,
          content_rating INTEGER
         );
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
          timestamp_last_modification INTEGER,
          signature VARCHAR(256),
          showcase INTEGER,
          thruthful INTEGER
         );
        "
      );
    } catch (PDOException $e) {
      throw new Exception("can't create tables: " . $e->getMessage());
    }
  }

  /*
   ***********************************************************************************************************************
   * section: person-specific table functions
   ***********************************************************************************************************************
   */

  public function getPersons($sieves = null, $userId = self::DB_SYSTEM_USER_ID) { # TODO: select SYSTEM and USER fields...???!!!
    $tableMaster = "person";
    $tableDetail = "person" . "_" . "detail";
    $groupByField = "id_person";
    if ($userId === null) {
      $userId = self::DB_SYSTEM_USER_ID; // handle case where userId is set, but null
    }
    $params = [];

    try {
      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.*, max({$tableDetail}.id_user) AS id_user_max
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.{$groupByField}
        WHERE (id_user = {$this->userIdSystem} OR id_user = {$userId})
      ";

      list($sqlSieves, $params) = $this->sieves2Sql($sieves);
      $sql .= $sqlSieves;

      // group by $groupByField
      $sql .= " GROUP BY {$tableDetail}.{$groupByField}";

      $statement = $this->db->prepare($sql);
      foreach ($params as $key => &$value) {
        $statement->bindParam(":" . $key, $value);
      }
      $statement->execute();
#$this->router->log("debug", " db->getPersons() - sql: $sql");
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
#$this->router->log("debug", "db->getPersons() - result length: " . count($result));
#$this->router->log("debug", "db->getPersons() - userId: $userId - result:" . any2string($result));
      return $result;
    } catch (PDOException $e) {
      #throw new Exception("error getting persons with filters", 0, $e); # TODO: SHOULD USE THIS??? 
      throw new Exception("can't get from $tableMaster, $tableDetail with filters: " . $e->getMessage()); # TODO: USE THIS METHOD EVERYWHERE!!!
    }
  }

  public function getPerson($id, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "person";
    $tableDetail = "person" . "_" . "detail";
    $groupByField = "id_person";
    try {
      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.* --, max({$tableDetail}.id_user) AS id_user_max
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.{$groupByField}
        WHERE 1 = 1
      ";
      $sql .= " AND {$tableMaster}.id = :id";
      $sql .= " AND ({$tableDetail}.id_user = {$this->userIdSystem} OR {$tableDetail}.id_user = {$userId})";
      $sql .= " GROUP BY {$tableDetail}.{$groupByField}";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id", $id, PDO::PARAM_INT);
      #$statement->bindParam(":id_user", $userId, PDO::PARAM_INT);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get person data: " . $e->getMessage());
    }
  }

  public function getPersonByField($fieldName, $fieldValue, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "person";
    $tableDetail = "person" . "_" . "detail";
    $groupByField = "id_person";
    try {
      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.*, max({$tableDetail}.id_user) AS id_user_max
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.{$groupByField}
        WHERE $fieldName = :$fieldName
      ";
      $sql .= " AND ({$tableDetail}.id_user = {$this->userIdSystem} OR {$tableDetail}.id_user = {$userId})";
      $sql .= " GROUP BY {$tableDetail}.{$groupByField}";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue);
#$this->router->log("debug", " db->getPersonByField() - sql: [$sql]" . "\n" . any2string([$fieldName, $fieldValue]));
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
$this->router->log("debug", " db->getPersonByField() - count(result):" . "\n" . count($result));
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get person data: " . $e->getMessage());
    }
  }

  public function addPerson($arrayMaster, $arrayDetail = null, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "person";
    $tableDetail = "person" . "_" . "detail";
    $groupByField = "id_person";

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
        $statement->bindParam(":" . $key, $value);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($count != 1) {
        throw new Exception("can't add record to table $tableMaster (added $count records)");
      }
      $lastMasterInsertId = $this->db->lastInsertId();
    } catch (PDOException $e) {
      throw new Exception("can't add record to table $tableMaster: " . $e->getMessage());
    }

    if (!empty($arrayDetail)) {

      $arrayDetail["id_person"] = $lastMasterInsertId; // add master person id to this detail record
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
#throw new Exception("detail sql: [$sql], arrayDetail:" . var_export($arrayDetail, true));
        $statement = $this->db->prepare($sql);
        foreach ($arrayDetail as $key => &$value) {
          $statement->bindParam(":" . $key, $value);
        }
        $statement->execute();
        $count = $statement->rowCount();
        if ($count != 1) {
          throw new Exception("can't add record to table $tableDetail (added $count records)");
        }
        $lastDetailInsertId = $this->db->lastInsertId();
      } catch (PDOException $e) {
        throw new Exception("can't add record to table $tableDetail: " . $e->getMessage());
      }
    }
    return $lastMasterInsertId;
  }

  public function setPerson($id, $arrayMaster = null, $arrayDetail = null, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "person";
    $tableDetail = "person_detail";
    $groupByField = "id_person";

    if (!empty($arrayMaster)) {
      try {
        $set = "";
        foreach ($arrayMaster as $key => $value) {
          $set .= ($set ? ", " : "") . $key . " = " . ":" . $key;
        }
        $sql = "
          UPDATE {$tableMaster}
          SET $set
          WHERE id = :$groupByField
        ";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':' . $groupByField, $id, PDO::PARAM_INT);
        foreach ($arrayMaster as $key => &$value) {
          $statement->bindParam(":" . $key, $value);
        }
        $statement->execute();
        $count = $statement->rowCount();
        if ($statement->rowCount() != 1) {
          throw new Exception("update into table $tableMaster for id [$id] did update " . $statement->rowCount() . " records");
        }
      } catch (PDOException $e) {
        throw new Exception("can't update record to table $tableMaster: " . $e->getMessage());
      }
    }

    if (!empty($arrayDetail)) {
      #$this->router->log("debug", " setPerson() - arrayDetail:" . any2string($arrayDetail));
      $set = "";
      $ins_fields = $ins_values = "";
      foreach ($arrayDetail as $key => $value) {
        $set .= ($set ? ", " : "") . $key . " = " . ":" . $key;
        $ins_fields .= ($ins_fields ? ", " : "") . $key;
        $ins_values .= ($ins_values ? ", " : "") . ":" . $key;
      }

      try { // check if details data should be inserted or updated
        $sql = "
          SELECT count(*) as count FROM {$tableDetail}
          WHERE
           $groupByField = :$groupByField
          AND
           id_user = :id_user
        ";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':' . $groupByField, $id, PDO::PARAM_INT);
        $statement->bindParam(':id_user', $userId, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $mode = null;
        if ($result["count"] === "0") {
          $mode = "insert";
          $this->router->log("debug", "   DB::setPerson() - will INSERT record");
        } else {
          $mode = "update";
          $this->router->log("debug", "   DB::setPerson() - will UPDATE record");
        }
      } catch (PDOException $e) {
        throw new Exception("can't check record in table $tableDetail: " . $e->getMessage());
      }

      try { // add details data
        if ($mode === "update") { // update
          $sql = "
            UPDATE {$tableDetail}
            SET $set
            WHERE
             id_person = :$groupByField
            AND
             id_user = :id_user
          ";
        } else { // insert
          $sql = "
            INSERT INTO {$tableDetail}
            ($groupByField, id_user, $ins_fields)
            VALUES
            (:$groupByField, :id_user, $ins_values)
          ";
        }
        #$this->router->log("debug", " setPerson() - sql:" . $sql);
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':' . $groupByField, $id, PDO::PARAM_INT);
        $statement->bindParam(':id_user', $userId, PDO::PARAM_INT);
        foreach ($arrayDetail as $key => &$value) {
          $statement->bindParam(":" . $key, $value);
        }
        $statement->execute();
        $count = $statement->rowCount();
        if ($count != 1) {
          throw new Exception("can't $mode record in table $tableDetail (updated $count records)");
        }
      } catch (PDOException $e) {
        throw new Exception("can't $mode record in table $tableDetail: " . $e->getMessage());
      }
    }
    return $id;
  }

  public function deletePerson($id, $userId = self::DB_SYSTEM_USER_ID) {
    # TODO: handle userId
    $table = "person";
    try {
      $sql = "
        DELETE
        FROM {$table}
        WHERE id = :id
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);
      $statement->bindParam(":" . $key, $value);
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

/*
  / **
   * get all persons uniqueness codes
   * /
  public function getPersonsUniqcodes($userId = self::DB_SYSTEM_USER_ID) {
    $table = "person_uniqcode";
    try {
      $sql = "
        SELECT id, id_user, id_person_1, id_person_2, same
        FROM {$table}
        WHERE (id_user = :id_user_system OR id_user = :id_user)
        ORDER BY id_user DESC -- lower id_user (systems's) last
      ";
$this->router->log("debug", " getPersonsUniqcodes() - sql: [$sql]" . any2string([$this->userIdSystem, $userId]));
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id_user_system', $this->userIdSystem);
      $statement->bindParam(':id_user', $userId);
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
$this->router->log("debug", " getPersonsUniqcodes() - result: [".any2string($result)."]");
      if ($result) { // at least one record present
        return $result; // return only first (user's, if present) record
      } else { // no records present
        return null; // no uniqcode set for any person
      }
    } catch (PDOException $e) {
      throw new Exception("can't get persons uniq codes in table $table: " . $e->getMessage());
    }
  }

  public function getPersonsUniqcode($id_person_1, $id_person_2, $userId = self::DB_SYSTEM_USER_ID) {
    $table = "person_uniqcode";
    if ($userId === null) $userId = intval(self::DB_SYSTEM_USER_ID); # TODO: BLAHHHHH
    try {
      $sql = "
        SELECT id, id_user, same
        FROM {$table}
        WHERE (id_user = :id_user_system OR id_user = :id_user)
        AND (
          id_person_1 = :id_person_1 AND
          id_person_2 = :id_person_2
        ) OR (
          id_person_1 = :id_person_2 AND
          id_person_2 = :id_person_1
        )
        ORDER BY id_user DESC -- lower id_user (systems's) last
      ";
$this->router->log("debug", " getPersonsUniqcode() - sql: [$sql], userId: " . any2string($userId) . ", userIdSystem: " . any2string($this->userIdSystem));
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id_user_system', $this->userIdSystem);
      $statement->bindParam(':id_user', $userId);
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
    if ($userId === null) $userId = intval(self::DB_SYSTEM_USER_ID); # TODO: BLAHHHHH
    try {
      $result = $this->getPersonsUniqcode($id_person_1, $id_person_2, $userId);
$this->router->log("debug", " getPersonsUniqcode($id_person_1, $id_person_2) => " . var_export($result, true));
      $count = count($result);
      if ($count > 0) { // a uniqcode already present for these persons, update it
$this->router->log("debug", " EXISTING UNIQCODE");
        if ($result["same"] === $same) { // current value is equal to the value to be set
$this->router->log("debug", "  SAME VALUE IS ALREADY [$same], DO NOTHING...");
          ; // do nothing
        } else { // current value is different from the value to be set
$this->router->log("debug", "  SAME VALUE IS *NOT* ALREADY [$same], UPDATING...");
          $id = $result["id"];
          $sql = "
            UPDATE {$table}
            SET same = :same
            WHERE
             (id = :id) AND
             (id_user = :id_user)
          ";
          $statement = $this->db->prepare($sql);
          $statement->bindParam(":same", $same, PDO::PARAM_INT);
          $statement->bindParam(":id", $id, PDO::PARAM_INT);
          $statement->bindParam(":id_user", $userId, PDO::PARAM_INT);
          $statement->execute();
          $count = $statement->rowCount();
          if ($count != 1) {
           throw new Exception("can't update persons [$id_person_1] and [$id_person_2] uniq code in table $table: " . $e->getMessage());
          }
        }
      } else { // a uniqcode not yet present for these persons, insert it
$this->router->log("debug", " NEW UNIQCODE");
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
*/

/*
  public function getAverageFieldByPerson($table, $personId, $fieldName) {
    try {
      $sql = "
        SELECT AVG($fieldName) AS avg
        FROM {$table}
        WHERE id_person = :id_person
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . "id_person", $personId);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result["avg"];
    } catch (PDOException $e) {
      throw new Exception("can't get average field by person from $table: " . $e->getMessage());
    }
  }
*/

  /*
   ***********************************************************************************************************************
   * section: comment-specific table functions
   ***********************************************************************************************************************
   */

   public function getComments($userId = self::DB_SYSTEM_USER_ID) { # TODO: select SYSTEM and USER fields...???!!!
    $tableMaster = "comment";
    $tableDetail = "comment" . "_" . "detail";
    $groupByField = "id_comment";
    if ($userId === null) {
      $userId = self::DB_SYSTEM_USER_ID; // handle case where userId is set, but null
    }
    $params = [];

    try {
      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.*, max({$tableDetail}.id_user) AS id_user_max
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.{$groupByField}
        WHERE (id_user = {$this->userIdSystem} OR id_user = {$userId})
      ";

      // group by id_comment
      $sql .= " GROUP BY {$tableDetail}.{$groupByField}";

      $statement = $this->db->prepare($sql);
      foreach ($params as $key => &$value) {
        $statement->bindParam(":" . $key, $value);
      }
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get from $tableMaster, $tableDetail with filters: " . $e->getMessage());
    }
  }

  public function getComment($id, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "comment";
    $tableDetail = "comment" . "_" . "detail";
    $groupByField = "id_comment";
    try {
      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.* --, max({$tableDetail}.id_user) AS id_user_max
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.{$groupByField}
        WHERE 1 = 1
      ";
      $sql .= " AND {$tableMaster}.id = :id";
      $sql .= " AND ({$tableDetail}.id_user = {$this->userIdSystem} OR {$tableDetail}.id_user = {$userId})";
      $sql .= " GROUP BY {$tableDetail}.{$groupByField}";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":id", $id, PDO::PARAM_INT);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get person data: " . $e->getMessage());
    }
  }

  public function getCommentByField($fieldName, $fieldValue, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "comment";
    $tableDetail = "comment" . "_" . "detail";
    $groupByField = "id_comment";
    try {
      $sql = "
        SELECT {$tableMaster}.*, {$tableDetail}.*, max({$tableDetail}.id_user) AS id_user_max
        FROM {$tableMaster}
        JOIN {$tableDetail}
        ON {$tableMaster}.id = {$tableDetail}.{$groupByField}
        WHERE $fieldName = :$fieldName
      ";
      $sql .= " AND ({$tableDetail}.id_user = {$this->userIdSystem} OR {$tableDetail}.id_user = {$userId})";
      $sql .= " GROUP BY {$tableDetail}.{$groupByField}";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue);
#$this->router->log("debug", " db->getCommentByField() - sql: [$sql]" . "\n" . any2string([$fieldName, $fieldValue]));
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
#$this->router->log("debug", " db->getCommentByField() - count(result):" . "\n" . count($result));
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get person data: " . $e->getMessage());
    }
  }

  public function addComment($arrayMaster, $arrayDetail = null, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "comment";
    $tableDetail = "comment" . "_" . "detail";
    $groupByField = "id_comment";
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
        $statement->bindParam(":" . $key, $value);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($count != 1) {
        throw new Exception("can't add record to table $tableMaster (added $count records)");
      }
      $lastMasterInsertId = $this->db->lastInsertId();
    } catch (PDOException $e) {
      throw new Exception("can't add record to table $tableMaster: " . $e->getMessage());
    }

    if (!empty($arrayDetail)) {
      $arrayDetail[$groupByField] = $lastMasterInsertId; // add master person id to this detail record
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
#throw new Exception("detail sql: [$sql], arrayDetail:" . var_export($arrayDetail, true));
        $statement = $this->db->prepare($sql);
        foreach ($arrayDetail as $key => &$value) {
          $statement->bindParam(":" . $key, $value);
        }
        $statement->execute();
        $count = $statement->rowCount();
        if ($count != 1) {
          throw new Exception("can't add record to table $tableDetail (added $count records)");
        }
        $lastDetailInsertId = $this->db->lastInsertId();
      } catch (PDOException $e) {
        throw new Exception("can't add record to table $tableDetail: " . $e->getMessage());
      }
    }
    return $lastMasterInsertId;
  }

  public function setComment($id, $arrayMaster = null, $arrayDetail = null, $userId = self::DB_SYSTEM_USER_ID) {
    $tableMaster = "comment";
    $tableDetail = "comment_detail";
    $groupByField = "id_comment";

    if (!empty($arrayMaster)) {
      try {
        $set = "";
        foreach ($arrayMaster as $key => $value) {
          $set .= ($set ? ", " : "") . $key . " = " . ":" . $key;
        }
        $sql = "
          UPDATE {$tableMaster}
          SET $set
          WHERE id = :$groupByField
        ";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':' . $groupByField, $id, PDO::PARAM_INT);
        foreach ($arrayMaster as $key => &$value) {
          $statement->bindParam(":" . $key, $value);
        }
        $statement->execute();
        $count = $statement->rowCount();
        if ($statement->rowCount() != 1) {
          throw new Exception("update into table $tableMaster for id [$id] did update " . $statement->rowCount() . " records");
        }
      } catch (PDOException $e) {
        throw new Exception("can't update record to table $tableMaster: " . $e->getMessage());
      }
    }

    if (!empty($arrayDetail)) {
$this->router->log("debug", " setComment() - arrayDetail:" . any2string($arrayDetail));
      $set = "";
      $ins_fields = $ins_values = "";
      foreach ($arrayDetail as $key => $value) {
        $set .= ($set ? ", " : "") . $key . " = " . ":" . $key;
        $ins_fields .= ($ins_fields ? ", " : "") . $key;
        $ins_values .= ($ins_values ? ", " : "") . ":" . $key;
      }

      try { // check if details data should be inserted or updated
        $sql = "
          SELECT count(*) as count FROM {$tableDetail}
          WHERE
           $groupByField = :$groupByField
          AND
           id_user = :id_user
        ";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':' . $groupByField, $id, PDO::PARAM_INT);
        $statement->bindParam(':id_user', $userId, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
#$this->router->log("debug", " setComment() - check result:" . any2string($result));
        $mode = null;
        if ($result["count"] === "0") {
          $mode = "insert";
          $this->router->log("debug", " setComment() - will INSERT record");
        } else {
          $mode = "update";
          $this->router->log("debug", " setComment() - will UPDATE record");
        }
      } catch (PDOException $e) {
        throw new Exception("can't check record in table $tableDetail: " . $e->getMessage());
      }

      try { // add details data
        if ($mode === "update") { // update
          $sql = "
            UPDATE {$tableDetail}
            SET $set
            WHERE
             $groupByField = :$groupByField
            AND
             id_user = :id_user
          ";
        } else { // insert
          $sql = "
            INSERT INTO {$tableDetail}
            ($groupByField, id_user, $ins_fields)
            VALUES
            (:$groupByField, :id_user, $ins_values)
          ";
        }
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':' . $groupByField, $id, PDO::PARAM_INT);
        $statement->bindParam(':id_user', $userId, PDO::PARAM_INT);
        foreach ($arrayDetail as $key => &$value) {
          $statement->bindParam(":" . $key, $value);
        }
        $statement->execute();
        $count = $statement->rowCount();
        if ($count != 1) {
          throw new Exception("can't $mode record in table $tableDetail (updated $count records)");
        }
      } catch (PDOException $e) {
        throw new Exception("can't $mode record in table $tableDetail: " . $e->getMessage());
      }
    }
    return $id;
  }

  public function deleteComment($id, $userId = self::DB_SYSTEM_USER_ID) {
    # TODO: handle userId
    $table = "comment";
    try {
      $sql = "
        DELETE
        FROM {$table}
        WHERE id = :id
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);
      $statement->bindParam(":" . $key, $value);
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


  /*
   ***********************************************************************************************************************
   * section: generic table functions
   ***********************************************************************************************************************
   */

  public function getAll($table) {
    try {
      $sql = "
        SELECT *
        FROM {$table}
      ";
      $statement = $this->db->prepare($sql);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception("can't get all records from table $table: " . $e->getMessage());
    }
  }

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
#$this->router->log("debug", " db->getByField() - sql: [$sql]" . "\n" . any2string([$fieldName, $fieldValue]));
      $statement->bindParam(":" . $fieldName, $fieldValue);
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_ASSOC);
#$this->router->log("debug", " db->getByField($fieldName, $fieldValue) - results:" . any2string($results));
      return $results;
    } catch (PDOException $e) {
      throw new Exception("can't get $table by field: " . $e->getMessage());
    }
  }

  public function getFieldDistinctValues($table, $fieldName, $userId = self::DB_SYSTEM_USER_ID) {
    try {
      $sql = "
        SELECT DISTINCT {$fieldName}
        FROM {$table}
      ";
      $statement = $this->db->prepare($sql);
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_COLUMN);
      return $results;
    } catch (PDOException $e) {
      throw new Exception("can't get $table field $fieldName distinct values: " . $e->getMessage());
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
        $statement->bindParam(":" . $key, $value);
      }
      $statement->execute();
      $results = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $results;
    } catch (PDOException $e) {
      throw new Exception("can't get $table by fields: " . $e->getMessage());
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
      $statement->bindParam(":fieldValue", $fieldValue);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result["count"];
    } catch (PDOException $e) {
      throw new Exception("can't count $table by field: " . $e->getMessage());
    }
  }

  public function add($table, $array, $userId = self::DB_SYSTEM_USER_ID) {
    try { // add data
      $fields = $values = "";
      foreach ($array as $key => $value) {
        $fields .= ($fields ? ", " : "") . $key;
        $values .= ($values ? ", " : "") . ":" . $key;
      }
      $sql = "
        INSERT INTO {$table}
        ($fields)
        VALUES
        ($values)
      ";
      $statement = $this->db->prepare($sql);
      foreach ($array as $key => &$value) {
        $statement->bindParam(":" . $key, $value);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($count != 1) {
        throw new Exception("can't add record to table $table (added $count records)");
      }
      $lastInsertId = $this->db->lastInsertId();
    } catch (PDOException $e) {
      throw new Exception("can't add record to table $table: " . $e->getMessage());
    }
    return $lastInsertId;
  }

  public function set($table, $id, $array, $userId = self::DB_SYSTEM_USER_ID) {
    try {
      $set = "";
      foreach ($array as $key => $value) {
        $set .= ($set ? ", " : "") . $key . " = " . ":" . $key;
      }
      $sql = "
        UPDATE {$table}
        SET $set
        WHERE id = :id
      ";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);
      foreach ($array as $key => $value) {
        $statement->bindParam(":" . $key, $value);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($statement->rowCount() != 1) {
        throw new Exception("update into table $table for id [$id] did update " . $statement->rowCount() . " records");
      }
    } catch (PDOException $e) {
      throw new Exception("can't update record to $table: " . $e->getMessage());
    }
    return $id;
  }

  public function setByField($table, $fieldName, $fieldValue) {
    try {
      $set = $fieldName . " = " . ":" . $fieldName;
      $sql = "
        UPDATE {$table}
        SET $set
        WHERE field_name = '$fieldName'
      ";

      $statement = $this->db->prepare($sql);
      $statement->bindParam(':field_name', $fieldName, PDO::PARAM_STRING);
      $statement->bindParam(":" . $fieldName, $fieldValue);
      $statement->execute();
      $count = $statement->rowCount();
      if ($statement->rowCount() != 1) {
        throw new Exception("update into table $table for id [$id] did update " . $statement->rowCount() . " records");
      }
    } catch (PDOException $e) {
      throw new Exception("can't update record to $table: " . $e->getMessage());
    }
    return $id;
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
      $statement->bindParam(":" . $key, $value);
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
    $sql = "";
    $params = [];

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
        $params["active"] = ($sieves["filters"]["active"] === "yes") ? 1 : 0;
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

/*
    // add condition to get only first 'uniq' persons
    $sql .= " AND ";
    $sql .= "uniq_prev is NULL";
    if (
      $sieves &&
      $sieves["uniqIds"]
    ) {
      foreach ($sieves["uniqIds"] as $uniqId) {
        $sql .= " OR ";
        $sql .= "person.id = $uniqId";
      }
    }
*/
    return [ $sql, $params ];
  }

/* WE USE XDEBUG TO PROFILE, REMOVE-ME...
  private function profileForSpeed($method) { # TODO: to be tested...
    $time_start = microtime(true);
    call($method);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    return $time;
  }
*/

  function __destruct() {
    $this->db = null;
  }
}
?>