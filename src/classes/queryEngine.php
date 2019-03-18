<?php

class dbQueries {
    private $queryDescription;
    private $query;
    private $db;
    private $host;
    private $user;
    private $password;
    private $database;
    private $utilities;

    public function __construct($host, $user, $password, $database) {
        $this->utilities = new Utilities;
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
        $this->mysqlConnect();
    }
    /*
    *
    */
    function sampleQuery1($param) {
        $this->queryDescription = "Performs selected database query";
        $this->query = "SELECT *\n\r ";
        $this->query .= "FROM table\n\r";
        $this->query .= "WHERE condition = $param\n\r";

        return $this->processQuery();
    }
    /*
     *
     */
    private function processQuery()
    {
        if (!$result = $this->db->query($this->query)) {
            print_r("Error with query: $this->query<br><br>");
            exit(mysqli_error($this->db));
        } else {
            if (isset($_REQUEST['debug'])) {
                $this->utilities->debug($this->query, $result, $this->queryDescription);
            }
            return $result;
        }
    }
    /*
     *
     */
    private function mysqlConnect() {
        $this->db = mysqli_connect($this->host, $this->user, $this->password, $this->database);
        if (!$this->db) {
            echo "Error: Unable to connect to MySQL." . PHP_EOL;
            echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
            echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
            exit;
        }
    }
}