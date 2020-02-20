<?php
/*
 * CALL DIRECTLY WITH THE FOLLOWING URL OR USER THE MATCHING xlsx_parser_frontend AS INTERFACE
 * http://localhost/xlsx_importer/xlsx_parser_backend/src/index.php?debug=1&sheet_option=customer.csv
 *
  DELETE FROM wp_usermeta WHERE user_id != 1;
  DELETE FROM wp_users WHERE id != 1;
 */
session_start();
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
require '../vendor/autoload.php';
require 'classes/xlsxReader.php';
require 'classes/utilities.php';
require 'classes/queryEngine.php';
require '.env';

$utils = new Utilities();
if (count($_FILES)) {//upload file
    $response = $utils->getFileToProcess();
    if ($response['success'] == 'true') {

        $fileLocation             = $response['payload'];
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
            switch ($sheetName) {
              case 'CUSTOMERS':
                processCustomers($output, $queryEngine);
                break;
              case 'ORDERS':
                processOrders($output, $queryEngine);
                break;
              case 'ORDERITEMS':
                processOrderItems($output, $queryEngine);
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
function processOrders($output, $queryEngine) {
  foreach ($output['data'] as $order) {
    $customer = $queryEngine->getUser($order['customer_key']);
    print_r($customer);
  }
}
/*
 *
 */
function processOrderItem($output, $queryEngine) {

}
/*
 *
 */
function processCustomers($output, $queryEngine) {
  foreach ($output['data'] as $user) {
    $queryEngine->addUser($user);
    $userId = $queryEngine->getInsertId();
    $user['nickname'] = ucfirst($param['first_name']) . " " . ucfirst($param['last_name']);
    $user['description'] = "Automated import from Yumbi database";
    $user['wp_capabilities'] = serialize(["customer" => true]);
    $user['order_count'] = 0; //will have to get this....
    $user['wp_user_level'] = 0;
    foreach ($user as $key => $field) {
      $param['id'] = $userId;
      $param['key'] = $key;
      $param['value'] = $field;
      $queryEngine->addUserMeta($param);
    }
  }
}