<?php
//enable buffering to avoid chunked encoding
//because basic HTTP-Libs tend to not implement it

function sendLengthHeader($string) {
    header("Content-Length: ".strlen($string));
    return $string;
}
ob_start(sendLengthHeader);
function getClient() {
    return $_SERVER['REMOTE_ADDR'].'-'.$_SERVER['REMOTE_PORT'].'-'.$_SERVER['SERVER_ADDR'].'-'.$_SERVER['SERVER_PORT'];
}

function getRandomString() {
    return bin2hex(file_get_contents ("/dev/urandom", false, null, 0, 32));
}

function decodeTelemetry($telemetryBlob) {
    $result = array();
    foreach(explode(";", $telemetryBlob) as $row) {
        $columns = explode("-", $row);
        $result[$columns[0]] = $columns[1];
    }
    return $result;
}

