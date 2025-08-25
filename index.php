<?php
// Protege a página — exige login
require_once __DIR__.'/auth_check.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>COHIDRO BI · Dashboard</title>
  <meta name="description" content="Painel COHIDRO BI: KPIs, gráficos, tabela de obras e formulários." />
  <meta name="theme-color" content="#0b1020" />
  <link rel="icon" href="/favicon.ico" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- CSS externo -->
  <link rel="stylesheet" href="/assets/cohidro.css" />

  <!-- Chart.js (pinned) -->
  <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" crossorigin="anonymous"></script>
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
              <option>Diretoria de Engenharia</option>
              <option>Diretoria de Obras</option>
              <option>Diretoria Administrativa</option>
            </select>
            <select id="fStatus" class="input">
              <option value="">Status (todos)</option>
              <option>Em execução</option>
              <option>Concluída</option>
              <option>Paralisada</option>
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
            <div class="meta"><div class="value" id="kpiObras">—</div><div class="label">Obras ativas</div></div>
          </article>
          <article class="panel kpi">
            <div class="icon" aria-hidden="true" style="background:rgba(34,211,238,.15); border-color:rgba(34,211,238,.35)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpiContratado">—</div><div class="label">Valor contratado (R$)</div></div>
          </article>
          <article class="panel kpi">
            <div class="icon" aria-hidden="true" style="background:rgba(34,197,94,.15); border-color:rgba(34,197,94,.35)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpiLiquidado">—</div><div class="label">Valor liquidado (R$)</div></div>
          </article>
          <article class="panel kpi">
            <div class="icon" aria-hidden="true" style="background:rgba(245,158,11,.15); border-color:rgba(245,158,11,.35)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10H7"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M21 18H7"/></svg>
            </div>
            <div class="meta"><div class="value" id="kpiAndamento">—</div><div class="label">Andamento médio (%)</div></div>
          </article>
        </section>

        <section class="grid two">
          <article class="panel"><h3>Obras por Diretoria</h3><canvas id="chartDiretoria" height="140"></canvas></article>
          <article class="panel"><h3>Valor por Status</h3><canvas id="chartStatus" height="140"></canvas></article>
        </section>

        <section class="panel"><h3>Andamento Físico · Linha do Tempo</h3><canvas id="chartTimeline" height="120"></canvas></section>

        <section class="panel">
<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px">
  <h3 style="margin:0">Lista de Obras</h3>

  <div style="display:flex; gap:8px; align-items:center">
    <!-- Campo de busca -->
    <input id="tblBusca" class="input" type="search" placeholder="Filtrar tabela..."
      style="width:220px; padding:6px 10px; border:1px solid #444; border-radius:6px; background:#222; color:#fff;">

    <!-- Lista suspensa -->
    <select id="tblLinhas" class="input"
      style="width:140px; padding:6px 10px; border:1px solid #444; border-radius:6px; background:#222; color:#fff; cursor:pointer;">
      <option value="10">10 por página</option>
      <option value="25">25 por página</option>
      <option value="50" selected>50 por página</option>
      <option value="100">100 por página</option>
    </select>
  </div>
</div>
          <div class="table-wrap">
            <table id="tObras" aria-label="Tabela de obras">
              <thead><tr><th>Órgão</th><th>Município</th><th>Diretoria</th><th>Empresa</th><th>Nº Contrato</th><th>Valor (R$)</th><th>Andamento</th><th>Prazo (dias)</th><th>Término Prev.</th><th>Status</th></tr></thead>
              <tbody id="tbodyObras"><tr><td colspan="10" style="text-align:center; padding:18px; color:var(--muted)">Carregando dados...</td></tr></tbody>
            </table>
          </div>
          <div id="paginacao" style="display:flex; justify-content:flex-end; gap:6px; margin-top:10px"></div>
        </section>

        <footer>© <span id="ano"></span> COHIDRO · Template inicial. Conecte aos seus endpoints em <code>/api/...</code> ou n8n.</footer>
      </main>
    </div>
  </div>

  <!-- Modais (mantidos) -->
  <div class="modal" id="mUpload" role="dialog" aria-modal="true" aria-labelledby="mUploadTitle">
    <div class="box">
      <header><h3 id="mUploadTitle">Upload de Planilha (CSV/XLSX)</h3><button class="btn" data-close="#mUpload" aria-label="Fechar">Fechar</button></header>
      <section>
        <div class="form-grid">
          <div class="full"><label>Arquivo</label><input id="filePlan" type="file" accept=".csv,.xlsx" /></div>
          <div><label>Origem</label><select id="uploadOrigem"><option value="obras">Obras/Contratos</option><option value="colaboradores">Colaboradores</option><option value="aai">Formulários AAI</option></select></div>
          <div><label>Destino (endpoint)</label><input id="uploadEndpoint" type="text" placeholder="/api/upload" value="/api/upload" /></div>
          <div class="full"><small style="opacity:.75">Dica: aponte para webhook do n8n (ex.: <code>https://n8n.seu-dominio.com/webhook/emops-upload</code>).</small></div>
        </div>
      </section>
      <footer><button class="btn" data-close="#mUpload">Cancelar</button><button class="btn brand" id="enviarUpload">Enviar</button></footer>
    </div>
  </div>

  <div class="modal" id="mAAI" role="dialog" aria-modal="true" aria-labelledby="mAAITitle">
    <div class="box">
      <header><h3 id="mAAITitle">Formulário AAI · Acompanhamento de Atividades</h3><button class="btn" data-close="#mAAI" aria-label="Fechar">Fechar</button></header>
      <section>
        <div class="form-grid">
          <div><label>Colaborador</label><input id="aaiColaborador" type="text" placeholder="Nome completo" /></div>
          <div><label>Setor</label><input id="aaiSetor" type="text" placeholder="Setor" /></div>
          <div><label>Coord.</label><input id="aaiCoord" type="text" placeholder="Coordenador(a)" /></div>
          <div><label>Função</label><input id="aaiFuncao" type="text" placeholder="Função" /></div>
          <div><label>Período</label><input id="aaiPeriodo" type="text" placeholder="Ex.: 01–15/08/2025" /></div>
          <div><label>Data</label><input id="aaiData" type="date" /></div>
          <div class="full"><label>Atividades Realizadas</label><textarea id="aaiRealizadas" placeholder="Descreva..."></textarea></div>
          <div class="full"><label>Atividades Previstas</label><textarea id="aaiPrevistas" placeholder="Descreva..."></textarea></div>
          <div class="full"><label>Pontos Relevantes</label><textarea id="aaiRelevantes" placeholder="Descreva..."></textarea></div>
        </div>
      </section>
      <footer><button class="btn" data-close="#mAAI">Cancelar</button><button class="btn brand" id="salvarAAI">Salvar</button></footer>
    </div>
  </div>

  <div id="toast" style="position:fixed; right:20px; bottom:20px; background:#0b1230; border:1px solid rgba(255,255,255,.12); color:var(--text); padding:12px 14px; border-radius:12px; box-shadow:var(--shadow); display:none"></div>

  <script>
    const API_BASE = '/api';
    const $ = (q,ctx=document)=>ctx.querySelector(q);
    const $$ = (q,ctx=document)=>Array.from(ctx.querySelectorAll(q));
    const money = v => (Number(v)||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
    const pct = v => `${(Number(v)||0).toFixed(1)}%`;
    const showToast = (msg)=>{ const t=$('#toast'); t.textContent=msg; t.style.display='block'; setTimeout(()=>t.style.display='none', 2500) };

    // Sidebar toggle (ícone só aparece no mobile via CSS)
    const sidebar = $('#sidebar');
    const btnMenu = $('#btnMenu');
    if(btnMenu){ btnMenu.addEventListener('click', ()=> sidebar.classList.toggle('open')); }

    // Navegação lateral - CORRIGIDA: só intercepta links sem classe external-link
    $$('.nav a:not(.external-link)').forEach(a=>a.addEventListener('click',e=>{
      e.preventDefault();
      $$('.nav a:not(.external-link)').forEach(x=>x.classList.remove('active'));
      a.classList.add('active');
      document.getElementById('conteudo').scrollIntoView({behavior:'smooth'});
    }));

    // Modais opcionais (só se os botões existirem em alguma página)
    const btnUpload = $('#btnUpload');
    if(btnUpload){ btnUpload.addEventListener('click', ()=> $('#mUpload').classList.add('open')); }

    const btnAAI = $('#btnAAI');
    if(btnAAI){ btnAAI.addEventListener('click', ()=> $('#mAAI').classList.add('open')); }

    const btnExport = $('#btnExport');
    if(btnExport){
      btnExport.addEventListener('click', ()=>{
        const rows = [['Órgão','Município','Diretoria','Empresa','Contrato','Valor','Andamento','Prazo','Término','Status'],
          ...filtered.map(o=>[o.orgao,o.municipio,o.diretoria,o.empresa,o.contrato,o.valor,o.andamento,o.prazo,o.termino,o.status])];
        const csv = rows.map(r=> r.map(v=>`"${String(v).replaceAll('"','""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
        const a = document.createElement('a'); a.href=URL.createObjectURL(blob); a.download = `obras_export_${new Date().toISOString().slice(0,10)}.csv`; a.click();
      });
    }

    $$('[data-close]').forEach(btn=> btn.addEventListener('click', ()=> $(btn.getAttribute('data-close')).classList.remove('open')));

    // Dados de exemplo
    const seed = {
      kpis: { obras: 42, contratado: 128000000, liquidado: 78000000, andamento: 63.4 },
      porDiretoria: { labels: ['Engenharia','Obras','Administrativa','Projetos','Financeira'], values: [14, 9, 7, 6, 6] },
      porStatus: { labels: ['Em execução','Concluída','Paralisada'], values: [92_000_000, 24_000_000, 12_000_000] },
      timeline: { labels: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'], values: [48,50,52,55,57,58,60,63,65,68,70,73] },
      obras: Array.from({length: 120}).map((_,i)=>({
        orgao:['SEINFRA','EMOP','DER','Prefeitura RJ'][i%4],
        municipio:['Rio de Janeiro','Niterói','Duque de Caxias','Nova Iguaçu','Campos'][i%5],
        diretoria:['Diretoria de Engenharia','Diretoria de Obras','Diretoria Administrativa'][i%3],
        empresa:['Construcenter','Alpha Obras','Beta Engenharia'][i%3],
        contrato:`CT-${2023 + (i%3)}-${String(1000+i).padStart(4,'0')}`,
        valor: 500000 + (i%12)*250000,
        andamento: 30 + (i%70),
        prazo: 180 + (i%120),
        termino: `2025-${String((i%12)+1).padStart(2,'0')}-${String((i%28)+1).padStart(2,'0')}`,
        status: ['Em execução','Concluída','Paralisada'][i%3]
      }))
    };

    function renderKPIs(data){
      $('#kpiObras').textContent = data.kpis.obras;
      $('#kpiContratado').textContent = money(data.kpis.contratado);
      $('#kpiLiquidado').textContent = money(data.kpis.liquidado);
      $('#kpiAndamento').textContent = pct(data.kpis.andamento);
    }

    let chDiretoria, chStatus, chTimeline;
    function renderCharts(data){
      const brand = 'rgba(79,70,229,0.9)';
      const cyan = 'rgba(34,211,238,0.9)';
      const cyanSoft = 'rgba(34,211,238,0.2)';

      chDiretoria?.destroy();
      chDiretoria = new Chart($('#chartDiretoria'), {
        type: 'bar',
        data: { labels: data.porDiretoria.labels, datasets: [{ label: 'Qtde Obras', data: data.porDiretoria.values, backgroundColor: brand, borderColor: brand, borderWidth:1 }] },
        options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{ grid:{color:'rgba(255,255,255,.06)'} }, y:{ grid:{color:'rgba(255,255,255,.06)'} } } }
      });

      chStatus?.destroy();
      chStatus = new Chart($('#chartStatus'), {
        type: 'doughnut',
        data: { labels: data.porStatus.labels, datasets: [{ data: data.porStatus.values, backgroundColor:[brand, cyan, 'rgba(239,68,68,.9)'] }] },
        options: { plugins:{ legend:{ position:'bottom' } } }
      });

      chTimeline?.destroy();
      chTimeline = new Chart($('#chartTimeline'), {
        type: 'line',
        data: { labels: data.timeline.labels, datasets: [{ label:'Andamento médio (%)', data:data.timeline.values, fill:true, borderColor: cyan, backgroundColor: cyanSoft, tension:.35, pointRadius:2 }] },
        options: { plugins:{ legend:{display:false} }, scales:{ x:{ grid:{color:'rgba(255,255,255,.06)'} }, y:{ grid:{color:'rgba(255,255,255,.06)'}, suggestedMin:0, suggestedMax:100 } } }
      });
    }

    // Tabela + paginação
    let page=1, perPage=50, filtered=[], query='';
    const tbody = $('#tbodyObras');

    function renderTable(){
      const q = query.trim().toLowerCase();
      filtered = seed.obras.filter(o => Object.values(o).some(v=> String(v).toLowerCase().includes(q)));
      const start = (page-1)*perPage;
      const it = filtered.slice(start, start+perPage);

      tbody.innerHTML = it.map(o=>`
        <tr>
          <td>${o.orgao}</td><td>${o.municipio}</td><td>${o.diretoria}</td><td>${o.empresa}</td>
          <td>${o.contrato}</td><td>${money(o.valor)}</td><td>${pct(o.andamento)}</td>
          <td>${o.prazo}</td><td>${o.termino}</td>
          <td><span class="status ${o.status==='Concluída'?'ok':o.status==='Paralisada'?'danger':'warn'}">${o.status}</span></td>
        </tr>`).join('')
        || `<tr><td colspan="10" style="text-align:center; padding:18px; color:var(--muted)">Nenhum resultado.</td></tr>`;

      const total = filtered.length;
      const pages = Math.ceil(total/perPage)||1;
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

    // Filtros da tabela
    $('#tblBusca').addEventListener('input', e=>{ query=e.target.value; page=1; renderTable(); });
    $('#tblLinhas').addEventListener('change', e=>{ perPage=Number(e.target.value)||50; page=1; renderTable(); });

    // Botões Filtros
    $('#btnAplicar').addEventListener('click', ()=>{ showToast('Filtros aplicados (dados de exemplo)'); });
    $('#btnLimpar').addEventListener('click', ()=>{ $('#fDataIni').value=''; $('#fDataFim').value=''; $('#fDiretoria').value=''; $('#fStatus').value=''; showToast('Filtros limpos'); });

    // Upload
    const enviarUpload = $('#enviarUpload');
    if(enviarUpload){
      enviarUpload.addEventListener('click', async ()=>{
        const file = $('#filePlan').files?.[0]; const ep = $('#uploadEndpoint').value || '/api/upload'; const origem=$('#uploadOrigem').value;
        if(!file) return showToast('Selecione um arquivo.');
        const fd = new FormData(); fd.append('file', file); fd.append('origem', origem);
        try{
          const r = await fetch(ep, { method:'POST', body: fd });
          if(!r.ok) throw new Error('Falha no upload');
          showToast('Upload enviado!'); $('#mUpload').classList.remove('open');
        }catch(err){ console.error(err); showToast('Erro no upload'); }
      });
    }

    // AAI
    const salvarAAI = $('#salvarAAI');
    if(salvarAAI){
      salvarAAI.addEventListener('click', async ()=>{
        const payload = {
          colaborador: $('#aaiColaborador').value,
          setor: $('#aaiSetor').value,
          coord: $('#aaiCoord').value,
          funcao: $('#aaiFuncao').value,
          periodo: $('#aaiPeriodo').value,
          data: $('#aaiData').value,
          realizadas: $('#aaiRealizadas').value,
          andamento: $('#aaiEmAndamento').value,
          previstas: $('#aaiPrevistas').value,
          relevantes: $('#aaiRelevantes').value,
          criticos: $('#aaiCriticos').value
        };
        try{
          const r = await fetch(`${API_BASE}/aai`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
          if(!r.ok) throw new Error('Falha ao salvar');
          showToast('AAI salvo com sucesso!'); $('#mAAI').classList.remove('open');
        }catch(err){ console.error(err); showToast('Erro ao salvar AAI'); }
      });
    }

    // Boot
    function init(){
      $('#ano').textContent = new Date().getFullYear();
      renderKPIs(seed);
      renderCharts(seed);
      renderTable();
    }
    window.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>