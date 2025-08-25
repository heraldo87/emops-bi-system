<?php
/* ==================================================================
   formulario.php — EMOPS BI · envia AAI para n8n (Webhook)
   Payload 1-para-1 com a tabela: nome, cpf, diretoria, data_inicial,
   data_final, atividades_realizadas, atividades_previstas, pontos_relevantes
   ================================================================== */

require_once __DIR__ . '/auth_check.php';
require_login(1);

$PROJECT_NAME = 'COHIDRO BI';

// ========= CONFIG n8n =========
$N8N_ENDPOINT = 'https://n8n.alunosdamedicina.com/webhook/formulario_aai';
// $N8N_ENDPOINT = 'http://localhost:5678/webhook/formulario_aai'; // teste local

// ========= Sessão / CSRF =========
session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf_form'])) { $_SESSION['csrf_form'] = bin2hex(random_bytes(32)); }
$csrf_form = $_SESSION['csrf_form'];

// ========= Dados do usuário logado =========
$cpf_sess   = (string)($_SESSION['cpf'] ?? '');
$nome_sess  = (string)($_SESSION['nome'] ?? '');
$dir_sess   = (string)($_SESSION['diretoria'] ?? '');

// ========= Helpers =========
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function only_digits($s){ return preg_replace('/\D+/', '', (string)$s); }

$errors  = [];
$ok_text = null;

// Campos para repintar em caso de erro
$nome=$cpf_raw=$diretoria=$data_inicial=$data_final=$atividades_realizadas=$atividades_previstas=$pontos_relevantes='';
$atividade_andamento=$pontos_criticos=''; $enviar_por_email=0;

// ========= POST Handler =========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_form'], $_POST['csrf'])) {
    $errors[] = 'Sessão expirada. Recarregue a página e tente novamente.';
  }

  // Coleta campos (apenas os que existem na tabela)
  $nome                 = trim($_POST['nome'] ?? $nome_sess);
  $cpf_raw              = trim($_POST['cpf'] ?? $cpf_sess);
  $cpf                  = only_digits($cpf_raw);
  $diretoria            = trim($_POST['diretoria'] ?? $dir_sess);
  $data_inicial         = trim($_POST['data_inicial'] ?? '');
  $data_final           = trim($_POST['data_final'] ?? '');
  $atividades_realizadas= trim($_POST['atividades_realizadas'] ?? '');
  $atividades_previstas = trim($_POST['atividades_previstas'] ?? '');
  $pontos_relevantes    = trim($_POST['pontos_relevantes'] ?? '');

  // (Extras visuais — NÃO enviados)
  $atividade_andamento  = trim($_POST['atividade_andamento'] ?? '');
  $pontos_criticos      = trim($_POST['pontos_criticos'] ?? '');
  $enviar_por_email     = !empty($_POST['enviar_por_email']) ? 1 : 0;

  // Validações mínimas
  if ($nome === '')                      $errors[] = 'Informe o nome.';
  if ($cpf === '' || strlen($cpf) < 11)  $errors[] = 'CPF inválido.';
  if ($diretoria === '')                 $errors[] = 'Informe a diretoria.';
  if ($data_inicial === '')              $errors[] = 'Informe a data inicial.';
  if ($data_final === '')                $errors[] = 'Informe a data final.';

  $reData = '/^\d{4}-\d{2}-\d{2}$/';
  if ($data_inicial && !preg_match($reData, $data_inicial)) $errors[] = 'Data inicial inválida (use AAAA-MM-DD).';
  if ($data_final   && !preg_match($reData, $data_final))   $errors[] = 'Data final inválida (use AAAA-MM-DD).';

  if (!$errors) {
    // Payload EXATO da tabela (sem id, sem data_registro)
    $payload = [
      'nome'                   => $nome,
      'cpf'                    => $cpf,
      'diretoria'              => $diretoria,
      'data_inicial'           => $data_inicial,
      'data_final'             => $data_final,
      'atividades_realizadas'  => $atividades_realizadas,
      'atividades_previstas'   => $atividades_previstas,
      'pontos_relevantes'      => $pontos_relevantes,
    ];

    // Envia ao n8n
    $ch = curl_init($N8N_ENDPOINT);
    curl_setopt_array($ch, [
      CURLOPT_POST           => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
      $errors[] = 'Falha ao comunicar com o n8n: '.h($err);
    } elseif ($httpCode >= 400) {
      $errors[] = 'n8n retornou HTTP '.(int)$httpCode.': '.h($response);
    } else {
      // Opcional: validar JSON do n8n (esperando { ok:true, insert_id:123 })
      $ok = true;
      $r = json_decode((string)$response, true);
      if (is_array($r) && array_key_exists('ok', $r)) {
        $ok = (bool)$r['ok'];
      }
      if ($ok) {
        $ok_text = 'Registro inserido com sucesso. Deseja lançar outro agora?';
        // Limpa SOMENTE campos de digitação; mantém pré-preenchidos do usuário
        $data_inicial=$data_final=$atividades_realizadas=$atividades_previstas=$pontos_relevantes='';
        $atividade_andamento=$pontos_criticos=''; $enviar_por_email=0;
      } else {
        $errors[] = 'Fluxo n8n respondeu, mas sem confirmação de sucesso.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>AAI · <?= h($PROJECT_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0b1020;--panel:#0f162e;--panel-2:#121a36;--muted:#a9b1d1;--text:#eef2ff;--r:18px;--sh:0 10px 30px rgba(0,0,0,.25)}*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,sans-serif;background:var(--bg);color:var(--text)}
.panel{width:min(860px,96vw);margin:40px auto;background:linear-gradient(180deg,var(--panel),var(--panel-2));padding:24px;border-radius:var(--r);box-shadow:var(--sh)}
label{font-size:12px;color:var(--muted)}
input,textarea{width:100%;margin-top:6px;padding:12px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.1);background:#0b1230;color:#eef2ff}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{background:linear-gradient(135deg,rgba(79,70,229,.95),rgba(34,211,238,.95));border:none;color:#fff;padding:12px 18px;border-radius:14px;cursor:pointer}
.btn-ghost{background:transparent;border:1px solid rgba(255,255,255,.2);color:#fff;padding:10px 16px;border-radius:12px;cursor:pointer}
.ok{color:#22c55e;font-size:14px}
.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);padding:10px 14px;border-radius:10px;margin:10px 0}
.small{color:#a9b1d1;font-size:12px}
.center{display:flex;gap:12px;justify-content:center;align-items:center;margin-top:12px}
.card{background:#0b1230;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:16px}
</style>
</head>
<body>
  <main class="panel">
    <h2 style="margin:0 0 12px">Acompanhamento de Atividades (AAI)</h2>
    <p class="small">Este formulário envia ao n8n os campos da tabela <b>acompanhamento_atividades</b>.</p>

    <?php if ($ok_text): ?>
      <div class="card">
        <div class="ok">✓ <?= h($ok_text) ?></div>
        <div class="center">
          <!-- Lançar outro: recarrega a página limpando os campos manuais -->
          <form method="GET" action="">
            <button class="btn" type="submit" name="novo" value="1">Lançar outro</button>
          </form>
          <!-- Logout: envia para logout.php (que deve encerrar sessão e redirecionar login) -->
          <form method="POST" action="logout.php">
            <input type="hidden" name="csrf" value="<?= h($csrf_form) ?>">
            <button class="btn-ghost" type="submit">Sair</button>
          </form>
        </div>
      </div>
      <div style="height:8px"></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="err">
        <b>Foram encontrados erros:</b>
        <ul style="margin:8px 0 0 16px">
          <?php foreach($errors as $e) echo '<li>'.h($e).'</li>'; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Se houve sucesso ($ok_text), ocultamos o formulário até o usuário decidir -->
    <form method="POST" action="" <?= $ok_text ? 'style="display:none"' : '' ?>>
      <input type="hidden" name="csrf" value="<?= h($csrf_form) ?>">

      <div class="grid2">
        <div><label>Nome</label><input type="text" name="nome" value="<?= h($nome_sess) ?>" readonly></div>
        <div><label>CPF</label><input type="text" name="cpf" value="<?= h($cpf_sess) ?>" readonly></div>
      </div>

      <div class="grid2" style="margin-top:12px">
        <div><label>Diretoria</label><input type="text" name="diretoria" value="<?= h($dir_sess) ?>" readonly></div>
        <div></div>
      </div>

      <div class="grid2" style="margin-top:12px">
        <div><label>Data Inicial</label><input id="data_inicial" type="date" name="data_inicial" value="<?= h($data_inicial ?? '') ?>" required></div>
        <div><label>Data Final</label><input id="data_final" type="date" name="data_final" value="<?= h($data_final ?? '') ?>" required></div>
      </div>

      <div style="margin-top:12px"><label>Atividades Realizadas</label>
        <textarea name="atividades_realizadas"><?= h($atividades_realizadas ?? '') ?></textarea></div>

      <div style="margin-top:12px"><label>Atividades Previstas</label>
        <textarea name="atividades_previstas"><?= h($atividades_previstas ?? '') ?></textarea></div>

      <div style="margin-top:12px"><label>Pontos Relevantes</label>
        <textarea name="pontos_relevantes"><?= h($pontos_relevantes ?? '') ?></textarea></div>

      <!-- Extras visuais (NÃO enviados no payload) -->
      <div style="margin-top:12px"><label>Atividade em Andamento (opcional)</label>
        <textarea name="atividade_andamento"><?= h($atividade_andamento ?? '') ?></textarea></div>
      <div style="margin-top:12px"><label>Pontos Críticos (opcional)</label>
        <textarea name="pontos_criticos"><?= h($pontos_criticos ?? '') ?></textarea></div>

      <div style="display:flex;justify-content:flex-end;align-items:center;margin-top:14px;gap:10px">
        <!-- Se quiser manter o e-mail, deixe; caso contrário, remova o bloco abaixo -->
        <!-- <label class="small"><input type="checkbox" name="enviar_por_email" value="1" <?= !empty($enviar_por_email) ? 'checked' : '' ?>> Enviar cópia por e-mail</label> -->
        <button class="btn" type="submit">Enviar</button>
      </div>
    </form>
  </main>

<script>
const i=document.getElementById('data_inicial'),f=document.getElementById('data_final');
const hoje=new Date().toISOString().split('T')[0];
if(i&&f){
  i.max=f.max=hoje;
  i.addEventListener('change',()=>{if(i.value>hoje){alert('A data inicial não pode ser no futuro!');i.value='';return}
    if(f.value&&i.value>f.value){alert('A data inicial não pode ser maior que a data final!');i.value='';return}
    f.min=i.value});
  f.addEventListener('change',()=>{if(f.value>hoje){alert('A data final não pode ser no futuro!');f.value='';return}
    if(i.value&&f.value<i.value){alert('A data final não pode ser menor que a data inicial!');f.value='';return}
    i.max=f.value});
}
</script>
</body>
</html>
