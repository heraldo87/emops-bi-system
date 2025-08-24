<?php
// listar-aai.php — EMOPS BI · Listagem de Formulários AAI
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conn.php';

$PROJECT_NAME = 'EMOPS BI';

// Parâmetros de filtro e paginação
$filtro_colaborador = trim($_GET['colaborador'] ?? '');
$filtro_setor = trim($_GET['setor'] ?? '');
$filtro_status = trim($_GET['status'] ?? '');
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Construir query com filtros
$where_conditions = [];
$params = [];
$param_types = '';

if ($filtro_colaborador) {
    $where_conditions[] = "colaborador LIKE ?";
    $params[] = "%{$filtro_colaborador}%";
    $param_types .= 's';
}

if ($filtro_setor) {
    $where_conditions[] = "setor LIKE ?";
    $params[] = "%{$filtro_setor}%";
    $param_types .= 's';
}

if ($filtro_status) {
    $where_conditions[] = "status = ?";
    $params[] = $filtro_status;
    $param_types .= 's';
}

if ($filtro_data_inicio) {
    $where_conditions[] = "data_inicial >= ?";
    $params[] = $filtro_data_inicio;
    $param_types .= 's';
}

if ($filtro_data_fim) {
    $where_conditions[] = "data_final <= ?";
    $params[] = $filtro_data_fim;
    $param_types .= 's';
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de registros
$count_sql = "SELECT COUNT(*) FROM acompanhamento_atividades {$where_clause}";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_registros = $count_stmt->get_result()->fetch_row()[0];
$total_paginas = ceil($total_registros / $por_pagina);

// Buscar registros
$sql = "SELECT 
            id, colaborador, setor, coordenador, funcao, 
            data_inicial, data_final, status, data_registro,
            LEFT(atividades_realizadas, 100) as atividades_preview
        FROM acompanhamento_atividades 
        {$where_clause}
        ORDER BY data_registro DESC 
        LIMIT ? OFFSET ?";

$params[] = $por_pagina;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
?>

<div style="display:flex; flex-direction:column; min-width:0">
    <div class="topbar">
        <div class="mobile-top">
            <button id="btnMenu" class="btn" aria-label="Abrir menu" title="Menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <div class="breadcrumbs">
                <span><?php echo htmlspecialchars($PROJECT_NAME); ?></span> 
                <span style="opacity:.5">/</span> 
                <span>Formulários AAI</span>
            </div>
        </div>
        <div class="actions">
            <span class="tag" id="tz">America/Rio_Branco</span>
            <span class="tag" title="Usuário logado">👤 <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></span>
            <a class="btn" href="formulario-aai.php" title="Novo Formulário">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Novo AAI
            </a>
            <a class="btn" href="index.php" title="Dashboard">Dashboard</a>
            <a class="btn" href="/logout.php" title="Sair">Sair</a>
        </div>
    </div>

    <main id="conteudo">
        <!-- Filtros -->
        <section class="panel" aria-labelledby="filtros-title">
            <h3 id="filtros-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; margin-right:8px">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                Filtros
            </h3>
            
            <form method="GET" class="filters">
                <input type="text" name="colaborador" class="input" placeholder="Nome do colaborador" 
                       value="<?= htmlspecialchars($filtro_colaborador) ?>">
                
                <input type="text" name="setor" class="input" placeholder="Setor" 
                       value="<?= htmlspecialchars($filtro_setor) ?>">
                
                <select name="status" class="input">
                    <option value="">Status (todos)</option>
                    <option value="rascunho" <?= $filtro_status === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="enviado" <?= $filtro_status === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                    <option value="aprovado" <?= $filtro_status === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                    <option value="rejeitado" <?= $filtro_status === 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                </select>
                
                <input type="date" name="data_inicio" class="input" placeholder="Data inicial" 
                       value="<?= htmlspecialchars($filtro_data_inicio) ?>">
                
                <input type="date" name="data_fim" class="input" placeholder="Data final" 
                       value="<?= htmlspecialchars($filtro_data_fim) ?>">
                
                <button type="submit" class="btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    Filtrar
                </button>
                
                <a href="listar-aai.php" class="btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    Limpar
                </a>
            </form>
        </section>

        <!-- Estatísticas -->
        <section class="grid kpis">
            <?php
            // Buscar estatísticas
            $stats_sql = "SELECT 
                            COUNT(*) as total,
                            COUNT(CASE WHEN status = 'enviado' THEN 1 END) as enviados,
                            COUNT(CASE WHEN status = 'aprovado' THEN 1 END) as aprovados,
                            COUNT(CASE WHEN status = 'rejeitado' THEN 1 END) as rejeitados
                         FROM acompanhamento_atividades";
            $stats_result = $conn->query($stats_sql);
            $stats = $stats_result->fetch_assoc();
            ?>
            
            <article class="panel kpi">
                <div class="icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="meta">
                    <div class="value"><?= number_format($stats['total']) ?></div>
                    <div class="label">Total de formulários</div>
                </div>
            </article>
            
            <article class="panel kpi">
                <div class="icon" aria-hidden="true" style="background:rgba(34,211,238,.15); border-color:rgba(34,211,238,.35)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                        <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                        <path d="M2 2l7.586 7.586"/>
                        <circle cx="11" cy="11" r="2"/>
                    </svg>
                </div>
                <div class="meta">
                    <div class="value"><?= number_format($stats['enviados']) ?></div>
                    <div class="label">Enviados</div>
                </div>
            </article>
            
            <article class="panel kpi">
                <div class="icon" aria-hidden="true" style="background:rgba(34,197,94,.15); border-color:rgba(34,197,94,.35)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div class="meta">
                    <div class="value"><?= number_format($stats['aprovados']) ?></div>
                    <div class="label">Aprovados</div>
                </div>
            </article>
            
            <article class="panel kpi">
                <div class="icon" aria-hidden="true" style="background:rgba(245,158,11,.15); border-color:rgba(245,158,11,.35)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div class="meta">
                    <div class="value"><?= number_format($stats['rejeitados']) ?></div>
                    <div class="label">Rejeitados</div>
                </div>
            </article>
        </section>

        <!-- Lista de Formulários -->
        <section class="panel">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px">
                <h3 style="margin:0">Formulários AAI</h3>
                <div style="display:flex; gap:8px; align-items:center">
                    <span class="tag"><?= number_format($total_registros) ?> registro(s)</span>
                </div>
            </div>

            <div class="table-wrap">
                <table aria-label="Tabela de formulários AAI">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Setor</th>
                            <th>Função</th>
                            <th>Período</th>
                            <th>Status</th>
                            <th>Enviado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['colaborador']) ?></strong>
                                        <?php if ($row['coordenador']): ?>
                                            <br><small style="color:var(--muted)">Coord: <?= htmlspecialchars($row['coordenador']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['setor'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($row['funcao'] ?: '-') ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($row['data_inicial'])) ?><br>
                                        <small style="color:var(--muted)">até <?= date('d/m/Y', strtotime($row['data_final'])) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'rascunho' => 'background:rgba(107,114,128,.2); color:#d1d5db',
                                            'enviado' => 'background:rgba(34,211,238,.2); color:#67e8f9',
                                            'aprovado' => 'background:rgba(34,197,94,.2); color:#86efac',
                                            'rejeitado' => 'background:rgba(239,68,68,.2); color:#fca5a5'
                                        ];
                                        $color = $status_colors[$row['status']] ?? $status_colors['enviado'];
                                        ?>
                                        <span class="tag" style="<?= $color ?>"><?= ucfirst($row['status']) ?></span>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($row['data_registro'])) ?>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:4px">
                                            <a href="visualizar-aai.php?id=<?= $row['id'] ?>" class="btn-icon" title="Visualizar">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </a>
                                            <a href="editar-aai.php?id=<?= $row['id'] ?>" class="btn-icon" title="Editar">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 20h9"/>
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:24px; color:var(--muted)">
                                    Nenhum formulário encontrado.
                                    <br><br>
                                    <a href="formulario-aai.php" class="btn brand">Criar primeiro formulário</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div id="paginacao" style="display:flex; justify-content:center; gap:6px; margin-top:16px">
                    <?php
                    $query_params = $_GET;
                    
                    // Botão anterior
                    if ($pagina > 1):
                        $query_params['pagina'] = $pagina - 1;
                        $url_anterior = '?' . http_build_query($query_params);
                    ?>
                        <a href="<?= htmlspecialchars($url_anterior) ?>" class="btn">« Anterior</a>
                    <?php endif; ?>
                    
                    <?php
                    // Números das páginas
                    $inicio = max(1, $pagina - 2);
                    $fim = min($total_paginas, $pagina + 2);
                    
                    for ($i = $inicio; $i <= $fim; $i++):
                        $query_params['pagina'] = $i;
                        $url_pagina = '?' . http_build_query($query_params);
                        $ativo = ($i == $pagina) ? 'brand' : '';
                    ?>
                        <a href="<?= htmlspecialchars($url_pagina) ?>" class="btn <?= $ativo ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php
                    // Botão próximo
                    if ($pagina < $total_paginas):
                        $query_params['pagina'] = $pagina + 1;
                        $url_proximo = '?' . http_build_query($query_params);
                    ?>
                        <a href="<?= htmlspecialchars($url_proximo) ?>" class="btn">Próximo »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <footer style="margin-top:24px">
            © <span id="ano"><?= date('Y') ?></span> <?php echo htmlspecialchars($PROJECT_NAME); ?> · 
            Gestão de Formulários AAI
        </footer>
    </main>
</div>

<style>
.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px;
    color: var(--text);
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background: rgba(255,255,255,.1);
    border-color: rgba(255,255,255,.2);
    transform: translateY(-1px);
}

.filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    align-items: end;
}

@media (max-width: 768px) {
    .filters {
        grid-template-columns: 1fr;
    }
}

.table-wrap {
    overflow-x: auto;
}

.table-wrap table {
    width: 100%;
    min-width: 800px;
}

.kpis {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

@media (max-width: 600px) {
    .kpis {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Ano no footer
document.getElementById('ano').textContent = new Date().getFullYear();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>