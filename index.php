
<?php

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);

require 'config.php';
require 'library/settings.php';

function __autoload($class) {
    require LIBS . $class . ".php";
}

$app = new Bootstrap();
/* ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

include_once('controllers/index.php');

$input = $_POST['subscriberInput'];
$sessionId = $_POST['sessionId'];
$telephone = $_POST['msisdn'];
$newRequest = $_POST['newRequest'];
$actionFailed = false;

// print("##################### data");

// print("##################### input: ");
// print($input);
// print("##################### sessionId: ");
// print($sessionId);
// print("##################### newRequest: ");
// print($newRequest);
// print("##################### telephone: ");
// print($telephone);

$response = new index();
print("##################### response: ");
print($response);
die();


//=== Default data
$lang = 'EN';
$MAP_CODE = null;
$display = "WELCOME TO TRACAR RWANDA";
$session_ussd_user_data = null;

// Default config
$arrayField['Freeflow'] = 'FB';


    
header('Content-type: UTF-8');
header('Freeflow: '.$arrayField['Freeflow']);
echo $display." ";
 */
?>

