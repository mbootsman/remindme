<?php

/* Remind ME Mastodon Bot

This file is called by an external cronjob

When called it will:
- Get notifications of @reminme@toot.re account
- Loop through the notifications (Array of objects)
  and get notifications of type = mention
    - Check if the mention id is greater than the one stored in the lastmention_id file
        - Get the in_reply_to_id
        - Get the status -> content
        - Parse the content to see if we can determine a date in the future
            - Post status update to notifiy user of succesful reminder 
            - Post status update to remind the user of the replied to toot with scheduled_at
        - If no correct date possible post error message with link to usage docs
    - If no mentions found - end the process.
- Finished
*/

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

    function getLastSeeMentionId() {
        include("env.php");
        $this->environment = $env; // variable in env.php is called $env

        $mention_file_file_id = false;

        // Check if file exists
        if (file_exists($this->environment['last_mention_file'])) {
            echo "File exists<br />";

            $mention_file = fopen($this->environment['last_mention_file'], 'r');

            if ((filesize($this->environment['last_mention_file'])) > 0) {
                $mention_file_file_id = fread($mention_file, filesize($this->environment['last_mention_file']));
                if ($mention_file_file_id) {
                    echo "Found mention ID in file: " . $mention_file_file_id . "<br />";
                    return $mention_file_file_id;
                }
            } else {
                echo "File is empty<br />";
                return false;
            }
            // Close the file
            fclose($mention_file);
        } else {
            echo "File does not exist";
            return false;
        }
    }

    // Methods
    function getMentions() {
        /**
         * Get notifications from Mastodon,
         * 
         * @param array $status Contains all parameters needed to post a status update
         * 
         * @return array with not yet processed mention objects or false if no mentions found
         * 
         */

        $mentions = Array();
         // Add request parameters

        $since_id = $this->getLastSeeMentionId();

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
            // We should have an array of one ore more mention objects now.
            // Let's process these / this
            $result_array = json_decode($result);

            // first let's sort the array so we get the oldest object ID first
            usort($result_array, function ($a, $b) {
                return $a->id - $b->id;
            });

            // loop through all mentions and 
            foreach ($result_array as $object) {
                if ($object->type == 'mention') {
                    // check mention id in file
                    if ($mention_id_from_file = $this->getLastSeeMentionId()) {
                        echo "Mention ID found in file: " . $mention_id_from_file . "<br />";
                        if ($object->id > $mention_id_from_file ) {
                            // we have a new mention, add it to the mentions array
                            $mentions[] = $object;
                        }
                    } else {
                        echo "Nothing found in file<br />";
                    }
                    echo '<b>Object ID: </b>' . $object->id . '<br />';
                }
            }

            
            echo '<b>Returned Array:</b><pre>' . print_r($mentions, true) . '</pre>';
            echo '<pre>' . print_r(json_decode($result), true) . '</pre>';
            // Close cURL session handle
            curl_close($curl_handle);

            // Return Array of objects (mentions)
            return $mentions;
        }
    }
}

class Status {

    // include instance and API parameters
    function __construct() {
        global $env;
        include("env.php");

        $this->env = $env;
    }

    // Methods

    function scheduleReminder($status) {
        /**
         * Post reminder status on Mastodon
         * 
         * @param array $status     Contains all parameters needed to post a status update
         * 
         * @return status or scheduled_at
         * 
         */
    }

    function postConfirmation($status) {
        /**
         * Post confirmation that reminder is scheduled on Mastodon
         * 
         * @param array $status     Contains all parameters needed to post a status update
         * 
         * @return status 
         * 
         */
    }

    function postFailure($status) {
        /**
         * Post failure with information on how to use @remindme on Mastodon
         * 
         * @param array $status     Contains all parameters needed to post a status update
         * 
         * @return status 
         * 
         */
    }
}

// Let's start with getting the notifications
$notifications = new Notifications();
$mentions = $notifications->getMentions();

// If we have mentions we have not yet processed, process 'em
if ($mentions) {
}

