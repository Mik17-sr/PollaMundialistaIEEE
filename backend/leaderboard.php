<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');


require_once 'conexion.php'; 


function query($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return [];
    $rows = [];
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    return $rows;
}


$real_podio_rows = query($conn, "SELECT * FROM real_podio LIMIT 1");
$real_podio = $real_podio_rows[0] ?? ['campeon'=>null,'subcampeon'=>null,'tercero'=>null,'cuarto'=>null];

$real_grupos = [];
foreach (query($conn, "SELECT * FROM real_grupos") as $rg) {
    $real_grupos[$rg['grupo']] = $rg;
}

$real_elim = [];
foreach (query($conn, "SELECT * FROM real_eliminatorias") as $re) {
    $real_elim[$re['partido_id']] = $re;
}


$real_thirds = query($conn, "
    SELECT rt.grupo, rg.tercero AS equipo
    FROM real_terceros rt
    INNER JOIN real_grupos rg ON rg.grupo = rt.grupo
");

$preds = query($conn, "
    SELECT p.*, u.nombre, u.codigo, u.correo, u.telefono, u.proyecto
    FROM predicciones p
    JOIN usuario u ON u.id = p.id_usuario
    ORDER BY p.id
");


if (empty($preds)) {
    echo json_encode([
        'usuarios' => [], 'predicciones' => [],
        'podios' => [], 'desempates' => [],
        'grupos' => [], 'eliminatorias' => [],
        'preguntas' => [],
        'real' => [
            'podio'         => $real_podio,
            'grupos'        => $real_grupos,
            'eliminatorias' => $real_elim,
        ]
    ]);
    exit;
}


$in = implode(',', array_map('intval', array_column($preds, 'id')));


$podios = [];
foreach (query($conn, "SELECT * FROM podio WHERE id_prediccion IN ($in)") as $row) {
    $podios[$row['id_prediccion']] = $row;
}


$desempates = [];
foreach (query($conn, "SELECT * FROM desempate WHERE id_prediccion IN ($in)") as $row) {
    $desempates[$row['id_prediccion']] = $row;
}


$grupos = [];
foreach (query($conn, "SELECT * FROM grupos WHERE id_prediccion IN ($in)") as $row) {
    $grupos[$row['id_prediccion']][] = $row;
}


$eliminatorias = [];
foreach (query($conn, "SELECT * FROM eliminatorias WHERE id_prediccion IN ($in)") as $row) {
    $eliminatorias[$row['id_prediccion']][] = $row;
}


$preguntas = [];
foreach (query($conn, "SELECT * FROM preguntas_extra WHERE id_prediccion IN ($in)") as $row) {
    $preguntas[$row['id_prediccion']] = $row;
}


$terceros = [];
foreach (query($conn, "SELECT * FROM terceros WHERE id_prediccion IN ($in)") as $row) {
    $id = $row['id_prediccion'];
    $grupo = $row['grupo'];
    $grupoData = null;
    foreach ($grupos[$id] ?? [] as $g) {
        if ($g['grupo'] === $grupo) {
            $grupoData = $g;
            break;
        }
    }
    if ($grupoData && isset($grupoData['tercero'])) {
        $terceros[$id][] = $grupoData['tercero'];
    }
}


$usuarios     = [];
$predicciones = [];

foreach ($preds as $p) {
    $usuarios[$p['id_usuario']] = [
        'id'       => $p['id_usuario'],
        'nombre'   => $p['nombre'],
        'codigo'   => $p['codigo'],
        'correo'   => $p['correo'],
        'telefono' => $p['telefono'],
        'proyecto' => $p['proyecto'],
    ];
    $predicciones[] = [
        'id'          => $p['id'],
        'id_usuario'  => $p['id_usuario'],
        'tipo'        => $p['tipo'],
        'estado'      => $p['estado'],
        'comprobante' => $p['comprobante'],
    ];
}

echo json_encode([
    'usuarios'      => array_values($usuarios),
    'predicciones'  => $predicciones,
    'podios'        => $podios,
    'desempates'    => $desempates,
    'grupos'        => $grupos,
    'eliminatorias' => $eliminatorias,
    'preguntas'     => $preguntas,
    'terceros'      => $terceros,      
    'real'          => [
        'podio'         => $real_podio,
        'grupos'        => $real_grupos,
        'eliminatorias' => $real_elim,
        'terceros'      => $real_thirds, 
    ],
]);

$conn->close();
?>