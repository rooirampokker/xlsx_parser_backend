<?php
ini_set ('memory_limit', '-1');
ini_set ('max_execution_time', 2400); //40 minutes
ini_set ('display_errors', 1);
error_reporting(E_ALL);

class dbQueries {
    private $queryDescription;
    private $query;
    private $db;
    private $host;
    private $user;
    private $password;
    private $database;
    private $utils;

    public function __construct($host, $user, $password, $database) {
        $this->utils = new Utilities;
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
        $this->mysqlConnect();
    }
  /*
  *
  */
    function getInsertId() {
      return $this->db->insert_id;
    }
    /*
    *
    */
    function addUser($param) {
      $param = $this->utils->cleanInput($param, false);

      $this->queryDescription = "Populating table: wp_users";
      $this->query = "INSERT INTO wp_users\n\r ";
      $this->query .= "(user_login, user_pass, user_nicename, user_email, user_registered, display_name)\n\r ";
      $this->query .= "VALUES ( ";
      $this->query .= "'".$param['first_name']."_".$param['last_name']."',";
      $this->query .= "'test',";
      $this->query .= "'".$param['first_name']."_".$param['last_name']."',";
      $this->query .= "'".$param['email_address']."',";
      $this->query .= "NOW(),";
      $this->query .= "'".ucfirst($param['first_name'])." ".ucfirst($param['last_name'])."'";
      $this->query .= ")\n\r ";

      return $this->processQuery();
    }
  /*
   *
   */
    function addUserMeta($param) {
      $this->queryDescription = "Populating table: wp_usermeta";
      $this->query = "INSERT INTO wp_usermeta\n\r ";
      $this->query .= "(user_id, meta_key, meta_value)\n\r ";
      $this->query .= "VALUES ( ";
      $this->query .= "'".$param['id']."',";
      $this->query .= "'".$param['key']."',";
      $this->query .= "'".$param['value']."'";
      $this->query .= ")\n\r ";

      return $this->processQuery();
    }
  /*
  *
  */
  function getUser($param) {
    $this->queryDescription = "Get data from: wp_users";
    $this->query = "SELECT *\r ";
    $this->query .= "FROM wp_usermeta\r ";
    $this->query .= "JOIN wp_users on wp_usermeta.user_id = wp_users.id\r ";
    $this->query .= "WHERE wp_usermeta.meta_key = 'customer_key'\r ";
    $this->query .= "AND meta_value = '$param'\r ";

    return $this->processQuery(true);
  }
    /*
     *
     */
    private function processQuery($convertToArray=false) {
        if (!$result = $this->db->query($this->query)) {
            $this->utils->log($this->query);
            print_r("Error with query: $this->query<br><br>");
            exit(mysqli_error($this->db));
        } else {
            if (isset($_REQUEST['debug'])) {
                $this->utils->debug($this->query, $result, $this->queryDescription);
            }
            if ($convertToArray) {
              $result = $this->utils->convertMySQLOutputToArray($result);
            }

            return $result;
        }
    }
    /*
     *
     */
    private function mysqlConnect() {
        $this->db = mysqli_connect($this->host, $this->user, $this->password, $this->database);
        $msg = '';
        if (!$this->db) {
            $msg .= "Error: Unable to connect to MySQL." . PHP_EOL;
            $msg .= "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
            $msg .= "Debugging error: " . mysqli_connect_error() . PHP_EOL;
            $this->utils->log($msg);
            exit;
        }
    }
}