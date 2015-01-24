<?php
namespace REST;
require_once '/../config.php';
abstract class API {
    protected $method = '';
    
    protected $endpoint = '';
    
    protected $verb = '';
    
    protected $args = Array();
    
    protected $file = Null;
    
    public function __construct($request){
        //check the config file to see if CORS is enabled
        if(\config\ALLOW_CORS){
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: *");
        }
        header("Content-Type: application/json");

        $this->args = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);

        //let's cut the verb out for now

        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE'){
                $this->method = 'DELETE';
            } else if($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT'){
                $this->method = 'PUT';
            } else {
                throw new \Exception('Unexpected Header');
            }
        }

        switch($this->method) {
            case 'DELETE':
            case 'POST':
                $this->request = $this->_cleanInputs($_POST);
                break;
            case 'GET':
                $this->request = $this->_cleanInputs($_GET);
                break;
            case 'PUT';
                $this->request = $this->_cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    public function processAPI(){
        $reflection = new \ReflectionMethod($this, $this->endpoint);
        if((int)method_exists($this, $this->endpoint) > 0 &&
            !$reflection->isPrivate()){
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response("No Endpoint: $this->endpoint", 404);
    }

    private function _response($data, $status = 200){
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    private function _cleanInputs($data){
        $clean_input = Array();
        if( is_array($data) ){
            foreach($data as $key => $value){
                $clean_input[$key] = $this->_cleanInputs($value);
            }
        } else{
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _requestStatus($code){
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }
} 