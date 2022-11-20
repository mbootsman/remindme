<?php

class Notifications {
    private $environment;

    // include instance and API parameters
    function __construct() {

        include("env.php");
        $this->environment = $env; // variable in env.php is called $env
    }

    function getEnvironment() {
        return $this->environment;
    }

    function getLastSeenMentionId() {
       $mention_file_file_id = false;

        // Check if file exists
        if (file_exists($this->environment['last_mention_file'])) {
            echo "File exists: " . $this->environment['last_mention_file'] . "<br />";

            if ((filesize($this->environment['last_mention_file'])) > 0) {
                $mention_file_file_id = file_get_contents($this->environment['last_mention_file']);
                if ($mention_file_file_id) {
                    echo "Found mention ID in file: " . $mention_file_file_id . "<br />";
                    return $mention_file_file_id;
                }
            } else {
                echo "File is empty<br />";
                return false;
            }
        } else {
            echo "File does not exist";
            return false;
        }
    }

    function setLastSeenMentionId() {

    }

    function getMentions() {
        /**
         * Get notifications from Mastodon, only non-processed, new notifications of type `mention`
         * 
         * @param array $status Contains all parameters needed to post a status update
         * 
         * @return array with not yet processed mention objects or false if no mentions found
         * 
         */

        $mentions = array();
        // Add request parameters

        // Store last processed notification ID
        $since_id = $this->getLastSeenMentionId();

        $parameters = array(
            "exclude_types" => array("follow", "favourite", "reblog", "poll", "follow_request"),
            "since_id" => $since_id
        );
        
        // Convert exclude_types array to HTTP query so we can use it in the GET request
        $parameters_http_query = http_build_query($parameters);

        $environment = $this->getEnvironment();

        // Prepare cURL resource
        $curl_handle = curl_init($environment['server'] . $environment['uri_notifications']);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_handle, CURLOPT_POST, false);

        // Set HTTP Header for GET request and include the access token for our Mastodon user.
        curl_setopt(
            $curl_handle,
            CURLOPT_HTTPHEADER,
            array(
                'Authorization: Bearer ' . $environment['access_token']
            )
        );

        curl_setopt($curl_handle, CURLOPT_URL, $environment['server'] . $environment['uri_notifications'] . "?" . $parameters_http_query);

        // Send the request
        $result = curl_exec($curl_handle);

        // handle curl error
        if ($result === false) {
            throw new Exception('Curl error: ' . curl_error($curl_handle));
            // TODO Send a private toot with the error.
            // print_r('Curl error: ' . curl_error($curl_handle));
            // Close cURL session handle
            curl_close($curl_handle);
            // TODO remove the die
            die();
        } else {
            // We should have an array of one or more mention objects now.
            // Let's process these / this
            $result_array = json_decode($result);

            // first let's sort the array so we get the oldest object ID first
            usort($result_array, function ($a, $b) {
                return $a->id - $b->id;
            });

            // loop through all mentions
            foreach ($result_array as $object) {
                if ($object->type == 'mention') {
                    // check mention id in file
                    if ($mention_id_from_file = $this->getLastSeenMentionId()) {
                        // echo "Mention ID found in file: " . $mention_id_from_file . "<br />";
                        // Check if notification ID is larger than ID in file
                        if ($object->id > $mention_id_from_file) {
                            // we have a new mention, add it to the mentions array
                            $mentions[] = $object;
                        }
                    }
                    echo '<b>Object ID: </b>' . $object->id . '<br />';
                }
            }


            // echo '<b>Returned Array:</b><pre>' . print_r($mentions, true) . '</pre>';
            // echo '<pre>' . print_r(json_decode($result), true) . '</pre>';
            // Close cURL session handle
            curl_close($curl_handle);

            // Return Array of objects (mentions)
            return $mentions;
        }
    }
}
