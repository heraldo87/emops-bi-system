<?php
// aai-mes-atual.php — Volumetria e Distribuição do mês atual (mínimo)
// Requer: conn.php
require_once __DIR__ . '/conn.php';
header('Content-Type: text/html; charset=utf-8');

// Garantir fuso horário BR (Acre)
date_default_timezone_set('America/Rio_Branco');

$start = date('Y-m-01');
$end   = date('Y-m-t');   // inclusivo

// -------- Volumetria diária (barras) --------
$sql_daily = "
  SELECT DATE_FORMAT(COALESCE(data_inicial, data_registro), '%Y-%m-%d') AS d,
         COUNT(*) AS total
  FROM acompanhamento_atividades
  WHERE COALESCE(data_inicial, data_registro) BETWEEN '$start' AND '$end'
  GROUP BY d
  ORDER BY d
";
$map = [];
if ($res = $conn->query($sql_daily)) {
  while ($row = $res->fetch_assoc()) {
    $map[$row['d']] = (int)$row['total'];
  }
  $res->free();
}

// Monta vetores com todos os dias do mês (preenche 0 onde não houver)
$labels_days = [];
$values_days = [];
$lastDay = (int)date('t'); // nº de dias do mês atual
$ym = date('Y-m-');
for ($i = 1; $i <= $lastDay; $i++) {
  $dayStr = sprintf('%02d', $i);
  $labels_days[] = $dayStr;
  $dateKey = $ym . $dayStr;
  $values_days[] = $map[$dateKey] ?? 0;
}

// -------- Distribuição por diretoria (rosca) --------
$sql_dir = "
  SELECT COALESCE(NULLIF(TRIM(diretoria), ''), 'Sem diretoria') AS diretoria,
         COUNT(*) AS total
  FROM acompanhamento_atividades
  WHERE COALESCE(data_inicial, data_registro) BETWEEN '$start' AND '$end'
  GROUP BY diretoria
  ORDER BY total DESC
";
$pie_data = [];
if ($res2 = $conn->query($sql_dir)) {
  while ($row = $res2->fetch_assoc()) {
    $pie_data[] = ['name' => $row['diretoria'], 'value' => (int)$row['total']];
  }
  $res2->free();
}
$conn->close();

$labels_days_json = json_encode($labels_days, JSON_UNESCAPED_UNICODE);
$values_days_json = json_encode($values_days, JSON_UNESCAPED_UNICODE);
$pie_data_json    = json_encode($pie_data, JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>AAI · Mês atual — Volumetria & Distribuição</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root { --bg:#0b1020; --card:#111735; --bd:#24306b; --tx:#e7ecff; }
    * { box-sizing: border-box }
    body { margin:0; background:var(--bg); color:var(--tx); font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif; }
    .wrap { max-width: 1100px; margin: 16px auto; padding: 0 12px; }
    .grid { display:grid; gap:12px; grid-template-columns: 1fr; }
    @media(min-width:960px){ .grid { grid-template-columns: 1fr 1fr; } }
    .card { background:var(--card); border:1px solid var(--bd); border-radius:14px; padding:10px; }
    .h { font-weight:600; margin:6px 8px 8px; font-size:14px; opacity:.9 }
    .chart { width:100%; height:380px; }
    .sub { font-size:12px; opacity:.8; margin:0 8px 8px }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/echarts@5"></script>
</head>
<body>
  <div class="wrap">
    <div class="grid">
      <div class="card">
        <div class="h">Volumetria diária — mês atual (<?= htmlspecialchars(date('F Y')) ?>)</div>
        <div class="sub">Contagem de lançamentos por dia (base: <code>acompanhamento_atividades</code>).</div>
        <div id="c1" class="chart"></div>
      </div>
      <div class="card">
        <div class="h">Distribuição por Diretoria — mês atual</div>
        <div class="sub">Participação de cada diretoria nos lançamentos do mês.</div>
        <div id="c2" class="chart"></div>
      </div>
    </div>
  </div>

  <script>
    const labelsDays = <?php echo $labels_days_json; ?>;
    const valuesDays = <?php echo $values_days_json; ?>;
    const pieData    = <?php echo $pie_data_json; ?>;

    const c1 = echarts.init(document.getElementById('c1'));
    c1.setOption({
      tooltip: { trigger: 'axis' },
      grid: { left: 40, right: 20, top: 20, bottom: 40 },
      xAxis: { type: 'category', data: labelsDays, axisLabel: { rotate: 0 } },
      yAxis: { type: 'value' },
      series: [{ type: 'bar', data: valuesDays }]
    });

    const c2 = echarts.init(document.getElementById('c2'));
    c2.setOption({
      tooltip: { trigger: 'item' },
      series: [{
        type: 'pie',
        radius: ['50%','75%'],
        avoidLabelOverlap: true,
        data: pieData,
        label: { show: true, formatter: '{b}: {c}' }
      }]
    });

    addEventListener('resize', () => { c1.resize(); c2.resize(); });
  </script>
</body>
</html>
