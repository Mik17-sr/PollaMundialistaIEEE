<?php
// ============================================================
//  SEGURIDAD — Verificar sesión y rol admin
// ============================================================
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

// ============================================================
//  HANDLERS AJAX
// ============================================================

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

// 2. Guardar grupos y mejores terceros
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
        echo json_encode(['ok' => true, 'msg' => 'Resultados guardados']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 3. Listar apuestas con filtros
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

// 4. Obtener resultados actuales de grupos
if (isset($_GET['action']) && $_GET['action'] === 'get_grupos') {
    header('Content-Type: application/json');
    $grupos   = $conn->query("SELECT * FROM real_grupos ORDER BY grupo")->fetch_all(MYSQLI_ASSOC);
    $raw      = $conn->query("SELECT grupo FROM real_terceros")->fetch_all(MYSQLI_ASSOC);
    $terceros = array_column($raw, 'grupo');
    echo json_encode(['grupos' => $grupos, 'terceros' => $terceros]);
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
</head>
<body>

<nav>
  <a class="logo" href="#">
    <div class="logo-icon">⚽</div>
    Admin · Polla
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
    <button class="tab-btn" onclick="switchTab('grupos',this)">
      <i class="bi bi-trophy"></i> Resultados de Grupos
    </button>
  </div>

  <!-- TAB 1 — APUESTAS -->
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
              <tr class="loading-row"><td colspan="11"><div class="spin"></div> Cargando…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 2 — GRUPOS -->
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
          <button class="btn-save" onclick="guardarGrupos()">
            <i class="bi bi-floppy"></i> Guardar Resultados
          </button>
        </div>
      </div>
    </div>
  </div>

</div>
<div id="toast"></div>

<script>
function switchTab(id, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  btn.classList.add('active');
  if (id === 'grupos') cargarGrupos();
}

function toast(msg, tipo = 'ok') {
  const el = document.createElement('div');
  el.className = 'toast-item ' + tipo;
  el.innerHTML = `<i class="bi bi-${tipo === 'ok' ? 'check-circle' : 'x-circle'}"></i> ${msg}`;
  document.getElementById('toast').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ── Apuestas ──────────────────────────────────────
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
    const rows = await fetch('?' + params).then(r => r.json());
    if (!rows.length) {
      body.innerHTML = '<tr class="empty-row"><td colspan="11"><i class="bi bi-inbox"></i> Sin resultados</td></tr>';
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
        <td>${r.comprobante
          ? `<a class="comp-link" href="../comprobantes/${esc(r.comprobante)}" target="_blank"><i class="bi bi-paperclip"></i> Ver</a>`
          : '<span style="color:var(--text-m);font-size:.75rem">—</span>'}</td>
        <td style="color:var(--text-m);font-size:.75rem;white-space:nowrap">${r.created_at}</td>
        <td>
          <button class="btn-activar" id="btn-${r.id}"
            ${r.estado === 'activa' ? 'disabled' : ''}
            onclick="activar(${r.id})">
            ${r.estado === 'activa'
              ? '<i class="bi bi-check2"></i> Activa'
              : '<i class="bi bi-lightning"></i> Activar'}
          </button>
        </td>
      </tr>`).join('');
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
  ['f-nombre','f-codigo','f-carrera'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('f-estado').value = '';
  cargarApuestas();
}
['f-nombre','f-codigo','f-carrera'].forEach(id =>
  document.getElementById(id).addEventListener('keydown', e => { if(e.key==='Enter') cargarApuestas(); })
);

// ── Grupos ────────────────────────────────────────
const LETRAS = ['A','B','C','D','E','F','G','H'];

function buildGruposUI(data = {}, terceros = []) {
  document.getElementById('grupos-grid').innerHTML = LETRAS.map(g => {
    const info = data[g] || {};
    const fields = ['primero','segundo','tercero','cuarto'].map((pos, i) => `
      <div class="pos-row">
        <span class="pos-num">${i+1}</span>
        <input data-grupo="${g}" data-pos="${pos}" type="text"
               placeholder="Equipo ${i+1}" value="${esc(info[pos] || '')}">
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

  document.getElementById('terceros-checks').innerHTML = LETRAS.map(g => {
    const sel = terceros.includes(g);
    return `<label class="tercero-check ${sel?'sel':''}" onclick="toggleTercero(this)">
      <input type="checkbox" value="${g}" ${sel?'checked':''}> Grupo ${g}
    </label>`;
  }).join('');
}

function toggleTercero(label) {
  label.classList.toggle('sel');
  label.querySelector('input').checked = label.classList.contains('sel');
}

async function cargarGrupos() {
  try {
    const data = await fetch('?action=get_grupos').then(r => r.json());
    const map  = {};
    data.grupos.forEach(g => { map[g.grupo] = g; });
    buildGruposUI(map, data.terceros);
  } catch(e) { buildGruposUI(); }
}

async function guardarGrupos() {
  const grupos = {};
  document.querySelectorAll('#grupos-grid input').forEach(inp => {
    const g = inp.dataset.grupo, pos = inp.dataset.pos;
    if (!grupos[g]) grupos[g] = {};
    grupos[g][pos] = inp.value.trim();
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
    toast(data.msg || 'Guardado', data.ok ? 'ok' : 'err');
  } catch(e) { toast('Error al guardar', 'err'); }
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

cargarApuestas();
buildGruposUI();
</script>
</body>
</html>
