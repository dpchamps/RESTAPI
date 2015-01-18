<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 1/16/2015
 * Time: 2:35 PM
 */

namespace Models;
require_once('/../config.php');

/**
 * Class Database
 * @package Models
 *
 * Insures that only one connection to a database occurs at a time
 */
class Database {
    private $_connection;
    private static $_instance;

    /**
     * Get an instance of the Database class
     * @return Database
     */
    public static function get_instance() {
        if(!self::$_instance){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct(){
        $this->_connection = new \mysqli(\config\DB_HOSTNAME, \config\DB_USERNAME, \config\DB_PASSWORD, \config\DB_DATABASE);
        if(mysqli_connect_error()){
            throw new \Exception('Failed to connect to MySql: ' . mysqli_connect_error(), E_USER_ERROR);
        }
    }

    public function update($table, $id, $keyvalue = Array()){
        if( !is_array($keyvalue) ){
            throw new \Exception('Expected type array for keyvalue, got: ' .getType($keyvalue));
        }
        $update = "UPDATE $table";
        $set = "SET";
        $where = "WHERE " . $table.".id= $id";
        $index = sizeof($keyvalue);
        foreach($keyvalue as $key => $value){
            $set .= " $key='$value'";
            $index--;
            if($index > 0){
                $set .= ", ";
            }
        }
        $mysql_statement = $update . " " . $set . " " . $where;
        $this->query($mysql_statement);
    }

    public function select($cols, $table, $vals){
        $select = "SELECT";
        $from = " FROM $table";
        $where = " WHERE";

        if( is_array($cols) ){
            $index = sizeof($cols);
            foreach($cols as $key => $value){

                $select .= " $value ";
                $index--;
                if( $index > 0 ){
                   $select .= ',';
                }
            }
        } else {
            $select .= " $cols ";
        }

        if( is_array($vals) ){
            $index = sizeof($vals);
            foreach($vals as $key => $value){
                $where .= " $key='$value' ";
                $index--;
                if( $index > 0 ){
                    $where .= " AND ";
                }
            }
        }else{
            throw new \Exception('Need key value pair for setting a value');
        }

        $sql_statement = $select . " " . $from . " " . $where;
        return $this->query($sql_statement);
    }

    public function query($sql_statement){
        $q = $this->_connection->query($sql_statement);

        return $q;

    }
    /**
     * Empty clone magic method to prevent a duplicate connection
     */
    private function __clone(){}

    /**
     * return the mysqli connection
     * @return \mysqli
     */
    public function get_connection(){
        return $this->_connection;
    }
} 