<?php
// Verifica se o formulário foi enviado via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Captura os valores enviados
    $nome = $_POST["nome"] ?? 'Heraldo';
    $email = $_POST["email"] ?? 'heraldo.as1@gmail.com';

    // Exemplo: apenas mostrar os dados recebidos
    echo "<h2>Dados Recebidos:</h2>";
    echo "Nome: " . htmlspecialchars($nome) . "<br>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
} else {
    echo "Acesso inválido.";
}
?>