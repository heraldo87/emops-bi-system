<?php
// request_min.php — página simples para fazer uma requisição e mostrar a resposta.
// Agora com preview de imagem quando Content-Type for image/*

$defaultUrl = 'https://n8n.alunosdamedicina.com/webhook-test/graficos_dinamicos';
$method     = $_POST['method'] ?? 'GET';
$url        = trim($_POST['url'] ?? $defaultUrl);
$bodyIn     = $_POST['body'] ?? '';

$result = null; $err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ch = curl_init($url);
  $opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,   // inclui headers na resposta
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_CUSTOMREQUEST  => $method,
  ];
  // Se NÃO for GET e houver corpo, envia
  if ($method !== 'GET' && $bodyIn !== '') {
    $opts[CURLOPT_POSTFIELDS] = $bodyIn;
    // Se parece JSON, define Content-Type automaticamente
    $headers = [];
    $trim = ltrim($bodyIn);
    if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
      $headers[] = 'Content-Type: application/json';
    }
    if ($headers) $opts[CURLOPT_HTTPHEADER] = $headers;
  }
  curl_setopt_array($ch, $opts);
  $raw = curl_exec($ch);
  if ($raw === false) {
    $err = curl_error($ch);
  } else {
    $hs     = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $result = [
      'http_code'    => curl_getinfo($ch, CURLINFO_HTTP_CODE),
      'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '',
      'headers'      => substr($raw, 0, $hs),
      'body'         => substr($raw, $hs),
    ];
  }
  curl_close($ch);
}
?>
<!doctype html>
<meta charset="utf-8">
<title>request_min · teste de requisição</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{font-family:system-ui,Arial;margin:24px;background:#0f172a;color:#e5e7eb}
  .card{background:#0b1228;border:1px solid #1e293b;border-radius:12px;padding:16px;max-width:900px;margin:auto}
  label{display:block;margin:.5rem 0 .25rem}
  input,textarea,select,button{width:100%;box-sizing:border-box;background:#0b1228;color:#e5e7eb;border:1px solid #334155;border-radius:10px;padding:10px}
  textarea{min-height:120px}
  button{background:#2563eb;border:0;width:auto;cursor:pointer}
  pre{white-space:pre-wrap;word-break:break-word;background:#0b1228;border:1px solid #1e293b;border-radius:10px;padding:12px}
  .row{display:flex;gap:12px;flex-wrap:wrap}
  .col{flex:1 1 260px}
  .tag{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #1e293b;margin-right:6px}
  img{max-width:100%;height:auto;border:1px solid #1e293b;border-radius:10px}
  .muted{color:#94a3b8}
</style>
<div class="card">
  <h1 style="margin:0 0 12px">request_min · teste rápido</h1>
  <form method="post">
    <label>URL</label>
    <input name="url" value="<?= htmlspecialchars($url) ?>">

    <div class="row">
      <div class="col">
        <label>Método</label>
        <select name="method">
          <?php foreach (['GET','POST','PUT','PATCH','DELETE'] as $m): ?>
            <option value="<?= $m ?>" <?= $method===$m?'selected':'' ?>><?= $m ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>&nbsp;</label>
        <button type="submit">Enviar</button>
      </div>
    </div>

    <label>Corpo (opcional; se começar com { ou [, envia como JSON)</label>
    <textarea name="body"><?= htmlspecialchars($bodyIn) ?></textarea>
  </form>
</div>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
  <div class="card" style="margin-top:16px">
    <h2 style="margin:0 0 8px">Resposta</h2>
    <?php if ($err): ?>
      <p style="color:#f87171">Erro cURL: <?= htmlspecialchars($err) ?></p>
    <?php else: ?>
      <p>
        <span class="tag">HTTP <?= intval($result['http_code']) ?></span>
        <span class="tag">Content-Type: <?= htmlspecialchars($result['content_type'] ?: '—') ?></span>
      </p>
      <details open><summary>Headers</summary><pre><?= htmlspecialchars($result['headers']) ?></pre></details>

      <?php
        $ct = strtolower($result['content_type'] ?? '');
        $body = $result['body'] ?? '';

        // Se for imagem, exibe inline (data URL) e link para download
        if ($ct && substr($ct,0,6)==='image/') {
          $b64 = base64_encode($body);
          $fname = 'arquivo';
          // tenta extrair filename de Content-Disposition (se houver)
          if (preg_match('/filename="?([^"]+)"?/i', $result['headers'], $m)) {
            $fname = $m[1];
          } else {
            // fallback por tipo
            $ext = 'bin';
            if (strpos($ct,'png')!==false)  $ext='png';
            elseif (strpos($ct,'jpeg')!==false || strpos($ct,'jpg')!==false) $ext='jpg';
            elseif (strpos($ct,'svg')!==false) $ext='svg';
            $fname = 'arquivo.'.$ext;
          }
          echo '<p class="muted">Preview da imagem:</p>';
          echo '<img src="data:'.htmlspecialchars($ct).';base64,'.$b64.'" alt="imagem">';
          echo '<p style="margin-top:8px"><a download="'.htmlspecialchars($fname).'" href="data:'.htmlspecialchars($ct).';base64,'.$b64.'">Baixar '.$fname.'</a></p>';
        }
        // Se for texto/JSON/XML, mostra formatado
        elseif ($ct && (substr($ct,0,5)==='text/' || strpos($ct,'json')!==false || strpos($ct,'xml')!==false)) {
          $dec = json_decode($body, true);
          if (json_last_error() === JSON_ERROR_NONE) {
            $body = json_encode($dec, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
          }
          echo '<details open><summary>Body</summary><pre>'. htmlspecialchars($body) .'</pre></details>';
        }
        // Qualquer outro binário: mostra tamanho
        else {
          echo '<p>Body (binário) — bytes: '.number_format(strlen($body)).'</p>';
        }
      ?>
    <?php endif; ?>
  </div>
<?php endif; ?>
