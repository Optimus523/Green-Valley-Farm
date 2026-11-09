<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'farm_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error){
    die('Database connection failed.\n');
}

$conn->set_charset('utf8mb4');