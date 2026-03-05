<?php

use Cfo\SisConsultas\lib\Session;
Session::CheckSession();

$tituloConsulta = 'Consulta Identidade por Intervalo';

// Recebe as datas do formulário via POST; se não enviadas, usa valores padrão: primeiro e último dia do mês atual
$data_inicio = $_POST['data_inicio'] ?? date('Y-m-01');
$data_fim    = $_POST['data_fim'] ?? date('Y-m-t');

// Função para converter datas do registro retornado pela API
function converterData($dataString) {
    // Tenta criar um objeto DateTime usando o formato "d/m/Y H:i:s" (ajuste se necessário)
    $dt = DateTime::createFromFormat('d/m/Y H:i:s', $dataString);
    if (!$dt) {
        // Fallback: tenta converter com strtotime
        $dt = new DateTime($dataString);
    }
    return $dt;
}

// Inicializa o array para agrupar e contar os registros por dia (ou dia-mês)
$quantidade_por_dia = [];

try {
    // Parâmetros fixos de paginação para a API
    $page_number = 1;
    $page_amount = 100;

    // Recupera o token da API a partir das variáveis de ambiente
    $token = $_ENV['API_TOKEN'] ?? null;
    if (!$token) {
        die("Token da API não configurado. Verifique seu arquivo .env.");
    }

    // Monta a URL da API
    $apiUrl = "https://id.cfo.org.br/api/consulta/lista/identidade?token=" . urlencode($token)
            . "&page_number=" . urlencode($page_number)
            . "&page_amount=" . urlencode($page_amount);

    // Inicia a requisição cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die("Erro na requisição: " . curl_error($ch));
    }
    curl_close($ch);

    // Decodifica a resposta JSON da API
    $dataApi = json_decode($response, true);
    if (!isset($dataApi['list'])) {
        die("Erro: resposta inválida ou sem dados da API.");
    }
    $identidades = $dataApi['list'];

    // Filtra os registros com base no intervalo de datas
    // Compara as datas de postagem (campo 'POSTADO') com as datas informadas
    foreach ($identidades as $row) {
        if (!isset($row['POSTADO'])) continue;
        $dataPostadoObj = converterData($row['POSTADO']);
        // Obtém a data no formato "Y-m-d" para comparação
        $dataPostado = $dataPostadoObj->format('Y-m-d');
        if ($dataPostado >= $data_inicio && $dataPostado <= $data_fim) {
            // Agrupa por dia-mês (por exemplo "d-m")
            $diaMes = $dataPostadoObj->format('d-m');
            $categoria = $row['CATEGORIA'] ?? '';
            if (!isset($quantidade_por_dia[$diaMes])) {
                $quantidade_por_dia[$diaMes] = ['CD' => 0, 'Outras' => 0];
            }
            // Incrementa a contagem para "CD" ou "Outras"
            $quantidade_por_dia[$diaMes][($categoria === 'CD') ? 'CD' : 'Outras']++;
        }
    }
    // Ordena o array pelas chaves (datas)
    ksort($quantidade_por_dia);
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tituloConsulta) ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; }
        table { width: 50%; border-collapse: collapse; margin: 20px auto; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #f2f2f2; }
        .form-row { display: flex; flex-wrap: wrap; justify-content: center; }
        .form-group { margin: 10px; }
        .btn { padding: 8px 12px; background-color: #007bff; color: #fff; border: none; cursor: pointer; }
        .alert { padding: 15px; margin: 20px auto; width: 50%; }
        .alert-info { background-color: #e7f3fe; color: #31708f; }
        .alert-danger { background-color: #f2dede; color: #a94442; }
    </style>
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($tituloConsulta) ?></h1>
    <div class="col-md-6 offset-md-3 mb-4">
        <form action="" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="data_inicio">Data de Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>" required>
                </div>
                <div class="form-group">
                    <label for="data_fim">Data de Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Pesquisar</button>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($identidades)): ?>
        <div class="alert alert-info">
            <b>Período de pesquisa:</b> <?= htmlspecialchars($data_inicio) ?> a <?= htmlspecialchars($data_fim) ?>
        </div>
        <div class="row">
            <div class="col">
                <canvas id="grafico" style="max-width: 100%; max-height: 400px;"></canvas>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <b>Nenhum dado encontrado!</b>
        </div>
    <?php endif; ?>
</div>

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
                data: <?= json_encode(array_column($quantidade_por_dia, 'CD')) ?>
            }, {
                label: 'Outras',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                data: <?= json_encode(array_column($quantidade_por_dia, 'Outras')) ?>
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>
