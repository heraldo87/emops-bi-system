<?php
// Arquivo: partials/topbar.php
if (!function_exists('__can_manage')) {
  function __can_manage(): bool {
    if (function_exists('has_level')) return has_level(3);
    return !empty($_SESSION['nivel_acesso']) && (int)$_SESSION['nivel_acesso'] === 3
        || !empty($_SESSION['access_level']) && (int)$_SESSION['access_level'] === 3;
  }
}

$user_name = htmlspecialchars($_SESSION['nome'] ?? 'Usuário');
?>
<div class="topbar">
  <div class="mobile-top">
    <button id="btnMenu" class="btn btn-menu" aria-label="Abrir menu" title="Menu">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="6"  x2="21" y2="6"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <div class="breadcrumbs">
      <span>CORTEX360</span> <span style="opacity:.5">/</span> <span>Dashboard</span>
    </div>
  </div>

  <div class="actions">
    <?php if (__can_manage()): ?>
      <a class="btn" href="/admin.php" title="Gerenciamento do sistema">Gestão do Sistema</a>
    <?php endif; ?>

    <!-- Logout vem antes -->
    <a class="btn" href="/logout.php" title="Sair">Sair</a>

    <!-- Nome do usuário vai à direita -->
    <span class="btn user-badge" title="Usuário logado">
      👤 <?= $user_name ?>
    </span>
  </div>
</div>
