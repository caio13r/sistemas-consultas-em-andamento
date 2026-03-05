<?php
/**
 * Script de diagnóstico para conexão com a Receita Federal
 */

echo "<h2>🔍 Diagnóstico de Conexão - Receita Federal</h2>";

// 1. Verificar extensões PHP
echo "<h3>1. Extensões PHP</h3>";
echo "<ul>";
echo "<li>SOAP: " . (extension_loaded('soap') ? '✅ Instalado' : '❌ NÃO instalado') . "</li>";
echo "<li>OpenSSL: " . (extension_loaded('openssl') ? '✅ Instalado' : '❌ NÃO instalado') . "</li>";
echo "<li>cURL: " . (extension_loaded('curl') ? '✅ Instalado' : '❌ NÃO instalado') . "</li>";
echo "</ul>";

// 2. Verificar versão OpenSSL
if (extension_loaded('openssl')) {
    echo "<h3>2. Versão OpenSSL</h3>";
    echo "<p>" . OPENSSL_VERSION_TEXT . "</p>";
}

// 3. Testar conexão HTTP simples
echo "<h3>3. Teste de Conexão HTTP</h3>";
$url = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL';
echo "<p>URL: <code>$url</code></p>";

// Teste com file_get_contents
echo "<h4>Teste com file_get_contents:</h4>";
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$result = @file_get_contents($url, false, $context);
if ($result !== false) {
    echo "✅ Conexão bem-sucedida! Tamanho do WSDL: " . strlen($result) . " bytes<br>";
    echo "<details><summary>Ver primeiros 500 caracteres do WSDL</summary>";
    echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "...</pre>";
    echo "</details>";
} else {
    echo "❌ Falha na conexão<br>";
    $error = error_get_last();
    if ($error) {
        echo "<pre>Erro: " . htmlspecialchars($error['message']) . "</pre>";
    }
}

// Teste com cURL
if (extension_loaded('curl')) {
    echo "<h4>Teste com cURL:</h4>";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode<br>";
    if ($result !== false && $httpCode == 200) {
        echo "✅ Conexão bem-sucedida via cURL!<br>";
    } else {
        echo "❌ Falha na conexão via cURL<br>";
        if ($curlError) {
            echo "Erro: $curlError<br>";
        }
    }
}

// 4. Testar SoapClient
if (extension_loaded('soap')) {
    echo "<h3>4. Teste SoapClient</h3>";
    try {
        $options = [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ])
        ];
        
        $client = new SoapClient($url, $options);
        echo "✅ SoapClient criado com sucesso!<br>";
        
        echo "<h4>Funções disponíveis:</h4>";
        $functions = $client->__getFunctions();
        echo "<ul>";
        foreach ($functions as $func) {
            echo "<li><code>" . htmlspecialchars($func) . "</code></li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "❌ Erro ao criar SoapClient:<br>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}

// 5. Verificar certificado
echo "<h3>5. Verificar Certificado PFX</h3>";
$certPath = __DIR__ . '/../config/CFOORGBR.pfx';
if (file_exists($certPath)) {
    echo "✅ Arquivo encontrado: <code>$certPath</code><br>";
    echo "Tamanho: " . filesize($certPath) . " bytes<br>";
    echo "Permissões: " . substr(sprintf('%o', fileperms($certPath)), -4) . "<br>";
    echo "Pode ler: " . (is_readable($certPath) ? '✅ Sim' : '❌ Não') . "<br>";

    // Tentar ler informações do certificado
    if (extension_loaded('openssl')) {
        $certContent = file_get_contents($certPath);
        $certData = openssl_pkcs12_read($certContent, $cert, 'CFO166');

        if ($certData) {
            echo "✅ Certificado lido com sucesso!<br>";
            $certInfo = openssl_x509_parse($cert['cert']);
            echo "<ul>";
            echo "<li>Titular: " . ($certInfo['subject']['CN'] ?? 'N/A') . "</li>";
            echo "<li>Válido de: " . date('d/m/Y', $certInfo['validFrom_time_t']) . "</li>";
            echo "<li>Válido até: " . date('d/m/Y', $certInfo['validTo_time_t']) . "</li>";

            $diasRestantes = floor(($certInfo['validTo_time_t'] - time()) / 86400);
            if ($diasRestantes > 0) {
                echo "<li>Status: ✅ Válido (faltam $diasRestantes dias)</li>";
            } else {
                echo "<li>Status: ❌ Expirado há " . abs($diasRestantes) . " dias</li>";
            }
            echo "</ul>";
        } else {
            echo "❌ Erro ao ler certificado. Verifique a senha.<br>";
            echo "Erro OpenSSL: " . openssl_error_string() . "<br>";
        }
    }
} else {
    echo "❌ Certificado NÃO encontrado em: <code>$certPath</code><br>";
}

// 6. Info do PHP
echo "<h3>6. Configurações PHP Relevantes</h3>";
echo "<ul>";
echo "<li>allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ Habilitado' : '❌ Desabilitado') . "</li>";
echo "<li>default_socket_timeout: " . ini_get('default_socket_timeout') . "s</li>";
echo "<li>max_execution_time: " . ini_get('max_execution_time') . "s</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>💡 Dica:</strong> Se os testes básicos funcionarem mas o SoapClient falhar, ";
echo "pode ser necessário ajustar as configurações SSL do PHP ou usar um wrapper personalizado.</p>";
?>

