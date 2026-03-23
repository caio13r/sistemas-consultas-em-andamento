<?php
// Habilitar exibição de erros para debug (temporário)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

Session::CheckSession();

// Debug: verificar se chegou até aqui
error_log("DEBUG CI23: Iniciando verificações - Grupo: " . Session::get('grupo'));

// Carrega dados de acesso do usuário para verificação de permissão
$row = [];
if (Session::get('grupo') != 0) {
    try {
        $grupo = Session::get('grupo');
        $subgrupo = Session::get('subgrupo');
        $db = Database1::getInstance();
        $con = $db->getConnection();
        $query = "SELECT * FROM tbl_acessos WHERE grupo = :grupo AND subgrupo = :subgrupo";
        $stmt = $con->prepare($query);
        $stmt->bindParam(':grupo', $grupo);
        $stmt->bindParam(':subgrupo', $subgrupo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $row = [];
        }
    } catch (PDOException $error) {
        $row = [];
        error_log("DEBUG CI23: Erro ao carregar permissões: " . $error->getMessage());
    }
}

// Debug: verificar dados carregados
error_log("DEBUG CI23: Dados carregados - Row: " . print_r($row, true));
error_log("DEBUG CI23: CI23acesso = " . (isset($row['CI23acesso']) ? $row['CI23acesso'] : 'não_existe'));

// Verificação de permissão
if (Session::get('grupo') != 0) {
    if (!isset($row['CI23acesso']) || $row['CI23acesso'] == false) {
        echo "<script language='javascript'>
        window.alert('Você não tem permissão para acessar essa página.');
        window.location.href='/consulta-identidade'; // Redireciona para a página de identidade
        </script>";
        exit;
    }
}

// Debug: passou pela verificação de permissão
error_log("DEBUG CI23: Passou pela verificação de permissão com sucesso");

// Teste de saída - se você ver essa mensagem, o problema está depois
echo "<!-- DEBUG: Script funcionando até aqui -->\n";

// Conexão com o banco
require_once __DIR__ . '/../../config/config.php';

// Configurações otimizadas para grandes volumes
ini_set('memory_limit', '2048M'); // 2GB
ini_set('max_execution_time', 300); // 5 minutos
ini_set('max_input_time', 300); // 5 minutos
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

// Debug: configurações aplicadas
echo "<!-- DEBUG: Configurações aplicadas -->\n";

// Verificar se há mensagem de sucesso na URL
if (isset($inputGet['success']) && $inputGet['success'] == 1) {
    $success_message = 'Consulta realizada com sucesso!';
}

// Parâmetros de filtro
$data_inicial = $inputGet['data_inicial'] ?? '';
$data_final = $inputGet['data_final'] ?? '';
$cro_uf = $inputGet['cro_uf'] ?? '';

// Se não há filtros, definir período padrão dos últimos 30 dias
if (empty($data_inicial) && empty($data_final)) {
    $data_final = date('Y-m-d');
    $data_inicial = date('Y-m-d', strtotime('-30 days'));
}

// Validação de datas
if (!empty($data_inicial) && !empty($data_final)) {
    if (strtotime($data_inicial) > strtotime($data_final)) {
        $error_message = 'A data inicial não pode ser maior que a data final.';
        $data_inicial = '';
        $data_final = '';
    }
}

$todos_despachos = [];
$contagem_estados = [];
$total_geral = 0;

// Buscar dados apenas se há filtros válidos
if (!empty($data_inicial) && !empty($data_final)) {
    try {
        $con = Database1::getInstance()->getConnection();
        
        // Verificar se a tabela existe
        $check_table = "SELECT 1 FROM carteirinhas_despachadas_cro LIMIT 1";
        $stmt_check = $con->prepare($check_table);
        $stmt_check->execute();
        
        // Construir query com filtros
        $where_conditions = [];
        $params = [];
        
        // Filtro por período (data_despacho)
        $where_conditions[] = "data_despacho BETWEEN :data_inicial AND :data_final";
        $params[':data_inicial'] = $data_inicial;
        $params[':data_final'] = $data_final;
        
        // Filtro por UF se selecionado
        if (!empty($cro_uf)) {
            $where_conditions[] = "cro_uf = :cro_uf";
            $params[':cro_uf'] = $cro_uf;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Query principal
        $query = "SELECT * FROM carteirinhas_despachadas_cro WHERE $where_clause ORDER BY id DESC LIMIT 3000";
        $stmt = $con->prepare($query);
        $stmt->execute($params);
        $todos_despachos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar contagem por estado no período
        $query_count = "SELECT cro_uf, COUNT(*) as total FROM carteirinhas_despachadas_cro WHERE $where_clause GROUP BY cro_uf ORDER BY cro_uf";
        $stmt_count = $con->prepare($query_count);
        $stmt_count->execute($params);
        $contagem_estados = $stmt_count->fetchAll(PDO::FETCH_ASSOC);
        
        // Total geral no período
        $query_total = "SELECT COUNT(*) as total FROM carteirinhas_despachadas_cro WHERE $where_clause";
        $stmt_total = $con->prepare($query_total);
        $stmt_total->execute($params);
        $total_result = $stmt_total->fetch(PDO::FETCH_ASSOC);
        $total_geral = $total_result['total'];
        
        // Buscar todos os estados para o select
        $query_ufs = "SELECT DISTINCT cro_uf FROM carteirinhas_despachadas_cro ORDER BY cro_uf";
        $stmt_ufs = $con->prepare($query_ufs);
        $stmt_ufs->execute();
        $estados_disponiveis = $stmt_ufs->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $error) {
        $error_message = "Erro ao retornar os dados: " . $error->getMessage();
        error_log("ERRO CONSULTA CI23: " . $error->getMessage());
    } catch (Exception $error) {
        $error_message = "Erro geral: " . $error->getMessage();
        error_log("ERRO GERAL CI23: " . $error->getMessage());
    }
} else {
    // Buscar apenas os estados disponíveis para o filtro
    try {
        $con = Database1::getInstance()->getConnection();
        $query_ufs = "SELECT DISTINCT cro_uf FROM carteirinhas_despachadas_cro ORDER BY cro_uf";
        $stmt_ufs = $con->prepare($query_ufs);
        $stmt_ufs->execute();
        $estados_disponiveis = $stmt_ufs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $error) {
        $estados_disponiveis = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Envios de Identidades ao CRO - Por Período</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <style>
       
      
        .alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #b6d4fe; }
        .filter-form { background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 2rem; border: 1px solid #dee2e6; }
        .form-row { display: flex; gap: 1rem; align-items: end; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; min-width: 150px; }
        .form-group label { margin-bottom: 0.5rem; font-weight: bold; color: #495057; }
        .form-group input, .form-group select { padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; font-size: 1rem; }
        .stats-table { margin-bottom: 2rem; max-width: 100%; overflow-x: auto; }
        .stats-table th, .stats-table td { padding: 0.5rem; text-align: center; }
        .total-row { font-weight: bold; background: #e9ecef; }
        .btn { padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer; text-decoration: none; display: inline-block; border: none; font-size: 1rem; }
        .btn-primary { background: #007bff; color: white; border: 1px solid #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; border: 1px solid #28a745; margin-left: 1rem; }
        .btn-success:hover { background: #218838; }
        .table { width: 100%; margin-bottom: 1rem; color: #212529; }
        .table th, .table td { padding: 0.5rem; vertical-align: top; border-top: 1px solid #dee2e6; font-size: 0.9rem; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
        .table tbody + tbody { border-top: 2px solid #dee2e6; }
        .table-sm th, .table-sm td { padding: 0.3rem; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.05); }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
        .table-responsive { margin-top: 20px; }
        .info-box { background: #e3f2fd; padding: 1rem; border-radius: 0.25rem; margin-bottom: 1rem; border-left: 4px solid #2196f3; }
    </style>
    <script>
        $(document).ready(function() {
            if ($('#tabelaDespachos tbody tr').length > 0) {
                $('#tabelaDespachos').DataTable({
                    "paging": true,
                    "pageLength": 25,
                    "lengthMenu": [10, 25, 50, 100],
                    "language": {
                        "url": "../../assets/lang/pt-BR.json"
                    },
                    "order": [[4, "desc"]]
                });
            }
        });
        
        function limparFiltros() {
            document.getElementById('data_inicial').value = '';
            document.getElementById('data_final').value = '';
            document.getElementById('cro_uf').value = '';
        }
    </script>
</head>
<body>
<div class="container-fluid">
    <h1> Consulta de Envios de Identidades ao CRO - Por Período</h1>
    
    <!-- Mensagens de Sucesso/Erro -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <!-- Formulário de Filtros -->
    <div class="filter-form">
  
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="data_inicial">Data Inicial:</label>
                    <input type="date" id="data_inicial" name="data_inicial" value="<?= htmlspecialchars($data_inicial) ?>" required>
                </div>
                <div class="form-group">
                    <label for="data_final">Data Final:</label>
                    <input type="date" id="data_final" name="data_final" value="<?= htmlspecialchars($data_final) ?>" required>
                </div>
                <div class="form-group">
                    <label for="cro_uf">UF (Opcional):</label>
                    <select id="cro_uf" name="cro_uf">
                        <option value="">Todos os Estados</option>
                        <?php if (isset($estados_disponiveis)): ?>
                            <?php foreach ($estados_disponiveis as $estado): ?>
                                <option value="<?= htmlspecialchars($estado['cro_uf']) ?>" 
                                    <?= $cro_uf == $estado['cro_uf'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['cro_uf']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Consultar</button>
                 
                </div>
            </div>
        </form>
    </div>
    
    <?php if (!empty($data_inicial) && !empty($data_final)): ?>
        <div class="info-box">
            <strong>📅 Período:</strong> <?= date('d/m/Y', strtotime($data_inicial)) ?> até <?= date('d/m/Y', strtotime($data_final)) ?>
            <?php if (!empty($cro_uf)): ?>
                | <strong>🌎 Estado:</strong> <?= htmlspecialchars($cro_uf) ?>
            <?php endif; ?>
            | <strong>📊 Total de registros:</strong> <?= number_format($total_geral, 0, ',', '.') ?>
            <?php if ($total_geral >= 3000): ?>
                <br><strong>⚠️ Atenção:</strong> Resultado limitado a 3.000 registros. Use filtros mais específicos para ver todos os dados.
            <?php endif; ?>
        </div>
        
        <?php if ($total_geral > 0): ?>
            <!-- Mini Tabela de Contagem por Estado -->
            <?php if (!empty($contagem_estados)): ?>
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
            <?php endif; ?>
            
                         <!-- Botão de Download CSV -->
             <a href="/download-excel-streaming?data_inicial=<?= urlencode($data_inicial) ?>&data_final=<?= urlencode($data_final) ?><?= !empty($cro_uf) ? '&cro_uf=' . urlencode($cro_uf) : '' ?>&limit=3000" 
                class="btn btn-success" style="text-decoration: none;">
                                  📄 DOWNLOAD CSV - <?= number_format(min($total_geral, 3000), 0, ',', '.') ?> registros
             </a>
            
            <!-- Título e Tabela -->
            <h3>Todos os Envios (<?= number_format(count($todos_despachos), 0, ',', '.') ?> de <?= number_format($total_geral, 0, ',', '.') ?> registros)</h3>
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
        <?php else: ?>
            <div class="no-data">
                <h3>🚫 Nenhum registro encontrado</h3>
                <p>Não foram encontrados despachos para o período selecionado.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="info-box">
            <strong>ℹ️ Instruções:</strong> Selecione um período para consultar os despachos de identidades. 
            Por padrão, será exibido o período dos últimos 30 dias.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
