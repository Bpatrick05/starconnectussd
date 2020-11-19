
<?php

$input = $_POST['subscriberInput'];
$sessionId = $_POST['sessionId'];
$telephone = $_POST['msisdn'];
$newRequest = $_POST['newRequest'];
$actionFailed = false;


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

?>

