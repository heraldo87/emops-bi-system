<?php
// api_aai_list.php — lista paginada/filtrada de AAI
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth_check.php';
require_login(2);
require_once __DIR__ . '/conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;
$dir   = trim($_GET['diretoria'] ?? '');
$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$pp    = min(1000, max(1, (int)($_GET['per_page'] ?? 50)));
$off   = ($page-1)*$pp;

$reDate='/^\d{4}-\d{2}-\d{2}$/';
if($start && !preg_match($reDate,$start)) $start=null;
if($end   && !preg_match($reDate,$end))   $end=null;

$where=[]; $types=''; $params=[];
if ($start && $end){ $where[]="DATE(data_registro) BETWEEN ? AND ?"; $types.='ss'; $params[]=$start; $params[]=$end; }
elseif($start){ $where[]="DATE(data_registro) >= ?"; $types.='s'; $params[]=$start; }
elseif($end){ $where[]="DATE(data_registro) <= ?"; $types.='s'; $params[]=$end; }
if ($dir){ $where[]="diretoria = ?"; $types.='s'; $params[]=$dir; }
if ($q){
  $where[]="(nome LIKE CONCAT('%',?,'%') OR cpf LIKE CONCAT('%',?,'%') OR diretoria LIKE CONCAT('%',?,'%'))";
  $types.='sss'; array_push($params, $q, $q, $q);
}
$WHERE = $where ? 'WHERE '.implode(' AND ',$where) : '';

function q(mysqli $c, string $sql, string $t='', array $p=[]){
  if($t){ $st=$c->prepare($sql); $st->bind_param($t, ...$p); $st->execute(); $r=$st->get_result(); $rows=$r->fetch_all(MYSQLI_ASSOC); $st->close(); return $rows; }
  $r=$c->query($sql); return $r->fetch_all(MYSQLI_ASSOC);
}

$total = (int)(q($conn, "SELECT COUNT(*) AS n FROM acompanhamento_atividades $WHERE", $types, $params)[0]['n'] ?? 0);

$sql = "
  SELECT
    nome, cpf, diretoria,
    DATE_FORMAT(data_inicial,'%Y-%m-%d') AS data_inicial,
    DATE_FORMAT(data_final,'%Y-%m-%d')   AS data_final,
    DATEDIFF(data_final, data_inicial)   AS dias,
    DATE_FORMAT(data_registro,'%Y-%m-%d') AS data_registro,
    atividades_realizadas
  FROM acompanhamento_atividades
  $WHERE
  ORDER BY data_registro DESC, id DESC
  LIMIT ? OFFSET ?
";
$types2 = $types . 'ii';
$params2 = array_merge($params, [$pp, $off]);

$st = $conn->prepare($sql);
$st->bind_param($types2, ...$params2);
$st->execute();
$res = $st->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$st->close();

echo json_encode(['ok'=>true, 'total'=>$total, 'rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
