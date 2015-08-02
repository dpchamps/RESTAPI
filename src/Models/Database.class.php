<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 1/16/2015
 * Time: 2:35 PM
 */


require_once(__DIR__ . '/../config.php');

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
        $this->_connection = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        $this->_connection->set_charset(DB_CHARSET);

        if(mysqli_connect_error()){
            throw new Exception('Failed to connect to MySql: ' . mysqli_connect_error(), E_USER_ERROR);
        }
    }

    public function update($table, $id, $keyvalue){
        if( !is_array($keyvalue) ){
            throw new Exception('Expected type array for keyvalue, got: ' .getType($keyvalue));
        }
        $update = "UPDATE $table";
        $set = "SET";
        $where = "WHERE " . $table.".id= $id";
        $index = sizeof($keyvalue);
        foreach($keyvalue as $key => $value){

            $value = ($value==NULL) ? 'NULL' : '\'' . $value . '\'';
            $set .= " $key=".$value;

            $index--;
            if($index > 0){
                $set .= ", ";
            }
        }
        $mysql_statement = $update . " " . $set . " " . $where;
        return $this->query($mysql_statement);
    }

    public function select($cols, $table, $vals = Array()){
        $select = "SELECT";
        if(is_array($table)){
            $table = implode(',', $table);
        }
        $from = " FROM $table";
        $where = "";

        if( is_array($cols) ){
            $index = sizeof($cols);
            foreach($cols as $key => $value){

                $select .= " $value ";
                $index--;
                if( $index > 0 ){
                   $select .= ',';
                }
            }
        } else if($cols == "" || $cols == NULL){
            $select .= " *";
        } else {
            $select .= " $cols ";
        }

        if( isset($vals) && is_array($vals) && $vals != NULL ){
            $where = " WHERE";
            $index = sizeof($vals);
            foreach($vals as $key => $value){
                $where .= " $key='$value' ";
                $index--;
                if( $index > 0 ){
                    $where .= " AND ";
                }
            }
        }else if (isset($vals) && !is_array($vals) ){
            throw new Exception('Need key value pair for finding a value');
        }

        $sql_statement = $select . " " . $from . " " . $where;

        return $this->query($sql_statement);
    }
    /*
     * returns a the first value of the query,
     * useful for specific queries where one item is expected
     */
    public function select_single_item($col, $table, $clause=Array()){
        $result = $this->select($col, $table, $clause);
        $result = $this->fetch_all($result);
        $result = $result[0];

        return $result[$col];

    }
    public function fetch_all($result){
        if(!method_exists($result, 'fetch_all')){
            $data = Array();
            $i = 0;
            while ($row = $result->fetch_assoc()) {
                $data[$i] = $row;
                $i = $i+1;
            }
        }else{
            $data = $result->fetch_all(MYSQLI_ASSOC);
        }

        return $data;
    }
    public function fetch_all_query($query){
        return $this->fetch_all($this->query($query));
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