<?php
$DB_HOST = 'localhost';
$DB_USER = 'cortex360';
$DB_PASS = 'Cortex360Vini';
$DB_NAME = 'cortex360';
$DB_PORT = 3306;

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($conn->connect_error) {
  die('Erro ao conectar ao MySQL: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');