<?php
// chart_proxy.php — recebe GET (?labels=..&data=..) e faz POST JSON ao n8n; devolve PNG
$endpoint = 'https://n8n.alunosdamedicina.com/webhook/graficos_dinamicos'; // ou -test
$labels = $_GET['labels'] ?? 'Realizado,Pendente';
$data   = $_GET['data']   ?? '72,28';
$payload = json_encode([
  'labels' => array_map('trim', explode(',', $labels)),
  'data'   => array_map('intval', explode(',', $data)),
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => $payload,
  CURLOPT_HTTPHEADER     => ['Content-Type: application/json','Accept: */*'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HEADER         => true,
  CURLOPT_TIMEOUT        => 30,
  CURLOPT_FOLLOWLOCATION => true,
]);
$resp = curl_exec($ch);
if ($resp === false) {
  http_response_code(502);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Erro ao contatar n8n: ' . curl_error($ch);
  exit;
}
$hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($resp, $hs);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

header('Cache-Control: no-cache');
header('Content-Type: ' . ($ctype ?: 'image/png'));
echo $body;
