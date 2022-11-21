<?php
require 'vendor/autoload.php';

use Carbon\Carbon;

// Helper class with methods to process string transformations
class Helper {

    static protected $environment;

    public static function getEnvironment() {
        include("env.php");
        self::$environment = $env; // variable in env.php is called $env

        return self::$environment;
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

    function getRelativeDateDelta($str, $mention_id) {
        /**
         * Try to get relative date daelta from string $str
         * 
         * @param string $str Contains the content of the toot
         * 
         * @return array with not yet processed mention objects or false if no mentions found
         * 
         */

        echo "<br/ >getRelativeDateDelta()<br/>";
        $keys = array(
            'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4', 'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9',
            'ten' => '10', 'eleven' => '11', 'twelve' => '12', 'thirteen' => '13', 'fourteen' => '14', 'fifteen' => '15', 'sixteen' => '16', 'seventeen' => '17', 'eighteen' => '18', 'nineteen' => '19',
            'twenty' => '20', 'thirty' => '30', 'forty' => '40', 'fifty' => '50', 'sixty' => '60', 'seventy' => '70', 'eighty' => '80', 'ninety' => '90',
            'hundred' => '100', 'thousand' => '1000', 'million' => '1000000', 'billion' => '1000000000'
        );

        preg_match_all('#((?:^|and|,| |-)*(\b' . implode('\b|\b', array_keys($keys)) . '\b))+#i', $str, $tokens);
        //print_r($tokens); exit;
        $tokens = $tokens[0];
        usort($tokens, array($this, 'strlenSort'));

        foreach ($tokens as $token) {
            $token = trim(strtolower($token));
            preg_match_all('#(?:(?:and|,| |-)*\b' . implode('\b|\b', array_keys($keys)) . '\b)+#', $token, $words);
            $words = $words[0];
            //print_r($words);
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
            echo "<br />$token : $total<br />";
            $str = preg_replace("#\b$token\b#i", number_format($total), $str);
        }
        // printf("<br />Right now is %s<br/>", Carbon::now()->toDateTimeString());
        // Strip HMTL 
        // Remove all mentions (@....)
        // Trim leading spaces
        // Remove some omit words
        $omit_words = array('in');
        
        $content = ltrim(preg_replace('/(\s+|^)@\S+/', '', strip_tags($str)));
        $content_array = array_values(array_filter(str_ireplace($omit_words, '', explode(" ", $content))));
        print_r($content_array);
        $content = implode(' ', $content_array);


        // Time to try to convert this to a datetime thingy
        try {
            $date = Carbon::parse($content);
        } catch (\Throwable $exception) {
            //echo $exception->getMessage(). "<br />";
            $date = null;
            // set the last modified id in our file so it doesn't get processde again
            // TODO send reply to sender that setting the reminder failed, and link to docs
            $notifications = new Notifications;
            $notifications->setLastSeenMentionId($mention_id);
        }
        return $date;
    }
}
