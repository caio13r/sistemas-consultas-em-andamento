<?php
/**
 * Geração de PDF profissional para consultas RFB
 * Formato institucional CFO
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database1;
use PDO;

Session::CheckSession();

// Verificar se tem os dados na sessão ou via GET
$consultaId = $_GET['id'] ?? null;
$dadosJSON = $_GET['dados'] ?? null;

if (!$consultaId && !$dadosJSON) {
    die('Erro: Dados da consulta não encontrados.');
}

// Se tiver ID, buscar do banco
if ($consultaId) {
    $db = Database1::getInstance();
    $con = $db->getConnection();

    $query = "SELECT * FROM tbl_rfb_auditoria WHERE id = :id AND usuario_id = :usuario_id";
    $stmt = $con->prepare($query);
    $stmt->execute([
        ':id' => $consultaId,
        ':usuario_id' => Session::get('id')
    ]);

    $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consulta) {
        die('Erro: Consulta não encontrada ou sem permissão.');
    }

    // Reconstruir dados do resultado
    $resultado = [
        'tipo' => $consulta['tipo_consulta'],
        'documento' => $consulta['documento_consultado'],
        'nome' => $consulta['nome_consultado'],
        'situacao_cadastral' => $consulta['situacao_cadastral'],
        'dados_cfo' => null
    ];

    if ($consulta['existe_base_cfo']) {
        $resultado['dados_cfo'] = [
            'Inscricao' => $consulta['inscricao_cfo'],
            'Nome' => $consulta['nome_cfo'],
            'CRO' => $consulta['cro_cfo'],
            'Situacao' => $consulta['situacao_cfo']
        ];
    }

    // Parsear XML completo para pegar detalhes
    if (!empty($consulta['xml_completo'])) {
        try {
            $xml = simplexml_load_string($consulta['xml_completo']);
            // Extrair dados conforme tipo
            // (implementar parseamento completo conforme necessário)
        } catch (Exception $e) {
            // Ignorar erros de parsing
        }
    }
} else {
    // Dados vêm via GET (base64 encoded JSON)
    $resultado = json_decode(base64_decode($dadosJSON), true);
}

// Usar sempre HTML (funciona perfeitamente sem dependências)
gerarPDFcomHTML($resultado);

/**
 * Gera PDF usando TCPDF
 */
function gerarPDFcomTCPDF($resultado)
{
    require_once __DIR__ . '/../../lib/CFOPDF.php';

    use Cfo\SisConsultas\lib\CFOPDF;
    use Cfo\SisConsultas\lib\Session;

    $tipo = $resultado['tipo'];
    $usuario = Session::get('name');

    $pdf = new CFOPDF($tipo, $usuario);

    $pdf->AddPage();

    // Título do documento
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(141, 15, 18);
    $pdf->Cell(0, 10, 'RELATÓRIO DE CONSULTA - RECEITA FEDERAL DO BRASIL', 0, 1, 'C');
    $pdf->Ln(5);

    // Info CFO (se existir)
    if (!empty($resultado['dados_cfo'])) {
        $pdf->AddAlert('Documento encontrado na base de dados do CFO', 'success');
        $pdf->AddSection('Informações na Base CFO');
        $pdf->AddField('Inscrição CFO', $resultado['dados_cfo']['Inscricao'], 50);
        $pdf->AddField('Nome/Razão Social', $resultado['dados_cfo']['Nome'], 50);
        $pdf->AddField('CRO', $resultado['dados_cfo']['CRO'], 50);
        $pdf->AddField('Situação', $resultado['dados_cfo']['Situacao'], 50);
    }

    // Dados da RFB
    $pdf->AddSection('Dados Cadastrais - Receita Federal');

    if ($tipo === 'CPF') {
        gerarSecaoCPF($pdf, $resultado);
    } else {
        gerarSecaoCNPJ($pdf, $resultado);
    }

    // Rodapé final
    $pdf->Ln(10);
    $pdf->AddAlert('Este documento foi gerado eletronicamente pelo Sistema de Consultas do CFO e possui validade legal conforme MP 2.200-2/2001.', 'info');

    // Output
    $nomeArquivo = 'Consulta_RFB_' . $tipo . '_' . date('Ymd_His') . '.pdf';
    $pdf->Output($nomeArquivo, 'D'); // D = Download
}

/**
 * Seção CPF no PDF
 */
function gerarSecaoCPF($pdf, $resultado)
{
    $pdf->AddField('CPF', $resultado['cpf'] ?? $resultado['documento'], 50);
    $pdf->AddField('Nome', $resultado['nome'], 50);
    $pdf->AddField('Situação Cadastral', $resultado['situacao_cadastral'], 50);

    if (!empty($resultado['nome_mae'])) {
        $pdf->AddField('Nome da Mãe', $resultado['nome_mae'], 50);
    }

    if (!empty($resultado['data_nascimento'])) {
        $data = $resultado['data_nascimento'];
        $dataFormatada = substr($data, 6, 2) . '/' . substr($data, 4, 2) . '/' . substr($data, 0, 4);
        $pdf->AddField('Data de Nascimento', $dataFormatada, 50);
    }

    // Endereço
    if (!empty($resultado['logradouro'])) {
        $pdf->Ln(3);
        $pdf->AddSection('Endereço', '📍');
        $endereco = $resultado['tipo_logradouro'] . ' ' . $resultado['logradouro'] . ', ' . $resultado['numero_logradouro'];
        if (!empty($resultado['complemento'])) {
            $endereco .= ' - ' . $resultado['complemento'];
        }
        $pdf->AddField('Logradouro', $endereco, 50);
        $pdf->AddField('Bairro', $resultado['bairro'], 50);
        $pdf->AddField('Município/UF', $resultado['municipio'] . ' - ' . $resultado['uf'], 50);
        $pdf->AddField('CEP', $resultado['cep'], 50);
    }
}

/**
 * Seção CNPJ no PDF
 */
function gerarSecaoCNPJ($pdf, $resultado)
{
    $pdf->AddField('CNPJ', $resultado['cnpj'] ?? $resultado['documento'], 50);
    $pdf->AddField('Razão Social', $resultado['razao_social'] ?? $resultado['nome'], 50);

    if (!empty($resultado['nome_fantasia'])) {
        $pdf->AddField('Nome Fantasia', $resultado['nome_fantasia'], 50);
    }

    $pdf->AddField('Situação Cadastral', $resultado['situacao_cadastral'], 50);

    if (!empty($resultado['data_abertura'])) {
        $data = $resultado['data_abertura'];
        $dataFormatada = substr($data, 6, 2) . '/' . substr($data, 4, 2) . '/' . substr($data, 0, 4);
        $pdf->AddField('Data de Abertura', $dataFormatada, 50);
    }

    if (!empty($resultado['capital_social'])) {
        $pdf->AddField('Capital Social', 'R$ ' . number_format($resultado['capital_social'], 2, ',', '.'), 50);
    }

    // CNAE
    if (!empty($resultado['cnae_fiscal'])) {
        $pdf->Ln(3);
        $pdf->AddSection('Atividade Econômica', '📊');
        $pdf->AddField('CNAE Principal', $resultado['cnae_fiscal'], 50);
    }

    // Endereço
    if (!empty($resultado['logradouro'])) {
        $pdf->Ln(3);
        $pdf->AddSection('Endereço', '📍');
        $endereco = $resultado['tipo_logradouro'] . ' ' . $resultado['logradouro'] . ', ' . $resultado['numero_logradouro'];
        if (!empty($resultado['complemento'])) {
            $endereco .= ' - ' . $resultado['complemento'];
        }
        $pdf->AddField('Logradouro', $endereco, 50);
        $pdf->AddField('Bairro', $resultado['bairro'], 50);
        $pdf->AddField('Município/UF', $resultado['municipio'] . ' - ' . $resultado['uf'], 50);
        $pdf->AddField('CEP', $resultado['cep'], 50);
    }

    // QSA
    if (!empty($resultado['qsa']) && is_array($resultado['qsa'])) {
        $pdf->Ln(3);
        $pdf->AddSection('Quadro de Sócios e Administradores', '👥');

        $headers = ['Nome', 'Qualificação', 'Documento'];
        $widths = [70, 70, 40];
        $data = [];

        foreach ($resultado['qsa'] as $socio) {
            $data[] = [
                $socio['nome'] ?? '-',
                $socio['qualificacao_descricao'] ?? $socio['qualificacao'] ?? '-',
                $socio['documento'] ?? '-'
            ];
        }

        $pdf->AddTable($headers, $data, $widths);
    }
}

/**
 * Fallback: Gera HTML para impressão
 */
function gerarPDFcomHTML($resultado)
{
    $tipo = $resultado['tipo'];
    $usuario = Session::get('name');
    $data = date('d/m/Y H:i:s');

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Consulta RFB - <?= $tipo ?></title>
        <style>
            @media print {
                .no-print { display: none; }
            }
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #8D0F12;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            .header h1 {
                color: #8D0F12;
                margin: 5px 0;
                font-size: 20px;
            }
            .header h2 {
                color: #555;
                margin: 5px 0;
                font-size: 14px;
                font-weight: normal;
            }
            .section {
                margin: 20px 0;
                padding: 10px;
                background: #f5f5f5;
                border-left: 4px solid #8D0F12;
            }
            .section h3 {
                margin: 0 0 10px 0;
                color: #8D0F12;
                font-size: 14px;
            }
            .field {
                margin: 5px 0;
            }
            .field strong {
                color: #555;
            }
            .alert {
                padding: 10px;
                margin: 15px 0;
                border-left: 4px solid #28a745;
                background: #d4edda;
            }
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 2px solid #8D0F12;
                font-size: 10px;
                text-align: center;
                color: #666;
            }
            .lgpd {
                background: #fff3cd;
                border: 1px solid #ffc107;
                padding: 10px;
                margin: 20px 0;
                font-size: 10px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>CONSELHO FEDERAL DE ODONTOLOGIA</h1>
            <h2>Consulta à Receita Federal do Brasil</h2>
            <p>Tipo: <?= $tipo ?> | Data: <?= $data ?> | Usuário: <?= htmlspecialchars($usuario) ?></p>
        </div>

        <?php if (!empty($resultado['dados_cfo'])): ?>
        <div class="alert">
            <strong>✓ Documento encontrado na base CFO</strong><br>
            Inscrição: <?= htmlspecialchars($resultado['dados_cfo']['Inscricao']) ?> |
            Nome: <?= htmlspecialchars($resultado['dados_cfo']['Nome']) ?> |
            CRO: <?= htmlspecialchars($resultado['dados_cfo']['CRO']) ?>
        </div>
        <?php endif; ?>

        <div class="section">
            <h3>📋 Dados Cadastrais - Receita Federal</h3>
            <?php if ($tipo === 'CPF'): ?>
                <div class="field"><strong>CPF:</strong> <?= htmlspecialchars($resultado['cpf'] ?? $resultado['documento']) ?></div>
                <div class="field"><strong>Nome:</strong> <?= htmlspecialchars($resultado['nome']) ?></div>
                <div class="field"><strong>Situação Cadastral:</strong> <?= htmlspecialchars($resultado['situacao_cadastral']) ?></div>

                <?php if (!empty($resultado['nome_mae'])): ?>
                <div class="field"><strong>Nome da Mãe:</strong> <?= htmlspecialchars($resultado['nome_mae']) ?></div>
                <?php endif; ?>

                <?php if (!empty($resultado['data_nascimento'])): ?>
                <div class="field"><strong>Data de Nascimento:</strong> <?= substr($resultado['data_nascimento'], 6, 2) ?>/<?= substr($resultado['data_nascimento'], 4, 2) ?>/<?= substr($resultado['data_nascimento'], 0, 4) ?></div>
                <?php endif; ?>

                <?php if (!empty($resultado['sexo_descricao'])): ?>
                <div class="field"><strong>Sexo:</strong> <?= htmlspecialchars($resultado['sexo_descricao']) ?></div>
                <?php endif; ?>

            <?php else: ?>
                <div class="field"><strong>CNPJ:</strong> <?= htmlspecialchars($resultado['cnpj'] ?? $resultado['documento']) ?></div>
                <div class="field"><strong>Razão Social:</strong> <?= htmlspecialchars($resultado['razao_social'] ?? $resultado['nome']) ?></div>

                <?php if (!empty($resultado['nome_fantasia'])): ?>
                <div class="field"><strong>Nome Fantasia:</strong> <?= htmlspecialchars($resultado['nome_fantasia']) ?></div>
                <?php endif; ?>

                <div class="field"><strong>Situação Cadastral:</strong> <?= htmlspecialchars($resultado['situacao_cadastral']) ?></div>

                <?php if (!empty($resultado['data_abertura'])): ?>
                <div class="field"><strong>Data de Abertura:</strong> <?= substr($resultado['data_abertura'], 6, 2) ?>/<?= substr($resultado['data_abertura'], 4, 2) ?>/<?= substr($resultado['data_abertura'], 0, 4) ?></div>
                <?php endif; ?>

                <?php if (!empty($resultado['capital_social'])): ?>
                <div class="field"><strong>Capital Social:</strong> R$ <?= number_format($resultado['capital_social'], 2, ',', '.') ?></div>
                <?php endif; ?>

                <?php if (!empty($resultado['descricao_porte'])): ?>
                <div class="field"><strong>Porte:</strong> <?= htmlspecialchars($resultado['descricao_porte']) ?></div>
                <?php endif; ?>

                <?php if (!empty($resultado['cnae_fiscal'])): ?>
                <div class="field"><strong>CNAE Principal:</strong> <?= htmlspecialchars($resultado['cnae_fiscal']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($resultado['logradouro'])): ?>
        <div class="section">
            <h3>📍 Endereço</h3>
            <div class="field"><strong>Logradouro:</strong> <?= htmlspecialchars($resultado['tipo_logradouro']) ?> <?= htmlspecialchars($resultado['logradouro']) ?>, Nº <?= htmlspecialchars($resultado['numero_logradouro']) ?></div>
            <?php if (!empty($resultado['complemento'])): ?>
            <div class="field"><strong>Complemento:</strong> <?= htmlspecialchars($resultado['complemento']) ?></div>
            <?php endif; ?>
            <div class="field"><strong>Bairro:</strong> <?= htmlspecialchars($resultado['bairro']) ?></div>
            <div class="field"><strong>Município/UF:</strong> <?= htmlspecialchars($resultado['municipio']) ?> - <?= htmlspecialchars($resultado['uf']) ?></div>
            <div class="field"><strong>CEP:</strong> <?= htmlspecialchars($resultado['cep']) ?></div>
            <?php if (!empty($resultado['ddd']) && !empty($resultado['telefone'])): ?>
            <div class="field"><strong>Telefone:</strong> (<?= htmlspecialchars($resultado['ddd']) ?>) <?= htmlspecialchars($resultado['telefone']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['email'])): ?>
            <div class="field"><strong>Email:</strong> <?= htmlspecialchars($resultado['email']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado['qsa']) && is_array($resultado['qsa'])): ?>
        <div class="section">
            <h3>👥 Quadro de Sócios e Administradores</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="background: #8D0F12; color: white;">
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Nome</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Qualificação</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Documento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['qsa'] as $socio): ?>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($socio['nome'] ?? '-') ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><small><?= htmlspecialchars($socio['qualificacao_descricao'] ?? $socio['qualificacao'] ?? '-') ?></small></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><small><?= htmlspecialchars($socio['documento'] ?? '-') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="lgpd">
            <strong>AVISO LGPD:</strong> Este documento contém dados sensíveis protegidos pela Lei 13.709/2018.
            Uso restrito e auditado. Distribuição não autorizada é proibida.
        </div>

        <div class="footer">
            Sistema de Consultas CFO - Documento gerado eletronicamente<br>
            Possui validade legal conforme MP 2.200-2/2001
        </div>

        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="padding: 10px 30px; background: #8D0F12; color: white; border: none; cursor: pointer; font-size: 14px;">
                Imprimir / Salvar PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 30px; background: #666; color: white; border: none; cursor: pointer; font-size: 14px; margin-left: 10px;">
                Fechar
            </button>
        </div>

        <script>
            // Auto-print quando carregar
            // window.onload = function() { window.print(); };
        </script>
    </body>
    </html>
    <?php
    exit;
}
