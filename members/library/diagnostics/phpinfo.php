<?php

require_once __DIR__.'/../../config.php';

//clean up the divider for windows
if ( ! defined( "PATH_SEPARATOR" ) ) {
  if ( strpos( $_ENV[ "OS" ], "Win" ) !== false )
    define( "PATH_SEPARATOR", ";" );
  else define( "PATH_SEPARATOR", ":" );
}

define('APPLICATION_PATH', realpath(dirname(__FILE__)));
define('CACHE_PATH',APPLICATION_PATH.'/../uploads/cache/');

set_include_path(APPLICATION_PATH.'/..'.PATH_SEPARATOR.APPLICATION_PATH);

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
//let's register the CE namespace for autoloader of our own lib classes
$autoloader->registerNamespace('CE_');

$options = Array(
    'name'=> (SESSION_NAME == 'CLIENTEXEC') ? md5(realpath(dirname(__FILE__)."/../")) : SESSION_NAME,
    'cookie_httponly' => true
);
if (defined('SESSION_PATH') && SESSION_PATH) {
    $options['save_path'] = SESSION_PATH;

    // setOptions() uses ini_set()
    if (!function_exists('ini_set')) {
        session_save_path(SESSION_PATH);
    }
}
Zend_Session::setOptions($options);
$session = new Zend_Session_Namespace('admin');

if (!isset($session->groupId) || $session->groupId  != 2) die('Information only viewable by logged-in administrators.');

phpinfo();

?>
