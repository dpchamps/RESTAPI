<?php
namespace REST;
require_once 'API.class.php';
require_once '/Models/User.class.php';

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

    public function __construct($request) {
        parent::__construct($request);

        //first check the endpoint method name, read is allowed without an api key or token
        if($this->endpoint == 'read' || $this->endpoint == 'login' ){
        } else {
            $this->verify_user();
        }
    }
    /**
     * Endpoint methods
     */

    protected function create(){

    }

    protected function read(){
        //connect to db
        $content = Null;
        $item = Null;
        $result = '';

        if(isset($this->args[0])){
            $content = $this->args[0];
        }
        if(isset($this->args[1])){
            $item = $this->args[1];
        }

        if($content != Null){
            $result = ''; //read_from_database(content, item);
        } else {
            throw new \Exception("Content not specified");
        }

        return $result;
    }
    protected function exampleUpdate(){
        $this->is_method('POST');
        $some_dummy_write = Array();
        if(is_array($this->request['update'])){
            foreach($this->request['update'] as $key => $value){
                $some_dummy_write[$key] = $value;
            }
        }

        return Array(
            'response' => $some_dummy_write
        );
    }
    protected function update(){

    }

    protected function delete(){

    }
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