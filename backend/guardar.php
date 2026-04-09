<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';

$codigo = trim($_POST['codigo'] ?? '');
$tipo   = trim($_POST['tipo']   ?? '');

if(!$codigo || !$tipo){
    echo json_encode(["error" => "Faltan datos obligatorios"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, nombre, correo FROM usuario WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if($row){
    $id_usuario = $row['id'];
    $nombre     = $row['nombre'];
    $correo     = $row['correo'];
} else {
    $nombre   = trim($_POST['nombre']   ?? '');
    $correo   = trim($_POST['correo']   ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $proyecto = trim($_POST['proyecto'] ?? '');
    $es_ud = isset($_POST['es_ud']) ? (int)$_POST['es_ud'] : 1;

    if(!$nombre || !$correo){
        echo json_encode(["error" => "Faltan datos del usuario"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO usuario(nombre,codigo,correo,telefono,proyecto,es_ud) VALUES(?,?,?,?,?,?)");
    $stmt->bind_param("sssssi", $nombre, $codigo, $correo, $telefono, $proyecto, $es_ud);
    $stmt->execute();
    $id_usuario = $stmt->insert_id;
}

if($tipo == 'free'){
    $stmt = $conn->prepare("SELECT id FROM predicciones WHERE id_usuario=? AND tipo='free'");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0){
        echo json_encode(["error" => "Ya registraste una polla gratis"]);
        exit;
    }
}

$estado = ($tipo == '3000') ? 'pendiente' : 'activa';

$stmt = $conn->prepare("INSERT INTO predicciones(id_usuario,tipo,estado) VALUES(?,?,?)");
$stmt->bind_param("iss", $id_usuario, $tipo, $estado);
$stmt->execute();
$id_prediccion = $stmt->insert_id;

if($tipo == '3000' && isset($_FILES['comprobante'])){
    $archivo    = $_FILES['comprobante'];
    $permitidos = ['image/jpeg','image/png','application/pdf'];

    if(!in_array($archivo['type'], $permitidos)){
        echo json_encode(["error" => "Formato inválido"]); exit;
    }
    if($archivo['size'] > 5 * 1024 * 1024){
        echo json_encode(["error" => "Archivo muy grande"]); exit;
    }

    $nombreArchivo = time() . "_" . basename($archivo['name']);
    $ruta = "../uploads/comprobantes/" . $nombreArchivo;

    if(move_uploaded_file($archivo['tmp_name'], $ruta)){
        $stmt = $conn->prepare("UPDATE predicciones SET comprobante=? WHERE id=?");
        $stmt->bind_param("si", $ruta, $id_prediccion);
        $stmt->execute();
    } else {
        echo json_encode(["error" => "Error subiendo archivo"]); exit;
    }
}

$stmt = $conn->prepare("INSERT INTO podio(id_prediccion,campeon,subcampeon,tercero,cuarto) VALUES(?,?,?,?,?)");
$stmt->bind_param("issss",
    $id_prediccion,
    $_POST['campeon'],
    $_POST['subcampeon'],
    $_POST['tercero'],
    $_POST['cuarto']
);
$stmt->execute();

if($tipo == '3000'){

    foreach(range('A','L') as $g){
        $stmt = $conn->prepare("INSERT INTO grupos(id_prediccion,grupo,primero,segundo,tercero,cuarto) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("isssss",
            $id_prediccion, $g,
            $_POST["grupo_{$g}_1"],
            $_POST["grupo_{$g}_2"],
            $_POST["grupo_{$g}_3"],
            $_POST["grupo_{$g}_4"]
        );
        $stmt->execute();
    }

    if(isset($_POST['terceros'])){
        foreach($_POST['terceros'] as $grupo){
            $stmt = $conn->prepare("INSERT INTO terceros(id_prediccion,grupo) VALUES(?,?)");
            $stmt->bind_param("is", $id_prediccion, $grupo);
            $stmt->execute();
        }
    }

    $i = 1;
    while(isset($_POST["partido_$i"])){
        $equipo1 = $_POST["equipo1_$i"] ?? '';
        $equipo2 = $_POST["equipo2_$i"] ?? '';
        if($equipo1 && $equipo2){
            $stmt = $conn->prepare("INSERT INTO eliminatorias(id_prediccion,ronda,partido_id,equipo1,equipo2,ganador) VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("isssss",
                $id_prediccion,
                $_POST["ronda_$i"],
                $_POST["partido_$i"],
                $equipo1,
                $equipo2,
                $_POST["ganador_$i"]
            );
            $stmt->execute();
        }
        $i++;
    }
}

$goleador       = trim($_POST['goleador']        ?? '');
$mejor_arquero  = trim($_POST['mejor_arquero']   ?? '');
$goles_final    = intval($_POST['goles_final']   ?? 0);
$tarjetas_rojas = intval($_POST['tarjetas_rojas']?? 0);
$goles_grupos   = intval($_POST['goles_grupos']  ?? 0);

$stmt = $conn->prepare("
    INSERT INTO desempate
        (id_prediccion, goleador, mejor_arquero, goles_final, tarjetas_rojas, goles_grupos)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("issiii",
    $id_prediccion,
    $goleador,
    $mejor_arquero,
    $goles_final,
    $tarjetas_rojas,
    $goles_grupos
);
$stmt->execute();

$equipo_sorpresa   = trim($_POST['equipo_sorpresa']   ?? '');
$equipo_decepcion  = trim($_POST['equipo_decepcion']  ?? '');
$jugador_joven     = trim($_POST['jugador_joven']     ?? '');
$seleccion_goles   = trim($_POST['seleccion_goles']   ?? '');
$seleccion_defensa = trim($_POST['seleccion_defensa'] ?? '');
$prorroga_final    = trim($_POST['prorroga_final']    ?? '');
$prorroga_final    = in_array($prorroga_final, ['si','no']) ? $prorroga_final : null;

$stmt = $conn->prepare("
    INSERT INTO preguntas_extra
        (id_prediccion, equipo_sorpresa, equipo_decepcion, jugador_joven,
         seleccion_goles, seleccion_defensa, prorroga_final)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("issssss",
    $id_prediccion,
    $equipo_sorpresa,
    $equipo_decepcion,
    $jugador_joven,
    $seleccion_goles,
    $seleccion_defensa,
    $prorroga_final
);
$stmt->execute();

echo json_encode(["success" => true, "id" => $id_prediccion]);
$conn->close();
?>