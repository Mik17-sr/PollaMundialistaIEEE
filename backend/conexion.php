<?php
$host = "localhost";
$user = "polla_user";
$pass = "admin7942_";
$db = "polla_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>