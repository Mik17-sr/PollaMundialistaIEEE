<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$EQUIPOS_MUNDIAL = [
    'México', 'Sudáfrica', 'Corea del Sur', 'República Checa',
    'Canadá', 'Bosnia & Herzegovina', 'Catar', 'Suiza',
    'Brasil', 'Marruecos', 'Haití', 'Escocia',
    'Estados Unidos', 'Paraguay', 'Australia', 'Turquía',
    'Alemania', 'Curazao', 'Costa de Marfil', 'Ecuador',
    'Países Bajos', 'Japón', 'Suecia', 'Túnez',
    'Bélgica', 'Egipto', 'Irán', 'Nueva Zelanda',
    'España', 'Cabo Verde', 'Arabia Saudita', 'Uruguay',
    'Francia', 'Sénégal', 'Irak', 'Noruega',
    'Argentina', 'Argelia', 'Austria', 'Jordania',
    'Portugal', 'República Democrática del Congo', 'Uzbekistán', 'Colombia',
    'Inglaterra', 'Croacia', 'Ghana', 'Panamá'
];
sort($EQUIPOS_MUNDIAL);

// 1. Activar apuesta
if (isset($_POST['action']) && $_POST['action'] === 'activar_apuesta') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit; }

    $stmt = $conn->prepare(
        "UPDATE predicciones SET estado = 'activa' WHERE id = ? AND tipo = '3000' AND estado = 'pendiente'"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Apuesta activada' : 'Sin cambios']);
    exit;
}

// 2. Guardar PODIO (campeón, subcampeón, tercero, cuarto)
if (isset($_POST['action']) && $_POST['action'] === 'guardar_podio') {
    header('Content-Type: application/json');
    $campeon    = trim($_POST['campeon'] ?? '');
    $subcampeon = trim($_POST['subcampeon'] ?? '');
    $tercero    = trim($_POST['tercero'] ?? '');
    $cuarto     = trim($_POST['cuarto'] ?? '');
    
    $stmt = $conn->prepare(
        "INSERT INTO real_podio (id, campeon, subcampeon, tercero, cuarto) 
         VALUES (1, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
           campeon = VALUES(campeon),
           subcampeon = VALUES(subcampeon),
           tercero = VALUES(tercero),
           cuarto = VALUES(cuarto)"
    );
    $stmt->bind_param('ssss', $campeon, $subcampeon, $tercero, $cuarto);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();
    echo json_encode(['ok' => true, 'msg' => 'Podio guardado']);
    exit;
}

// 3. Guardar grupos y mejores terceros
if (isset($_POST['action']) && $_POST['action'] === 'guardar_grupos') {
    header('Content-Type: application/json');
    $grupos   = $_POST['grupos']   ?? [];
    $terceros = $_POST['terceros'] ?? [];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "INSERT INTO real_grupos (grupo, primero, segundo, tercero, cuarto)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               primero = VALUES(primero), segundo = VALUES(segundo),
               tercero = VALUES(tercero), cuarto  = VALUES(cuarto)"
        );
        foreach ($grupos as $letra => $pos) {
            $letra = strtoupper(substr($letra, 0, 1));
            $p1 = $pos['primero'] ?? null;
            $p2 = $pos['segundo'] ?? null;
            $p3 = $pos['tercero'] ?? null;
            $p4 = $pos['cuarto']  ?? null;
            $stmt->bind_param('sssss', $letra, $p1, $p2, $p3, $p4);
            $stmt->execute();
        }
        $stmt->close();

        $conn->query("DELETE FROM real_terceros");
        $ins = $conn->prepare("INSERT INTO real_terceros (grupo) VALUES (?)");
        foreach ($terceros as $g) {
            $g = strtoupper(substr($g, 0, 1));
            $ins->bind_param('s', $g);
            $ins->execute();
        }
        $ins->close();

        $conn->commit();
        echo json_encode(['ok' => true, 'msg' => 'Resultados de grupos guardados']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 4. Guardar eliminatorias (todas las fases)
if (isset($_POST['action']) && $_POST['action'] === 'guardar_eliminatorias') {
    header('Content-Type: application/json');
    $partidos = json_decode($_POST['partidos'] ?? '[]', true);
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM real_eliminatorias");
        $stmt = $conn->prepare(
            "INSERT INTO real_eliminatorias (ronda, partido_id, equipo1, equipo2, ganador)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($partidos as $p) {
            $ronda      = $p['ronda'] ?? '';
            $partido_id = $p['partido_id'] ?? '';
            $equipo1    = $p['equipo1'] ?? null;
            $equipo2    = $p['equipo2'] ?? null;
            $ganador    = $p['ganador'] ?? null;
            $stmt->bind_param('sssss', $ronda, $partido_id, $equipo1, $equipo2, $ganador);
            $stmt->execute();
        }
        $stmt->close();
        $conn->commit();
        echo json_encode(['ok' => true, 'msg' => 'Eliminatorias guardadas']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 5. Guardar desempate
if (isset($_POST['action']) && $_POST['action'] === 'guardar_desempate') {
    header('Content-Type: application/json');
    
    $goleador        = trim($_POST['goleador'] ?? '');
    $mejor_arquero   = trim($_POST['mejor_arquero'] ?? '');
    $goles_final     = $_POST['goles_final'] !== '' ? (int)$_POST['goles_final'] : null;
    $tarjetas_rojas  = $_POST['tarjetas_rojas'] !== '' ? (int)$_POST['tarjetas_rojas'] : null;
    $goles_grupos    = $_POST['goles_grupos'] !== '' ? (int)$_POST['goles_grupos'] : null;
    $equipo_sorpresa   = trim($_POST['equipo_sorpresa'] ?? '');
    $equipo_decepcion  = trim($_POST['equipo_decepcion'] ?? '');
    $jugador_joven     = trim($_POST['jugador_joven'] ?? '');
    $seleccion_goles   = trim($_POST['seleccion_goles'] ?? '');
    $seleccion_defensa = trim($_POST['seleccion_defensa'] ?? '');
    $prorroga_final    = $_POST['prorroga_final'] ?? null;
    
    $conn->begin_transaction();
    try {
        // Limpiar tablas
        $conn->query("DELETE FROM real_desempate");
        $conn->query("DELETE FROM real_preguntas_extra");
        
        // Insertar desempate
        $stmt1 = $conn->prepare(
            "INSERT INTO real_desempate (goleador, mejor_arquero, goles_final, tarjetas_rojas, goles_grupos)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt1->bind_param('ssiii', $goleador, $mejor_arquero, $goles_final, $tarjetas_rojas, $goles_grupos);
        $stmt1->execute();
        $stmt1->close();
        
        // Insertar preguntas extra
        $stmt2 = $conn->prepare(
            "INSERT INTO real_preguntas_extra 
             (equipo_sorpresa, equipo_decepcion, jugador_joven, seleccion_goles, seleccion_defensa, prorroga_final)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt2->bind_param('ssssss', $equipo_sorpresa, $equipo_decepcion, $jugador_joven, $seleccion_goles, $seleccion_defensa, $prorroga_final);
        $stmt2->execute();
        $stmt2->close();
        
        $conn->commit();
        echo json_encode(['ok' => true, 'msg' => 'Desempate guardado']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 6. Obtener todos los datos actuales
if (isset($_GET['action']) && $_GET['action'] === 'get_todos_datos') {
    header('Content-Type: application/json');
    
    $podio = $conn->query("SELECT * FROM real_podio WHERE id = 1")->fetch_assoc();
    $grupos = $conn->query("SELECT * FROM real_grupos ORDER BY grupo")->fetch_all(MYSQLI_ASSOC);
    $tercerosRaw = $conn->query("SELECT grupo FROM real_terceros")->fetch_all(MYSQLI_ASSOC);
    $terceros = array_column($tercerosRaw, 'grupo');
    $eliminatorias = $conn->query("SELECT * FROM real_eliminatorias ORDER BY FIELD(ronda, 'R32', 'R16', 'QF', 'SF', 'TP', 'F'), partido_id")->fetch_all(MYSQLI_ASSOC);
    $desempate = $conn->query("SELECT * FROM real_desempate LIMIT 1")->fetch_assoc();
    $preguntasExtra = $conn->query("SELECT * FROM real_preguntas_extra LIMIT 1")->fetch_assoc();
    
    echo json_encode([
        'podio' => $podio ?: [],
        'grupos' => $grupos,
        'terceros' => $terceros,
        'eliminatorias' => $eliminatorias,
        'desempate' => $desempate ?: [],
        'preguntasExtra' => $preguntasExtra ?: [],
        'equipos' => $EQUIPOS_MUNDIAL
    ]);
    exit;
}

// 7. Listar apuestas con filtros
if (isset($_GET['action']) && $_GET['action'] === 'listar_apuestas') {
    header('Content-Type: application/json');

    $nombre  = '%' . trim($_GET['nombre']  ?? '') . '%';
    $codigo  = '%' . trim($_GET['codigo']  ?? '') . '%';
    $carrera = '%' . trim($_GET['carrera'] ?? '') . '%';
    $estado  =       trim($_GET['estado']  ?? '');

    if ($estado !== '') {
        $stmt = $conn->prepare(
            "SELECT p.id, u.nombre, u.codigo, u.correo, u.telefono, u.proyecto,
                    p.tipo, p.estado, p.comprobante, p.created_at
             FROM predicciones p
             JOIN usuario u ON u.id = p.id_usuario
             WHERE p.tipo = '3000'
               AND u.nombre   LIKE ?
               AND u.codigo   LIKE ?
               AND u.proyecto LIKE ?
               AND p.estado   = ?
             ORDER BY p.created_at DESC LIMIT 200"
        );
        $stmt->bind_param('ssss', $nombre, $codigo, $carrera, $estado);
    } else {
        $stmt = $conn->prepare(
            "SELECT p.id, u.nombre, u.codigo, u.correo, u.telefono, u.proyecto,
                    p.tipo, p.estado, p.comprobante, p.created_at
             FROM predicciones p
             JOIN usuario u ON u.id = p.id_usuario
             WHERE p.tipo = '3000'
               AND u.nombre   LIKE ?
               AND u.codigo   LIKE ?
               AND u.proyecto LIKE ?
             ORDER BY p.created_at DESC LIMIT 200"
        );
        $stmt->bind_param('sss', $nombre, $codigo, $carrera);
    }

    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    exit;
}

// ============================================================
//  ESTADÍSTICAS RÁPIDAS
// ============================================================
$stats = $conn->query("
    SELECT
        COUNT(*)                  AS total,
        SUM(estado = 'activa')    AS activas,
        SUM(estado = 'pendiente') AS pendientes,
        SUM(tipo   = 'free')      AS gratuitas
    FROM predicciones
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Polla Mundialista</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../admin.css">
<style>
/* ================================
   ELIMINATORIAS (DARK THEME)
================================ */
.rounds-container { 
  display: flex; 
  gap: 1rem; 
  overflow-x: auto; 
  padding-bottom: 1rem; 
}

.round-col { 
  min-width: 260px; 
  background: var(--card-bg);
  border-radius: 16px; 
  padding: 1rem;
  border: 1px solid var(--border);
}

.round-title { 
  font-weight: 700; 
  margin-bottom: 1rem; 
  padding-bottom: 0.5rem; 
  border-bottom: 2px solid var(--accent);
  color: var(--text);
}

.match-card { 
  background: var(--card-bg-2); 
  border-radius: 12px; 
  padding: 0.75rem; 
  margin-bottom: 0.75rem; 
  border: 1px solid var(--border);
  overflow: hidden;
}

.match-header { 
  font-size: 0.7rem; 
  color: var(--text-m); 
  margin-bottom: 0.5rem; 
}

.match-team { 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  padding: 0.4rem 0; 
}

.match-team-name { 
  flex: 1; 
  font-size: 0.8rem; 
  font-weight: 500; 
  color: var(--text);
}

.match-team select { 
  flex: 2; 
  padding: 0.4rem; 
  border-radius: 8px; 
  border: 1px solid var(--border);
  background: var(--input-bg);
  color: var(--text);
  font-family: inherit; 
  font-size: 0.75rem; 
}

.winner-select { 
  width: 100%; 
  padding: 0.5rem; 
  margin-top: 0.5rem; 
  border-radius: 8px; 
  border: 1px solid var(--accent);
  background: rgba(16,185,129,0.08);
  color: var(--text);
  font-family: inherit; 
  font-size: 0.8rem; 
  font-weight: 500; 
}

/* ================================
   PODIO
================================ */
.podio-grid { 
  display: grid; 
  grid-template-columns: repeat(4,1fr); 
  gap: 1rem; 
  margin-bottom: 1rem; 
}

.podio-card { 
  background: var(--card-bg);
  border-radius: 16px; 
  padding: 1rem; 
  text-align: center;
  border: 1px solid var(--border);
}

.podio-card .rank { 
  font-size: 1.2rem; 
  font-weight: 700; 
  color: var(--accent);
  margin-bottom: 0.75rem; 
}

.podio-card select { 
  width: 100%; 
  padding: 0.5rem; 
  border-radius: 8px; 
  border: 1px solid var(--border);
  background: var(--input-bg);
  color: var(--text);
  font-family: inherit; 
}

/* ================================
   DESEMPATE
================================ */
.desempate-grid { 
  display: grid; 
  grid-template-columns: repeat(4,1fr); 
  gap: 1rem; 
  margin-top: 1rem; 
}

.desempate-field { 
  display: flex; 
  flex-direction: column; 
  gap: 0.25rem; 
}

.desempate-field label { 
  font-size: 0.75rem; 
  font-weight: 500; 
  color: var(--text-m); 
}

.desempate-field input, 
.desempate-field select { 
  padding: 0.5rem; 
  border: 1px solid var(--border);
  border-radius: 8px; 
  font-family: inherit;
  background: var(--input-bg);
  color: var(--text);
}

/* FIX selects eliminatorias overflow */
.match-card {
  overflow: hidden;
}

.match-team {
  width: 100%;
  min-width: 0;
}

.match-team {
  display: grid;
  grid-template-columns: 70px 1fr;
  gap: 0.5rem;
  align-items: center;
  width: 100%;
}

.match-team-name {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--text);
}

.match-team select,
.winner-select {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.match-team-name {
  flex: 0 0 90px;
}

/* =========================
   GRUPOS - SELECT DARK FIX
========================= */

.grupo-select {
  width: 100%;
  padding: 0.5rem;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--input-bg);
  color: var(--text);
  font-family: inherit;
}

.grupo-select option {
  background: #0b1220;
  color: #e5e7eb;
}

/* =========================
   MEJORES TERCEROS
========================= */

.terceros-checks {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: .5rem;
  margin-top: .75rem;
}

.tercero-check {
  padding: .55rem;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--card-bg);
  color: var(--text);
  cursor: pointer;
  font-size: .8rem;
  text-align: center;
  transition: .15s ease;
}

.tercero-check:hover {
  border-color: var(--accent);
}

.tercero-check.sel {
  background: rgba(59,130,246,.15);
  border-color: var(--accent);
  color: #93c5fd;
  font-weight: 600;
}

.tercero-check input {
  display: none;
}

.grupo-select:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 1px var(--accent);
}

</style>
</head>
<body>

<nav>
  <a class="logo" href="#">
    <div class="logo-icon">⚽</div>
    Admin · Polla Mundialista
  </a>
  <div class="nav-badge"><span>●</span> Panel activo</div>
  <div class="nav-user"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($_SESSION['usuario']) ?></div>
  <a class="btn-logout" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a>
</nav>

<div class="page">

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="s-label">Total apuestas</span>
      <span class="s-val c-text"><?= $stats['total'] ?></span>
      <span class="s-sub">Registradas</span>
    </div>
    <div class="stat-card">
      <span class="s-label">Activas</span>
      <span class="s-val c-ok"><?= $stats['activas'] ?></span>
      <span class="s-sub">Pago verificado</span>
    </div>
    <div class="stat-card">
      <span class="s-label">Pendientes</span>
      <span class="s-val c-warn"><?= $stats['pendientes'] ?></span>
      <span class="s-sub">Requieren revisión</span>
    </div>
    <div class="stat-card">
      <span class="s-label">Gratuitas</span>
      <span class="s-val c-acc"><?= $stats['gratuitas'] ?></span>
      <span class="s-sub">Tipo free</span>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('apuestas',this)">
      <i class="bi bi-cash-stack"></i> Gestión de Apuestas
    </button>
    <button class="tab-btn" onclick="switchTab('podio',this)">
      <i class="bi bi-trophy"></i> Podio
    </button>
    <button class="tab-btn" onclick="switchTab('grupos',this)">
      <i class="bi bi-grid"></i> Grupos
    </button>
    <button class="tab-btn" onclick="switchTab('eliminatorias',this)">
      <i class="bi bi-diagram-3"></i> Eliminatorias
    </button>
    <button class="tab-btn" onclick="switchTab('desempate',this)">
      <i class="bi bi-clipboard-data"></i> Desempate
    </button>
  </div>

  <!-- TAB 1 - Apuestas -->
  <div id="tab-apuestas" class="tab-panel active">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-funnel" style="color:var(--accent-l);font-size:1.3rem;"></i>
        <div>
          <h2>Apuestas de $3.000 COP</h2>
          <p>Filtra y activa apuestas tras verificar el pago manualmente</p>
        </div>
      </div>
      <div class="card-body">
        <div class="filters">
          <input id="f-nombre"  type="text"  placeholder="🔍 Nombre">
          <input id="f-codigo"  type="text"  placeholder="🔍 Código estudiantil">
          <input id="f-carrera" type="text"  placeholder="🔍 Carrera / Proyecto">
          <select id="f-estado">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="activa">Activa</option>
          </select>
          <button class="btn-filtrar" onclick="cargarApuestas()"><i class="bi bi-search"></i> Filtrar</button>
          <button class="btn-reset"   onclick="resetFiltros()"><i class="bi bi-x-circle"></i> Reset</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Nombre</th><th>Código</th><th>Carrera</th>
                <th>Correo</th><th>Teléfono</th><th>Tipo</th><th>Estado</th>
                <th>Comprobante</th><th>Fecha</th><th>Acción</th>
              </tr>
            </thead>
            <tbody id="tbl-body">
              <tr class="loading-row"><td colspan="11"><div class="spin"></div> Cargando…</tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 2 - Podio -->
  <div id="tab-podio" class="tab-panel">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-trophy" style="color:var(--accent-l);font-size:1.3rem;"></i>
        <div>
          <h2>Podio del Mundial</h2>
          <p>Selecciona Campeón, Subcampeón, Tercero y Cuarto lugar</p>
        </div>
      </div>
      <div class="card-body">
        <div class="podio-grid" id="podio-grid"></div>
        <div class="form-footer">
          <button class="btn-save" onclick="guardarPodio()"><i class="bi bi-floppy"></i> Guardar Podio</button>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 3 - Grupos -->
  <div id="tab-grupos" class="tab-panel">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-diagram-3" style="color:var(--accent-l);font-size:1.3rem;"></i>
        <div>
          <h2>Posiciones de Grupos</h2>
          <p>Ingresa los clasificados de cada grupo para el cálculo de puntos</p>
        </div>
      </div>
      <div class="card-body">
        <div class="grupos-grid" id="grupos-grid"></div>
        <div class="terceros-section">
          <div class="sec-label">Mejores Terceros Clasificados</div>
          <div class="terceros-checks" id="terceros-checks"></div>
        </div>
        <div class="form-footer">
          <button class="btn-save" onclick="guardarGrupos()"><i class="bi bi-floppy"></i> Guardar Resultados</button>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 4 - Eliminatorias -->
  <div id="tab-eliminatorias" class="tab-panel">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-diagram-3" style="color:var(--accent-l);font-size:1.3rem;"></i>
        <div>
          <h2>Eliminatorias</h2>
          <p>Selecciona los equipos y ganadores de cada fase</p>
        </div>
      </div>
      <div class="card-body">
        <div class="rounds-container" id="rounds-container"></div>
        <div class="form-footer">
          <button class="btn-save" onclick="guardarEliminatorias()"><i class="bi bi-floppy"></i> Guardar Eliminatorias</button>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 5 - Desempate -->
  <div id="tab-desempate" class="tab-panel">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-clipboard-data" style="color:var(--accent-l);font-size:1.3rem;"></i>
        <div>
          <h2>Desempate y Preguntas Extra</h2>
          <p>Resultados reales para comparar con las predicciones</p>
        </div>
      </div>
      <div class="card-body">
        <div class="desempate-grid" id="desempate-grid"></div>
        <div class="form-footer">
          <button class="btn-save" onclick="guardarDesempate()"><i class="bi bi-floppy"></i> Guardar Desempate</button>
        </div>
      </div>
    </div>
  </div>

</div>
<div id="toast"></div>

<script>
const LETRAS = ['A','B','C','D','E','F','G','H','I','J','K','L'];
let listaEquipos = [];

// Definición de las fases de eliminatorias
const RONDAS_ELIMINATORIAS = {
  'R32': { nombre: 'Dieciseisavos (R32)', partidos: 16, ids: Array.from({length:16}, (_,i) => `R32-${i+1}`) },
  'R16': { nombre: 'Octavos (R16)', partidos: 8, ids: Array.from({length:8}, (_,i) => `R16-${i+1}`) },
  'QF': { nombre: 'Cuartos (QF)', partidos: 4, ids: Array.from({length:4}, (_,i) => `QF-${i+1}`) },
  'SF': { nombre: 'Semifinales', partidos: 2, ids: ['SF-1', 'SF-2'] },
  'TP': { nombre: '3er Puesto', partidos: 1, ids: ['TP-1'] },
  'F': { nombre: 'Final', partidos: 1, ids: ['F-1'] }
};

// Cargar todos los datos existentes
async function cargarTodosDatos() {
  try {
    const data = await fetch('?action=get_todos_datos').then(r => r.json());
    listaEquipos = data.equipos || [];
    
    // Construir Podio UI
    construirPodioUI(data.podio);
    
    // Construir Grupos UI
    const gruposMap = {};
    data.grupos.forEach(g => { gruposMap[g.grupo] = g; });
    construirGruposUI(gruposMap, data.terceros);
    
    // Construir Eliminatorias UI
    construirEliminatoriasUI(data.eliminatorias);
    
    // Construir Desempate UI
    construirDesempateUI(data.desempate, data.preguntasExtra);
    
  } catch(e) { 
    console.error(e); 
    toast('Error al cargar datos', 'err'); 
  }
}

// Construir UI del Podio
function construirPodioUI(podioData) {
  const container = document.getElementById('podio-grid');
  const podios = [
    { id: 'campeon', label: '🥇 Campeón', value: podioData.campeon || '' },
    { id: 'subcampeon', label: '🥈 Subcampeón', value: podioData.subcampeon || '' },
    { id: 'tercero', label: '🥉 3er Lugar', value: podioData.tercero || '' },
    { id: 'cuarto', label: '4° Lugar', value: podioData.cuarto || '' }
  ];
  
  container.innerHTML = podios.map(p => `
    <div class="podio-card">
      <div class="rank">${p.label}</div>
      <select id="${p.id}">
        <option value="">Selecciona un equipo</option>
        ${listaEquipos.map(e => `<option value="${esc(e)}" ${p.value === e ? 'selected' : ''}>${e}</option>`).join('')}
      </select>
    </div>
  `).join('');
}

// Construir UI de grupos
function construirGruposUI(gruposData = {}, terceros = []) {
  const grid = document.getElementById('grupos-grid');
  grid.innerHTML = LETRAS.map(g => {
    const info = gruposData[g] || {};
    const fields = ['primero','segundo','tercero','cuarto'].map((pos, i) => `
      <div class="pos-row">
        <span class="pos-num">${i+1}°</span>
        <select data-grupo="${g}" data-pos="${pos}" class="grupo-select">
          <option value="">Seleccionar equipo</option>
          ${listaEquipos.map(e => `<option value="${esc(e)}" ${info[pos] === e ? 'selected' : ''}>${e}</option>`).join('')}
        </select>
      </div>`).join('');
    return `
      <div class="grupo-block">
        <div class="grupo-block-head">
          <h3>⚽ Grupo ${g}</h3>
          <span style="font-size:.65rem;color:var(--text-m)">4 equipos</span>
        </div>
        <div class="grupo-fields">${fields}</div>
      </div>`;
  }).join('');
  
  const tercerosDiv = document.getElementById('terceros-checks');
  tercerosDiv.innerHTML = LETRAS.map(g => {
    const sel = terceros.includes(g);
    return `<label class="tercero-check ${sel?'sel':''}">
      <input type="checkbox" value="${g}" ${sel?'checked':''} onchange="toggleTercero(this)">
      Grupo ${g}
    </label>`;
  }).join('');
}

function toggleTercero(input) {
  input.closest('.tercero-check')
       .classList.toggle('sel', input.checked);
}

// Construir UI de eliminatorias
function construirEliminatoriasUI(eliminatoriasGuardadas = []) {
  const container = document.getElementById('rounds-container');
  container.innerHTML = '';
  
  const partidosMap = {};
  eliminatoriasGuardadas.forEach(p => { partidosMap[p.partido_id] = p; });
  
  const rondas = ['R32', 'R16', 'QF', 'SF', 'TP', 'F'];
  rondas.forEach(ronda => {
    const cfg = RONDAS_ELIMINATORIAS[ronda];
    const col = document.createElement('div');
    col.className = 'round-col';
    col.innerHTML = `<div class="round-title">${cfg.nombre}</div>`;
    
    for (let i = 0; i < cfg.partidos; i++) {
      const partidoId = cfg.ids[i];
      const guardado = partidosMap[partidoId] || {};
      const matchDiv = document.createElement('div');
      matchDiv.className = 'match-card';
      matchDiv.dataset.partidoId = partidoId;
      matchDiv.dataset.ronda = ronda;
      matchDiv.innerHTML = `
        <div class="match-header">Partido ${partidoId}</div>
        <div class="match-team">
          <span class="match-team-name">Equipo 1:</span>
          <select class="team1-select">
            <option value="">Seleccionar</option>
            ${listaEquipos.map(e => `<option value="${esc(e)}" ${guardado.equipo1 === e ? 'selected' : ''}>${e}</option>`).join('')}
          </select>
        </div>
        <div class="match-team">
          <span class="match-team-name">Equipo 2:</span>
          <select class="team2-select">
            <option value="">Seleccionar</option>
            ${listaEquipos.map(e => `<option value="${esc(e)}" ${guardado.equipo2 === e ? 'selected' : ''}>${e}</option>`).join('')}
          </select>
        </div>
        <div class="match-team">
          <span class="match-team-name">🏆 Ganador:</span>
          <select class="winner-select">
            <option value="">Seleccionar ganador</option>
            ${listaEquipos.map(e => `<option value="${esc(e)}" ${guardado.ganador === e ? 'selected' : ''}>${e}</option>`).join('')}
          </select>
        </div>
      `;
      col.appendChild(matchDiv);
    }
    container.appendChild(col);
  });
}

// Construir UI de Desempate
function construirDesempateUI(desempateData, preguntasData) {
  const container = document.getElementById('desempate-grid');
  container.innerHTML = `
    <div class="desempate-field"><label><i class="bi bi-dribbble"></i> Goleador (Bota de Oro)</label><input type="text" id="goleador" value="${esc(desempateData?.goleador || '')}" placeholder="Ej. Kylian Mbappé"></div>
    <div class="desempate-field"><label><i class="bi bi-shield"></i> Mejor Portero</label><input type="text" id="mejor_arquero" value="${esc(desempateData?.mejor_arquero || '')}" placeholder="Ej. Emiliano Martínez"></div>
    <div class="desempate-field"><label><i class="bi bi-123"></i> Goles en la Final</label><input type="number" id="goles_final" value="${desempateData?.goles_final || ''}" placeholder="Ej: 3"></div>
    <div class="desempate-field"><label><i class="bi bi-exclamation-triangle"></i> Tarjetas Rojas totales</label><input type="number" id="tarjetas_rojas" value="${desempateData?.tarjetas_rojas || ''}" placeholder="Total"></div>
    <div class="desempate-field"><label><i class="bi bi-123"></i> Goles en Fase de Grupos</label><input type="number" id="goles_grupos" value="${desempateData?.goles_grupos || ''}" placeholder="Ej: 96"></div>
    <div class="desempate-field"><label><i class="bi bi-lightning-charge"></i> Equipo sorpresa</label><select id="equipo_sorpresa"><option value="">Selecciona</option>${listaEquipos.map(e => `<option value="${esc(e)}" ${preguntasData?.equipo_sorpresa === e ? 'selected' : ''}>${e}</option>`).join('')}</select></div>
    <div class="desempate-field"><label><i class="bi bi-emoji-frown"></i> Equipo decepción</label><select id="equipo_decepcion"><option value="">Selecciona</option>${listaEquipos.map(e => `<option value="${esc(e)}" ${preguntasData?.equipo_decepcion === e ? 'selected' : ''}>${e}</option>`).join('')}</select></div>
    <div class="desempate-field"><label><i class="bi bi-person-bounding-box"></i> Mejor jugador joven</label><input type="text" id="jugador_joven" value="${esc(preguntasData?.jugador_joven || '')}" placeholder="Ej. Jude Bellingham"></div>
    <div class="desempate-field"><label><i class="bi bi-people"></i> Selección con más goles</label><select id="seleccion_goles"><option value="">Selecciona</option>${listaEquipos.map(e => `<option value="${esc(e)}" ${preguntasData?.seleccion_goles === e ? 'selected' : ''}>${e}</option>`).join('')}</select></div>
    <div class="desempate-field"><label><i class="bi bi-shield-x"></i> Selección menos goleada</label><select id="seleccion_defensa"><option value="">Selecciona</option>${listaEquipos.map(e => `<option value="${esc(e)}" ${preguntasData?.seleccion_defensa === e ? 'selected' : ''}>${e}</option>`).join('')}</select></div>
    <div class="desempate-field"><label><i class="bi bi-arrow-repeat"></i> ¿Hubo prórroga en la final?</label><select id="prorroga_final"><option value="">Selecciona</option><option value="si" ${preguntasData?.prorroga_final === 'si' ? 'selected' : ''}>Sí</option><option value="no" ${preguntasData?.prorroga_final === 'no' ? 'selected' : ''}>No</option></select></div>
  `;
}

// Guardar Podio
async function guardarPodio() {
  const fd = new FormData();
  fd.append('action', 'guardar_podio');
  fd.append('campeon', document.getElementById('campeon')?.value || '');
  fd.append('subcampeon', document.getElementById('subcampeon')?.value || '');
  fd.append('tercero', document.getElementById('tercero')?.value || '');
  fd.append('cuarto', document.getElementById('cuarto')?.value || '');
  try {
    const data = await fetch('', { method: 'POST', body: fd }).then(r => r.json());
    toast(data.msg, data.ok ? 'ok' : 'err');
  } catch(e) { toast('Error al guardar', 'err'); }
}

// Guardar Grupos
async function guardarGrupos() {
  const grupos = {};
  document.querySelectorAll('.grupo-select').forEach(sel => {
    const g = sel.dataset.grupo, pos = sel.dataset.pos;
    if (!grupos[g]) grupos[g] = {};
    grupos[g][pos] = sel.value;
  });
  const terceros = [...document.querySelectorAll('#terceros-checks input:checked')].map(i => i.value);
  const fd = new FormData();
  fd.append('action', 'guardar_grupos');
  LETRAS.forEach(g => {
    ['primero','segundo','tercero','cuarto'].forEach(pos =>
      fd.append(`grupos[${g}][${pos}]`, (grupos[g] || {})[pos] || '')
    );
  });
  terceros.forEach(g => fd.append('terceros[]', g));
  try {
    const data = await fetch('', { method: 'POST', body: fd }).then(r => r.json());
    toast(data.msg, data.ok ? 'ok' : 'err');
  } catch(e) { toast('Error al guardar', 'err'); }
}

// Guardar Eliminatorias
async function guardarEliminatorias() {
  const partidos = [];
  document.querySelectorAll('.match-card').forEach(card => {
    const partidoId = card.dataset.partidoId;
    const ronda = card.dataset.ronda;
    const equipo1 = card.querySelector('.team1-select')?.value || '';
    const equipo2 = card.querySelector('.team2-select')?.value || '';
    const ganador = card.querySelector('.winner-select')?.value || '';
    if (equipo1 || equipo2) {
      partidos.push({ ronda, partido_id: partidoId, equipo1, equipo2, ganador });
    }
  });
  const fd = new FormData();
  fd.append('action', 'guardar_eliminatorias');
  fd.append('partidos', JSON.stringify(partidos));
  try {
    const data = await fetch('', { method: 'POST', body: fd }).then(r => r.json());
    toast(data.msg, data.ok ? 'ok' : 'err');
  } catch(e) { toast('Error al guardar', 'err'); }
}

// Guardar Desempate
async function guardarDesempate() {
  const fd = new FormData();
  fd.append('action', 'guardar_desempate');
  fd.append('goleador', document.getElementById('goleador')?.value || '');
  fd.append('mejor_arquero', document.getElementById('mejor_arquero')?.value || '');
  fd.append('goles_final', document.getElementById('goles_final')?.value || '');
  fd.append('tarjetas_rojas', document.getElementById('tarjetas_rojas')?.value || '');
  fd.append('goles_grupos', document.getElementById('goles_grupos')?.value || '');
  fd.append('equipo_sorpresa', document.getElementById('equipo_sorpresa')?.value || '');
  fd.append('equipo_decepcion', document.getElementById('equipo_decepcion')?.value || '');
  fd.append('jugador_joven', document.getElementById('jugador_joven')?.value || '');
  fd.append('seleccion_goles', document.getElementById('seleccion_goles')?.value || '');
  fd.append('seleccion_defensa', document.getElementById('seleccion_defensa')?.value || '');
  fd.append('prorroga_final', document.getElementById('prorroga_final')?.value || '');
  try {
    const data = await fetch('', { method: 'POST', body: fd }).then(r => r.json());
    toast(data.msg, data.ok ? 'ok' : 'err');
  } catch(e) { toast('Error al guardar', 'err'); }
}

// Apuestas
async function cargarApuestas() {
  const body = document.getElementById('tbl-body');
  body.innerHTML = '<tr class="loading-row"><td colspan="11"><div class="spin"></div> Cargando…</tr>';
  const params = new URLSearchParams({
    action: 'listar_apuestas',
    nombre: document.getElementById('f-nombre')?.value || '',
    codigo: document.getElementById('f-codigo')?.value || '',
    carrera: document.getElementById('f-carrera')?.value || '',
    estado: document.getElementById('f-estado')?.value || ''
  });
  try {
    const rows = await fetch('?' + params).then(r => r.json());
    if (!rows.length) {
      body.innerHTML = '<tr class="empty-row"><td colspan="11"><i class="bi bi-inbox"></i> Sin resultados</tr>';
      return;
    }
    body.innerHTML = rows.map(r => `
      <tr>
        <td style="color:var(--text-m);font-size:.72rem">${r.id}</td>
        <td style="font-weight:600">${esc(r.nombre)}</td>
        <td><span class="badge badge-info">${esc(r.codigo)}</span></td>
        <td style="color:var(--text-s)">${esc(r.proyecto || '—')}</td>
        <td style="color:var(--text-s);font-size:.78rem">${esc(r.correo)}</td>
        <td style="color:var(--text-s)">${esc(r.telefono || '—')}</td>
        <td><span class="badge ${r.tipo === 'free' ? 'badge-info' : 'badge-warn'}">${r.tipo === 'free' ? 'Free' : '$3.000'}</span></td>
        <td id="estado-${r.id}"><span class="badge ${r.estado === 'activa' ? 'badge-ok' : 'badge-warn'}">${r.estado}</span></td>
        <td>${r.comprobante ? `<a class="comp-link" href="../comprobantes/${esc(r.comprobante)}" target="_blank"><i class="bi bi-paperclip"></i> Ver</a>` : '<span style="color:var(--text-m);font-size:.75rem">—</span>'}</td>
        <td style="color:var(--text-m);font-size:.75rem;white-space:nowrap">${r.created_at}</td>
        <td><button class="btn-activar" id="btn-${r.id}" ${r.estado === 'activa' ? 'disabled' : ''} onclick="activar(${r.id})">${r.estado === 'activa' ? '<i class="bi bi-check2"></i> Activa' : '<i class="bi bi-lightning"></i> Activar'}</button></td>
      </tr>
    `).join('');
  } catch(e) {
    body.innerHTML = '<tr class="empty-row"><td colspan="11" style="color:var(--err)"><i class="bi bi-exclamation-triangle"></i> Error al cargar</tr>';
  }
}

async function activar(id) {
  const btn = document.getElementById('btn-' + id);
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div>';
  const fd = new FormData();
  fd.append('action', 'activar_apuesta');
  fd.append('id', id);
  try {
    const data = await fetch('', { method: 'POST', body: fd }).then(r => r.json());
    if (data.ok) {
      document.getElementById('estado-' + id).innerHTML = '<span class="badge badge-ok">activa</span>';
      btn.innerHTML = '<i class="bi bi-check2"></i> Activa';
      toast('Apuesta #' + id + ' activada');
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-lightning"></i> Activar';
      toast(data.msg || 'Error', 'err');
    }
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-lightning"></i> Activar';
    toast('Error de red', 'err');
  }
}

function resetFiltros() {
  ['f-nombre','f-codigo','f-carrera'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const estado = document.getElementById('f-estado');
  if (estado) estado.value = '';
  cargarApuestas();
}

function switchTab(id, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  const panel = document.getElementById('tab-' + id);
  if (panel) panel.classList.add('active');
  if (btn) btn.classList.add('active');
  if (id === 'apuestas') cargarApuestas();
}

function toast(msg, tipo = 'ok') {
  const toastContainer = document.getElementById('toast');
  const el = document.createElement('div');
  el.className = 'toast-item ' + tipo;
  el.innerHTML = `<i class="bi bi-${tipo === 'ok' ? 'check-circle' : 'x-circle'}"></i> ${msg}`;
  toastContainer.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function esc(s) {
  return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Inicialización
cargarTodosDatos();
cargarApuestas();

// Event listeners para filtros
['f-nombre','f-codigo','f-carrera'].forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('keydown', e => { if(e.key === 'Enter') cargarApuestas(); });
  }
});
</script>
</body>
</html>