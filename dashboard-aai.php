<?php
// dashboard-aai.php — EMOPS BI · Dashboard AAI (MySQL)
// - KPIs e gráficos a partir da tabela acompanhamento_atividades
// - Requer: auth_check.php, conn.php, partials/sidebar.php, partials/topbar.php

require_once __DIR__ . '/auth_check.php';
require_login(2);
require_once __DIR__ . '/conn.php';

$PROJECT_NAME = 'COHIDRO BI';

// =====================
// COLETA DE INDICADORES
// =====================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

try {
  // KPI 1: total de registros
  $total = (int)($conn->query("SELECT COUNT(*) AS c FROM acompanhamento_atividades")->fetch_assoc()['c'] ?? 0);

  // KPI 2: últimos 30 dias (usa data_registro quando disponível; cai para data_final)
  $ultimos30 = (int)($conn->query("
      SELECT COUNT(*) AS c
        FROM acompanhamento_atividades
       WHERE COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE()) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
  ")->fetch_assoc()['c'] ?? 0);

  // KPI 3: colaboradores únicos (pelo CPF; se vazio, cai para nome)
  $colabs = (int)($conn->query("
      SELECT COUNT(*) AS c FROM (
        SELECT DISTINCT CASE WHEN NULLIF(TRIM(cpf),'') IS NOT NULL THEN cpf ELSE NULL END AS k
          FROM acompanhamento_atividades
        UNION
        SELECT DISTINCT CASE WHEN NULLIF(TRIM(cpf),'') IS NULL THEN NULLIF(TRIM(nome),'') ELSE NULL END
          FROM acompanhamento_atividades
      ) t WHERE t.k IS NOT NULL
  ")->fetch_assoc()['c'] ?? 0);

  // KPI 4: diretorias ativas
  $dirs = (int)($conn->query("
      SELECT COUNT(*) AS c
        FROM (SELECT DISTINCT COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') AS d
                FROM acompanhamento_atividades) t
  ")->fetch_assoc()['c'] ?? 0);

  // Registros por Diretoria
  $rs = $conn->query("
      SELECT COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') AS diretoria,
             COUNT(*) AS qt
        FROM acompanhamento_atividades
    GROUP BY 1
    ORDER BY qt DESC, diretoria ASC
  ");
  $porDiretoria = ['labels'=>[], 'values'=>[]];
  while ($row = $rs->fetch_assoc()) {
    $porDiretoria['labels'][] = $row['diretoria'];
    $porDiretoria['values'][] = (int)$row['qt'];
  }

  // Top 5 colaboradores por nº de registros (usa nome como label; empata com cpf)
  $rs = $conn->query("
      SELECT
        COALESCE(NULLIF(TRIM(nome),''), '(sem nome)') AS nome,
        COUNT(*) AS qt
      FROM acompanhamento_atividades
      GROUP BY 1
      ORDER BY qt DESC, nome ASC
      LIMIT 5
  ");
  $topColabs = ['labels'=>[], 'values'=>[]];
  while ($row = $rs->fetch_assoc()) {
    $topColabs['labels'][] = $row['nome'];
    $topColabs['values'][] = (int)$row['qt'];
  }

  // Registros por mês — últimos 12 meses
  $rs = $conn->query("
      SELECT DATE_FORMAT(COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE()), '%Y-%m') AS ym,
             COUNT(*) AS qt
        FROM acompanhamento_atividades
       WHERE COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE()) >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY ym
    ORDER BY ym
  ");
  $timeline_labels = [];
  $timeline_values = [];
  while ($row = $rs->fetch_assoc()) {
    // Formata para mm/aaaa
    $ym = $row['ym']; // ex: 2025-08
    $timeline_labels[] = substr($ym,5,2) . '/' . substr($ym,0,4);
    $timeline_values[] = (int)$row['qt'];
  }

  // Tabela — últimas 500 linhas (pode ajustar conforme necessidade)
  $rs = $conn->query("
      SELECT
        id,
        COALESCE(NULLIF(TRIM(nome),''),'(sem nome)') AS nome,
        NULLIF(TRIM(cpf),'') AS cpf,
        COALESCE(NULLIF(TRIM(diretoria),''),'(não informado)') AS diretoria,
        data_inicial, data_final,
        LEFT(COALESCE(atividades_realizadas,''), 160) AS atividades_realizadas,
        LEFT(COALESCE(atividades_previstas,''), 160) AS atividades_previstas,
        LEFT(COALESCE(pontos_relevantes,''), 160) AS pontos_relevantes,
        COALESCE(data_registro, CURRENT_TIMESTAMP()) AS data_registro
      FROM acompanhamento_atividades
      ORDER BY COALESCE(data_registro, data_final, data_inicial, CURRENT_DATE()) DESC, id DESC
      LIMIT 500
  ");
  $tabela = [];
  while ($row = $rs->fetch_assoc()) {
    $tabela[] = $row;
  }

  $DATA = [
    'kpis' => [
      'total' => $total,
      'ultimos30' => $ultimos30,
      'colabs' => $colabs,
      'dirs' => $dirs
    ],
    'porDiretoria' => $porDiretoria,
    'topColabs' => $topColabs,
    'timeline' => ['labels'=>$timeline_labels, 'values'=>$timeline_values],
    'tabela' => $tabela
  ];
} catch (Throwable $e) {
  // Em caso de erro, envia payload vazio para evitar quebra do front
  $DATA = [
    'kpis' => ['total'=>0,'ultimos30'=>0,'colabs'=>0,'dirs'=>0],
    'porDiretoria' => ['labels'=>[],'values'=>[]],
    'topColabs' => ['labels'=>[],'values'=>[]],
    'timeline' => ['labels'=>[],'values'=>[]],
    'tabela' => [],
    'error' => 'Falha ao consultar o banco: ' . $e->getMessage()
  ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>COHIDRO BI · Dashboard AAI</title>
  <meta name="description" content="Painel COHIDRO BI: Acompanhamento de Atividades (AAI)." />
  <meta name="theme-color" content="#0b1020" />
  <link rel="icon" href="/favicon.ico" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/cohidro.css" />
  <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" crossorigin="anonymous"></script>
  <style>
    /* pequenos ajustes visuais */
    .kpi .label{opacity:.9}
    .status.ok{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.35)}
    .status.warn{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.35)}
    .status.danger{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.35)}
    .ellipsis{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:380px;display:block}
  </style>
</head>
<body>
  <a href="#conteudo" class="tag" style="position:absolute; left:-1000px; top:-1000px">Pular para o conteúdo</a>

  <div class="container">
    <?php include __DIR__.'/partials/sidebar.php'; ?>

    <div style="display:flex; flex-direction:column; min-width:0">
      <?php include __DIR__.'/partials/topbar.php'; ?>

      <main id="conteudo">
        <section class="panel" aria-labelledby="filtros-title">
          <h3 id="filtros-title">Filtros</h3>
          <div class="filters">
            <input id="fDataIni" class="input" type="date" />
            <input id="fDataFim" class="input" type="date" />
            <select id="fDiretoria" class="input">
              <option value="">Diretoria (todas)</option>
              <?php foreach(($DATA['porDiretoria']['labels'] ?? []) as $d): ?>
                <option><?= htmlspecialchars($d) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn" id="btnAplicar">Aplicar</button>
            <button class="btn" id="btnLimpar">Limpar</button>
          </div>
        </section>

        <section class="grid kpis">
          <article class="panel kpi">
            <div class="icon" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="15" width="7" height="6"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpiTotal">—</div><div class="label">Registros AAI</div></div>
          </article>
          <article class="panel kpi">
            <div class="icon" aria-hidden="true" style="background:rgba(34,211,238,.15); border-color:rgba(34,211,238,.35)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpi30d">—</div><div class="label">Últimos 30 dias</div></div>
          </article>
          <article class="panel kpi">
            <div class="icon" aria-hidden="true" style="background:rgba(34,197,94,.15); border-color:rgba(34,197,94,.35)">
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

        <section class="grid two">
          <article class="panel"><h3>Registros por Diretoria</h3><canvas id="chartDiretoria" height="140"></canvas></article>
          <article class="panel"><h3>Top 5 Colaboradores</h3><canvas id="chartTopColabs" height="140"></canvas></article>
        </section>

        <section class="panel"><h3>Registros por mês (últimos 12)</h3><canvas id="chartTimeline" height="120"></canvas></section>

        <section class="panel">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px">
            <h3 style="margin:0">Registros AAI</h3>
            <div style="display:flex; gap:8px; align-items:center">
              <input id="tblBusca" class="input" type="search" placeholder="Filtrar..." style="width:260px">
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
                  <th>Data Reg.</th>
                  <th>Colaborador</th>
                  <th>CPF</th>
                  <th>Diretoria</th>
                  <th>Período</th>
                  <th>Ativ. Realizadas (resumo)</th>
                  <th>Pontos Relevantes (resumo)</th>
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
    // ========= Payload vindo do PHP =========
    window.DATA = <?=
      json_encode($DATA, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    ?>;

    // ========= Helpers =========
    const $ = (q,ctx=document)=>ctx.querySelector(q);
    const $$ = (q,ctx=document)=>Array.from(ctx.querySelectorAll(q));
    const showToast = (msg)=>{ const t=$('#toast'); t.textContent=msg; t.style.display='block'; setTimeout(()=>t.style.display='none', 2500) };
    const maskCPF = cpf => {
      if(!cpf) return '—';
      const d = cpf.replace(/\D/g,'');
      if(d.length === 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,'$1.$2.$3-$4');
      return cpf;
    };

    // ========= KPIs =========
    function renderKPIs(d){
      $('#kpiTotal').textContent = d.kpis.total ?? 0;
      $('#kpi30d').textContent   = d.kpis.ultimos30 ?? 0;
      $('#kpiColabs').textContent= d.kpis.colabs ?? 0;
      $('#kpiDirs').textContent  = d.kpis.dirs ?? 0;
    }

    // ========= Charts =========
    let chDiretoria, chTop, chTimeline;
    function renderCharts(d){
      const brand = 'rgba(79,70,229,0.9)';
      const cyan  = 'rgba(34,211,238,0.9)';
      const cyanSoft = 'rgba(34,211,238,0.2)';

      chDiretoria?.destroy();
      chDiretoria = new Chart($('#chartDiretoria'), {
        type: 'bar',
        data: {
          labels: d.porDiretoria.labels || [],
          datasets: [{ label: 'Registros', data: d.porDiretoria.values || [], backgroundColor: brand, borderColor: brand, borderWidth:1 }]
        },
        options: { responsive:true, plugins:{ legend:{display:false} },
          scales:{ x:{ grid:{color:'rgba(255,255,255,.06)'} }, y:{ grid:{color:'rgba(255,255,255,.06)'} } } }
      });

      chTop?.destroy();
      chTop = new Chart($('#chartTopColabs'), {
        type: 'doughnut',
        data: { labels: d.topColabs.labels || [], datasets: [{ data: d.topColabs.values || [] }] },
        options: { plugins:{ legend:{ position:'bottom' } } }
      });

      chTimeline?.destroy();
      chTimeline = new Chart($('#chartTimeline'), {
        type: 'line',
        data: { labels: d.timeline.labels || [], datasets: [{ label:'Registros/mês', data:d.timeline.values || [], fill:true, borderColor: cyan, backgroundColor: cyanSoft, tension:.35, pointRadius:2 }] },
        options: { plugins:{ legend:{display:false} }, scales:{ x:{ grid:{color:'rgba(255,255,255,.06)'} }, y:{ grid:{color:'rgba(255,255,255,.06)'} } } }
      });
    }

    // ========= Tabela =========
    let page=1, perPage=50, filtered=[], query='';
    const tbody = $('#tbodyAAI');

    function renderTable(d){
      const arr = d.tabela || [];
      const q = query.trim().toLowerCase();
      filtered = arr.filter(o =>
        Object.values(o).some(v => String(v ?? '').toLowerCase().includes(q))
      );

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

      const total = filtered.length;
      const pages = Math.ceil(total/perPage)||1;
      page = Math.min(page, pages);

      const p = $('#paginacao'); p.innerHTML = '';
      const mk = (n,txt=n)=>{
        const b=document.createElement('button');
        b.className='btn'; b.textContent=txt; b.disabled = (n===page);
        b.addEventListener('click',()=>{page=n; renderTable({tabela:filtered}); window.scrollTo({top:0,behavior:'smooth'})});
        return b;
      };
      p.append(mk(1,'«'));
      if(page>1) p.append(mk(page-1,'‹'));
      for(let n=Math.max(1,page-2); n<=Math.min(pages,page+2); n++) p.append(mk(n));
      if(page<pages) p.append(mk(page+1,'›'));
      p.append(mk(pages,'»'));
    }

    // ========= Filtros =========
    $('#tblBusca').addEventListener('input', e=>{ query=e.target.value; page=1; renderTable(window.DATA); });
    $('#tblLinhas').addEventListener('change', e=>{ perPage=Number(e.target.value)||50; page=1; renderTable(window.DATA); });
    $('#btnAplicar').addEventListener('click', ()=>{ showToast('Filtros aplicados'); });
    $('#btnLimpar').addEventListener('click', ()=>{
      $('#fDataIni').value=''; $('#fDataFim').value=''; $('#fDiretoria').value='';
      query=''; $('#tblBusca').value='';
      page=1; renderTable(window.DATA);
      showToast('Filtros limpos');
    });

    // ========= Boot =========
    function init(){
      $('#ano').textContent = new Date().getFullYear();
      const d = window.DATA || {};
      renderKPIs(d);
      renderCharts(d);
      renderTable(d);
      if(d.error){ console.error(d.error); showToast('Aviso: parte dos dados não pôde ser carregada.'); }
    }
    window.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>
