<?php
/* ==================================================================
   cohidro_insert.php — corrigido: colunas conferidas com tabela real
   ================================================================== */
require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método não permitido.'; exit; }
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/conn.php';

// CSRF do formulário
if (!hash_equals($_SESSION['csrf_form'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); echo 'Sessão expirada.'; exit; }

// Valores vindos do formulário / sessão
$cpf   = digits_only((string)($_POST['cpf'] ?? $_SESSION['cpf'] ?? ''));
$nome  = trim((string)($_POST['nome'] ?? $_SESSION['nome'] ?? ''));
$dir   = trim((string)($_POST['diretoria'] ?? $_SESSION['diretoria'] ?? ''));
$di    = trim((string)($_POST['data_inicial'] ?? ''));
$df    = trim((string)($_POST['data_final']   ?? ''));
$ar    = trim((string)($_POST['atividades_realizadas'] ?? ''));
$ap    = trim((string)($_POST['atividades_previstas'] ?? ''));
$pr    = trim((string)($_POST['pontos_relevantes'] ?? ''));

// Validações mínimas
$errors=[]; 
if($cpf===''||strlen($cpf)<11)$errors[]='CPF ausente/ inválido.'; 
if($nome==='')$errors[]='Nome ausente.'; 
if($di===''||$df==='')$errors[]='Período inválido.'; 
if($di&&$df&&$di>$df)$errors[]='Data inicial > data final.';
if ($errors){ http_response_code(400); echo implode("\n",$errors); exit; }

// Conforme estrutura enviada: id (auto inc), nome, cpf, diretoria, data_inicial,
// data_final, atividades_realizadas, atividades_previstas, pontos_relevantes, data_registro
$sql = "INSERT INTO acompanhamento_atividades
          (nome, cpf, diretoria, data_inicial, data_final,
           atividades_realizadas, atividades_previstas, pontos_relevantes)
        VALUES (?,?,?,?,?,?,?,?)";
$st = $conn->prepare($sql);
if(!$st){ http_response_code(500); echo 'Erro ao preparar INSERT.'; exit; }
$st->bind_param('ssssssss', $nome, $cpf, $dir, $di, $df, $ar, $ap, $pr);
$ok=$st->execute(); if(!$ok){ http_response_code(500); echo 'Falha ao salvar.'; exit; }

header('Location: formulario.php?ok=1');
exit;
?>
