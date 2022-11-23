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

// The main logic
// Calls all methods to process notifications

// Let's start with getting the notifications
$notifications = new Notifications();
$mentions = $notifications->getMentions();

// If we have mentions we have not yet processed, process 'em
if ($mentions) {
    // Loop through mentions
    foreach ($mentions as $mention) {
        // Read the content
        // echo "<pre>" . print_r($mention) . "</pre>";
        $content = $mention->status->content;
        // try to convert content to datetime delta
        $helper = new Helper();
        $schedule_at = $helper->getScheduledatDate($content, $mention); // this is in UTC format

        if ($schedule_at) {
            // We have a correct datetime formatted delta. So it's time to do two things:
            // 1. build status update to set a reminder (scheduled post)
            // 2. build status update to confirm that a remidner has been set (reply)
            $status = new Status();

            // printf("<br />Converted \" %s \" to a scheduled date/time = %s", $content, $schedule_at);

            $replied_to_toot_url = $status->getRepliedToTootURL(array(
                "mention_status_id" => $mention->status->id,
                "mention_status_in_reply_to_id" => $mention->status->in_reply_to_id,
                "api_uri" => "/api/v1/statuses/%statusid%/context" // %statusid% will be replacef in the getRepliedToTootURL
            ));

            $in_reply_to_id = $mention->status->id;
            $visibility = 'unlisted'; // TODO for testing set to unlisted, public should be used when ready
            $language = 'en';
            $reply_to_username = $mention->status->account->username;
            $reply_to_username_url = $mention->status->account->url;
            $reminder_status_message = "@" . $reply_to_username . " here is your reminder for " . $replied_to_toot_url . ". Thanks for using #remindmebot!";

            $reminder_data = array(
                "status" => $reminder_status_message,
                "scheduled_at" => $schedule_at->format(DateTime::ATOM), // formatted to ISO8601
                "language" => $language,
                "in_reply_to_id" => $in_reply_to_id,
                "visibility" => $visibility                
            );

            $reminder_parameters = array(
                "status_parameters" => $reminder_data,
                "api_uri" => "/api/v1/statuses",
                "mention_id" => $mention->id
            );
            // $reminder = $status->scheduleReminder($reminder_parameters);
            
        }
    }
}
