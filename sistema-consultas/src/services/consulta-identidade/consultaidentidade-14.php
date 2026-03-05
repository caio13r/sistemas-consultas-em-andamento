<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
Session::CheckSession();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

if (Session::get('grupo') != 0 && $row['CI1acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$token = $_ENV['API_TOKEN'] ?? null;
if (!$token) {
    die("Token da API não configurado.");
}

$start_date = '';
$end_date = '';
$start_display = '';
$end_display = '';
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (isset($_POST['start_date'])) {
        $start_display = $_POST['start_date'];
        $start_date = date('d/m/Y 00:00:00', strtotime($_POST['start_date']));
    }
    if (isset($_POST['end_date'])) {
        $end_display = $_POST['end_date'];
        $end_date = date('d/m/Y 23:59:59', strtotime($_POST['end_date']));
    }

    $endpoint = 'http://192.168.161.165:8082/api/consulta/postagem/estatistica/total';
    $apiUrl = "{$endpoint}?token=" . urlencode($token) .
        "&start_date=" . urlencode($start_date) .
        "&end_date=" . urlencode($end_date);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta Estatística Postagem --</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
     
        .chart-container { height: 400px; width: 100%; margin-top: 20px; }
        .total-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }
        .total-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #0d6efd;
        }
        .form-group { margin-bottom: 1rem; }
        .btn { padding: 0.5rem 1.25rem; }
        .d-flex.gap-2 { display: flex; gap: 10px; }
        .table-responsive { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        table thead {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>

    <script>
        function setDefaultDates() {
            const now = new Date();
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(now.getMonth() - 1);
            const formatDate = d => d.toISOString().slice(0, 10);
            document.getElementById('start_date').value = formatDate(oneMonthAgo);
            document.getElementById('end_date').value = formatDate(now);
        }
        window.onload = setDefaultDates;
    </script>
</head>
<body>

<div class="container-fluid">
    <h1>Estatísticas de Identidade</h1>

    <div class="card mt-4">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="start_date">Data Inicial:</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="end_date">Data Final:</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group mt-3 d-flex gap-2">
                    <button type="submit" name="submit" class="btn btn-primary">Consultar</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($data['estatistico'])): ?>
        <?php
            $total = array_sum(array_column($data['estatistico'], 'total'));
            usort($data['estatistico'], fn($a, $b) => $b['total'] - $a['total']);
            $data['estatistico'][] = ['uf' => 'BRASIL', 'total' => $total];
        ?>

        <!-- Botão de Excel -->
        <div class="row justify-content-end my-2">
            <div class="col-auto">
                <form id="excelForm" method="POST" action="ExcelDownload">
                    <input type="hidden" name="tituloConsulta" value="Total de Postagens por UF">
                    <input type="hidden" name="dadosConsulta" value='<?= htmlspecialchars(json_encode($data['estatistico'], JSON_UNESCAPED_UNICODE)); ?>'>
                    <button type="submit" name="ExcelDownload" class="btn btn-success">Exportar Excel</button>
                </form>
            </div>
        </div>

        <!-- Card com total -->
        <div class='total-card'>
            <h3>Total de Postagens no Período (<?= date('d/m/Y', strtotime($start_display)) ?> a <?= date('d/m/Y', strtotime($end_display)) ?>)</h3>
            <div class='total-number'><?= number_format($total, 0, ',', '.') ?></div>
        </div>

        <!-- Tabela -->
        <div class='table-responsive mt-4'>
            <table class='table table-sm table-bordered table-striped table-hover'>
                <thead>
                    <tr>
                        <th>CRO</th>
                        <th>Total</th>
                        <th>Percentual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['estatistico'] as $item): 
                        $percent = $item['uf'] === 'BRASIL' ? 100 : ($item['total'] / $total) * 100;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($item['uf']) ?></td>
                            <td><?= number_format($item['total'], 0, ',', '.') ?></td>
                            <td><?= number_format($percent, 2, ',', '.') ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Gráfico -->
        <div class='chart-container'>
            <canvas id='statisticsChart'></canvas>
        </div>

        <script>
            const ctx = document.getElementById('statisticsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($data['estatistico'], 'uf')) ?>,
                    datasets: [{
                        label: 'Total de Postagens por CRO',
                        data: <?= json_encode(array_column($data['estatistico'], 'total')) ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.5)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });
        </script>
    <?php endif; ?>
</div>

</body>
</html>
