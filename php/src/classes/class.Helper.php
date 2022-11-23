<?php
require 'vendor/autoload.php';

use Carbon\Carbon;

// TODO add docblocks to methods

// Helper class with methods to process string transformations
class Helper {

    static protected $environment;

    public static function getEnvironment() {
        include("env.php");
        self::$environment = $env; // variable in env.php is called $env

        return self::$environment;
    }

    public static function doCurlGETRequest($parameters) {
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
            // TODO Send a private toot with the error.
            // print_r('Curl error: ' . curl_error($curl_handle));
            // Close cURL session handle
            curl_close($curl_handle);
            // TODO do we leave the die() here?
            die();
        } else {
            return $result;
        }
    }

    public static function doCurlPOSTRequest($parameters) {
        if ($parameters["api_uri"] != '') {
            $api_uri = $parameters["api_uri"];
        }

        // convert array to JSON
        $status_data_json = json_encode($parameters["status_parameters"]);
        //echo "<br/>";
        //var_dump($status_data_json);

        // Prepare new cURL resource
        $crl = curl_init(self::getEnvironment()["server"] . $api_uri);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLINFO_HEADER_OUT, true);
        curl_setopt($crl, CURLOPT_POST, true);
        curl_setopt($crl, CURLOPT_POSTFIELDS, $status_data_json);

        // Set HTTP Header for POST request 
        curl_setopt(
            $crl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($status_data_json),
                'Authorization: Bearer ' . self::getEnvironment()['access_token']
            )
        );

        // Submit the POST request
        $result = curl_exec($crl);

        // handle curl error
        if ($result === false) {
            throw new Exception('Curl error: ' . curl_error($crl));
            print_r('Curl error: ' . curl_error($crl));
            // Close cURL session handle
            curl_close($crl);
            die();
        } else {
            // echo '<pre>' . print_r(json_decode($result), true) . '</pre>';
            // Close cURL session handle
            curl_close($crl);
        }
        return $result;
    }

    public static function setLastSeenMentionId($mentionid) {
        // Check if file exists
        if (file_exists(self::getEnvironment()['last_mention_file'])) {
            // echo "File exists for writing: " . self::getEnvironment()['last_mention_file'] . "<br />";

            file_put_contents(self::getEnvironment()['last_mention_file'], $mentionid);
        } else {
            // echo "File does not exist";
            return false;
        }
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
         * Try to get relative$scheduledate daelta from string $str
         * 
         * @param string $str Contains the content of the toot
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

        // print_r($content_array);
        $content = implode(' ', $content_array);

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

            // add seconds to mention date
            $scheduledate = $mentiondate->addSeconds($diffinseconds);
            // printf("%s from mentiondate is: %s", $content, $scheduledate);

        } catch (\Throwable $exception) {
            // echo $exception->getMessage() . "<br />";
            $scheduledate = null;
            // set the last modified id in our file so it doesn't get processed again
            // TODO send reply to sender that setting the reminder failed, and link to docs
            self::setLastSeenMentionId($mention->id);
        }

        return $scheduledate;
    }
}
