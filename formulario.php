<?php
/* ==================================================================
   formulario.php — envia AAI para n8n (Webhook)
   Mantém seu backend original: sessão, CSRF, validação e payload
   ================================================================== */

// ========= Sessão / Cookies =========
if (session_status() === PHP_SESSION_NONE) {
  $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => $isHttps,
    'httponly' => true, 'samesite' => 'Lax',
  ]);
  session_start();
}

require_once __DIR__ . '/auth_check.php';
require_login(1);

$PROJECT_NAME = 'COHIDRO BI';

// ========= CONFIG n8n (sem token) =========
$N8N_ENDPOINT = 'https://n8n.alunosdamedicina.com/webhook/formulario_aai';

// ========= CSRF =========
if (empty($_SESSION['csrf_form'])) { $_SESSION['csrf_form'] = bin2hex(random_bytes(32)); }
$csrf_form = $_SESSION['csrf_form'];

// ========= Dados do usuário logado =========
$cpf_sess  = (string)($_SESSION['cpf'] ?? '');
$nome_sess = (string)($_SESSION['nome'] ?? '');
$dir_sess  = (string)($_SESSION['diretoria'] ?? '');

// ========= Helpers =========
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function only_digits($s){ return preg_replace('/\D+/', '', (string)$s); }

$errors = [];
$ok_text = null;

// Campos livres p/ re-render
$nome=$cpf_raw=$diretoria=$data_inicial=$data_final=$atividades_realizadas=$atividades_previstas=$pontos_relevantes='';
$atividade_andamento=$pontos_criticos=''; $local=''; $data_unica=''; $visto='';

if (isset($_GET['novo'])) {
  $data_inicial=$data_final=$atividades_realizadas=$atividades_previstas=$pontos_relevantes='';
  $atividade_andamento=$pontos_criticos=''; $local=''; $data_unica=''; $visto='';
}

// ========= POST =========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_form'], $_POST['csrf'])) {
    $errors[] = 'Sessão expirada. Recarregue a página e tente novamente.';
  }

  // Campos compatíveis com sua tabela
  $nome       = trim($_POST['nome'] ?? $nome_sess);
  $cpf_raw    = trim($_POST['cpf']  ?? $cpf_sess);
  $cpf        = only_digits($cpf_raw);
  $diretoria  = trim($_POST['diretoria'] ?? $dir_sess);

  // virão de "Período (mm/aaaa)" via JS
  $data_inicial = trim($_POST['data_inicial'] ?? '');
  $data_final   = trim($_POST['data_final']   ?? '');

  $atividades_realizadas = trim($_POST['atividades_realizadas'] ?? '');
  $atividades_previstas  = trim($_POST['atividades_previstas']  ?? '');
  $pontos_relevantes     = trim($_POST['pontos_relevantes']     ?? '');

  // Extras (não entram no payload)
  $atividade_andamento = trim($_POST['atividade_andamento'] ?? '');
  $pontos_criticos     = trim($_POST['pontos_criticos']     ?? '');
  $local               = trim($_POST['local']               ?? '');
  $data_unica          = trim($_POST['data']                ?? '');
  $visto               = trim($_POST['visto']               ?? '');

  // Validações mínimas do backend
  if ($nome === '') $errors[] = 'Informe o nome.';
  if ($cpf === '' || strlen($cpf) < 11) $errors[] = 'CPF inválido.';
  if ($diretoria === '') $errors[] = 'Informe a diretoria.';
  if ($data_inicial === '') $errors[] = 'Informe o período (data inicial).';
  if ($data_final === '')   $errors[] = 'Informe o período (data final).';

  $reData = '/^\d{4}-\d{2}-\d{2}$/';
  if ($data_inicial && !preg_match($reData, $data_inicial)) $errors[] = 'Data inicial inválida (AAAA-MM-DD).';
  if ($data_final   && !preg_match($reData, $data_final))   $errors[] = 'Data final inválida (AAAA-MM-DD).';

  if (!$errors) {
    $payload = [
      'nome' => $nome, 'cpf' => $cpf, 'diretoria' => $diretoria,
      'data_inicial' => $data_inicial, 'data_final' => $data_final,
      'atividades_realizadas' => $atividades_realizadas,
      'atividades_previstas'  => $atividades_previstas,
      'pontos_relevantes'     => $pontos_relevantes,
    ];

    $ch = curl_init($N8N_ENDPOINT);
    curl_setopt_array($ch, [
      CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err)           $errors[] = 'Falha ao comunicar com o n8n: '.h($err);
    elseif ($httpCode >= 400) $errors[] = 'n8n retornou HTTP '.(int)$httpCode.': '.h($response);
    else {
      $ok = true; $r = json_decode((string)$response, true);
      if (is_array($r) && array_key_exists('ok', $r)) $ok = (bool)$r['ok'];
      if ($ok) {
        $ok_text = 'Registro inserido com sucesso. Deseja lançar outro agora?';
        $data_inicial=$data_final=$atividades_realizadas=$atividades_previstas=$pontos_relevantes='';
        $atividade_andamento=$pontos_criticos=''; $local=''; $data_unica=''; $visto='';
      } else {
        $errors[] = 'Fluxo n8n respondeu, mas sem confirmação de sucesso.';
      }
    }
  }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <title>Formulário de Atividades · <?= h($PROJECT_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes" />
  <meta name="color-scheme" content="light dark" />

  <!-- CSS via CDN (AdminLTE 4 + Bootstrap 5) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/css/adminlte.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body{ background:var(--bs-body-bg); }
    .container-limit{ max-width:980px; margin:0 auto; }
    .card{ border-radius:14px; overflow:hidden; }
    .card-header{ background:linear-gradient(90deg,#1d4ed8,#2563eb,#3b82f6); color:#fff; }
    .required::after{ content:" *"; color:#ef4444; }
  </style>
</head>
<body class="layout-fixed bg-body-tertiary">
  <div class="app-wrapper">
    <!-- Header minimal (sem marca) -->
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-limit d-flex align-items-center w-100">
        <span class="navbar-text text-secondary small">Formulário AAI</span>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Sair</a></li>
        </ul>
      </div>
    </nav>

    <!-- Título -->
    <div class="app-content-header">
      <div class="container-limit">
        <h3 class="mb-0">Formulário de Acompanhamento de Atividades (AAI)</h3>
        <p class="text-secondary mb-0">Preencha os campos. Os obrigatórios estão marcados com *.</p>
      </div>
    </div>

    <!-- Conteúdo -->
    <div class="app-content">
      <div class="container-limit">

        <?php if ($ok_text): ?>
          <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check2-circle me-2"></i>
            <div><?= h($ok_text) ?></div>
          </div>
          <div class="d-flex gap-2 mb-3">
            <a class="btn btn-primary" href="?novo=1">Lançar outro</a>
            <form method="post" action="logout.php" class="m-0">
              <input type="hidden" name="csrf" value="<?= h($csrf_form) ?>">
              <button class="btn btn-outline-secondary" type="submit">Sair</button>
            </form>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger" role="alert">
            <strong>Foram encontrados erros:</strong>
            <ul class="mb-0">
              <?php foreach($errors as $e) echo '<li>'.h($e).'</li>'; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="card shadow-sm" <?= $ok_text ? 'style="display:none"' : '' ?>>
          <div class="card-header">
            <div class="card-title m-0"><i class="bi bi-clipboard2-check me-2"></i>Dados do Registro</div>
          </div>

          <form method="POST" action="" class="card-body needs-validation" novalidate>
            <input type="hidden" name="csrf" value="<?= h($csrf_form) ?>">

            <!-- 1) COLABORADOR -->
            <div class="row g-3 align-items-end">
              <div class="col-md-8">
                <label class="form-label required">Colaborador</label>
                <input type="text" class="form-control" name="nome" value="<?= h($nome_sess) ?>" readonly>
                <div class="form-text">Informado pelo login.</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">CPF</label>
                <input type="text" class="form-control" name="cpf" value="<?= h($cpf_sess) ?>" readonly>
              </div>
            </div>

            <!-- 2) PERÍODO (mm/aaaa) → preenche data_inicial + data_final -->
            <div class="row g-3 mt-1">
              <div class="col-md-4">
                <label class="form-label required" for="periodo">Período de Avaliação</label>
                <input type="text" class="form-control" id="periodo" placeholder="mm/aaaa" required>
                <div class="form-text">Ex.: 08/2025</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Diretoria</label>
                <input type="text" class="form-control" name="diretoria" value="<?= h($dir_sess) ?>" readonly>
              </div>
              <!-- ocultos exigidos pelo backend -->
              <input type="hidden" id="data_inicial" name="data_inicial" value="<?= h($data_inicial) ?>">
              <input type="hidden" id="data_final"   name="data_final"   value="<?= h($data_final) ?>">
            </div>

            <!-- 3) SETOR / COORD. / FUNÇÃO (informativos) -->
            <div class="row g-3 mt-1">
              <div class="col-md-4">
                <label class="form-label">Setor</label>
                <input type="text" class="form-control" name="setor">
              </div>
              <div class="col-md-4">
                <label class="form-label">Coordenação</label>
                <input type="text" class="form-control" name="coordenacao">
              </div>
              <div class="col-md-4">
                <label class="form-label">Função</label>
                <input type="text" class="form-control" name="funcao">
              </div>
            </div>

            <!-- 4) ATIVIDADES -->
            <div class="mt-3">
              <label class="form-label">Atividades Realizadas</label>
              <textarea class="form-control" name="atividades_realizadas" rows="3"><?= h($atividades_realizadas) ?></textarea>
            </div>

            <div class="mt-3">
              <label class="form-label">Atividade em Andamento</label>
              <textarea class="form-control" name="atividade_andamento" rows="3"><?= h($atividade_andamento) ?></textarea>
            </div>

            <div class="mt-3">
              <label class="form-label">Atividades Previstas</label>
              <textarea class="form-control" name="atividades_previstas" rows="3"><?= h($atividades_previstas) ?></textarea>
            </div>

            <div class="mt-3">
              <label class="form-label">Pontos Relevantes</label>
              <textarea class="form-control" name="pontos_relevantes" rows="3"><?= h($pontos_relevantes) ?></textarea>
            </div>

            <div class="mt-3">
              <label class="form-label">Pontos Críticos</label>
              <textarea class="form-control" name="pontos_criticos" rows="3"><?= h($pontos_criticos) ?></textarea>
            </div>

            <!-- 5) LOCAL E DATA + VISTO -->
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label">Local</label>
                <input type="text" class="form-control" name="local" value="<?= h($local) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Data</label>
                <input type="date" class="form-control" name="data" value="<?= h($data_unica) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Visto</label>
                <input type="text" class="form-control" name="visto" value="<?= h($visto) ?>">
              </div>
            </div>

            <div class="d-flex align-items-center gap-2 pt-3">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-1"></i> Enviar
              </button>
              <button type="reset" class="btn btn-outline-secondary">Limpar</button>
            </div>
          </form>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 text-secondary small">
          <span>Formulário AAI</span><span>Homologação</span>
        </div>
      </div>
    </div>
  </div>

  <!-- JS via CDN -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script>

  <script>
    // Converte "mm/aaaa" => data_inicial (AAAA-MM-01) e data_final (último dia do mês)
    const periodo = document.getElementById('periodo');
    const di = document.getElementById('data_inicial');
    const df = document.getElementById('data_final');

    function ultDia(ano, mes1a12){
      return new Date(ano, mes1a12, 0).getDate(); // dia 0 do mês seguinte
    }
    function setPeriodo(val){
      const m = (val||'').match(/^(\d{2})[\/\-](\d{4})$/);
      if(!m){ di.value=''; df.value=''; return; }
      const mm = parseInt(m[1],10), aa = parseInt(m[2],10);
      if(mm<1 || mm>12){ di.value=''; df.value=''; return; }
      const last = ultDia(aa, mm);
      di.value = `${aa}-${String(mm).padStart(2,'0')}-01`;
      df.value = `${aa}-${String(mm).padStart(2,'0')}-${String(last).padStart(2,'0')}`;
    }
    periodo.addEventListener('input', e => setPeriodo(e.target.value));
    // Se já vier pré-preenchido no re-render:
    if (periodo.value) setPeriodo(periodo.value);

    // Auto-resize nas textareas
    document.querySelectorAll('textarea').forEach(ta=>{
      const auto=()=>{ ta.style.height='auto'; ta.style.height=(ta.scrollHeight + 2)+'px'; };
      ta.addEventListener('input', auto); auto();
    });
  </script>
</body>
</html>
