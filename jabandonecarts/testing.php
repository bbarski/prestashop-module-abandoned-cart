<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Envia los correos a los diferentes clientes
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/jabandonecarts.php');

$modulo = new jabandonecarts();
$modulo->testing();