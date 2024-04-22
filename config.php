<?php

// db connection

//old connection
//$server = "spay.sifalo.com";
//$user = "spay_admin";
//$pass = "BUELFJet#28u";
//$db =  "paydb"; 

if ($_SERVER['HTTP_HOST'] == "sifalopay.local" || $_SERVER['HTTP_HOST'] == "localhost") {
  $server = "localhost";
  $user = "root";
  $pass = "";
  $db =  "pay";
} else if ($_SERVER['HTTP_HOST'] == "pay.sifalo.net" || $_SERVER['HTTP_HOST'] == "phpstack-889786-3206524.cloudwaysapps.com") {
  $server = "74.207.253.75";
  $user = "staging_admin";
  $pass = "1q2w3e4r-";
  $db =  "sifalo_pay_staging";
} else {
  $server = "147.182.238.173";
  $user = "mybajnednq";
  $pass = "haRjJeP3Hp";
  $db =  "mybajnednq";
}

$con = mysqli_connect($server, $user, $pass, $db);

// mail gateway

//$CFG->wwwroot   = 'http://phpstack-889786-3206524.cloudwaysapps.com';
