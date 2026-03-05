<?php
/**
 * Teste da API RFB COM certificado cliente
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste RFB com Certificado</title>
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

<h1>🔐 Teste RFB com Certificado Cliente</h1>

<?php

$wsdlUrl = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL';
$pemFile = __DIR__ . '/../config/CFOORGBR.pem';
$pfxFile = __DIR__ . '/../config/CFOORGBR.pfx';
$password = 'CFO166';

echo "<div class='box'>";
echo "<h2>1. Verificando Certificados</h2>";

if (file_exists($pemFile)) {
    echo "<p class='success'>✅ Arquivo PEM encontrado: <code>$pemFile</code></p>";
    echo "<p>Tamanho: " . filesize($pemFile) . " bytes</p>";
    $certToUse = $pemFile;
    $certType = 'PEM';
} elseif (file_exists($pfxFile)) {
    echo "<p class='warning'>⚠️ Arquivo PFX encontrado (use PEM para melhor compatibilidade): <code>$pfxFile</code></p>";
    echo "<p>Tamanho: " . filesize($pfxFile) . " bytes</p>";
    $certToUse = $pfxFile;
    $certType = 'PFX';
} else {
    die("<p class='error'>❌ Nenhum certificado encontrado!</p></div></body></html>");
}

// Ler informações do certificado
if ($certType === 'PEM') {
    $certContent = file_get_contents($pemFile);
    $certData = openssl_x509_parse($certContent);
    if ($certData) {
        echo "<p class='success'>✅ Certificado PEM é válido</p>";
        echo "<ul>";
        echo "<li><strong>Titular:</strong> " . ($certData['subject']['CN'] ?? 'N/A') . "</li>";
        echo "<li><strong>Válido até:</strong> " . date('d/m/Y', $certData['validTo_time_t']) . "</li>";
        $diasRestantes = floor(($certData['validTo_time_t'] - time()) / 86400);
        if ($diasRestantes > 0) {
            echo "<li class='success'><strong>Status:</strong> ✅ Válido (faltam $diasRestantes dias)</li>";
        } else {
            echo "<li class='error'><strong>Status:</strong> ❌ Expirado</li>";
        }
        echo "</ul>";
    }
}

echo "</div>";

// Teste 1: cURL com certificado
echo "<div class='box'>";
echo "<h2>2. Teste cURL COM Certificado Cliente</h2>";

$ch = curl_init($wsdlUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSLCERT => $certToUse,
    CURLOPT_SSLCERTPASSWD => $password,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_VERBOSE => false
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($httpCode == 200 && $result) {
    echo "<p class='success'>✅ SUCESSO! Conexão estabelecida com certificado!</p>";
    echo "<p>WSDL baixado: " . strlen($result) . " bytes</p>";

    // Salvar WSDL
    $wsdlLocal = __DIR__ . '/../config/rfb_wsdl_cache.xml';
    file_put_contents($wsdlLocal, $result);
    echo "<p>✅ WSDL salvo em: <code>$wsdlLocal</code></p>";

    echo "<details><summary>Ver início do WSDL (500 chars)</summary>";
    echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "...</pre>";
    echo "</details>";
} else {
    echo "<p class='error'>❌ Falhou - HTTP $httpCode</p>";
    if ($error) {
        echo "<p class='error'>Erro cURL: $error</p>";
    }
    echo "<p><strong>Possível causa:</strong> Certificado incorreto ou expirado</p>";
}

echo "</div>";

// Teste 2: SoapClient COM certificado
if ($httpCode == 200) {
    echo "<div class='box'>";
    echo "<h2>3. Teste SoapClient COM Certificado</h2>";

    try {
        $context = stream_context_create([
            'ssl' => [
                'local_cert' => $certToUse,
                'passphrase' => $password,
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
            'keep_alive' => false
        ];

        // Usar WSDL local se existir
        $wsdlToUse = file_exists($wsdlLocal) ? $wsdlLocal : $wsdlUrl;

        echo "<p>Criando SoapClient com WSDL: " . ($wsdlToUse === $wsdlLocal ? 'LOCAL (cache)' : 'REMOTO') . "</p>";

        $client = new SoapClient($wsdlToUse, $options);

        echo "<p class='success'>✅ SoapClient criado com sucesso!</p>";

        echo "<h4>Funções disponíveis:</h4>";
        $functions = $client->__getFunctions();
        echo "<ul>";
        foreach ($functions as $func) {
            echo "<li><code>" . htmlspecialchars($func) . "</code></li>";
        }
        echo "</ul>";

        // Fazer uma consulta de teste
        echo "<h3>4. Teste de Consulta Real</h3>";

        $cpfTeste = '03990316184';
        $cpfUsuario = '03990316184';

        echo "<p>Consultando CPF: <strong>$cpfTeste</strong></p>";

        $startTime = microtime(true);

        $params = [
            'ListaDeCPF' => $cpfTeste,
            'CPFUsuario' => $cpfUsuario
        ];

        $response = $client->ConsultarCPFPDEC8789($params);

        $endTime = microtime(true);
        $tempoMs = round(($endTime - $startTime) * 1000);

        echo "<p class='success'>✅ CONSULTA REALIZADA COM SUCESSO!</p>";
        echo "<p><strong>Tempo de resposta:</strong> {$tempoMs}ms</p>";

        if (isset($response->ConsultarCPFPDEC8789Result->PessoaPerfilDEC8789)) {
            $pessoa = $response->ConsultarCPFPDEC8789Result->PessoaPerfilDEC8789;

            echo "<div style='background:#e8f5e9; padding:15px; border-radius:5px;'>";
            echo "<h4>Dados Retornados:</h4>";
            echo "<ul>";
            echo "<li><strong>CPF:</strong> " . htmlspecialchars($pessoa->CPF) . "</li>";
            echo "<li><strong>Nome:</strong> " . htmlspecialchars($pessoa->Nome) . "</li>";
            echo "<li><strong>Nome da Mãe:</strong> " . htmlspecialchars($pessoa->NomeMae) . "</li>";
            echo "<li><strong>Data Nascimento:</strong> " . htmlspecialchars($pessoa->DataNascimento) . "</li>";
            echo "<li><strong>Município:</strong> " . htmlspecialchars($pessoa->Municipio) . " - " . htmlspecialchars($pessoa->UF) . "</li>";
            echo "</ul>";
            echo "</div>";

            echo "<details><summary><strong>Ver Resposta Completa (XML)</strong></summary>";
            echo "<pre>" . htmlspecialchars($client->__getLastResponse()) . "</pre>";
            echo "</details>";
        }

    } catch (SoapFault $e) {
        echo "<p class='error'>❌ Erro SoapClient: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<details><summary>Stack Trace</summary>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</details>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "</div>";
}

?>

<div class='box' style='background:#e3f2fd;'>
    <h3>📋 Resumo</h3>
    <?php if ($httpCode == 200): ?>
        <p class='success'>✅ <strong>SUCESSO!</strong> A conexão com a RFB está funcionando com o certificado!</p>
        <p><strong>Próximos passos:</strong></p>
        <ol>
            <li>✅ O certificado está configurado corretamente</li>
            <li>✅ A API pode ser usada na aplicação</li>
            <li>🚀 Teste na consulta integrada: <a href="/consulta-integrada?tipoConsulta=3">Consultar CPF</a></li>
        </ol>
    <?php else: ?>
        <p class='error'>❌ A conexão falhou. Verifique:</p>
        <ul>
            <li>Certificado está válido (não expirado)?</li>
            <li>Senha do certificado está correta? (<code>RFB_CERT_PASSWORD</code> no .env)</li>
            <li>IP está liberado no SERPRO?</li>
        </ul>
    <?php endif; ?>
</div>

<p style='text-align:center; margin-top:30px;'>
    <a href='javascript:location.reload()' style='display:inline-block; padding:12px 24px; background:#28a745; color:white; text-decoration:none; border-radius:5px; font-weight:bold;'>
        🔄 Testar Novamente
    </a>
</p>

</body>
</html>
