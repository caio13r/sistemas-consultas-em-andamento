<?php

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database5;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['RP1acesso']) && $row['RP1acesso'] == false)) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='consulta-identidade';
  </script>";
  exit;
}

$tituloConsulta = 'Consulta de Prescrições por Intervalo de Datas';

// Definir datas padrão
$dataFinal = date('Y-m-d\TH:i');
$dataInicial = date('Y-m-d\TH:i', strtotime('-30 days'));

?>

<div class="col-md-6 offset-md-3 mb-4">
  <form action="" method="post">
    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="data_inicial">Data Inicial:</label>
        <input type="datetime-local" id="data_inicial" name="data_inicial" class="form-control" value="<?= $dataInicial ?>" required>
      </div>
      <div class="form-group col-md-6">
        <label for="data_final">Data Final:</label>
        <input type="datetime-local" id="data_final" name="data_final" class="form-control" value="<?= $dataFinal ?>" required>
      </div>
      <div class="form-group col-md-12">
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
      </div>
    </div>
  </form>
</div>

<?php
if (isset($inputPost["submit"])) {
  $dataInicial = $inputPost["data_inicial"];
  $dataFinal = $inputPost["data_final"];
}

// Converter as datas para o formato esperado pelo banco de dados
$dataInicialConvertida = date('Y-m-d H:i:s', strtotime($dataInicial));
$dataFinalConvertida = date('Y-m-d H:i:s', strtotime($dataFinal));

try {
  $db = Database5::getInstance();
  $con = $db->getConnection();

  // Para depuração: Exibir as datas convertidas
  // echo "Data Inicial Convertida: " . $dataInicialConvertida . "<br>";
  // echo "Data Final Convertida: " . $dataFinalConvertida . "<br>";

  $query = "SELECT 
                psc,
                COUNT(*) as quantidade
            FROM 
                db_prescricao.tbl_prescricoes
            WHERE 
                STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') BETWEEN :data_inicial AND :data_final
            GROUP BY 
                psc
            ORDER BY 
                quantidade DESC";
  $stmt = $con->prepare($query);
  $stmt->bindValue(':data_inicial', $dataInicialConvertida);
  $stmt->bindValue(':data_final', $dataFinalConvertida);

  // Para depuração: Exibir a query com os valores reais
  $queryWithValues = str_replace(
    [':data_inicial', ':data_final'],
    ["'" . $dataInicialConvertida . "'", "'" . $dataFinalConvertida . "'"],
    $query
  );

  $stmt->execute();
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Inicialize arrays para contar a quantidade de resultados por psc
  $quantidadePorPsc = [];

  // Iterar pelos resultados e contar a quantidade por psc
  foreach ($result as $row) {
    $psc = $row['psc'];
    $quantidade = $row['quantidade'];

    if( $psc == ''){
      $quantidadePorPsc['Legado'] = $quantidade;
    }else {
      $quantidadePorPsc[$psc] = $quantidade;
    }
  }

  // Ordenar o array associativo pela chave (psc)
  ksort($quantidadePorPsc);
} catch (PDOexception $error) {
  error_log("Erro consulta prescricao-7: " . $error->getMessage());
  echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
  $result = [];
  $quantidadePorPsc = [];
}

if (count($result) > 0) {
?>

  <div class="row">
    <div class="col">
      <canvas id="grafico" style="max-width: 100%; max-height: 500px; height: 300px;"></canvas>
    </div>
  </div>
  <div class="alert alert-info mt-3" role="alert">
    <b>Período de pesquisa:</b> <?= htmlspecialchars($dataInicial) ?> a <?= htmlspecialchars($dataFinal) ?>
  </div>

<?php } else {
  echo "<div class='alert alert-danger mt-3' role='alert'>
          <b>Erro</b> <br>
          Não foi encontrada nenhuma informação.
          </div>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  var ctx = document.getElementById('grafico').getContext('2d');
  var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_keys($quantidadePorPsc)) ?>,
      datasets: [{
        label: 'Quantidade',
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1,
        data: <?= json_encode(array_values($quantidadePorPsc)) ?>
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
</script>
</div>