<?php
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
        var_dump($status);
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