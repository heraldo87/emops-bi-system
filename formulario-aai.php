rs']['id_colaborador'] = 'ID do colaborador deve ser numérico.';
  }
  if (!valid_date($val['data_inicial'])) {
    $feedback['errors']['data_inicial'] = 'Data inicial inválida (YYYY-MM-DD).';
  }
  if (!valid_date($val['data_final'])) {
    $feedback['errors']['data_final'] = 'Data final inválida (YYYY-MM-DD).';
  }
  if ($val['data_inicial'] && $val['data_final'] && $val['data_final'] < $val['data_inicial']) {
    $feedback['errors']['data_final'] = 'Data final não pode ser menor que a data inicial.';
  }
  if ($val['atividades_realizadas'] === '') {
    $feedback['errors']['atividades_realizadas'] = 'Descreva as atividades realizadas.';
  }
  if ($val['atividades_previstas'] === '') {
    $feedback['errors']['atividades_previstas'] = 'Descreva as atividades previstas.';
  }

  // Se sem erros: insere
  if (empty($feedback['errors'])) {
    try {
      date_default_timezone_set('America/Sao_Paulo');
      $agora = (new DateTime())->format('Y-m-d H:i:s');

      // IMPORTANTE: assumindo que `id` é AUTO_INCREMENT — não o incluímos no INSERT
      $sql = "INSERT INTO acompanhamento_atividades (
                enviar_por_email, cpf, id_colaborador, data_inicial, data_final,
                atividades_realizadas, atividades_previstas, pontos_relevantes, data_registro
              ) VALUES (?,?,?,?,?,?,?,?,?)";
      if (!$stmt = $conn->prepare($sql)) {
        throw new Exception('Prepare falhou: ' . $conn->error);
      }
      $id_colab = (int)$val['id_colaborador'];
      $stmt->bind_param(
        'ssissssss',
        $val['enviar_por_email'],
        $val['cpf'],
        $id_colab,
        $val['data_inicial'],
        $val['data_final'],
        $val['atividades_realizadas'],
        $val['atividades_previstas'],
        $val['pontos_relevantes'],
        $agora
      );
      if (!$stmt->execute()) {
        throw new Exception('Execute falhou: ' . $stmt->error);
      }

      $feedback['ok'] = true;
      $feedback['id'] = $stmt->insert_id ?: $conn->insert_id;
      $feedback['msg'] = 'Registro salvo com sucesso!';

      // Limpa o form
      $val = [
        'enviar_por_email'      => '',
        'cpf'                   => '',
        'id_colaborador'        => '',
        'data_inicial'          => '',
        'data_final'            => '',
        'atividades_realizadas' => '',
        'atividades_previstas'  => '',
        'pontos_relevantes'     => '',
      ];
      $stmt->close();
    } catch (Throwable $e) {
      $feedback['ok'] = false;
      $feedback['msg'] = 'Erro ao salvar no banco.';
      $feedback['errors']['db'] = $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EMOPS BI · Novo Registro – Acompanhamento</title>
  <meta name="theme-color" content="#0b1020" />
  <link rel="icon" href="/favicon.ico" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- CSS Global do tema -->
  <link rel="stylesheet" href="/assets/emops.css">
  <style>
    /* específicos desta página */
    .page-wrap{max-width:980px;margin:30px auto;padding:0 18px}
    .muted{opacity:.8}
    .error{color:#fda4af;font-size:12px;margin-top:6px}
    .form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
    .title-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
  </style>
</head>
<body>
  <main class="page-wrap" id="conteudo" aria-labelledby="titulo">
    <section class="panel" style="margin-bottom:18px">
      <div class="title-row">
        <div>
          <h3 id="titulo" style="margin:0">Novo Registro – Acompanhamento de Atividades</h3>
          <div class="muted" style="font-size:13px">Insira um novo registro na tabela <code>acompanhamento_atividades</code>.</div>
        </div>
        <a href="/" class="btn" title="Voltar ao sistema">Voltar</a>
      </div>
    </section>

    <?php if(isset($feedback['errors']['db'])): ?>
      <section class="panel" style="border-color:rgba(239,68,68,.45)">
        <h3>Erro de banco de dados</h3>
        <div class="muted"><?php echo htmlspecialchars($feedback['errors']['db']); ?></div>
      </section>
    <?php endif; ?>

    <section class="panel" aria-labelledby="formTitle">
      <h3 id="formTitle" style="margin:0 0 8px">Dados do registro</h3>
      <form method="post" action="" novalidate>
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf_token); ?>" />
        <div class="form-grid" style="margin-top:8px">
          <div>
            <label>Enviar por e-mail</label>
            <input name="enviar_por_email" type="email" placeholder="email@exemplo.com" value="<?php echo htmlspecialchars($val['enviar_por_email']); ?>" required />
            <?php if(isset($feedback['errors']['enviar_por_email'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['enviar_por_email']); ?></div><?php endif; ?>
          </div>
          <div>
            <label>CPF</label>
            <input name="cpf" id="fCpf" type="text" placeholder="Somente números" value="<?php echo htmlspecialchars($val['cpf']); ?>" required />
            <?php if(isset($feedback['errors']['cpf'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['cpf']); ?></div><?php endif; ?>
          </div>
          <div>
            <label>ID do colaborador</label>
            <input name="id_colaborador" type="number" min="0" step="1" placeholder="Ex.: 4" value="<?php echo htmlspecialchars($val['id_colaborador']); ?>" required />
            <?php if(isset($feedback['errors']['id_colaborador'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['id_colaborador']); ?></div><?php endif; ?>
          </div>
          <div>
            <label>Data inicial</label>
            <input name="data_inicial" type="date" value="<?php echo htmlspecialchars($val['data_inicial']); ?>" required />
            <?php if(isset($feedback['errors']['data_inicial'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['data_inicial']); ?></div><?php endif; ?>
          </div>
          <div>
            <label>Data final</label>
            <input name="data_final" type="date" value="<?php echo htmlspecialchars($val['data_final']); ?>" required />
            <?php if(isset($feedback['errors']['data_final'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['data_final']); ?></div><?php endif; ?>
          </div>
          <div class="full">
            <label>Atividades realizadas</label>
            <textarea name="atividades_realizadas" placeholder="Descreva as atividades realizadas" required><?php echo htmlspecialchars($val['atividades_realizadas']); ?></textarea>
            <?php if(isset($feedback['errors']['atividades_realizadas'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['atividades_realizadas']); ?></div><?php endif; ?>
          </div>
          <div class="full">
            <label>Atividades previstas</label>
            <textarea name="atividades_previstas" placeholder="Descreva as atividades previstas" required><?php echo htmlspecialchars($val['atividades_previstas']); ?></textarea>
            <?php if(isset($feedback['errors']['atividades_previstas'])): ?><div class="error"><?php echo htmlspecialchars($feedback['errors']['atividades_previstas']); ?></div><?php endif; ?>
          </div>
          <div class="full">
            <label>Pontos relevantes</label>
            <textarea name="pontos_relevantes" placeholder="Observações e pontos relevantes"><?php echo htmlspecialchars($val['pontos_relevantes']); ?></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button type="reset" class="btn">Limpar</button>
          <button type="submit" class="btn brand">Salvar</button>
        </div>
      </form>
    </section>

    <footer class="muted" style="text-align:center;margin-top:8px">© <span id="ano"></span> EMOPS · Acompanhamento</footer>
  </main>

  <!-- Toast -->
  <div id="toast" style="position:fixed; right:20px; bottom:20px; background:#0b1230; border:1px solid rgba(255,255,255,.12); color:var(--text); padding:12px 14px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.25); display:none"></div>

  <script>
    const $ = (q,ctx=document)=>ctx.querySelector(q);
    const showToast = (msg)=>{ const t=$('#toast'); t.textContent=msg; t.style.display='block'; setTimeout(()=>t.style.display='none', 2400); };
    window.addEventListener('DOMContentLoaded', ()=>{
      document.getElementById('ano').textContent = new Date().getFullYear();
      const cpf = document.getElementById('fCpf');
      if(cpf){ cpf.addEventListener('input', e=>{ e.target.value = e.target.value.replace(/\D+/g,'').slice(0,14); }); }
      const feedback = <?php echo json_encode($feedback, JSON_UNESCAPED_UNICODE); ?>;
      if (feedback && feedback.msg) {
        showToast(feedback.msg + (feedback.id ? ` (ID: ${feedback.id})` : ''));
      }
    });
  </script>
</body>
</html>

<?php /*a
SQL de referência (MySQL):

CREATE TABLE IF NOT EXISTS acompanhamento_atividades (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  enviar_por_email VARCHAR(255) NOT NULL,
  cpf VARCHAR(14) NOT NULL,
  id_colaborador BIGINT UNSIGNED NOT NULL,
  data_inicial DATE NOT NULL,
  data_final DATE NOT NULL,
  atividades_realizadas TEXT NOT NULL,
  atividades_previstas TEXT NOT NULL,
  pontos_relevantes TEXT NULL,
  data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_colab (id_colaborador),
  KEY idx_periodo (data_inicial, data_final)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

Observação: este arquivo assume que conn.php define $conn como instância de mysqli e já define set_charset('utf8mb4').
*/ ?>
