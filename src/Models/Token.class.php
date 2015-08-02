<?php



/**
 * Class Token
 * generates a random token of given length
 *
 * inspired from http://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
 */
class Token {
    private $_token = "";
    private function crypto_rand_secure($min, $max) {

        $range = $max - $min;
        $rnd = NULL;
        if ($range < 0) return $min; // not so random...
        if(function_exists('openssl_random_pseudo_bytes')){
            $log = log($range, 2);

            $bytes = (int) ($log / 8) + 1; // length in bytes
            $bits = (int) $log + 1; // length in bits
            $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
            do {
                $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
                $rnd = $rnd & $filter; // discard irrelevant bits
            } while ($rnd >= $range);
        }else{
            $rnd = mt_rand($min, $max);
        }

        return $min + $rnd;
    }

    private function get_token($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        for($i=0;$i<$length;$i++){
            $token .= $codeAlphabet[$this->crypto_rand_secure(0,strlen($codeAlphabet))];
        }

        $this->_token = $token;
    }

    public function __construct($length=32){
        $this->get_token($length);
        return $this->_token;
    }

    public function __toString(){
        return (string)$this->_token;
    }
} 