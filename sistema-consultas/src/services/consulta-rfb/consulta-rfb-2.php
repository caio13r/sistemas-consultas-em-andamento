<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use PDO;
use PDOException;

$db = Database1::getInstance();
$con = $db->getConnection();

// Buscar estatísticas gerais
try {
    $queryStats = "SELECT
                    COUNT(*) as total_consultas,
                    COUNT(DISTINCT usuario_id) as total_usuarios,
                    COUNT(DISTINCT cpf_consultado) as cpfs_unicos,
                    SUM(CASE WHEN existe_base_cfo = 1 THEN 1 ELSE 0 END) as consultas_com_cfo,
                    SUM(CASE WHEN sucesso = 0 THEN 1 ELSE 0 END) as consultas_erro
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
    $stats = ['total_consultas' => 0, 'total_usuarios' => 0, 'cpfs_unicos' => 0, 'consultas_com_cfo' => 0, 'consultas_erro' => 0];
    $statsHoje = ['total' => 0];
    $statsMes = ['total' => 0];
}

// Filtros
$filtroUsuario = $_GET['usuario'] ?? '';
$filtroPeriodo = $_GET['periodo'] ?? '30'; // últimos 30 dias por padrão
$filtroCPF = $_GET['cpf'] ?? '';

// Montar query de auditoria
$whereFilters = ["1=1"];
$params = [];

if (!empty($filtroUsuario)) {
    $whereFilters[] = "a.usuario_id = :usuario";
    $params[':usuario'] = $filtroUsuario;
}

if (!empty($filtroCPF)) {
    $cpfLimpo = preg_replace('/[^0-9]/', '', $filtroCPF);
    $whereFilters[] = "a.cpf_consultado = :cpf";
    $params[':cpf'] = $cpfLimpo;
}

if ($filtroPeriodo != 'todos') {
    $whereFilters[] = "a.data_hora >= DATE_SUB(NOW(), INTERVAL :dias DAY)";
    $params[':dias'] = (int)$filtroPeriodo;
}

$whereClause = implode(' AND ', $whereFilters);

try {
    $queryAuditoria = "SELECT
                        a.*,
                        u.name as usuario_nome_completo,
                        u.email as usuario_email
                       FROM tbl_rfb_auditoria a
                       LEFT JOIN tbl_users u ON a.usuario_id = u.id
                       WHERE $whereClause
                       ORDER BY a.data_hora DESC
                       LIMIT 500";

    $stmt = $con->prepare($queryAuditoria);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $auditorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar auditoria: " . $e->getMessage());
    $auditorias = [];
}

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

<!-- Estatísticas Gerais -->
<div class="row mb-4 mt-3">
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Consultas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($stats['total_consultas'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-search fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Consultas Hoje
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($statsHoje['total'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Consultas Este Mês
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($statsMes['total'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            CPFs Únicos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($stats['cpfs_unicos'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
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
        <form method="GET" action="">
            <input type="hidden" name="tipo" value="2">
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
                <div class="col-md-3">
                    <label>CPF:</label>
                    <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00" value="<?= htmlspecialchars($filtroCPF) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

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
                        <th>Usuário</th>
                        <th>Grupo/Subgrupo</th>
                        <th>CPF Consultado</th>
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
                            <td colspan="9" class="text-center">Nenhum registro encontrado</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($auditorias as $aud): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($aud['data_hora'])) ?></td>
                                <td><?= htmlspecialchars($aud['usuario_nome'] ?? $aud['usuario_nome_completo']) ?></td>
                                <td><small><?= htmlspecialchars($aud['usuario_grupo']) ?> / <?= htmlspecialchars($aud['usuario_subgrupo']) ?></small></td>
                                <td class="text-monospace"><?= htmlspecialchars($aud['cpf_consultado']) ?></td>
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
                                        <span class="badge badge-danger" title="<?= htmlspecialchars($aud['mensagem_erro']) ?>">Erro</span>
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

<script>
$(document).ready(function() {
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
