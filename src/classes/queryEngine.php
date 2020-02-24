<?php
//DELETE FROM `wp_usermeta` WHERE user_id > 1;
//DELETE FROM `wp_users` WHERE id > 1;
//DELETE FROM `wp_postmeta` WHERE user_id > 1;
//DELETE FROM `wp_posts` WHERE id > 1;

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
    function createUser($param) {
      $param = $this->utils->cleanInput($param, false);

      $this->queryDescription = "Populating table: wp_users";
      $this->query = "INSERT INTO wp_users\n ";
      $this->query .= "(user_login, user_pass, user_nicename, user_email, user_registered, display_name)\n ";
      $this->query .= "VALUES (\n ";
      $this->query .= "'".$param['user_login']."',\n ";
      $this->query .= "'".$param['password']."',\n ";
      $this->query .= "'".$param['user_login']."',\n ";
      $this->query .= "'".$param['email_address']."',\n ";
      $this->query .= "NOW(),\n ";
      $this->query .= "'".$param['nickname']."'\n ";
      $this->query .= ")\n\r ";

      return $this->processQuery();
    }
  /*
   *
   */
    function createUserMeta($param) {
      $this->queryDescription = "Populating table: wp_usermeta";
      $this->query = "INSERT INTO wp_usermeta\n ";
      $this->query .= "(user_id, meta_key, meta_value)\n ";
      $this->query .= "VALUES (\n ";
      $this->query .= "'".$param['id']."',\n ";
      $this->query .= "'".$param['key']."',\n ";
      $this->query .= "'".$param['value']."'\n ";
      $this->query .= ")\n ";

      return $this->processQuery();
    }
  /*
  *
  */
  function getUser($param) {
    $this->queryDescription = "Get data from: wp_users";
    $this->query = "SELECT *\n ";
    $this->query .= "FROM wp_usermeta\n ";
    $this->query .= "JOIN wp_users on wp_usermeta.user_id = wp_users.id\n ";
    $this->query .= "WHERE wp_usermeta.meta_key = 'customer_key'\n ";
    $this->query .= "AND meta_value = '$param'\n ";

    return $this->processQuery(true);
  }
  /*
  *
  */
  function getUserMeta($param) {
    $this->queryDescription = "Get data from: wp_usermeta";
    $this->query = "SELECT *\n ";
    $this->query .= "FROM wp_usermeta\n ";
    $this->query .= "WHERE user_id = '".$param['user_id']."'\n ";

    return $this->processQuery(true);
  }
  /*
   *
   */
    function createOrder($param) {
      $this->queryDescription = "Populating table: wp_posts";
      $this->query = "INSERT INTO wp_posts\n ";
      $this->query .= "(post_author, post_date, post_date_gmt, post_title, post_status, ping_status, post_password, post_name, post_modified, post_modified_gmt, post_content, post_excerpt, to_ping, pinged, post_content_filtered,  post_type)\n ";
      $this->query .= "VALUES (\n ";
      $this->query .= "'".$param['post_author']."',\n ";
      $this->query .= "NOW(),\n ";
      $this->query .= "NOW(),\n ";
      $this->query .= "'".$param['post_title']."',\n ";
      $this->query .= "'".$param['post_status']."',\n ";
      $this->query .= "'".$param['ping_status']."',\n ";
      $this->query .= "'".$param['post_password']."',\n ";
      $this->query .= "'".$param['post_name']."',\n ";
      $this->query .= "NOW(),\n ";
      $this->query .= "NOW(),\n ";
      $this->query .= "'',\n ";
      $this->query .= "'yumby import',\n "; //post_excerpt
      $this->query .= "'',\n ";//to_ping
      $this->query .= "'',\n ";//pinged
      $this->query .= "'',\n ";//post_content_filtered
      $this->query .= "'".$param['post_type']."'\n ";
      $this->query .= ")\n ";

      return $this->processQuery();
    }
  /*
   *
   */
    function createOrderMeta($param) {
      $this->queryDescription = "Populating table: wp_postmeta";
      $this->query = "INSERT INTO wp_postmeta\n ";
      $this->query .= "(post_id, meta_key, meta_value)\n ";
      $this->query .= "VALUES (\n ";
      $this->query .= "'".$param['post_id']."',\n ";
      $this->query .= "'".$param['key']."',\n ";
      $this->query .= "'".$param['value']."'\n ";
      $this->query .= ")\n ";

      return $this->processQuery();
    }
  /*
  *
  */
    function updateOrder($param) {
      $this->queryDescription = "Update table: wp_posts";
      $this->query = "UPDATE wp_posts\n ";
      $this->query .= "SET\n ";
      $this->query .= "guid = '".$param['guid']."'\n ";
      $this->query .= "WHERE ID = ".$param['id']."\n ";

      return $this->processQuery();
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