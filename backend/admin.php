<?php
// ============================================================
//  SEGURIDAD — Verificar sesión y rol admin
// ============================================================
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// ============================================================
//  CONFIGURACIÓN DE BASE DE DATOS
// ============================================================
//$host   = 'localhost';
//$db     = 'polla_mundialista';
//$user   = 'root';
//$pass   = '';
//$dsn    = "mysql:host=$host;dbname=$db;charset=utf8mb4";
//$host = "localhost";
//$user = "root";
//$pass = "admin7942_";
//$db = "polla_db";
$host = 'localhost';
$db   = 'polla_db';       // ← tu nombre real
$user = 'root';
$pass = 'admin7942_';  
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
}

// ============================================================
//  HANDLERS AJAX — Responden JSON y terminan
// ============================================================

// 1. Activar apuesta
if (isset($_POST['action']) && $_POST['action'] === 'activar_apuesta') {
    header('Content-Type: application/json');
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit; }

    $stmt = $pdo->prepare(
        "UPDATE predicciones SET estado = 'activa' WHERE id = :id AND tipo = '3000' AND estado = 'pendiente'"
    );
    $stmt->execute([':id' => $id]);
    echo json_encode(['ok' => $stmt->rowCount() > 0, 'msg' => $stmt->rowCount() > 0 ? 'Apuesta activada' : 'Sin cambios']);
    exit;
}

// 2. Guardar resultados de grupos / mejores terceros
if (isset($_POST['action']) && $_POST['action'] === 'guardar_grupos') {
    header('Content-Type: application/json');
    $grupos  = $_POST['grupos']  ?? [];   // array: grupo => [prim, seg, ter, cuar]
    $terceros = $_POST['terceros'] ?? []; // array de grupos clasificados como mejor tercero

    $pdo->beginTransaction();
    try {
        foreach ($grupos as $letra => $pos) {
            $letra = strtoupper(substr($letra, 0, 1));
            $stmt = $pdo->prepare(
                "INSERT INTO real_grupos (grupo, primero, segundo, tercero, cuarto)
                 VALUES (:g, :p1, :p2, :p3, :p4)
                 ON DUPLICATE KEY UPDATE
                   primero  = VALUES(primero),
                   segundo  = VALUES(segundo),
                   tercero  = VALUES(tercero),
                   cuarto   = VALUES(cuarto)"
            );
            $stmt->execute([
                ':g'  => $letra,
                ':p1' => $pos[0] ?? null,
                ':p2' => $pos[1] ?? null,
                ':p3' => $pos[2] ?? null,
                ':p4' => $pos[3] ?? null,
            ]);
        }

        // Limpiar mejores terceros y reinsertar
        $pdo->exec("DELETE FROM real_terceros");
        $ins = $pdo->prepare("INSERT INTO real_terceros (grupo) VALUES (:g)");
        foreach ($terceros as $g) {
            $g = strtoupper(substr($g, 0, 1));
            $ins->execute([':g' => $g]);
        }

        $pdo->commit();
        echo json_encode(['ok' => true, 'msg' => 'Resultados guardados']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 3. Búsqueda / listado de apuestas pendientes (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'listar_apuestas') {
    header('Content-Type: application/json');

    $nombre  = '%' . trim($_GET['nombre']  ?? '') . '%';
    $codigo  = '%' . trim($_GET['codigo']  ?? '') . '%';
    $carrera = '%' . trim($_GET['carrera'] ?? '') . '%';
    $estado  =       trim($_GET['estado']  ?? '');

    $sql = "SELECT p.id, u.nombre, u.codigo, u.correo, u.telefono, u.proyecto,
                   p.tipo, p.estado, p.comprobante, p.created_at
            FROM predicciones p
            JOIN usuario u ON u.id = p.id_usuario
            WHERE p.tipo = '3000'
              AND u.nombre   LIKE :nombre
              AND u.codigo   LIKE :codigo
              AND u.proyecto LIKE :carrera";

    $params = [':nombre' => $nombre, ':codigo' => $codigo, ':carrera' => $carrera];

    if ($estado !== '') {
        $sql .= " AND p.estado = :estado";
        $params[':estado'] = $estado;
    }
    $sql .= " ORDER BY p.created_at DESC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

// 4. Obtener resultados actuales de grupos
if (isset($_GET['action']) && $_GET['action'] === 'get_grupos') {
    header('Content-Type: application/json');
    $grupos   = $pdo->query("SELECT * FROM real_grupos ORDER BY grupo")->fetchAll();
    $terceros = $pdo->query("SELECT grupo FROM real_terceros")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['grupos' => $grupos, 'terceros' => $terceros]);
    exit;
}

// ============================================================
//  ESTADÍSTICAS RÁPIDAS PARA EL DASHBOARD
// ============================================================
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(estado = 'activa')   AS activas,
        SUM(estado = 'pendiente') AS pendientes,
        SUM(tipo   = 'free')     AS gratuitas
    FROM predicciones
")->fetch();

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
<style>
/* ── Variables & Reset ───────────────────────────── */
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0a0a0f;
  --bg-card:#111118;
  --bg-el:#1a1a24;
  --bg-el2:#22222e;
  --accent:#085F9A;
  --accent-l:#3877ff;
  --accent-g:rgba(8,95,154,0.22);
  --text:#f0f0f8;
  --text-s:#9090b0;
  --text-m:#5a5a78;
  --border:#252535;
  --border-l:#30304a;
  --ok:#22c55e;
  --ok-bg:rgba(34,197,94,0.1);
  --warn:#f59e0b;
  --warn-bg:rgba(245,158,11,0.1);
  --err:#ef4444;
  --err-bg:rgba(239,68,68,0.1);
}
html{scroll-behavior:smooth;}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;line-height:1.5;}

/* ── Nav ─────────────────────────────────────────── */
nav{position:sticky;top:0;z-index:200;background:rgba(10,10,15,0.95);backdrop-filter:blur(18px);border-bottom:1px solid var(--border);padding:.85rem 1.5rem;display:flex;align-items:center;gap:.75rem;}
.logo{display:flex;align-items:center;gap:.55rem;font-weight:800;font-size:1.05rem;text-decoration:none;color:var(--text);margin-right:auto;}
.logo-icon{width:30px;height:30px;background:var(--accent);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.9rem;}
.nav-badge{font-size:.72rem;font-weight:700;color:var(--ok);background:var(--ok-bg);border:1px solid rgba(34,197,94,0.3);border-radius:100px;padding:.2rem .7rem;display:flex;align-items:center;gap:.35rem;}
.nav-badge span{animation:blink 1.4s infinite;}
.nav-user{font-size:.8rem;color:var(--text-s);display:flex;align-items:center;gap:.4rem;}
.btn-logout{font-size:.78rem;font-weight:700;background:var(--err-bg);color:var(--err);border:1px solid rgba(239,68,68,0.3);border-radius:7px;padding:.35rem .85rem;text-decoration:none;transition:all .2s;}
.btn-logout:hover{background:var(--err);color:#fff;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.3;}}

/* ── Layout ──────────────────────────────────────── */
.page{max-width:1100px;margin:0 auto;padding:2rem 1.25rem 4rem;}

/* ── Stats cards ─────────────────────────────────── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.85rem;margin-bottom:2rem;}
@media(max-width:700px){.stats-row{grid-template-columns:1fr 1fr;}}
.stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:13px;padding:1.1rem 1.25rem;display:flex;flex-direction:column;gap:.3rem;}
.stat-card .s-label{font-size:.72rem;font-weight:600;color:var(--text-m);text-transform:uppercase;letter-spacing:.08em;}
.stat-card .s-val{font-size:1.75rem;font-weight:800;letter-spacing:-.03em;}
.stat-card .s-sub{font-size:.72rem;color:var(--text-m);}
.c-ok{color:var(--ok);} .c-warn{color:var(--warn);} .c-acc{color:var(--accent-l);} .c-text{color:var(--text);}

/* ── Section Tabs ────────────────────────────────── */
.tabs{display:flex;gap:.4rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.tab-btn{background:transparent;border:1px solid var(--border-l);color:var(--text-m);padding:.45rem 1rem;border-radius:100px;font-family:inherit;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;}
.tab-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.tab-btn:not(.active):hover{border-color:var(--accent-l);color:var(--accent-l);}
.tab-panel{display:none;} .tab-panel.active{display:block;}

/* ── Card ────────────────────────────────────────── */
.card{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:1.5rem;}
.card-header{padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.75rem;}
.card-header h2{font-size:1rem;font-weight:800;letter-spacing:-.02em;}
.card-header p{font-size:.8rem;color:var(--text-s);margin-top:.1rem;}
.card-body{padding:1.5rem;}

/* ── Filtros ─────────────────────────────────────── */
.filters{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.7rem;margin-bottom:1.25rem;}
.filters input,.filters select{background:var(--bg-el);border:1px solid var(--border-l);border-radius:9px;padding:.55rem .85rem;font-family:inherit;font-size:.84rem;color:var(--text);outline:none;transition:border-color .2s;width:100%;}
.filters input:focus,.filters select:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-g);}
select option{background:var(--bg-el);}
.btn-filtrar{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:.55rem 1.2rem;font-family:inherit;font-size:.84rem;font-weight:700;cursor:pointer;transition:all .2s;white-space:nowrap;}
.btn-filtrar:hover{background:var(--accent-l);}
.btn-reset{background:transparent;border:1px solid var(--border-l);color:var(--text-s);border-radius:9px;padding:.55rem .9rem;font-family:inherit;font-size:.84rem;cursor:pointer;transition:all .2s;}
.btn-reset:hover{border-color:var(--err);color:var(--err);}

/* ── Tabla apuestas ──────────────────────────────── */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:.82rem;}
thead th{padding:.6rem .9rem;text-align:left;font-size:.67rem;font-weight:700;color:var(--text-m);text-transform:uppercase;letter-spacing:.08em;background:var(--bg-el2);border-bottom:1px solid var(--border);white-space:nowrap;}
tbody td{padding:.55rem .9rem;border-bottom:1px solid var(--border);vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr{transition:background .1s;}
tbody tr:hover{background:var(--bg-el);}

.badge{display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:100px;}
.badge-ok{background:var(--ok-bg);color:var(--ok);border:1px solid rgba(34,197,94,.3);}
.badge-warn{background:var(--warn-bg);color:var(--warn);border:1px solid rgba(245,158,11,.3);}
.badge-err{background:var(--err-bg);color:var(--err);border:1px solid rgba(239,68,68,.3);}
.badge-info{background:var(--accent-g);color:var(--accent-l);border:1px solid rgba(56,119,255,.3);}

.btn-activar{background:var(--ok-bg);color:var(--ok);border:1px solid rgba(34,197,94,.3);border-radius:7px;padding:.3rem .75rem;font-family:inherit;font-size:.75rem;font-weight:700;cursor:pointer;transition:all .2s;white-space:nowrap;}
.btn-activar:hover{background:var(--ok);color:#fff;}
.btn-activar:disabled{opacity:.4;cursor:default;}

.empty-row td{text-align:center;color:var(--text-m);padding:2.5rem;font-size:.9rem;}
.loading-row td{text-align:center;color:var(--text-m);padding:2.5rem;}

/* ── Comprobante mini ────────────────────────────── */
.comp-link{color:var(--accent-l);text-decoration:none;font-size:.75rem;}
.comp-link:hover{text-decoration:underline;}

/* ── Grupos Form ─────────────────────────────────── */
.grupos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;}
.grupo-block{background:var(--bg-el);border:1px solid var(--border);border-radius:11px;overflow:hidden;}
.grupo-block-head{padding:.5rem .85rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--bg-el2);}
.grupo-block-head h3{font-size:.82rem;font-weight:800;}
.grupo-fields{padding:.75rem;display:flex;flex-direction:column;gap:.45rem;}
.pos-row{display:flex;align-items:center;gap:.5rem;}
.pos-num{font-size:.65rem;font-weight:700;color:var(--text-m);width:1.1rem;text-align:right;flex-shrink:0;}
.pos-row input{background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:.38rem .6rem;font-family:inherit;font-size:.78rem;color:var(--text);outline:none;flex:1;min-width:0;transition:border-color .2s;}
.pos-row input:focus{border-color:var(--accent);}

/* ── Terceros section ────────────────────────────── */
.terceros-section{margin-top:1.25rem;}
.sec-label{font-size:.68rem;font-weight:700;color:var(--accent-l);text-transform:uppercase;letter-spacing:.12em;display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;}
.sec-label::after{content:'';flex:1;height:1px;background:var(--border);}
.terceros-checks{display:flex;flex-wrap:wrap;gap:.5rem;}
.tercero-check{display:flex;align-items:center;gap:.35rem;background:var(--bg-el);border:1px solid var(--border-l);border-radius:8px;padding:.35rem .75rem;cursor:pointer;transition:all .2s;font-size:.8rem;font-weight:600;}
.tercero-check input{display:none;}
.tercero-check.sel{background:var(--ok-bg);border-color:rgba(34,197,94,.5);color:var(--ok);}
.tercero-check:hover{border-color:var(--ok);}

/* ── Toast ───────────────────────────────────────── */
#toast{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;}
.toast-item{background:var(--bg-card);border:1px solid var(--border-l);border-radius:10px;padding:.75rem 1.1rem;font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:.5rem;box-shadow:0 8px 24px rgba(0,0,0,.5);animation:toastIn .25s ease;max-width:320px;}
.toast-item.ok{border-color:rgba(34,197,94,.4);color:var(--ok);}
.toast-item.err{border-color:rgba(239,68,68,.4);color:var(--err);}
@keyframes toastIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

/* ── Spinner ─────────────────────────────────────── */
.spin{display:inline-block;width:14px;height:14px;border:2px solid var(--border-l);border-top-color:var(--accent-l);border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}

/* ── Btn guardar ─────────────────────────────────── */
.btn-save{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:.65rem 1.6rem;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:.5rem;}
.btn-save:hover{background:var(--accent-l);transform:translateY(-1px);}
.form-footer{margin-top:1.25rem;display:flex;justify-content:flex-end;}
</style>
</head>
<body>

<!-- ══ NAV ══════════════════════════════════════════ -->
<nav>
  <a class="logo" href="#">
    <div class="logo-icon">⚽</div>
    Admin · Polla
  </a>
  <div class="nav-badge"><span>●</span> Panel activo</div>
  <div class="nav-user"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($_SESSION['usuario']) ?></div>
  <a class="btn-logout" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a>
</nav>

<!-- ══ PÁGINA ════════════════════════════════════════ -->
<div class="page">

  <!-- Stats rápidos -->
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
    <button class="tab-btn" onclick="switchTab('grupos',this)">
      <i class="bi bi-trophy"></i> Resultados de Grupos
    </button>
  </div>

  <!-- ════════════════════════════════════
       TAB 1 — GESTIÓN DE APUESTAS
  ════════════════════════════════════ -->
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

        <!-- Filtros -->
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

        <!-- Tabla -->
        <div class="table-wrap">
          <table id="tbl-apuestas">
            <thead>
              <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Código</th>
                <th>Carrera</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Comprobante</th>
                <th>Fecha</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody id="tbl-body">
              <tr class="loading-row"><td colspan="11"><div class="spin"></div> Cargando…</td></tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div><!-- /tab-apuestas -->

  <!-- ════════════════════════════════════
       TAB 2 — RESULTADOS DE GRUPOS
  ════════════════════════════════════ -->
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

        <div class="grupos-grid" id="grupos-grid">
          <!-- Generado por JS -->
        </div>

        <!-- Mejores terceros -->
        <div class="terceros-section">
          <div class="sec-label">Mejores Terceros Clasificados</div>
          <div class="terceros-checks" id="terceros-checks">
            <!-- Generado por JS -->
          </div>
        </div>

        <div class="form-footer">
          <button class="btn-save" onclick="guardarGrupos()">
            <i class="bi bi-floppy"></i> Guardar Resultados
          </button>
        </div>
      </div>
    </div>
  </div><!-- /tab-grupos -->

</div><!-- /page -->

<!-- Toast container -->
<div id="toast"></div>

<!-- ══ SCRIPTS ════════════════════════════════════════ -->
<script>
// ──────────────────────────────────────────
//  TABS
// ──────────────────────────────────────────
function switchTab(id, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  btn.classList.add('active');
  if (id === 'grupos') cargarGrupos();
}

// ──────────────────────────────────────────
//  TOAST
// ──────────────────────────────────────────
function toast(msg, tipo = 'ok') {
  const el = document.createElement('div');
  el.className = 'toast-item ' + tipo;
  el.innerHTML = `<i class="bi bi-${tipo === 'ok' ? 'check-circle' : 'x-circle'}"></i> ${msg}`;
  document.getElementById('toast').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ──────────────────────────────────────────
//  TAB 1 — APUESTAS
// ──────────────────────────────────────────
async function cargarApuestas() {
  const body = document.getElementById('tbl-body');
  body.innerHTML = '<tr class="loading-row"><td colspan="11"><div class="spin"></div> Cargando…</td></tr>';

  const params = new URLSearchParams({
    action:  'listar_apuestas',
    nombre:  document.getElementById('f-nombre').value,
    codigo:  document.getElementById('f-codigo').value,
    carrera: document.getElementById('f-carrera').value,
    estado:  document.getElementById('f-estado').value,
  });

  try {
    const res  = await fetch('?' + params);
    const rows = await res.json();

    if (!rows.length) {
      body.innerHTML = '<tr class="empty-row"><td colspan="11"><i class="bi bi-inbox"></i> Sin resultados</td></tr>';
      return;
    }

    body.innerHTML = rows.map(r => `
      <tr id="row-${r.id}">
        <td style="color:var(--text-m);font-size:.72rem">${r.id}</td>
        <td style="font-weight:600">${esc(r.nombre)}</td>
        <td><span class="badge badge-info">${esc(r.codigo)}</span></td>
        <td style="color:var(--text-s)">${esc(r.proyecto || '—')}</td>
        <td style="color:var(--text-s);font-size:.78rem">${esc(r.correo)}</td>
        <td style="color:var(--text-s)">${esc(r.telefono || '—')}</td>
        <td><span class="badge ${r.tipo === 'free' ? 'badge-info' : 'badge-warn'}">${r.tipo === 'free' ? 'Free' : '$3.000'}</span></td>
        <td id="estado-${r.id}"><span class="badge ${r.estado === 'activa' ? 'badge-ok' : 'badge-warn'}">${r.estado}</span></td>
        <td>${r.comprobante ? `<a class="comp-link" href="${esc(r.comprobante)}" target="_blank"><i class="bi bi-paperclip"></i> Ver</a>` : '<span style="color:var(--text-m);font-size:.75rem">—</span>'}</td>
        <td style="color:var(--text-m);font-size:.75rem;white-space:nowrap">${r.created_at}</td>
        <td>
          <button class="btn-activar"
            id="btn-${r.id}"
            ${r.estado === 'activa' ? 'disabled' : ''}
            onclick="activar(${r.id})">
            ${r.estado === 'activa' ? '<i class="bi bi-check2"></i> Activa' : '<i class="bi bi-lightning"></i> Activar'}
          </button>
        </td>
      </tr>
    `).join('');
  } catch(e) {
    body.innerHTML = '<tr class="empty-row"><td colspan="11" style="color:var(--err)"><i class="bi bi-exclamation-triangle"></i> Error al cargar</td></tr>';
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
    const res = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      document.getElementById('estado-' + id).innerHTML = '<span class="badge badge-ok">activa</span>';
      btn.innerHTML = '<i class="bi bi-check2"></i> Activa';
      toast('Apuesta #' + id + ' activada correctamente');
      // Actualizar stat pendientes en pantalla
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-lightning"></i> Activar';
      toast(data.msg || 'Error al activar', 'err');
    }
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-lightning"></i> Activar';
    toast('Error de red', 'err');
  }
}

function resetFiltros() {
  ['f-nombre','f-codigo','f-carrera'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('f-estado').value = '';
  cargarApuestas();
}

// Filtrar con Enter
['f-nombre','f-codigo','f-carrera'].forEach(id => {
  document.getElementById(id).addEventListener('keydown', e => { if(e.key === 'Enter') cargarApuestas(); });
});

// ──────────────────────────────────────────
//  TAB 2 — GRUPOS
// ──────────────────────────────────────────
const LETRAS  = ['A','B','C','D','E','F','G','H'];
const POSICIONES = ['1°','2°','3°','4°'];

function buildGruposUI(data = {}, terceros = []) {
  const grid = document.getElementById('grupos-grid');
  const terc = document.getElementById('terceros-checks');

  grid.innerHTML = LETRAS.map(g => {
    const info = data[g] || {};
    const fields = ['primero','segundo','tercero','cuarto'].map((pos, i) => `
      <div class="pos-row">
        <span class="pos-num">${i+1}</span>
        <input data-grupo="${g}" data-pos="${pos}" type="text"
               placeholder="Equipo ${i+1}"
               value="${esc(info[pos] || '')}">
      </div>
    `).join('');

    return `
      <div class="grupo-block">
        <div class="grupo-block-head">
          <h3>⚽ Grupo ${g}</h3>
          <span style="font-size:.65rem;color:var(--text-m)">4 equipos</span>
        </div>
        <div class="grupo-fields">${fields}</div>
      </div>`;
  }).join('');

  terc.innerHTML = LETRAS.map(g => {
    const sel = terceros.includes(g);
    return `
      <label class="tercero-check ${sel ? 'sel' : ''}" onclick="toggleTercero(this)">
        <input type="checkbox" value="${g}" ${sel ? 'checked' : ''}> Grupo ${g}
      </label>`;
  }).join('');
}

function toggleTercero(label) {
  label.classList.toggle('sel');
  label.querySelector('input').checked = label.classList.contains('sel');
}

async function cargarGrupos() {
  try {
    const res  = await fetch('?action=get_grupos');
    const data = await res.json();
    // Convertir array a objeto indexado por letra
    const gruposMap = {};
    data.grupos.forEach(g => { gruposMap[g.grupo] = g; });
    buildGruposUI(gruposMap, data.terceros);
  } catch(e) {
    buildGruposUI();
  }
}

async function guardarGrupos() {
  // Recoger grupos
  const grupos = {};
  document.querySelectorAll('#grupos-grid input').forEach(inp => {
    const g   = inp.dataset.grupo;
    const pos = inp.dataset.pos;
    if (!grupos[g]) grupos[g] = {};
    grupos[g][pos] = inp.value.trim();
  });

  // Recoger terceros
  const terceros = [...document.querySelectorAll('#terceros-checks input:checked')].map(i => i.value);

  const fd = new FormData();
  fd.append('action', 'guardar_grupos');
  // Serializar manualmente para PHP
  LETRAS.forEach(g => {
    const info = grupos[g] || {};
    ['primero','segundo','tercero','cuarto'].forEach(pos => {
      fd.append(`grupos[${g}][${pos}]`, info[pos] || '');
    });
  });
  terceros.forEach(g => fd.append('terceros[]', g));

  try {
    const res  = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    toast(data.msg || 'Guardado', data.ok ? 'ok' : 'err');
  } catch(e) {
    toast('Error al guardar', 'err');
  }
}

// ──────────────────────────────────────────
//  HELPERS
// ──────────────────────────────────────────
function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ──────────────────────────────────────────
//  INIT
// ──────────────────────────────────────────
cargarApuestas();
buildGruposUI(); // Estructura vacía inmediata; se llena al abrir tab
</script>
</body>
</html>
