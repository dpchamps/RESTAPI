<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 1/15/2015
 * Time: 4:28 PM
 */

namespace Models;
require_once 'Auth.class.php';

class User extends \Models\Auth{

    private $_token;
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
        $this->_id = $query->fetch_assoc()['id'];

        if($query->num_rows == 1){
            //check if the user is logged in
            if( !$this->is_inactive($this->_id) ){
                throw new \Exception('User has already logged in');
            }
            //user successfully logged in

            //create a new token, and timestamp
            $pair = $this->get_token_timestamp_pair();
            //insert token and timestamp pair into user table
            $this->_db->update(
                $this->login_table,
                $this->_id,
                $pair
            );

            $this->_token = (string)$pair['token'];
            //echo $this->_token;
            $this->_username = $username;
            $this->_logged_in = true;
        }else{
            throw new \Exception('Username or Password incorrect');
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

        $this->_id = $uid->fetch_assoc()['id'];
        //make sure the query returned one result and the token is still valid
        //  if it is valid set class variables
        if($uid->num_rows == 1 &&
            !$this->is_inactive( $this->_id ) ){

            $valid_token = true;

            $this->_username = $username;
            $this->_token = $token;
        }

        return $valid_token;
    }
    public function logout($username, $token){
        //if the username / token pair is not valid then nothing else needs to happen
        if(!$this->valid_token($token, $username)){
            throw new \Exception('User session does not exist');
        }

        $this->_db->update(
            $this->login_table,
            $this->_id,
            Array('token' => NULL, 'token_timestamp' => NULL)
        );

        $this->_token = NULL;


    }
    public function get($what = ''){
        $user_attribs = get_object_vars($this);

        $return_assoc = Array();
        if( is_array($what) ){
            foreach( $what as $key){
                $return_assoc[$key] = $user_attribs['_'.$key];
            }
        }else{
            if(array_key_exists($what, $user_attribs)){
                $return_assoc[$what] = $user_attribs['_'.$what];
            }
        }

        return $return_assoc;
    }
} 