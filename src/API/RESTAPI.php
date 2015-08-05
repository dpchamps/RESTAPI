<?php

require_once 'API.class.php';
require_once __DIR__.'./../Models/User.class.php';
require_once __DIR__.'./../Models/Database.class.php';
require_once __DIR__.'./../Models/SQL_Statements.class.php';
require_once __DIR__.'./../Models/Cms.class.php';
require_once __DIR__.'./../Models/Utilities.class.php';

class REST_API extends API
{
    protected $User;
    private $db;
    private $lists;
    private $sql;
    private $cms;
    private $util;

    private function verify_user()
    {
        $User = new User();
        /**
         * In order for it to be a valid request, the user must have passed a unique token
         * and username pair to the server with the request
         */
        if (array_key_exists('token', $this->request) &&
            array_key_exists('username', $this->request) &&
            !$User->valid_token($this->request['token'], $this->request['username'])
        ) {
            throw new Exception(401);
        } elseif (!array_key_exists('token', $this->request) ||
            !array_key_exists('username', $this->request)
        ) {
            throw new Exception(401);
        }

        $this->User = $User;
    }
    private function allowed_methods($string){
        $methods = explode(" ", $string);
        //head and options are allowed by default
        $methods = array_merge($methods, Array('OPTIONS', 'HEAD'));
        //set header for allowed methods
        header("Allow: $string");
        if(!in_array($this->method, $methods)){
            throw new Exception(405);
        }
    }
    public function __construct($request)
    {
        parent::__construct($request);
        $this->db = Database::get_instance();
        $this->lists = new List_functions();
        $this->sql = new SQL_Statements();
        $this->cms = new Cms();
        $this->util = new Utilities();
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
        $this->util->is_method($this->method, "POST");
        if (
            !array_key_exists('username', $this->request) ||
            !array_key_exists('password', $this->request)
        ) {
            throw new Exception(401);
        }
        $this->User = new User();
        $this->User->login($this->request['username'], $this->request['password']);

        return $this->User->get(Array('username', 'token'));

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
        $this->util->is_method($this->method, 'POST');
        //$this->User = new \Models\User();
        $this->User->logout($this->request['username'], $this->request['token']);

        return $this->User->get(Array('username', 'token'));
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
        $this->allowed_methods('GET');
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
                $response = $this->get_menu((int)$item);
                break;
            case('merch'):
                $response = $this->get_merch($item);
                break;
            case('press'):
                $response = $this->get_press($item);
                break;
            default:
                throw new Exception(404);
                break;
        }

        return $response;
    }
    protected function get_menu($item)
    {
        array_shift($this->args);

        if (!isset($this->args[0])) {

            $sql =  $this->sql->get('available_menus');
            $available_menus = $this->db->fetch_all_query($sql);
            if($item){
                if($this->utils->check($available_menus[$item-1])){
                    return $available_menus[$item-1];
                }else{
                    throw new Exception(404);
                }
            }else{
                return $available_menus;
            }
        }
        $menu_type = $this->db->select('id', 'menu_type', Array(
            'type' => $this->args[0]
        ));
        if ($menu_type !== false) {
            $menu_type = $menu_type->fetch_assoc();
            $menu_type = $menu_type['id'];
        }

        $sql_query = $this->sql->get('menu', $menu_type);
        $raw_array = $this->db->fetch_all_query($sql_query);
        $raw_array = $this->lists->build_menu($raw_array);
        if( $item && $this->util->check( $raw_array[$item-1] ) ){
            return $raw_array[$item-1];
        }else{
        }
        return $raw_array;
        //return $this->lists->order_menu_array($raw_array);
    }
    public function get_merch($item)
    {
        $response = NULL;
        //select all merch titles from db
        $types = Database::get_instance()->select('title', 'merch_items', Array());
        $types = Database::get_instance()->fetch_all($types);

        if ($this->util->check($item)) {
            $item_id = $this->db->select_single_item('id', 'merch_items', Array('title' => $item));
            if ($item_id) {
                $text_query = $this->sql->get('merch', $item_id, 'text');
                $image_query = $this->sql->get('merch', $item_id, 'image');
                $response = $this->db->fetch_all_query($text_query);
                $response['images'] = $this->db->fetch_all_query($image_query);
                $response = $this->lists->build_merch($response);
            }
        } else {
            $response = $types;
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

    protected function get_press($item)
    {
        $response = NULL;
        array_shift($this->args);
        if( !$this->util->check( $this->args[0] ) ){
            $response = $this->db->fetch_all_query("SELECT type FROM press_type");
        }else{
            $press_type = $this->args[0];
            if ( isset($item) ){
                $clause = $item;
                $item_id = $this->db->select_single_item('id', 'press_items', Array('title'=>$clause));
                if ($item_id) {
                    $text_query = $this->sql->get('press', $item_id, 'text');
                    $image_query = $this->sql->get('press', $item_id, 'image');
                    $response = $this->db->fetch_all_query($text_query);
                    $response['images'] = $this->db->fetch_all_query($image_query);
                }
            } else {
                $query = $this->sql->get('press');
                $data = $this->db->fetch_all_query($query);
                $response = $this->lists->order_press($data);
                if ($this->util->check($response[$press_type]) ){
                    $response = $response[$press_type]
;               }else{
                    throw new Exception(404);
                }
            }
        }

        return $response;
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