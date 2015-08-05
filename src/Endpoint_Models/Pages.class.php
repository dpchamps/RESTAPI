<?php

require_once __DIR__.'./../Models/User.class.php';
require_once __DIR__.'./../Models/Database.class.php';
require_once __DIR__.'./../Models/SQL_Statements.class.php';
require_once __DIR__.'./../Models/Cms.class.php';
require_once __DIR__.'./../Models/Utilities.class.php';
class Pages {

    private $db;
    private $lists;
    private $sql;
    private $util;
    private $args;
    private $method;
    private $item;

    /*
     * case 'menu'
     */
    private function menu_item_edit(){
        $id = $this->util->check($this->item['id']);

        if(!$id){
            throw new Exception(400);
        }
    }
    private function menu_item(){
        $menu = $this->menu();
        if($this->method === 'GET'){
            $item = $menu[(int)($this->item)-1];
            if($item){
                return $item;
            }else{
                throw new Exception(404);
            }
        }else{
            return $this->menu_item_edit();
        }

    }
    private function available_menus(){
        $item = $this->item;
        $sql =  $this->sql->get('available_menus');
        $available_menus = $this->db->fetch_all_query($sql);
        if($item){
            if($this->util->check($available_menus[$item-1])){
                return $available_menus[$item-1];
            }else{
                throw new Exception(404);
            }

        }else{
            return $available_menus;
        }
    }

    private function menu(){
        $type = $this->db->select('id', 'menu_type', Array(
            'type' => $this->args[0]
        ));
        if ($type !== false) {
            $type = $type->fetch_assoc();
            $type = $type['id'];
            $sql_query = $this->sql->get('menu', $type);
            $raw_array = $this->db->fetch_all_query($sql_query);
            $raw_array = $this->lists->build_menu($raw_array);
;        }else{
            throw new Exception(404);
        }
        return $raw_array;
        //return $this->lists->order_menu_array($raw_array);

    }
    public function get_menu($item)
    {
        array_shift($this->args);
        $this->item = $item;
        if($item){
            $this->util->allowed_methods('GET PUT POST PATCH');
            return $this->menu_item();
        }
        $this->util->allowed_methods('GET');
        if (!isset($this->args[0])) {
            return $this->available_menus($item);
        }

        return $this->menu();

    }
    /*
    * case 'merch'
    */
    private function merch_edit(){
        $id = $this->util->check($item['id']);
        if(!$id){
            throw new Exception(400);
        }
    }
    private function merch_item(){
        if($this->method === 'GET') {
            $item_id = $this->db->select_single_item('id', 'merch_items', Array('title' => $this->item));
            if ($item_id) {
                $text_query = $this->sql->get('merch', $item_id, 'text');
                $image_query = $this->sql->get('merch', $item_id, 'image');
                $response = $this->db->fetch_all_query($text_query);
                $response['images'] = $this->db->fetch_all_query($image_query);
                return $this->lists->build_merch($response);
            }else{
                throw new Exception(404);
            }
        }else{
            return $this->merch_edit();
        }
    }
    public function get_merch($item)
    {
        $this->item = $item;
        $response = NULL;
        //select all merch titles from db
        $types = Database::get_instance()->select('title', 'merch_items', Array());
        $types = Database::get_instance()->fetch_all($types);

        if ($this->item) {
            $this->util->allowed_methods('GET PUT POST PATCH');
            return $this->merch_item();
        } else {
            $this->util->allowed_methods('GET');
            $response = $types;
        }
        return $response;
    }


    public function __construct($args, $method){
        $this->args = $args;
        $this->method = $method;

        $this->db = Database::get_instance();
        $this->lists = new List_functions();
        $this->sql = new SQL_Statements();
        $this->util = new Utilities($this->method);
    }




    public function get_press($item)
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
}