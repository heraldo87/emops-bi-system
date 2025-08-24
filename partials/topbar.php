<?php
// Arquivo: partials/topbar.php
?>
<div class="topbar">
  <div class="mobile-top">
    <!-- Ícone do menu: visível apenas no mobile via CSS (.btn-menu) -->
    <button id="btnMenu" class="btn btn-menu" aria-label="Abrir menu" title="Menu">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="breadcrumbs"><span>CORTEX360</span> <span style="opacity:.5">/</span> <span>Dashboard</span></div>
  </div>
  <div class="actions">
    <!-- Nome do usuário com o mesmo visual dos botões -->
    <span class="btn user-badge" title="Usuário logado">👤 <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></span>
    <a class="btn" href="/logout.php" title="Sair">Sair</a>
    <!-- Removidos: localização, Upload, Exportar, Formulário AAI -->
  </div>
</div>