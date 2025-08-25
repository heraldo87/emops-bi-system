<?php
// api_dashboard.php — KPIs + gráficos a partir de acompanhamento_atividades
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth_check.php';
require_login(2);
require_once __DIR__ . '/conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$start = isset($_GET['start']) ? $_GET['start'] : null;
$end   = isset($_GET['end'])   ? $_GET['end']   : null;
$dir   = isset($_GET['diretoria']) ? trim($_GET['diretoria']) : null;

$reDate = '/^\d{4}-\d{2}-\d{2}$/';
if ($start && !preg_match($reDate,$start)) $start=null;
if ($end   && !preg_match($reDate,$end))   $end=null;

$where=[]; $types=''; $params=[];
if ($start && $end){ $where[]="DATE(data_registro) BETWEEN ? AND ?"; $types.='ss'; $params[]=$start; $params[]=$end; }
elseif($start){ $where[]="DATE(data_registro) >= ?"; $types.='s'; $params[]=$start; }
elseif($end){ $where[]="DATE(data_registro) <= ?"; $types.='s'; $params[]=$end; }
if ($dir){ $where[]="diretoria = ?"; $types.='s'; $params[]=$dir; }
$WHERE = $where ? 'WHERE '.implode(' AND ',$where) : '';

function q(mysqli $c, string $sql, string $t='', array $p=[]): array {
  if($t){ $st=$c->prepare($sql); $st->bind_param($t, ...$p); $st->execute(); $r=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close(); return $r; }
  $r=$c->query($sql); return $r->fetch_all(MYSQLI_ASSOC);
}

$total = (int)(q($conn, "SELECT COUNT(*) AS n FROM acompanhamento_atividades $WHERE", $types, $params)[0]['n'] ?? 0);

$media_dias = q($conn, "
  SELECT AVG(DATEDIFF(data_final, data_inicial)) AS m
  FROM acompanhamento_atividades
  $WHERE
  AND data_inicial IS NOT NULL AND data_final IS NOT NULL
", $types, $params);
$media_dias = isset($media_dias[0]['m']) ? round((float)$media_dias[0]['m'], 2) : null;

$serie = q($conn, "
  SELECT DATE(data_registro) AS dia, COUNT(*) AS qtd
  FROM acompanhamento_atividades
  $WHERE
  GROUP BY DATE(data_registro)
  ORDER BY DATE(data_registro)
", $types, $params);

$por_diretoria = q($conn, "
  SELECT diretoria, COUNT(*) AS qtd
  FROM acompanhamento_atividades
  $WHERE
  GROUP BY diretoria
  ORDER BY qtd DESC
", $types, $params);

$top = q($conn, "
  SELECT nome, COUNT(*) AS qtd
  FROM acompanhamento_atividades
  $WHERE
  GROUP BY nome
  ORDER BY qtd DESC
  LIMIT 10
", $types, $params);

$dirs = q($conn, "SELECT DISTINCT diretoria FROM acompanhamento_atividades ORDER BY diretoria");

echo json_encode([
  'ok'=>true,
  'kpis'=>[
    'total_registros'=>$total,
    'duracao_media_dias'=>$media_dias
  ],
  'serie_temporal'=>$serie,
  'por_diretoria'=>$por_diretoria,
  'top_colaboradores'=>$top,
  'diretorias'=>$dirs
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
