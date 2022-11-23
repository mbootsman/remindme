<?php

class Notifications {
    private $enivronment;

    function getLastSeenMentionId() {
        $mention_file_file_id = false;
        $environment = Helper::getEnvironment();

        // Check if file exists
        if (file_exists($environment['last_mention_file'])) {
            // echo "File exists: " . $environment['last_mention_file'] . "<br />";

            if ((filesize($environment['last_mention_file'])) > 0) {
                $mention_file_file_id = file_get_contents($environment['last_mention_file']);
                if ($mention_file_file_id) {
                    // echo "Found mention ID in file: " . $mention_file_file_id . "<br />";
                    return $mention_file_file_id;
                }
            } else {
                // echo "File is empty<br />";
                return false;
            }
        } else {
            // echo "File does not exist";
            return false;
        }
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

        // Build request parameters
        // Store last processed notification ID
        $since_id = $this->getLastSeenMentionId();

        $api_parameters = array(
            "exclude_types" => array("follow", "favourite", "reblog", "poll", "follow_request"),
            "since_id" => $since_id
        );

        $parameters = array(
            "api_parameters" => $api_parameters,
            "api_uri" => "/api/v1/notifications"
        );

        $result = Helper::doCurlGETRequest($parameters);

        // We have an array of one or more mention objects now.
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
                // echo '<b>Mention ID: </b>' . $object->id . '<br />';
            }
        }


        // echo '<b>Returned Array:</b><pre>' . print_r($mentions, true) . '</pre>';
        // echo '<pre>' . print_r(json_decode($result), true) . '</pre>';

        // Return Array of objects (mentions)
        return $mentions;
    }
}
