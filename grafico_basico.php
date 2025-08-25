<!doctype html><meta charset="utf-8">
<title>Gráfico de Barras</title>
<style>
  body{margin:0;background:#0f172a;color:#e5e7eb;font-family:Inter,system-ui,Arial}
  #wrap{padding:16px;max-width:820px;margin:0 auto}
  /* defina altura fixa pra evitar “pular” ao redimensionar */
  #card{height:420px;border:1px solid #1e293b;border-radius:12px;padding:12px}
  canvas{max-width:100%;height:100%}
</style>
<div id="wrap">
  <h3 style="margin:0 0 8px">AAI · Barras (interativo)</h3>
  <div id="card">
    <canvas id="c"></canvas>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart;

// --- DADOS FICTÍCIOS PARA TESTE ---
// opção 1 (simples): mesmas labels do donut
const labels = ["Realizado", "Pendente"];
const data   = [72, 28];

// // opção 2 (exemplo por diretoria): descomente para testar
// const labels = ["Norte","Sul","Leste","Oeste"];
// const data   = [12, 8, 15, 6];

function renderBar(labels, data){
  const ctx = document.getElementById('c').getContext('2d');
  if (chart) chart.destroy(); // evita sobreposição/crescimento
  chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Quantidade',
        data,
        borderWidth: 1
      }]
    },
    options: {
      animation: false,                 // sem animação (mais estável)
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true, position: 'bottom' },
        tooltip: { enabled: true }
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } },
        x: { grid: { display: false } }
      }
    }
  });
}

// render inicial
renderBar(labels, data);

// exemplo de atualização futura (sem animação):
// setTimeout(()=>{ renderBar(labels, [80, 20]); }, 3000);
</script>
