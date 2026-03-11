<?php
$host = "localhost";
$dbname = "cooparjana";
$username = "root";
$password = "";

/**
 * SECURITY NOTE: 
 * For production environments, it is HIGHLY RECOMMENDED to use environment variables 
 * (via a .env file or system variables) rather than hardcoding credentials here.
 * See .env.example for guidance.
 */
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
?>
