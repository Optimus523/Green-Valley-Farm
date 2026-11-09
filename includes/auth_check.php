<?php

session_start();

// check if user ID session variable exists
if (!isset($_SESSION['user_id'])){
    // if not logged in, redirect to login page
    header('Location: login.php');
    exit;
}