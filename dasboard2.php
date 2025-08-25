<?php
// dashboard-aai.php — EMOPS BI · AAI (filtro único responsivo + rosca distribuída)
require_once __DIR__ . '/auth_check.php';
require_login(2);
require_once __DIR__ . '/conn.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$PROJECT_NAME = 'COHIDRO BI';

// -------------------- Entradas (GET) --------------------
$mesAtual = (int)date('n');
$anoAtual = (int)date('Y');

$m  = isset($_GET['m']) ? max(1, min(12, (int)$_GET['m'])) : $mesAtual;
$y  = isset($_GET['y']) ? ((int)$_GET['y'] ?: $anoAtual) : $anoAtual;
$dir = trim($_GET['dir'] ?? '');

$inicio = sprintf('%04d-%02d-01', $y, $m);
$fim    = date('Y-m-t', strtotime($inicio));

$where = "COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE()) BETWEEN '$inicio' AND '$fim'";
if ($dir !== '') {
  $dirEsc = $conn->real_escape_string($dir);
  $where .= " AND COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') = '$dirEsc'";
}

// -------------------- Combos (anos/diretorias) --------------------
$anos = [];
$res = $conn->query("SELECT DISTINCT YEAR(COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE())) y
                       FROM acompanhamento_atividades
                   ORDER BY y DESC");
while ($r = $res->fetch_assoc()) $anos[] = (int)$r['y'];
if (!in_array($anoAtual, $anos)) array_unshift($anos, $anoAtual);

$todasDiretorias = [];
$res = $conn->query("SELECT DISTINCT COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') d
                       FROM acompanhamento_atividades
                   ORDER BY d");
while ($r = $res->fetch_assoc()) $todasDiretorias[] = $r['d'];

// -------------------- KPIs / Séries (mês filtrado) --------------------
try {
  $kpi_total = (int)($conn->query("SELECT COUNT(*) c FROM acompanhamento_atividades WHERE $where")->fetch_assoc()['c'] ?? 0);

  $kpi_colabs = (int)($conn->query("
      SELECT COUNT(*) c FROM (
        SELECT DISTINCT CASE WHEN NULLIF(TRIM(cpf),'') IS NOT NULL THEN cpf ELSE NULL END k
          FROM acompanhamento_atividades WHERE $where
        UNION
        SELECT DISTINCT CASE WHEN NULLIF(TRIM(cpf),'') IS NULL THEN NULLIF(TRIM(nome),'') ELSE NULL END
          FROM acompanhamento_atividades WHERE $where
      ) x WHERE x.k IS NOT NULL
  ")->fetch_assoc()['c'] ?? 0);

  $kpi_dirs = (int)($conn->query("
      SELECT COUNT(*) c FROM (
        SELECT DISTINCT COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') d
          FROM acompanhamento_atividades
         WHERE $where
      ) x
  ")->fetch_assoc()['c'] ?? 0);

  // Diretoria (ordenado desc)
  $porDiretoria = ['labels'=>[], 'values'=>[]];
  $rs = $conn->query("SELECT COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') diretoria, COUNT(*) qt
                        FROM acompanhamento_atividades
                       WHERE $where
                    GROUP BY 1 ORDER BY qt DESC, diretoria ASC");
  while ($row = $rs->fetch_assoc()) { $porDiretoria['labels'][] = $row['diretoria']; $porDiretoria['values'][] = (int)$row['qt']; }

  // Top 5 colaboradores (mês)
  $topColabs = ['labels'=>[], 'values'=>[]];
  $rs = $conn->query("SELECT COALESCE(NULLIF(TRIM(nome),''),'(sem nome)') nome, COUNT(*) qt
                        FROM acompanhamento_atividades
                       WHERE $where
                    GROUP BY 1 ORDER BY qt DESC, nome ASC LIMIT 5");
  while ($row = $rs->fetch_assoc()) { $topColabs['labels'][] = $row['nome']; $topColabs['values'][] = (int)$row['qt']; }

  // Registros por dia
  $diasNoMes = (int)date('t', strtotime($inicio));
  $countsByDay = array_fill(1, $diasNoMes, 0);
  $rs = $conn->query("
      SELECT DAY(COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE())) d, COUNT(*) qt
        FROM acompanhamento_atividades
       WHERE $where
    GROUP BY d
  ");
  while ($row = $rs->fetch_assoc()) $countsByDay[(int)$row['d']] = (int)$row['qt'];

  $timeline_labels = [];
  $timeline_values = [];
  for ($dia = 1; $dia <= $diasNoMes; $dia++) {
    $timeline_labels[] = str_pad((string)$dia, 2, '0', STR_PAD_LEFT);
    $timeline_values[] = $countsByDay[$dia];
  }

  // Tabela
  $tabela = [];
  $rs = $conn->query("
      SELECT id,
             COALESCE(NULLIF(TRIM(nome),''),'(sem nome)') AS nome,
             NULLIF(TRIM(cpf),'') AS cpf,
             COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') AS diretoria,
             data_inicial, data_final,
             LEFT(COALESCE(atividades_realizadas,''), 500) AS atividades_realizadas,
             LEFT(COALESCE(pontos_relevantes,''), 500) AS pontos_relevantes,
             COALESCE(data_registro, CURRENT_TIMESTAMP()) AS data_registro
        FROM acompanhamento_atividades
       WHERE $where
    ORDER BY COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE()) DESC, id DESC
       LIMIT 1000
  ");
  while ($row = $rs->fetch_assoc()) $tabela[] = $row;

  $DATA = [
    'filters' => ['m'=>$m,'y'=>$y,'dir'=>$dir,'inicio'=>$inicio,'fim'=>$fim],
    'kpis' => ['total'=>$kpi_total,'colabs'=>$kpi_colabs,'dirs'=>$kpi_dirs],
    'porDiretoria'=>$porDiretoria,
    'topColabs'=>$topColabs,
    'timeline'=>['labels'=>$timeline_labels,'values'=>$timeline_values],
    'tabela'=>$tabela,
    'anos'=>$anos,
    'diretorias'=>$todasDiretorias
  ];
} catch (Throwable $e) {
  $DATA = [
    'filters' => ['m'=>$m,'y'=>$y,'dir'=>$dir,'inicio'=>$inicio,'fim'=>$fim],
    'kpis' => ['total'=>0,'colabs'=>0,'dirs'=>0],
    'porDiretoria'=>['labels'=>[],'values'=>[]],
    'topColabs'=>['labels'=>[],'values'=>[]],
    'timeline'=>['labels'=>[],'values'=>[]],
    'tabela'=>[],
    'anos'=>$anos,
    'diretorias'=>$todasDiretorias,
    'error'=>$e->getMessage()
  ];
}

$meses = [1=>'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$mesLabel = $meses[$m] ?? "{$m}";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>COHIDRO BI · Dashboard AAI</title>
  <meta name="theme-color" content="#0b1020" />
  <link rel="icon" href="/favicon.ico" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/cohidro.css" />
  <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" crossorigin="anonymous"></script>
  <style>
    /* ===== HERO + FILTRO RESPONSIVO ===== */
    .hero{
      background: rgba(10,16,36,.65);
      border:1px solid rgba(255,255,255,.08);
      border-radius:20px;
      padding:18px;
      box-shadow: var(--shadow);
      margin-bottom:14px;
    }
    .hero-grid{
      display:grid;
      grid-template-columns: 1.2fr minmax(360px, 560px);
      gap:18px;
      align-items:start;
    }
    .hero h1{ margin:0; font-weight:800; letter-spacing:.2px }
    .hero .meta{ opacity:.9; display:flex; gap:10px; flex-wrap:wrap; font-size:.95rem; margin-top:6px }
    .chip{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); padding:6px 10px; border-radius:999px }

    .filter-grid{
      display:grid;
      grid-template-columns: repeat(4, minmax(140px, 1fr));
      gap:10px;
      align-items:center;
    }
    .filter-grid .input{ width:100% }
    .filter-actions{ display:flex; gap:10px; justify-content:flex-end }

    /* BREAKPOINTS (melhor responsividade) */
    @media (max-width: 1280px){
      .hero-grid{ grid-template-columns: 1fr; }
      .filter-grid{ grid-template-columns: repeat(4, minmax(140px, 1fr)); }
    }
    @media (max-width: 980px){
      .filter-grid{ grid-template-columns: repeat(3, minmax(140px, 1fr)); }
    }
    @media (max-width: 720px){
      .filter-grid{ grid-template-columns: repeat(2, minmax(140px, 1fr)); }
      .filter-actions{ justify-content:stretch }
      .filter-actions .btn{ flex:1 }
    }
    @media (max-width: 460px){
      .filter-grid{ grid-template-columns: 1fr; }
    }

    /* ===== Rosca bem distribuída ===== */
    .donut-grid{
      display:grid;
      grid-template-columns: minmax(260px, 340px) 1fr;
      gap:18px;
      align-items:center;
    }
    .donut-canvas{
      position:relative;
      width:100%;
      height: clamp(200px, 28vh, 320px);  /* sem loop de resize */
      aspect-ratio: 1 / 1;                /* círculo perfeito */
      margin: 0 auto;
    }
    #chartTopColabs{ width:100% !important; height:100% !important; }

    .donut-legend{ width:100%; }
    .donut-legend ul{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
    .donut-legend li{ display:flex; align-items:center; gap:10px; min-height:22px; }
    .legend-box{ width:10px; height:10px; border-radius:3px; display:inline-block; }
    .donut-legend span{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100%; }

    @media (max-width: 900px){
      .donut-grid{ grid-template-columns: 1fr; }
      .donut-legend ul{ flex-direction:row; flex-wrap:wrap; gap:10px 16px; }
      .donut-legend{ text-align:center; }
      .donut-legend li{ justify-content:center; }
    }

    /* ===== Tabela ===== */
    th.sortable{ cursor:pointer; user-select:none }
    th.sortable[aria-sort="asc"]::after{ content:" ▲"; opacity:.75 }
    th.sortable[aria-sort="desc"]::after{ content:" ▼"; opacity:.75 }
    .ellipsis{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:380px;display:block}

    /* Toggle diário/acumulado */
    .seg{ display:inline-flex; border:1px solid rgba(255,255,255,.12); border-radius:12px; overflow:hidden }
    .seg button{ background:transparent; color:var(--text); padding:8px 12px; border:0; cursor:pointer }
    .seg button.active{ background:rgba(255,255,255,.08) }
  </style>
</head>
<body>
  <a href="#conteudo" class="tag" style="position:absolute; left:-1000px; top:-1000px">Pular para o conteúdo</a>

  <div class="container">
    <?php include __DIR__.'/partials/sidebar.php'; ?>

    <div style="display:flex; flex-direction:column; min-width:0">
      <?php include __DIR__.'/partials/topbar.php'; ?>

      <main id="conteudo">
        <!-- HERO + FILTRO ÚNICO -->
        <section class="hero">
          <div class="hero-grid">
            <div>
              <h1>Dashboard AAI</h1>
              <div class="meta">
                <span class="chip">Período: <strong><?= htmlspecialchars("$mesLabel/$y") ?></strong> (<?= htmlspecialchars($DATA['filters']['inicio']) ?> a <?= htmlspecialchars($DATA['filters']['fim']) ?>)</span>
                <span class="chip">Diretoria: <strong><?= htmlspecialchars($dir !== '' ? $dir : 'Todas') ?></strong></span>
                <span class="chip">Registros no período: <strong><?= (int)$DATA['kpis']['total'] ?></strong></span>
              </div>
            </div>

            <form method="get" action="" aria-label="Filtros da dashboard">
              <div class="filter-grid">
                <?php $meses = [1=>'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']; ?>
                <select name="m" class="input" aria-label="Mês">
                  <?php for($i=1;$i<=12;$i++): ?>
                    <option value="<?= $i ?>" <?= $i===$m?'selected':''; ?>><?= $meses[$i] ?></option>
                  <?php endfor; ?>
                </select>

                <select name="y" class="input" aria-label="Ano">
                  <?php foreach($DATA['anos'] as $yy): ?>
                    <option value="<?= (int)$yy ?>" <?= ((int)$yy)===$y ? 'selected':''; ?>><?= (int)$yy ?></option>
                  <?php endforeach; ?>
                </select>

                <select name="dir" class="input" aria-label="Diretoria">
                  <option value="" <?= $dir===''?'selected':''; ?>>Todas as diretorias</option>
                  <?php foreach($DATA['diretorias'] as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>" <?= $dir===$d?'selected':''; ?>><?= htmlspecialchars($d) ?></option>
                  <?php endforeach; ?>
                </select>

                <div class="filter-actions">
                  <button class="btn" type="submit">Aplicar</button>
                  <a class="btn" href="?">Limpar</a>
                </div>
              </div>
            </form>
          </div>
        </section>

        <!-- KPIs -->
        <section class="grid kpis">
          <article class="panel kpi">
            <div class="icon" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="15" width="7" height="6"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpiTotal">—</div><div class="label">Registros (mês)</div></div>
          </article>
          <article class="panel kpi">
            <div class="icon" aria-hidden="true" style="background:rgba(34,211,238,.15); border-color:rgba(34,211,238,.35)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpiColabs">—</div><div class="label">Colaboradores únicos</div></div>
          </article>
          <article class="panel kpi">
            <div class="icon" aria-hidden="true" style="background:rgba(245,158,11,.15); border-color:rgba(245,158,11,.35)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10H7"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M21 18H7"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpiDirs">—</div><div class="label">Diretorias ativas</div></div>
          </article>
        </section>

        <!-- GRÁFICOS -->
        <section class="grid two">
          <article class="panel">
            <h3>Registros por Diretoria (mês)</h3>
            <canvas id="chartDiretoria" height="160"></canvas>
          </article>

          <article class="panel">
            <h3>Top 5 Colaboradores (mês)</h3>
            <div class="donut-grid">
              <div class="donut-canvas">
                <canvas id="chartTopColabs"></canvas>
              </div>
              <div id="legendTopColabs" class="donut-legend" aria-label="Legenda do gráfico de colaboradores"></div>
            </div>
          </article>
        </section>

        <section class="panel">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px">
            <h3 style="margin:0">Registros por dia (<?= htmlspecialchars($mesLabel) ?>/<?= (int)$y ?>)</h3>
            <div class="seg" role="tablist" aria-label="Modo do gráfico de linha">
              <button id="btnDaily" class="active" type="button" role="tab" aria-selected="true">Diário</button>
              <button id="btnCum" type="button" role="tab" aria-selected="false">Acumulado</button>
            </div>
          </div>
          <canvas id="chartTimeline" height="120"></canvas>
        </section>

        <!-- TABELA -->
        <section class="panel">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px">
            <h3 style="margin:0">Registros AAI</h3>
            <div style="display:flex; gap:8px; align-items:center">
              <input id="tblNome" class="input" type="search" placeholder="Filtrar por nome..." style="width:260px">
              <select id="tblLinhas" class="input" style="width:120px">
                <option value="10">10 / pág</option>
                <option value="25">25 / pág</option>
                <option value="50" selected>50 / pág</option>
                <option value="100">100 / pág</option>
              </select>
            </div>
          </div>

          <div class="table-wrap">
            <table id="tAAI" aria-label="Tabela AAI">
              <thead>
                <tr>
                  <th class="sortable" data-key="data_registro" aria-sort="desc">Data Reg.</th>
                  <th class="sortable" data-key="nome">Colaborador</th>
                  <th class="sortable" data-key="cpf">CPF</th>
                  <th class="sortable" data-key="diretoria">Diretoria</th>
                  <th class="sortable" data-key="data_inicial">Período</th>
                  <th class="sortable" data-key="atividades_realizadas">Ativ. Realizadas (resumo)</th>
                  <th class="sortable" data-key="pontos_relevantes">Pontos Relevantes (resumo)</th>
                </tr>
              </thead>
              <tbody id="tbodyAAI">
                <tr><td colspan="7" style="text-align:center; padding:18px; color:var(--muted)">Carregando dados...</td></tr>
              </tbody>
            </table>
          </div>
          <div id="paginacao" style="display:flex; justify-content:flex-end; gap:6px; margin-top:10px"></div>
        </section>

        <footer>© <span id="ano"></span> <?= htmlspecialchars($PROJECT_NAME) ?> · AAI.</footer>
      </main>
    </div>
  </div>

  <div id="toast" style="position:fixed; right:20px; bottom:20px; background:#0b1230; border:1px solid rgba(255,255,255,.12); color:var(--text); padding:12px 14px; border-radius:12px; box-shadow:var(--shadow); display:none"></div>

  <script>
    // ========= Payload do PHP =========
    window.DATA = <?= json_encode($DATA, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

    const $ = (q,ctx=document)=>ctx.querySelector(q);
    const $$ = (q,ctx=document)=>Array.from(ctx.querySelectorAll(q));
    const showToast = (msg)=>{ const t=$('#toast'); t.textContent=msg; t.style.display='block'; setTimeout(()=>t.style.display='none', 2200) };
    const maskCPF = cpf => {
      if(!cpf) return '—';
      const d = String(cpf).replace(/\D/g,'');
      if(d.length === 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,'$1.$2.$3-$4');
      return cpf;
    };

    // ========= KPIs =========
    function renderKPIs(d){
      $('#kpiTotal').textContent  = d.kpis.total ?? 0;
      $('#kpiColabs').textContent = d.kpis.colabs ?? 0;
      $('#kpiDirs').textContent   = d.kpis.dirs ?? 0;
    }

    // Paleta para rosca
    function donutColors(n){
      const base = ['#60a5fa','#34d399','#f472b6','#fbbf24','#a78bfa'];
      const out=[]; for(let i=0;i<n;i++) out.push(base[i%base.length]); return out;
    }

    // ========= Plugin de legenda HTML =========
    const htmlLegendPlugin = {
      id: 'htmlLegend',
      afterUpdate(chart, args, opts) {
        const container = opts.container;
        if (!container) return;
        let ul = container.querySelector('ul');
        if (!ul) { ul = document.createElement('ul'); container.appendChild(ul); }
        ul.innerHTML = '';
        const items = chart.options.plugins.legend.labels.generateLabels(chart);
        items.forEach(item => {
          const li = document.createElement('li'); li.className = 'legend-item';
          li.onclick = () => { chart.toggleDataVisibility(item.index); chart.update(); };
          const box = document.createElement('span'); box.className='legend-box'; box.style.background = item.fillStyle;
          const label = document.createElement('span'); label.textContent = item.text;
          li.appendChild(box); li.appendChild(label); ul.appendChild(li);
        });
      }
    };

    // ========= Charts =========
    let chDiretoria, chTop, chTimeline;
    let timelineMode = 'daily'; // 'daily' | 'cumulative'

    function makeGradient(ctx){
      const g = ctx.createLinearGradient(0,0,0,200);
      g.addColorStop(0, 'rgba(34,211,238,0.28)');
      g.addColorStop(1, 'rgba(34,211,238,0.03)');
      return g;
    }
    function cumulative(arr){ let s=0; return arr.map(v=> (s+=Number(v||0))); }

    function renderCharts(d){
      const brand = 'rgba(79,70,229,0.9)'; // roxo
      const cyan  = 'rgba(34,211,238,0.9)'; // ciano

      // --- Diretoria: horizontal ---
      chDiretoria?.destroy();
      chDiretoria = new Chart($('#chartDiretoria'), {
        type: 'bar',
        data: { labels: d.porDiretoria.labels || [],
                datasets: [{ label: 'Registros', data: d.porDiretoria.values || [], backgroundColor: brand, borderColor: brand, borderWidth:1 }] },
        options: {
          indexAxis: 'y',
          responsive: true,
          plugins: { legend: { display:false }, tooltip:{ mode:'nearest', intersect:false } },
          scales: {
            x: { beginAtZero:true, grid:{ color:'rgba(255,255,255,.06)' } },
            y: { grid:{ color:'rgba(255,255,255,.06)' } }
          }
        }
      });

      // --- Rosca: canvas + legenda externa (ocupando a largura do painel) ---
      chTop?.destroy();
      const donutCtx = $('#chartTopColabs').getContext('2d');
      const legendEl = document.getElementById('legendTopColabs');
      chTop = new Chart(donutCtx, {
        type: 'doughnut',
        data: { labels: d.topColabs.labels || [],
                datasets: [{ data: d.topColabs.values || [], backgroundColor: donutColors((d.topColabs.labels||[]).length) }] },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          resizeDelay: 200,
          animation: { duration: 250 },
          cutout: '60%',
          plugins:{ legend:{ display:false },
                    tooltip:{ callbacks:{ label: (ctx)=> `${ctx.label}: ${ctx.parsed}` } },
                    htmlLegend: { container: legendEl } }
        },
        plugins: [htmlLegendPlugin]
      });

      // --- Timeline: diário / acumulado ---
      chTimeline?.destroy();
      const ctx = $('#chartTimeline').getContext('2d');
      const series = (timelineMode==='daily') ? (d.timeline.values||[]) : cumulative(d.timeline.values||[]);
      chTimeline = new Chart(ctx, {
        type: 'line',
        data: { labels: d.timeline.labels || [],
                datasets: [{ label:(timelineMode==='daily'?'Registros/dia':'Acumulado no mês'),
                             data: series, borderColor: cyan, backgroundColor: makeGradient(ctx), fill:true, tension:.32, pointRadius:2 }] },
        options: {
          plugins:{ legend:{display:false}, tooltip:{ mode:'index', intersect:false } },
          interaction:{ mode:'index', intersect:false },
          scales:{ x:{ grid:{color:'rgba(255,255,255,.06)'} }, y:{ beginAtZero:true, grid:{color:'rgba(255,255,255,.06)'} } }
        }
      });
    }

    // Toggle diário/acumulado
    function setTimelineMode(mode){
      if(mode===timelineMode) return;
      timelineMode = mode;
      $('#btnDaily').classList.toggle('active', mode==='daily');
      $('#btnCum').classList.toggle('active', mode==='cumulative');
      renderCharts(window.DATA);
    }
    $('#btnDaily')?.addEventListener('click', ()=> setTimelineMode('daily'));
    $('#btnCum')?.addEventListener('click',   ()=> setTimelineMode('cumulative'));

    // ========= Tabela =========
    let page=1, perPage=50, sortKey='data_registro', sortDir='desc', nomeFiltro='';
    const tbody = $('#tbodyAAI');

    function cmp(a,b,key){
      const va = a[key] ?? '', vb = b[key] ?? '';
      if(key.startsWith('data')){
        const da = new Date(va), db = new Date(vb);
        return da - db;
      }
      if(typeof va === 'number' && typeof vb === 'number') return va - vb;
      return String(va).localeCompare(String(vb), 'pt-BR', {numeric:true, sensitivity:'base'});
    }

    function renderTable(){
      const arr = (window.DATA.tabela || []).slice();
      const filtered = arr.filter(o => String(o.nome ?? '').toLowerCase().includes(nomeFiltro));
      filtered.sort((a,b)=> (sortDir==='asc' ? cmp(a,b,sortKey) : -cmp(a,b,sortKey)));

      const start = (page-1)*perPage;
      const it = filtered.slice(start, start+perPage);

      tbody.innerHTML = it.map(o=>`
        <tr>
          <td>${(o.data_registro||'').toString().substring(0,10)}</td>
          <td>${o.nome||'—'}</td>
          <td>${maskCPF(o.cpf)}</td>
          <td>${o.diretoria||'(não informado)'}</td>
          <td>${(o.data_inicial||'').toString().substring(0,10)} → ${(o.data_final||'').toString().substring(0,10)}</td>
          <td><span class="ellipsis" title="${(o.atividades_realizadas||'').replaceAll('"','&quot;')}">${o.atividades_realizadas||''}</span></td>
          <td><span class="ellipsis" title="${(o.pontos_relevantes||'').replaceAll('"','&quot;')}">${o.pontos_relevantes||''}</span></td>
        </tr>`).join('') || `<tr><td colspan="7" style="text-align:center; padding:18px; color:var(--muted)">Nenhum resultado.</td></tr>`;

      // paginação
      const total = filtered.length;
      const pages = Math.max(1, Math.ceil(total/perPage));
      page = Math.min(page, pages);
      const p = $('#paginacao'); p.innerHTML = '';
      const mk = (n,txt=n)=>{
        const b=document.createElement('button');
        b.className='btn'; b.textContent=txt; b.disabled = (n===page);
        b.addEventListener('click',()=>{page=n; renderTable(); window.scrollTo({top:0,behavior:'smooth'})});
        return b;
      };
      p.append(mk(1,'«'));
      if(page>1) p.append(mk(page-1,'‹'));
      for(let n=Math.max(1,page-2); n<=Math.min(pages,page+2); n++) p.append(mk(n));
      if(page<pages) p.append(mk(page+1,'›'));
      p.append(mk(pages,'»'));
    }

    // eventos tabela
    $('#tblNome').addEventListener('input', e=>{ nomeFiltro = (e.target.value||'').trim().toLowerCase(); page=1; renderTable(); });
    $('#tblLinhas').addEventListener('change', e=>{ perPage = Number(e.target.value)||50; page=1; renderTable(); });

    // ordenação
    $$('#tAAI thead th.sortable').forEach(th=>{
      th.addEventListener('click', ()=>{
        const key = th.dataset.key;
        if(sortKey === key){ sortDir = (sortDir==='asc'?'desc':'asc'); }
        else { sortKey = key; sortDir = key.startsWith('data') ? 'desc' : 'asc'; }
        $$('#tAAI thead th.sortable').forEach(x=>x.setAttribute('aria-sort','none'));
        th.setAttribute('aria-sort', sortDir);
        renderTable();
      });
    });

    // Boot
    function init(){
      $('#ano').textContent = new Date().getFullYear();
      const d = window.DATA || {};
      renderKPIs(d);
      renderCharts(d);
      renderTable();
      if(d.error){ console.error(d.error); }
    }
    window.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>
