<?php
// test_n8n_post_chart_cli.php — executa o MESMO curl do terminal e renderiza a resposta
// Requer: proc_open habilitado e /usr/bin/curl disponível

$endpoint_prod = 'https://n8n.alunosdamedicina.com/webhook/graficos_dinamicos';
$endpoint_test = 'https://n8n.alunosdamedicina.com/webhook-test/graficos_dinamicos';

$isTest = isset($_POST['testmode']);
$url    = trim($_POST['url'] ?? ($isTest ? $endpoint_test : $endpoint_prod));
$labels = $_POST['labels'] ?? 'Realizado,Pendente';
$data   = $_POST['data']   ?? '72,28';

$payload = json_encode([
  'labels' => array_map('trim', explode(',', $labels)),
  'data'   => array_map('intval', explode(',', $data)),
], JSON_UNESCAPED_UNICODE);

$curlBin = is_executable('/usr/bin/curl') ? '/usr/bin/curl' :
           (is_executable('/usr/local/bin/curl') ? '/usr/local/bin/curl' : 'curl');

$cmd = escapeshellcmd($curlBin)
     . ' -sS -i --location --max-time 20 '
     . escapeshellarg($url)
     . ' -H ' . escapeshellarg('Content-Type: application/json')
     . ' -d ' . escapeshellarg($payload);

$descriptors = [
  0 => ["pipe","r"],
  1 => ["pipe","w"],
  2 => ["pipe","w"],
];
$proc = @proc_open($cmd, $descriptors, $pipes);
if (!is_resource($proc)) {
  http_response_code(500);
  exit("proc_open indisponível. Habilite em disable_functions e reinicie o PHP-FPM.");
}
fclose($pipes[0]);
$out = stream_get_contents($pipes[1]); fclose($pipes[1]);
$err = stream_get_contents($pipes[2]); fclose($pipes[2]);
$code = proc_close($proc);

// Parse headers/body (considera redirects)
$parts = preg_split("/\r\n\r\n/", $out);
if (count($parts) < 2) {
  http_response_code(502);
  echo "Saída inesperada do curl.\nSTDERR:\n$err";
  exit;
}
$rawHeaders = $parts[count($parts)-2];
$body       = $parts[count($parts)-1];

$http = '—'; $ctype = '';
if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/m', $rawHeaders, $m)) $http = $m[1];
if (preg_match('/\bContent-Type:\s*([^\r\n]+)/i', $rawHeaders, $m)) $ctype = trim($m[1]);

if ($ctype && stripos($ctype, 'image/') === 0) {
  header('Content-Type: '.$ctype);
  header('Cache-Control: no-cache');
  echo $body; // a página vira a imagem
  exit;
}

// Se não for imagem, mostra diagnóstico
header('Content-Type: text/plain; charset=utf-8');
echo "HTTP: $http\nContent-Type: $ctype\n\nHeaders:\n$rawHeaders\n\nBody:\n$body\n";
