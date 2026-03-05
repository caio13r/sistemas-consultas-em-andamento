<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
Session::CheckSession();

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

// Process POST data
$uf = $_POST['uf'] ?? '';
$page_number = $_POST['page_number'] ?? 1;
$page_amount = $_POST['page_amount'] ?? 100;

// Convert datetime-local format to required format (dd/mm/yyyy hh:mm:ss)
$start_date = '';
$end_date = '';
if (isset($_POST['start_date'])) {
    $start_date = date('d/m/Y H:i:s', strtotime($_POST['start_date']));
}
if (isset($_POST['end_date'])) {
    $end_date = date('d/m/Y H:i:s', strtotime($_POST['end_date']));
}

$endpoint = 'https://id.cfo.org.br/api/consulta/postagem/identidade';

// Only build API URL if we have a UF selected
if (!empty($uf)) {
    $apiUrl = "{$endpoint}?token=" . urlencode($token) .
        "&uf=" . urlencode($uf) .
        "&page_number=" . urlencode($page_number) .
        "&page_amount=" . urlencode($page_amount) .
        "&start_date=" . urlencode($start_date) .
        "&end_date=" . urlencode($end_date);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta Postagem de Identidade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; }
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
        .datetime-group input { flex: 1; }
        .btn-primary { 
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
    </style>
    <script>
        function formatDateTime(date) {
            const d = new Date(date);
            const pad = (num) => String(num).padStart(2, '0');
            return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
        }

        function setDefaultDates() {
            const now = new Date();
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(now.getMonth() - 1);
            
            document.getElementById('start_date').value = oneMonthAgo.toISOString().slice(0, 16);
            document.getElementById('end_date').value = now.toISOString().slice(0, 16);
        }

        window.onload = setDefaultDates;
    </script>
</head>
<body>

<div class="container-fluid">
    <h1 class="mt-4">Consulta de Postagem de Identidade</h1>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="uf">UF:</label>
                        <select name="uf" id="uf" class="form-control" required>
                            <option value="">Selecione uma UF</option>
                            <?php
                            $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                                    'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                                    'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                            foreach ($ufs as $sigla) {
                                echo "<option value='$sigla'" . ($uf == $sigla ? ' selected' : '') . ">$sigla</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Período:</label>
                        <div class="datetime-group">
                            <div>
                                <label for="start_date">Data Inicial:</label>
                                <input type="datetime-local" name="start_date" id="start_date" class="form-control" required>
                            </div>
                            <div>
                                <label for="end_date">Data Final:</label>
                                <input type="datetime-local" name="end_date" id="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="page_amount">Quantidade por Página:</label>
                        <input type="number" name="page_amount" id="page_amount" class="form-control" value="<?= htmlspecialchars($page_amount) ?>" min="1" max="1000" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="page_number">Número da Página:</label>
                        <input type="number" name="page_number" id="page_number" class="form-control" value="<?= htmlspecialchars($page_number) ?>" min="1" required>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Consultar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    if (!empty($uf) && isset($apiUrl)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            echo "<div class='alert alert-danger'>Erro CURL: " . curl_error($ch) . "</div>";
            curl_close($ch);
            exit;
        }
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['list']) && is_array($data['list']) && count($data['list']) > 0) {
            echo "<div class='table-responsive'><table class='table table-sm table-bordered table-striped table-hover'>";
            echo "<thead><tr>
                <th>ID Profissional</th>
                <th>Categoria</th>
                <th>Inscrição</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Data do Evento</th>
            </tr></thead><tbody>";
            
            foreach ($data['list'] as $item) {
                echo "<tr>
                    <td>" . htmlspecialchars($item['id_professional']) . "</td>
                    <td>" . htmlspecialchars($item['categoria']) . "</td>
                    <td>" . htmlspecialchars($item['inscricao']) . "</td>
                    <td>" . htmlspecialchars($item['nome']) . "</td>
                    <td>" . htmlspecialchars($item['cpf']) . "</td>
                    <td>" . htmlspecialchars($item['data_evento'] ?? 'N/A') . "</td>
                </tr>";
            }
            echo "</tbody></table></div>";
        } else {
            echo "<div class='alert alert-warning'>Nenhum resultado encontrado.</div>";
        }
    }
    ?>
</div>

</body>
</html>
