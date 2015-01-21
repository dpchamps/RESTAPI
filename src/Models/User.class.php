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
    private $logged_in = false;
    
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
        //check if the user is logged in
        if( !$this->is_inactive($this->_id) ){
            throw new \Exception('User has already logged in');
        }
        if($query->num_rows == 1){
            //user successfully logged in

            //create a new token, and timestamp
            $pair = $this->get_token_timestamp_pair();
            //insert token and timestamp pair into user table
            $this->_db->update(
                $this->login_table,
                $this->_id,
                $pair
            );
            //return token/timestamp pair
            return $pair;
        }else{
            throw new \Exception('Username or Password incorrect');
        }
    }
    public function __construct(){
        parent::__construct();
    }
    public function valid_token($token){

        $valid_token = false;
        $uid = $this->_db->select(
            'id',
            'users',
            Array('token' => $token)
        )->fetch_assoc()['id'];

        if( !$this->is_inactive($uid) ){
            $valid_token = true;
        }

        return $valid_token;
    }

} 