<?php

// include instance and API parameters
include_once('env.php');

// get current time
$datetime = new DateTime();
// for testing only
$now = date_format($datetime, 'Y-m-d G:i:s e');
// add ten minutes
$datetime->modify('+10 minutes');

// status message
$status_message = "This is a test, posted on " . $now . ", scheduled at " . date_format($datetime, 'Y-m-d G:i:s e');
/*
// status update array
$status_data = array(
    "status" => $status_message,
    "scheduled_at" => $datetime->format(DateTime::ATOM), // formatted to ISO8601
    "language" => "eng",
    "visibility" => "unlisted" // for testing set to unlisted, public should be used when ready
);

// convert array to JSON
$status_data_json = json_encode($status_data);

// Prepare new cURL resource
$crl = curl_init($server . $uri);
curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($crl, CURLINFO_HEADER_OUT, true);
curl_setopt($crl, CURLOPT_POST, true);
curl_setopt($crl, CURLOPT_POSTFIELDS, $status_data_json);

// Set HTTP Header for POST request 
curl_setopt(
    $crl,
    CURLOPT_HTTPHEADER,
    array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($status_data_json),
        'Authorization: Bearer ' . $access_token
    )
);

// Submit the POST request
$result = curl_exec($crl);

// handle curl error
if ($result === false) {
    throw new Exception('Curl error: ' . curl_error($crl));
    print_r('Curl error: ' . curl_error($crl));
    // Close cURL session handle
    curl_close($crl);
    die();
} else {
    echo '<pre>' . print_r(json_decode($result), true) . '</pre>';
    // Close cURL session handle
    curl_close($crl);
    die();
}
*/

$notifications = array(
    "exclude_types" => array("follow", "favourite", "reblog", "poll", "follow_request")
);

// convert array to JSON
$notifications_http_query = http_build_query($notifications);


// Prepare new cURL resource
$crl = curl_init($env['server'] . $env['uri_notifications']);
curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($crl, CURLINFO_HEADER_OUT, true);
curl_setopt($crl, CURLOPT_POST, false);

// Set HTTP Header for GET request 
curl_setopt(
    $crl,
    CURLOPT_HTTPHEADER,
    array(
        'Authorization: Bearer ' . $env['access_token']
    )
);

curl_setopt($crl, CURLOPT_URL, $env['server'] . $env['uri_notifications'] . "?" . http_build_query($notifications));

// Send the request
$result = curl_exec($crl);

// handle curl error
if ($result === false) {
    throw new Exception('Curl error: ' . curl_error($crl));
    print_r('Curl error: ' . curl_error($crl));
    // Close cURL session handle
    curl_close($crl);
    die();
} else {
    echo '<pre>' . print_r(json_decode($result), true) . '</pre>';
    // Close cURL session handle
    curl_close($crl);
    die();
}
