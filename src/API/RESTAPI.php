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
    protected $Unverified_Endpoints = Array(
        "login",
        'check_login',
        "get_content",
        "get_menu",
        "get_press",
        "get_page",
        "get_merch",
        "test"
    );
    public function test(){
        $sql_query = $this->sql->get('menu', 1);

        $raw_array = $this->db->fetch_all_query($sql_query);

        return $this->lists->order_menu_cms($raw_array);
    }
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
            throw new Exception('Invalid User Token');
        } elseif (!array_key_exists('token', $this->request) ||
            !array_key_exists('username', $this->request)
        ) {
            throw new Exception('Please log in to complete this action');
        }

        $this->User = $User;
    }

    public function __construct($request)
    {
        parent::__construct($request);
        $this->db = Database::get_instance();
        $this->lists = new List_functions();
        $this->sql = new SQL_Statements();
        $this->cms = new Cms();
        $this->util = new Utilities();
        //first check the endpoint method name, read is allowed without an api key or token
        if (in_array($this->endpoint, $this->Unverified_Endpoints)) {
            //can add security measures here to ensure someone isn't spamming the system
        } else {
            $this->verify_user();
        }
    }
    /**
     * Endpoint methods
     */

    protected function login()
    {
        $this->util->is_method($this->method, "POST");
        if (
            !array_key_exists('username', $this->request) ||
            !array_key_exists('password', $this->request)
        ) {
            throw new Exception('Please provide username and password');
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

    protected function get_content()
    {
        $table = "";
        $cols = "";
        $vals = NULL;
        if (isset($this->args[0])) {
            $table = $this->util->parse_url_array($this->args[0]);
        } else {
            throw new Exception('No content specified');
        }
        if (isset($this->args[1])) {
            $cols = $this->util->parse_url_array($this->args[1]);
        }
        if (isset($this->args[2])) {
            $vals = $this->util->parse_url_key_value($this->args[2]);
        }

        $result = $this->db->select($cols, $table, $vals);
        if ($result === false) {
            throw new Exception("Content Not Found");
        } else {
            return $this->db->fetch_all($result);
        }

    }

    protected function get_menu()
    {

        if (!isset($this->args[0])) {
            throw new Exception('Please specify content');
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

        return $this->lists->order_menu_array($raw_array);
    }

    protected function get_page()
    {
        $page = $this->util->required($this->args[0], "Please specify page data.");
        $page_id = $this->db->select_single_item('id', 'page_data', Array('title'=>$page));

        if (!$page_id) {
            throw new Exception("Page Not Found");
        }
        $page_query = $this->sql->get('page', $page_id);
        $page_data = $this->db->fetch_all_query($page_query);

        if (!$page_data) {
            throw new Exception('Page Not Found');
        } else {
            return $page_data[0];
        }
    }

    protected function get_press()
    {
        $response = NULL;
        if (isset($this->args[0])) {
            $clause = $this->args[0];
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
        }
        return $response;
    }

    function get_merch()
    {
        $response = NULL;

        if (isset($this->args[0])) {
            $clause = $this->args[0];

            $item_id = $this->db->select_single_item('id', 'merch_items', Array('title' => $clause));
            if ($item_id) {
                $text_query = $this->sql->get('merch', $item_id, 'text');
                $image_query = $this->sql->get('merch', $item_id, 'image');
                $response = $this->db->fetch_all_query($text_query);
                $response['images'] = $this->db->fetch_all_query($image_query);
            }
        } else {
            $response = Database::get_instance()->select('title', 'merch_items', Array());
            $response = Database::get_instance()->fetch_all($response);
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