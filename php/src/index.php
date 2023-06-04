<?php

/* Remind Me Mastodon Bot

This file is called by an external cronjob

When called it will:
- Get notifications of @remindme@toot.re account
- Loop through the notifications (Array of objects)
  and get notifications of type = mention
    - Check if the mention id is greater than the one stored in the lastmention_id file, or get just the last one if the file doesn't exist
        - Get the in_reply_to_id
        - Get the status -> content
        - Parse the content to see if we can determine a date in the future
        - Post status update to notify user of successful reminder 
        - Post status update to remind the user of the replied to toot with scheduled_at
- Finished

*/
// TODO Add logging - replace var_dumps and echos
// TODO add get method to test and add unique token for this
include "classLoader.php";
include "env.php";

// for testing purposes
// check get_token
$token = $_GET["token"];
if ($token) {
    // only use the numbers for safety
    $token = preg_replace("/[^0-9]/", "", $token);
    // check if token is valid
    if ($token == $env["get_token"]) {
        // valid token.
        // now get the string to check
        $string = $_GET["str"];
        // try to convert content to datetime delta
        $helper = new Helper();
        $dateandcontent = $helper->getScheduledatDate($string, null);
        echo "<pre>";
        var_dump($dateandcontent);
        echo "</pre";
    } else {
        echo "token is invalid";
    }
} else {
    // The main logic
    // Calls all methods to process notifications

    // Let's start with getting the notifications
    $notifications = new Notifications();
    $mentions = $notifications->getMentions();

    // If we have mentions we have not yet processed, process 'em
    if ($mentions) {
        Helper::ProcessMentions($mentions);
    }
}
