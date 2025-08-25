<?php
// cadastro.php — EMOPS BI · Cadastro de usuário (compatível sem mbstring)
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
session_start();

require_once __DIR__ . '/conn.php';

// se já logado, manda pro dashboard
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

// --- helper de comprimento seguro sem mbstring ---
function str_len($s){
  if (function_exists('mb_strlen')) return mb_strlen($s, 'UTF-8');
  // Conta caracteres UTF-8 via PCRE
  return preg_match_all('/./u', (string)$s, $m);
}

// CSRF
if (empty($_SESSION['csrf_cad'])) { $_SESSION['csrf_cad'] = bin2hex(random_bytes(32)); }

$ok = false; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  $conf  = $_POST['conf'] ?? '';

  if (!hash_equals($_SESSION['csrf_cad'], $csrf)) $errors[] = 'Sessão expirada. Recarregue e tente novamente.';
  if ($nome === '' || str_len($nome) < 3) $errors[] = 'Informe um nome válido.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail válido.';
  if (str_len($senha) < 8) $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
  if ($senha !== $conf) $errors[] = 'As senhas não conferem.';

  if (!$errors) {
    try {
      // verifica se já existe
      $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) $errors[] = 'Já existe um cadastro com este e-mail.';
      $stmt->close();

      if (!$errors) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $access = 1; $status = 'ativo';
        $stmt = $conn->prepare("INSERT INTO usuarios (nome,email,senha_hash,access_level,status) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssis', $nome, $email, $hash, $access, $status);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
      }
    } catch(Throwable $e){
      error_log('[CADASTRO] '.$e->getMessage());
      $errors[] = 'Erro ao salvar cadastro.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastrar · COHIDRO BI</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{ --bg:#0b1020; --panel:#0f162e; --panel-2:#121a36; --muted:#a9b1d1; --text:#eef2ff; --brand:#4f46e5; --brand-2:#22d3ee; --danger:#ef4444; --card-radius:18px; --shadow:0 10px 30px rgba(0,0,0,.25) }
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,'Noto Sans',sans-serif;
         background:radial-gradient(1200px 800px at 5% 0%, rgba(79,70,229,.25), rgba(34,211,238,.1) 35%, transparent 60%),
                    radial-gradient(1200px 800px at 100% 40%, rgba(34,211,238,.2), transparent 55%), var(--bg);
         color:var(--text); display:grid; place-items:center; padding:24px}
    .panel{width:min(520px,92vw); background:linear-gradient(180deg,var(--panel) 0%,var(--panel-2) 100%);
           border:1px solid rgba(255,255,255,.08); border-radius:var(--card-radius); box-shadow:var(--shadow); padding:22px}
    .brand{display:flex;align-items:center;gap:10px;margin-bottom:8px}
    .brand-logo{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;background:linear-gradient(135deg,var(--brand),var(--brand-2));box-shadow:var(--shadow)}
    h2{margin:0 0 14px}
    label{font-size:12px; opacity:.85}
    input{width:100%; background:#0b1230; border:1px solid rgba(255,255,255,.1); color:var(--text); padding:12px 14px; border-radius:12px; outline:none}
    .row{display:grid; gap:10px; margin:12px 0}
    .btn{background:#121a36; border:1px solid rgba(255,255,255,.12); color:var(--text); padding:12px 16px; border-radius:12px; display:inline-flex; align-items:center; gap:8px; cursor:pointer}
    .btn.brand{background:linear-gradient(135deg, rgba(79,70,229,.9), rgba(34,211,238,.9)); border:none}
    .muted{color:var(--muted);font-size:13px}
    .error{background: rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.35); color:#fecaca; padding:10px 12px; border-radius:12px; margin:8px 0}
    .ok{background: rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.35); color:#dcfce7; padding:10px 12px; border-radius:12px; margin:8px 0}
  </style>
</head>
<body>
  <main class="panel">
    <div class="brand">
      <div class="brand-logo" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M2 12h20"/></svg>
      </div>
      <div>
        <strong>COHIDRO BI</strong>
        <div class="muted">Novo cadastro</div>
      </div>
    </div>

    <h2>Criar conta</h2>

    <?php if ($ok): ?>
      <div class="ok">Cadastro realizado com sucesso! Você já pode <a href="login.php" style="color:#a5b4fc">entrar</a>.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="error">
        <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_cad']) ?>">
      <div class="row">
        <label for="nome">Nome completo</label>
        <input id="nome" name="nome" type="text" placeholder="Seu nome" required>
      </div>
      <div class="row">
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" placeholder="seu.email@dominio.com" required autocomplete="email">
      </div>
      <div class="row">
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" placeholder="mín. 8 caracteres" required autocomplete="new-password">
      </div>
      <div class="row">
        <label for="conf">Confirmar senha</label>
        <input id="conf" name="conf" type="password" placeholder="repita a senha" required autocomplete="new-password">
      </div>

      <div class="row" style="display:flex; gap:10px">
        <button class="btn brand" type="submit">Cadastrar</button>
        <a class="btn" href="login.php" role="button">Voltar ao login</a>
      </div>
    </form>
  </main>
</body>
</html>
