<?php

/*
 * Development Environment DB Parameters
 * *
 */

ini_set('memory_limit', '-1');
define('DB_TYPE', 'mysql');
// error_reporting(E_ALL & ~E_NOTICE);
define('DB_HOST', 'localhost', true);
define('DB_PORT', '3306', true);
define('DB_USER', 'sys_access', true);
define('DB_PASS', 'password', true);
define('DB_NAME', 'callstar_ussd', true);



/*
 * Encryption Algo
 */

define('ENC', 'sha256');

/*
 * System Hash Keys
 */


define("SAVE_CONNECT", 'http://localhost/callstarapi/');
define("SAVE_CONNECT_PASS",'btr');