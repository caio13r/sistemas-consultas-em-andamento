<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();

// Verificação de permissão. Ajuste 'CI_DESCARTE_PERIODO_acesso' conforme necessário.
if (Session::get('grupo') != 0 && (isset($row['CI13acesso']) && $row['CI13acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-identidade'; // Redireciona para a página de identidade ou outra inicial
    </script>";
    exit;
}

$token = $_ENV['API_TOKEN'] ?? null;
if (!$token) {
    error_log("Token da API não configurado.");
    echo "<div class='alert alert-danger'>Token da API não configurado.</div>";
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uf_form = $inputPost['uf'] ?? '';
} else {
    $uf_form = '';
}

$error_message = '';
$is_form_submitted = isset($inputPost['submit_query']);

// Definir um page_amount alto (para quando a API estiver pronta)
$page_amount = 1000000; // Máximo permitido pela API
$page_number = 1;

$list_results = []; // Para armazenar os resultados da API
$total_results = 0; // Para armazenar o total de resultados

// --- Lógica de Validação e Exibição ---
if ($is_form_submitted) {
    // Sempre buscar de 01/12/2023 até hoje
    $start_date_form = '2023-12-01';
    $end_date_form = date('Y-m-d');
    // Se não houver erros de validação, fazer a chamada à API
    if (empty($error_message)) {
        // Ajustar as datas para incluir o início e o fim do dia
        $start_date_api = '01/12/2023 00:00:00';
        $end_date_api = date('d/m/Y 23:59:59');
        $endpoint = 'http://192.168.161.165:8082/api/consulta/descartada';
        $apiUrl = $endpoint .
            '?token=' . urlencode($token) .
            (!empty($uf_form) ? '&uf=' . urlencode($uf_form) : '') .
            '&page_number=' . urlencode($page_number) .
            '&page_amount=' . urlencode($page_amount) .
            '&start_date=' . urlencode($start_date_api) .
            '&end_date=' . urlencode($end_date_api);

        // Exibir a URL gerada para facilitar o teste no Postman
        // echo "<div style='margin: 10px 0; padding: 10px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;'>";
        // echo "<strong>URL gerada para teste no Postman:</strong><br>";
        // echo "<a href='" . htmlspecialchars($apiUrl) . "' target='_blank'>" . htmlspecialchars($apiUrl) . "</a>";
        // echo "</div>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Adicionar timeout de 30 segundos
        $response = curl_exec($ch);

        // Exibir o JSON bruto retornado da API para debug
        // echo '<details style="margin: 16px 0;"><summary style="cursor:pointer;font-weight:bold;">Ver dados brutos retornados da API</summary>';
        // echo '<pre style="background:#f8f9fa;border:1px solid #ccc;padding:10px;max-height:400px;overflow:auto;">' . htmlspecialchars($response) . '</pre>';
        // echo '</details>';

        if (curl_errno($ch)) {
            $error_message = "Erro CURL: " . curl_error($ch);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $data = json_decode($response, true);

            if ($httpCode == 200 && isset($data['list']) && is_array($data['list'])) {
                $list_results = $data['list'];
                $total_results = count($list_results);
            } else if ($httpCode == 200) {
                $error_message = "Formato de resposta inválido da API: " . $response;
            } else if ($httpCode == 400) {
                $error_message = "Requisição inválida: " . ($data['message'] ?? 'Parâmetros incorretos ou ausentes');
            } else if ($httpCode == 401) {
                $error_message = "Não autorizado: Token inválido ou ausente";
            } else if ($httpCode == 404) {
                $error_message = "Nenhum resultado encontrado para os critérios informados";
            } else if ($httpCode == 500) {
                $error_message = "Erro interno no servidor";
            } else {
                $error_message = "Erro desconhecido na API (HTTP Code: " . $httpCode . ")";
            }
        }
        curl_close($ch);
    }
}

// Título para o Excel
$tituloConsulta = "Consulta de Descarte : " . htmlspecialchars(date('d/m/Y', strtotime($start_date_form))) . " a " . htmlspecialchars(date('d/m/Y', strtotime($end_date_form)));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Identidades Descartadas Antes da Fila de Impressão </title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <style>
        body { font-family: Arial, sans-serif; }
        .container-fluid {
            padding: 20px;
        }
        .col-md-6.offset-md-3 {
            margin-left: auto;
            margin-right: auto;
            max-width: 50%;
        }
        h6 { margin-bottom: 1rem; }
        .card { margin-top: 20px; }
        .table-responsive { margin-top: 20px; }
        .table { width: 100%; margin-bottom: 1rem; color: #212529; }
        .table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
        .table tbody + tbody { border-top: 2px solid #dee2e6; }
        .table-sm th, .table-sm td { padding: 0.3rem; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.05); }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
        .form-group { margin-bottom: 1rem; }
        .datetime-group { display: flex; gap: 10px; }
        .datetime-group input { flex: 1; max-width: 180px; }
        .btn-primary {
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .total-results {
            margin-top: 15px;
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
        }
        .btn-success {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .mr-1 { margin-right: 0.25rem !important; }
        .alert-danger-custom {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
            padding: 1rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
    </style>
    <script>
        function setDefaultDates() {
            const now = new Date();
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(now.getMonth() - 1);

            if (!document.getElementById('start_date').value) {
                document.getElementById('start_date').value = oneMonthAgo.toISOString().slice(0, 10);
            }
            if (!document.getElementById('end_date').value) {
                document.getElementById('end_date').value = now.toISOString().slice(0, 10);
            }
        }

        window.onload = setDefaultDates;

        $(document).ready(function() {
            <?php if (!empty($list_results)): ?>
            $('#tabelaIdentidade').DataTable({
                "paging": true,
                "pageLength": 50,
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "url": "../assets/lang/pt-BR.json"
                },
                "order": []
            });
            <?php endif; ?>
        });
    </script>
</head>
<body>

<div class="container-fluid">
    <h1 class="mt-4">Consulta de Identidade Descartadas Antes da Fila de Impressão </h1>

    <div class="col-md-86 offset-md-3 mb-4">
        <form action="" method="post" data-gtm-form-interact-id="0">
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label for="uf">Selecione o CRO:</label>
                    <select name="uf" id="uf" class="form-control">
                        <option value=""<?= $uf_form == '' ? ' selected' : '' ?>>Todos</option>
                        <?php
                        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                        foreach ($ufs as $sigla) {
                            echo "<option value='$sigla'" . ($uf_form == $sigla ? ' selected' : '') . ">$sigla</option>";
                        }
                        ?>
                    </select>
                </div>
                <!--
                <div class="form-group col-md-6">
                    <div class="datetime-group">
                        <div>
                            <label for="start_date">Data Inicial:</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date_form) ?>" required>
                        </div>
                        <div>
                            <label for="end_date">Data Final:</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date_form) ?>" required>
                        </div>
                    </div>
                </div>
                -->
            </div>
            <button type="submit" name="submit_query" class="btn btn-primary">Consultar</button>
        </form>
    </div>
</div>

    <?php if (!empty($list_results) && empty($error_message)): ?>
        <div class="total-results">
            Total de registros para **Período: <?= htmlspecialchars(date('d/m/Y', strtotime($start_date_form))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($end_date_form))) ?>**: **<?= number_format($total_results, 0, ',', '.') ?>**
        </div>

        <div class="row justify-content-end mr-1">
            <form action="ExcelDownload" method="post">
                <input type="hidden" name="tituloConsulta" value="<?= htmlspecialchars($tituloConsulta) ?>">
                <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($list_results)); ?>">
                <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
            </form>
        </div>

        <div class="row mt-4">
            <div class="col table-responsive">
                <table id="tabelaIdentidade" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                    <thead>
                        <tr>
                            <th scope="col">ID Profissional</th>
                            <th scope="col">CRO (UF)</th>
                            <th scope="col">Categoria</th>
                            <th scope="col">Inscrição</th>
                            <th scope="col">Nome</th>
                            <th scope="col">CPF</th>
                            <th scope="col">Data do Evento</th>
                            <th scope="col">Detalhe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list_results as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['id_professional'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['uf'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['categoria'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['inscricao'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['nome'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['cpf'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['data_evento'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['detalhe'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif (isset($inputPost['submit_query']) && empty($list_results) && empty($error_message)): ?>
        <div class='alert alert-info'>Nenhum resultado encontrado para os critérios informados.</div>
    <?php endif; ?>

</div>

</body>
</html>