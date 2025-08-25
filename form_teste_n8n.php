<?php
// ping_n8n.php — POST simples para n8n e exibe a resposta

// Altere aqui se quiser testar local:
// $ENDPOINT = 'http://localhost:5678/webhook/formulario_aai';
$ENDPOINT = 'https://n8n.alunosdamedicina.com/webhook/formulario_aai';

// Payload mínimo de teste (ajuste se quiser)
$payload = [
  'cpf'                     => '12606110781',
  'colaborador'             => 'Ping Automático',
  'setor'                   => 'Diretoria de Engenharia',
  'coordenacao'             => 'DIM',
  'funcao'                  => 'Assistente',
  'periodo' => [
    'data_inicial'          => date('Y-m-d', strtotime('-7 days')),
    'data_final'            => date('Y-m-d'),
  ],
  'atividades_realizadas'   => 'Teste de integração via PHP cURL',
  'atividades_em_andamento' => '—',
  'atividades_previstas'    => '—',
  'pontos_relevantes'       => '—',
  'pontos_criticos'         => '—',
  'enviar_por_email'        => 0,
];

$result = null; $http = null; $err = null; $info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ch = curl_init($ENDPOINT);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
    CURLOPT_TIMEOUT        => 20,
  ]);
  $result = curl_exec($ch);
  $http   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err    = curl_error($ch);
  $info   = curl_getinfo($ch);
  curl_close($ch);
}
?>
<!doctype html>
<html lang="pt-BR">
<meta charset="utf-8">
<title>Ping n8n</title>
<body style="font-family:system-ui,Arial;padding:20px">
  <h1>Ping n8n (POST)</h1>
  <form method="post">
    <button type="submit">Enviar POST de teste</button>
  </form>

  <?php if ($http !== null): ?>
    <h3>Resultado</h3>
    <p><b>Status:</b> <?= htmlspecialchars($http) ?></p>
    <?php if ($err): ?>
      <p style="color:#c00"><b>Erro cURL:</b> <?= htmlspecialchars($err) ?></p>
    <?php endif; ?>
    <p><b>Corpo da resposta:</b></p>
    <pre style="background:#f6f6f6;padding:12px;border:1px solid #ddd;white-space:pre-wrap"><?= htmlspecialchars($result) ?></pre>
    <details>
      <summary>Debug (curl_getinfo)</summary>
      <pre><?= htmlspecialchars(print_r($info, true)) ?></pre>
    </details>
  <?php endif; ?>
</body>
</html>
