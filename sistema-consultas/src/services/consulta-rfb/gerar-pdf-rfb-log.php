<?php
/**
 * Geração de PDF compilado - Relatório de Consultas RFB (Auditoria)
 * Módulo de Gerenciamento e Fiscalização
 * Formato compacto para impressão/arquivamento
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database1;
use PDO;
use PDOException;

Session::CheckSession();

if (!Helper::temPermissaoGerenciarRFB()) {
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado. Você não tem permissão para gerar este relatório.');
}

$db = Database1::getInstance();
$con = $db->getConnection();

// Mesmos filtros de gerenciar-rfb
$filtroUsuario = $_GET['usuario'] ?? '';
$filtroPeriodo = $_GET['periodo'] ?? '30';
$filtroCPF = $_GET['cpf'] ?? '';
$filtroFinalidade     = $_GET['finalidade'] ?? '';
$filtroBaseLegal      = $_GET['base_legal'] ?? '';
$filtroUF             = $_GET['uf'] ?? '';
$filtroSetor          = $_GET['setor'] ?? '';
$filtroRisco = $_GET['risco'] ?? '';
$filtroIrregularidade = $_GET['irregularidade'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

$whereFilters = ["1=1"];
$params = [];

if (!empty($filtroUsuario)) {
    $whereFilters[] = "a.usuario_id = :usuario";
    $params[':usuario'] = (int)$filtroUsuario;
}
if (!empty($filtroCPF)) {
    $docLimpo = preg_replace('/[^0-9]/', '', $filtroCPF);
    if (!empty($docLimpo)) {
        $whereFilters[] = "(COALESCE(a.documento_consultado, a.cpf_consultado) = :documento)";
        $params[':documento'] = $docLimpo;
    }
}
if ($filtroPeriodo != 'todos') {
    if ($filtroPeriodo == '1') {
        $whereFilters[] = "DATE(a.data_hora) = CURDATE()";
    } else {
        $whereFilters[] = "a.data_hora >= DATE_SUB(NOW(), INTERVAL :dias DAY)";
        $params[':dias'] = (int)$filtroPeriodo;
    }
}
if (!empty($filtroFinalidade)) {
    $whereFilters[] = "a.finalidade_consulta = :finalidade";
    $params[':finalidade'] = $filtroFinalidade;
}
if (!empty($filtroBaseLegal)) {
    $whereFilters[] = "a.base_legal_lgpd = :base_legal";
    $params[':base_legal'] = $filtroBaseLegal;
}
if (!empty($filtroUF)) {
    $whereFilters[] = "a.conselho_uf = :uf";
    $params[':uf'] = $filtroUF;
}
if (!empty($filtroSetor)) {
    $whereFilters[] = "a.setor_origem = :setor";
    $params[':setor'] = $filtroSetor;
}
if (!empty($filtroRisco)) {
    $whereFilters[] = "a.nivel_risco = :risco";
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

// Buscar dados
try {
    $query = "SELECT a.id, a.usuario_id, a.usuario_nome, a.usuario_grupo, a.usuario_subgrupo,
              COALESCE(a.tipo_consulta, CASE WHEN LENGTH(COALESCE(a.documento_consultado, a.cpf_consultado)) = 11 THEN 'CPF' WHEN LENGTH(COALESCE(a.documento_consultado, a.cpf_consultado)) = 14 THEN 'CNPJ' ELSE 'CPF' END) as tipo_consulta,
              COALESCE(a.documento_consultado, a.cpf_consultado) as documento_consultado,
              COALESCE(a.nome_consultado, '') as nome_consultado,
              COALESCE(a.situacao_cadastral, '') as situacao_cadastral,
              a.existe_base_cfo, a.inscricao_cfo, a.ip_origem, a.data_hora, a.sucesso, a.tempo_resposta_ms,
              a.finalidade_consulta, a.conselho_uf, a.nivel_risco, a.indicador_irregularidade,
              u.name as usuario_nome_completo
              FROM tbl_rfb_auditoria a
              LEFT JOIN tbl_users u ON a.usuario_id = u.id
              WHERE $whereClause
              ORDER BY a.data_hora DESC
              LIMIT 1000";

    $stmt = $con->prepare($query);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $auditorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estatísticas para o bloco de resumo
    $queryStats = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN COALESCE(tipo_consulta, 'CPF') = 'CPF' THEN 1 ELSE 0 END) as total_cpf,
                   SUM(CASE WHEN tipo_consulta = 'CNPJ' THEN 1 ELSE 0 END) as total_cnpj,
                   SUM(CASE WHEN existe_base_cfo = 1 THEN 1 ELSE 0 END) as total_cfo,
                   SUM(CASE WHEN sucesso = 1 THEN 1 ELSE 0 END) as total_sucesso,
                   SUM(CASE WHEN indicador_irregularidade = 1 THEN 1 ELSE 0 END) as total_irregular
                   FROM tbl_rfb_auditoria a WHERE $whereClause";
    $stmtStats = $con->prepare($queryStats);
    foreach ($params as $k => $v) {
        $stmtStats->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro gerar-pdf-rfb-log: " . $e->getMessage());
    $auditorias = [];
    $stats = ['total' => 0, 'total_cpf' => 0, 'total_cnpj' => 0, 'total_cfo' => 0, 'total_sucesso' => 0, 'total_irregular' => 0];
}

$usuarioGerador = Session::get('name') ?? 'Usuário';
$dataGeracao = date('d/m/Y H:i:s');
$periodoLabel = $filtroPeriodo == '1' ? 'Hoje' : ($filtroPeriodo == 'todos' ? 'Todos' : "Últimos {$filtroPeriodo} dias");

function h($s, $d = '-') {
    if ($s === null || $s === false || $s === '') return $d;
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function abrev($s, $max = 28) {
    $s = trim($s ?: '');
    return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 3) . '...' : $s;
}
function fmtDoc($doc) {
    $d = preg_replace('/\D/', '', $doc);
    if (strlen($d) == 11) return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2);
    if (strlen($d) == 14) return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2);
    return $doc;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Consultas RFB - Auditoria</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            @page { margin: 0.5cm; size: A4 landscape; }
            body { margin: 0; padding: 4px; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7.5pt; line-height: 1.2; color: #222; }
        .header { text-align: center; border-bottom: 2px solid #8D0F12; padding-bottom: 6px; margin-bottom: 6px; }
        .header h1 { color: #8D0F12; font-size: 12pt; }
        .header h2 { color: #555; font-size: 9pt; font-weight: normal; margin-top: 2px; }
        .info { font-size: 7pt; color: #666; margin-top: 4px; }
        .resumo { background: #f5f5f5; border-left: 3px solid #8D0F12; padding: 6px 8px; margin-bottom: 6px; font-size: 7pt; }
        .resumo strong { color: #8D0F12; }
        table { width: 100%; border-collapse: collapse; font-size: 6.5pt; }
        th { background: #8D0F12; color: white; padding: 3px 4px; text-align: left; font-weight: bold; }
        td { border: 1px solid #ddd; padding: 2px 4px; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 8px; padding-top: 4px; border-top: 1px solid #8D0F12; font-size: 6pt; text-align: center; color: #666; }
        .lgpd { background: #fff3cd; border: 1px solid #ffc107; padding: 4px; margin-top: 6px; font-size: 6pt; text-align: center; }
        .buttons { text-align: center; margin: 20px 0; }
        .btn { padding: 10px 24px; margin: 0 5px; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .btn-primary { background: #8D0F12; color: white; }
        .btn-secondary { background: #666; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CONSELHO FEDERAL DE ODONTOLOGIA</h1>
        <h2>Relatório de Consultas à RFB – Auditoria</h2>
        <div class="info">
            Período: <strong><?= h($periodoLabel) ?></strong> |
            Gerado em: <strong><?= h($dataGeracao) ?></strong> |
            Usuário: <strong><?= h($usuarioGerador) ?></strong>
        </div>
    </div>

    <div class="resumo">
        <strong>Resumo:</strong>
        Total: <?= (int)($stats['total'] ?? 0) ?> |
        CPF: <?= (int)($stats['total_cpf'] ?? 0) ?> | CNPJ: <?= (int)($stats['total_cnpj'] ?? 0) ?> |
        Na Base CFO: <?= (int)($stats['total_cfo'] ?? 0) ?> |
        Sucesso: <?= (int)($stats['total_sucesso'] ?? 0) ?> |
        <?php if (isset($stats['total_irregular']) && $stats['total_irregular'] > 0): ?>
        <span style="color:#c00;">Irregularidades: <?= (int)$stats['total_irregular'] ?></span>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Tp</th>
                <th>Doc</th>
                <th>Nome</th>
                <th>Sit</th>
                <th>CFO</th>
                <th>Insc</th>
                <th>Usr</th>
                <th>Grp</th>
                <th>IP</th>
                <th>T(ms)</th>
                <th>St</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($auditorias as $a): ?>
            <?php
                $doc = $a['documento_consultado'] ?? '';
                $usr = abrev($a['usuario_nome'] ?? $a['usuario_nome_completo'] ?? '-', 18);
                $grp = abrev(($a['usuario_grupo'] ?? '') . '/' . ($a['usuario_subgrupo'] ?? ''), 16);
            ?>
            <tr>
                <td><?= h(date('d/m/Y H:i', strtotime($a['data_hora']))) ?></td>
                <td><?= h($a['tipo_consulta'] ?? 'CPF') ?></td>
                <td class="text-monospace"><?= h(fmtDoc($doc)) ?></td>
                <td><?= h(abrev($a['nome_consultado'] ?? '-', 30)) ?></td>
                <td><?= h(abrev($a['situacao_cadastral'] ?? '-', 12)) ?></td>
                <td><?= !empty($a['existe_base_cfo']) ? 'Sim' : 'Não' ?></td>
                <td><?= h($a['inscricao_cfo'] ?? '-') ?></td>
                <td><?= h($usr) ?></td>
                <td><?= h($grp) ?></td>
                <td><small><?= h($a['ip_origem'] ?? '-') ?></small></td>
                <td><?= (int)($a['tempo_resposta_ms'] ?? 0) ?></td>
                <td><?= !empty($a['sucesso']) ? 'OK' : 'Erro' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($auditorias)): ?>
            <tr><td colspan="12" style="text-align:center;padding:12px;">Nenhum registro encontrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="lgpd">
        <strong>AVISO LGPD:</strong> Dados sensíveis protegidos pela Lei 13.709/2018. Uso restrito e auditado.
        Documento com validade legal conforme MP 2.200-2/2001. Sistema de Consultas CFO.
    </div>

    <div class="footer">
        Documento gerado eletronicamente em <?= h($dataGeracao) ?> | Sistema de Consultas CFO v1.2
    </div>

    <div class="buttons no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimir / Salvar PDF</button>
        <button onclick="window.close()" class="btn btn-secondary">Fechar</button>
    </div>
</body>
</html>

</body>
</html>
iv>
</body>
</html>

</body>
</html>
ir / Salvar PDF</button>
        <button onclick="window.close()" class="btn btn-secondary">Fechar</button>
    </div>
</body>
</html>

</body>
</html>
iv>
</body>
</html>

</body>
</html>
</html>
ml>
