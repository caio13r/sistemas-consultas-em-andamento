<?php
/**
 * Script para converter certificado .PFX para .PEM
 * Conforme documentação SERPRO/RFB
 */

echo "<h1>🔐 Conversor PFX para PEM</h1>";

$pfxFile = __DIR__ . '/src/config/CFOORGBR.pfx';
$pemFile = __DIR__ . '/src/config/CFOORGBR.pem';
$password = 'CFO166';

echo "<h2>1. Verificando arquivo PFX</h2>";
if (!file_exists($pfxFile)) {
    die("<p style='color:red'>❌ Arquivo PFX não encontrado: $pfxFile</p>");
}
echo "<p style='color:green'>✅ Arquivo PFX encontrado: $pfxFile</p>";
echo "<p>Tamanho: " . filesize($pfxFile) . " bytes</p>";

echo "<h2>2. Convertendo PFX para PEM</h2>";

// Ler o arquivo PFX
$pfxContent = file_get_contents($pfxFile);

// Extrair certificado e chave privada
$certs = [];
if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
    die("<p style='color:red'>❌ Erro ao ler certificado PFX. Verifique a senha.</p><p>Erro OpenSSL: " . openssl_error_string() . "</p>");
}

echo "<p style='color:green'>✅ Certificado PFX lido com sucesso!</p>";

// Montar o arquivo PEM
$pemContent = '';

// Adicionar certificado
if (isset($certs['cert'])) {
    $pemContent .= $certs['cert'] . "\n";
    echo "<p>✅ Certificado extraído</p>";
}

// Adicionar chave privada
if (isset($certs['pkey'])) {
    $pemContent .= $certs['pkey'] . "\n";
    echo "<p>✅ Chave privada extraída</p>";
}

// Adicionar certificados da cadeia (se houver)
if (isset($certs['extracerts']) && is_array($certs['extracerts'])) {
    foreach ($certs['extracerts'] as $extracert) {
        $pemContent .= $extracert . "\n";
    }
    echo "<p>✅ " . count($certs['extracerts']) . " certificado(s) da cadeia extraído(s)</p>";
}

echo "<h2>3. Salvando arquivo PEM</h2>";

// Salvar arquivo PEM
if (file_put_contents($pemFile, $pemContent)) {
    echo "<p style='color:green'>✅ Arquivo PEM salvo com sucesso!</p>";
    echo "<p><strong>Localização:</strong> <code>$pemFile</code></p>";
    echo "<p><strong>Tamanho:</strong> " . filesize($pemFile) . " bytes</p>";

    // Verificar permissões
    echo "<h2>4. Verificando Permissões</h2>";
    echo "<p>Permissões: " . substr(sprintf('%o', fileperms($pemFile)), -4) . "</p>";
    echo "<p>Pode ler: " . (is_readable($pemFile) ? '✅ Sim' : '❌ Não') . "</p>";

    // Testar leitura do PEM
    echo "<h2>5. Testando Arquivo PEM</h2>";
    $certInfo = openssl_x509_parse(openssl_x509_read($certs['cert']));

    if ($certInfo) {
        echo "<p style='color:green'>✅ Certificado PEM é válido!</p>";
        echo "<ul>";
        echo "<li><strong>Titular:</strong> " . ($certInfo['subject']['CN'] ?? 'N/A') . "</li>";
        echo "<li><strong>Emissor:</strong> " . ($certInfo['issuer']['CN'] ?? 'N/A') . "</li>";
        echo "<li><strong>Válido de:</strong> " . date('d/m/Y', $certInfo['validFrom_time_t']) . "</li>";
        echo "<li><strong>Válido até:</strong> " . date('d/m/Y', $certInfo['validTo_time_t']) . "</li>";

        $diasRestantes = floor(($certInfo['validTo_time_t'] - time()) / 86400);
        if ($diasRestantes > 0) {
            echo "<li style='color:green'><strong>Status:</strong> ✅ Válido (faltam $diasRestantes dias)</li>";
        } else {
            echo "<li style='color:red'><strong>Status:</strong> ❌ Expirado há " . abs($diasRestantes) . " dias</li>";
        }
        echo "</ul>";
    }

    echo "<h2>✅ Conversão Concluída!</h2>";
    echo "<div style='background:#e8f5e9; padding:15px; border-radius:5px; margin:20px 0;'>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>✅ Certificado PEM criado em: <code>src/config/CFOORGBR.pem</code></li>";
    echo "<li>⏳ Atualize a classe ReceitaFederalAPI.php para usar o arquivo .pem</li>";
    echo "<li>⏳ Teste a conexão com: <code>test-rfb-api.php</code></li>";
    echo "</ol>";
    echo "</div>";

    // Mostrar trecho do PEM (primeiras linhas)
    echo "<h3>📄 Prévia do arquivo PEM:</h3>";
    echo "<pre style='background:#f4f4f4; padding:10px; border-radius:5px; max-height:200px; overflow:auto;'>";
    $lines = explode("\n", $pemContent);
    echo htmlspecialchars(implode("\n", array_slice($lines, 0, 15)));
    echo "\n... (arquivo completo tem " . count($lines) . " linhas)";
    echo "</pre>";

} else {
    echo "<p style='color:red'>❌ Erro ao salvar arquivo PEM</p>";
}

echo "<hr>";
echo "<p><a href='test-rfb-api.php' style='display:inline-block; padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>🧪 Testar API RFB</a></p>";
?>
