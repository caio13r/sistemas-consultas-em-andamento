<?php
/**
 * Script de teste da API da Receita Federal
 * Testa a consulta de CPF real usando a classe ReceitaFederalAPI
 */

// Carregar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Cfo\SisConsultas\lib\ReceitaFederalAPI;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste API RFB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .box {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        h3 { color: #0066cc; }
    </style>
</head>
<body>

<h1>🔍 Teste da API da Receita Federal</h1>

<?php

// Instanciar a API
echo "<div class='box'>";
echo "<h2>1. Inicializando API</h2>";
try {
    $api = new ReceitaFederalAPI();
    echo "<p class='success'>✅ API inicializada com sucesso!</p>";

    // Verificar certificado
    echo "<h3>Verificando Certificado:</h3>";
    $certInfo = $api->verificarCertificado();

    if ($certInfo['valido']) {
        echo "<p class='success'>✅ " . $certInfo['mensagem'] . "</p>";
        echo "<ul>";
        echo "<li><strong>Titular:</strong> " . $certInfo['titular'] . "</li>";
        echo "<li><strong>Expira em:</strong> " . $certInfo['data_expiracao'] . "</li>";
        echo "<li><strong>Dias restantes:</strong> " . $certInfo['dias_restantes'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ " . $certInfo['mensagem'] . "</p>";
        if (isset($certInfo['data_expiracao'])) {
            echo "<p>Data de expiração: " . $certInfo['data_expiracao'] . "</p>";
        }
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao inicializar API: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    exit;
}
echo "</div>";

// Teste de consulta
echo "<div class='box'>";
echo "<h2>2. Teste de Consulta</h2>";

$cpfTeste = '03990316184'; // CPF de exemplo (do seu XML de resposta)
$cpfUsuario = '03990316184'; // CPF do usuário consultando

echo "<p>Consultando CPF: <strong>$cpfTeste</strong></p>";
echo "<p>CPF do usuário: <strong>$cpfUsuario</strong></p>";

$startTime = microtime(true);

try {
    $resultado = $api->consultarCPF($cpfTeste, $cpfUsuario);

    $endTime = microtime(true);
    $tempoTotal = round(($endTime - $startTime) * 1000);

    echo "<p><strong>Tempo de resposta:</strong> {$tempoTotal}ms</p>";

    if ($resultado['sucesso']) {
        echo "<h3 class='success'>✅ Consulta realizada com sucesso!</h3>";

        echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Dados Retornados:</h4>";
        echo "<ul>";
        echo "<li><strong>CPF:</strong> " . htmlspecialchars($resultado['cpf']) . "</li>";
        echo "<li><strong>Nome:</strong> " . htmlspecialchars($resultado['nome']) . "</li>";
        echo "<li><strong>Nome da Mãe:</strong> " . htmlspecialchars($resultado['nome_mae']) . "</li>";
        echo "<li><strong>Data de Nascimento:</strong> " . htmlspecialchars($resultado['data_nascimento']) . "</li>";
        echo "<li><strong>Sexo:</strong> " . htmlspecialchars($resultado['sexo_descricao']) . "</li>";
        echo "<li><strong>Situação Cadastral:</strong> <strong>" . htmlspecialchars($resultado['situacao_cadastral']) . "</strong></li>";
        echo "<li><strong>Município:</strong> " . htmlspecialchars($resultado['municipio']) . " - " . htmlspecialchars($resultado['uf']) . "</li>";
        echo "</ul>";
        echo "</div>";

        // Mostrar todos os campos retornados
        echo "<h4>Todos os Campos (Debug):</h4>";
        echo "<pre>";
        $debugData = $resultado;
        unset($debugData['xml_completo']); // Remover XML para não poluir
        print_r($debugData);
        echo "</pre>";

        // Opção para ver XML completo
        if (!empty($resultado['xml_completo'])) {
            echo "<details>";
            echo "<summary><strong>Ver XML Completo da Resposta</strong></summary>";
            echo "<pre>" . htmlspecialchars($resultado['xml_completo']) . "</pre>";
            echo "</details>";
        }

    } else {
        echo "<h3 class='error'>❌ Erro na consulta</h3>";
        echo "<p class='error'>" . htmlspecialchars($resultado['erro']) . "</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ Exceção: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div>";

?>

<div class='box' style='background: #fff3cd;'>
    <h3>📝 Notas:</h3>
    <ul>
        <li>Este script testa a conexão com a API real da Receita Federal</li>
        <li>Certifique-se de que o certificado CFOORGBR.pfx está no diretório <code>src/config/</code></li>
        <li>A senha do certificado deve estar configurada no arquivo <code>src/.env</code> como <code>RFB_CERT_PASSWORD</code></li>
        <li>O teste usa o CPF de exemplo fornecido</li>
    </ul>
</div>

<div style='text-align: center; margin-top: 30px;'>
    <a href='test-rfb-conexao.php' style='display: inline-block; padding: 10px 20px; background: #0066cc; color: white; text-decoration: none; border-radius: 5px;'>
        🔍 Ver Diagnóstico de Conexão
    </a>
    <a href='javascript:location.reload()' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>
        🔄 Testar Novamente
    </a>
</div>

</body>
</html>
