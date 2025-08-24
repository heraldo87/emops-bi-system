<?php
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], !empty($_SERVER['HTTPS']), true);
}
session_destroy();
header("Location: /login.php");
exit;
