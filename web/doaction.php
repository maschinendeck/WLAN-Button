<?php
include("secret.php");
include("database.php");
$device = $_GET['device'];

if(!$db->isValid($device))
    die("invalid device");

$data = $_GET['data'];
/*
challenge:aefaefaefaef
tele:vdd-3567;abe-def
*/
$auth = $_GET['auth'];
$dataLines = explode("\"", $data);
$dataValues = array();
foreach($dataLines as $line) {
    $cols = explode(":", $line);
    $dataValues[$cols[0]] = $cols[1];
}
$challenge = $dataValues['challenge'];
if($db->isValidChallenge($device, $challenge)){
    $db->removeChallenge($device, $challenge);
    if(hash_hmac("sha256", $data, $HMAC_KEY) != $auth)
        die("invalid response");

    foreach(decodeTelemetry($dataValues['tele']) as $key => $value) {
        $db->writeTelemetry($device, $key, $value);
    }

    $raumstatus = getRaumstatus();

    if(setRaumstatus(!$raumstatus))
        $raumstatus = !$raumstatus;

    if($raumstatus)
        echo "green";
    else
        echo "red";
} else {
    die("invalid challenge");
}
