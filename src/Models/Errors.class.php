<?php
/**
 * Created by PhpStorm.
 * User: dave
 * Date: 8/3/15
 * Time: 8:51 PM
 */
require_once('error_status_codes.php');

class Errors {
    private function _errorStatus($code){
        $status = unserialize(ERR_STATUS);
        return ($status[$code]) ? $status[$code] : $status[500];
    }
    public function __construct(){

    }
    public function lookup($e){
        $status = $this->_errorStatus($e);
        header("HTTP/1.1 " . $e . " " . $status);
        return $status;
    }

}