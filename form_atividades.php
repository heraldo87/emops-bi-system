<?php
// formulario-aai.php — EMOPS BI · Formulário de Acompanhamento de Atividades
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conn.php';

$PROJECT_NAME = 'EMOPS BI';

// CSRF
if (empty($_SESSION['csrf_aai'])) {
    $_SESSION['csrf_aai'] = bin2hex(random_bytes(32));
}

$success = false;
$errors = [];

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_aai'], $csrf)) {
        $errors[] = 'Sessão expirada. Recarregue e tente novamente.';
    }
    
    // Capturar dados do formulário
    $enviar_email = isset($_POST['enviar_por_email']) ? 1 : 0;
    $cpf = trim($_POST['cpf'] ?? '');
    $colaborador = trim($_POST['colaborador'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $coordenador = trim($_POST['coordenador'] ?? '');
    $funcao = trim($_POST['funcao'] ?? '');
    $data_inicial = $_POST['data_inicial'] ?? '';
    $data_final = $_POST['data_final'] ?? '';
    $atividades_realizadas = trim($_POST['atividades_realizadas'] ?? '');
    $atividades_andamento = trim($_POST['atividades_andamento'] ?? '');
    $atividades_previstas = trim($_POST['atividades_previstas'] ?? '');
    $pontos_relevantes = trim($_POST['pontos_relevantes'] ?? '');
    $pontos_criticos = trim($_POST['pontos_criticos'] ?? '');
    $local_data = trim($_POST['local_data'] ?? '');
    
    // Validações básicas
    if (empty($colaborador)) $errors[] = 'Nome do colaborador é obrigatório.';
    if (empty($cpf)) $errors[] = 'CPF é obrigatório.';
    if (empty($data_inicial)) $errors[] = 'Data inicial é obrigatória.';
    if (empty($data_final)) $errors[] = 'Data final é obrigatória.';
    if (empty($atividades_realizadas)) $errors[] = 'Atividades realizadas são obrigatórias.';
    
    // Validar CPF (básico)
    $cpf_numbers = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf_numbers) !== 11) {
        $errors[] = 'CPF deve ter 11 dígitos.';
    }
    
    // Se não há erros, inserir no banco
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO acompanhamento_atividades 
                    (enviar_por_email, cpf, colaborador, setor, coordenador, funcao, 
                     data_inicial, data_final, atividades_realizadas, atividades_andamento,
                     atividades_previstas, pontos_relevantes, pontos_criticos, local_data, data_registro) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isssssssssssss', 
                $enviar_email, $cpf_numbers, $colaborador, $setor, $coordenador, $funcao,
                $data_inicial, $data_final, $atividades_realizadas, $atividades_andamento,
                $atividades_previstas, $pontos_relevantes, $pontos_criticos, $local_data
            );
            
            if ($stmt->execute()) {
                $success = true;
                // Regenerar CSRF para novo formulário
                $_SESSION['csrf_aai'] = bin2hex(random_bytes(32));
            } else {
                $errors[] = 'Erro ao salvar o formulário.';
            }
            
        } catch (Exception $e) {
            error_log('[AAI] ' . $e->getMessage());
            $errors[] = 'Erro interno. Tente novamente.';
        }
    }
}

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
                <span>Formulário AAI</span>
            </div>
        </div>
        <div class="actions">
            <span class="tag" id="tz">America/Rio_Branco</span>
            <span class="tag" title="Usuário logado">👤 <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></span>
            <a class="btn" href="index.php" title="Dashboard">Dashboard</a>
            <a class="btn" href="/logout.php" title="Sair">Sair</a>
        </div>
    </div>

    <main id="conteudo">
        <section class="panel">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px">
                <div class="icon" style="background:rgba(79,70,229,.15); border:1px solid rgba(79,70,229,.35); border-radius:12px; padding:8px">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div>
                    <h2 style="margin:0">Formulário AAI</h2>
                    <p style="margin:0; color:var(--muted); font-size:14px">Acompanhamento de Atividades - Apoio Técnico, Administrativo e Operacional</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert success" style="background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.35); color:#dcfce7; padding:12px 16px; border-radius:12px; margin-bottom:20px">
                    <strong>✓ Formulário enviado com sucesso!</strong> O acompanhamento de atividades foi registrado no sistema.
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert error" style="background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.35); color:#fecaca; padding:12px 16px; border-radius:12px; margin-bottom:20px">
                    <strong>Erro ao enviar:</strong>
                    <ul style="margin:8px 0 0 0; padding-left:20px">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_aai']) ?>">
                
                <!-- Informações do Colaborador -->
                <div class="form-section">
                    <h3 style="color:var(--brand-2); margin-bottom:16px; font-size:18px">Informações do Colaborador</h3>
                    
                    <div class="form-grid two">
                        <div class="form-group">
                            <label for="colaborador">Nome do Colaborador *</label>
                            <input type="text" id="colaborador" name="colaborador" class="input" 
                                   placeholder="Nome completo do colaborador" required
                                   value="<?= htmlspecialchars($_POST['colaborador'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" class="input" 
                                   placeholder="000.000.000-00" maxlength="14" required
                                   value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-grid three">
                        <div class="form-group">
                            <label for="setor">Setor</label>
                            <input type="text" id="setor" name="setor" class="input" 
                                   placeholder="Setor de trabalho"
                                   value="<?= htmlspecialchars($_POST['setor'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="coordenador">Coordenador</label>
                            <input type="text" id="coordenador" name="coordenador" class="input" 
                                   placeholder="Nome do coordenador"
                                   value="<?= htmlspecialchars($_POST['coordenador'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="funcao">Função</label>
                            <input type="text" id="funcao" name="funcao" class="input" 
                                   placeholder="Função exercida"
                                   value="<?= htmlspecialchars($_POST['funcao'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Período de Avaliação -->
                <div class="form-section">
                    <h3 style="color:var(--brand-2); margin-bottom:16px; font-size:18px">Período de Avaliação</h3>
                    
                    <div class="form-grid two">
                        <div class="form-group">
                            <label for="data_inicial">Data Inicial *</label>
                            <input type="date" id="data_inicial" name="data_inicial" class="input" required
                                   value="<?= htmlspecialchars($_POST['data_inicial'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="data_final">Data Final *</label>
                            <input type="date" id="data_final" name="data_final" class="input" required
                                   value="<?= htmlspecialchars($_POST['data_final'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Atividades -->
                <div class="form-section">
                    <h3 style="color:var(--brand-2); margin-bottom:16px; font-size:18px">Atividades</h3>
                    
                    <div class="form-group">
                        <label for="atividades_realizadas">Atividades Realizadas *</label>
                        <textarea id="atividades_realizadas" name="atividades_realizadas" class="input textarea" 
                                  rows="4" placeholder="Descreva as atividades que foram realizadas no período..." required><?= htmlspecialchars($_POST['atividades_realizadas'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="atividades_andamento">Atividades em Andamento</label>
                        <textarea id="atividades_andamento" name="atividades_andamento" class="input textarea" 
                                  rows="3" placeholder="Atividades que estão sendo desenvolvidas..."><?= htmlspecialchars($_POST['atividades_andamento'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="atividades_previstas">Atividades Previstas</label>
                        <textarea id="atividades_previstas" name="atividades_previstas" class="input textarea" 
                                  rows="3" placeholder="Atividades planejadas para o próximo período..."><?= htmlspecialchars($_POST['atividades_previstas'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Pontos Relevantes e Críticos -->
                <div class="form-section">
                    <h3 style="color:var(--brand-2); margin-bottom:16px; font-size:18px">Observações</h3>
                    
                    <div class="form-group">
                        <label for="pontos_relevantes">Pontos Relevantes</label>
                        <textarea id="pontos_relevantes" name="pontos_relevantes" class="input textarea" 
                                  rows="3" placeholder="Destaque os pontos positivos e relevantes..."><?= htmlspecialchars($_POST['pontos_relevantes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="pontos_criticos">Pontos Críticos</label>
                        <textarea id="pontos_criticos" name="pontos_criticos" class="input textarea" 
                                  rows="3" placeholder="Identifique dificuldades, problemas ou pontos de atenção..."><?= htmlspecialchars($_POST['pontos_criticos'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Informações Finais -->
                <div class="form-section">
                    <h3 style="color:var(--brand-2); margin-bottom:16px; font-size:18px">Informações Finais</h3>
                    
                    <div class="form-group">
                        <label for="local_data">Local e Data</label>
                        <input type="text" id="local_data" name="local_data" class="input" 
                               placeholder="Ex: Rio de Janeiro, 22 de agosto de 2025"
                               value="<?= htmlspecialchars($_POST['local_data'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="enviar_por_email" value="1" 
                                   <?= isset($_POST['enviar_por_email']) ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Enviar cópia por e-mail
                        </label>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="form-actions">
                    <button type="submit" class="btn brand">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        Enviar Formulário
                    </button>
                    
                    <button type="reset" class="btn" onclick="if(confirm('Limpar todos os campos?')) { window.location.reload(); }">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                        </svg>
                        Limpar
                    </button>
                    
                    <a href="index.php" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5"/>
                            <polyline points="12 19 5 12 12 5"/>
                        </svg>
                        Voltar ao Dashboard
                    </a>
                </div>
            </form>
        </section>

        <footer style="margin-top:24px">
            © <span id="ano"><?= date('Y') ?></span> <?php echo htmlspecialchars($PROJECT_NAME); ?> · 
            Formulário AAI - Acompanhamento de Atividades
        </footer>
    </main>
</div>

<style>
/* Estilos específicos para o formulário */
.form-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-grid {
    display: grid;
    gap: 16px;
}

.form-grid.two {
    grid-template-columns: 1fr 1fr;
}

.form-grid.three {
    grid-template-columns: 1fr 1fr 1fr;
}

@media (max-width: 768px) {
    .form-grid.two,
    .form-grid.three {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
    opacity: 0.9;
}

.textarea {
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
}

.checkbox-group {
    flex-direction: row !important;
    align-items: center;
    gap: 0;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
}

.checkbox-label input[type="checkbox"] {
    margin: 0;
    width: 18px;
    height: 18px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-start;
    flex-wrap: wrap;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,.08);
}

@media (max-width: 600px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        justify-content: center;
    }
}

/* Máscara para CPF */
#cpf {
    mask: 000.000.000-00;
}
</style>

<script>
// Máscara para CPF
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});

// Validação de datas
document.getElementById('data_final').addEventListener('change', function() {
    const dataInicial = document.getElementById('data_inicial').value;
    const dataFinal = this.value;
    
    if (dataInicial && dataFinal && dataFinal < dataInicial) {
        alert('A data final não pode ser anterior à data inicial.');
        this.value = '';
    }
});

// Auto-preenchimento do local e data
document.addEventListener('DOMContentLoaded', function() {
    const localDataField = document.getElementById('local_data');
    if (!localDataField.value) {
        const hoje = new Date().toLocaleDateString('pt-BR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        localDataField.value = `Xapuri, ${hoje}`;
    }
});

// Ano no footer
document.getElementById('ano').textContent = new Date().getFullYear();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>