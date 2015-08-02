<?php



require_once 'Database.class.php';
require_once 'Token.class.php';

/**
 * Class Auth
 * Provides methods for authenticating a user
 */
class Auth {

    private $_connection;
    private $_token;

    private $INACTIVE_TIMEOUT = 900;

    protected $_db;
    protected $_id;

    protected $login_table = 'users';
    /**
     * generates an auth token of 32 chars in length
     */
    private function generate_token(){
        $token = new
        Token(32);
        return $token;
    }
    private function generate_timestamp(){
        return date('Y-m-d G:i:s');
    }
    protected function get_token_timestamp_pair(){
        return Array(
            'token' => $this->generate_token(),
            'token_timestamp' => $this->generate_timestamp()
        );
    }
    private function connect_to_db(){

    }

    /**
     * @param $username
     * @return int time in seconds between last activity and now
     */
    private function inactivity_time($id){
        $timestamp = $this->_db->select(
            'token_timestamp',
            'users',
            Array( 'id' => $id)
        )->fetch_assoc();
        $timestamp = $timestamp['token_timestamp'];

        $t = strtotime('now') - strtotime($timestamp);

        return $t;
    }
    protected  function update_timestamp(){
        $timestamp = $this->generate_timestamp();

        $this->_db->update(
            $this->login_table,
            $this->_id,
            Array( 'token_timestamp' => $timestamp)
        );
    }
    protected function is_inactive($id){
        $seconds_passed = $this->inactivity_time($id);
        $inactive = false;

        if($seconds_passed > $this->INACTIVE_TIMEOUT){
            $inactive = true;
        }

        return $inactive;
    }


    public function __construct(){
        $this->_db = Database::get_instance();
        $this->_connection = $this->_db->get_connection();
        if($this->_connection == Null){
            throw new Exception('No connection to database');
        }
    }

} 