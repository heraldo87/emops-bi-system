<?php
/* ==================================================================
   auth_check.php — Middleware de autenticação e controle de acesso
   ================================================================== */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Função para exigir login e nível mínimo de acesso.
 * 
 * @param int    $min_level   Nível mínimo necessário
 * @param string $redirect    Página para redirecionar se não autorizado
 */
function require_login(int $min_level = 1, string $redirect = 'login.php'): void {
    if (empty($_SESSION['user_id']) || !isset($_SESSION['access_level'])) {
        session_destroy();
        header("Location: $redirect");
        exit;
    }

    $nivel = (int) $_SESSION['access_level'];

    if ($nivel < $min_level) {
        session_destroy();
        header("Location: $redirect");
        exit;
    }
}
?>
