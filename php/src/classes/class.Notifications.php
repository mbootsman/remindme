<?php

class Notifications {
    
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
        $api_parameters = array(
            "exclude_types" => array("follow", "favourite", "reblog", "poll", "follow_request"),

        );

        // Store last processed notification ID
        $since_id = Helper::getLastSeenMentionId();

        if ($since_id) {
            $api_parameters["since_id"] = $since_id;
        }

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
                if ($mention_id_from_file = Helper::getLastSeenMentionId()) {
                    // echo "Mention ID found in file: " . $mention_id_from_file . "<br />";
                    // Check if notification ID is larger than ID in file
                    if ($object->id > $mention_id_from_file) {
                        // we have a new mention, add it to the mentions array
                        $mentions[] = $object;
                    }
                } else {
                    // somehow the last mention id file does not exist.
                    // So let's only get the last mention for now
                    $mentions[] = $object;
                    break; //break the foreach loop
                }
            }
        }

        // echo '<b>Returned Array:</b><pre>' . print_r($mentions, true) . '</pre>';
        // echo '<pre>' . print_r($mentions) . '</pre>';

        // Return Array of objects (mentions)
        return $mentions;
    }
}
