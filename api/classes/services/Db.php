<?php

$db = new DB();
$db->insert("persons", ["name" => "pippo", "age" => 27 ]);
echo $db->getAll($table);



class DB extends PDO {
  
  define("DB_TYPE", "sqlite");
  define("DB_NAME", "db/escrape.sqlite");

  public function __construct() {
    try {
      parent::__construct(DB_TYPE . ":" . $DB_NAME); #, db_USER, db_PASSWORD);
time_start = microtime(true);
      self::createTables();
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "createTables duration: $time seconds\n";
    } catch (PDOException $e) {
      throw new Exception($e->getMessage()); # ok ???
    }
  }

  public static function createTables() {
    try {
      $db = new db();
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $db->exec(
        "create table if not exists persons (
          id integer primary key, 
          name text,
          site text,
          url text,
          timestamp integer,
          sex text,
          zone text,
          description text,
          phone text,
          page_sum text,
          age text,
          vote integer,
         )");
          #photos
          #comments_count
          #comments_last_synced
      $db->exec(
        "create table if not exists comments (
          id integer primary key, 
         )");
         # ...
      $db = null;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  public static function getAll($table) {
    $sql = "select * from $table";
    return self::getBySql($sql);                
  }

  public static function getBySql($sql) {
    try {
      $db = new db();
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $statement = $db->query($sql);
      $statement->setFetchMode(PDO::FETCH_CLASS, __CLASS__);
      $result = $statement->fetchAll();
      $db = null;
      return $result;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }     
  }

  public static function getById($table, $id) {
    try {
      $db = new db();
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $sql = "select * from $table where id = :id limit 1";
      $statement = $db->prepare($sql);
      $statement->bindParam(':id', $id, PDO::PARAM_INT);       
      $statement->execute();
      $statement->setFetchMode(PDO::FETCH_CLASS, __CLASS__);
      $result = $statement->fetch();
      $db = null;
      return $result;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

/*
$db->insert("persons",
  [
    "name" => "bob",
    "age" => 27,
  ]
);
*/
  public static function insert($table, $data) {   
    try {
      $db = new db();
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $fields = $values = "";
      foreach ($data as $key => $value) {
        $fields .= ($fields ? ", " : "") . $key;
        $values .= ($values ? ", " : "") . ":" . $key;
      }
      $sql = "insert into $table ($fields) values ($values)";
      $statement = $db->prepare($sql);
      foreach ($data as $key => $value) {
        $statement->bindParam(":" . $key, $value.content); #, PDO::PARAM_STR);
      }
      $statement->execute();
      $count = $statement->rowCount();
      $db = null;
      return $count;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage());
    }
  }

  function __destruct() {
  }
}
?>