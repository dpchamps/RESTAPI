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
require_once 'Models/Errors.class.php';

$error = new Errors();
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
    $API = new REST_API($_REQUEST['request']);
    echo $API->processAPI();

} catch (Exception $e){
    echo json_encode( Array('error' => $error->lookup($e->getMessage()) ));
}



 