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

    public function __construct($request) {
        parent::__construct($request);
        //first check the endpoint method name, read is allowed without an api key or token
        if($this->endpoint == 'read' || $this->endpoint == 'login'|| $this->endpoint == 'test' || $this->endpoint == 'read' ){
        } else {
            $this->verify_user();
        }
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

} 