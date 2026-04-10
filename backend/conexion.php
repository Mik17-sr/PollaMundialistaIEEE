<?php
/*
$host = "127.0.0.1";
$user = "u496942219_root";
$pass = "vB#W/ff7H&";
$db = "u496942219_polla_db";
$port = 3306;*/

$host = "localhost";
$user = "root"; 
$pass = "admin7942_";
$db = "polla_db";

//$conn = new mysqli($host, $user, $pass, $db, $port);
$conn = new mysqli($host, $user, $pass, $db);


if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>