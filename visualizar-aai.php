<?php
// visualizar-aai.php — EMOPS BI · Visualização de Formulário AAI
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conn.php';

$PROJECT_NAME = 'COHIDRO BI';

// Pegar ID do formulário
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: listar-aai.php');
    exit;
}

// Buscar dados do formulário
try {
    $sql = "SELECT * FROM acompanhamento_atividades WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$formulario = $result->fetch_assoc()) {
        header('Location: listar-aai.php?erro=nao_encontrado');
        exit;
    }
    
} catch (Exception $e) {
    error_log('[VISUALIZAR_AAI] Erro: ' . $e->getMessage());
    header('Location: listar-aai.php?erro=db');
    exit;
}

// Função para formatar data
function formatarData($data) {
    return $data ? date('d/m/Y', strtotime($data)) : '-';
}

// Função para formatar CPF
function formatarCPF($cpf) {
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Função para status com cores
function getStatusBadge($status) {
    $badges = [
        'rascunho' => ['Rascunho', 'background:rgba(107,114,128,.2); color:#d1d5db'],
        'enviado' => ['Enviado', 'background:rgba(34,211,238,.2); color:#67e8f9'],
        'aprovado' => ['Aprovado', 'background:rgba(34,197,94,.2); color:#86efac'],
        'rejeitado' => ['Rejeitado', 'background:rgba(239,68,68,.2); color:#fca5a5']
    ];
    
    $badge = $badges[$status] ?? $badges['enviado'];
    return "<span class='status-badge' style='{$badge[1]}'>{$badge[0]}</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($PROJECT_NAME); ?> · Visualizar AAI #<?= $id ?></title>
  <meta name="theme-color" content="#0b1020" />
  <link rel="icon" href="/favicon.ico" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/cohidro.css">
</head>
<body>
  <div class="container">
    <?php include __DIR__.'/partials/sidebar.php'; ?>

    <div style="display:flex; flex-direction:column; min-width:0">
      <?php include __DIR__.'/partials/topbar.php'; ?>

      <main id="conteudo">
        <!-- Header da página -->
        <section class="panel" style="margin-bottom:20px">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:16px">
            <div style="display:flex; align-items:center; gap:12px">
              <div class="icon" style="background:rgba(34,211,238,.15); border:1px solid rgba(34,211,238,.35); border-radius:12px; padding:10px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </div>
              <div>
                <h2 style="margin:0">Formulário AAI #<?= $id ?></h2>
                <p style="margin:0; color:var(--muted); font-size:14px">
                  <?= htmlspecialchars($formulario['colaborador']) ?> · 
                  Enviado em <?= date('d/m/Y H:i', strtotime($formulario['data_registro'])) ?>
                </p>
              </div>
            </div>
            
            <div style="display:flex; gap:10px; align-items:center">
              <?= getStatusBadge($formulario['status']) ?>
              
              <a href="listar-aai.php" class="btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 12H5"/>
                  <polyline points="12 19 5 12 12 5"/>
                </svg>
                Voltar à Lista
              </a>
              
              <button onclick="window.print()" class="btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 6 2 18 2 18 9"/>
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                  <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Imprimir
              </button>
            </div>
          </div>
        </section>

        <!-- Conteúdo do formulário -->
        <div class="form-view">
          <!-- Dados do Colaborador -->
          <section class="panel form-section">
            <h3 class="section-title">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M4 21v-2a4 4 0 0 1 3-3.87"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              Dados do Colaborador
            </h3>
            
            <div class="info-grid">
              <div class="info-item">
                <label>Nome Completo</label>
                <div class="value"><?= htmlspecialchars($formulario['colaborador']) ?></div>
              </div>
              
              <div class="info-item">
                <label>CPF</label>
                <div class="value"><?= htmlspecialchars(formatarCPF($formulario['cpf'])) ?></div>
              </div>
              
              <div class="info-item">
                <label>Setor</label>
                <div class="value"><?= htmlspecialchars($formulario['setor'] ?: 'Não informado') ?></div>
              </div>
              
              <div class="info-item">
                <label>Coordenador</label>
                <div class="value"><?= htmlspecialchars($formulario['coordenador'] ?: 'Não informado') ?></div>
              </div>
              
              <div class="info-item">
                <label>Função</label>
                <div class="value"><?= htmlspecialchars($formulario['funcao'] ?: 'Não informada') ?></div>
              </div>
            </div>
          </section>

          <!-- Período de Avaliação -->
          <section class="panel form-section">
            <h3 class="section-title">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
              Período de Avaliação
            </h3>
            
            <div class="info-grid">
              <div class="info-item">
                <label>Data Inicial</label>
                <div class="value"><?= formatarData($formulario['data_inicial']) ?></div>
              </div>
              
              <div class="info-item">
                <label>Data Final</label>
                <div class="value"><?= formatarData($formulario['data_final']) ?></div>
              </div>
              
              <div class="info-item">
                <label>Duração</label>
                <div class="value">
                  <?php
                  if ($formulario['data_inicial'] && $formulario['data_final']) {
                      $inicio = new DateTime($formulario['data_inicial']);
                      $fim = new DateTime($formulario['data_final']);
                      $diferenca = $inicio->diff($fim);
                      echo $diferenca->days . ' dias';
                  } else {
                      echo 'N/A';
                  }
                  ?>
                </div>
              </div>
            </div>
          </section>

          <!-- Atividades -->
          <section class="panel form-section">
            <h3 class="section-title">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
              </svg>
              Atividades
            </h3>
            
            <div class="content-blocks">
              <div class="content-block">
                <h4>Atividades Realizadas</h4>
                <div class="content-text">
                  <?= nl2br(htmlspecialchars($formulario['atividades_realizadas'] ?: 'Nenhuma atividade informada.')) ?>
                </div>
              </div>
              
              <?php if ($formulario['atividades_andamento']): ?>
                <div class="content-block">
                  <h4>Atividades em Andamento</h4>
                  <div class="content-text">
                    <?= nl2br(htmlspecialchars($formulario['atividades_andamento'])) ?>
                  </div>
                </div>
              <?php endif; ?>
              
              <?php if ($formulario['atividades_previstas']): ?>
                <div class="content-block">
                  <h4>Atividades Previstas</h4>
                  <div class="content-text">
                    <?= nl2br(htmlspecialchars($formulario['atividades_previstas'])) ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </section>

          <!-- Observações -->
          <?php if ($formulario['pontos_relevantes'] || $formulario['pontos_criticos']): ?>
            <section class="panel form-section">
              <h3 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Observações
              </h3>
              
              <div class="content-blocks">
                <?php if ($formulario['pontos_relevantes']): ?>
                  <div class="content-block">
                    <h4 style="color:var(--ok)">Pontos Relevantes</h4>
                    <div class="content-text">
                      <?= nl2br(htmlspecialchars($formulario['pontos_relevantes'])) ?>
                    </div>
                  </div>
                <?php endif; ?>
                
                <?php if ($formulario['pontos_criticos']): ?>
                  <div class="content-block">
                    <h4 style="color:var(--warn)">Pontos Críticos</h4>
                    <div class="content-text">
                      <?= nl2br(htmlspecialchars($formulario['pontos_criticos'])) ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </section>
          <?php endif; ?>

          <!-- Informações do Registro -->
          <section class="panel form-section">
            <h3 class="section-title">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              Informações do Registro
            </h3>
            
            <div class="info-grid">
              <div class="info-item">
                <label>Local e Data</label>
                <div class="value"><?= htmlspecialchars($formulario['local_data'] ?: 'Não informado') ?></div>
              </div>
              
              <div class="info-item">
                <label>Enviado por E-mail</label>
                <div class="value">
                  <?php if ($formulario['enviar_por_email']): ?>
                    <span style="color:var(--ok)">✓ Sim</span>
                  <?php else: ?>
                    <span style="color:var(--muted)">✗ Não</span>
                  <?php endif; ?>
                </div>
              </div>
              
              <div class="info-item">
                <label>Data de Registro</label>
                <div class="value"><?= date('d/m/Y H:i:s', strtotime($formulario['data_registro'])) ?></div>
              </div>
              
              <?php if ($formulario['data_atualizacao']): ?>
                <div class="info-item">
                  <label>Última Atualização</label>
                  <div class="value"><?= date('d/m/Y H:i:s', strtotime($formulario['data_atualizacao'])) ?></div>
                </div>
              <?php endif; ?>
              
              <div class="info-item">
                <label>Status</label>
                <div class="value"><?= getStatusBadge($formulario['status']) ?></div>
              </div>
            </div>
          </section>
        </div>

        <footer style="margin-top:32px; text-align:center; color:var(--muted); font-size:13px; padding:20px 0; border-top:1px solid rgba(255,255,255,.08)">
          © <span id="ano"><?= date('Y') ?></span> <?php echo htmlspecialchars($PROJECT_NAME); ?> · 
          Formulário AAI #<?= $id ?> - Acompanhamento de Atividades
        </footer>
      </main>
    </div>
  </div>

  <style>
    /* Estilos específicos para visualização */
    .form-view {
      display: grid;
      gap: 24px;
    }
    
    .form-section {
      break-inside: avoid;
    }
    
    .section-title {
      color: var(--brand-2);
      margin-bottom: 20px;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(255,255,255,.08);
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }
    
    .info-item {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    
    .info-item label {
      font-size: 12px;
      font-weight: 500;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .info-item .value {
      font-size: 14px;
      color: var(--text);
      font-weight: 500;
      min-height: 20px;
    }
    
    .content-blocks {
      display: grid;
      gap: 20px;
    }
    
    .content-block {
      background: rgba(255,255,255,.02);
      border: 1px solid rgba(255,255,255,.06);
      border-radius: 12px;
      padding: 16px;
    }
    
    .content-block h4 {
      margin: 0 0 12px 0;
      font-size: 14px;
      font-weight: 600;
      color: var(--brand-2);
    }
    
    .content-text {
      font-size: 14px;
      line-height: 1.6;
      color: var(--text);
      white-space: pre-wrap;
    }
    
    .status-badge {
      font-size: 12px;
      font-weight: 500;
      padding: 4px 10px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,.1);
    }
    
    /* Estilos para impressão */
    @media print {
      body {
        background: white !important;
        color: black !important;
      }
      
      .container {
        display: block !important;
      }
      
      .sidebar, .topbar {
        display: none !important;
      }
      
      .panel {
        background: white !important;
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        margin-bottom: 20px;
      }
      
      .section-title {
        color: #333 !important;
        border-bottom-color: #ddd !important;
      }
      
      .content-block {
        background: #f9f9f9 !important;
        border-color: #ddd !important;
      }
      
      .value, .content-text {
        color: #333 !important;
      }
    }
    
    @media (max-width: 768px) {
      .info-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }
    }
  </style>

  <script>
    // Ano no footer
    document.getElementById('ano').textContent = new Date().getFullYear();
    
    // Sidebar mobile
    const btnMenu = document.getElementById('btnMenu');
    const sidebar = document.getElementById('sidebar');
    if (btnMenu && sidebar) {
      btnMenu.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
  </script>
</body>
</html>