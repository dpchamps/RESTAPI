<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 3/24/2015
 * Time: 3:44 PM
 */

class SQL_Statements {
    private function menu_query($menu_type_id){
        return "select
                menu_items.list_order, menu_items.id, menu_headers.header as header, menu_items.title, menu_descriptions.description, menu_descriptions.id as desc_id, menu_prices.price as price, menu_subprices.sub_price as subprice, menu_subprices.desc_id as subprice_id
                from menu_items
                left outer
                	join menu_headers
                    on menu_items.header_id = menu_headers.id
                left outer
                    join menu_descriptions
                    on menu_descriptions.item_id = menu_items.id
                left outer
                    join menu_prices
                    on menu_prices.item_id = menu_items.id
                left outer
                    join menu_subprices
                    on menu_subprices.desc_id = menu_descriptions.id
                where
                    menu_items.menu_type_id = $menu_type_id
                order by
                    menu_items.header_id, menu_items.list_order
                 ";
    }
    private function press_text_query($item_id){
        return "
                    select

                        press_headers.content as header,
                        press_descriptions.content as description
                    from press_items
                    left outer join press_headers
                        on press_headers.item_id = press_items.id
                    left outer join press_descriptions
                        on press_descriptions.item_id = press_items.id
                    where press_items.id = $item_id";
    }
    private function press_image_query($item_id){
        return "
                    select
                        press_images.path
                    from press_items
                    left outer join press_images
                        on press_images.item_id = press_items.id
                    where press_items.id = $item_id
                ";
    }
    private function press_query(){
        return "
                SELECT
                     press_items.link,press_items.id, press_type.type as type, press_items.title
                FROM press_items
                LEFT OUTER
                    JOIN press_type
                    ON press_items.press_type_id = press_type.id
            ";
    }
    private function merch_text_query($item_id){
        return "
                    select
                        merch_headers.content as header,
                        merch_descriptions.content as description
                    from merch_items
                    left outer join merch_headers
                        on merch_headers.item_id = merch_items.id
                    left outer join merch_descriptions
                        on merch_descriptions.item_id = merch_items.id
                    where merch_items.id = $item_id
                    ";
    }
    private function merch_image_query($item_id){
        return "
                    select merch_images.path
                    from merch_images
                    where merch_images.item_id = $item_id";
    }
    private function is_empty($s){
        $b = false;
        if($s === ""){
            $b = true;
        }
        return $b;
    }
    private function page_query($page_id){
        return "select
                title,
                template,
                image_path as imagePath,
                default_image as defaultImage,
                default_background_image as defaultBackgroundImage,
                default_header as default_header,
                default_description as defaultDescription
            from page_data
            where id = $page_id";
    }

    public function get($name, $id ="", $sub_type=""){
        $query = "";
        switch($name){
            case('menu'):
                $query = $this->menu_query($id);
                break;
            case('press'):
                if($this->is_empty($sub_type)){
                    $query = $this->press_query();
                }else{
                    $sub_type = "press_".$sub_type."_query";
                    $query =call_user_func(Array($this, $sub_type), $id);
                }
                break;
            case('merch'):
                if($this->is_empty($sub_type)){
                    $query = "";
                }else{
                    $sub_type = "merch_".$sub_type."_query";
                    $query = call_user_func(Array($this, $sub_type), $id);
                }
                break;
            case('page'):
                $query = $this->page_query($id);
                break;

        }

        return $query;
    }
} 