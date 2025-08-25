<?php
/* ==================================================================
   bootstrap.php — inicia sessão UMA vez + helpers globais
   ================================================================== */
if (session_status() !== PHP_SESSION_ACTIVE) {
  // Segurança básica de cookies de sessão
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

/* ---------- Helpers (com guardas) ---------- */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
  }
}
if (!function_exists('csrf_check')) {
  function csrf_check(string $t): bool { return hash_equals($_SESSION['csrf'] ?? '', $t); }
}
if (!function_exists('digits_only')) {
  function digits_only(string $s): string { return preg_replace('/\D+/', '', $s); }
}
if (!function_exists('status_ativo')) {
  // Política permissiva: se não estiver explicitamente inativo → ativo
  function status_ativo(?string $s): bool {
    $s = mb_strtoupper(trim((string)$s));
    if ($s === '') return true;
    $inativos = ['INATIVO','DESLIGADO','BLOQUEADO','AFASTADO','SUSPENSO'];
    return !in_array($s, $inativos, true);
  }
}
if (!function_exists('redirect_by_level')) {
  function redirect_by_level(int $lvl): string {
    return match ($lvl) { 3=>'index.php', 2=>'dashboard.php', default=>'formulario.php' };
  }
}
