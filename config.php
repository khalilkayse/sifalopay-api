<?php

// Check the HTTP_HOST and assign the appropriate environment variables
switch ($_SERVER['HTTP_HOST']) {
    case "api.sifalopay.com":
    case "phpstack-889786-3084881.cloudwaysapps.com":
        // Set environment variables for the production environment
        define('DB_SERVER','147.182.238.173');
        define('DB_USER','mybajnednq');
        define('DB_PASS','haRjJeP3Hp');
        define('DB_NAME','mybajnednq');
        break;
    default:
        define('DB_SERVER', '74.207.253.75');
        define('DB_USER', 'staging_admin');
        define('DB_PASS','1q2w3e4r-');
        define('DB_NAME','sifalo_pay_staging');
        break;
}

$server = DB_SERVER;
$user = DB_USER;
$pass = DB_PASS;
$db = DB_NAME;
// Create a new mysqli connection using environment variables
$con = new mysqli($server, $user, $pass, $db);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error . 'db_user'. $user);
}


// mail gateway

//$CFG->wwwroot   = 'http://api.sifalopay.com';
