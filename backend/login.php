<?php
session_start();
if (isset($_SESSION['usuario']) && $_SESSION['rol'] === 'admin') {
    header('Location: admin.php');
    exit;
}

require_once 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_input = trim($_POST['usuario'] ?? '');
    $pass_input    = $_POST['password'] ?? '';

    // Rate limiting por sesión
    if (!isset($_SESSION['intentos']))      $_SESSION['intentos']      = 0;
    if (!isset($_SESSION['ultimo_intento'])) $_SESSION['ultimo_intento'] = 0;

    if ($_SESSION['intentos'] >= 5) {
        $restantes = 30 - (time() - $_SESSION['ultimo_intento']);
        if ($restantes > 0) {
            $error = "Demasiados intentos. Espera {$restantes} segundos.";
        } else {
            $_SESSION['intentos'] = 0;
        }
    }

    if (!$error && $usuario_input && $pass_input) {
        $stmt = $conn->prepare("SELECT id, nombre, password_hash FROM admins WHERE usuario = ? LIMIT 1");
        $stmt->bind_param('s', $usuario_input);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($pass_input, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario']  = $admin['nombre'];
            $_SESSION['rol']      = 'admin';
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['intentos'] = 0;
            header('Location: admin.php');
            exit;
        } else {
            $_SESSION['intentos']++;
            $_SESSION['ultimo_intento'] = time();
            sleep(1);
            $error = 'Usuario o contraseña incorrectos.';
        }
    } elseif (!$error) {
        $error = 'Completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Admin — Polla Mundialista</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0a0a0f;--bg-card:#111118;--bg-el:#1a1a24;
  --accent:#085F9A;--accent-l:#3877ff;--accent-g:rgba(8,95,154,0.22);
  --text:#f0f0f8;--text-s:#9090b0;--text-m:#5a5a78;
  --border:#252535;--border-l:#30304a;
  --ok:#22c55e;--ok-bg:rgba(34,197,94,0.1);
  --err:#ef4444;--err-bg:rgba(239,68,68,0.1);
}
html,body{height:100%;}
body{
  font-family:'Space Grotesk',sans-serif;
  background:var(--bg);color:var(--text);
  display:flex;align-items:center;justify-content:center;
  min-height:100vh;padding:1.5rem;overflow:hidden;position:relative;
}
body::before{
  content:'';position:fixed;top:-30%;left:-10%;width:70%;height:80%;
  background:radial-gradient(ellipse,rgba(8,95,154,0.07) 0%,transparent 65%);
  pointer-events:none;
}
body::after{
  content:'';position:fixed;bottom:-20%;right:-10%;width:60%;height:70%;
  background:radial-gradient(ellipse,rgba(56,119,255,0.05) 0%,transparent 65%);
  pointer-events:none;
}
.login-card{
  background:var(--bg-card);border:1px solid var(--border);border-radius:20px;
  width:100%;max-width:420px;overflow:hidden;position:relative;z-index:1;
  box-shadow:0 32px 80px rgba(0,0,0,.6);
  animation:cardIn .4s cubic-bezier(.22,1,.36,1);
}
@keyframes cardIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.card-top{padding:2rem 2rem 1.5rem;border-bottom:1px solid var(--border);text-align:center;}
.logo-wrap{
  width:52px;height:52px;background:var(--accent);border-radius:14px;
  display:flex;align-items:center;justify-content:center;font-size:1.5rem;
  margin:0 auto 1.25rem;box-shadow:0 0 30px var(--accent-g);
}
.card-top h1{font-size:1.25rem;font-weight:800;letter-spacing:-.02em;margin-bottom:.3rem;}
.card-top p{font-size:.82rem;color:var(--text-m);}
.card-body{padding:1.75rem 2rem 2rem;}
.alert-err{
  background:var(--err-bg);border:1px solid rgba(239,68,68,.35);border-radius:10px;
  padding:.75rem 1rem;font-size:.82rem;color:var(--err);
  display:flex;align-items:center;gap:.5rem;margin-bottom:1.25rem;
  animation:shake .35s ease;
}
@keyframes shake{0%,100%{transform:translateX(0);}20%{transform:translateX(-6px);}40%{transform:translateX(6px);}60%{transform:translateX(-4px);}80%{transform:translateX(4px);}}
.field{display:flex;flex-direction:column;gap:.35rem;margin-bottom:1rem;}
.field label{font-size:.78rem;font-weight:600;color:var(--text-s);}
.input-wrap{position:relative;}
.input-wrap .ico{
  position:absolute;left:.85rem;top:50%;transform:translateY(-50%);
  color:var(--text-m);font-size:.95rem;pointer-events:none;transition:color .2s;
}
.input-wrap input{
  width:100%;background:var(--bg-el);border:1px solid var(--border-l);border-radius:10px;
  padding:.7rem .9rem .7rem 2.5rem;font-family:inherit;font-size:.9rem;color:var(--text);
  outline:none;transition:border-color .2s,box-shadow .2s;
}
.input-wrap input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-g);}
.input-wrap.has-eye input{padding-right:2.5rem;}
.btn-eye{
  position:absolute;right:.75rem;top:50%;transform:translateY(-50%);
  background:none;border:none;color:var(--text-m);cursor:pointer;font-size:1rem;
  padding:.2rem;transition:color .2s;
}
.btn-eye:hover{color:var(--accent-l);}
.attempts-info{font-size:.72rem;color:var(--text-m);text-align:right;margin-top:.35rem;}
.attempts-info.warn{color:var(--err);}
.btn-login{
  width:100%;background:var(--accent);color:#fff;border:none;border-radius:10px;
  padding:.8rem;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;
  transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;
  margin-top:1.5rem;
}
.btn-login:hover{background:var(--accent-l);transform:translateY(-1px);box-shadow:0 10px 28px var(--accent-g);}
.btn-login:disabled{opacity:.5;cursor:default;transform:none;}
.spin{
  display:inline-block;width:16px;height:16px;
  border:2px solid rgba(255,255,255,.3);border-top-color:#fff;
  border-radius:50%;animation:spin .7s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg);}}
.card-footer{padding:.85rem 2rem 1.25rem;border-top:1px solid var(--border);text-align:center;}
.card-footer p{font-size:.75rem;color:var(--text-m);}
.card-footer a{color:var(--accent-l);text-decoration:none;}
.card-footer a:hover{text-decoration:underline;}
</style>
</head>
<body>

<div class="login-card">
  <div class="card-top">
    <div class="logo-wrap">⚽</div>
    <h1>Panel Administrativo</h1>
    <p>Polla Mundialista IEEE — Acceso restringido</p>
  </div>

  <div class="card-body">
    <?php if ($error): ?>
    <div class="alert-err">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="login-form" autocomplete="off" novalidate>

      <div class="field">
        <label for="usuario">Usuario administrador</label>
        <div class="input-wrap">
          <input type="text" id="usuario" name="usuario"
                 placeholder="tu_usuario"
                 value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                 autocomplete="username" required>
          <i class="bi bi-person ico"></i>
        </div>
      </div>

      <div class="field">
        <label for="password">Contraseña</label>
        <div class="input-wrap has-eye">
          <input type="password" id="password" name="password"
                 placeholder="••••••••"
                 autocomplete="current-password" required>
          <i class="bi bi-lock ico"></i>
          <button type="button" class="btn-eye" id="btn-eye">
            <i class="bi bi-eye" id="eye-icon"></i>
          </button>
        </div>
        <?php
          $intentos  = $_SESSION['intentos'] ?? 0;
          $restantes = 5 - $intentos;
          if ($intentos > 0 && $intentos < 5):
        ?>
        <span class="attempts-info warn">
          <i class="bi bi-shield-exclamation"></i>
          <?= $restantes ?> intento<?= $restantes !== 1 ? 's' : '' ?> restante<?= $restantes !== 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-login" id="btn-login">
        <i class="bi bi-shield-lock"></i> Ingresar al panel
      </button>
    </form>
  </div>

  <div class="card-footer">
    <p>¿Problemas para acceder? <a href="../index.html">Volver al inicio</a></p>
  </div>
</div>

<script>
document.getElementById('btn-eye').addEventListener('click', function () {
  const inp  = document.getElementById('password');
  const icon = document.getElementById('eye-icon');
  const show = inp.type === 'password';
  inp.type       = show ? 'text' : 'password';
  icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});

document.getElementById('login-form').addEventListener('submit', function () {
  const btn = document.getElementById('btn-login');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Verificando…';
});
</script>
</body>
</html>
