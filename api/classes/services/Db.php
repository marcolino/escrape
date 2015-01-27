<?php

/*
$db = new Db();
$db->load();
*/

class Db {
    private $dbPath = "db/";
    private $dbExt = ".db";
    private $db = [];
    public $data = [];

    function __construct() {
        $this->setup();
    }

    protected function setup() {
        $this->db["persons"] = $this->dbPath . "persons" . $this->dbExt;
        $this->db["comments"] = $this->dbPath . "comments" . $this->dbExt;
        $this->data["persons"] = [];
        $this->data["comments"] = [];
    }

    public function load($table) {
        if (!array_key_exists($table, $this->db)) {
            throw new Exception("table [$table] does not exist");
        }
        if (file_exists($this->db[$table])) {
            if (($serialized = @file_get_contents($this->db[$table])) === FALSE) {
                throw new Exception("can't open to read db file [" . $this->db[$table] . "]");
            }
            if (($this->data[$table] = unserialize($serialized)) === FALSE) {
                throw new Exception("can't unserialize db contents for [$table]");
            }
        } else {
            $this->data[$table] = [];
        }
    }

    public function store($table) {
        if (!array_key_exists($table, $this->db)) {
            throw new Exception("table [$table] does not exist");
        }
        if (isset($this->data[$table])) {
            if (($serialized = serialize($this->data[$table])) === FALSE) {
                throw new Exception("can't serialize data for [$table]");
            }
            if (@file_put_contents($this->db[$table], $serialized) === FALSE) {
                throw new Exception("can't open to write db file [" . $this->db[$table] . "]");
            }
        } else {
        }
    }

    function __destruct() {
    }
}
?>