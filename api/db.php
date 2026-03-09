<?php
header("Access-Control-Allow-Origin: *");
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_internal_app";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}
?>