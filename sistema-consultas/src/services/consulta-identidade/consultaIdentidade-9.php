<?php

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

// Verificação de acesso
if ((Session::get('grupo') != 0 && $row['CI9acesso'] == false) || (Session::get('grupo') != 0 && $row['CI7acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Gráficos e Consulta Identidade por Intervalo';

// Verificar se um CRO foi selecionado
$croSelecionado = isset($_POST['cro']) ? $_POST['cro'] : '';

// Lista de todos os estados brasileiros
$estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

// Obtém o primeiro e último dia do mês atual
$data_inicio_default = date('Y-m-01');
$data_fim_default = date('Y-m-t');

// Define as datas para a pesquisa, usando os valores do POST se disponíveis, senão os valores padrão
$data_inicio = isset($_POST['data_inicio']) ? $_POST['data_inicio'] : $data_inicio_default;
$data_fim = isset($_POST['data_fim']) ? $_POST['data_fim'] : $data_fim_default;

?>

<meta charset="UTF-8">
<title><?php echo $tituloConsulta; ?></title>
<style>
    .box {
        border: 1px solid #ddd;
        padding: 20px;
        margin: 10px;
        flex: 1 1 45%;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        overflow-x: auto;
        display: block;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 3px;
        text-align: left;
    }

    th {
        background-color: #858796;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #ddd;
    }

    .chart-container {
        position: relative;
        height: 400px;
        width: 100%;
    }
</style>

<div class="container">
    <!-- Primeira seção -->
    <div class="box">
        <h2>Status de impressões</h2>
        <!-- Formulário para selecionar o estado (CRO) -->
        <form method="post" action="">
            <label for="cro">Selecione o estado (CRO):</label>
            <select name="cro" id="cro">
                <option value="">Total</option>
                <?php foreach ($estados as $estado) : ?>
                    <option value="<?php echo $estado; ?>" <?php if ($croSelecionado == $estado) echo 'selected'; ?>><?php echo $estado; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>

        <?php
        // Conexão com a base de dados
        $db = Database3::getInstance();
        $con = $db->getConnection();

        // Construir a cláusula WHERE com base no CRO selecionado
        $whereClause = "";
        if (!empty($croSelecionado)) {
            $whereClause = "WHERE CRO = '$croSelecionado'";
        }

        // Query SQL com o filtro do CRO
        $query = "
            SELECT TD.CFO_ID, YY.THOMAS,
                ((YY.THOMAS*100)/TD.CFO_ID) as perc_prod,
                KK.POSTADAS,
                ((KK.POSTADAS*100)/TD.CFO_ID) as perc_post,
                WW.ENTREGUES,
                ((WW.ENTREGUES*100)/TD.CFO_ID) as perc_entr,
                QQ.DEVOLVIDAS,
                ((QQ.DEVOLVIDAS*100)/TD.CFO_ID) as perc_dev,
                (KK.POSTADAS - WW.ENTREGUES - QQ.DEVOLVIDAS) AS EM_TRANSITO,
                (((KK.POSTADAS - WW.ENTREGUES - QQ.DEVOLVIDAS)*100)/TD.CFO_ID) as perc_tram
            FROM (SELECT COUNT(*) AS CFO_ID
                  FROM (SELECT DISTINCT CRO, CATEGORIA, INSC, PROFISSIONAL, MIN(data_emissao_id) AS DT_EMISSAO_CFOID
                        FROM CFO_CWS.dbo.cfo_id_cobranca
                        $whereClause
                        GROUP BY CRO, CATEGORIA, INSC, PROFISSIONAL) AS tt) AS TD,
             (SELECT COUNT(*) AS THOMAS FROM CFO_CWS.dbo.Identidade_coleta $whereClause) AS YY,
             (SELECT COUNT(*) AS POSTADAS FROM CFO_CWS.dbo.Carga_postagem_ECT WHERE EVENTO = 'Postado' AND AR IN (SELECT AR FROM CFO_CWS.dbo.Identidade_coleta $whereClause)) AS KK,
             (SELECT COUNT(*) AS ENTREGUES FROM CFO_CWS.dbo.Carga_postagem_ECT WHERE EVENTO = 'Entregue' AND AR IN (SELECT AR FROM CFO_CWS.dbo.Identidade_coleta $whereClause)) AS WW,
             (SELECT COUNT(*) AS DEVOLVIDAS FROM CFO_CWS.dbo.Carga_postagem_ECT WHERE EVENTO = 'Distribuído ao remetente' AND AR IN (SELECT AR FROM CFO_CWS.dbo.Identidade_coleta $whereClause)) AS QQ
            ";

        $stmt = $con->prepare($query);
        $stmt->execute();
        $resultadoGrafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'><th>CFO_ID</th><th>THOMAS</th><th>perc_prod</th><th>POSTADAS</th><th>perc_post</th><th>ENTREGUES</th><th>perc_entr</th><th>DEVOLVIDAS</th><th>perc_dev</th><th>EM_TRANSITO</th><th>perc_tram</th></tr>";

        foreach ($resultadoGrafico as $row) {
            echo "<tr style='border: 1px solid #ddd; padding: 8px;'><td>{$row['CFO_ID']}</td><td>{$row['THOMAS']}</td><td>{$row['perc_prod']}%</td><td>{$row['POSTADAS']}</td><td>{$row['perc_post']}%</td><td>{$row['ENTREGUES']}</td><td>{$row['perc_entr']}%</td><td>{$row['DEVOLVIDAS']}</td><td>{$row['perc_dev']}%</td><td>{$row['EM_TRANSITO']}</td><td>{$row['perc_tram']}%</td></tr>";
        }

        echo "</table><br><br>";
        // Encodando dados para JavaScript
        $dadosGrafico = json_encode($resultadoGrafico);
        ?>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <div class="chart-container">
            <canvas id="meuGrafico"></canvas>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var dados = JSON.parse('<?php echo $dadosGrafico; ?>');
                var thomas = '<?php echo $row['THOMAS']; ?>';
                var postadas = '<?php echo $row['POSTADAS']; ?>';
                var entregues = '<?php echo $row['ENTREGUES']; ?>';
                var devolvidas = '<?php echo $row['DEVOLVIDAS']; ?>';
                var em_transito = postadas - entregues - devolvidas;

                var ctx = document.getElementById('meuGrafico').getContext('2d');
                var meuGrafico = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Produzidas: ' + thomas, 'Postadas: ' + postadas, 'Entregues: ' + entregues, 'Devolvidas: ' + devolvidas, 'Em trânsito: ' + em_transito],
                        datasets: [{
                            label: 'Percentuais',
                            data: [
                                dados[0].perc_prod,
                                dados[0].perc_post,
                                dados[0].perc_entr,
                                dados[0].perc_dev,
                                dados[0].perc_tram
                            ],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(255, 159, 64, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                barPercentage: 0.9,
                                categoryPercentage: 0.8
                            },
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            datalabels: {
                                color: 'black',
                                display: function(context) {
                                    return context.dataset.data[context.dataIndex] > 0;
                                },
                                font: {
                                    weight: 'bold'
                                },
                                formatter: Math.round,
                                padding: 6
                            }
                        }
                    }
                });
            });
        </script>
    </div>

    <!-- Segunda seção -->
    <!-- <div class="box">
        <h2>Identidade Impressas em um Intervalo</h2>
        <p>Consulte abaixo o volume de identidades em policarbonato já enviadas, por período:</p>


        <div class="col-md-6 offset-md-3 mb-4">
            <form action="" method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="data_inicio">Data de Início:</label>
                        <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= $data_inicio ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="data_fim">Data de Fim:</label>
                        <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= $data_fim ?>" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
                </div>
            </form>
        </div>

        <?php
        try {
            $db = Database3::getInstance();
            $con = $db->getConnection();

            $query = "SELECT tom.NOME,
                            tom.CRO,
                            tom.CATEGORIA,
                            tom.INSCRICAO,
                            tom.CPFCNPJ,
                            tom.AR,
                            convert(char,tom.Data_Disponivel,29) as DISPONIVEL,
                            convert(char,tom.Data_Postado,29) as POSTADO
                        FROM [CFO_CWS].[dbo].[Carga_postagem_Thomas] as tom
                        WHERE tom.Data_Postado >= '{$data_inicio}' AND tom.Data_Postado <= '{$data_fim}'";
            $stmt = $con->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Inicialize arrays para contar a quantidade de resultados por dia para cada categoria
            $quantidade_por_dia = [];
            $categorias_por_dia = [];

            // Iterar pelos resultados e contar a quantidade por dia para cada categoria
            foreach ($result as $row) {
                $data_postado = date('Y-m-d', strtotime($row['POSTADO']));
                $categoria = $row['CATEGORIA'];

                if (!isset($quantidade_por_dia[$data_postado])) {
                    $quantidade_por_dia[$data_postado] = ['CD' => 0, 'Outras' => 0];
                    $categorias_por_dia[$data_postado] = [];
                }

                if (!in_array($categoria, $categorias_por_dia[$data_postado])) {
                    $categorias_por_dia[$data_postado][] = $categoria;
                }

                if ($categoria === 'CD') {
                    $quantidade_por_dia[$data_postado]['CD']++;
                } else {
                    $quantidade_por_dia[$data_postado]['Outras']++;
                }
            }

            // Ordenar o array associativo pela chave (data)
            ksort($quantidade_por_dia);
        } catch (PDOexception $error) {
            die("Erro ao retornar os dados: " . $error->getMessage());
        }

        if (count($result) > 0) {
        ?>

            <div class="row">
                <div class="col">
                    <canvas id="grafico" style="max-width: 100%; max-height: 400px;"></canvas>
                </div>
            </div>
            <div class="alert alert-info mt-3" role="alert">
                <b>Período de pesquisa:</b> <?= htmlspecialchars($data_inicio) ?> a <?= htmlspecialchars($data_fim) ?>
            </div>
        <?php
        } else {
            echo "<div class='alert alert-danger mt-3' role='alert'>
                    <b>Código inválido!</b> <br>
                    Não foi encontrado nenhum dado com esse código.
                    </div>";
        }
        ?>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            var ctx = document.getElementById('grafico').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($quantidade_por_dia)) ?>,
                    datasets: [{
                        label: 'CD',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        data: <?= json_encode(array_values(array_column($quantidade_por_dia, 'CD'))) ?>
                    }, {
                        label: 'Outras',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        data: <?= json_encode(array_values(array_column($quantidade_por_dia, 'Outras'))) ?>
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
    </div> -->
</div>