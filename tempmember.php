<?php
/*************************************************
 * tempmembers' home
 * 
 */

define('_ROOT_DIR', __DIR__ . '/');
require_once _ROOT_DIR . '../php_libs/init.php';
$controller = new TempmemberController();
$controller->run();

exit;


?>
