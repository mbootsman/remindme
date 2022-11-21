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
// TODO remove all echo's / printfs / var_dumps/ when ready
include('classLoader.php');

// phpinfo();

// Let's start with getting the notifications
$notifications = new Notifications();
$mentions = $notifications->getMentions();

// If we have mentions we have not yet processed, process 'em
if ($mentions) {
    // loop through mentions
    foreach ($mentions as $mention) {
        // read the content
        // echo "<pre>" . print_r($mention) . "</pre>";
        $content = $mention->status->content;
        // try to convert content to datetime delta
        $helper = new Helper();
        $delta = $helper->getRelativeDateDelta($content, $mention->id); // this is in UTC format
        
        if ($delta) {
            // We have a correct datetime formatted delta. So it's time to do two things:
            // 1. build status update to set a reminder (schedulted post)
            // 2. build status update to confirm that a remidner has been set (reply)
            $status = new Status();
            echo "<pre>$delta</pre>";
            $reminder_data = array();
            $reminder = $status->scheduleReminder($reminder_data);
            printf("Converted \" %s \" to a scheduled date/time = %s", $content, $delta);
        }
    }
}
