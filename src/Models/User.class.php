<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 1/15/2015
 * Time: 4:28 PM
 */


require_once 'Auth.class.php';

class User extends Auth{

    private $_token;
    public $token;
    public $username;
    private $_username;
    private $_logged_in = false;

    public function login($username, $password){

        //select from the users table the id where username and the MD5 hash of the password exist
        $query = $this->_db->select(
            'id',
            'users',
            Array(
                'username' => $username,
                'password' => MD5($password)
            )
        );
        $this->_id = $query->fetch_assoc();
        $this->_id = $this->_id['id'];

        if($query->num_rows == 1){
            //create a new token, and timestamp
            $pair = $this->get_token_timestamp_pair();
            //insert token and timestamp pair into user table
            $this->_db->update(
                $this->login_table,
                $this->_id,
                $pair
            );

            $this->token = (string)$pair['token'];
            //echo $this->_token;
            $this->username = $username;
            $this->_logged_in = true;
        }else{
            throw new Exception(401);
        }
    }
    public function __construct(){
        parent::__construct();
    }
    public function valid_token($token, $username){
        $valid_token = false;
        $uid = $this->_db->select(
            'id',
            'users',
            Array('token' => $token, 'username' => $username)
        );

        $this->_id = $uid->fetch_assoc();
        $this->_id = $this->_id['id'];
        //make sure the query returned one result and the token is still valid
        //  if it is valid set class variables
        if($uid->num_rows == 1 &&
            !$this->is_inactive( $this->_id ) ){

            $valid_token = true;

            $this->username = $username;
            $this->token = $token;
        }
        //upon successful request, update the timestamp
        if($valid_token){
            $this->update_timestamp();
        }
        return $valid_token;
    }
    /*
     * logout()
     * this method should be called only after verifying a
     * valid un/token pair, so the internal variables will be set.
     * error otherwise
     */
    public function logout(){

        $this->_db->update(
            $this->login_table,
            $this->_id,
            Array('token' => NULL, 'token_timestamp' => NULL)
        );

        $this->token = NULL;
    }

    /*
     * change user password
     */
    public function change_password($new_pw){
        //insert new password
        $this->_db->update(
            $this->login_table,
            $this->_id,
            Array('password' => MD5($new_pw))
        );
        //update timestamp
        $this->update_timestamp();
    }
} 