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

    private function is_post(){

        if( $this->method != 'POST' ){

            throw new \Exception('Method only accepts POST requests. Sent type: ' . $this->method);
        }

        return true;
    }
    public function __construct($request) {
        parent::__construct($request);

        //first check the endpoint method name, read is allowed without an api key or token
        if($this->endpoint == 'read' || $this->endpoint == 'test' || $this->endpoint == 'login' || $this->endpoint == 'logout'){
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

    protected function update(){

    }

    protected function delete(){

    }

    protected function test(){

        return $this->request;
    }

    protected function login(){
        //if( $this->method == 'POST'){
            if(
                !array_key_exists('username', $this->request) ||
                !array_key_exists('password', $this->request)
            ){
                throw new \Exception('Please provide username and password');
            }
            $user = new \Models\User();
            try {
                $user->login($this->request['username'], $this->request['password']);
                return $user->get(Array('token', 'username'));
            } catch (\Exception $e) {
                throw $e;
            }
        //}else{
            //throw new \Exception('Method only accepts POST requests');
        //}

    }
    protected function logout(){

        try{
            $this->is_post();
            $this->verify_user();
            $this->User->logout($this->request['username'], $this->request['token']);
            return $this->User->get(Array('username', 'token'));
        }catch(\Exception $e ){
            throw $e;
        }
    }

} 