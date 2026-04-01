<?php
session_start();
header('Content-Type: application/json');
require_once 'conexion.php';

$codigo = trim($_GET['codigo'] ?? '');

if (strlen($codigo) < 5) {
    echo json_encode(["existe" => false]);
    exit;
}

$stmt = $conn->prepare("SELECT id, nombre, correo, telefono, proyecto FROM usuario WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    session_unset();
    session_regenerate_id(true);
    $_SESSION['codigo_verificado'] = $codigo;
    function censurar($texto, $visibles = 2) {
        $len = mb_strlen($texto);
        if ($len <= $visibles) return str_repeat('*', $len);
        return mb_substr($texto, 0, $visibles) . str_repeat('*', $len - $visibles);
    }
    function censurarCorreo($correo) {
        $partes = explode('@', $correo);
        return censurar($partes[0], 2) . '@' . $partes[1];
    }
    echo json_encode([
        "existe"   => true,
        "nombre"   => $row['nombre'],
        "correo"   => censurarCorreo($row['correo']),
        "telefono" => censurar($row['telefono'], 3),
        "proyecto" => $row['proyecto']
    ]);
} else {
    echo json_encode(["existe" => false]);
}
?>