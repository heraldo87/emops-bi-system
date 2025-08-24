<?php
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
session_start();
if (empty($_SESSION['user_id'])) {
  $next = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
  header("Location: /login.php?next=$next");
  exit;
}