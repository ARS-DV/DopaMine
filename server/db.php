<?php
$host     = "localhost";
$user     = "youruser";
$password = "";
$dbname   = "dbname";

$conn = new mysqli($host, $user, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode([
        'status'  => 'error',
        'message' => 'Connection failed: ' . $conn->connect_error
    ]));
}
?>