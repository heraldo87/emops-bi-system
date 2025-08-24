<?php
// ==============================================
// login.php — EMOPS BI · Página de Login (PHP)
// - Layout minimalista: somente o card de login centralizado
// - Sessão segura, CSRF, validação e redirecionamento
// - Depende de: conn.php (conexão MySQL)
// ==============================================

// ---- Sessão segura ----
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

// Se já logado, vá para o dashboard
if (!empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once __DIR__ . '/conn.php'; // <- crie este arquivo conforme instruções

$PROJECT_NAME = 'EMOPS BI';

// Gera CSRF se não houver
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$errors = [];

// ---- POST: tentativa de login ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  $csrf  = $_POST['csrf'] ?? '';

  // Valida CSRF
  if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
    $errors[] = 'Sessão expirada. Recarregue a página e tente novamente.';
  }

  // Valida campos
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Informe um e-mail válido.';
  }
  if ($senha === '') {
    $errors[] = 'Informe sua senha.';
  }

  if (!$errors) {
    try {
      $sql = "SELECT id, nome, email, senha_hash, access_level, status FROM usuarios WHERE email = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      if (!$stmt) { throw new Exception('Falha ao preparar consulta.'); }
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($u = $result->fetch_assoc()) {
        if (strcasecmp($u['status'] ?? 'ativo', 'ativo') !== 0) {
          $errors[] = 'Usuário inativo. Contate o administrador.';
        } elseif (!password_verify($senha, $u['senha_hash'])) {
          $errors[] = 'Credenciais inválidas.';
        } else {
          // OK — autenticar
          session_regenerate_id(true);
          $_SESSION['user_id']      = (int)$u['id'];
          $_SESSION['nome']         = (string)$u['nome'];
          $_SESSION['email']        = (string)$u['email'];
          $_SESSION['access_level'] = (int)$u['access_level'];
          $_SESSION['last_login']   = time();

          // next=? para redirecionar ao destino protegido originalmente
          $next = $_GET['next'] ?? 'index.php';
          // Sanitiza: permite apenas paths relativos
          if (!preg_match('~^[a-zA-Z0-9_\-/.]*$~', $next)) { $next = 'index.php'; }
          header('Location: ' . $next);
          exit;
        }
      } else {
        $errors[] = 'Credenciais inválidas.';
      }
    } catch (Throwable $e) {
      error_log('[LOGIN] ' . $e->getMessage());
      $errors[] = 'Erro interno. Tente novamente mais tarde.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Entrar · <?php echo htmlspecialchars($PROJECT_NAME); ?></title>
  <meta name="description" content="Acesso ao sistema <?php echo htmlspecialchars($PROJECT_NAME); ?>." />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{ --bg:#0b1020; --panel:#0f162e; --panel-2:#121a36; --muted:#a9b1d1; --text:#eef2ff; --brand:#4f46e5; --brand-2:#22d3ee; --danger:#ef4444; --ok:#22c55e; --card-radius:18px; --shadow:0 10px 30px rgba(0,0,0,.25) }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{ margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,'Noto Sans',sans-serif; background: radial-gradient(1200px 800px at 5% 0%, rgba(79,70,229,.25), rgba(34,211,238,.1) 35%, transparent 60%), radial-gradient(1200px 800px at 100% 40%, rgba(34,211,238,.2), transparent 55%), var(--bg); color:var(--text); display:grid; place-items:center; padding:24px }

    /* Card único */
    .panel{width:min(520px, 92vw); background:linear-gradient(180deg,var(--panel) 0%,var(--panel-2) 100%); border:1px solid rgba(255,255,255,.08); border-radius:var(--card-radius); box-shadow:var(--shadow); padding:24px}
    .brand{display:flex; align-items:center; gap:12px; margin-bottom:8px}
    .brand-logo{width:40px; height:40px; border-radius:12px; display:grid; place-items:center; background:linear-gradient(135deg,var(--brand),var(--brand-2)); box-shadow:var(--shadow)}
    .brand small{display:block; color:var(--muted)}
    h2{margin:6px 0 18px; font-size:26px}

    label{font-size:12px; opacity:.85}
    input[type="email"], input[type="password"]{width:100%; background:#0b1230; border:1px solid rgba(255,255,255,.1); color:var(--text); padding:12px 14px; border-radius:12px; outline:none}
    input::placeholder{color:rgba(255,255,255,.45)}
    .row{display:grid; gap:10px; margin:12px 0}

    /* Botões bonitinhos :) */
    .actions{display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:14px}
    .btn{background:#121a36; border:1px solid rgba(255,255,255,.12); color:var(--text); padding:12px 16px; border-radius:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; transition:transform .06s ease, box-shadow .2s ease}
    .btn:hover{transform:translateY(-1px); box-shadow:0 8px 22px rgba(0,0,0,.25)}
    .btn:active{transform:translateY(0)}
    .btn.brand{background:linear-gradient(135deg, rgba(79,70,229,.95), rgba(34,211,238,.95)); border:none}
    .btn svg{flex:0 0 auto}

    .muted{color:var(--muted); font-size:12px}
    .error{background: rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.35); color:#fecaca; padding:10px 12px; border-radius:12px; margin:8px 0}

    .pwd{position:relative}
    .pwd button{position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:0; color:var(--muted); cursor:pointer}

    footer{margin-top:18px; color:var(--muted); font-size:12px; display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between}

    @media (max-width: 480px){ .actions{grid-template-columns: 1fr} }
  </style>
</head>
<body>
  <main class="panel" aria-label="Login <?php echo htmlspecialchars($PROJECT_NAME); ?>">
    <div class="brand">
      <div class="brand-logo" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20"/><path d="M2 12h20"/>
        </svg>
      </div>
      <div>
        <strong><?php echo htmlspecialchars($PROJECT_NAME); ?></strong>
        <small>Acesso ao sistema</small>
      </div>
    </div>

    <h2>Entrar</h2>

    <?php if ($errors): ?>
      <div class="error">
        <?php foreach ($errors as $e): ?>
          <div>• <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>" />

      <div class="row">
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" placeholder="seu.email@dominio.com" autocomplete="username" required />
      </div>

      <div class="row pwd">
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" placeholder="••••••••" autocomplete="current-password" required />
        <button type="button" aria-label="Mostrar/ocultar senha" onclick="togglePwd()">👁️</button>
      </div>

      <!-- Ações: dois botões iguais -->
      <div class="actions">
        <button class="btn brand" type="submit">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
          Entrar
        </button>
        <a class="btn brand" href="cadastro.php" role="button">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
          Cadastrar
        </a>
      </div>

      <footer>
        <span>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($PROJECT_NAME); ?></span>
        <span>Dica: proteja seu dashboard incluindo <code>auth_check.php</code> no topo das páginas.</span>
      </footer>
    </form>
  </main>

  <script>
    function togglePwd(){
      const i = document.getElementById('senha');
      i.type = i.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>