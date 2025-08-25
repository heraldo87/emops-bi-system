<?php
/* ==========
========================================================
   logout.php — encerra a sessão corretamente
   ================================================================== */
// Salve como logout.php
require_once __DIR__ . '/bootstrap.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: login.php?bye=1');
exit;
?>

<?php
/* ==================================================================
   login.php — CPF sem senha, status permissivo, salva diretoria
   ================================================================== */
// Substitua seu login.php por este bloco
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/conn.php';
$PROJECT_NAME = 'COHIDRO BI';

// Usuário já logado? redireciona
if (!empty($_SESSION['user_id'])) {
  header('Location: ' . redirect_by_level((int)($_SESSION['access_level'] ?? 1)));
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cpf_raw = trim($_POST['cpf'] ?? '');
  $token   = (string)($_POST['csrf'] ?? '');
  $cpf     = digits_only($cpf_raw);

  if (!csrf_check($token)) { $errors[] = 'Sessão expirada. Recarregue a página.'; }
  if ($cpf === '' || strlen($cpf) !== 11) { $errors[] = 'CPF inválido.'; }

  if (!$errors) {
    try {
      $sql = "SELECT id_colaborador AS id, nome, cpf, diretoria, access_level, status
              FROM colaboradores
              WHERE REPLACE(REPLACE(REPLACE(REPLACE(cpf,'.',''),'-',''),' ',''),'/','') = ?
              LIMIT 1";
      $st = $conn->prepare($sql);
      if (!$st) throw new Exception('Falha ao preparar SELECT.');
      $st->bind_param('s', $cpf);
      $st->execute();
      $u = $st->get_result()->fetch_assoc();

      if ($u && status_ativo($u['status'] ?? '')) {
        session_regenerate_id(true);
        $_SESSION['user_id']      = (string)$u['id'];
        $_SESSION['nome']         = (string)$u['nome'];
        $_SESSION['cpf']          = digits_only((string)$u['cpf']);
        $_SESSION['diretoria']    = (string)($u['diretoria'] ?? '');
        $_SESSION['access_level'] = (int)$u['access_level'];
        $_SESSION['last_login']   = time();

        $next = $_GET['next'] ?? '';
        $safe = (is_string($next) && preg_match('~^[a-zA-Z0-9_\-/.]*$~',$next)) ? $next : redirect_by_level($_SESSION['access_level']);
        header('Location: '.$safe);
        exit;
      }
      $errors[] = $u ? 'Usuário inativo. Contate o administrador.' : 'CPF não encontrado.';
    } catch (Throwable $e) {
      error_log('[LOGIN_CPF] '.$e->getMessage());
      $errors[] = 'Erro interno.';
    }
  }
}
?>