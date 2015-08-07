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
        //menu_items, menu_prices, menu_subprices, menu_descriptions
        $item = $update_cols;
        $id = $this->db->select_single_item('id', 'menu_items', Array('id' => $item['id']));
        $title = $this->utilities->check($update_cols['title']);
        $header_id = $this->utilities->check($update_cols['header']);
        $header_id = $this->db->select_single_item('id', 'menu_headers', Array('header'=> $header_id));
        $menu_type_id  = $this->utilities->check($update_cols['menu_type']);
        $menu_type_id = $this->db->select_single_item('id', 'menu_type', Array('type' => $menu_type_id));
        $list_order = $this->utilities->check($update_cols['list_order']);
        $price = $this->utilities->check($update_cols['price']);
        $desc_array = $this->utilities->check($update_cols['descriptions']);
        $vals = "(NULL, '$title', $header_id, $menu_type_id, $list_order)";
        if($id){
            $this->item_edit($update_cols);
        }else{
            $ins_menu_items = "INSERT INTO menu_items
              (id, title, header_id, menu_type_id, list_order)
              VALUES
              $vals
            ";
            $this->db->query($ins_menu_items);
            $item_id = $this->db->get_connection()->insert_id;
            $ins_price = "INSERT INTO menu_prices
              (id, item_id, price)
              VALUES
              (NULL, $item_id, '$price')
            ";
          
            $this->db->query($ins_price);
            if(is_array($desc_array)){
                echo "IS ARRAY";
                $this->update_descriptions($item_id, $desc_array);
            }
            $this->reorder_section($header_id);
        }
    }
    private function update_descriptions($item_id, $desc_array=Array()){
        foreach($desc_array as $desc){
            $id = $this->utilities->check($desc['id']);
            $text = $this->utilities->check($desc['text']);
            $price = $this->utilities->check($desc['price']);

            //delete item if no text is given
            if(!$text){
                $sql = "DELETE FROM menu_descriptions WHERE id = $id";
                $this->db->query($sql);
                $this->db->query("DELETE FROM menu_subprices WHERE desc_id=$id");
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
                if($price){
                    $price = (string)$price;
                    $sid = $this->db->select_single_item('id', 'menu_subprices', Array('desc_id'=> $id));
                    $vals = ($sid) ? "('$sid', '$id', '$price')" : "(NULL, '$id', '$price')";

                    $this->db->query("INSERT INTO menu_subprices
                        (id, desc_id, sub_price)
                        VALUES
                        $vals
                        ON DUPLICATE KEY UPDATE
                        sub_price = '$price'
                    ");

                }
            }

        }

    }
    private function update_title($title, $id){
        $this->db->update('menu_items', $id, Array('title' => $title));
    }
    private function update_price($price, $id){
        $price = (string)$price;
        $this->db->query("UPDATE menu_prices SET price='$price' WHERE item_id=$id");
    }
    public function item_edit($update_cols = Array()){
        $value = $update_cols;
        $item_id = $this->utilities->required($value['id'], 404);
        $item_title = $this->utilities->check($value['title']);
        $item_price = $this->utilities->check($value['price']);
        $desc_array = $this->utilities->check($value['descriptions']);
        $subprice_array = $this->utilities->check($value['subprices']);
        $response = false;


        //update title
        if($item_title){
            $this->update_title($item_title, $item_id);
        }
        //update price
        if($item_price){
           $this->update_price($item_price, $item_id);
        }
        if(is_array($desc_array)){
            $this->update_descriptions($item_id, $desc_array);
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