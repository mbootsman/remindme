<?php
class Status {

    // include instance and API parameters
    function __construct() {
        global $env;
        include("env.php");

        $this->env = $env;
    }

    // Methods

    function getRepliedToTootURL($parameters) {
        /**
         * Get replied to toot
         * 
         * @param array parameters array
         * 
         * @return url of toot user wants to be reminded of of null if not found
         * 
         */

        $url = null;

        // check if status id is an integer
        if (filter_var($parameters["mention_status_id"], FILTER_VALIDATE_INT)) {
            // build api uri
            $api_uri = str_replace('%statusid%', $parameters["mention_status_id"], $parameters["api_uri"]);
            $parameters = array(
                "api_uri" => $api_uri
            );
            //  get result

            $result = Helper::doCurlGETRequest($parameters);
            // echo "getting context<br />";
            // echo '<pre>' . print_r(json_decode($result), true) . '</pre>';
            $result_array = json_decode($result);
            if (is_object($ancestor = $result_array->ancestors[0])) {
                $url = $ancestor->url;
            }
        }

        return $url;
    }

    function scheduleReminder($parameters) {
        /**
         * Post reminder status on Mastodon
         * 
         * @param array $parameters Contains all parameters needed to post a status update
         * 
         * @return status or scheduled_at
         * 
         */
        // var_dump($parameters);
        $result = Helper::doCurlPOSTRequest($parameters);
        var_dump($result);
        // Check if we got JSON back
        if (is_string($result) && is_array(json_decode($result, true))) {
            //succesfully posted a reminder. Let's write the mention id to our last_mention_id file
            Helper::setLastSeenMentionId($parameters["mention_id"]);
        }
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

        // set mention id in mentionfile
        var_dump($status);
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
