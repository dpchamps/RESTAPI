<?php
/*
 * In order to use this interface, add the following rewrite
 * conditions & rules to an htaccess file:
 *
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteCond %{REQUEST_FILENAME} !-d
 *
 * RewriteRule api/(.*)$ api/server.php?request$1 [QSA, NC,L]
 */

require_once 'API/RESTAPI.php';
require_once 'Models/Auth.class.php';
require_once 'Models/Database.class.php';


if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
    $API = new \REST\REST_API($_REQUEST['request']);
    echo $API->processAPI();
    $db = \Models\Database::get_instance();
    /*
    $query = $db->select(
        Array(
            'id' => 'id',
            'username' => 'username',
            'password' => 'password'
        ), 'users',
        Array(
            'username' => 'dummy',
            'password' => md5('dummy')
        ));

    var_dump( $query->fetch_assoc() );
    */
    $login = new \Models\Auth();
    $login->check_login('test', 'test');
    $query = $db->select(
        Array(
            'id' => 'id',
            'username' => 'username',
            'token' => 'token',
            'token_timestamp' => 'token_timestamp'
        ),
        'users',
        Array(
            'username' => 'test',

        )
    )->fetch_assoc();
    foreach($query as $key => $value){
        echo "\n $key  :  $value";
    }





    //$db = \Models\Database::get_instance();
    //$db->get_connection();
} catch (Exception $e){
    echo json_encode( Array('error' => $e->getMessage() ));
}



 