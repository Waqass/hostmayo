<?php

$_GET['fuse']='billing';
$_GET['action'] = 'gatewaycallback';
$_GET['plugin'] = 'internetsecure';

chdir('../../..');

require_once dirname(__FILE__).'/../../../library/front.php';

?>
