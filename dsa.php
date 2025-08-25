<?php
// aai_registro_novo.php — Página de cadastro (SEM sidebar/topbar) seguindo o tema EMOPS
// Opcional: proteger a página
// require_once __DIR__.'/auth_check.php';

session_start();

// ==========================
// Config de Banco (PDO)
// ==========================
// Sugestão: defina variáveis de ambiente no servidor (DB_DSN, DB_USER, DB_PASS)
$dsn   = getenv('DB_DSN')  ?: 'mysql:host=localhost;dbname=emops;charset=utf8mb4';
$user  = getenv('DB_USER') ?: 'root';
$pass  = getenv('DB_PASS') ?: '';

try {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  $db_error = null;
} catch (PDOException $e) {
  $db_error = $e->getMessage();
}

// ==========================
// CSRF
// ==========================
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ==========================
// Helpers de validação
// ==========================
function only_digits(string $s): string { return preg_replace('/\D+/', '', $s) ?? ''; }
function valid_date(string $s): bool {
  $d = DateTime::createFromFormat('Y-m-d', $s);
  return $d && $d->format('Y-m-d') === $s;
}
function clip(?string $s, int $max = 5000): string { return mb_substr((string)$s, 0, $max); }

$feedback = [ 'ok' => false, 'msg' => null, 'id' => null, 'errors' => [] ];

// Valores padrão (para repovoar o form após erro)
$val = [
  'enviar_por_email'   => '',
  'cpf'                => '',
  'id_colaborador'     => '',
  'data_inicial'       => '',
  'data_final'         => '',
  'atividades_realizadas'  => '',
  'atividades_previstas'   => '',
  'pontos_relevantes'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf'])) {
    $feedback['errors']['csrf'] = 'Token CSRF inválido. Recarregue a página.';
  }

  // Coleta + saneamento básico
  $val['enviar_por_email']   = trim((string)($_POST['enviar_por_email'] ?? ''));
  $val['cpf']                = only_digits((string)($_POST['cpf'] ?? ''));
  $val['id_colaborador']     = trim((string)($_POST['id_colaborador'] ?? ''));
  $val['data_inicial']       = trim((string)($_POST['data_inicial'] ?? ''));
  $val['data_final']         = trim((string)($_POST['data_final'] ?? ''));
  $val['atividades_realizadas'] = clip((string)($_POST['atividades_realizadas'] ?? ''), 8000);
  $val['atividades_previstas']  = clip((string)($_POST['atividades_previstas'] ?? ''), 8000);
  $val['pontos_relevantes']     = clip((string)($_POST['pontos_relevantes'] ?? ''), 8000);

  // Validações
  if (!filter_var($val['enviar_por_email'], FILTER_VALIDATE_EMAIL)) {
    $feedback['errors']['enviar_por_email'] = 'Informe um e-mail válido.';
  }
  if ($val['cpf'] === '' || strlen($val['cpf']) < 11) {
    $feedback['errors']['cpf'] = 'Informe um CPF válido (apenas números).';
  }
  if ($val['id_colaborador'] === '' || !ctype_digit($val['id_colaborador'])) {
    $feedback['errors']['id_colaborador'] = 'ID do colaborador deve ser numérico.';
  }
  if (!valid_date($val['data_inicial'])) {
    $feedback['errors']['data_inicial'] = 'Data inicial inválida (YYYY-MM-DD).';
  }
  if (!valid_date($val['data_final'])) {
    $feedback['errors']['data_final'] = 'Data final inválida (YYYY-MM-DD).';
  }
  // Campos de texto longos — apenas obrigatoriedade simples
  if ($val['atividades_realizadas'] === '') {
    $feedback['errors']['atividades_realizadas'] = 'Descreva as atividades realizadas.';
  }
  if ($val['atividades_previstas'] === '') {
    $feedback['errors']['atividades_previstas'] = 'Descreva as atividades previstas.';
  }

  // Se sem erros e DB ok: insere
  if (!$db_error && empty($feedback['errors'])) {
    try {
      date_default_timezone_set('America/Sao_Paulo');
      $agora = (new DateTime())->format('Y-m-d H:i:s');

      $sql = 'INSERT INTO aai_registros (
                enviar_por_email, cpf, id_colaborador, data_inicial, data_final,
                atividades_realizadas, atividades_previstas, pontos_relevantes, data_registro
              ) VALUES (
                :email, :cpf, :idc, :dini, :dfim, :real, :prev, :pontos, :dtreg
              )';
      $st = $pdo->prepare($sql);
      $st->execute([
        ':email'  => $val['enviar_por_email'],
        ':cpf'    => $val['cpf'],
        ':idc'    => (int)$val['id_colaborador'],
        ':dini'   => $val['data_inicial'],
        ':dfim'   => $val['data_final'],
        ':real'   => $val['atividades_realizadas'],
        ':prev'   => $val['atividades_previstas'],
        ':pontos' => $val['pontos_relevantes'],
        ':dtreg'  => $agora,
      ]);

      $feedback['ok'] = true;
      $feedback['id'] = $pdo->lastInsertId();
      $feedback['msg'] = 'Registro salvo com sucesso!';

      // Limpa o form após sucesso
      $val = [
        'enviar_por_email'   => '',
        'cpf'                => '',
        'id_colaborador'     => '',
        'data_inicial'       => '',
        'data_final'         => '',
        'atividades_realizadas'  => '',
        'atividades_previstas'   => '',
        'pontos_relevantes'      => '',
      ];

    } catch (Throwable $e) {
      $feedback['ok'] = false;
      $feedback['msg'] = 'Erro ao salvar no banco.';
      $feedback['errors']['db'] = $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>COHIDRO BI · Novo Registro AAI</title>
  <meta name="theme-color" content="#0b1020" />
  <link rel="icon" href="/favicon.ico" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/cohidro.css">
  <style>
    /* Estilos específicos desta página (sem topbar e sem sidebar) */
    .page-wrap{max-width:980px;margin:30px auto;padding:0 18px}
    .muted{opacity:.8}
    .error{color:#fda4af;font-size:12px;margin-top:6px}
    .form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
    .title-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
  </style>
</head>
<body>
  <main class="page-wrap" id="conteudo" aria-labelledby="titulo">
    <section class="panel" style="margin-bottom:18px">
      <div class="title-row">
        <div>
          <h3 id="titulo" style="margin:0">Novo Registro AAI</h3>
          <div class="muted" style="font-size:13px">Preencha os campos abaixo para inserir um novo registro na tabela <code>aai_registros</code>.</div>
        </div>
        <a href="/" class="btn" title="Voltar ao sistema">Voltar</a>
      </div>
    </section>

    <?php if ($db_error): ?>
      <section class="panel" style="border-color:rgba(239,68,68,.45)">
        <h3>Erro de conexão com o banco</h3>
        <div class="muted"><?php echo htmlspecialchars($db_error); ?></div>
      </section>
    <?php endif; ?>

    <section class="panel" aria-labelledby="formTitle">
      <h3 id="formTitle" style="margin:0 0 8px">Dados do registro</h3>
      <form method="post" action="" novalidate>
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf_token); ?>" />

        <div class="form-grid" style="margin-top:8px">
          <div>
            <label>Enviar por e-mail (destinatário)</label>
            <input name="enviar_por_email" id="fEmail" type="email" placeholder="email@exemplo.com" value="<?php echo htmlspecialchars($val['enviar_por_email']); ?>" required />
            <?php if(isset($feedback['errors']['enviar_por_email'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['enviar_por_email']); ?></div><?php endif; ?>
          </div>

          <div>
            <label>CPF</label>
            <input name="cpf" id="fCpf" type="text" placeholder="Somente números" value="<?php echo htmlspecialchars($val['cpf']); ?>" required />
            <?php if(isset($feedback['errors']['cpf'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['cpf']); ?></div><?php endif; ?>
          </div>

          <div>
            <label>ID do colaborador</label>
            <input name="id_colaborador" id="fColab" type="number" min="0" step="1" placeholder="Ex.: 4" value="<?php echo htmlspecialchars($val['id_colaborador']); ?>" required />
            <?php if(isset($feedback['errors']['id_colaborador'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['id_colaborador']); ?></div><?php endif; ?>
          </div>

          <div>
            <label>Data inicial</label>
            <input name="data_inicial" id="fDataIni" type="date" value="<?php echo htmlspecialchars($val['data_inicial']); ?>" required />
            <?php if(isset($feedback['errors']['data_inicial'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['data_inicial']); ?></div><?php endif; ?>
          </div>

          <div>
            <label>Data final</label>
            <input name="data_final" id="fDataFim" type="date" value="<?php echo htmlspecialchars($val['data_final']); ?>" required />
            <?php if(isset($feedback['errors']['data_final'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['data_final']); ?></div><?php endif; ?>
          </div>

          <div class="full">
            <label>Atividades realizadas</label>
            <textarea name="atividades_realizadas" id="fRealizadas" placeholder="Descreva as atividades realizadas" required><?php echo htmlspecialchars($val['atividades_realizadas']); ?></textarea>
            <?php if(isset($feedback['errors']['atividades_realizadas'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['atividades_realizadas']); ?></div><?php endif; ?>
          </div>

          <div class="full">
            <label>Atividades previstas</label>
            <textarea name="atividades_previstas" id="fPrevistas" placeholder="Descreva as atividades previstas" required><?php echo htmlspecialchars($val['atividades_previstas']); ?></textarea>
            <?php if(isset($feedback['errors']['atividades_previstas'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['atividades_previstas']); ?></div><?php endif; ?>
          </div>

          <div class="full">
            <label>Pontos relevantes</label>
            <textarea name="pontos_relevantes" id="fRelevantes" placeholder="Observações e pontos relevantes">&nbsp;<?php echo htmlspecialchars($val['pontos_relevantes']); ?></textarea>
          </div>
        </div>

        <?php if(isset($feedback['errors']['db'])): ?>
          <div class="error" style="margin-top:8px">Banco: <?php echo htmlspecialchars($feedback['errors']['db']); ?></div>
        <?php endif; ?>

        <div class="form-actions">
          <button type="reset" class="btn" id="btnLimparForm">Limpar</button>
          <button type="submit" class="btn brand" id="btnSalvar">Salvar</button>
        </div>
      </form>
    </section>

    <footer class="muted" style="text-align:center;margin-top:8px">© <span id="ano"></span> COHIDRO · Registros AAI</footer>
  </main>

  <!-- Toast -->
  <div id="toast" style="position:fixed; right:20px; bottom:20px; background:#0b1230; border:1px solid rgba(255,255,255,.12); color:var(--text); padding:12px 14px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.25); display:none"></div>

  <script>
    const $ = (q,ctx=document)=>ctx.querySelector(q);
    const showToast = (msg)=>{ const t=$('#toast'); t.textContent=msg; t.style.display='block'; setTimeout(()=>t.style.display='none', 2400); };
    window.addEventListener('DOMContentLoaded', ()=>{
      document.getElementById('ano').textContent = new Date().getFullYear();
      const feedback = <?php echo json_encode($feedback, JSON_UNESCAPED_UNICODE); ?>;
      if (feedback && feedback.msg) {
        showToast(feedback.msg + (feedback.id ? ` (ID: ${feedback.id})` : ''));
      }
    });
  </script>
</body>
</html>

<?php /*
SQL de referência (MySQL):

CREATE TABLE IF NOT EXISTS aai_registros (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  enviar_por_email VARCHAR(255) NOT NULL,
  cpf VARCHAR(14) NOT NULL,
  id_colaborador BIGINT UNSIGNED NOT NULL,
  data_inicial DATE NOT NULL,
  data_final DATE NOT NULL,
  atividades_realizadas TEXT NOT NULL,
  atividades_previstas TEXT NOT NULL,
  pontos_relevantes TEXT NULL,
  data_registro DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_colab (id_colaborador),
  KEY idx_periodo (data_inicial, data_final)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

*/ ?>
