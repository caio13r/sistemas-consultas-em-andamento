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

$tituloConsulta = 'Consulta de Prescrições por UF';

$ufs = [
    "AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA",
    "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN",
    "RS", "RO", "RR", "SC", "SP", "SE", "TO"
];

sort($ufs); // Ordenar os estados em ordem alfabética

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
                <label for="uf">Selecione o Estado:</label>
                <select id="uf" name="uf" class="form-control" required>
                    <option value="todos" selected>Todos</option>
                    <?php foreach ($ufs as $uf) { ?>
                        <option value="<?= $uf ?>"><?= $uf ?></option>
                    <?php } ?>
                </select>
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
    $ufSelecionada = $inputPost["uf"];
} else {
    $ufSelecionada = "todos";
}

// Converter as datas para o formato esperado pelo banco de dados
$dataInicialConvertida = date('Y-m-d H:i:s', strtotime($dataInicial));
$dataFinalConvertida = date('Y-m-d H:i:s', strtotime($dataFinal));

try {
    $db = Database5::getInstance();
    $con = $db->getConnection();

    // Consulta para o gráfico (todos os estados)
    $queryGrafico = "SELECT 
                        uf,
                        COUNT(*) as quantidade
                    FROM 
                        db_prescricao.tbl_prescricoes
                    WHERE 
                        STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') BETWEEN :data_inicial AND :data_final
                    GROUP BY 
                        uf
                    ORDER BY 
                        quantidade DESC";
    $stmtGrafico = $con->prepare($queryGrafico);
    $stmtGrafico->bindValue(':data_inicial', $dataInicialConvertida);
    $stmtGrafico->bindValue(':data_final', $dataFinalConvertida);
    $stmtGrafico->execute();
    $resultGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

    // Inicialize arrays para contar a quantidade de resultados por estado para o gráfico
    $quantidadePorEstado = [];

    // Iterar pelos resultados e contar a quantidade por estado
    foreach ($resultGrafico as $row) {
        $uf = $row['uf'];
        $quantidade = $row['quantidade'];

        $quantidadePorEstado[$uf] = $quantidade;
    }

    // Ordenar o array associativo pela chave (UF)
    ksort($quantidadePorEstado);

    // Consulta para a tabela (estado selecionado)
    if ($ufSelecionada == "todos") {
        $queryTabela = "SELECT 
                            uf,
                            insc,
                            cd_nome,
                            paciente_nome,
                            tipo,
                            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') as `data`
                        FROM 
                            db_prescricao.tbl_prescricoes
                        WHERE 
                            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') BETWEEN :data_inicial AND :data_final
                        ORDER BY 
                            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') DESC";
        $stmtTabela = $con->prepare($queryTabela);
        $stmtTabela->bindValue(':data_inicial', $dataInicialConvertida);
        $stmtTabela->bindValue(':data_final', $dataFinalConvertida);
    } else {
        $queryTabela = "SELECT 
                            uf,
                            insc,
                            cd_nome,
                            paciente_nome,
                            tipo,
                            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') as `data`
                        FROM 
                            db_prescricao.tbl_prescricoes
                        WHERE 
                            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') BETWEEN :data_inicial AND :data_final
                        AND 
                            uf = :uf
                        ORDER BY 
                            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') DESC";
        $stmtTabela = $con->prepare($queryTabela);
        $stmtTabela->bindValue(':data_inicial', $dataInicialConvertida);
        $stmtTabela->bindValue(':data_final', $dataFinalConvertida);
        $stmtTabela->bindValue(':uf', $ufSelecionada);
    }
    $stmtTabela->execute();
    $resultTabela = $stmtTabela->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOexception $error) {
    error_log("Erro consulta prescricao-4: " . $error->getMessage());
    echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
    $resultTabela = [];
    $quantidadePorEstado = [];
}

if (count($resultTabela) > 0) {
?>

    <div class="row">
        <div class="col">
            <canvas id="grafico" style="max-width: 100%; max-height: 400px;"></canvas>
        </div>
    </div>
    <div class="alert alert-info mt-3" role="alert">
        <b>Período de pesquisa:</b> <?= htmlspecialchars($dataInicial) ?> a <?= htmlspecialchars($dataFinal) ?>
    </div>

    <div class="row mt-4">
        <div class="col table-responsive">
            <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th scope="col">UF</th>
                        <th scope="col">Inscrição</th>
                        <th scope="col">Nome do(a) Cirurgião(ã)-Dentista</th>
                        <th scope="col">Nome do Paciente</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($resultTabela as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['uf']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['insc']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['cd_nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['paciente_nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['tipo']) . "</td>";
                        echo "<td>" . date('d-m-Y H:i', strtotime($row['data'])) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
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
            labels: <?= json_encode(array_keys($quantidadePorEstado)) ?>,
            datasets: [{
                label: 'Quantidade',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                data: <?= json_encode(array_values($quantidadePorEstado)) ?>
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