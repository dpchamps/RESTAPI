<?php


namespace Models;
require_once 'Database.class.php';
require_once 'Token.class.php';

/**
 * Class Auth
 * Provides methods for authenticating users
 */
class Auth {

    private $_connection = Null;
    private $_db = Null;
    /**
     * generates an auth token of 32 chars in length
     */
    private function generate_token(){
        return new \Models\Token(32);
    }
    private function generate_timestamp(){
        return date('Y-m-d G:i:s');
    }
    private function get_token_timestamp_pair(){
        return Array(
            'token' => $this->generate_token(),
            'token_timestamp' => $this->generate_timestamp()
        );
    }
    private function connect_to_db(){
        $this->_db = \Models\Database::get_instance();
        $this->_connection = $this->_db->get_connection();
    }

    public function check_login($user, $password){
        $this->connect_to_db();

        if($this->_connection == Null){
            throw new \Exception('No connection to database');
        }

        $query = "SELECT id FROM users WHERE username='$user' AND password=MD5('$password')";
        $check =  $this->_connection->query($query);

        if($check->num_rows == 1){
            //user successfully logged in
            $id = $check->fetch_assoc()['id'];
            //create a new token, and timestamp
            $pair = $this->get_token_timestamp_pair();
            //insert token and timestamp pair into user table
            $this->_db->update(
                'users',
                $id,
                $pair
            );
            //return token/timestamp pair
            return $pair;
        }else{
            throw new \Exception('Username or Password incorrect');
        }



    }

} 