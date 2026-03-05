<?php
/**
 * Debug Visual - Consulta RFB
 * Mostra na tela cada passo da consulta
 */

// Carregar autoload
require_once __DIR__ . '/../vendor/autoload.php';

use Cfo\SisConsultas\lib\ReceitaFederalAPI;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug RFB Consulta</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .step { background: #252526; padding: 15px; margin: 10px 0; border-left: 4px solid #007acc; }
        .success { border-left-color: #4ec9b0; }
        .error { border-left-color: #f48771; }
        .code { background: #1e1e1e; padding: 10px; margin: 10px 0; border-radius: 3px; overflow-x: auto; }
        h1 { color: #4ec9b0; }
        h2 { color: #dcdcaa; }
    </style>
</head>
<body>

<h1>🔍 Debug RFB - Consulta Passo a Passo</h1>

<?php

$cpf = '03990316184';
$cpfUsuario = '03990316184';

echo "<div class='step'>";
echo "<h2>Passo 1: Instanciar ReceitaFederalAPI</h2>";
try {
    $api = new ReceitaFederalAPI();
    echo "<p style='color:#4ec9b0'>✅ API instanciada com sucesso</p>";
} catch (Exception $e) {
    echo "<p style='color:#f48771'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("</div></body></html>");
}
echo "</div>";

echo "<div class='step'>";
echo "<h2>Passo 2: Verificar Certificado Manualmente</h2>";
$pfxPath = __DIR__ . '/../config/CFOORGBR.pfx';
echo "<p>Caminho: <code>$pfxPath</code></p>";
echo "<p>Existe: " . (file_exists($pfxPath) ? "✅ SIM" : "❌ NÃO") . "</p>";
if (file_exists($pfxPath)) {
    echo "<p>Tamanho: " . filesize($pfxPath) . " bytes</p>";
    echo "<p>Legível: " . (is_readable($pfxPath) ? "✅ SIM" : "❌ NÃO") . "</p>";
}
echo "</div>";

echo "<div class='step'>";
echo "<h2>Passo 3: Verificar Cache WSDL</h2>";
$wsdlCache = __DIR__ . '/../config/rfb_wsdl_cache.xml';
echo "<p>Caminho: <code>$wsdlCache</code></p>";
echo "<p>Existe: " . (file_exists($wsdlCache) ? "✅ SIM" : "❌ NÃO") . "</p>";
if (file_exists($wsdlCache)) {
    echo "<p>Tamanho: " . filesize($wsdlCache) . " bytes</p>";
}
echo "</div>";

echo "<div class='step'>";
echo "<h2>Passo 4: Chamar consultarCPF()</h2>";
echo "<p>CPF: <code>$cpf</code></p>";
echo "<p>CPF Usuário: <code>$cpfUsuario</code></p>";
echo "<p style='color:#dcdcaa'>⏳ Executando consulta...</p>";

$startTime = microtime(true);

try {
    $resultado = $api->consultarCPF($cpf, $cpfUsuario);
    $endTime = microtime(true);
    $tempo = round(($endTime - $startTime) * 1000);
    
    echo "<p style='color:#4ec9b0'>✅ Consulta executada em {$tempo}ms</p>";
    
    echo "</div>";
    
    echo "<div class='step success'>";
    echo "<h2>Passo 5: Resultado</h2>";
    
    if ($resultado['sucesso']) {
        echo "<p style='color:#4ec9b0;font-size:1.2em;'>✅✅✅ SUCESSO!</p>";
        echo "<div class='code'>";
        echo "<strong>Dados Retornados:</strong><br>";
        echo "CPF: " . htmlspecialchars($resultado['cpf'] ?? 'N/A') . "<br>";
        echo "Nome: " . htmlspecialchars($resultado['nome'] ?? 'N/A') . "<br>";
        echo "Situação: " . htmlspecialchars($resultado['situacao_cadastral'] ?? 'N/A') . "<br>";
        echo "Tempo: " . ($resultado['tempo_resposta_ms'] ?? 'N/A') . "ms<br>";
        echo "</div>";
        
        echo "<details>";
        echo "<summary><strong>Ver JSON Completo</strong></summary>";
        echo "<div class='code'>";
        echo "<pre>" . htmlspecialchars(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        echo "</div>";
        echo "</details>";
    } else {
        echo "<p style='color:#f48771'>❌ FALHOU</p>";
        echo "<div class='code'>";
        echo "<strong>Erro:</strong> " . htmlspecialchars($resultado['erro'] ?? 'Erro desconhecido') . "<br>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    $endTime = microtime(true);
    $tempo = round(($endTime - $startTime) * 1000);
    
    echo "<p style='color:#f48771'>❌ EXCEPTION após {$tempo}ms</p>";
    echo "</div>";
    
    echo "<div class='step error'>";
    echo "<h2>ERRO!</h2>";
    echo "<div class='code'>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
    echo "<strong>Código:</strong> " . $e->getCode() . "<br>";
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    echo "</div>";
}

?>

<div class='step' style='text-align:center; margin-top:30px;'>
    <a href='javascript:location.reload()' style='display:inline-block; padding:12px 24px; background:#007acc; color:white; text-decoration:none; border-radius:3px;'>
        🔄 Executar Novamente
    </a>
    <a href='/consulta-rfb' style='display:inline-block; padding:12px 24px; background:#4ec9b0; color:white; text-decoration:none; border-radius:3px; margin-left:10px;'>
        🚀 Voltar para Consulta
    </a>
</div>

</body>
</html>

