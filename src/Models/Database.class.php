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