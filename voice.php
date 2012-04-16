<?php
require('CatFax.php');

$from = trim($_REQUEST['From'], '+');
$response = CatFax::get_random('voice', $from);

CatFax::log($response, $from, true, true);

// make sure twilio interperets this correctly
header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8" ?>  
<Response>
    <Pause length="2" />
    <Say voice="woman" language="en-gb">' . $response . '</Say>
</Response>';