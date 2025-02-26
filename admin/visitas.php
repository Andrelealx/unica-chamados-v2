<?php include 'header.php'; ?>
<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}
require_once '../inc/conexao.php';

// Define o mês atual (formato "YYYY-MM")
$currentMonth = date("Y-m");

// Consulta dos registros das tabelas de locais
$stmtSec = $pdo->query("SELECT * FROM secretarias ORDER BY nome ASC");
$secretarias = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

$stmtInst = $pdo->query("SELECT * FROM instituicoes ORDER BY nome ASC");
$instituicoes = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

/**
 * Função para obter o status de visita para um local.
 */
function getVisitStatus($pdo, $tipo, $local_id, $currentMonth) {
    $stmt = $pdo->prepare("SELECT visitado FROM visitas WHERE tipo = ? AND local_id = ? AND mes = ?");
    $stmt->execute([$tipo, $local_id, $currentMonth]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data ? (int)$data['visitado'] : 0;
}

/**
 * Função para renderizar as linhas da tabela.
 */
function renderTable($data, $tipo, $pdo, $currentMonth) {
    foreach ($data as $row) {
        $visitado = getVisitStatus($pdo, $tipo, $row['id'], $currentMonth);
        echo "<tr data-local-id='{$row['id']}' data-tipo='{$tipo}'>";
        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($row['endereco']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bairro']) . "</td>";
        echo "<td class='text-center'>";
        echo "<label class='switch'>";
        echo "<input type='checkbox' class='visitado-checkbox' " . ($visitado ? "checked" : "") . ">";
        echo "<span class='slider round'></span>";
        echo "</label>";
        echo "</td>";
        echo "</tr>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Visitas Programadas - Admin - Unica Serviços</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- CSS Customizado -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
  <style>
    /* Tema escuro para toda a página */
    body {
      background: linear-gradient(135deg, #001f3f, #003366);
      font-family: 'Roboto', sans-serif;
      color: #f8f9fa;
      margin: 0;
      padding-bottom: 20px;
    }
    
    /* Cabeçalho – Mantém o padrão do painel */
    nav.navbar {
      background-color: #001237 !important;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    nav.navbar .navbar-brand {
      display: flex;
      align-items: center;
    }
    nav.navbar .navbar-brand img {
      max-height: 40px;
      margin-right: 10px;
    }
    nav.navbar .navbar-brand span {
      font-size: 1.25rem;
      color: #fff;
    }
    
    /* Botão Voltar aprimorado */
    .voltar-button a {
  display: inline-block;
  background-color: #007bff;
  color: #fff;
  padding: 12px 25px;
  border-radius: 5px;
  text-decoration: none;
  font-weight: bold;
  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
  transition: background-color 0.3s, transform 0.3s;
}

.voltar-button a:hover {
  background-color: #0056b3;
  transform: scale(1.05);
}

    
    /* Container principal */
    .visitas-container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 30px;
      background-color: #00274d;
      color: #f8f9fa;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }
    .visitas-container h2 {
      color: #fff;
      text-align: center;
      margin-bottom: 20px;
    }
    
    /* Botões para Marcar e Limpar Todos */
    .btn-group-custom {
      margin-bottom: 20px;
    }
    .btn-group-custom .btn {
      font-size: 0.9rem;
      padding: 8px 15px;
      border-radius: 5px;
      margin-right: 5px;
    }
    
    /* Abas */
    .nav-tabs .nav-link {
      color: #fff;
      font-weight: 600;
      background-color: #003366;
      border: none;
      margin-right: 5px;
    }
    .nav-tabs .nav-link.active {
      background-color: #007bff;
      color: #001237;
      border: none;
    }
    
    /* Tabela – Fundo sem gradiente, cores alternadas invertidas */
    table.table {
      font-size: 0.9rem;
      margin-bottom: 0;
    }
    table.table thead th {
      background-color: #001237;
      color: #fff;
      padding: 15px;
      border: none;
      text-align: center;
      vertical-align: middle;
    }
    table.table tbody td {
      padding: 12px;
      vertical-align: middle;
      text-align: center;
      border: none;
    }
    /* Linhas alternadas: ímpares em azul claro, pares em azul escuro */
    table.table tbody tr:nth-child(odd) {
      background-color: #003366;
    }
    table.table tbody tr:nth-child(even) {
      background-color: #001f3f;
    }
    table.table tbody tr:hover {
      background-color: #00264d;
    }
    
    /* Toggle switch para o checkbox (Visitado) */
    .switch {
      position: relative;
      display: inline-block;
      width: 46px;
      height: 24px;
    }
    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background-color: #28a745;
    }
    input:checked + .slider:before {
      transform: translateX(22px);
    }
    
    /* Botão de ação "Salvar" removido (atualização automática via toggle) */
  </style>
</head>
<body>
 
  <!-- Botão Voltar para Index -->
  <div class="container mt-3">
  <div class="text-right">
    <a href="painel.php" class="voltar-button animate__animated animate__pulse animate__infinite">
      <i class="fas fa-arrow-left"></i> Voltar
    </a>
  </div>
</div>


  <!-- Conteúdo Principal -->
  <div class="visitas-container">
    <h2>Visitas Programadas</h2>
    
    <!-- Botões Marcar/ Limpar Todos -->
    <div class="btn-group-custom text-right">
      <button id="marcarTodos" class="btn btn-success">Marcar Todos</button>
      <button id="limparTodos" class="btn btn-danger">Limpar Todos</button>
    </div>
    
    <!-- Abas para Secretarias e Instituições -->
    <ul class="nav nav-tabs" id="visitasTab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="secretarias-tab" data-toggle="tab" href="#secretarias" role="tab" aria-controls="secretarias" aria-selected="true">Secretarias</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="instituicoes-tab" data-toggle="tab" href="#instituicoes" role="tab" aria-controls="instituicoes" aria-selected="false">Escolas/Creches</a>
      </li>
    </ul>
    <div class="tab-content" id="visitasTabContent">
      <!-- Tab Secretarias -->
      <div class="tab-pane fade show active" id="secretarias" role="tabpanel" aria-labelledby="secretarias-tab">
        <div class="table-responsive mt-3">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Secretaria</th>
                <th>Endereço</th>
                <th>Bairro</th>
                <th>Visitado</th>
              </tr>
            </thead>
            <tbody>
              <?php renderTable($secretarias, "secretaria", $pdo, $currentMonth); ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Tab Instituições -->
      <div class="tab-pane fade" id="instituicoes" role="tabpanel" aria-labelledby="instituicoes-tab">
        <div class="table-responsive mt-3">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Instituição</th>
                <th>Endereço</th>
                <th>Bairro</th>
                <th>Visitado</th>
              </tr>
            </thead>
            <tbody>
              <?php renderTable($instituicoes, "instituicao", $pdo, $currentMonth); ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Botão de Exportação de Dados -->
    <div class="text-right mb-4">
      <a href="export_visitas.php" class="btn btn-success"><i class="fas fa-file-csv"></i> Exportar Dados (CSV)</a>
    </div>
  </div>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    // Atualização automática via toggle switch
    $('.visitado-checkbox').change(function(){
      var row = $(this).closest('tr');
      var localId = row.data('local-id');
      var tipo = row.data('tipo');
      var visitado = $(this).is(':checked') ? 1 : 0;
      
      $.ajax({
        url: 'update_visita.php',
        type: 'POST',
        data: {
          tipo: tipo,
          local_id: localId,
          visitado: visitado,
          mes: '<?php echo $currentMonth; ?>'
        },
        success: function(response){
          console.log("Status atualizado para local_id: " + localId);
        },
        error: function(){
          alert("Erro ao atualizar o status!");
        }
      });
    });
    
    // Botão Marcar Todos: marca todos os checkboxes e dispara a atualização
    $('#marcarTodos').click(function(){
      $('.visitado-checkbox:not(:checked)').each(function(){
        $(this).prop('checked', true).trigger('change');
      });
    });
    
    // Botão Limpar Todos: desmarca todos os checkboxes e dispara a atualização
    $('#limparTodos').click(function(){
      $('.visitado-checkbox:checked').each(function(){
        $(this).prop('checked', false).trigger('change');
      });
    });
  </script>
  
  <?php
  if(isset($_SESSION['sucesso'])){
    echo '<div class="alert alert-success text-center" role="alert">' . $_SESSION['sucesso'] . '</div>';
    unset($_SESSION['sucesso']);
  }
  if(isset($_SESSION['erro'])){
    echo '<div class="alert alert-danger text-center" role="alert">' . $_SESSION['erro'] . '</div>';
    unset($_SESSION['erro']);
  }
  ?>
</body>
</html>
