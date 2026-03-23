<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();
if (Session::get('grupo') != 0 && (isset($row['CI19acesso']) && $row['CI19acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-identidade'; // Redireciona para a página de identidade ou outra inicial
    </script>";
    exit;
}


$token = $_ENV['API_TOKEN'] ?? null;
if (!$token) {
    error_log("consultaIdentidade-19: Token da API não configurado.");
    echo "<div class='alert alert-danger'>Erro interno: configuração da API indisponível. Contate o administrador.</div>";
    return;
}

// Inicializa variáveis do formulário
$uf_form = $inputPost['uf'] ?? '';
$error_message = '';
$is_form_submitted = isset($inputPost['submit_query']);

$page_amount = 1000000;
$page_number = 1;
$list_results = [];
$total_results = 0;

if ($is_form_submitted) {
    $start_date_api = '01/01/2024 00:00:00';
    $end_date_api = date('d/m/Y 23:59:59');
    $endpoint = 'http://192.168.161.165:8082/api/consulta/perda';
    $params = [
        'token' => $token,
        'uf' => $uf_form,
        'page_number' => $page_number,
        'page_amount' => $page_amount,
        'start_date' => $start_date_api,
        'end_date' => $end_date_api
    ];
    // Remove params vazios
    $params = array_filter($params, function($v) { return $v !== '' && $v !== null; });
    $apiUrl = $endpoint . '?' . http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_message = "Erro CURL: " . curl_error($ch);
        curl_close($ch);
    } else {
        curl_close($ch);
        $data = json_decode($response, true);
        if (isset($data['list']) && is_array($data['list'])) {
            $list_results = $data['list'];
            $total_results = $data['amount_result_total'] ?? count($list_results);
        } else {
            $error_message = "Erro na resposta da API: " . htmlspecialchars($data['message'] ?? 'Formato de resposta inválido ou erro interno.');
            $data = null;
        }
    }
}

$tituloConsulta = "Consulta de Identidades Perdidas - Período: 01/01/2024 a " . htmlspecialchars(date('d/m/Y'));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Identidades Perdidas</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <style>
        body { font-family: Arial, sans-serif; }
        .col-md-6.offset-md-3 { margin-left: auto; margin-right: auto; max-width: 50%; }
        .card { margin-top: 20px; }
        .table-responsive { margin-top: 20px; }
        .table { width: 100%; margin-bottom: 1rem; color: #212529; }
        .table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
        .table-sm th, .table-sm td { padding: 0.3rem; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.05); }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
        .form-group { margin-bottom: 1rem; }
        .btn-primary { color: #fff; background-color: #0d6efd; border-color: #0d6efd; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; }
        .btn-primary:hover { background-color: #0b5ed7; border-color: #0a58ca; }
        .total-results { margin-top: 15px; font-size: 1.1em; font-weight: bold; color: #333; }
        .btn-success { color: #fff; background-color: #28a745; border-color: #28a745; padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; }
        .btn-success:hover { background-color: #218838; border-color: #1e7e34; }
        .mr-1 { margin-right: 0.25rem !important; }
        .alert-danger-custom { color: #842029; background-color: #f8d7da; border-color: #f5c2c7; padding: 1rem 1rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.25rem; }
    </style>
    <script>
        $(document).ready(function() {
            <?php if (!empty($list_results)): ?>
            $('#tabelaIdentidade').DataTable({
                "paging": true,
                "pageLength": 50,
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "url": "../assets/lang/pt-BR.json",
                    "info": "Mostrando de _START_ até _END_ de <?= number_format($total_results, 0, ',', '.') ?> registros"
                },
                "order": []
            });
            <?php endif; ?>
        });
    </script>
</head>
<body>
<div class="container-fluid">
    <h1 class="mt-4">Consulta de Identidades Perdidas</h1>
    <?php if (!empty($error_message)): ?>
        <div class="alert-danger-custom">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    <div class="col-md-6 offset-md-3 mb-4">
        <form action="" method="post">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="uf">CRO:</label>
                    <select name="uf" id="uf" class="form-control">
                        <option value=""<?= ($uf_form == '' || $uf_form == 'Todos') ? ' selected' : '' ?>>Todos</option>
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
            </div>
            <button type="submit" name="submit_query" class="btn btn-primary">Consultar</button>
        </form>
    </div>
    <?php if ($is_form_submitted && empty($error_message) && !empty($list_results)): ?>
        <div class="total-results">
            Total de registros para <b>UF: <?= htmlspecialchars($uf_form ?: 'Todos') ?></b> e <b>Período: 01/01/2024 a <?= htmlspecialchars(date('d/m/Y')) ?></b>: <b><?= number_format($total_results, 0, ',', '.') ?></b>
        </div>
    <?php elseif ($is_form_submitted && empty($list_results) && empty($error_message)): ?>
        <div class='alert alert-info'>Nenhum resultado encontrado para os critérios informados.</div>
    <?php endif; ?>
    <?php if (!empty($list_results) && empty($error_message)): ?>
        <div class="row justify-content-end mr-1">
            <form action="ExcelDownload" method="post">
                <input type="hidden" name="tituloConsulta" value="<?= htmlspecialchars($tituloConsulta) ?>">
                <input type="hidden" name="dadosConsulta" value='<?= htmlspecialchars(json_encode($list_results)); ?>'>
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
                                <td><?= htmlspecialchars($item['id_professional'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['uf'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['categoria'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['inscricao'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['nome'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['cpf'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['data_evento'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['detalhe'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html> 