<?php
require 'vendor/autoload.php';

use Carbon\Carbon;

// TODO Add logfile with some debug info - limit filesize FIFO

// Helper class with a diverse amount of methods to do all kinds of stuff :) 
class Helper {

    static protected $environment;

    public static function getEnvironment() {
        /**
         * Get environment values
         * 
         * @param none
         * 
         * @return array with environment values
         * 
         */
        include("env.php");
        self::$environment = $env; // variable in env.php is called $env

        return self::$environment;
    }

    public static function doCurlGETRequest($parameters) {
        /**
         * Execute cURL GET request
         * 
         * @param string $parameters Contains all parameters to execute the GET request
         * 
         * @return string $result with the result of the cURL request
         * 
         */
        if ($parameters["api_uri"] != '') {
            $api_uri = $parameters["api_uri"];
        }

        // set parameters to an empty string, for some queries we don't need it   
        $parameters_http_query = '';

        if (array_key_exists("api_parameters", $parameters)) {
            if (is_array($parameters["api_parameters"])) {
                // Convert array to HTTP query so we can use it in the GET request
                $parameters_http_query = http_build_query($parameters["api_parameters"]);
            }
        }

        // Prepare cURL resource
        $curl_handle = curl_init(self::getEnvironment()["server"] . $api_uri);

        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_handle, CURLOPT_POST, false);

        // Set HTTP Header for GET request and include the access token for our Mastodon user.
        curl_setopt(
            $curl_handle,
            CURLOPT_HTTPHEADER,
            array(
                'Authorization: Bearer ' . self::getEnvironment()['access_token']
            )
        );

        curl_setopt($curl_handle, CURLOPT_URL, self::getEnvironment()['server'] . $api_uri . "?" . $parameters_http_query);

        // Send the request
        $result = curl_exec($curl_handle);

        // Close cURL session handle
        curl_close($curl_handle);

        // handle curl error
        if ($result === false) {
            throw new Exception('Curl error: ' . curl_error($curl_handle));
            // Send a private toot to @remindme with the error.
            $error_status_message = '@remindme@toot.re cURL error: ' . curl_error($curl_handle);
            $error_data = array(
                "status" => $error_status_message,
                "language" => 'en',
                "visibility" => 'direct' // Error messages do not need to be public.
            );

            $error_parameters = array(
                "status_parameters" => $error_data,
                "api_uri" => "/api/v1/statuses"
            );

            $status = new Status();
            $status->postFailure($error_parameters);
            // print_r('Curl error: ' . curl_error($curl_handle));
            // Close cURL session handle
            curl_close($curl_handle);
            return false;
        } else {
            return $result;
        }
    }

    public static function doCurlPOSTRequest($parameters) {
        /**
         * Execute cURL POST request
         * 
         * @param string $parameters Contains all parameters to execute the POST request
         * 
         *  @return string $result with the result of the cURL request
         * 
         */
        if ($parameters["api_uri"] != '') {
            $api_uri = $parameters["api_uri"];
        }

        // convert array to JSON
        $status_data_json = json_encode($parameters["status_parameters"]);
        //echo "<br/>";
        //var_dump($status_data_json);

        // Prepare new cURL resource
        $curl_handle = curl_init(self::getEnvironment()["server"] . $api_uri);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl_handle, CURLOPT_POST, true);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $status_data_json);

        // Set HTTP Header for POST request 
        curl_setopt(
            $curl_handle,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($status_data_json),
                'Authorization: Bearer ' . self::getEnvironment()['access_token']
            )
        );

        // Submit the POST request
        $result = curl_exec($curl_handle);

        // handle curl error
        if ($result === false) {
            throw new Exception('Curl error: ' . curl_error($curl_handle));
            // Send a private toot to @remindme with the error.
            $error_status_message = '@remindme@toot.re cURL error: ' . curl_error($curl_handle);
            $error_data = array(
                "status" => $error_status_message,
                "language" => 'en',
                "visibility" => 'direct' // Error messages do not need to be public.
            );

            $error_parameters = array(
                "status_parameters" => $error_data,
                "api_uri" => "/api/v1/statuses"
            );

            $status = new Status();
            $status->postFailure($error_parameters);
            // print_r('Curl error: ' . curl_error($crl));
            // Close cURL session handle
            curl_close($curl_handle);
            die();
        } else {
            // echo '<pre>' . print_r(json_decode($result), true) . '</pre>';
            // Close cURL session handle
            curl_close($curl_handle);
        }
        return $result;
    }

    public static function getLastSeenMentionId() {
        /**
         * Get last processed Mention ID from last_mention_id file
         * 
         * @param none
         * 
         *  @return string $mention_file_id with the mention id from the file
         * 
         */

        $mention_file_id = false;

        // Check if file exists
        if (file_exists(self::getEnvironment()['last_mention_file'])) {
            // echo "File exists: " . self::getEnvironment()['last_mention_file'] . "<br />";

            if ((filesize(self::getEnvironment()['last_mention_file'])) > 0) {
                $mention_file_id = file_get_contents(self::getEnvironment()['last_mention_file']);
                if ($mention_file_id) {
                    // echo "Found mention ID in file: " . $mention_file_id . "<br />";
                    return $mention_file_id;
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
    public static function setLastSeenMentionId($mentionid) {
        /**
         * Set last processed Mention ID in last_mention_id file
         * 
         * @param id $mentionid Contains mention id to store in the file
         * 
         *  @return string $result with the result of the cURL request
         * 
         */
        return file_put_contents(self::getEnvironment()['last_mention_file'], $mentionid);
    }

    public static function isReplyTo($mention) {

        return filter_var($mention->status->in_reply_to_id, FILTER_VALIDATE_INT);
    }

    // Inspired by code found here: https://9to5answer.com/converting-words-to-numbers-in-php

    function strlenSort($a, $b) {
        if (strlen($a) > strlen($b)) {
            return -1;
        } else if (strlen($a) < strlen($b)) {
            return 1;
        }
        return 0;
    }

    function startsWith($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }

    function getScheduledatDate($str, $mention) {
        /**
         * Try to get relative date delta from string $str
         * 
         * @param string $str Contains the content of the toot
         * @param object $mention Contains the mention object
         * 
         * @return array with not yet processed mention objects or false if no mentions found
         * 
         */

        $keys = array(
            'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4', 'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9',
            'ten' => '10', 'eleven' => '11', 'twelve' => '12', 'thirteen' => '13', 'fourteen' => '14', 'fifteen' => '15', 'sixteen' => '16', 'seventeen' => '17', 'eighteen' => '18', 'nineteen' => '19',
            'twenty' => '20', 'thirty' => '30', 'forty' => '40', 'fifty' => '50', 'sixty' => '60', 'seventy' => '70', 'eighty' => '80', 'ninety' => '90',
            'hundred' => '100', 'thousand' => '1000', 'million' => '1000000', 'billion' => '1000000000'
        );
        // echo "\$str = " . $str . "<br />";
        preg_match_all('#((?:^|and|,| |-)*(\b' . implode('\b|\b', array_keys($keys)) . '\b))+#i', $str, $tokens);
        // print_r($tokens);
        $tokens = $tokens[0];
        usort($tokens, array($this, 'strlenSort'));

        foreach ($tokens as $token) {
            $token = trim(strtolower($token));
            preg_match_all('#(?:(?:and|,| |-)*\b' . implode('\b|\b', array_keys($keys)) . '\b)+#', $token, $words);
            $words = $words[0];
            // print_r($words);
            $num = '0';
            $total = 0;
            foreach ($words as $word) {
                $word = trim($word);
                $val = $keys[$word];
                //echo "$val\n";
                if (bccomp($val, 100) == -1) {
                    $num = bcadd($num, $val);
                    continue;
                } else if (bccomp($val, 100) == 0) {
                    $num = bcmul($num, $val);
                    continue;
                }
                $num = bcmul($num, $val);
                $total = bcadd($total, $num);
                $num = '0';
            }
            $total = bcadd($total, $num);
            // echo "<br />$token : $total<br />";
            $str = preg_replace("#\b$token\b#i", number_format($total), $str);
        }

        // printf("<br />Right now is %s<br/>", Carbon::now()->toDateTimeString());
        // Strip HMTL 
        // Remove all mentions (@....)
        // Trim leading spaces
        // Remove some omit words
        $omit_words = array('in');

        $content = ltrim(preg_replace('/(\s+|^)@\S+/', '', strip_tags($str)));
        // echo "content after @ removal: $content<br />";
        // $content = "in 1 minute";
        $content_array = array_diff(explode(' ', $content), $omit_words);
        // echo "content after removing omit words: " . print_r($content_array, true) . "</pre>";
        $content = implode(' ', $content_array);

        // replace some words
        $content = str_replace('tomorrow', '1 day', $content);
        // echo $content;

        // Time to try to convert this to a datetime thingy
        try {
            $scheduledate = Carbon::parse($content);
            // printf("<br />\$scheduledate is %s<br/>", $scheduledate);
            // we have a $scheduledate, but we need the date used as the mention created_at as a base
            // get the original mention datetime
            $mentiondate = new Carbon($mention->created_at);
            // printf("<br />Mention created at %s<br/>", $mentiondate);

            // get the diff in seconds between now() and the parsed $scheduledate based upon the content of the toot
            $diffinseconds = Carbon::now()->diffInSeconds($scheduledate);
            // printf("Diff in seconds: %s<br/>", $diffinseconds);

            if ($diffinseconds > 300) {
                // 5 minute minimum as mentioned in API docs https://docs.joinmastodon.org/methods/statuses/#form-data-parameters
                // add seconds to mention date
                $scheduledate = $mentiondate->addSeconds($diffinseconds);
                // printf("%s from mentiondate is: %s", $content, $scheduledate);
            } else {
                $scheduledate = null;
                // set the last modified id in our file so it doesn't get processed again
                self::setLastSeenMentionId($mention->id);

                // Send a private toot to SENDER with the error.
                $status = new Status();

                $replied_to_toot_url = $status->getRepliedToTootURL(array(
                    "mention_status_id" => $mention->status->id,
                    "mention_status_in_reply_to_id" => $mention->status->in_reply_to_id,
                    "api_uri" => "/api/v1/statuses/%statusid%/context" // %statusid% will be replaced in the getRepliedToTootURL
                ));

                $in_reply_to_id = $mention->status->id;

                $visibility = 'private'; // Failures don't need to be public, so we set them to private
                $language = 'en';

                $reply_to_username = $mention->status->account->acct;
                $failure_status_message = "@" . $reply_to_username . " setting your reminder for " . $replied_to_toot_url . " failed. Please use a minumum of five minutes.\n\n Please try again with a different reminder text. For instance 'in ten minutes', 'in two years' or 'next week'. \n\rThanks for using #remindmebot!";

                $failure_data = array(
                    "status" => $failure_status_message,
                    "language" => $language,
                    "in_reply_to_id" => $in_reply_to_id,
                    "visibility" => $visibility
                );

                $failure_parameters = array(
                    "status_parameters" => $failure_data,
                    "api_uri" => "/api/v1/statuses"
                );

                $failure_status = $status->postFailure($failure_parameters);
            }
        } catch (\Throwable $exception) {
            // echo $exception->getMessage() . "<br />";
            $scheduledate = null;
            // set the last modified id in our file so it doesn't get processed again
            self::setLastSeenMentionId($mention->id);

            // Send a private toot to SENDER with the error.
            $status = new Status();

            $replied_to_toot_url = $status->getRepliedToTootURL(array(
                "mention_status_id" => $mention->status->id,
                "mention_status_in_reply_to_id" => $mention->status->in_reply_to_id,
                "api_uri" => "/api/v1/statuses/%statusid%/context" // %statusid% will be replaced in the getRepliedToTootURL
            ));

            $in_reply_to_id = $mention->status->id;

            $visibility = 'private'; // Failures don't need to be public, so we set them to private
            $language = 'en';

            $reply_to_username = $mention->status->account->acct;
            $failure_status_message = "@" . $reply_to_username . " somehow setting your reminder for " . $replied_to_toot_url . " failed. \nPlease try again with a different reminder text. For instance 'in ten minutes', 'in two years' or 'next week'. \n\rThanks for using #remindmebot!";

            $failure_data = array(
                "status" => $failure_status_message,
                "language" => $language,
                "in_reply_to_id" => $in_reply_to_id,
                "visibility" => $visibility
            );

            $failure_parameters = array(
                "status_parameters" => $failure_data,
                "api_uri" => "/api/v1/statuses"
            );

            $failure_status = $status->postFailure($failure_parameters);
        }

        return $scheduledate;
    }
}
