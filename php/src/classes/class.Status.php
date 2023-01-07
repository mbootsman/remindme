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
         * array (
         *    status_parameters array (
         *       status
         *       scheduled_at
         *       language
         *       in_reply_to_id
         *       visibility
         *    ap_uri
         *    mention
         * ))
         * 
         * 
         * @return status or scheduled_at or false in case of failure
         * 
         */

        $reminder_result = Helper::doCurlPOSTRequest($parameters);

        // Check if we got JSON back
        if (is_string($reminder_result) && is_array(json_decode($reminder_result, true))) {
            //successfully posted a reminder. Let's write the mention id to our last_mention_id file
            $write_to_file = Helper::setLastSeenMentionId($parameters["mention"]->id);
            if (!$write_to_file) {
                // we could not write to the file.
                echo "File write failed<br/>";
                // TODO send direct message to @remindme to report error or write to log
            };

            $replied_to_toot_url = $this->getRepliedToTootURL(array(
                "mention_status_id" => $parameters["mention"]->status->id,
                "mention_status_in_reply_to_id" => $parameters["mention"]->status->in_reply_to_id,
                "api_uri" => "/api/v1/statuses/%statusid%/context" // %statusid% will be replaced in the getRepliedToTootURL
            ));

            $status_parameters = $parameters["status_parameters"];
            $scheduledatetime = date("F jS, o @ G:i e", strtotime($status_parameters["scheduled_at"]));

            $in_reply_to_id = $status_parameters["in_reply_to_id"];
            $visibility = 'public'; // For promotion purposes, set to public 
            $language = $status_parameters["language"];
            $reply_to_username = $parameters["mention"]->status->account->acct;
            $confirmation_status_message = "@" . $reply_to_username . " your reminder for " . $replied_to_toot_url . " is set at " . $scheduledatetime . "!\n\r⏰ Thanks for using #remindmebot!";
            
            $confirmation_data = array(
                "status" => $confirmation_status_message,
                "language" => $language,
                "in_reply_to_id" => $in_reply_to_id,
                "visibility" => $visibility
            );

            $confirmation_parameters = array(
                "status_parameters" => $confirmation_data,
                "api_uri" => "/api/v1/statuses",
                "mention" => $parameters["mention"]
            );

            $confirmation_result = $this->postConfirmation($confirmation_parameters);

            if (is_string($confirmation_result) && is_array(json_decode($confirmation_result, true))) {
                // all went good, let's return the scheduled status
                return $reminder_result;
            }
        }

        return false;
    }

    function postConfirmation($parameters) {
        /**
         * Post confirmation that reminder is scheduled on Mastodon
         * 
         * @param array $parameters Contains all parameters needed to post a status update
         * 
         * @return status 
         * 
         */

        return Helper::doCurlPOSTRequest($parameters);
    }

    function postFailure($parameters) {
        /**
         * Post failure with information on how to use @remindme on Mastodon
         * 
         * @param array $status     Contains all parameters needed to post a status update
         * 
         * @return status 
         * 
         */

        return Helper::doCurlPOSTRequest($parameters);
    }
}
