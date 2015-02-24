<?php
namespace REST;
require_once 'API.class.php';
require_once '/Models/User.class.php';
require_once '/Models/Database.class.php';


class REST_API extends \REST\API {
    protected $User;

    private function verify_user() {

        $User = new \Models\User();

        /**
         * In order for it to be a valid request, the user must have passed a unique token
         * and username pair to the server with the request
         */
        if (array_key_exists('token', $this->request) &&
            array_key_exists('username', $this->request) &&
            !$User->valid_token($this->request['token'], $this->request['username'])
        ) {
            throw new \Exception('Invalid User Token');
        }elseif (!array_key_exists('token', $this->request) ||
                 !array_key_exists('username', $this->request)){
            throw new \Exception('Please log in to complete this action');
        }

        $this->User = $User;
    }

    /**
     * @param string $type
     * @throws \Exception
     */
    private function is_method($type = ""){
        $type = strtoupper($type);
        if ( $this->method != $type ){
            throw new \Exception('Method only accepts ' . $type . " requests.");
        }
    }

    /**
     * @param $s
     *
     * instead of throwing an error all invalid keyvalue pairs are ignored
     */
    private function parse_url_key_value($s){
        $keyvalue_strings = explode('|', $s);
        $assoc_array = Array();
        foreach($keyvalue_strings as $pair){
            $pair = explode(':', $pair);
            if(sizeof($pair) > 1){
                $assoc_array[$pair[0]] = $pair[1];
            }
        }

        return $assoc_array;
    }
    private function parse_url_array($s){
        return explode('|', $s);
    }

    public function __construct($request) {
        parent::__construct($request);
        //first check the endpoint method name, read is allowed without an api key or token
        if($this->endpoint == 'get_content' || $this->endpoint == 'login'|| $this->endpoint == 'test' || $this->endpoint == 'read' ){
        } else {
            $this->verify_user();
        }
    }
    /*
     * A general gdet method for querying a database and getting content back.
     *
     *  Write an endpoint method that makes use of this
     *
     */
    private function get($table, $cols, $vals){
        //$this->is_method('GET');

        return \Models\Database::get_instance()->select($cols, $table, $vals);
    }
    /**
     * Endpoint methods
     */
    
    protected function login(){
        $this->is_method("POST");
        if(
            !array_key_exists('username', $this->request) ||
            !array_key_exists('password', $this->request)
        ){
            throw new \Exception('Please provide username and password');
        }
        $this->User = new \Models\User();
        $this->User->login($this->request['username'], $this->request['password']);

        return $this->User->get(Array('username', 'token'));

    }
    protected function logout(){
        $this->is_method('POST');
        $this->User->logout($this->request['username'], $this->request['token']);

        return $this->User->get(Array('username', 'token'));
    }

    protected function get_content(){
        $table = "";
        $cols  = "";
        $vals  = NULL;
        if(isset($this->args[0])){
            $table = $this->args[0];
        }else{
            throw new \Exception('No content specified');
        }
        if(isset($this->args[1])){
            $cols = $this->parse_url_array($this->args[1]);
        }
        if(isset($this->args[2])){
            $vals = $this->parse_url_key_value($this->args[2]);
        }

        //return $this->get($table, $cols, $vals)->fetch_all(MYSQLI_ASSOC);
        $result = $this->get($table, $cols, $vals);
        if($result === false){
            throw new \Exception("Content Not Found");
        }else{
            return $result->fetch_all(MYSQLI_ASSOC);
        }

    }


} 