<?php
/*
 * CALL DIRECTLY WITH THE FOLLOWING URL OR USER THE MATCHING xlsx_parser_frontend AS INTERFACE
 * http://localhost/xlsx_importer/xlsx_parser_backend/src/index.php?debug=1&sheet_option=customer
 *

  DELETE wp_users, wp_usermeta
  FROM wp_users
  JOIN wp_usermeta ON wp_usermeta.user_id = wp_users.ID
  WHERE user_pass = 'set password'

	DELETE wp_posts, wp_postmeta
  FROM wp_posts
  JOIN wp_postmeta ON wp_postmeta.post_id = wp_posts.id
		AND wp_posts.post_excerpt = 'yumby import'

  DELETE wp_woocommerce_order_items, wp_woocommerce_order_itemmeta
  FROM wp_woocommerce_order_items
  JOIN wp_woocommerce_order_itemmeta ON wp_woocommerce_order_itemmeta.order_item_id = wp_woocommerce_order_items.order_item_id
	AND wp_woocommerce_order_items.order_id NOT IN (
		SELECT ID
		FROM wp_posts
		WHERE 1)
 */
header('Access-Control-Allow-Origin: http://192.168.1.8:3000');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With, token, application/json,  multipart/form-data');

ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
session_start();
$_SESSION['order_id'] = 0;
//manually specify file and location - this would typically be provided during the file upload process via the front-end
$_SESSION['fileLocation'] = 'xlsx_files/FFY_import.xlsx';
require '../vendor/autoload.php';
require 'classes/xlsxReader.php';
require 'classes/utilities.php';
require 'classes/queryEngine.php';
require '.env';
$utils = new Utilities();
if (count($_FILES)) {//upload file
    $response = $utils->getFileToProcess();
    if ($response['success'] == 'true') {

        $fileLocation             = 'FFY_import.xlsx'; //$response['payload'];
        $_SESSION['fileLocation'] = $fileLocation;
        $sheetReader              = new xlsxReader($fileLocation);
        $sheetNames               = $sheetReader->getSheetNames();
        die(json_encode($sheetNames));
    } else {
        die(json_encode($response));
    }
} else {//file is uploaded and a sheet has been specified
    if (isset($_REQUEST['sheet_option'])) {
        if (count($_SESSION)) {

            $queryEngine = new dbQueries($host, $user, $password, $database);
            $sheetName   = $_REQUEST['sheet_option'];
            $sheetReader = new xlsxReader($_SESSION['fileLocation']);
            $dataStarts  = isset($_REQUEST['dataStarts']) ? $_REQUEST['dataStarts'] : 1;
            $output      = $sheetReader->matchColToRow($sheetName, $dataStarts);
            switch (strtoupper($sheetName)) {
              case 'CUSTOMERS':
                processCustomers($output, $queryEngine, $utils);
                break;
              case 'ORDERS':
                processOrders($output, $queryEngine, $utils);
                break;
              case 'ORDERITEMS':
                processOrderItems($output, $queryEngine, $utils);
                break;
              default:
                echo "$sheetName is not catered for";
            }
            $response = ['success' => 'true',
                         'debugData'  => $user,
                         'payload' => $output];
        } else {
            $response = ['success' => 'false',
                         'payload' => 'Server session invalid. Please re-upload your file'];
        }
    } else {
        $response = ['success' => 'false',
                     'payload' => 'Unknown action'];
    }
    die(json_encode($response));
}
/*
 *
 */
function processCustomers($output, $queryEngine) {
  foreach ($output['data'] as $user) {
    $user['password']        = 'set password';
    $user['nickname']        = ucfirst(trim($user['first_name'])) . " " . ucfirst(trim($user['last_name']));
    $user['description']     = "Automated import from Yumbi database";
    $user['wp_capabilities'] = serialize(["customer" => true]);
    $user['billing_phone']   = $user['mobile_number'];
    $user['user_login']      = str_replace(" ", "_", $user['nickname']);
    $user['order_count']     = 0; //will have to get this....
    $user['wp_user_level']   = 0;
    //LETS CHECK IF THE USER EXISTS FIRST BEFORE WE ADD...
		$existingUser = $queryEngine->getUser($user['email_address']);
		if ($existingUser === 0 || $existingUser === false || !count($existingUser)) {
			$queryEngine->createUser($user);
			$userId = $queryEngine->getInsertId();
		} else {
			$userId = $existingUser[0]['ID'];
		}
    //we don't want the following fields in meta
    unset($user['password']);
    unset($user['user_login']);

    foreach ($user as $key => $field) {
      $param['id']    = $userId;
      $param['key']   = $key;
      $param['value'] = $field;
      $queryEngine->createUserMeta($param);
    }
  }
}
/*
 *
 */
function processOrders($output, $queryEngine, $utils) {
  foreach ($output['data'] as $order) {
    $orderDesc = formatOrderDesc($order);
    $customer  = $queryEngine->getUserByMeta($order['customer_key']);
    if (!count($customer)) {
			$utils->log('Customer not found: '.$order['customer_key'], 'ERROR');
		}
    $order['user_id']       = count($customer) ? $customer[0]['user_id'] : 0;
    $order['post_author']   = 1;
    $order['post_title']    = $orderDesc['title'];
    $order['post_status']   = formatPostStatus($order);
    $order['ping_status']   = "closed";
    $order['post_password'] = uniqid('order_');
    $order['post_name']     = $orderDesc['name'];
    $order['post_type']     = "shop_order";
    $queryEngine->createOrder($order);

    //go back in now that we have an order ID and update GUID
    $orderID       = $queryEngine->getInsertId();
    $order['id']   = $orderID;
    $order['guid'] = "https://frozenforyou.co.za/?post_type=shop_order&p=".$orderID;
    $queryEngine->updateOrder($order);
    processOrderMeta($order, $customer, $queryEngine);
  }
}
/*
 * //TODO: WE STILL NEED A DELIVERED/DELIVERY-BY DATE
 */
function processOrderMeta($order, $customer, $queryEngine) {
  $order['user_id'] = $order['user_id'];
  $orderParam = $userParam = [];
  $userInfo = $queryEngine->getUserMeta($order);

  $orderMeta['_order_key']            = uniqid('wc_order_');
  $orderMeta['_yumbi_order_id']       = $order['order_id'];
  $orderMeta['_customer_user']        = $order['user_id'];
  $orderMeta['_payment_method']       = $order['payment_method'];
  $orderMeta['_payment_method_title'] = $order['payment_method'];
  $orderMeta['_created_via'] = 'yumby_import';
  $firstName = getMeta($userInfo, 'meta_key', 'first_name');
  $lastName  = getMeta($userInfo, 'meta_key', 'last_name');
  //$orderMeta['_cart_hash'] = $userInfo[1];
  $orderMeta['_billing_first_name']    =
    $orderMeta['_shipping_first_name'] =
    $userMeta['billing_first_name']    =
    $userMeta['shipping_first_name']   =
      $firstName;

  $orderMeta['_billing_last_name']    =
    $orderMeta['_shipping_last_name'] =
      $userMeta['billing_last_name']  =
      $userMeta['shipping_last_name'] =
      $lastName;

  $orderMeta['_billing_address_1']    =
    $orderMeta['_shipping_address_1'] =
    $userMeta['billing_address_1']    =
    $userMeta['shipping_address_1']   =
      $order['address_line_1'];

  $orderMeta['_billing_address_2']    =
    $orderMeta['_shipping_address_2'] =
    $userMeta['billing_address_2']    =
    $userMeta['shipping_address_2']   =
      $order['address_line_2'];

  $orderMeta['_billing_email'] =
    $userMeta['billing_email'] =
      count($customer) ? $customer[0]['user_email'] : "not@available.com";

  $orderMeta['_billing_phone'] =
    $userMeta['billing_phone'] =
      getMeta($userInfo, 'meta_key', 'billing_phone');

  $orderMeta['_cart_discount']    = $order['discount_value'];
  $orderMeta['_cart_discount']    = $order['discount_value'];
  $orderMeta['_billing_state']    = $orderMeta['_shipping_state'] = $order['province'];
  $orderMeta['_shipping_country'] = $orderMeta['_billing_country'] = "ZA";
  $orderMeta['_order_currency']   = 'ZAR';
  $orderMeta['_cart_discount']    = $order['discount_value'];
  $orderMeta['_order_shipping']   = $order['delivery_fee'];
  $orderMeta['_order_total']      = $order['card_payment_value'];
  $orderMeta['_date_completed']   = $orderMeta['_date_paid'] = strtotime($order['local_order_date']);
  $orderMeta['_paid_date']        = $orderMeta['_completed_date'] = $order['local_order_date'];
  $orderMeta['delivery_long']     = $order['delivery_longitude_coordinate'];
  $orderMeta['delivery_lat']      = $order['delivery_latitude_coordinate'];
  $orderMeta['delivery_tip']      = $order['driver_tip'];
  $orderMeta['discount']          = $order['discount'];
  //$orderMeta['_paid_date']        = $order[''];

  //$queryEngine->createUserMeta($param);
  foreach ($orderMeta as $key => $field) {
    $orderParam['post_id'] = $order['id'];
    $orderParam['key']     = $key;
    $orderParam['value']   = $field;
    $queryEngine->createOrderMeta($orderParam);
  }
  foreach ($userMeta as $key => $field) {
    $userParam['id'] = $order['user_id'];
    $userParam['key']     = $key;
    $userParam['value']   = $field;
    $queryEngine->createUserMeta($userParam);
  }
}
/*
 *
 */
function processOrderItems($output, $queryEngine) {
  $orderItemParam = [];
  foreach($output['data'] as $orderItem) {
    $order     = $queryEngine->getOrder($orderItem['order_id']);
    $orderMeta = $queryEngine->getOrderMeta($order[0]['post_id']);
    $orderItemParam['item_name'] = $orderItem['item'];
    $orderItemParam['item_type'] = "line_item";
    $orderItemParam['order_id']  = $order[0]['post_id'];
    $queryEngine->createOrderItem($orderItemParam);
    $orderItem['item_id']   = $queryEngine->getInsertId();
    //ADD SHIPPING AT THE END OF EACH ORDER
    if ($_SESSION['order_id'] != $order[0]['post_id']) {
      $_SESSION['order_id']   = $order[0]['post_id'];
      $shippingCost           = getMeta($orderMeta, 'meta_key', '_order_shipping');
      $orderItemParam['item_name'] = ($shippingCost > 0) ? 'Flat rate' : 'Free shipping';
      $orderItemParam['item_type'] = "shipping";
      //$queryEngine->createOrderItem($orderItemParam);
    }
    processOrderItemMeta($orderItem, $order, $orderMeta, $queryEngine);
  }

}
/*
 *
 */
function processOrderItemMeta($orderItem, $order, $orderMeta, $queryEngine) {
  $orderID = $queryEngine->getProduct($orderItem['item']);
  $orderItemMeta['_product_id'] = $orderID['ID'];
  //$orderItemMeta['_variation_id'] = $orderItem[''];
  $orderItemMeta['_qty'] = $orderItem['quantity'];
  $orderItemMeta['_line_subtotal'] = $orderItem['total_value'];
  $orderItemMeta['_line_subtotal_tax'] = $orderItem['total_vat'];
  $orderItemMeta['_line_total'] = $orderItem['total_value'];
  $orderItemMeta['_line_tax'] = $orderItem['total_vat'];
  //$orderItemMeta['_line_tax_data'] = $orderItem[''];

  foreach ($orderItemMeta as $key => $field) {
    $itemMetaParam['item_id'] = $orderItem['item_id'];
    $itemMetaParam['meta_key']     = $key;
    $itemMetaParam['meta_value']   = $field;
    $queryEngine->createOrderItemMeta($itemMetaParam);
  }
}
/*
 * Order &ndash; July 15, 2016 @ 03:20 PM
 */
function formatOrderDesc($order) {
  $orderDate  = date_parse($order['local_order_date']);
  $month      = DateTime::createFromFormat('!m', $orderDate['month'])->format('F');
  $titleTime  = date('h:i A', strtotime($order['local_order_date']));
  $nameTime   = date('hi-A', strtotime($order['local_order_date']));


  $orderDesc['title'] = "Order &ndash; $month ".$orderDate['day'].", ".$orderDate['year']." @ $titleTime";
  $orderDesc['name'] = strtolower("order-$month-".$orderDate['day']."-".$orderDate['year']."-$nameTime");
  return $orderDesc;
}
/*
 *
 */
function formatPostStatus($order) {
  $postStatus = $order['status'];
  switch($postStatus) {
    case 'Success':
      $postStatus = "wc-completed";
      break;
    default:
      $postStatus = "wc-on-hold";
  }
  return $postStatus;
}
/*
 *
 */
function getMeta($metaArray, $metaKey, $metaValue) {
  foreach($metaArray as $item => $value) {
    if (isset($value[$metaKey])) {
      if ($value[$metaKey] == $metaValue) {

        return $value['meta_value'];
      }
    }
  }
}
