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

function getRaumstatus() {
    $stateJSON = json_decode(file_get_contents("http://state.maschinendeck.org/spaceapi.php"));
    if(isset($stateJSON->state->open)) {
        return $stateJSON->state->open;
    } else {
        return false;
    }
}

function setRaumstatus($newState) {
    global $APIUSER, $APIPASSWORD;
    $url = 'http://state.maschinendeck.org/update.php';
    $data = array("open" => $newState);

    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Basic ".base64_encode($APIUSER.':'.$APIPASSWORD)."\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if($state = json_decode($result))
        if($state->status == 200)
            return true;

    return false;
}

