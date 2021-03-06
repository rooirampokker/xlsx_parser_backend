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
	function getProduct($param) {
		$this->queryDescription = "Get data from: wp_posts";
		$param								  = $this->utils->cleanInput($param, false);
		$param 									= $this->db->real_escape_string($param);
		$this->query = "SELECT *\n ";
		$this->query .= "FROM wp_posts\n ";
		$this->query .= "WHERE post_title = '".$param."'\n ";
		$this->query .= "AND post_status = 'publish'\n ";
		$this->query .= "AND post_type = 'product'\n ";

		return $this->processQuery(true);
	}
	/*
	*getProductVariation($orderID[0]['ID'], $orderItem['variation']);
	*/
	function getProductVariation($parentID, $param) {
		$this->queryDescription = "Get data from: wp_posts";
		$param								  = $this->utils->cleanInput($param, false);
		$param 									= $this->db->real_escape_string($param);
		$this->query = "SELECT *\n ";
		$this->query .= "FROM wp_posts\n ";
		$this->query .= "WHERE post_title LIKE '%".$param."'\n ";
		$this->query .= "AND post_type    = 'product_variation'\n ";
		$this->query .= "AND post_parent  = '".$parentID."'\n ";
		$this->query .= "AND post_status  = 'publish';\n ";

		return $this->processQuery(true);
	}
	/*
	 *
	 */
	function updateOrderItemWithVariation($parentID, $param) {
		$this->queryDescription = "Updating product item with variation";

//		$param	= $this->utils->cleanInput($param, false);
//		$param	= $this->db->real_escape_string($param);

		$this->query = "UPDATE wp_woocommerce_order_items\n ";
		$this->query .= "SET\n ";
		$this->query .= "order_item_name = '$param'\n ";
		$this->query .= "WHERE order_item_id = $parentID\n";

		return $this->processQuery();
	}
	/*
	*
	*/
	function createUser($param) {
		$this->queryDescription = "Populating table: wp_users";
		$param								  = $this->utils->cleanInput($param, false);

		$this->query = "INSERT IGNORE INTO wp_users\n ";
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
		if ($param['key'] != 'wp_capabilities') {//this is serialised info that contains double-quotes that should not be escaped
			$param = $this->utils->cleanInput($param, false);
		}
		$param['value'] = ($param['key'] === 'first_name' || $param['key'] === 'last_name') ? ucwords($param['value']) : $param['value'];

		$this->query = "INSERT IGNORE INTO wp_usermeta\n ";
		$this->query .= "(user_id, meta_key, meta_value)\n ";
		$this->query .= "VALUES (\n ";
		$this->query .= "'".$param['id']."',\n ";
		$this->query .= "'".$param['key']."',\n ";
		$this->query .= "'".$param['value']."'\n ";
		$this->query .= ")\n ";

		return $this->processQuery(true);
	}
	/*
	*
	*/
	function getUser($param) {
		$this->queryDescription = "Get data from: wp_users by email";

		$this->query = "SELECT *\n ";
		$this->query .= "FROM wp_users\n ";
		$this->query .= "WHERE user_email = '$param'\n ";

		return $this->processQuery(true);
	}
	/*
	*
	*/
	function getUserByMeta($param) {
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
		$this->query .=  "'".$param['order_date']."',\n ";
		$this->query .=  "'".$param['order_date']."',\n ";
		$this->query .= "'".$param['post_title']."',\n ";
		$this->query .= "'".$param['post_status']."',\n ";
		$this->query .= "'".$param['ping_status']."',\n ";
		$this->query .= "'".$param['post_password']."',\n ";
		$this->query .= "'".$param['post_name']."',\n ";
		$this->query .=  "'".$param['order_date']."',\n ";
		$this->query .=  "'".$param['order_date']."',\n ";
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
		$param = $this->utils->cleanInput($param, false);

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
	function getOrder($param) {
		$this->queryDescription = "Get data from: wp_posts and wp_postmeta";
		$this->query = "SELECT *\n ";
		$this->query .= "FROM wp_posts\n ";
		$this->query .= "LEFT JOIN wp_postmeta on wp_postmeta.post_id = wp_posts.id\n ";
		$this->query .= "WHERE wp_postmeta.meta_key = '_yumbi_order_id'\n ";
		$this->query .= "AND meta_value = '$param'\n ";

		return $this->processQuery(true);
	}
	/*
	*
	*/
	function getOrderMeta($param) {

		$this->queryDescription = "Get data from: wp_posts and wp_postmeta";
		$this->query = "SELECT *\n ";
		$this->query .= "FROM wp_postmeta\n ";
		$this->query .= "WHERE post_id = '$param'\n ";

		return $this->processQuery(true);
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
	function createOrderItem($param) {
		$this->queryDescription = "Populating table: wp_woocommerce_order_items";

		$param								  = $this->utils->cleanInput($param, false);
		$param['item_name']			= $this->db->real_escape_string($param['item_name']);

		$this->query = "INSERT INTO wp_woocommerce_order_items\n ";
		$this->query .= "(order_item_name, order_item_type, order_id)\n ";
		$this->query .= "VALUES (\n ";
		$this->query .= "'".$param['item_name']."',\n ";
		$this->query .= "'".$param['item_type']."',\n ";
		$this->query .= "'".$param['order_id']."'\n ";
		$this->query .= ")\n ";

		return $this->processQuery();
	}
/*
 *
 */
	function createOrderItemMeta($param) {
		$this->queryDescription = "Populating table: wp_woocommerce_order_itemmeta";
		$this->query = "INSERT INTO wp_woocommerce_order_itemmeta\n ";
		$this->query .= "(order_item_id, meta_key, meta_value)\n ";
		$this->query .= "VALUES (\n ";
		$this->query .= "'".$param['item_id']."',\n ";
		$this->query .= "'".$param['meta_key']."',\n ";
		$this->query .= "'".$param['meta_value']."'\n ";
		$this->query .= ")\n ";

		return $this->processQuery();
	}
/*
 *
 */
	function clearCustomers() {
		$this->queryDescription = "Deleting imported items from : wp_users and wp_usermeta";
		$this->query = "DELETE wp_users, wp_usermeta\n ";
		$this->query .= "FROM wp_users\n ";
		$this->query .= "JOIN wp_usermeta ON wp_usermeta.user_id = wp_users.ID\n ";
		$this->query .= "WHERE user_pass = 'set password'\n ";

		return $this->processQuery();
	}
/*
 *
 */
	function clearOrders() {
		$this->queryDescription = "Deleting imported items from : wp_posts and wp_postmeta";
		$this->query = "DELETE wp_posts, wp_postmeta\n ";
		$this->query .= "FROM wp_posts\n ";
		$this->query .= "JOIN wp_postmeta ON wp_postmeta.post_id = wp_posts.id\n ";
		$this->query .= "AND wp_posts.post_excerpt = 'yumby import';\n ";

		return $this->processQuery();
	}
/*
 *
 */
	function clearOrderItems() {
		$this->queryDescription = "Deleting imported items from : wp_woocommerce_order_items and wp_woocommerce_order_itemmeta";
		$this->query = "DELETE wp_woocommerce_order_items, wp_woocommerce_order_itemmeta\n ";
		$this->query .= "FROM wp_woocommerce_order_items\n ";
		$this->query .= "JOIN wp_woocommerce_order_itemmeta ON wp_woocommerce_order_itemmeta.order_item_id = wp_woocommerce_order_items.order_item_id\n ";
		$this->query .= "AND wp_woocommerce_order_items.order_id IN (\n ";
		$this->query .= "SELECT ID\n ";
  	$this->query .= "FROM wp_posts\n ";
		$this->query .= "WHERE wp_posts.post_excerpt = 'yumby import'\n ";
		$this->query .= "AND post_type = 'shop_order')\n ";

		return $this->processQuery();
	}
	/*
	 *
	 */
	private function processQuery($convertToArray=false) {
		if (!$result = $this->db->query($this->query)) {
			$msg = "Error with query: $this->query<br><br>";
			$msg .= mysqli_error($this->db);
			$this->utils->log($msg, 'ERROR');
		} else {
			$this->utils->debug($this->query, $result, $this->queryDescription);
		}
		if ($convertToArray) {
			$result = $this->utils->convertMySQLOutputToArray($result);
		}
		return $result;
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
			$this->utils->log($msg, 'ERROR');
			exit;
		}
	}
}