<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CI21acesso']) && $row['CI21acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-identidade'; // Redireciona para a página de identidade ou outra inicial
    </script>";
    exit;
}


$token = $_ENV['API_TOKEN'] ?? null;
if (!$token) {
    die("Token da API não configurado.");
}

// Inicializa variáveis para os campos do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date_form = $_POST['start_date'] ?? '';
    $end_date_form = $_POST['end_date'] ?? '';
} else {
    // Se não veio do POST, define padrão: hoje e um mês atrás
    $end_date_form = date('Y-m-d');
    $start_date_form = date('Y-m-d', strtotime('-1 month'));
}
$uf_form = $_POST['uf'] ?? 'BRASIL'; // Valor padrão agora é BRASIL (todos)

// Variáveis para mensagens de erro ou status
$error_message = '';
$is_form_submitted = isset($_POST['submit_query']);

// Definir um page_amount alto (para quando a API estiver pronta)
$page_amount = 100000; // Aumentado para buscar mais registros
$page_number = 1;

$list_results = []; // Para armazenar os resultados da API (ou dados de prévia)
$total_results = 0; // Para armazenar o total de resultados

// Ambiente
$is_production = strpos($_SERVER['HTTP_HOST'] ?? '', 'cfo.org.br') !== false;
if ($is_production) {
    $API_BASE_URL = 'https://192.168.161.165:8082'; // HTTPS para produção
} else {
    $API_BASE_URL = 'http://192.168.161.165:8082'; // HTTP para desenvolvimento
}
$endpoint = $API_BASE_URL . '/api/consulta/retornada';

// --- Lógica de Validação e Chamada à API (Para quando o endpoint estiver disponível) ---
if ($is_form_submitted) {
    // Validar datas para garantir que não estejam vazias
    if (empty($start_date_form) || empty($end_date_form)) {
        $error_message = 'Por favor, preencha a Data Inicial e a Data Final.';
    } else {
        // Ajustar as datas para incluir o início e o fim do dia
        // Usando o formato correto (dd/mm/yyyy HH:mm:ss)
        $start_date_api = date('d/m/Y 00:00:00', strtotime($start_date_form));
        $end_date_api = date('d/m/Y 23:59:59', strtotime($end_date_form));

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

            // Limitação de 100 dias
            $max_days = 10000;
            if ($days_diff > $max_days) {
                $error_message = 'O período máximo permitido é de ' . $max_days . ' dias. Por favor, ajuste as datas.';
            }
        }
    }

    // Se não houver erros de validação, fazer a construção da URL de requisição
    if (empty($error_message)) {
        $endpoint = 'http://192.168.161.165:8082/api/consulta/retornada';
        
        // Construir URL base sem UF
        $apiUrl = sprintf('%s?token=%s&page_number=%s&page_amount=%s&start_date=%s&end_date=%s',
            $endpoint,
            urlencode($token),
            urlencode($page_number),
            urlencode($page_amount),
            str_replace(' ', '%20', $start_date_api),
            str_replace(' ', '%20', $end_date_api)
        );
        
        // Adicionar UF apenas se não for "BRASIL" (todos)
        if ($uf_form !== 'BRASIL') {
            $apiUrl .= '&uf=' . urlencode($uf_form);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos de timeout
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
}

// Título para o Excel
$tituloConsulta = "Consulta de Identidades Retornadas ao CFO por Período - Período: " . htmlspecialchars(date('d/m/Y', strtotime($start_date_form))) . " a " . htmlspecialchars(date('d/m/Y', strtotime($end_date_form)));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Identidades Retornadas ao CFO</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <style>
        body { font-family: Arial, sans-serif; }
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

        /* Estilo para a mensagem de implementação */
        .implementation-alert {
            background-color: #fff3cd;
            color: #664d03;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ffecb5;
            border-radius: 0.3rem;
            font-size: 1.1em;
            font-weight: bold;
            text-align: center;
        }
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
    <h1 class="mt-4">Consulta de Identidades Retornadas ao CFO por Período</h1>

    <?php if (!empty($error_message)): ?>
        <div class="alert-danger-custom">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="col-md-6 offset-md-3 mb-4">
        <form action="" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="uf">UF:</label>
                    <select name="uf" id="uf" class="form-control">
                        <option value="BRASIL" <?= ($uf_form == 'BRASIL' ? ' selected' : '') ?>>Brasil (Todos)</option>
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

    <?php if ($is_form_submitted && empty($error_message) && !empty($list_results)): ?>
        <div class="total-results">
            Total de registros para **UF: <?= htmlspecialchars($uf_form == 'BRASIL' ? 'Brasil (Todos)' : $uf_form) ?>** e **Período: <?= htmlspecialchars(date('d/m/Y', strtotime($start_date_form))) ?> a <?= htmlspecialchars(date('d/m/Y', strtotime($end_date_form))) ?>**: **<?= number_format($total_results, 0, ',', '.') ?>**
        </div>
    <?php elseif ($is_form_submitted && empty($list_results) && empty($error_message)): ?>
        <div class='alert alert-info'>Nenhum resultado encontrado para os critérios informados.</div>
    <?php endif; ?>

    <?php if (!empty($list_results) && empty($error_message)): ?>
        <div class="row justify-content-end mr-1">
            <!-- Download CSV Streaming -->
            <a href="/download-identidades-retornadas-csv?start_date=<?= urlencode($start_date_form) ?>&end_date=<?= urlencode($end_date_form) ?>&uf=<?= urlencode($uf_form) ?>" 
               class="btn btn-md btn-success" style="margin-right: 10px; text-decoration: none;">
                📄 Download CSV (<?= number_format($total_results, 0, ',', '.') ?> registros)
            </a>
            
            <!-- Download Excel -->
            <form action="ExcelDownload" method="post" style="display: inline;">
                <input type="hidden" name="tituloConsulta" value="<?= htmlspecialchars($tituloConsulta) ?>">
                <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($list_results)); ?>">
                <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">📊 Excel</button>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list_results as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['id_professional']) ?></td>
                                <td><?= htmlspecialchars($item['uf'] ?? 'N/A') ?></td>
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