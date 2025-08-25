<?php
if (!function_exists('activeClass')) {
  function activeClass($id, $current) {
    return (!empty($current) && $current === $id) ? ' active' : '';
  }
}
?>
<aside id="sidebar" class="sidebar" aria-label="Menu lateral">
  <style>
    /* Identidade */
    #sidebar .brand { display:flex; align-items:center; gap:10px; margin:8px 6px 6px; }
    #sidebar .brand strong { font-size:14px; }
    #sidebar .brand small { font-size:11px; opacity:.7; display:block; }

    /* Linha divisória */
    #sidebar .sep{
      height:1px; margin:10px 6px; background:linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent);
    }

    /* Cabeçalho COHIDRO BI */
    #sidebar .title-wrap{ text-align:center; margin: 14px 6px 12px; }
    #sidebar .title{ font-size:18px; font-weight:800; }
    #sidebar .subtitle{ font-size:10px; text-transform:uppercase; opacity:.75; letter-spacing:.08em; }

    /* Cards menores */
    #sidebar .group-card{
      margin:6px 0 8px; padding:4px; border-radius:12px;
      background: linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,.01));
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.05);
    }
    #sidebar .group-head{
      display:flex; align-items:center; gap:6px; padding:5px 8px;
      border-radius:8px; cursor:pointer; user-select:none; position:relative;
    }
    #sidebar .group-head .label-link{ flex:1; text-decoration:none; color:inherit; font-weight:600; font-size:13px; }
    #sidebar .group-head .caret{ transition: transform .18s ease; opacity:.85; font-size:10px; }
    #sidebar .group-head[aria-expanded="true"] .caret{ transform: rotate(180deg); }

    /* Chip centralizado */
    #sidebar .chip{
      font-size:10px; font-weight:700;
      min-width:18px; height:18px; display:flex; align-items:center; justify-content:center;
      border-radius:50%; background: rgba(99,102,241,.2);
      box-shadow: inset 0 0 0 1px rgba(99,102,241,.35);
    }

    /* Submenus menores */
    #sidebar .nav-sub{ margin:4px 4px 6px 10px; padding-left:8px; border-left:2px solid rgba(255,255,255,.08);
      overflow:hidden; max-height:0; transition:max-height .22s ease; }
    #sidebar .nav-sub.open{ max-height:240px; }
    #sidebar .nav-item{ display:flex; padding:5px 9px; border-radius:8px; font-size:12.5px; text-decoration:none; }
    #sidebar .nav-item:hover{ background: rgba(255,255,255,.05); }
    #sidebar .nav-item.active{
      background: rgba(99,102,241,.18);
      box-shadow: inset 0 0 0 1px rgba(99,102,241,.35);
    }

    /* Links externos - SEM setinha */
    #sidebar .external-link{ 
      display:flex; padding:5px 9px; border-radius:8px; font-size:12.5px; 
      text-decoration:none; color:inherit;
    }
    #sidebar .external-link:hover{ background: rgba(255,255,255,.05); }
    #sidebar .external-link.active{
      background: rgba(99,102,241,.18);
      box-shadow: inset 0 0 0 1px rgba(99,102,241,.35);
    }
  </style>

  <!-- Identidade -->
  <div class="brand">
    <div class="brand-logo" aria-hidden="true">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M2 12h20"/></svg>
    </div>
    <div>
      <strong>CORTEX360</strong>
      <small>Controle de Obras</small>
    </div>
  </div>

  <div class="sep"></div>

  <!-- Título BI -->
  <div class="title-wrap">
    <div class="title">COHIDRO BI</div>
    <div class="subtitle">Business Intelligence</div>
  </div>

  <div class="sep"></div>

  <nav class="nav" aria-label="COHIDRO BI Menu">
    <!-- Dashboard -->
    <div class="group-card" data-group="dashboard">
      <div class="group-head" role="button" tabindex="0" aria-expanded="false" aria-controls="sub-dashboard">
        <a href="#" data-goto="dashboard" class="label-link<?php echo activeClass('dashboard', $CURRENT_SECTION ?? null); ?>">Dashboard</a>
        <span class="chip">2</span>
        <span class="caret">▼</span>
      </div>
      <div class="nav-sub" id="sub-dashboard">
        <a href="#" data-goto="aai" class="nav-item<?php echo activeClass('aai', $CURRENT_SECTION ?? null); ?>">AAI</a>
        <a href="#" data-goto="obras" class="nav-item<?php echo activeClass('obras', $CURRENT_SECTION ?? null); ?>">Obras</a>
      </div>
    </div>

    <!-- Atividades -->
    <div class="group-card" data-group="atividades">
      <div class="group-head" role="button" tabindex="0" aria-expanded="false" aria-controls="sub-atividades">
        <a href="#" data-goto="atividades" class="label-link<?php echo activeClass('atividades', $CURRENT_SECTION ?? null); ?>">Atividades</a>
        <span class="chip">3</span>
        <span class="caret">▼</span>
      </div>
      <div class="nav-sub" id="sub-atividades">
        <a href="#" data-goto="colaboradores" class="nav-item<?php echo activeClass('colaboradores', $CURRENT_SECTION ?? null); ?>">Colaboradores</a>
        <a href="formulario.php" class="external-link<?php echo activeClass('formulario', $CURRENT_SECTION ?? null); ?>">Formulário</a>
        <a href="#" data-goto="rel_atividades" class="nav-item<?php echo activeClass('rel_atividades', $CURRENT_SECTION ?? null); ?>">Rel. Atividades</a>
      </div>
    </div>
  </nav>

  <script>
  (function(){
    const KEY = 'cohidro_sidebar_open';
    let state = {};
    try { state = JSON.parse(localStorage.getItem(KEY) || '{}'); } catch(e){}
    function setOpen(groupEl, open){
      const head = groupEl.querySelector('.group-head');
      const sub = groupEl.querySelector('.nav-sub');
      head.setAttribute('aria-expanded', String(open));
      sub.classList.toggle('open', open);
      state[groupEl.dataset.group] = open;
      localStorage.setItem(KEY, JSON.stringify(state));
    }
    document.querySelectorAll('#sidebar .group-card').forEach(group=>{
      const open = state[group.dataset.group] || false; // fechado por padrão
      setOpen(group, open);
      const head = group.querySelector('.group-head');
      
      head.addEventListener('click', (e) => {
        // Só abre/fecha se não clicou em um link
        if (!e.target.classList.contains('label-link') || e.target.style.cursor === 'default') {
          setOpen(group, head.getAttribute('aria-expanded')!=='true');
        }
      });
      
      head.addEventListener('keydown', e=>{
        if(e.key==='Enter'||e.key===' '){ e.preventDefault(); setOpen(group, head.getAttribute('aria-expanded')!=='true'); }
      });
    });

    // Log para debug
    console.log('Sidebar carregado. Links externos:', document.querySelectorAll('.external-link').length);
  })();
  </script>
</aside>