<?php

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

require 'config.php';
require 'library/settings.php';

function __autoload($class) {
    require LIBS . $class . ".php";
}

$app = new Bootstrap();


?>