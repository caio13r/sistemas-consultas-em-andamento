<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database1;
use PDO;
use PDOException;

Session::CheckSession();

// VERIFICAÇÃO DE PERMISSÃO: Apenas gerenciadores (CFO Admin/TI ou email específico)
if (!Helper::temPermissaoGerenciarRFB()) {
    echo '<div class="container-fluid mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4 class="alert-heading"><i class="fas fa-ban"></i> Acesso Negado</h4>';
    echo '<p>Você não tem permissão para acessar esta funcionalidade.</p>';
    echo '<p>Apenas administradores e usuários autorizados podem gerenciar logs da RFB.</p>';
    echo '<a href="/" class="btn btn-primary mt-2"><i class="fas fa-home"></i> Voltar ao Início</a>';
    echo '</div>';
    echo '</div>';
    require_once INC_PATH . '/footer.php';
    exit;
}

$db = Database1::getInstance();
$con = $db->getConnection();

// Buscar estatísticas gerais
try {
    $queryStats = "SELECT
                    COUNT(*) as total_consultas,
                    COUNT(DISTINCT usuario_id) as total_usuarios,
                    COUNT(DISTINCT COALESCE(documento_consultado, cpf_consultado)) as documentos_unicos,
                    COUNT(DISTINCT CASE WHEN COALESCE(tipo_consulta, 'CPF') = 'CPF' THEN COALESCE(documento_consultado, cpf_consultado) END) as cpfs_unicos,
                    COUNT(DISTINCT CASE WHEN tipo_consulta = 'CNPJ' THEN documento_consultado END) as cnpjs_unicos,
                    COALESCE(SUM(CASE WHEN COALESCE(tipo_consulta, 'CPF') = 'CPF' THEN 1 ELSE 0 END), 0) as consultas_cpf,
                    COALESCE(SUM(CASE WHEN tipo_consulta = 'CNPJ' THEN 1 ELSE 0 END), 0) as consultas_cnpj,
                    COALESCE(SUM(CASE WHEN existe_base_cfo = 1 THEN 1 ELSE 0 END), 0) as consultas_com_cfo,
                    COALESCE(SUM(CASE WHEN sucesso = 0 THEN 1 ELSE 0 END), 0) as consultas_erro,
                    COALESCE(SUM(CASE WHEN sucesso = 1 THEN 1 ELSE 0 END), 0) as consultas_sucesso
                   FROM tbl_rfb_auditoria";
    $stmt = $con->prepare($queryStats);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas por período
    $queryHoje = "SELECT COUNT(*) as total FROM tbl_rfb_auditoria 
                  WHERE DATE(data_hora) = CURDATE()";
    $stmt = $con->prepare($queryHoje);
    $stmt->execute();
    $statsHoje = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $queryMes = "SELECT COUNT(*) as total FROM tbl_rfb_auditoria 
                 WHERE YEAR(data_hora) = YEAR(CURDATE()) 
                 AND MONTH(data_hora) = MONTH(CURDATE())";
    $stmt = $con->prepare($queryMes);
    $stmt->execute();
    $statsMes = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $stats = [
        'total_consultas' => 0, 
        'total_usuarios' => 0, 
        'documentos_unicos' => 0,
        'cpfs_unicos' => 0, 
        'cnpjs_unicos' => 0,
        'consultas_cpf' => 0,
        'consultas_cnpj' => 0,
        'consultas_com_cfo' => 0, 
        'consultas_erro' => 0,
        'consultas_sucesso' => 0
    ];
    $statsHoje = ['total' => 0];
    $statsMes = ['total' => 0];
}

// Filtros
$filtroUsuario   = $_GET['usuario'] ?? '';
$filtroPeriodo   = $_GET['periodo'] ?? '30'; // últimos 30 dias por padrão
$filtroCPF       = $_GET['cpf'] ?? '';
$filtroFinalidade     = $_GET['finalidade'] ?? '';
$filtroBaseLegal      = $_GET['base_legal'] ?? '';
$filtroUF             = $_GET['uf'] ?? '';
$filtroSetor          = $_GET['setor'] ?? '';
$filtroRisco          = $_GET['risco'] ?? '';
$filtroIrregularidade = $_GET['irregularidade'] ?? '';
$filtroStatus         = $_GET['status'] ?? '';

// Montar query de auditoria
$whereFilters = ["1=1"];
$params = [];

if (!empty($filtroUsuario)) {
    $whereFilters[] = "a.usuario_id = :usuario";
    $params[':usuario'] = (int)$filtroUsuario; // Garantir que seja inteiro
}

if (!empty($filtroCPF)) {
    $docLimpo = preg_replace('/[^0-9]/', '', $filtroCPF);
    if (!empty($docLimpo)) {
        // Buscar tanto em documento_consultado quanto em cpf_consultado (compatibilidade)
        $whereFilters[] = "(COALESCE(a.documento_consultado, a.cpf_consultado) = :documento)";
        $params[':documento'] = $docLimpo;
    }
}

if ($filtroPeriodo != 'todos') {
    if ($filtroPeriodo == '1') {
        // Hoje
        $whereFilters[] = "DATE(a.data_hora) = CURDATE()";
    } else {
        // Últimos X dias
        $whereFilters[] = "a.data_hora >= DATE_SUB(NOW(), INTERVAL :dias DAY)";
        $params[':dias'] = (int)$filtroPeriodo;
    }
}

if (!empty($filtroFinalidade)) {
    $whereFilters[] = "COALESCE(a.finalidade_consulta, a.finalidade, '') = :finalidade";
    $params[':finalidade'] = $filtroFinalidade;
}
if (!empty($filtroBaseLegal)) {
    $whereFilters[] = "COALESCE(a.base_legal, '') = :base_legal";
    $params[':base_legal'] = $filtroBaseLegal;
}
if (!empty($filtroUF)) {
    $whereFilters[] = "COALESCE(a.conselho_uf, a.uf, '') = :uf";
    $params[':uf'] = $filtroUF;
}
if (!empty($filtroSetor)) {
    $whereFilters[] = "COALESCE(a.setor, a.usuario_grupo, '') = :setor";
    $params[':setor'] = $filtroSetor;
}
if (!empty($filtroRisco)) {
    $whereFilters[] = "COALESCE(a.nivel_risco, a.risco, '') = :risco";
    $params[':risco'] = $filtroRisco;
}
if ($filtroIrregularidade === 'sim' || $filtroIrregularidade === '1') {
    $whereFilters[] = "COALESCE(a.indicador_irregularidade, a.irregularidade, 0) = 1";
} elseif ($filtroIrregularidade === 'nao' || $filtroIrregularidade === '0') {
    $whereFilters[] = "(COALESCE(a.indicador_irregularidade, a.irregularidade, 0) = 0 OR a.indicador_irregularidade IS NULL)";
}
if ($filtroStatus === 'sucesso') {
    $whereFilters[] = "a.sucesso = 1";
} elseif ($filtroStatus === 'erro') {
    $whereFilters[] = "a.sucesso = 0";
}

$whereClause = implode(' AND ', $whereFilters);

try {
    // Primeiro, verificar se há dados na tabela
    $queryCount = "SELECT COUNT(*) as total FROM tbl_rfb_auditoria";
    $stmtCount = $con->prepare($queryCount);
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    $queryAuditoria = "SELECT
                        a.id,
                        a.usuario_id,
                        a.usuario_nome,
                        a.usuario_grupo,
                        a.usuario_subgrupo,
                        COALESCE(a.tipo_consulta, 
                            CASE 
                                WHEN LENGTH(COALESCE(a.documento_consultado, a.cpf_consultado)) = 11 THEN 'CPF'
                                WHEN LENGTH(COALESCE(a.documento_consultado, a.cpf_consultado)) = 14 THEN 'CNPJ'
                                ELSE 'CPF'
                            END
                        ) as tipo_consulta,
                        COALESCE(a.documento_consultado, a.cpf_consultado) as documento_consultado,
                        COALESCE(a.nome_consultado, '') as nome_consultado,
                        COALESCE(a.situacao_cadastral, '') as situacao_cadastral,
                        a.existe_base_cfo,
                        a.inscricao_cfo,
                        a.nome_cfo,
                        a.cro_cfo,
                        a.ip_origem,
                        a.data_hora,
                        a.sucesso,
                        a.mensagem_erro,
                        a.tempo_resposta_ms,
                        u.name as usuario_nome_completo,
                        u.email as usuario_email
                       FROM tbl_rfb_auditoria a
                       LEFT JOIN tbl_users u ON a.usuario_id = u.id
                       WHERE $whereClause
                       ORDER BY a.data_hora DESC
                       LIMIT 500";
    
    $stmt = $con->prepare($queryAuditoria);
    foreach ($params as $key => $value) {
        // Especificar tipo de dado para melhor performance e segurança
        if ($key === ':usuario' || $key === ':dias') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    $stmt->execute();
    $auditorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: logar se não encontrar dados
    if (empty($auditorias) && $totalRegistros > 0) {
        error_log("AVISO: Há $totalRegistros registros na tabela, mas a query não retornou resultados. WHERE: $whereClause");
    }
    
} catch (PDOException $e) {
    error_log("Erro ao buscar auditoria: " . $e->getMessage());
    error_log("Query: " . ($queryAuditoria ?? 'N/A'));
    error_log("WHERE: " . $whereClause);
    error_log("Params: " . print_r($params, true));
    $auditorias = [];
    $totalRegistros = 0;
}

// Estatísticas adicionais (total_irregular, pct_cfo) e consultas por finalidade
$statsFiltradas = ['total_irregular' => 0, 'pct_cfo' => 0];
$consultasPorFinalidade = [];
try {
    $sqlFiltrado = "SELECT
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN a.existe_base_cfo = 1 THEN 1 ELSE 0 END), 0) as total_cfo
        FROM tbl_rfb_auditoria a WHERE " . $whereClause;
    $st = $con->prepare($sqlFiltrado);
    foreach ($params as $k => $v) {
        $st->bindValue($k, $v, ($k === ':usuario' || $k === ':dias') ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    $rf = $st->fetch(PDO::FETCH_ASSOC);
    $totalF = (int)($rf['total'] ?? 0);
    $totalCfo = (int)($rf['total_cfo'] ?? 0);
    $statsFiltradas['pct_cfo'] = $totalF > 0 ? round(100 * $totalCfo / $totalF, 1) : 0;
} catch (PDOException $e) {
    error_log("Erro stats filtradas: " . $e->getMessage());
}
try {
    $sqlIrreg = "SELECT COALESCE(SUM(CASE WHEN COALESCE(a.indicador_irregularidade, a.irregularidade, 0) = 1 THEN 1 ELSE 0 END), 0) as total_irregular
        FROM tbl_rfb_auditoria a WHERE $whereClause";
    $st = $con->prepare($sqlIrreg);
    foreach ($params as $k => $v) {
        $st->bindValue($k, $v, ($k === ':usuario' || $k === ':dias') ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    $statsFiltradas['total_irregular'] = (int)($st->fetchColumn() ?: 0);
} catch (PDOException $e) {
    // Coluna indicador_irregularidade/irregularidade pode não existir
    $statsFiltradas['total_irregular'] = 0;
}
try {
    $sqlFinalidade = "SELECT COALESCE(a.finalidade_consulta, a.finalidade, '') as finalidade, COUNT(*) as total
        FROM tbl_rfb_auditoria a WHERE $whereClause GROUP BY COALESCE(a.finalidade_consulta, a.finalidade, '')
        ORDER BY total DESC LIMIT 10";
    $st = $con->prepare($sqlFinalidade);
    foreach ($params as $k => $v) {
        $st->bindValue($k, $v, ($k === ':usuario' || $k === ':dias') ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    $consultasPorFinalidade = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $consultasPorFinalidade = [];
}

// Buscar valores distintos para filtros (finalidade, base_legal, uf, setor, risco)
$opcoesFinalidade = [];
$opcoesBaseLegal = [];
$opcoesUF = [];
$opcoesSetor = [];
$opcoesRisco = [];
try {
    $st = $con->query("SELECT DISTINCT usuario_grupo FROM tbl_rfb_auditoria WHERE usuario_grupo IS NOT NULL AND usuario_grupo != '' ORDER BY usuario_grupo LIMIT 50");
    $opcoesSetor = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (PDOException $e) { $opcoesSetor = []; }
try {
    $st = $con->query("SELECT DISTINCT finalidade_consulta FROM tbl_rfb_auditoria WHERE finalidade_consulta IS NOT NULL AND finalidade_consulta != '' ORDER BY finalidade_consulta LIMIT 50");
    $opcoesFinalidade = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (PDOException $e) { $opcoesFinalidade = []; }
try {
    $st = $con->query("SELECT DISTINCT base_legal FROM tbl_rfb_auditoria WHERE base_legal IS NOT NULL AND base_legal != '' ORDER BY base_legal LIMIT 50");
    $opcoesBaseLegal = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (PDOException $e) { $opcoesBaseLegal = []; }
try {
    $st = $con->query("SELECT DISTINCT conselho_uf FROM tbl_rfb_auditoria WHERE conselho_uf IS NOT NULL AND conselho_uf != '' ORDER BY conselho_uf LIMIT 50");
    $opcoesUF = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (PDOException $e) { $opcoesUF = []; }
try {
    $st = $con->query("SELECT DISTINCT nivel_risco FROM tbl_rfb_auditoria WHERE nivel_risco IS NOT NULL AND nivel_risco != '' ORDER BY nivel_risco LIMIT 50");
    $opcoesRisco = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (PDOException $e) { $opcoesRisco = []; }

// Buscar lista de usuários para filtro
try {
    $queryUsuarios = "SELECT DISTINCT u.id, u.name 
                      FROM tbl_users u
                      INNER JOIN tbl_rfb_auditoria a ON u.id = a.usuario_id
                      ORDER BY u.name";
    $stmt = $con->prepare($queryUsuarios);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
}

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar mr-2"></i>Gerenciamento de Consultas - Receita Federal</h5>
        </div>

        <div class="card-body">
            
            <!-- Estatísticas Gerais -->
            <style>
                .stat-card {
                    border-left: 4px solid;
                    transition: transform 0.2s, box-shadow 0.2s;
                    height: 100%;
                }
                .stat-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                }
                .stat-card-body {
                    padding: 1.5rem;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    height: 100%;
                }
                .stat-icon {
                    font-size: 2.5rem;
                    opacity: 0.8;
                    margin-bottom: 0.5rem;
                }
                .stat-label {
                    font-size: 0.85rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 0.5rem;
                    opacity: 0.8;
                }
                .stat-value {
                    font-size: 2rem;
                    font-weight: 700;
                    line-height: 1.2;
                }
                .stat-card-primary { border-left-color: #4e73df; }
                .stat-card-primary .stat-icon { color: #4e73df; }
                .stat-card-primary .stat-label { color: #4e73df; }
                .stat-card-primary .stat-value { color: #2c3e50; }
                
                .stat-card-success { border-left-color: #1cc88a; }
                .stat-card-success .stat-icon { color: #1cc88a; }
                .stat-card-success .stat-label { color: #1cc88a; }
                .stat-card-success .stat-value { color: #2c3e50; }
                
                .stat-card-info { border-left-color: #36b9cc; }
                .stat-card-info .stat-icon { color: #36b9cc; }
                .stat-card-info .stat-label { color: #36b9cc; }
                .stat-card-info .stat-value { color: #2c3e50; }
                
                .stat-card-warning { border-left-color: #f6c23e; }
                .stat-card-warning .stat-icon { color: #f6c23e; }
                .stat-card-warning .stat-label { color: #e67e22; }
                .stat-card-warning .stat-value { color: #2c3e50; }
                
                .stat-card-secondary { border-left-color: #858796; }
                .stat-card-secondary .stat-icon { color: #858796; }
                .stat-card-secondary .stat-label { color: #858796; }
                .stat-card-secondary .stat-value { color: #2c3e50; }
                
                .stat-card-dark { border-left-color: #5a5c69; }
                .stat-card-dark .stat-icon { color: #5a5c69; }
                .stat-card-dark .stat-label { color: #5a5c69; }
                .stat-card-dark .stat-value { color: #2c3e50; }
            </style>
            
            <div class="row mb-4">
                <!-- Primeira Linha - Cards Principais -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-primary shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-search stat-icon"></i>
                                <div class="stat-label">Total de Consultas</div>
                            </div>
                            <div class="stat-value"><?= number_format($stats['total_consultas'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-success shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-calendar-day stat-icon"></i>
                                <div class="stat-label">Consultas Hoje</div>
                            </div>
                            <div class="stat-value"><?= number_format($statsHoje['total'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-info shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-calendar-alt stat-icon"></i>
                                <div class="stat-label">Consultas Este Mês</div>
                            </div>
                            <div class="stat-value"><?= number_format($statsMes['total'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-warning shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-users stat-icon"></i>
                                <div class="stat-label">Documentos Únicos</div>
                            </div>
                            <div class="stat-value"><?= number_format($stats['documentos_unicos'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Segunda Linha - Cards Detalhados -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-primary shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-user stat-icon"></i>
                                <div class="stat-label">CPFs Únicos</div>
                            </div>
                            <div class="stat-value"><?= number_format($stats['cpfs_unicos'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-info shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-building stat-icon"></i>
                                <div class="stat-label">CNPJs Únicos</div>
                            </div>
                            <div class="stat-value"><?= number_format($stats['cnpjs_unicos'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-secondary shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-user-check stat-icon"></i>
                                <div class="stat-label">Total CPF</div>
                            </div>
                            <div class="stat-value"><?= number_format($stats['consultas_cpf'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-dark shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-building stat-icon"></i>
                                <div class="stat-label">Total CNPJ</div>
                            </div>
                            <div class="stat-value"><?= number_format($stats['consultas_cnpj'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Novos cards: % Na Base CFO, Irregularidades, Consultas por finalidade -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-success shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-percentage stat-icon"></i>
                                <div class="stat-label">% Na Base CFO</div>
                            </div>
                            <div class="stat-value"><?= number_format($statsFiltradas['pct_cfo'] ?? 0, 1, ',', '.') ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card stat-card-warning shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-exclamation-triangle stat-icon"></i>
                                <div class="stat-label">Irregularidades</div>
                            </div>
                            <div class="stat-value"><?= number_format($statsFiltradas['total_irregular'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 mb-3">
                    <div class="card stat-card stat-card-info shadow">
                        <div class="stat-card-body">
                            <div>
                                <i class="fas fa-tasks stat-icon"></i>
                                <div class="stat-label">Consultas por Finalidade (top 10)</div>
                            </div>
                            <div class="stat-value" style="font-size:1rem;">
                                <?php if (!empty($consultasPorFinalidade)): ?>
                                    <?php foreach (array_slice($consultasPorFinalidade, 0, 5) as $row): ?>
                                        <span class="badge badge-info mr-1"><?= htmlspecialchars($row['finalidade'] ?: '-') ?>: <?= (int)$row['total'] ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($consultasPorFinalidade) > 5): ?>
                                        <span class="badge badge-secondary">+<?= count($consultasPorFinalidade) - 5 ?> mais</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filtros de Busca</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="" id="formFiltrosRfb">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Usuário:</label>
                                <select name="usuario" class="form-control">
                                    <option value="">Todos os usuários</option>
                                    <?php foreach ($usuarios as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= $filtroUsuario == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Período:</label>
                                <select name="periodo" class="form-control">
                                    <option value="1" <?= $filtroPeriodo == '1' ? 'selected' : '' ?>>Hoje</option>
                                    <option value="7" <?= $filtroPeriodo == '7' ? 'selected' : '' ?>>Últimos 7 dias</option>
                                    <option value="30" <?= $filtroPeriodo == '30' ? 'selected' : '' ?>>Últimos 30 dias</option>
                                    <option value="90" <?= $filtroPeriodo == '90' ? 'selected' : '' ?>>Últimos 90 dias</option>
                                    <option value="todos" <?= $filtroPeriodo == 'todos' ? 'selected' : '' ?>>Todos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>CPF/CNPJ:</label>
                                <input type="text" name="cpf" class="form-control form-control-sm" placeholder="CPF ou CNPJ" value="<?= htmlspecialchars($filtroCPF) ?>">
                            </div>
                            <div class="col-md-2">
                                <label>Finalidade:</label>
                                <select name="finalidade" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    <?php foreach ($opcoesFinalidade as $v): ?>
                                        <option value="<?= htmlspecialchars($v) ?>" <?= $filtroFinalidade === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Base Legal:</label>
                                <select name="base_legal" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    <?php foreach ($opcoesBaseLegal as $v): ?>
                                        <option value="<?= htmlspecialchars($v) ?>" <?= $filtroBaseLegal === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>UF:</label>
                                <select name="uf" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    <?php foreach ($opcoesUF as $v): ?>
                                        <option value="<?= htmlspecialchars($v) ?>" <?= $filtroUF === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Setor:</label>
                                <select name="setor" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($opcoesSetor as $v): ?>
                                        <option value="<?= htmlspecialchars($v) ?>" <?= $filtroSetor === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Risco:</label>
                                <select name="risco" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <?php foreach ($opcoesRisco as $v): ?>
                                        <option value="<?= htmlspecialchars($v) ?>" <?= $filtroRisco === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Irregularidade:</label>
                                <select name="irregularidade" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    <option value="sim" <?= $filtroIrregularidade === 'sim' ? 'selected' : '' ?>>Sim</option>
                                    <option value="nao" <?= $filtroIrregularidade === 'nao' ? 'selected' : '' ?>>Não</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Status:</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="sucesso" <?= $filtroStatus === 'sucesso' ? 'selected' : '' ?>>Sucesso</option>
                                    <option value="erro" <?= $filtroStatus === 'erro' ? 'selected' : '' ?>>Erro</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="#" id="btnPdfLog" class="btn btn-danger btn-block mt-1">
                                    <i class="fas fa-file-pdf"></i> Gerar PDF Log
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Debug Info (apenas em desenvolvimento) -->
            <?php if (empty($auditorias)): ?>
            <div class="alert alert-warning">
                <strong>Debug:</strong> Total de registros na tabela: <?= number_format($totalRegistros, 0, ',', '.') ?>
                <br><strong>Filtro aplicado:</strong> <code><?= htmlspecialchars($whereClause) ?></code>
                <?php if (!empty($params)): ?>
                    <br><strong>Parâmetros:</strong>
                    <ul>
                        <?php foreach ($params as $key => $value): ?>
                            <li><?= htmlspecialchars($key) ?> = <?= htmlspecialchars($value) ?> (<?= gettype($value) ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <br><strong>Nenhum parâmetro aplicado</strong>
                <?php endif; ?>
                <?php if (!empty($filtroUsuario) || !empty($filtroCPF) || $filtroPeriodo != 'todos'): ?>
                    <br><strong>Filtros ativos:</strong>
                    <ul>
                        <?php if (!empty($filtroUsuario)): ?>
                            <li>Usuário: <?= htmlspecialchars($filtroUsuario) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($filtroCPF)): ?>
                            <li>CPF/CNPJ: <?= htmlspecialchars($filtroCPF) ?></li>
                        <?php endif; ?>
                        <?php if ($filtroPeriodo != 'todos'): ?>
                            <li>Período: <?= htmlspecialchars($filtroPeriodo) ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Tabela de Auditoria -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Registro de Consultas (Últimas 500)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm" id="tabelaAuditoria">
                            <thead class="thead-light">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Usuário</th>
                                    <th>Grupo/Subgrupo</th>
                                    <th>CPF/CNPJ Consultado</th>
                                    <th>Nome/Razão Social</th>
                                    <th>Situação</th>
                                    <th>Na Base CFO?</th>
                                    <th>Inscrição CFO</th>
                                    <th>IP Origem</th>
                                    <th>Tempo (ms)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($auditorias)): ?>
                                    <tr>
                                        <td colspan="12" class="text-center">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> Nenhum registro encontrado
                                                <?php if (isset($totalRegistros) && $totalRegistros > 0): ?>
                                                    <br><small>Total de registros na tabela: <?= number_format($totalRegistros, 0, ',', '.') ?></small>
                                                    <br><small>Verifique os filtros aplicados.</small>
                                                <?php else: ?>
                                                    <br><small>A tabela está vazia. Faça algumas consultas primeiro.</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($auditorias as $aud): ?>
                                        <?php 
                                            $tipoConsulta = $aud['tipo_consulta'] ?? 'CPF';
                                            $documento = $aud['documento_consultado'] ?? $aud['cpf_consultado'] ?? '';
                                            // Formatar documento
                                            if (strlen($documento) == 11) {
                                                $docFormatado = substr($documento, 0, 3) . '.' . substr($documento, 3, 3) . '.' . substr($documento, 6, 3) . '-' . substr($documento, 9, 2);
                                            } elseif (strlen($documento) == 14) {
                                                $docFormatado = substr($documento, 0, 2) . '.' . substr($documento, 2, 3) . '.' . substr($documento, 5, 3) . '/' . substr($documento, 8, 4) . '-' . substr($documento, 12, 2);
                                            } else {
                                                $docFormatado = $documento;
                                            }
                                        ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i:s', strtotime($aud['data_hora'])) ?></td>
                                            <td class="text-center">
                                                <?php if ($tipoConsulta === 'CNPJ'): ?>
                                                    <span class="badge badge-success">CNPJ</span>
                                                <?php else: ?>
                                                    <span class="badge badge-primary">CPF</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($aud['usuario_nome'] ?? $aud['usuario_nome_completo']) ?></td>
                                            <td><small><?= htmlspecialchars($aud['usuario_grupo']) ?> / <?= htmlspecialchars($aud['usuario_subgrupo']) ?></small></td>
                                            <td class="text-monospace"><?= htmlspecialchars($docFormatado) ?></td>
                                            <td><?= htmlspecialchars($aud['nome_consultado'] ?? '-') ?></td>
                                            <td><small><?= htmlspecialchars($aud['situacao_cadastral'] ?? '-') ?></small></td>
                                            <td class="text-center">
                                                <?php if ($aud['existe_base_cfo']): ?>
                                                    <span class="badge badge-success">Sim</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Não</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($aud['inscricao_cfo'] ?? '-') ?></td>
                                            <td><small><?= htmlspecialchars($aud['ip_origem']) ?></small></td>
                                            <td class="text-right"><?= number_format($aud['tempo_resposta_ms'], 0) ?></td>
                                            <td class="text-center">
                                                <?php if ($aud['sucesso']): ?>
                                                    <span class="badge badge-success">Sucesso</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger" title="<?= htmlspecialchars($aud['mensagem_erro'] ?? '') ?>">Erro</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="mt-4">
                <a href="/exportar-log-rfb-excel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exportar 500 Primeiros Logs para Excel
                </a>
                <a href="/consulta-integrada-3" class="btn btn-secondary ml-2">
                    <i class="fas fa-arrow-left"></i> Voltar para Consulta
                </a>
            </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#btnPdfLog').on('click', function(e) {
        e.preventDefault();
        var q = $('#formFiltrosRfb').serialize();
        window.open('/gerar-pdf-rfb-log' + (q ? '?' + q : ''), '_blank', 'width=900,height=700');
    });
    $('#tabelaAuditoria').DataTable({
        "language": {
            "url": "/assets/lang/pt-BR.json"
        },
        "order": [[0, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Exportar Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> Exportar PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                className: 'btn btn-info btn-sm'
            }
        ]
    });
});
</script>

<?php
require_once INC_PATH . '/footer.php';
?>
