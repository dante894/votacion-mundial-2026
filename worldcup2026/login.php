<?php
require_once __DIR__ . '/includes/bootstrap.php';

if(isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_verify($_POST['csrf'] ?? '')) { $error = 'Error de seguridad. Recarga la página.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $db    = getDB();
        $stmt  = $db->prepare("SELECT * FROM users WHERE email = ? AND password IS NOT NULL");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if($user && password_verify($pass, $user['password'])) {
            loginUser($user);
            header('Location: ' . ($_GET['redirect'] ?? 'index.php'));
            exit;
        }
        $error = 'Correo o contraseña incorrectos';
    }
}

// Iniciar OAuth Google
if(isset($_GET['google'])) {
    $_SESSION['oauth_redirect'] = $_GET['redirect'] ?? 'index.php';
    header('Location: ' . getGoogleAuthUrl());
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Iniciar Sesión — World Cup 2026</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root { --bg:#070a0f; --card:#0d1117; --border:#1e2832; --gold:#f0b429; --text:#e8edf3; --muted:#5a6a7a; --red:#ef4444; }
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:grid;place-items:center;
  background-image:radial-gradient(ellipse 70% 50% at 50% -10%,rgba(240,180,41,.08),transparent 70%);}
.card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:40px;width:100%;max-width:400px;}
.logo{text-align:center;margin-bottom:32px}
.logo .trophy{font-size:48px;display:block;margin-bottom:8px}
.logo h1{font-family:'Bebas Neue',sans-serif;font-size:28px;letter-spacing:3px;
  background:linear-gradient(135deg,var(--gold),#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.logo p{color:var(--muted);font-size:13px;margin-top:4px}
.btn-google{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:12px;border-radius:10px;
  background:#fff;color:#111;border:none;font-size:14px;font-weight:600;
  cursor:pointer;transition:opacity .2s;text-decoration:none;
}
.btn-google:hover{opacity:.9}
.btn-google svg{width:20px;height:20px;flex-shrink:0}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--muted);font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:6px;letter-spacing:.5px;text-transform:uppercase}
input[type=email],input[type=password]{
  width:100%;padding:11px 14px;border-radius:10px;
  background:rgba(255,255,255,.04);border:1px solid var(--border);
  color:var(--text);font-size:14px;outline:none;transition:border .2s;
  font-family:inherit;
}
input:focus{border-color:rgba(240,180,41,.5)}
.field{margin-bottom:16px}
.btn-submit{
  width:100%;padding:12px;border-radius:10px;
  background:linear-gradient(135deg,var(--gold),#d4911e);
  color:#000;font-size:14px;font-weight:700;
  border:none;cursor:pointer;transition:opacity .2s;margin-top:4px;
}
.btn-submit:hover{opacity:.85}
.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--red);margin-bottom:16px}
.back{display:block;text-align:center;color:var(--muted);font-size:12px;margin-top:20px;text-decoration:none}
.back:hover{color:var(--text)}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <span class="trophy">🏆</span>
    <h1>WORLD CUP 2026</h1>
    <p>Iniciá sesión para votar</p>
  </div>

  <!-- Google OAuth -->
  <a href="login.php?google=1<?= isset($_GET['redirect']) ? '&redirect='.urlencode($_GET['redirect']) : '' ?>" class="btn-google">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
    </svg>
    Continuar con Google
  </a>

  <div class="divider">o con correo</div>

  <?php if($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="field">
      <label>Correo electrónico</label>
      <input type="email" name="email" placeholder="tu@correo.com" required autofocus>
    </div>
    <div class="field">
      <label>Contraseña</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn-submit">Iniciar Sesión</button>
  </form>

  <a href="index.php" class="back">← Volver al inicio sin sesión</a>
</div>
</body>
</html>
