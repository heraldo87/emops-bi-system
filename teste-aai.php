<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Página básica com botão</title>
  <style>
    body { font-family: system-ui, Arial, sans-serif; margin: 2rem; }
    button { padding: 0.75rem 1rem; border: 0; border-radius: 8px; cursor: pointer; }
    .btn-primary { background: #2563eb; color: #fff; }
    .btn-primary:active { transform: translateY(1px); }
  </style>
</head>
<body>
  <h1>Olá 👋</h1>
  <p>Envie o formulário para o Webhook do n8n:</p>

  <form action="https://n8n.alunosdamedicina.com/webhook/extract_excel" method="POST">
    <label>Nome:</label><br>
    <input type="text" name="nome" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <button id="meuBotao" class="btn-primary" type="submit">Enviar</button>
  </form>

  <p id="mensagem" aria-live="polite" style="margin-top:1rem;"></p>
  <script>
    const botao = document.getElementById('meuBotao');
    const mensagem = document.getElementById('mensagem');
    let cliques = 0;

    botao.addEventListener('click', () => {
      cliques++;
      mensagem.textContent = `Você clicou ${cliques} vez(es).`;
    });
  </script>
</body>
</html>
