<?php
/**
 * Exportação de logs RFB para Excel
 * Exporta os 500 primeiros registros
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database1;
use PDO;
use PDOException;

Session::CheckSession();

// VERIFICAÇÃO DE PERMISSÃO
if (!Helper::temPermissaoGerenciarRFB()) {
    die('Acesso negado. Você não tem permissão para exportar logs.');
}

$db = Database1::getInstance();
$con = $db->getConnection();

try {
    // Buscar os 500 primeiros registros
    $query = "SELECT
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
                a.nome_consultado,
                a.situacao_cadastral,
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
               ORDER BY a.data_hora DESC
               LIMIT 500";
    
    $stmt = $con->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar logs para exportação: " . $e->getMessage());
    die('Erro ao buscar dados para exportação.');
}

// Configurar headers para download Excel
$filename = 'logs_rfb_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 para Excel reconhecer corretamente
echo "\xEF\xBB\xBF";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <table border="1">
        <thead>
            <tr style="background-color: #8D0F12; color: white; font-weight: bold;">
                <th>ID</th>
                <th>Data/Hora</th>
                <th>Tipo</th>
                <th>Usuário ID</th>
                <th>Nome do Usuário</th>
                <th>Email</th>
                <th>Grupo</th>
                <th>Subgrupo</th>
                <th>CPF/CNPJ Consultado</th>
                <th>Nome/Razão Social</th>
                <th>Situação Cadastral</th>
                <th>Existe na Base CFO?</th>
                <th>Inscrição CFO</th>
                <th>Nome CFO</th>
                <th>CRO CFO</th>
                <th>IP Origem</th>
                <th>Tempo Resposta (ms)</th>
                <th>Sucesso</th>
                <th>Mensagem Erro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <?php 
                    $tipoConsulta = $log['tipo_consulta'] ?? 'CPF';
                    $documento = $log['documento_consultado'] ?? $log['cpf_consultado'] ?? '';
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
                    <td><?= $log['id'] ?></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                    <td><?= htmlspecialchars($tipoConsulta) ?></td>
                    <td><?= htmlspecialchars($log['usuario_id']) ?></td>
                    <td><?= htmlspecialchars($log['usuario_nome'] ?? $log['usuario_nome_completo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['usuario_email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['usuario_grupo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['usuario_subgrupo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($docFormatado) ?></td>
                    <td><?= htmlspecialchars($log['nome_consultado'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['situacao_cadastral'] ?? '') ?></td>
                    <td><?= $log['existe_base_cfo'] ? 'Sim' : 'Não' ?></td>
                    <td><?= htmlspecialchars($log['inscricao_cfo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['nome_cfo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['cro_cfo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['ip_origem'] ?? '') ?></td>
                    <td><?= number_format($log['tempo_resposta_ms'], 0) ?></td>
                    <td><?= $log['sucesso'] ? 'Sim' : 'Não' ?></td>
                    <td><?= htmlspecialchars($log['mensagem_erro'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

