<?php
include("database.php");

$device = $_GET['device'];

if(!$db->isValid($device))
    die("invalid device");

$challenge = getRandomString();
$db->insertChallenge($device, $challenge);

echo $challenge;
