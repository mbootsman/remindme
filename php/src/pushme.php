<?php
// TODO Log everything to a file for every received notification/every call to this endpoint
// Done - Create a subscription

// include instance and API parameters
include_once "env.php";

function check_subscription() {
    // Check if we have subscription on the Mastodon instance
    // Docs: https://docs.joinmastodon.org/methods/push/#get
    // GET /api/v1/push/subscription HTTP/1.1
    // TODO check/store subscription ID in a file to prevent unnecessary traffic

    global $env;

    // Prepare new cURL resource
    $crl = curl_init($env["server"] . $env["uri_subscription"]);

    // Set Authorization Header for request
    curl_setopt($crl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $env["access_token"]
    ]);

    // Set URL for doing the CURL request
    curl_setopt($crl, CURLOPT_URL, $env["server"] . $env["uri_subscription"]);

    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_POST, false); // do a GET


    // Send the request
    $result = curl_exec($crl);

    // handle curl error
    if ($result === false) {
        throw new Exception("Curl error: " . curl_error($crl));
        add_log(__FUNCTION__ . " - Curl error: " . curl_error($crl));
        // Close cURL session handle
        curl_close($crl);
        return $result;
    } else {
        add_log(__FUNCTION__ . " - Curl result: " . print_r(json_decode($result), true));
        // Close cURL session handle
        curl_close($crl);
        return $result;
    }
}

function create_subscription() {
    // Creates a subscription at the Mastodon server
    // Docs for this: https://docs.joinmastodon.org/methods/push/#create
    global $env;
    // Prepare new cURL resource
    $crl = curl_init($env["server"] . $env["uri_subscription"]);

    // Set Authorization Header for request
    curl_setopt($crl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $env["access_token"]
    ]);

    // Set URL for doing the CURL request
    curl_setopt($crl, CURLOPT_URL, $env["server"] . $env["uri_subscription"]);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_POST, true); // do a POST 
    $post_data = array(
        "subscription" => array(
            "endpoint" => $env["endpoint"], // Endpoint URL of PHP script
            "keys" => array(
                "p256dh" => base64_encode($env["public_key"]), // Base64 encoded string of a public key from a ECDH keypair using 
                "auth" => $env['subscription_auth']  // Auth secret. Base64 encoded string of 16 bytes of random data. 
            )
        ),
        "data" => array(
            "alerts" => array(
                "mention" => true // Receive notifications for mentions
            )
        )
    );
    curl_setopt($crl, CURLOPT_POSTFIELDS, http_build_query($post_data));


    // Send the request
    $result = curl_exec($crl);

    // handle curl error
    if ($result === false) {
        throw new Exception("Curl error: " . curl_error($crl));
        add_log(__FUNCTION__ . " - Curl error: " . curl_error($crl));
        // Close cURL session handle
        curl_close($crl);
        die();
    } else {
        add_log(__FUNCTION__ . " - " . print_r(json_decode($result), true));
        // Close cURL session handle
        curl_close($crl);
        return $result;
    }
}

function delete_subscription() {
    // Deletes an active subscription at the Mastodon server
    // Docs for this: https://docs.joinmastodon.org/methods/push/#delete
    global $env;
    // Prepare new cURL resource
    $crl = curl_init($env["server"] . $env["uri_subscription"]);

    // Set Authorization Header for request
    curl_setopt($crl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $env["access_token"]
    ]);

    // Set URL for doing the CURL request
    curl_setopt($crl, CURLOPT_URL, $env["server"] . $env["uri_subscription"]);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_CUSTOMREQUEST, 'DELETE'); // do a DELETE 

    // Send the request
    $result = curl_exec($crl);

    // handle curl error
    if ($result === false) {
        throw new Exception("Curl error: " . curl_error($crl));
        add_log(__FUNCTION__ . " - Curl error: " . curl_error($crl));
        // Close cURL session handle
        curl_close($crl);
        die();
    } else {
        add_log(__FUNCTION__ . " - " . print_r(json_decode($result), true));
        // Close cURL session handle
        curl_close($crl);
        return $result;
    }
}

function get_request_method() {
    $method = '';
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            $method = "POST";
            break;
        case "GET":
            $method = "GET";
            break;
    }
    return $method;
}

function add_log($message) {
    $logfile = "./log_" . date('Y-m-d') . ".log";
    // Function to return logfile prepend with date/time of now and brackets
    error_log("\n[" . date('Y-m-d H:i:s') . "] - " . $message, 3, $logfile);
}

function var_dump_ret($mixed = null) {
    ob_start();
    var_dump($mixed);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

// TODO the flow: 
// Check if we have received a notification (GET or POST) 
// if we have received a notification
//    parse it with the remindme logic
// if not, check if we have a subscription server_key stored
//    if we have it stored
//       do nothing
//    if we don't have a server_key stored
//       create a subscription and store the server key

$request_method = get_request_method();
add_log("----------------------------------------------------");
add_log("main - File was called. Method was: " . $request_method);

if ($request_method == "GET") {
    // We are called with a GET request. Let's do some checking
    // Do we have a delete subscription request?
    if (isset($_GET["delete_subscription"])) {
        if ($_GET["delete_subscription"] == "1") {
            add_log("Let's delete the subscription");
            $result_object = delete_subscription();
            add_log(print_r($result_object, true));
            add_log("Subscription deleted...");
            echo "Subscription deleted...<br />";
        }
    }

    // check for subscription
    $have_subscription = check_subscription();

    // decode JSON
    $result_object = json_decode($have_subscription);
    if (is_object($result_object)) {
        // check for error  
        if (property_exists($result_object, "error")) {
            if ($result_object->error == "Not Found"); {
                // No subscription found
                // Create a subscription
                // On success, write file so we don't have to do the check/creation of subscription again
                add_log("main - No subscription found, let's make one");
                $subscription = create_subscription();
                add_log("main - Subscription created:");
                add_log(print_r($subscription, true));
            }
        } else {
            add_log("main - Subscription found.");
        }
    }
    echo "We wants it, we needs it. Must have the precious.";
    die();
} elseif ($request_method == "POST") {
    add_log("main - Something was POSTED to me.");
    //add_log("main - REQUEST: " . var_dump_ret($_REQUEST));
    //add_log("main - POST: " . var_dump_ret($_POST));
    // get all headers and push them to the log
    $request_headers = getallheaders();
    add_log("main - Request headers: " . print_r($request_headers, true));

    // Get the raw POST data
    // store the notification
    add_log("main - getting the posted request");
    $encrypted_notification = file_get_contents("php://input");
    // add_log("file_get_content: ". $input);

    // Check if there's data
    if ($encrypted_notification !== false) {
        // Save everything so we can debug decryption locally with another script
        file_put_contents('headers.txt', serialize($request_headers));
        file_put_contents('notification.txt', $encrypted_notification);

        $server_key = str_replace("p256ecdsa=", "", strstr($request_headers['Crypto-Key'], 'p256ecdsa='));
        $iv = str_replace("salt=", "", strstr($request_headers['Encryption'], 'salt='));
        // Decrypt it
        // Get all the keys
        // Passphrase = ?
        // IV - Initialization Vector = ?
        // Tag = ?
        $decrypted_notification = openssl_decrypt(
            $encrypted_notification,
            'aes-128-gcm', // source: https://developer.chrome.com/blog/web-push-encryption/#encryption
            $server_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag = null
        );

        if ($decrypted_notification != false) {
            echo "Decrypted🕺: " . $decrypted;
        } else {
            echo "Decryption FAILED ❌";
        }
    } else {
        // Handle the case where there's no input data
        add_log("main - No data received.");
    }
}


// Do something when a notification is received at the endpoint
// Maybe just send an email for starters, or add the notification in a file