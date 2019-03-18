<?php
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
} else {
    if (isset($_REQUEST['sheet_option'])) {
        if (count($_SESSION)) {
            $sheetName   = $_REQUEST['sheet_option'];
            $sheetReader = new xlsxReader($_SESSION['fileLocation']);
            $dataStarts  = strlen($_REQUEST['dataStarts']) ? $_REQUEST['dataStarts'] : 1;
            $output      = $sheetReader->matchColToRow($sheetName, $dataStarts);
            $response = ['success' => 'true',
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