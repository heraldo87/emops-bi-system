<?php
/* ==================================================================
   login.php — EMOPS BI · Login por CPF
   ================================================================== */
require_once __DIR__ . '/conn.php';

// Configura sessão segura
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Se já estiver logado, redireciona para a página correta
if (!empty($_SESSION['user_id']) && isset($_SESSION['access_level'])) {
    switch ($_SESSION['access_level']) {
        case 1: header("Location: formulario.php"); exit;
        case 2: header("Location: dashboard.php"); exit;
        case 3: header("Location: index.php"); exit;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');

    if (strlen($cpf) !== 11) {
        $errors[] = "CPF inválido.";
    } else {
        $stmt = $conn->prepare("SELECT id_colaborador, nome, cpf, diretoria, access_level 
                                FROM colaboradores 
                                WHERE cpf = ? AND status = 1");
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if ($user) {
            // Define a sessão
            $_SESSION['user_id']      = $user['id_colaborador'];
            $_SESSION['nome']         = $user['nome'];
            $_SESSION['cpf']          = $user['cpf'];
            $_SESSION['diretoria']    = $user['diretoria'];
            $_SESSION['access_level'] = (int) $user['access_level'];

            // Redireciona conforme nível
            switch ($_SESSION['access_level']) {
                case 1: header("Location: formulario.php"); exit;
                case 2: header("Location: dashboard.php"); exit;
                case 3: header("Location: index.php"); exit;
                default:
                    session_destroy();
                    $errors[] = "Acesso não autorizado.";
            }
        } else {
            $errors[] = "Usuário não encontrado ou inativo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login · EMOPS BI</title>
  <style>
    body{background:#0b1020;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh}
    .card{background:#1e293b;padding:20px;border-radius:10px;width:300px;box-shadow:0 4px 15px rgba(0,0,0,0.4)}
    h2{text-align:center;margin-bottom:15px}
    input{width:100%;padding:10px;margin:10px 0;border-radius:8px;border:none;font-size:16px}
    button{width:100%;padding:10px;background:#2563eb;color:white;border:none;border-radius:8px;font-size:16px;cursor:pointer}
    .err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);padding:10px;border-radius:8px;color:#ff7b7b;margin-bottom:12px}
  </style>
</head>
<body>
  <div class="card">
    <h2>Login</h2>

    <?php if ($errors): ?>
      <div class="err">
        <ul style="margin:0;padding-left:20px;">
          <?php foreach($errors as $e) echo "<li>$e</li>"; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="cpf" maxlength="14" placeholder="Digite seu CPF" required>
      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
