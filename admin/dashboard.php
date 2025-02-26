<?php include 'header.php'; ?>
<?php
session_start();
if(!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true){
    header("Location: login.php");
    exit;
}
date_default_timezone_set('America/Sao_Paulo');
require_once '../inc/conexao.php';

// Total de Chamados
$totalTickets = $pdo->query("SELECT COUNT(*) as total FROM tickets")->fetch(PDO::FETCH_ASSOC)['total'];

// Chamados por Status
$statusData = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM tickets GROUP BY status");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusData[$row['status']] = $row['total'];
}
$statuses = ['Aberto', 'Em Andamento', 'Resolvido', 'Cancelado'];
$statusCounts = [];
foreach($statuses as $status){
    $statusCounts[] = isset($statusData[$status]) ? (int)$statusData[$status] : 0;
}

// Tempo Médio de Resolução (para chamados resolvidos)
$avgResult = $pdo->query("SELECT AVG(TIMESTAMPDIFF(SECOND, data_criacao, data_atualizacao)) as avg_seconds FROM tickets WHERE status = 'Resolvido'")->fetch(PDO::FETCH_ASSOC);
$avgSeconds = $avgResult['avg_seconds'];
if($avgSeconds !== null){
    $hours   = floor($avgSeconds / 3600);
    $minutes = floor(($avgSeconds % 3600) / 60);
    $seconds = $avgSeconds % 60;
    $avgResolutionTime = sprintf("%02dh %02dm %02ds", $hours, $minutes, $seconds);
} else {
    $avgResolutionTime = "N/A";
}

// Tendência Mensal (últimos 12 meses)
$monthlyTrend = [];
for($i = 11; $i >= 0; $i--){
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $stmtMonth = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE data_criacao BETWEEN ? AND ?");
    $stmtMonth->execute([$start . " 00:00:00", $end . " 23:59:59"]);
    $resultMonth = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    $monthlyTrend[date('M Y', strtotime($start))] = (int)$resultMonth['total'];
}
$monthlyLabels = array_keys($monthlyTrend);
$monthlyData = array_values($monthlyTrend);

// Top 5 Setores com mais Chamados
$sectorData = $pdo->query("SELECT setor, COUNT(*) as total FROM tickets GROUP BY setor ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$sectorLabels = [];
$sectorCounts = [];
foreach($sectorData as $row){
    $sectorLabels[] = $row['setor'];
    $sectorCounts[] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Administrativo - Unica Serviços</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- CSS Customizado para o Dashboard (pode ser integrado ao admin-estilos.css) -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
 
  <!-- Conteúdo do Dashboard -->
  <div class="container-fluid mt-4">
    <h2 class="mb-4">Dashboard Administrativo</h2>
    
    <!-- Cards com Métricas -->
    <div class="row">
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary shadow">
          <div class="card-body">
            <h5 class="card-title">Total de Chamados</h5>
            <p class="card-text display-4"><?php echo $totalTickets; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-danger shadow">
          <div class="card-body">
            <h5 class="card-title">Chamados Abertos</h5>
            <p class="card-text display-4"><?php echo isset($statusData['Aberto']) ? $statusData['Aberto'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-dark bg-warning shadow">
          <div class="card-body">
            <h5 class="card-title">Em Andamento</h5>
            <p class="card-text display-4"><?php echo isset($statusData['Em Andamento']) ? $statusData['Em Andamento'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-success shadow">
          <div class="card-body">
            <h5 class="card-title">Resolvidos</h5>
            <p class="card-text display-4"><?php echo isset($statusData['Resolvido']) ? $statusData['Resolvido'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-secondary shadow">
          <div class="card-body">
            <h5 class="card-title">Cancelados</h5>
            <p class="card-text display-4"><?php echo isset($statusData['Cancelado']) ? $statusData['Cancelado'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-info shadow">
          <div class="card-body">
            <h5 class="card-title">Tempo Médio de Resolução</h5>
            <p class="card-text display-4" style="font-size:1.5rem;"><?php echo $avgResolutionTime; ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
      <!-- Gráfico de Distribuição por Status (Doughnut) -->
      <div class="col-md-6 mb-4">
        <div class="card shadow">
          <div class="card-header">
            <h5>Status dos Chamados</h5>
          </div>
          <div class="card-body">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
      <!-- Gráfico de Tendência Mensal (Linha) -->
      <div class="col-md-6 mb-4">
        <div class="card shadow">
          <div class="card-header">
            <h5>Chamados por Mês (Últimos 12 meses)</h5>
          </div>
          <div class="card-body">
            <canvas id="monthlyChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Gráfico de Distribuição por Setor (Pizza) -->
    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card shadow">
          <div class="card-header">
            <h5>Chamados por Setor (Top 5)</h5>
          </div>
          <div class="card-body">
            <canvas id="sectorChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    
  </div>
  
  <!-- Scripts para Gráficos -->
  <script>
    // Gráfico de Status (Doughnut)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode($statuses); ?>,
        datasets: [{
          data: <?php echo json_encode($statusCounts); ?>,
          backgroundColor: [
            'rgba(220,53,69,0.7)',    // Aberto - vermelho
            'rgba(255,193,7,0.7)',     // Em Andamento - amarelo
            'rgba(40,167,69,0.7)',     // Resolvido - verde
            'rgba(108,117,125,0.7)'    // Cancelado - cinza
          ],
          borderColor: [
            'rgba(220,53,69,1)',
            'rgba(255,193,7,1)',
            'rgba(40,167,69,1)',
            'rgba(108,117,125,1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
    
    // Gráfico de Tendência Mensal (Linha)
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($monthlyLabels); ?>,
        datasets: [{
          label: 'Chamados',
          data: <?php echo json_encode($monthlyData); ?>,
          backgroundColor: 'rgba(0, 123, 255, 0.2)',
          borderColor: 'rgba(0, 123, 255, 1)',
          borderWidth: 2,
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });
    
    // Gráfico de Setores (Pizza)
    const sectorCtx = document.getElementById('sectorChart').getContext('2d');
    const sectorChart = new Chart(sectorCtx, {
      type: 'pie',
      data: {
        labels: <?php echo json_encode($sectorLabels); ?>,
        datasets: [{
          data: <?php echo json_encode($sectorCounts); ?>,
          backgroundColor: [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)'
          ],
          borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  </script>
  
  <!-- Bootstrap JS e dependências -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
