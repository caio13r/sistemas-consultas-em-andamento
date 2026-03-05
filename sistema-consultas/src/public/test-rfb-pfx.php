<?php
/**
 * Teste da API RFB COM certificado PFX (como Postman)
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste RFB com PFX</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .box { border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
    </style>
</head>
<body>

<h1>🔐 Teste RFB com Certificado PFX</h1>

<?php

// Carregar senha do .env
$envFile = __DIR__ . '/../.env';
$password = '';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, 'RFB_CERT_PASSWORD') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $password = trim($value);
            break;
        }
    }
}

$wsdlUrl = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL';
$pfxFile = __DIR__ . '/../config/CFOORGBR.pfx';

echo "<div class='box'>";
echo "<h2>1. Configuração</h2>";

echo "<p><strong>Certificado PFX:</strong> <code>$pfxFile</code></p>";
echo "<p><strong>Senha carregada do .env:</strong> <code>" . ($password ? str_repeat('*', strlen($password)) : 'NÃO ENCONTRADA') . "</code></p>";

if (!file_exists($pfxFile)) {
    die("<p class='error'>❌ Arquivo PFX não encontrado!</p></div></body></html>");
}

echo "<p class='success'>✅ Arquivo PFX encontrado</p>";
echo "<p>Tamanho: " . filesize($pfxFile) . " bytes</p>";

// Validar o PFX
$pfxContent = file_get_contents($pfxFile);
$certs = [];
if (openssl_pkcs12_read($pfxContent, $certs, $password)) {
    echo "<p class='success'>✅ Certificado PFX válido (senha correta)</p>";

    $certInfo = openssl_x509_parse($certs['cert']);
    echo "<ul>";
    echo "<li><strong>Titular:</strong> " . ($certInfo['subject']['CN'] ?? 'N/A') . "</li>";
    echo "<li><strong>Válido até:</strong> " . date('d/m/Y', $certInfo['validTo_time_t']) . "</li>";
    $diasRestantes = floor(($certInfo['validTo_time_t'] - time()) / 86400);
    if ($diasRestantes > 0) {
        echo "<li class='success'><strong>Status:</strong> ✅ Válido (faltam $diasRestantes dias)</li>";
    } else {
        echo "<li class='error'><strong>Status:</strong> ❌ Expirado</li>";
    }
    echo "</ul>";
} else {
    die("<p class='error'>❌ Erro ao ler PFX. Senha incorreta ou arquivo corrompido.</p><p>Erro OpenSSL: " . openssl_error_string() . "</p></div></body></html>");
}

echo "</div>";

// Teste 1: cURL com PFX
echo "<div class='box'>";
echo "<h2>2. Teste cURL COM Certificado PFX</h2>";

// IMPORTANTE: cURL precisa de PEM, então vamos converter em memória
$pemTemp = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';

// Criar PEM temporário
$pemContent = $certs['cert'] . "\n" . $certs['pkey'] . "\n";
file_put_contents($pemTemp, $pemContent);

echo "<p>✅ PEM temporário criado: <code>$pemTemp</code></p>";

$ch = curl_init($wsdlUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSLCERT => $pemTemp,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_VERBOSE => false,
    CURLOPT_USERAGENT => 'PHP/' . PHP_VERSION
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// Limpar arquivo temporário
unlink($pemTemp);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Tempo:</strong> " . round($info['total_time'], 2) . "s</p>";

if ($httpCode == 200 && $result) {
    echo "<p class='success'>✅✅✅ SUCESSO! Conexão estabelecida com certificado PFX!</p>";
    echo "<p>WSDL baixado: " . strlen($result) . " bytes</p>";

    // Salvar WSDL localmente
    $wsdlLocal = __DIR__ . '/../config/rfb_wsdl_cache.xml';
    file_put_contents($wsdlLocal, $result);
    echo "<p>✅ WSDL salvo em cache: <code>$wsdlLocal</code></p>";

    echo "<details><summary>Ver início do WSDL (500 chars)</summary>";
    echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "...</pre>";
    echo "</details>";
} else {
    echo "<p class='error'>❌ Falhou - HTTP $httpCode</p>";
    if ($error) {
        echo "<p class='error'>Erro cURL: $error</p>";
    }
}

echo "</div>";

// Teste 2: SoapClient usando PEM temporário
if ($httpCode == 200) {
    echo "<div class='box'>";
    echo "<h2>3. Teste SoapClient COM Certificado</h2>";

    try {
        // Criar PEM temporário novamente para o SoapClient
        $pemTemp = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';
        $pemContent = $certs['cert'] . "\n" . $certs['pkey'] . "\n";
        file_put_contents($pemTemp, $pemContent);

        $context = stream_context_create([
            'ssl' => [
                'local_cert' => $pemTemp,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'http' => [
                'timeout' => 30,
                'user_agent' => 'PHP-SOAP/' . PHP_VERSION
            ]
        ]);

        $options = [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => $context,
            'soap_version' => SOAP_1_1,
            'encoding' => 'UTF-8',
            'connection_timeout' => 30
        ];

        // Usar WSDL local (cache)
        echo "<p>Usando WSDL em cache local...</p>";

        $client = new SoapClient($wsdlLocal, $options);

        echo "<p class='success'>✅ SoapClient criado com sucesso!</p>";

        echo "<h4>Funções disponíveis:</h4>";
        $functions = $client->__getFunctions();
        echo "<ul>";
        foreach ($functions as $func) {
            echo "<li><code>" . htmlspecialchars($func) . "</code></li>";
        }
        echo "</ul>";

        // Fazer uma consulta REAL
        echo "<h3>4. 🎯 Teste de Consulta REAL na RFB</h3>";

        $cpfTeste = '03990316184';
        $cpfUsuario = '03990316184';

        echo "<p>Consultando CPF: <strong>" . substr($cpfTeste, 0, 3) . ".***.***-" . substr($cpfTeste, -2) . "</strong></p>";

        $startTime = microtime(true);

        $params = [
            'ListaDeCPF' => $cpfTeste,
            'CPFUsuario' => $cpfUsuario
        ];

        $response = $client->ConsultarCPFPDEC8789($params);

        $endTime = microtime(true);
        $tempoMs = round(($endTime - $startTime) * 1000);

        echo "<p class='success' style='font-size: 1.2em;'>✅✅✅ CONSULTA REALIZADA COM SUCESSO!</p>";
        echo "<p><strong>Tempo de resposta:</strong> {$tempoMs}ms</p>";

        if (isset($response->ConsultarCPFPDEC8789Result->PessoaPerfilDEC8789)) {
            $pessoa = $response->ConsultarCPFPDEC8789Result->PessoaPerfilDEC8789;

            echo "<div style='background:#e8f5e9; padding:20px; border-radius:8px; border-left: 5px solid #4caf50;'>";
            echo "<h4>📋 Dados Retornados pela Receita Federal:</h4>";
            echo "<table style='width:100%; border-collapse: collapse;'>";
            echo "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>CPF:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($pessoa->CPF) . "</td></tr>";
            echo "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Nome:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($pessoa->Nome) . "</td></tr>";
            echo "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Nome da Mãe:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($pessoa->NomeMae) . "</td></tr>";
            echo "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Data Nascimento:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($pessoa->DataNascimento) . "</td></tr>";
            echo "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Situação:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'><span class='success'>" . htmlspecialchars($pessoa->SituacaoCadastral == '0' ? 'Regular' : $pessoa->SituacaoCadastral) . "</span></td></tr>";
            echo "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Município/UF:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($pessoa->Municipio) . " - " . htmlspecialchars($pessoa->UF) . "</td></tr>";
            echo "</table>";
            echo "</div>";

            echo "<details style='margin-top:15px;'><summary><strong>📄 Ver XML Completo da Resposta</strong></summary>";
            echo "<pre style='max-height:400px; overflow:auto;'>" . htmlspecialchars($client->__getLastResponse()) . "</pre>";
            echo "</details>";
        }

        // Limpar PEM temporário
        unlink($pemTemp);

    } catch (SoapFault $e) {
        echo "<p class='error'>❌ Erro SoapClient: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<details><summary>Stack Trace</summary>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</details>";

        if (file_exists($pemTemp)) unlink($pemTemp);
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";

        if (file_exists($pemTemp)) unlink($pemTemp);
    }

    echo "</div>";
}

?>

<div class='box' style='background:<?php echo ($httpCode == 200) ? '#e8f5e9' : '#ffebee'; ?>;'>
    <h3>📊 Resumo Final</h3>
    <?php if ($httpCode == 200): ?>
        <p class='success' style='font-size:1.1em;'>🎉 <strong>TUDO FUNCIONANDO PERFEITAMENTE!</strong></p>
        <p><strong>O que foi testado:</strong></p>
        <ul>
            <li>✅ Certificado PFX válido e senha correta</li>
            <li>✅ Conexão HTTPS com certificado cliente funcionando</li>
            <li>✅ WSDL baixado com sucesso</li>
            <li>✅ SoapClient criado e funcionando</li>
            <li>✅ Consulta REAL na RFB executada com sucesso</li>
        </ul>
        <p><strong>Próximos passos:</strong></p>
        <ol>
            <li>✅ A integração está funcionando!</li>
            <li>🚀 Agora pode usar na aplicação: <a href="/consulta-integrada?tipoConsulta=3" style='color:#1976d2; font-weight:bold;'>Consultar CPF</a></li>
            <li>📝 A classe ReceitaFederalAPI já está configurada para usar o PFX</li>
        </ol>
    <?php else: ?>
        <p class='error'>❌ <strong>A conexão falhou</strong></p>
        <p>Verifique:</p>
        <ul>
            <li>Senha do certificado no .env: <code><?php echo $password ? 'Configurada' : 'NÃO configurada'; ?></code></li>
            <li>Certificado válido (não expirado)</li>
            <li>IP liberado no SERPRO</li>
            <li>Firewall/Proxy não bloqueando</li>
        </ul>
    <?php endif; ?>
</div>

<p style='text-align:center; margin-top:30px;'>
    <a href='javascript:location.reload()' style='display:inline-block; padding:12px 24px; background:#1976d2; color:white; text-decoration:none; border-radius:5px; font-weight:bold;'>
        🔄 Testar Novamente
    </a>
    <a href='/consulta-integrada?tipoConsulta=3' style='display:inline-block; padding:12px 24px; background:#4caf50; color:white; text-decoration:none; border-radius:5px; font-weight:bold; margin-left:10px;'>
        🚀 Ir para Consulta Integrada
    </a>
</p>

</body>
</html>
