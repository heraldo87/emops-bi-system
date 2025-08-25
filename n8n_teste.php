<?php
// test_n8n_webhook.php — Teste de Webhook n8n com renderização de gráfico (PNG)
// Requisitos: PHP com cURL habilitado.

$defaultUrlProd = 'https://n8n.alunosdamedicina.com/webhook/graficos_dinamicos';
$defaultUrlTest = 'https://n8n.alunosdamedicina.com/webhook-test/graficos_dinamicos';

$mode   = $_POST['mode']   ?? 'post'; // 'post' ou 'get'
$isTest = !empty($_POST['testmode']);
$url    = trim($_POST['url'] ?? ($isTest ? $defaultUrlTest : $defaultUrlProd));

$labelsStr = $_POST['labels'] ?? 'Realizado,Pendente';
$dataStr   = $_POST['data']   ?? '72,28';

$defaultJson = json_encode([
  "labels" => array_map('trim', explode(',', $labelsStr)),
  "data"   => array_map('intval', explode(',', $dataStr)),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$jsonText = $_POST['json'] ?? $defaultJson;
$insecure = !empty($_POST['insecure']);

$resp = null; $err = null; $info = null; $headersRaw = ''; $body = '';
$imgSrc = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($mode === 'post') {
    // === POST JSON e renderiza a resposta ===
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $jsonText,
      CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: */*'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => true,
      CURLOPT_TIMEOUT        => 30,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_SSL_VERIFYPEER => $insecure ? 0 : 1,
      CURLOPT_SSL_VERIFYHOST => $insecure ? 0 : 2,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
      $err = curl_error($ch);
    } else {
      $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $headersRaw = substr($resp, 0, $headerSize);
      $body       = substr($resp, $headerSize);
      $info       = curl_getinfo($ch);
    }
    curl_close($ch);
  } else {
    // === GET imagem: a página só renderiza <img src="..."> ===
    // Monta a URL com querystring labels/data
    $qs = http_build_query([
      'labels' => $labelsStr,
      'data'   => $dataStr,
    ]);
    $imgSrc = rtrim($url, '?') . '?' . $qs;
  }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Teste Webhook n8n · Gráfico</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{--tx:#e5e7eb}
  body{margin:0;background:#0f172a;color:var(--tx);font-family:Inter,system-ui,Arial}
  .wrap{max-width:980px;margin:24px auto;padding:0 16px}
  .card{background:#0b1228;border:1px solid #1e293b;border-radius:14px;padding:16px}
  h1{font-size:20px;margin:.2rem 0 1rem}
  label{display:block;font-size:14px;margin:.6rem 0 .3rem}
  input[type=text],textarea{width:100%;background:#0b1228;border:1px solid #334155;color:#e5e7eb;border-radius:10px;padding:10px;font-family:ui-monospace,Consolas,monospace}
  textarea{min-height:160px;resize:vertical}
  .row{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
  .btn{background:#2563eb;color:#fff;border:0;border-radius:10px;padding:10px 14px;cursor:pointer}
  .btn:active{transform:translateY(1px)}
  .muted{color:#94a3b8;font-size:12px}
  pre{white-space:pre-wrap;word-break:break-word;background:#0b1228;border:1px solid #1e293b;border-radius:10px;padding:12px}
  .tag{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid #1e293b;background:#0b1228}
  .good{color:#22c55e}.bad{color:#ef4444}
  .grid{display:grid;grid-template-columns:1fr;gap:16px}
  @media(min-width:860px){.grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="wrap">
  <h1>Teste Webhook n8n · Gráfico</h1>

  <form method="post" class="card">
    <div class="row" style="justify-content:space-between">
      <div>
        <label><input type="checkbox" name="testmode" <?= $isTest ? 'checked' : '' ?> onchange="this.form.submit()"> Usar modo de teste (/webhook-test)</label>
        <div class="muted">Deixe o nó Webhook em <b>Listen for Test Event</b> para o modo de teste.</div>
      </div>
      <div class="row">
        <label><input type="radio" name="mode" value="post" <?= $mode==='post'?'checked':'' ?>> POST (JSON)</label>
        <label><input type="radio" name="mode" value="get"  <?= $mode==='get' ?'checked':'' ?>> GET (imagem)</label>
      </div>
    </div>

    <label>URL do Webhook</label>
    <input type="text" name="url" value="<?= htmlspecialchars($url) ?>">

    <div class="grid">
      <div>
        <label>Labels (para GET) — ex.: Realizado,Pendente</label>
        <input type="text" name="labels" value="<?= htmlspecialchars($labelsStr) ?>">
      </div>
      <div>
        <label>Data (para GET) — ex.: 72,28</label>
        <input type="text" name="data" value="<?= htmlspecialchars($dataStr) ?>">
      </div>
    </div>

    <label>Payload (JSON para POST)</label>
    <textarea name="json"><?= htmlspecialchars($jsonText) ?></textarea>

    <div class="row" style="justify-content:space-between;margin-top:10px">
      <label><input type="checkbox" name="insecure" <?= $insecure ? 'checked' : '' ?>> Ignorar SSL (diagnóstico)</label>
      <button class="btn" type="submit">Enviar</button>
    </div>

    <div class="muted" style="margin-top:10px">cURL (POST):</div>
    <pre><?php
      $curlPreview = 'curl -i -X POST "' . htmlspecialchars($url) . "\" \\\n"
                   . '  -H "Content-Type: application/json" \\' . "\n"
                   . '  -d ' . "'" . str_replace("'", "'\"'\"'", $jsonText) . "'";
      echo htmlspecialchars($curlPreview);
    ?></pre>
  </form>

  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="card" style="margin-top:16px">
      <h2 style="font-size:18px;margin:.2rem 0 1rem">Resultado</h2>

      <?php if ($mode === 'get'): ?>
        <p>
          <span class="tag">Método: GET</span>
          <span class="tag">Preview abaixo</span>
        </p>
        <p class="muted" style="margin:.4rem 0">URL gerada:</p>
        <pre><?= htmlspecialchars($imgSrc) ?></pre>
        <p class="muted">Embed direto no seu site:</p>
        <pre><?= htmlspecialchars('<img src="'.$imgSrc.'" alt="Gráfico n8n" style="max-width:100%;height:auto">') ?></pre>
        <p>Preview:</p>
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Gráfico n8n"
             style="width:100%;max-width:780px;border:1px solid #1e293b;border-radius:12px">
      <?php else: ?>
        <?php if ($err): ?>
          <p class="bad">Erro cURL: <?= htmlspecialchars($err) ?></p>
        <?php else: ?>
          <p>
            <span class="tag">Método: POST</span>
            <span class="tag">HTTP <?= intval($info['http_code'] ?? 0) ?></span>
            <span class="tag">Content-Type: <?= htmlspecialchars($info['content_type'] ?? 'desconhecido') ?></span>
            <span class="tag">Tempo: <?= htmlspecialchars(number_format(($info['total_time'] ?? 0), 3)) ?>s</span>
          </p>

          <details>
            <summary>Headers (resposta)</summary>
            <pre><?= htmlspecialchars($headersRaw) ?></pre>
          </details>

          <?php
            $ctype = strtolower($info['content_type'] ?? '');
            if (strpos($ctype, 'image/') === 0) {
              // Renderiza o PNG inline (base64)
              $b64 = base64_encode($body);
              echo '<p>Imagem:</p><img alt="imagem" style="width:100%;max-width:780px;border:1px solid #1e293b;border-radius:12px" src="data:' . htmlspecialchars($ctype) . ';base64,' . $b64 . '">';
              echo '<p class="muted" style="margin-top:8px">Para baixar: <a download="chart.png" href="data:' . htmlspecialchars($ctype) . ';base64,' . $b64 . '">chart.png</a></p>';
            } else {
              // Tenta exibir como JSON bonito ou texto/html
              $decoded = json_decode($body, true);
              if (json_last_error() === JSON_ERROR_NONE) {
                $pretty = json_encode($decoded, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
                echo '<label>Body (JSON)</label><pre>' . htmlspecialchars($pretty) . '</pre>';
              } else {
                echo '<label>Body</label><pre>' . htmlspecialchars($body) . '</pre>';
              }
            }
          ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
