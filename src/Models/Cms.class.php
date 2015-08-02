<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 4/3/2015
 * Time: 5:06 PM
 */

require_once(__DIR__ . './../Models/Database.class.php');
require_once(__DIR__ . './../Models/List_functions.class.php');
require_once(__DIR__ . './../Models/SQL_Statements.class.php');
require_once(__DIR__ . './../Models/Utilities.class.php');

class Cms {

    private $db;
    private $utilities;
    private $lists;
    private $sql;

    /*
     * sets first item in section to 1, proceeds from there
     */
    public function reset_list_order($header_id){
        $start = 1;
        $items = $this->db->fetch_all_query("SELECT id FROM menu_items WHERE header_id=$header_id ORDER BY id");
        foreach($items as $val){
            $this->db->update('menu_items', $val['id'], Array('list_order' => $start++));
        }
    }
    /*
     * Reorders a section of the menu given a header id,
     * This method skips over list orders with the value of zero
     */
    public function reorder_section($header_id){
        $list = $this->db->fetch_all_query("SELECT id, list_order FROM menu_items WHERE header_id=$header_id");
        $start = 0;
        foreach($list as &$val){
            $order = $val['list_order'];
            if((int)$order !== 0){
                $val['list_order'] = ++$start;
            }
        }
        foreach($list as $val){
            $id = $val['id'];
            $order = $val['list_order'];
            $this->db->update('menu_items', $id, Array('list_order' => $order));
        }
    }
    /*
     * returns the header id of a given table and item id
     */
    private function get_header_id($table_name, $item_id){
        return $this->db->select_single_item('header_id', $table_name, Array('id' => $item_id));
    }
    /*
     * self explanetory...
     */
    private function response_object($group, $subDivide){
        return Array(
            'group' => $group,
            'subDivide' => $subDivide
        );
    }
    public function __construct(){
        $this->db = Database::get_instance();
        $this->lists = new List_functions();
        $this->sql = new SQL_Statements();
        $this->utilities = new Utilities();
    }
    /*
     * action methods
     */
    public function add_item($update_cols = Array()){
        /*insert into menu_items
            title, header_id, menu_type_id, list_order
        */
        /*
         *insert into
         */
    }
    public function item_edit($update_cols = Array()){
        $value = $update_cols;
        $item_id = $this->utilities->required($value['id'], "Item not found.");
        $item_title = $this->utilities->check($value['title']);
        $item_price = $this->utilities->check($value['price']);
        $desc_array = $this->utilities->check($value['descriptions']);
        $subprice_array = $this->utilities->check($value['subprices']);


        //update title
        if($item_title){
            $this->db->update('menu_items', $item_id, Array('title' => $item_title));
        }
        //update price

        if($item_price){
            $item_price = (string)$item_price;
            $this->db->query("UPDATE menu_prices SET price='$item_price' WHERE item_id=$item_id");
        }

        //update description id / text pairs
        if(is_array($desc_array)){
            foreach($desc_array as $desc){
                $id = $this->utilities->check($desc['id']);
                $text = $this->utilities->check($desc['text']);

                if(!$text){
                    $sql = "DELETE FROM menu_descriptions WHERE id = $id";
                    $this->db->query($sql);
                }else{
                    $vals = "";
                    if(!$id){
                        $vals = "(NULL, '$item_id', '$text')";

                    }else{
                        $vals = "('$id', '$item_id', '$text')";
                    }
                    $description_update = "INSERT INTO menu_descriptions
                            (id, item_id, description)
                            VALUES
                            $vals
                            ON DUPLICATE KEY UPDATE
                            description = '$text'
                            ";
                    $this->db->query($description_update);
                    if(!$id){
                        $id = $this->db->select_single_item('id', "menu_descriptions", Array('item_id' => $item_id, 'description' => $text));
                        $potential_text = NULL;
                        foreach($subprice_array as $key => $val){
                            if(!$val['desc_id']){
                                $potential_text = $val['text'];
                                unset($subprice_array[$key]);
                                break;
                            }
                        }

                        $this->db->query("INSERT INTO menu_subprices
                          (id, desc_id, sub_price)
                          VALUES
                          (NULL, $id, $potential_text)
                        ");
                    }
                }
            }
        }

        if(is_array($subprice_array)){
            //update description id / subprice pairs
            foreach($subprice_array as $subprice){
                $id = $this->utilities->check($subprice['desc_id']);
                $text = $this->utilities->check($subprice['text']);
                if(!$id || ! $text){
                    //do nothing
                }else {
                    $this->db->query("UPDATE menu_subprices SET sub_price='$text' WHERE desc_id=$id");
                }
            }
        }
    }
    /*
     * Sets an item's list_order to zero and reorders the list.
     *
     * This way, items mistakingly deleted can be recovered
     */
    public function remove_item($table_name, $item_id){
        $item = Array('list_order' => '0');

        $header_id = $this->get_header_id($table_name, $item_id);
        $this->db->update($table_name, $item_id, $item);
        $this->reorder_section($header_id);
    }
    public function undelete($table_name, $id){
        $header_id = $this->get_header_id($table_name, $id);

        $this->db->update($table_name, $id, Array('list_order' => 1));
        $this->reorder_section($header_id);
    }
    public function swap_items($table_name, $id_1, $id_2){
        $this->lists->swap_menu_items($table_name, $id_1, $id_2);
    }
    /*
     * utility methods
     */
    public function action($verb, &$request_vars){
        switch($verb){
            case('item_edit'):
                $this->utilities->required($request_vars['update_columns'], "Nothing to update", NULL, true);
                return $this->item_edit($request_vars['update_columns']);
                break;
            case('swap'):
                $table_name = $request_vars['table_name'];
                $item_1 = $request_vars['item_1'];
                $item_2 = $request_vars['item_2'];
                $this->swap_items($table_name, $item_1, $item_2);
                break;
            case('remove'):
                $table_name = $request_vars['table_name'];
                $item = $request_vars["item"];
                $this->remove_item($table_name, $item);
                break;
            case('undelete'):
                $table_name = $request_vars['table_name'];
                $id = $request_vars["id"];
                $this->undelete($table_name, $id);
                break;
            case('add_item'):
                $this->utilities->required($request_vars['update_columns'], "Nothing to update", NULL, true);

                break;
            default:
                throw new Exception('Unknown verb');
        }
    }
    public function get_cms_content($base, &$sub_type){
        $page_type = $base."_type";
        $content_division = $this->get_sub($page_type);
        $content_type = 1;
        if($content_division){
            $content_type = $this->get_sub_type($content_division, $page_type, $sub_type);
        }
        $query = ""; $group = "";
        switch(strtolower($base)){
            case('menu'):
                $query = $this->sql->get('menu', $content_type);
                $group = $this->db->fetch_all_query($query);
                $group = $this->lists->order_menu_cms($group);
                break;
        }

        return $this->response_object($group, $content_division);
    }
    public function get_sub($category){
        return $this->db->fetch_all_query("SELECT type FROM $category");
    }
    public function get_sub_type($sub,$category, &$type_filter){
        if(!isset($type_filter)){
            $type = $sub[0]['type'];
        }else{
            $type = $type_filter;
        }

        return $this->db->select_single_item('id', $category, Array('type' => $type));
    }

} 