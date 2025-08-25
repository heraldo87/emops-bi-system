<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload de Planilha - COHIDRO BI</title>
  <style>
    :root {
      --bg: #0b1020;
      --panel: #0f162e;
      --panel-2: #121a36;
      --muted: #a9b1d1;
      --text: #eef2ff;
      --brand: #4f46e5;
      --brand-2: #22d3ee;
      --ok: #22c55e;
      --danger: #ef4444;
      --card-radius: 18px;
      --shadow: 0 10px 30px rgba(0,0,0,.25);
    }
    
    * {box-sizing: border-box;}
    
    body {
      margin: 0;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, 'Helvetica Neue', Arial, sans-serif;
      background: radial-gradient(1200px 800px at 5% 0%, rgba(79,70,229,.25), rgba(34,211,238,.1) 35%, transparent 60%),
                  radial-gradient(1200px 800px at 100% 40%, rgba(34,211,238,.2), transparent 55%), var(--bg);
      color: var(--text);
      display: grid;
      place-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      width: min(600px, 90vw);
      background: linear-gradient(180deg, var(--panel) 0%, var(--panel-2) 100%);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      padding: 30px;
    }
    
    .header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
    }
    
    .logo {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
      box-shadow: var(--shadow);
      flex-shrink: 0;
    }
    
    h1 {
      font-size: 24px;
      margin: 0;
    }
    
    .subtitle {
      color: var(--muted);
      font-size: 14px;
      margin-top: 4px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
    }
    
    .file-input-wrapper {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    
    .custom-file {
      background: #0b1230;
      border: 2px dashed rgba(255,255,255,.2);
      border-radius: 10px;
      padding: 30px 20px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }
    
    .custom-file:hover {
      border-color: rgba(255,255,255,.4);
      background: #0d1632;
    }
    
    .file-icon {
      font-size: 32px;
      margin-bottom: 10px;
    }
    
    .file-input {
      display: none;
    }
    
    .filename {
      margin-top: 15px;
      padding: 8px 12px;
      background: rgba(255,255,255,.05);
      border-radius: 8px;
      font-size: 14px;
      word-break: break-all;
      display: none;
    }
    
    .btn {
      background: linear-gradient(135deg, rgba(79,70,229,.9), rgba(34,211,238,.9));
      color: white;
      border: none;
      border-radius: 12px;
      padding: 14px 24px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: transform 0.1s, box-shadow 0.2s;
    }
    
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,.3);
    }
    
    .btn:active {
      transform: translateY(0);
    }
    
    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .status {
      margin-top: 20px;
      padding: 12px 16px;
      border-radius: 10px;
      display: none;
    }
    
    .status.success {
      background: rgba(34,197,94,.15);
      border: 1px solid rgba(34,197,94,.4);
    }
    
    .status.error {
      background: rgba(239,68,68,.15);
      border: 1px solid rgba(239,68,68,.4);
    }
    
    .progress-container {
      width: 100%;
      height: 10px;
      background: rgba(255,255,255,.1);
      border-radius: 5px;
      margin-top: 10px;
      overflow: hidden;
      display: none;
    }
    
    .progress-bar {
      height: 100%;
      background: linear-gradient(90deg, var(--brand), var(--brand-2));
      width: 0%;
      transition: width 0.3s;
    }
    
    footer {
      text-align: center;
      margin-top: 30px;
      color: var(--muted);
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <header class="header">
      <div class="logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
      </div>
      <div>
        <h1>Upload de Planilha</h1>
        <div class="subtitle">Envie arquivos CSV ou Excel para processamento</div>
      </div>
    </header>
    
    <form id="uploadForm">
      <div class="form-group">
        <label for="fileUpload">Selecione o arquivo:</label>
        <div class="file-input-wrapper">
          <div class="custom-file" id="fileDropArea">
            <div class="file-icon">📊</div>
            <div>Arraste e solte sua planilha aqui<br>ou clique para selecionar</div>
            <input type="file" id="fileUpload" class="file-input" accept=".csv,.xlsx,.xls">
          </div>
          <div id="filename" class="filename"></div>
        </div>
      </div>
      
      <div class="form-group">
        <button type="submit" id="submitBtn" class="btn" disabled>Enviar Planilha</button>
        <div class="progress-container" id="progressContainer">
          <div class="progress-bar" id="progressBar"></div>
        </div>
      </div>
      
      <div id="statusSuccess" class="status success">
        Planilha enviada com sucesso! Os dados estão sendo processados.
      </div>
      
      <div id="statusError" class="status error">
        Erro ao enviar a planilha. Por favor, tente novamente.
      </div>
    </form>
    
    <footer>
      © <span id="year"></span> COHIDRO BI · Importação de Dados
    </footer>
  </div>

  <script>
    // Elementos DOM
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileUpload');
    const fileDropArea = document.getElementById('fileDropArea');
    const filenameDisplay = document.getElementById('filename');
    const submitBtn = document.getElementById('submitBtn');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const statusSuccess = document.getElementById('statusSuccess');
    const statusError = document.getElementById('statusError');
    
    // Definir o ano atual no rodapé
    document.getElementById('year').textContent = new Date().getFullYear();
    
    // Webhook URL - Ajuste para seu endpoint
    const webhookUrl = 'https://n8n.alunosdamedicina.com/webhook-test/massive_import';
    
    // Mostrar nome do arquivo quando selecionado
    fileInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        filenameDisplay.textContent = this.files[0].name;
        filenameDisplay.style.display = 'block';
        submitBtn.disabled = false;
      } else {
        filenameDisplay.style.display = 'none';
        submitBtn.disabled = true;
      }
    });
    
    // Suporte para arrastar e soltar
    fileDropArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'rgba(34,211,238,.6)';
      this.style.background = '#121c3d';
    });
    
    fileDropArea.addEventListener('dragleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'rgba(255,255,255,.2)';
      this.style.background = '#0b1230';
    });
    
    fileDropArea.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'rgba(255,255,255,.2)';
      this.style.background = '#0b1230';
      
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        fileInput.files = e.dataTransfer.files;
        filenameDisplay.textContent = e.dataTransfer.files[0].name;
        filenameDisplay.style.display = 'block';
        submitBtn.disabled = false;
      }
    });
    
    // Também permite clicar na área para selecionar o arquivo
    fileDropArea.addEventListener('click', function() {
      fileInput.click();
    });
    
    // Enviar o formulário
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (!fileInput.files || !fileInput.files[0]) {
        return;
      }
      
      // Esconder status anteriores
      statusSuccess.style.display = 'none';
      statusError.style.display = 'none';
      
      // Preparar dados para envio
      const formData = new FormData();
      formData.append('file', fileInput.files[0]);
      
      // Desativar botão durante o envio
      submitBtn.disabled = true;
      submitBtn.textContent = 'Enviando...';
      
      // Mostrar barra de progresso
      progressContainer.style.display = 'block';
      progressBar.style.width = '0%';
      
      // Criar e configurar a requisição
      const xhr = new XMLHttpRequest();
      
      // Monitorar progresso do upload
      xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
          const percentComplete = (e.loaded / e.total) * 100;
          progressBar.style.width = percentComplete + '%';
        }
      });
      
      // Configurar callback para conclusão
      xhr.onload = function() {
        progressContainer.style.display = 'none';
        submitBtn.textContent = 'Enviar Planilha';
        
        if (xhr.status >= 200 && xhr.status < 300) {
          // Sucesso
          statusSuccess.style.display = 'block';
          // Resetar formulário após 3 segundos
          setTimeout(function() {
            form.reset();
            filenameDisplay.style.display = 'none';
            submitBtn.disabled = true;
          }, 3000);
        } else {
          // Erro
          statusError.style.display = 'block';
          submitBtn.disabled = false;
        }
      };
      
      // Configurar callback para erro
      xhr.onerror = function() {
        progressContainer.style.display = 'none';
        statusError.style.display = 'block';
        submitBtn.textContent = 'Enviar Planilha';
        submitBtn.disabled = false;
      };
      
      // Iniciar envio
      xhr.open('POST', webhookUrl, true);
      xhr.send(formData);
    });
  </script>
</body>
</html>