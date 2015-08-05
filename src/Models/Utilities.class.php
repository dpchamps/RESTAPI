<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 4/3/2015
 * Time: 10:12 PM
 */

class Utilities {
    private $method;

    public function __construct($method = NULL){
        if($method){
            $this->method = $method;
        }
    }
    /*
        * A helper function to shorten checks of variables that are required for variuous endpoint functions
        */
    public function required(&$var, $exception, $value = NULL, $is_array = false)
    {
        $check = true;
        if (!isset($var)) {
            $check = false;
        }
        if ($value !== NULL && $var !== $value) {
            $check = false;
            throw new Exception("Expected Value: $value");
        }
        if ($is_array && !is_array($var)) {
            $check = false;
            throw new Exception('Expected type: Array');
        }
        if (!$check) {
            throw new Exception($exception);
        } else {
            return $var;
        }
    }
    /*
     * returns variable if it exists, false otherwise
     */
    public function check(&$var){
        $exists = NULL;
        if(isset($var)){
            $exists = $var;
        }

        return $exists;
    }

    /**
     * for passing key/values via url in the form of:
     * somedomain.com/api/get/item:name|item:id
     *
     * instead of throwing an error all invalid keyvalue pairs are ignored
     */
    public function parse_url_key_value($s)
    {
        $keyvalue_strings = explode('|', $s);
        $assoc_array = Array();
        foreach ($keyvalue_strings as $pair) {
            $pair = explode(':', $pair);
            if (sizeof($pair) > 1) {
                $assoc_array[$pair[0]] = $pair[1];
            }
        }

        return $assoc_array;
    }

    public function parse_url_array($s)
    {
        return explode('|', $s);
    }
    /**
     * @param string $type
     * @throws \Exception
     */
    public function is_method($method, $type = "")
    {
        $type = strtoupper($type);
        if ($method != $type) {
            throw new Exception('Method only accepts ' . $type . " requests.");
        }
    }

    public function allowed_methods($string){
        $methods = explode(" ", $string);
        //head and options are allowed by default
        $methods = array_merge($methods, Array('OPTIONS', 'HEAD'));
        //set header for allowed methods
        header("Allow: $string");
        if(!in_array($this->method, $methods)){
            throw new Exception(405);
        }
    }
} 