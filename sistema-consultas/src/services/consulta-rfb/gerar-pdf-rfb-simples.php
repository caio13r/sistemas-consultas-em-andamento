<?php
/**
 * Geração de PDF simplificado - HTML para impressão
 * Sem dependências externas
 */

// Configurar encoding UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Ativar erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Não precisa de autoload para versão HTML simples
session_start();

// Verificar se usuário está logado
if (empty($_SESSION['id'])) {
    die('Erro: Acesso não autorizado. Faça login primeiro.');
}

// Pegar dados da URL
$dadosJSON = $_GET['dados'] ?? null;

if (!$dadosJSON) {
    die('Erro: Dados da consulta não encontrados.');
}

// Decodificar dados
try {
    // Decodificar base64 (pode vir codificado como URL-safe base64)
    // Restaurar caracteres especiais do base64 se foram substituídos
    $dadosJSON = str_replace(['-', '_'], ['+', '/'], $dadosJSON);
    
    // Adicionar padding se necessário (base64 precisa de múltiplos de 4)
    $padding = strlen($dadosJSON) % 4;
    if ($padding) {
        $dadosJSON .= str_repeat('=', 4 - $padding);
    }
    
    $dadosDecodificados = base64_decode($dadosJSON, true);
    
    if ($dadosDecodificados === false) {
        throw new Exception('Erro ao decodificar base64. String inválida.');
    }
    
    // Limpar caracteres inválidos que podem causar problemas
    $dadosDecodificados = mb_convert_encoding($dadosDecodificados, 'UTF-8', 'UTF-8');
    
    // Remover caracteres de controle inválidos, mas manter quebras de linha e tabs se necessário
    $dadosDecodificados = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $dadosDecodificados);
    
    // Decodificar JSON com UTF-8 explícito
    $resultado = json_decode($dadosDecodificados, true, 512, JSON_INVALID_UTF8_IGNORE);
    
    if ($resultado === null) {
        $jsonError = json_last_error_msg();
        $jsonErrorCode = json_last_error();
        
        // Debug: logar parte dos dados para análise
        $debugInfo = substr($dadosDecodificados, 0, 200);
        error_log("Erro JSON decode: " . $jsonError . " (código: " . $jsonErrorCode . ")");
        error_log("Dados recebidos (primeiros 200 chars): " . $debugInfo);
        
        throw new Exception('Erro ao decodificar JSON: ' . $jsonError . ' (código: ' . $jsonErrorCode . ')');
    }
    
    // Validar se tem os campos essenciais
    if (empty($resultado['tipo']) || !isset($resultado['sucesso'])) {
        throw new Exception('Dados incompletos. Campo "tipo" ou "sucesso" não encontrado.');
    }
    
    // Limpar dados do resultado para evitar problemas de encoding
    $resultado = array_map(function($value) {
        if (is_string($value)) {
            // Garantir que a string está em UTF-8 válido
            if (!mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
            return $value;
        } elseif (is_array($value)) {
            return array_map(function($v) {
                if (is_string($v)) {
                    if (!mb_check_encoding($v, 'UTF-8')) {
                        $v = mb_convert_encoding($v, 'UTF-8', 'auto');
                    }
                }
                return $v;
            }, $value);
        }
        return $value;
    }, $resultado);
    
} catch (Exception $e) {
    error_log("Erro ao processar dados PDF: " . $e->getMessage());
    // Usar htmlspecialchars diretamente pois a função h() ainda não foi definida
    die('Erro ao processar dados: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

$tipo = $resultado['tipo'] ?? 'CPF';
$usuario = $_SESSION['name'] ?? 'Usuário';
$data = date('d/m/Y H:i:s');

// Função helper para garantir UTF-8 em htmlspecialchars
function h($string, $default = '') {
    if ($string === null || $string === false) {
        return $default;
    }
    // Converter para string se necessário
    if (!is_string($string)) {
        $string = (string)$string;
    }
    if (empty($string)) {
        return $default;
    }
    // Garantir que está em UTF-8 válido
    if (!mb_check_encoding($string, 'UTF-8')) {
        $string = mb_convert_encoding($string, 'UTF-8', 'auto');
    }
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta RFB - <?= $tipo ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            @page { 
                margin: 0.3cm; 
                size: A4 portrait;
                size: 21cm 29.7cm;
            }
            html, body {
                width: 20.4cm;
                height: 29.1cm;
                overflow: hidden;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            margin: 0;
            padding: 2px;
            font-size: 8.5pt;
            color: #333;
            line-height: 1.25;
        }

        .container {
            max-width: 100%;
            margin: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #8D0F12;
            padding-bottom: 4px;
            margin-bottom: 5px;
            flex-shrink: 0;
        }

        .header h1 {
            color: #8D0F12;
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            line-height: 1.15;
        }

        .header h2 {
            color: #555;
            margin: 2px 0;
            font-size: 10pt;
            font-weight: normal;
            line-height: 1.15;
        }

        .header .info {
            color: #666;
            font-size: 7.5pt;
            margin-top: 3px;
            line-height: 1.15;
        }

        .content-wrapper {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .section {
            margin: 4px 0;
            padding: 4px 6px;
            background: #f8f9fa;
            border-left: 3px solid #8D0F12;
            page-break-inside: avoid;
            flex-shrink: 0;
        }

        .section h3 {
            margin: 0 0 3px 0;
            color: #8D0F12;
            font-size: 9pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .field {
            margin: 2px 0;
            line-height: 1.3;
            font-size: 8pt;
            display: inline-block;
            width: 49%;
            vertical-align: top;
            padding-right: 5px;
        }

        .field.full-width {
            width: 100%;
        }

        .field strong {
            color: #555;
            font-weight: bold;
            font-size: 8pt;
        }

        .field small {
            font-size: 7pt;
        }

        .alert-success {
            padding: 3px 5px;
            margin: 3px 0;
            border-left: 3px solid #28a745;
            background: #d4edda;
            color: #155724;
            font-size: 7.5pt;
            line-height: 1.2;
            flex-shrink: 0;
        }

        .alert-success strong {
            font-weight: bold;
        }

        .lgpd {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 3px solid #ffc107;
            padding: 3px 5px;
            margin: 4px 0;
            font-size: 7pt;
            text-align: center;
            page-break-inside: avoid;
            line-height: 1.2;
            flex-shrink: 0;
        }

        .footer {
            margin-top: 4px;
            padding-top: 3px;
            border-top: 1px solid #8D0F12;
            font-size: 7pt;
            text-align: center;
            color: #666;
            line-height: 1.2;
            flex-shrink: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 3px 0;
            font-size: 7pt;
        }

        table th {
            background: #8D0F12;
            color: white;
            padding: 3px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 7pt;
        }

        table td {
            border: 1px solid #ddd;
            padding: 3px 4px;
            font-size: 7pt;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .text-monospace {
            font-family: 'Courier New', monospace;
            font-size: 7pt;
        }

        ul {
            margin: 2px 0 2px 15px;
            padding: 0;
            font-size: 7.5pt;
        }

        li {
            margin: 1px 0;
            line-height: 1.2;
        }

        .buttons {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
        }

        .btn {
            padding: 12px 30px;
            margin: 0 5px;
            border: none;
            cursor: pointer;
            font-size: 13pt;
            border-radius: 5px;
            font-weight: bold;
        }

        .btn-primary {
            background: #8D0F12;
            color: white;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CONSELHO FEDERAL DE ODONTOLOGIA</h1>
            <h2>Consulta à Receita Federal do Brasil</h2>
            <div class="info">
                Tipo: <strong><?= h($tipo) ?></strong> |
                Data: <strong><?= $data ?></strong> |
                Usuário: <strong><?= h($usuario) ?></strong>
            </div>
        </div>

        <div class="content-wrapper">
        <?php if (!empty($resultado['dados_cfo'])): ?>
        <div class="alert-success">
            <strong>✓ Documento encontrado na base CFO</strong><br>
            <div style="margin-top: 2px;">
                Inscrição: <?= h($resultado['dados_cfo']['Inscricao'] ?? '-') ?> |
                Nome: <?= h($resultado['dados_cfo']['Nome'] ?? '-') ?> |
                CRO: <?= h($resultado['dados_cfo']['CRO'] ?? '-') ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- DADOS CADASTRAIS CPF -->
        <?php if ($tipo === 'CPF'): ?>
        <div class="section">
            <h3>📋 Identificação - Pessoa Física</h3>
            <div class="field"><strong>CPF:</strong> <?= h($resultado['cpf'] ?? $resultado['documento'] ?? '-') ?></div>
            <div class="field"><strong>Situação:</strong> <?= h($resultado['situacao_cadastral'] ?? '-') ?>
                <?php if (!empty($resultado['situacao_cadastral_codigo'])): ?>
                    <small>(<?= h($resultado['situacao_cadastral_codigo']) ?>)</small>
                <?php endif; ?>
            </div>
            <div class="field full-width"><strong>Nome:</strong> <?= h($resultado['nome'] ?? '-') ?></div>
            <?php if (!empty($resultado['nome_mae'])): ?>
            <div class="field full-width"><strong>Nome da Mãe:</strong> <?= h($resultado['nome_mae']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['data_nascimento'])): ?>
            <div class="field"><strong>Nascimento:</strong> <?= substr($resultado['data_nascimento'], 6, 2) ?>/<?= substr($resultado['data_nascimento'], 4, 2) ?>/<?= substr($resultado['data_nascimento'], 0, 4) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['sexo_descricao'])): ?>
            <div class="field"><strong>Sexo:</strong> <?= h($resultado['sexo_descricao']) ?>
                <?php if (!empty($resultado['sexo'])): ?>
                    <small>(<?= h($resultado['sexo']) ?>)</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($resultado['ano_obito']) && $resultado['ano_obito'] != '0000'): ?>
            <div class="field"><strong>Ano Óbito:</strong> <?= h($resultado['ano_obito']) ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($resultado['residente_exterior']) && $resultado['residente_exterior'] == 'S'): ?>
        <div class="section">
            <h3>🌍 Residência no Exterior</h3>
            <div class="field"><strong>Código País:</strong> <?= h($resultado['codigo_pais_exterior'] ?? '-') ?></div>
            <div class="field"><strong>País:</strong> <?= h($resultado['nome_pais_exterior'] ?? '-') ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado['estrangeiro']) || !empty($resultado['nome_pais_nacionalidade'])): ?>
        <div class="section">
            <h3>🌎 Nacionalidade</h3>
            <div class="field"><strong>Estrangeiro:</strong> <?= $resultado['estrangeiro'] == 'S' ? 'Sim' : 'Não' ?></div>
            <?php if (!empty($resultado['cod_pais_nacionalidade'])): ?>
            <div class="field"><strong>Código:</strong> <?= h($resultado['cod_pais_nacionalidade']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['nome_pais_nacionalidade'])): ?>
            <div class="field"><strong>País:</strong> <?= h($resultado['nome_pais_nacionalidade']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado['nome_municipio_naturalidade'])): ?>
        <div class="section">
            <h3>📍 Naturalidade</h3>
            <div class="field"><strong>Município:</strong> <?= h($resultado['nome_municipio_naturalidade']) ?></div>
            <?php if (!empty($resultado['uf_municipio_naturalidade'])): ?>
            <div class="field"><strong>UF:</strong> <?= h($resultado['uf_municipio_naturalidade']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['codigo_municipio_naturalidade'])): ?>
            <div class="field"><strong>Código:</strong> <?= h($resultado['codigo_municipio_naturalidade']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado['ocupacao_principal']) || !empty($resultado['natureza_ocupacao'])): ?>
        <div class="section">
            <h3>💼 Ocupação</h3>
            <?php if (!empty($resultado['ocupacao_principal'])): ?>
            <?php 
                // Usar descrição se disponível, senão usar o campo original
                $ocupacaoPrincipal = !empty($resultado['ocupacao_principal_descricao']) && $resultado['ocupacao_principal_descricao'] != $resultado['ocupacao_principal']
                    ? $resultado['ocupacao_principal_descricao'] 
                    : $resultado['ocupacao_principal'];
            ?>
            <div class="field full-width"><strong>Ocupação Principal:</strong> <?= h($ocupacaoPrincipal) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['natureza_ocupacao'])): ?>
            <?php 
                // Usar descrição se disponível, senão usar o campo original
                $naturezaOcupacao = !empty($resultado['natureza_ocupacao_descricao']) && $resultado['natureza_ocupacao_descricao'] != $resultado['natureza_ocupacao']
                    ? $resultado['natureza_ocupacao_descricao'] 
                    : $resultado['natureza_ocupacao'];
            ?>
            <div class="field"><strong>Natureza da Ocupação:</strong> <?= h($naturezaOcupacao) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['exercicio_ocupacao'])): ?>
            <?php 
                // Usar descrição se disponível, senão usar o campo original
                $exercicioOcupacao = !empty($resultado['exercicio_ocupacao_descricao']) && $resultado['exercicio_ocupacao_descricao'] != $resultado['exercicio_ocupacao']
                    ? $resultado['exercicio_ocupacao_descricao'] 
                    : $resultado['exercicio_ocupacao'];
            ?>
            <div class="field"><strong>Exercício da Ocupação:</strong> <?= h($exercicioOcupacao) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- DADOS CADASTRAIS CNPJ -->
        <?php else: ?>
        <div class="section">
            <h3>📋 Identificação - Pessoa Jurídica</h3>
            <div class="field"><strong>CNPJ:</strong> <?= h($resultado['cnpj'] ?? $resultado['documento'] ?? '-') ?></div>
            <?php if (!empty($resultado['estabelecimento'])): ?>
            <div class="field"><strong>Estab:</strong> <?= h($resultado['estabelecimento']) ?></div>
            <?php endif; ?>
            <div class="field full-width"><strong>Razão Social:</strong> <?= h($resultado['razao_social'] ?? $resultado['nome'] ?? '-') ?></div>
            <?php if (!empty($resultado['nome_fantasia'])): ?>
            <div class="field full-width"><strong>Nome Fantasia:</strong> <?= h($resultado['nome_fantasia']) ?></div>
            <?php endif; ?>
            <div class="field"><strong>Situação:</strong> <?= h($resultado['situacao_cadastral'] ?? '-') ?>
                <?php if (!empty($resultado['situacao_cadastral_codigo'])): ?>
                    <small>(<?= h($resultado['situacao_cadastral_codigo']) ?>)</small>
                <?php endif; ?>
            </div>
            <?php if (!empty($resultado['motivo_situacao_cadastral'])): ?>
            <div class="field"><strong>Motivo:</strong> <?= h($resultado['motivo_situacao_cadastral']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['data_situacao_cadastral'])): ?>
            <div class="field"><strong>Data Situação:</strong> <?= substr($resultado['data_situacao_cadastral'], 6, 2) ?>/<?= substr($resultado['data_situacao_cadastral'], 4, 2) ?>/<?= substr($resultado['data_situacao_cadastral'], 0, 4) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['data_abertura'])): ?>
            <div class="field"><strong>Data Abertura:</strong> <?= substr($resultado['data_abertura'], 6, 2) ?>/<?= substr($resultado['data_abertura'], 4, 2) ?>/<?= substr($resultado['data_abertura'], 0, 4) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['capital_social'])): ?>
            <div class="field"><strong>Capital Social:</strong> R$ <?= number_format($resultado['capital_social'], 2, ',', '.') ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['descricao_porte'])): ?>
            <div class="field"><strong>Porte:</strong> <?= h($resultado['descricao_porte']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['codigo_natureza_juridica']) || !empty($resultado['natureza_juridica'])): ?>
            <div class="field"><strong>Natureza Jurídica:</strong> <?= h($resultado['codigo_natureza_juridica'] ?? '') ?> <?= !empty($resultado['natureza_juridica']) ? '- ' . h($resultado['natureza_juridica']) : '' ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($resultado['cnae_fiscal']) || !empty($resultado['cnaes_secundarios'])): ?>
        <div class="section">
            <h3>📈 Atividade Econômica</h3>
            <?php if (!empty($resultado['cnae_fiscal'])): ?>
            <div class="field"><strong>CNAE Principal:</strong> <?= h($resultado['cnae_fiscal']) ?></div>
            <?php if (!empty($resultado['descricao_cnae_fiscal'])): ?>
            <div class="field"><strong>Descrição:</strong> <?= h($resultado['descricao_cnae_fiscal']) ?></div>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($resultado['cnaes_secundarios']) && is_array($resultado['cnaes_secundarios']) && count($resultado['cnaes_secundarios']) > 0): ?>
            <div class="field full-width"><strong>CNAEs Secundários:</strong> <?= implode(', ', array_slice($resultado['cnaes_secundarios'], 0, 5)) ?><?= count($resultado['cnaes_secundarios']) > 5 ? '...' : '' ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado['opcao_simples']) || !empty($resultado['opcao_mei']) || !empty($resultado['situacao_especial']) || !empty($resultado['cidade_exterior']) || !empty($resultado['nome_pais']) || !empty($resultado['cpf_responsavel'])): ?>
        <div class="section">
            <h3>📑 Informações Complementares</h3>
            <?php if (!empty($resultado['opcao_simples'])): ?>
            <div class="field"><strong>Simples Nacional:</strong> <?= ($resultado['opcao_simples'] ?? 'N') == 'S' ? 'Sim' : 'Não' ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['opcao_mei'])): ?>
            <div class="field"><strong>MEI:</strong> <?= ($resultado['opcao_mei'] ?? 'N') == 'S' ? 'Sim' : 'Não' ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['situacao_especial'])): ?>
            <div class="field"><strong>Situação Especial:</strong> <?= h($resultado['situacao_especial']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['cidade_exterior'])): ?>
            <div class="field"><strong>Cidade Exterior:</strong> <?= h($resultado['cidade_exterior']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['nome_pais'])): ?>
            <div class="field"><strong>País:</strong> <?= h($resultado['nome_pais']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['cpf_responsavel'])): ?>
            <div class="field"><strong>Responsável CPF:</strong> <?= h($resultado['cpf_responsavel']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['nome_responsavel'])): ?>
            <div class="field full-width"><strong>Responsável Nome:</strong> <?= h($resultado['nome_responsavel']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($resultado['logradouro'])): ?>
        <div class="section">
            <h3>📍 Endereço</h3>
            <div class="field full-width"><strong>Logradouro:</strong> <?= h($resultado['tipo_logradouro'] ?? '') ?> <?= h($resultado['logradouro']) ?>, Nº <?= h($resultado['numero_logradouro'] ?? 'S/N') ?></div>
            <?php if (!empty($resultado['complemento'])): ?>
            <div class="field"><strong>Complemento:</strong> <?= h($resultado['complemento']) ?></div>
            <?php endif; ?>
            <div class="field"><strong>Bairro:</strong> <?= h($resultado['bairro'] ?? '-') ?></div>
            <div class="field"><strong>Município/UF:</strong> <?= h($resultado['municipio'] ?? '-') ?> - <?= h($resultado['uf'] ?? '-') ?></div>
            <div class="field"><strong>CEP:</strong> <?= h($resultado['cep'] ?? '-') ?></div>
            <?php if (!empty($resultado['codigo_municipio'])): ?>
            <div class="field"><strong>Código Município:</strong> <?= h($resultado['codigo_municipio']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['referencia'])): ?>
            <div class="field full-width"><strong>Referência:</strong> <?= h($resultado['referencia']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado['ddd']) || !empty($resultado['ddd2']) || !empty($resultado['telefone']) || !empty($resultado['telefone2']) || !empty($resultado['email'])): ?>
        <div class="section">
            <h3>📞 Contato</h3>
            <?php if (!empty($resultado['ddd']) && !empty($resultado['telefone'])): ?>
            <div class="field"><strong>Tel 1:</strong> (<?= h($resultado['ddd']) ?>) <?= h($resultado['telefone']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['ddd2']) && !empty($resultado['telefone2'])): ?>
            <div class="field"><strong>Tel 2:</strong> (<?= h($resultado['ddd2']) ?>) <?= h($resultado['telefone2']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['email'])): ?>
            <div class="field full-width"><strong>Email:</strong> <?= h($resultado['email']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado['qsa']) && is_array($resultado['qsa']) && count($resultado['qsa']) > 0): ?>
        <div class="section">
            <h3>👥 Quadro de Sócios e Administradores (QSA)</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 35%;">Nome</th>
                        <th style="width: 25%;">Qualificação</th>
                        <th style="width: 20%;">CPF/CNPJ</th>
                        <th style="width: 20%;">País</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($resultado['qsa'], 0, 5) as $socio): ?>
                    <tr>
                        <td><?= h($socio['nome'] ?? '-') ?></td>
                        <td><?= h(substr($socio['qualificacao_descricao'] ?? $socio['qualificacao'] ?? '-', 0, 30)) ?></td>
                        <td class="text-monospace"><?= h($socio['documento'] ?? '-') ?></td>
                        <td><?= h(substr($socio['pais_origem'] ?? 'Brasil', 0, 15)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($resultado['qsa']) > 5): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; font-size: 6.5pt; padding: 4px;">
                            <em>... e mais <?= count($resultado['qsa']) - 5 ?> sócio(s)</em>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($tipo === 'CPF'): ?>
        <?php if (!empty($resultado['data_inscricao']) || !empty($resultado['data_atualizacao']) || !empty($resultado['unidade_administrativa'])): ?>
        <div class="section">
            <h3>📋 Informações Administrativas</h3>
            <?php if (!empty($resultado['unidade_administrativa'])): ?>
            <div class="field"><strong>Unidade Admin:</strong> <?= h($resultado['unidade_administrativa']) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['data_inscricao'])): ?>
            <div class="field"><strong>Data Inscrição:</strong> <?= substr($resultado['data_inscricao'], 6, 2) ?>/<?= substr($resultado['data_inscricao'], 4, 2) ?>/<?= substr($resultado['data_inscricao'], 0, 4) ?></div>
            <?php endif; ?>
            <?php if (!empty($resultado['data_atualizacao'])): ?>
            <div class="field"><strong>Data Atualização:</strong> <?= substr($resultado['data_atualizacao'], 6, 2) ?>/<?= substr($resultado['data_atualizacao'], 4, 2) ?>/<?= substr($resultado['data_atualizacao'], 0, 4) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="lgpd">
            <strong>⚠️ AVISO LGPD:</strong> Este documento contém dados sensíveis protegidos pela Lei Geral de Proteção de Dados (Lei 13.709/2018).
            Uso restrito e auditado. Distribuição não autorizada é proibida.
        </div>

        <div class="footer">
            <strong>Sistema de Consultas CFO</strong><br>
            Documento gerado eletronicamente em <?= $data ?><br>
            Possui validade legal conforme MP 2.200-2/2001
        </div>
        </div>

        <div class="buttons no-print">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir / Salvar PDF
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                ✖️ Fechar
            </button>
        </div>
    </div>
</body>
</html>
