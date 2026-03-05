<?php
/**
 * Diagnóstico completo de SSL/TLS para conexão com a RFB
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico SSL - RFB</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        h2 { border-bottom: 2px solid #333; }
    </style>
</head>
<body>

<h1>🔍 Diagnóstico SSL/TLS - Receita Federal</h1>

<?php

echo "<h2>1. Informações do Sistema</h2>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Sistema: " . PHP_OS . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "</pre>";

echo "<h2>2. Extensões PHP</h2>";
echo "<pre>";
echo "OpenSSL: " . (extension_loaded('openssl') ? '✅ INSTALADO' : '❌ NÃO INSTALADO') . "\n";
if (extension_loaded('openssl')) {
    echo "Versão OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
}
echo "SOAP: " . (extension_loaded('soap') ? '✅ INSTALADO' : '❌ NÃO INSTALADO') . "\n";
echo "cURL: " . (extension_loaded('curl') ? '✅ INSTALADO' : '❌ NÃO INSTALADO') . "\n";
if (extension_loaded('curl')) {
    $curlVersion = curl_version();
    echo "Versão cURL: " . $curlVersion['version'] . "\n";
    echo "SSL Version: " . $curlVersion['ssl_version'] . "\n";
}
echo "</pre>";

echo "<h2>3. Configurações PHP SSL</h2>";
echo "<pre>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ HABILITADO' : '❌ DESABILITADO') . "\n";
echo "openssl.cafile: " . (ini_get('openssl.cafile') ?: 'não configurado') . "\n";
echo "openssl.capath: " . (ini_get('openssl.capath') ?: 'não configurado') . "\n";
echo "curl.cainfo: " . (ini_get('curl.cainfo') ?: 'não configurado') . "\n";
echo "</pre>";

$url = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL';

echo "<h2>4. Teste de Conexão HTTP Básica</h2>";
echo "<p>Testando: <code>$url</code></p>";

// Teste 1: file_get_contents SEM validação SSL
echo "<h3>4.1 Teste com file_get_contents (SSL desabilitado)</h3>";
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
    'http' => [
        'timeout' => 10,
        'user_agent' => 'PHP/' . PHP_VERSION
    ]
]);

$result = @file_get_contents($url, false, $context);
if ($result !== false) {
    echo "<p class='success'>✅ SUCESSO! Conexão estabelecida</p>";
    echo "<p>Tamanho do WSDL: " . strlen($result) . " bytes</p>";
    echo "<details><summary>Ver início do WSDL (500 chars)</summary>";
    echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "...</pre>";
    echo "</details>";
} else {
    echo "<p class='error'>❌ FALHOU</p>";
    $error = error_get_last();
    if ($error) {
        echo "<pre class='error'>" . htmlspecialchars($error['message']) . "</pre>";
    }
}

// Teste 2: cURL
if (extension_loaded('curl')) {
    echo "<h3>4.2 Teste com cURL</h3>";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'PHP/' . PHP_VERSION
    ]);

    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);

    echo "<pre>";
    echo "HTTP Code: " . $info['http_code'] . "\n";
    echo "Total Time: " . round($info['total_time'], 2) . "s\n";
    echo "SSL Verify Result: " . $info['ssl_verify_result'] . "\n";
    echo "</pre>";

    if ($result && $info['http_code'] == 200) {
        echo "<p class='success'>✅ SUCESSO via cURL!</p>";
        echo "<p>Tamanho: " . strlen($result) . " bytes</p>";
    } else {
        echo "<p class='error'>❌ FALHOU via cURL</p>";
        if ($error) {
            echo "<p class='error'>Erro: $error</p>";
        }
    }
}

// Teste 3: Resolução DNS
echo "<h2>5. Teste de Resolução DNS</h2>";
$host = 'acesso.infoconv.receita.fazenda.gov.br';
$ip = gethostbyname($host);
echo "<pre>";
echo "Host: $host\n";
echo "IP: $ip\n";
if ($ip !== $host) {
    echo "Status: <span class='success'>✅ DNS resolvido</span>\n";
} else {
    echo "Status: <span class='error'>❌ DNS NÃO resolvido</span>\n";
}
echo "</pre>";

// Teste 4: Conectividade de porta
echo "<h2>6. Teste de Conectividade (Porta 443)</h2>";
$fp = @fsockopen($host, 443, $errno, $errstr, 10);
if ($fp) {
    echo "<p class='success'>✅ Porta 443 está acessível</p>";
    fclose($fp);
} else {
    echo "<p class='error'>❌ Não foi possível conectar na porta 443</p>";
    echo "<p>Erro: [$errno] $errstr</p>";
}

// Teste 5: SoapClient
if (extension_loaded('soap')) {
    echo "<h2>7. Teste SoapClient</h2>";

    echo "<h3>7.1 SoapClient SEM validação SSL</h3>";
    try {
        $options = [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => 10,
            'user_agent' => 'PHP/' . PHP_VERSION,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ])
        ];

        $client = new SoapClient($url, $options);
        echo "<p class='success'>✅ SoapClient criado com sucesso!</p>";

        echo "<h4>Funções disponíveis:</h4>";
        $functions = $client->__getFunctions();
        echo "<ul>";
        foreach (array_slice($functions, 0, 5) as $func) {
            echo "<li><code>" . htmlspecialchars($func) . "</code></li>";
        }
        if (count($functions) > 5) {
            echo "<li><em>... e mais " . (count($functions) - 5) . " funções</em></li>";
        }
        echo "</ul>";

    } catch (SoapFault $e) {
        echo "<p class='error'>❌ Erro ao criar SoapClient</p>";
        echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
        echo "<details><summary>Stack trace</summary>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</details>";
    }
}

// Teste 6: Baixar e salvar WSDL localmente
echo "<h2>8. Tentar Baixar WSDL Localmente</h2>";
$wsdlLocal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rfb_wsdl.xml';

if ($result !== false && strlen($result) > 100) {
    if (file_put_contents($wsdlLocal, $result)) {
        echo "<p class='success'>✅ WSDL salvo localmente em: <code>$wsdlLocal</code></p>";
        echo "<p>Tamanho: " . filesize($wsdlLocal) . " bytes</p>";

        // Tentar criar SoapClient com WSDL local
        if (extension_loaded('soap')) {
            echo "<h3>8.1 Teste SoapClient com WSDL Local</h3>";
            try {
                $client = new SoapClient($wsdlLocal, [
                    'trace' => 1,
                    'exceptions' => true,
                    'cache_wsdl' => WSDL_CACHE_NONE
                ]);
                echo "<p class='success'>✅ SoapClient criado com WSDL local!</p>";
            } catch (SoapFault $e) {
                echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ Não foi possível salvar WSDL localmente</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Não há WSDL para salvar (falhou nos testes anteriores)</p>";
}

// Teste 7: Verificar certificados CA
echo "<h2>9. Certificados CA do Sistema</h2>";
echo "<pre>";

$caFile = ini_get('openssl.cafile');
$caPath = ini_get('openssl.capath');

if ($caFile && file_exists($caFile)) {
    echo "✅ CA File configurado e existe: $caFile\n";
    echo "   Tamanho: " . filesize($caFile) . " bytes\n";
} else {
    echo "❌ CA File não configurado ou não existe\n";
    echo "   Configurado: " . ($caFile ?: 'não') . "\n";
}

if ($caPath && is_dir($caPath)) {
    echo "✅ CA Path configurado e existe: $caPath\n";
} else {
    echo "❌ CA Path não configurado ou não existe\n";
}

echo "</pre>";

?>

<hr>
<h2>📋 Resumo e Recomendações</h2>

<?php

echo "<ul>";

// Diagnóstico automático
$problemas = [];
$solucoes = [];

if (!extension_loaded('openssl')) {
    $problemas[] = "Extensão OpenSSL não está carregada";
    $solucoes[] = "Instale/habilite a extensão OpenSSL no PHP";
}

if (!extension_loaded('soap')) {
    $problemas[] = "Extensão SOAP não está carregada";
    $solucoes[] = "Instale/habilite a extensão SOAP no PHP";
}

if (!ini_get('allow_url_fopen')) {
    $problemas[] = "allow_url_fopen está desabilitado";
    $solucoes[] = "Habilite allow_url_fopen no php.ini";
}

$caFile = ini_get('openssl.cafile');
if (!$caFile || !file_exists($caFile)) {
    $problemas[] = "Certificados CA não configurados";
    $solucoes[] = "Configure openssl.cafile no php.ini apontando para cacert.pem";
}

if (empty($problemas)) {
    echo "<li class='success'>✅ Configurações básicas OK</li>";
} else {
    foreach ($problemas as $i => $problema) {
        echo "<li class='error'>❌ $problema</li>";
        echo "<ul><li class='warning'>💡 Solução: {$solucoes[$i]}</li></ul>";
    }
}

echo "</ul>";

?>

<h3>🔧 Próximos Passos</h3>
<ol>
    <li>Se os testes básicos (file_get_contents ou cURL) funcionaram:
        <ul>
            <li>✅ A rede está OK</li>
            <li>⚠️ O problema é específico do SoapClient</li>
            <li>💡 Use a solução alternativa com WSDL local ou cURL</li>
        </ul>
    </li>
    <li>Se nenhum teste funcionou:
        <ul>
            <li>❌ Problema de conectividade ou firewall</li>
            <li>💡 Verifique firewall, proxy ou restrições de rede</li>
        </ul>
    </li>
    <li>Se falhou por SSL/TLS:
        <ul>
            <li>💡 Baixe e configure o cacert.pem</li>
            <li>💡 Configure openssl.cafile no php.ini</li>
        </ul>
    </li>
</ol>

<div style="background: #ffffcc; padding: 15px; border-radius: 5px; margin-top: 20px;">
    <h3>📥 Download do cacert.pem</h3>
    <p>Se o problema for certificados CA, baixe:</p>
    <p><a href="https://curl.se/ca/cacert.pem" target="_blank">https://curl.se/ca/cacert.pem</a></p>
    <p>Depois configure no php.ini:</p>
    <pre>openssl.cafile="C:/path/to/cacert.pem"
curl.cainfo="C:/path/to/cacert.pem"</pre>
</div>

</body>
</html>
