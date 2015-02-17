<?php

/*
$db = new DB();
$db->add("person", array("name" => "pippo", "key" => "abc", "age" => 27 ));
$p = $db->getAll("person");
print_r($p);
*/

class DB extends PDO {
  const DB_TYPE = "sqlite";
  const DB_NAME = "db/escrape.sqlite";
  private $db;

  public function __construct() {
    try {
      $this->db = new PDO(self::DB_TYPE . ":" . self::DB_NAME);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->createTables();
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function createTables() {
    try {
      $this->db->exec(
        "create table if not exists user (
          id integer primary key autoincrement,
          name varchar,
          email text
         );
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
          --key varchar(32),
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
         --create unique index if not exists key_idx on comment (key);
         create unique index if not exists phone_idx on comment (phone);
         create index if not exists timestamp_idx on comment (timestamp);
         create index if not exists topic_idx on comment (topic);
        "
      );

      $this->db->exec(
        "create table if not exists photo (
          id integer primary key autoincrement,
          id_person integer,
          num integer,
          showcase integer,
          truthfulness integer,
          url text
         );
        "
      );

    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function getAll($table) {
    $sql = "select * from $table";
    return $this->getBySql($sql);                
  }

  public function getViaSQL($sql) {
    try {
      $statement = $this->db->query($sql);
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
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
      throw new Exception($e->getMessage());
    }
  }

  public function getByKey($table, $key) {
    try {
      $sql = "select * from $table where key = :key limit 1";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":key", $key, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function getByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "select * from $table where $fieldName = :$fieldName";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function getAverageFieldByPerson($table, $idPerson, $fieldName) {
    try {
      $sql = "select avg($fieldname) from $table where id_person = :id_person";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . "id_person", $idPerson); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function countByField($table, $fieldName, $fieldValue) {
    try {
      $sql = "count(*) from $table where $fieldName = :$fieldName";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(":" . $fieldName, $fieldValue); #, PDO::PARAM_STR);
      $statement->execute();
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function add($table, $data) {
    try {
      $fields = $values = "";
      foreach ($data as $key => $value) {
        $fields .= ($fields ? ", " : "") . $key;
        $values .= ($values ? ", " : "") . ":" . $key;
      }
      $sql = "insert into $table ($fields) values ($values)";
      $statement = $this->db->prepare($sql);
      foreach ($data as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      if ($statement->rowCount() != 1) {
        throw new Exception("insert into table $table did not insert one record");
      }
      return sqlite_last_insert_rowid();
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function set($table, $id, $data) {
    try {
      $set = "";
      foreach ($data as $key => $value) {
        $set .= ($set ? ", " : "") . $key . "=" . " " . ":" . $key;
      }
      $sql = "update $table set $set where id = :id";
      $statement = $this->db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);       
      foreach ($data as $key => &$value) {
        $statement->bindParam(":" . $key, $value); #, PDO::PARAM_STR);
      }
      $statement->execute();
      $count = $statement->rowCount();
      if ($statement->rowCount() != 1) {
        throw new Exception("update table $table did not update one record");
      }
      return $id;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
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
        throw new Exception("delete from $table did not delete one record");
      }
      return true;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  private function profile($method) {
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