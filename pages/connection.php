<?php

date_default_timezone_set('Asia/Kolkata');

$server_name = "";
$user_name = "";
$password = "";         // Add your password
$database_name = "";
$port = ;

$connection = new mysqli($server_name, $user_name, $password, $database_name, $port);

$connection->set_charset("utf8mb4");

?>
