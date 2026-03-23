<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

Session::CheckSession();
if (Session::get('grupo') != 0 && (isset($row['CI22acesso']) && $row['CI22acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-identidade'; // Redireciona para a página de identidade ou outra inicial
    </script>";
    exit;
}

// Conexão com o banco
require_once __DIR__ . '/../../config/config.php';

// Configurações otimizadas para grandes volumes
ini_set('memory_limit', '4096M'); // 4GB
ini_set('max_execution_time', 0); // Sem limite de tempo
ini_set('max_input_time', 600); // 10 minutos
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

// Verificar se há mensagem de sucesso na URL
if (isset($inputGet['success']) && $inputGet['success'] == 1) {
    $success_message = 'Registro inserido com sucesso!';
}

// Listar todos os registros e contagem por estado
try {
    $con = Database1::getInstance()->getConnection();
    $query = "SELECT * FROM carteirinhas_despachadas_cro ORDER BY id DESC";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $todos_despachos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar contagem por estado
    $query_count = "SELECT cro_uf, COUNT(*) as total FROM carteirinhas_despachadas_cro GROUP BY cro_uf ORDER BY cro_uf";
    $stmt_count = $con->prepare($query_count);
    $stmt_count->execute();
    $contagem_estados = $stmt_count->fetchAll(PDO::FETCH_ASSOC);
    
    // Total geral
    $total_geral = count($todos_despachos);
    
} catch (PDOException $error) {
    error_log("consultaIdentidade-22: Erro ao retornar os dados: " . $error->getMessage());
    $error_message = "Erro ao carregar os dados. Tente novamente mais tarde.";
    $todos_despachos = [];
    $contagem_estados = [];
    $total_geral = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Envios de Identidades ao CRO</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <style>
                body { font-family: Arial, sans-serif; }
        h1 { margin-bottom: 1rem; }
        h3 { margin-bottom: 1rem; }
        .alert { padding: 1rem; border-radius: 0.25rem; margin-bottom: 1rem; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-danger { background: #f8d7da; color: #842029; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        .stats-table { margin-bottom: 2rem; max-width: 100%; overflow-x: auto; }
        .stats-table th, .stats-table td { padding: 0.3rem 0.5rem; text-align: center; }
        .total-row { font-weight: bold; background: #e9ecef; }
        .btn { padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; text-decoration: none; display: inline-block; border: none; font-size: 1rem; }
        .btn-success { background: #28a745; color: white; border: 1px solid #28a745; }
        .btn-success:hover { background: #218838; }
        .table { width: 100%; margin-bottom: 1rem; color: #212529; }
        .table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
        .table tbody + tbody { border-top: 2px solid #dee2e6; }
        .table-sm th, .table-sm td { padding: 0.3rem; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.05); }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
        .table-responsive { margin-top: 20px; }
        .mr-1 { margin-right: 0.25rem !important; }
    </style>
    <script>
        $(document).ready(function() {
            $('#tabelaDespachos').DataTable({
                "paging": true,
                "pageLength": 25,
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "url": "../../assets/lang/pt-BR.json"
                },
                "order": [[4, "desc"]]
            });
        });
    </script>
</head>
<body>
<div class="container-fluid">
    <h1>Consulta de Despachos de Identidades ao CRO</h1>
    
    <!-- Mensagens de Sucesso/Erro -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <!-- Mini Tabela de Contagem por Estado -->
    <h3>Resumo por Estado</h3>
    <div class="stats-table">
        <table class="table table-sm table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <?php foreach ($contagem_estados as $estado): ?>
                        <th><?= htmlspecialchars($estado['cro_uf']) ?></th>
                    <?php endforeach; ?>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($contagem_estados as $estado): ?>
                        <td><?= number_format($estado['total'], 0, ',', '.') ?></td>
                    <?php endforeach; ?>
                    <td class="total-row"><?= number_format($total_geral, 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Botão de Download CSV -->
    <a href="/download-excel-streaming" class="btn btn-success" style="text-decoration: none;">
        📄 DOWNLOAD CSV - <?= number_format($total_geral, 0, ',', '.') ?> registros
    </a>
    
    <!-- Título e Tabela -->
    <h3>Todos os Envios (<?= number_format($total_geral, 0, ',', '.') ?> registros)</h3>
    <div class="table-responsive">
        <table id="tabelaDespachos" class="table table-sm table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>AR</th>
                    <th>CRO/UF</th>
                    <th>Inscrição</th>
                    <th>CPF</th>
                    <th>Data Despacho</th>
                    <th>Registrado em</th>
                    <th>Consta API</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($todos_despachos as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['ar'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['cro_uf']) ?></td>
                    <td><?= htmlspecialchars($row['inscricao']) ?></td>
                    <td><?= htmlspecialchars($row['cpf'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['data_despacho']) ?></td>
                    <td><?= htmlspecialchars($row['criado_em']) ?></td>
                    <td><?= htmlspecialchars($row['consta_api'] ? 'Sim' : 'Não') ?></td>
                    <td><?= htmlspecialchars($row['usuario_adicionou'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($row['ar'])): ?>
                        <a href="/consulta-identidade?tipoConsulta=1&searchType=ar&searchValue=<?= urlencode($row['ar']) ?>" target="_blank">Pesquisar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

