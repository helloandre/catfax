<?php
require('CatFax.php');

$from = trim($_REQUEST['From'], '+');
$body = $_REQUEST['Body'];

CatFax::log($body, $from, false);

if (preg_match("/cancel|off|unsubscribe|fuck/", strtolower($body))) {
    $response = "You have unsubscribed from Cat Facts."; // not actually unsubscribed
} else {
    $response = CatFax::get_random('sms', $from);
}

CatFax::log($response, $from, true);

// make sure twilio interperets this correctly
header("Content-type: text/plain");
echo $response;