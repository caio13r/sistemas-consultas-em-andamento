<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CI1acesso']) && $row['CI1acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$token = $_ENV['API_TOKEN'] ?? null;
if (!$token) {
    error_log("Token da API não configurado.");
    echo "<div class='alert alert-danger'>Token da API não configurado.</div>";
    return;
}

$cro = $inputPost['cro'] ?? '';
$start_date_form = $inputPost['start_date'] ?? '';
$end_date_form = $inputPost['end_date'] ?? '';

// Definir um page_amount alto, agora que a API permite
$page_amount = 1000000; // Conforme a nova capacidade da API
$page_number = 1; // Sempre 1, pois buscamos tudo de uma vez

$error_message = ''; // Variável para armazenar mensagens de erro para o usuário

// Processar a submissão do formulário
if (isset($inputPost['submit_query'])) {
    // Validar datas para garantir que não estejam vazias
    if (empty($start_date_form) || empty($end_date_form)) {
        $error_message = 'Por favor, preencha a Data Inicial e a Data Final.';
    } else {
        // Converter datas para objetos DateTime para cálculo de diferença
        $start_datetime = new DateTime($start_date_form);
        $end_datetime = new DateTime($end_date_form);

        // Validar se a data inicial não é maior que a final
        if ($start_datetime > $end_datetime) {
            $error_message = 'A Data Inicial não pode ser maior que a Data Final.';
        } else {
            // Calcular a diferença em dias
            $interval = $start_datetime->diff($end_datetime);
            $days_diff = $interval->days;

            // Limitação de 62 dias
            $max_days = 62;
            if ($days_diff > $max_days) {
                $error_message = 'O período máximo permitido é de ' . $max_days . ' dias (aproximadamente 2 meses). Por favor, ajuste as datas.';
            }
        }
    }
}


// Só executa a chamada à API se não houver mensagens de erro e se o formulário foi submetido
if (empty($error_message) && isset($inputPost['submit_query'])) {
    // Convert date format to required API format (dd/mm/yyyy hh:mm:ss)
    $start_date_api = date('d/m/Y 00:00:00', strtotime($start_date_form));
    $end_date_api = date('d/m/Y 23:59:59', strtotime($end_date_form));

    $endpoint = 'http://192.168.161.165:8082/api/consulta/postagem/identidade';

    $apiUrl = "{$endpoint}?token=" . urlencode($token) .
        "&page_number=" . urlencode($page_number) .
        "&page_amount=" . urlencode($page_amount) .
        "&start_date=" . urlencode($start_date_api) .
        "&end_date=" . urlencode($end_date_api);
    
    // Adicionar CRO (antiga UF) apenas se não for "TODOS" e não estiver vazio
    if (!empty($cro) && $cro !== 'TODOS') {
        $apiUrl .= "&uf=" . urlencode($cro); // O parâmetro na API ainda é 'uf'
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error_message = "Erro CURL: " . curl_error($ch);
    } else {
        $data = json_decode($response, true);
        if (isset($data['list']) && is_array($data['list'])) {
            $list_results = $data['list']; // Assign the list for table display and Excel
            $total_results = $data['amount_result_total'] ?? count($list_results); // Use total from API or count fetched list
        } else {
            $error_message = "Erro na resposta da API: " . htmlspecialchars($data['message'] ?? 'Formato de resposta inválido ou erro interno.');
            $data = null; // Clear data if there was an API error or invalid response
        }
    }
    curl_close($ch);
}

// Título da consulta para o Excel (se houver)
$tituloConsulta = "Consulta de Postagem de Identidade - CRO: " . htmlspecialchars($cro) . " Período: " . htmlspecialchars(date('d/m/Y', strtotime($start_date_form))) . " a " . htmlspecialchars(date('d/m/Y', strtotime($end_date_form)));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta Postagem de Identidade</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <style>
        body { font-family: Arial, sans-serif; }
        .col-md-6.offset-md-3 { /* Estilo do seu exemplo para centralizar o formulário */
            margin-left: auto;
            margin-right: auto;
            max-width: 50%; /* Adjust as needed */
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
        .alert-danger-custom { /* Novo estilo para a mensagem de erro */
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
            // A tabela só será inicializada se houver dados
            <?php if (!empty($list_results)): ?>
            $('#tabelaIdentidade').DataTable({
                "paging": true,
                "pageLength": 50, // Quantidade de registros por página padrão do DataTables
                "lengthMenu": [10, 25, 50, 100], // Opções de quantidade de registros
                "language": {
                    "url": "../assets/lang/pt-BR.json" // Verifique se o caminho para o arquivo de idioma está correto
                },
                "order": [] // Desativa a ordenação inicial, caso você queira que os dados apareçam na ordem da API
            });
            <?php endif; ?>
        });
    </script>
</head>
<body>

<div class="container-fluid">
    <h1 class="mt-4">Consulta de Postagem de Identidade</h1>

    <?php if (!empty($error_message)): ?>
        <div class="alert-danger-custom">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($inputPost['submit_query']) && !empty($cro) && empty($error_message) && !empty($list_results)): ?>
        <div class="total-results">
            Total de registros para **CRO: <?= htmlspecialchars($cro) ?>** e **Período: <?= htmlspecialchars(date('d/m/Y', strtotime($start_date_form))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($end_date_form))) ?>**: **<?= number_format($total_results, 0, ',', '.') ?>**
        </div>
    <?php elseif (isset($inputPost['submit_query']) && empty($list_results) && empty($error_message)): ?>
        <div class='alert alert-info'>Nenhum resultado encontrado para os critérios informados.</div>
    <?php endif; ?>

    <div class="col-md-6 offset-md-3 mb-4">
        
        <form action="" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cro">CRO:</label> <select name="cro" id="cro" class="form-control"> <option value="">Selecione um CRO</option> <option value="TODOS" <?= ($cro == 'TODOS' ? ' selected' : '') ?>>Todos</option>
                        <?php
                        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                        foreach ($ufs as $sigla) {
                            echo "<option value='$sigla'" . ($cro == $sigla ? ' selected' : '') . ">$sigla</option>";
                        }
                        ?>
                    </select>
                </div>
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
            </div>
            <button type="submit" name="submit_query" class="btn btn-primary">Consultar</button>
        </form>
    </div>

    <?php if (!empty($list_results) && empty($error_message)): // Mostrar tabela e botão de Excel apenas se houver resultados e sem erros ?>
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
                            <th scope="col">Categoria</th>
                            <th scope="col">Inscrição</th>
                            <th scope="col">Nome</th>
                            <th scope="col">CPF</th>
                            <th scope="col">Data do Evento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list_results as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['id_professional']) ?></td>
                                <td><?= htmlspecialchars($item['categoria']) ?></td>
                                <td><?= htmlspecialchars($item['inscricao']) ?></td>
                                <td><?= htmlspecialchars($item['nome']) ?></td>
                                <td><?= htmlspecialchars($item['cpf']) ?></td>
                                <td><?= htmlspecialchars($item['data_evento'] ?? 'N/A') ?></td>
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