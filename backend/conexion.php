<?php
/*$host = "localhost";
$user = "root";
$pass = "admin7942_";
$db = "polla_db";
*/

$host = "maglev.proxy.rlwy.net";
$port = 55559;
$user = "root"; 
$pass = "AUWnsHGUiRFPssoFbCHzXeyltpLkkiWa";
$db = "polla_db";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>