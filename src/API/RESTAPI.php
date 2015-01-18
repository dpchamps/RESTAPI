<?php
namespace REST;
require_once 'API.class.php';
require_once '/Models/User.class.php';

class REST_API extends \REST\API {
    protected $User;

    private function verify_user() {

        $User = new \Models\User();

        if (array_key_exists('token', $this->request) &&
            !$User->get('token', $this->request['token'])
        ) {
            throw new \Exception('Invalid User Token');
        }

        $this->User = $User;
    }
    public function __construct($request) {
        parent::__construct($request);

        //first check the endpoint method name, read is allowed without an api key or token
        if($this->method == 'read' || $this->method == 'test'){

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

} 