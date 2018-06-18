<?php

/************************************************************************
*
* TO CUSTOMIZE THE SIGNUP DON'T MODIFY THIS FILE,
* MODIFY INSTEAD THE FOLLOWING FILE:
*
* /modules/admin/controllers/SignuppublicController.php
*
*
*************************************************************************/

// We will need this to determine certain validation routines for server plugins' validateCredentials() method.
define('NE_SIGNUP', true);


if (!isset($_REQUEST['step'])) {
    $_REQUEST['step'] = 1;
}

if (!isset($_REQUEST['pass'])) {
    $pass = 0;
} else {
    $pass = $_REQUEST['pass'];
}

// paypal doesn't seem to decide on how to return to the merchant
// so this check is added in the event paypal returns back to order
// page with a completed transaction
if (    isset($_SERVER['REQUEST_URI'])
        && (    strpos($_SERVER['REQUEST_URI'], 'Access+Your+Subscription') !== false
                || strpos($_SERVER['REQUEST_URI'], 'Return+To+Merchant') !== false
                || strpos($_SERVER['REQUEST_URI'], 'merchant_return_link') !== false))
{
    $_REQUEST['step'] = "complete";
    $pass = 1;
}

//let's filter step
switch ($_REQUEST['step'])
{
    case "0":
    case "1":
        $view = "cart1";
        break;
    case "2":
        if ( !isset($_REQUEST['product']) || ($_REQUEST['product'] == 0) ) {
            $_REQUEST['step'] = 1;
            $view = "cart1";
        } else {
            $view = "cart2";
        }
        break;
    case "3":
        $view = "cart3";
        break;
    case "phone-verification":
        $view = "phoneverification";
        break;
    case "complete":
        if ( $pass == 1 ) {
            $view = 'success';
        } else {
            $view = 'cart3';
        }
        break;
    default:
        $view = "cart1";
        break;
}


$_GET['view'] = $view;


// need both _GET and _REQUEST for lang() calls to work
$_GET['fuse'] = $_REQUEST['fuse'] = 'admin';

$_GET['controller'] = 'signup';

chdir(dirname(__FILE__));
require_once 'library/front.php';