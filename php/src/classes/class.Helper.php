<?php

// Helper class with methods to process string transformations
class Helper {
    
    // Inspired by code found here: https://9to5answer.com/converting-words-to-numbers-in-php

    function strlen_sort($a, $b) {
        if (strlen($a) > strlen($b)) {
            return -1;
        } else if (strlen($a) < strlen($b)) {
            return 1;
        }
        return 0;
    }

    function getRelativeDateDelta($str) {
        $keys = array(
            'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4', 'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9',
            'ten' => '10', 'eleven' => '11', 'twelve' => '12', 'thirteen' => '13', 'fourteen' => '14', 'fifteen' => '15', 'sixteen' => '16', 'seventeen' => '17', 'eighteen' => '18', 'nineteen' => '19',
            'twenty' => '20', 'thirty' => '30', 'forty' => '40', 'fifty' => '50', 'sixty' => '60', 'seventy' => '70', 'eighty' => '80', 'ninety' => '90',
            'hundred' => '100', 'thousand' => '1000', 'million' => '1000000', 'billion' => '1000000000'
        );

        preg_match_all('#((?:^|and|,| |-)*(\b' . implode('\b|\b', array_keys($keys)) . '\b))+#i', $str, $tokens);
        //print_r($tokens); exit;
        $tokens = $tokens[0];
        usort($tokens, array($this, 'strlen_sort'));

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
        return $str;
    }
}