<?php
// api/kpis.php — KPIs e séries AAI (MySQL → JSON)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conn.php'; // usa seu conn.php (host, user, pass, db)

try {
  // KPIs principais
  $total = (int)($conn->query("SELECT COUNT(*) t FROM acompanhamento_atividades")->fetch_assoc()['t'] ?? 0);
  $ult30 = (int)($conn->query("SELECT COUNT(*) t FROM acompanhamento_atividades WHERE data_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['t'] ?? 0);
  $colab = (int)($conn->query("SELECT COUNT(DISTINCT cpf) t FROM acompanhamento_atividades")->fetch_assoc()['t'] ?? 0);

  // Filtros opcionais (?inicio=YYYY-MM-DD&fim=YYYY-MM-DD)
  $inicio = $_GET['inicio'] ?? null;
  $fim    = $_GET['fim'] ?? null;
  $where  = "WHERE 1=1";
  if ($inicio && preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) $where .= " AND DATE(data_registro) >= '".$conn->real_escape_string($inicio)."'";
  if ($fim && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim))       $where .= " AND DATE(data_registro) <= '".$conn->real_escape_string($fim)."'";

  // Série diária (últimos 90 dias por padrão)
  $serieSQL = $where === "WHERE 1=1"
    ? "WHERE data_registro >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)"
    : $where;

  $dias=[]; $qtds=[];
  $q = $conn->query("
    SELECT DATE(data_registro) dia, COUNT(*) qtd
    FROM acompanhamento_atividades
    $serieSQL
    GROUP BY DATE(data_registro)
    ORDER BY dia ASC
  ");
  while ($r = $q->fetch_assoc()) { $dias[]=$r['dia']; $qtds[]=(int)$r['qtd']; }

  // Top 10 diretorias
  $lab=[]; $val=[];
  $q2 = $conn->query("
    SELECT COALESCE(NULLIF(TRIM(diretoria), ''), 'Não informado') AS diretoria, COUNT(*) qtd
    FROM acompanhamento_atividades
    GROUP BY diretoria
    ORDER BY qtd DESC
    LIMIT 10
  ");
  while ($r2 = $q2->fetch_assoc()) { $lab[]=$r2['diretoria']; $val[]=(int)$r2['qtd']; }

  echo json_encode([
    'kpis'=>[
      'total_registros'=>$total,
      'ultimos_30_dias'=>$ult30,
      'colaboradores_unicos'=>$colab
    ],
    'series'=>['dias'=>$dias,'qtds'=>$qtds],
    'top_diretoria'=>['labels'=>$lab,'values'=>$val]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Falha ao consultar','detail'=>$e->getMessage()]);
}
