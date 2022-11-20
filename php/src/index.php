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

include('autoload.php');

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
    // loop through mentions
    foreach ($mentions as $mention) {
        // read the content
        // echo "<pre>" . print_r($mention) . "</pre>";
        echo $mention->status->content;
    }

}

// test string transformation
$helper = new Helper();
$string = "in four weeks";
echo $helper->getRelativeDateDelta($string);