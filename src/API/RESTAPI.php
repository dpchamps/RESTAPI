<?php

require_once 'API.class.php';
require_once __DIR__.'./../Models/User.class.php';
require_once __DIR__.'./../Models/Database.class.php';
require_once __DIR__.'./../Models/SQL_Statements.class.php';
require_once __DIR__.'./../Models/Cms.class.php';
require_once __DIR__.'./../Models/Utilities.class.php';
//require endpoint models
require_once __DIR__.'./../Endpoint_Models/Pages.class.php';

class REST_API extends API
{
    protected $User;
    private $db;
    private $lists;
    private $sql;
    private $cms;
    private $util;
    private $pages;

    private function check_auth_session(){
        $user = $_SERVER['PHP_AUTH_USER'];
        $pw = $_SERVER['PHP_AUTH_PW'];
        return $this->User->valid_token($pw, $user);
    }
    private function authorize_user(){
        $protected_methods = Array(
            'PUT', 'PUSH', 'PATCH', 'DELETE', 'POST'
        );
        if(in_array($this->method, $protected_methods)){
            if( !$this->check_auth_session() ){
                throw new Exception(401);
            }
        }
    }
    private function initialize(){
        $this->db = Database::get_instance();
        //models
        $this->lists = new List_functions();
        $this->sql = new SQL_Statements();
        $this->cms = new Cms();
        $this->util = new Utilities($this->method);
        $this->User = new User();
        //endpoint models

        $this->pages = new Pages($this->args, $this->method);
    }
    public function __construct($request)
    {
        parent::__construct($request);
        //Database instance
        $this->initialize();
        //if the user is doing something other than get, make sure they're logged in.
        $this->authorize_user();
    }
    /**
     * Endpoint methods
     */

    protected function API()
    {
        return Array(
            'version' => '',
            'site' => '',
            'links' => Array(
                'pages' => Array(
                    'ref' => SERVER_ROOT.'/pages',
                    'description' => "Retrieve page data."
                )
            )
        );
    }
    protected function login()
    {
        $this->util->allowed_methods('GET');
        $user = $_SERVER['PHP_AUTH_USER'];
        $pw = $_SERVER['PHP_AUTH_PW'];
        if(!$user || !$pw){
            throw new Exception(401);
        }
        $this->User = new User();
        $this->User->login($user, $pw);
        return $this->User->token;
    }

    protected function check_login()
    {
        $response = false;
        $this->util->is_method($this->method, "POST");
        if (
            !array_key_exists('username', $this->request) ||
            !array_key_exists('token', $this->request)
        ) {
            return false;
        } else {
            $this->User = new User();
            if ($this->User->valid_token($this->request['token'], $this->request['username'])) {
                $response = $this->User->get(Array('username', 'token'));

            }
        }

        return $response;
    }

    protected function logout()
    {
        $this->util->allowed_methods('GET');
        if($this->check_auth_session()){
            $this->User->logout();
        }else{
            throw new Exception(400);
        }
    }

    /*
     * url structure:
     *
     *  /pages
     *      returns array of pages that exist
     *  _____________________
     *  /pages/menus
     *      returns an array of menus that exist
     * -or-
     *  /pages/merch
     *      returns an relation of links / items
     *  _____________________
     *  /pages/menus/food?item=1
     *      returns item with the id of 1
     * -or-
     *  /pages/merch?item=1
     *      returns an item with the id of 1
     * -or-
     * /pages/menus?item=1
     *      returns the menu with the id of 1
     */
    protected function pages(){
        //deal with options first
        if($this->method === 'OPTIONS'){
            return null;
        }
        $page = $this->util->check($this->args[0]);
        $response = null;
        $item = $this->util->check($this->request['item']);

        switch($page){
            case(false):
                $response =  $this->db->fetch_all_query( $this->sql->get('available_pages') );
                break;
            case('menus'):
                $response = $this->pages->get_menu($item);
                break;
            case('merch'):
                $response = $this->pages->get_merch($item);
                break;
            case('press'):
                $response = $this->pages->get_press($item);
                break;
            default:
                throw new Exception(404);
                break;
        }

        return $response;
    }


    /*
     * url structure:
     *
     * /items
     *      returns an object of item types, i.e. {'type' : 'menu', type: 'merch' ...}
     *
     *
     */
    protected function get_content()
    {
        $table = "";
        $cols = "";
        $vals = NULL;
        if (isset($this->args[0])) {
            $table = $this->util->parse_url_array($this->args[0]);
        } else {
            throw new Exception(412);
        }
        if (isset($this->args[1])) {
            $cols = $this->util->parse_url_array($this->args[1]);
        }
        if (isset($this->args[2])) {
            $vals = $this->util->parse_url_key_value($this->args[2]);
        }

        $result = $this->db->select($cols, $table, $vals);
        if ($result === false) {
            throw new Exception(404);
        } else {
            return $this->db->fetch_all($result);
        }

    }



    protected function get_page()
    {
        $page = $this->util->required($this->args[0], "Please specify page data.");
        $page_id = $this->db->select_single_item('id', 'page_data', Array('title'=>$page));

        if (!$page_id) {
            throw new Exception(404);
        }
        $page_query = $this->sql->get('page', $page_id);
        $page_data = $this->db->fetch_all_query($page_query);

        if (!$page_data) {
            throw new Exception(404);
        } else {
            return $page_data[0];
        }
    }

    protected function cms()
    {
        $this->util->is_method($this->method, 'POST');
        $action = $this->util->required($this->args[0], "No content specified.");
        $response = NULL;
        switch ($action) {
            case('page_data'):
                $response = $this->get_content();
                break;
            case('page_content'):
                $base = $this->util->required($this->args[1], "No content specified");
                $response = $this->cms->get_cms_content($base, $this->args[2]);
                break;
            case('action'):
                $verb = $this->util->required($this->args[1], "Must specify verb");
                return $this->cms->action($verb, $this->request);
                break;
        }

        return $response;
    }
}