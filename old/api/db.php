<?php

private function setupDb() {
        try {
            $this->db = new PDO(
                'sqlite:db/escrape.sqlite3',
                'user', 'password',
                [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );

            $sql = "
                CREATE TABLE IF NOT EXISTS person (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key VARCHAR(32) NOT NULL,
                    name VARCHAR(64),
                    sex VARCHAR(1),
                    zone VARCHAR(32),
                    description VARCHAR(4096),
                    phone VARCHAR(32),
                    email VARCHAR(256),
                    timestamp TIMESTAMP,
                    from_site VARCHAR(4),
                    from_page_url VARCHAR(2048),
                    from_page_sum VARCHAR(32)
                );
                CREATE INDEX IF NOT EXISTS person_key_idx ON person (key);
            ";
            $this->db->exec($sql);

            $sql = "
                CREATE TABLE IF NOT EXISTS comment (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key VARCHAR(32) NOT NULL,
                    author VARCHAR(32),
                    timestamp TIMESTAMP,
                    content VARCHAR,
                    from_page_url VARCHAR(2048),
                    from_page_sum VARCHAR(32)
                );
                CREATE INDEX IF NOT EXISTS comment_key_idx ON comment (key);
            ";
            $this->db->exec($sql);

            $sql = "
                CREATE TABLE IF NOT EXISTS availability (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key VARCHAR(32) NOT NULL,
                    date_from TIMESTAMP NOT NULL,
                    date_to TIMESTAMP NOT NULL
                );
                CREATE INDEX IF NOT EXISTS availability_key_idx ON availability (key);
            ";
            $this->db->exec($sql);

        } catch(PDOException $e) {
            throw($e);
        }
    }

    private function loadDb() {
        $persons = [];
        try {
            $sql = "
                SELECT * FROM person
            ";
            #$query = $this->db->prepare($sql);
            #$query->execute();   
            $query = $this->db->exec($sql);
            for ($i = 0; $record = $query->fetch(); $i++) {
echo $i . " - " . var_dump($record); # echo $row['name'] . "<br/>";
                $persons[] = $record;
            }
            unset($query);
#var_dump($persons);

        } catch(PDOException $e) {
            throw($e);
        }
    }


                try {
                    $sth = $this->db->prepare('INSERT INTO person 
                        (name, sex, zone, description, phone, timestamp, from_site, from_page_url, from_page_sum) VALUES
                        (?,    ?,   ?,    ?,           ?,     ?,         ?,        ?,           ?)
                    ');
                    $sth->execute([
                        $data[$key]["name"],
                        $data[$key]["sex"],
                        $data[$key]["zone"],
                        $data[$key]["description"],
                        $data[$key]["phone"],
                        $data[$key]["timestamp"],
                        $data[$key]["site_id"],
                        $data[$key]["url"],
                        $data[$key]["page_sum"],
                    ]);
                } catch(PDOException $e) {
                    #return $this->ret("insert person", false, $e->getMessage());
                }

?>