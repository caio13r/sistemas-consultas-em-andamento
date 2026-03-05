<?php
/**
 * Script de Diagnóstico Completo - Conexão RFB
 * Acesse via: http://localhost:8080/test-rfb-diagnostico.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Diagnóstico RFB</title>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #eee; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 Diagnóstico Completo - Conexão RFB</h1>";

// 1. Verificar Extensões PHP
echo "<div class='section'>";
echo "<h2>1️⃣ Extensões PHP</h2>";

$extensoes = ['openssl', 'soap', 'curl'];
foreach ($extensoes as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "<span class='success'>✅ OK</span>" : "<span class='error'>❌ NÃO CARREGADO</span>";
    echo "- <strong>$ext</strong>: $status<br>";
}

if (extension_loaded('openssl')) {
    echo "<br>OpenSSL Version: <strong>" . OPENSSL_VERSION_TEXT . "</strong><br>";
}
echo "</div>";

// 2. Verificar Certificado
echo "<div class='section'>";
echo "<h2>2️⃣ Certificado Digital</h2>";

$pfxPath = __DIR__ . '/../config/CFOORGBR.pfx';
$pemPath = __DIR__ . '/../config/CFOORGBR.pem';

echo "Procurando certificado...<br><br>";

$certPath = null;
$certType = null;

if (file_exists($pfxPath)) {
    $certPath = $pfxPath;
    $certType = 'PFX';
    echo "✅ Arquivo <strong>.pfx</strong> encontrado: <code>$pfxPath</code><br>";
} elseif (file_exists($pemPath)) {
    $certPath = $pemPath;
    $certType = 'PEM';
    echo "✅ Arquivo <strong>.pem</strong> encontrado: <code>$pemPath</code><br>";
} else {
    echo "<span class='error'>❌ Nenhum certificado encontrado (.pfx ou .pem)</span><br>";
    echo "Procurado em:<br>";
    echo "- <code>$pfxPath</code><br>";
    echo "- <code>$pemPath</code><br>";
}

if ($certPath) {
    echo "Tamanho: <strong>" . filesize($certPath) . " bytes</strong><br>";
    echo "Permissões: <strong>" . substr(sprintf('%o', fileperms($certPath)), -4) . "</strong><br>";
    echo "Legível: " . (is_readable($certPath) ? "<span class='success'>✅ Sim</span>" : "<span class='error'>❌ Não</span>") . "<br>";
}
echo "</div>";

// 3. Testar Resolução DNS
echo "<div class='section'>";
echo "<h2>3️⃣ Resolução DNS</h2>";

$host = 'acesso.infoconv.receita.fazenda.gov.br';
echo "Resolvendo: <strong>$host</strong><br><br>";

$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "<span class='success'>✅ DNS Resolvido</span><br>";
    echo "IP: <strong>$ip</strong><br>";
} else {
    echo "<span class='error'>❌ Falha ao resolver DNS</span><br>";
    echo "<strong>Possível problema:</strong> Docker não consegue acessar DNS externo<br>";
    echo "<br><strong>Solução:</strong> Adicione DNS no docker-compose.yml:<br>";
    echo "<pre>
services:
  php-app:
    dns:
      - 8.8.8.8
      - 8.8.4.4
</pre>";
}
echo "</div>";

// 4. Testar Conectividade (fsockopen)
echo "<div class='section'>";
echo "<h2>4️⃣ Conectividade TCP (Porta 443)</h2>";

$host = 'acesso.infoconv.receita.fazenda.gov.br';
$port = 443;
$timeout = 10;

echo "Tentando conectar em: <strong>$host:$port</strong><br>";
echo "Timeout: <strong>{$timeout}s</strong><br><br>";

$startTime = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
$endTime = microtime(true);

if ($fp) {
    echo "<span class='success'>✅ Conexão TCP estabelecida</span><br>";
    echo "Tempo: <strong>" . round(($endTime - $startTime) * 1000) . "ms</strong><br>";
    fclose($fp);
} else {
    echo "<span class='error'>❌ Falha na conexão TCP</span><br>";
    echo "Erro #$errno: <strong>$errstr</strong><br><br>";
    
    echo "<strong>Possíveis causas:</strong><br>";
    echo "- Firewall bloqueando a porta 443<br>";
    echo "- Docker sem acesso à rede externa<br>";
    echo "- Proxy/VPN interferindo<br>";
    echo "- DNS não resolvido corretamente<br>";
}
echo "</div>";

// 5. Testar cURL (alternativa ao SOAP)
if (extension_loaded('curl')) {
    echo "<div class='section'>";
    echo "<h2>5️⃣ Teste cURL (HTTPS)</h2>";
    
    $url = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL';
    
    echo "Tentando baixar WSDL via cURL...<br>";
    echo "URL: <code>$url</code><br><br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    curl_close($ch);
    
    if ($response && $httpCode == 200) {
        echo "<span class='success'>✅ Download do WSDL bem-sucedido</span><br>";
        echo "HTTP Code: <strong>$httpCode</strong><br>";
        echo "Tempo: <strong>" . round(($endTime - $startTime) * 1000) . "ms</strong><br>";
        echo "Tamanho: <strong>" . strlen($response) . " bytes</strong><br>";
        
        if (strpos($response, 'wsdl:definitions') !== false) {
            echo "<span class='success'>✅ WSDL válido (contém 'wsdl:definitions')</span><br>";
        }
    } else {
        echo "<span class='error'>❌ Falha no download do WSDL</span><br>";
        echo "HTTP Code: <strong>$httpCode</strong><br>";
        echo "Erro cURL: <strong>$error</strong><br>";
        
        echo "<br><details><summary>📋 Log Verbose (clique para expandir)</summary>";
        echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
        echo "</details>";
    }
    
    echo "</div>";
}

// 6. Testar file_get_contents (simples)
echo "<div class='section'>";
echo "<h2>6️⃣ Teste file_get_contents (HTTP Wrapper)</h2>";

$url = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL';

echo "Tentando baixar WSDL via file_get_contents...<br>";
echo "URL: <code>$url</code><br><br>";

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ],
    'http' => [
        'timeout' => 15,
        'user_agent' => 'PHP-Test-Script'
    ]
]);

$startTime = microtime(true);
$response = @file_get_contents($url, false, $context);
$endTime = microtime(true);

if ($response) {
    echo "<span class='success'>✅ Download bem-sucedido</span><br>";
    echo "Tempo: <strong>" . round(($endTime - $startTime) * 1000) . "ms</strong><br>";
    echo "Tamanho: <strong>" . strlen($response) . " bytes</strong><br>";
} else {
    echo "<span class='error'>❌ Falha no download</span><br>";
    $error = error_get_last();
    echo "Erro: <strong>" . ($error['message'] ?? 'Desconhecido') . "</strong><br>";
}

echo "</div>";

// 7. Informações de Rede Docker
echo "<div class='section'>";
echo "<h2>7️⃣ Informações de Rede</h2>";

$ifconfig = @shell_exec('ip addr 2>&1 || ifconfig 2>&1');
if ($ifconfig) {
    echo "<details><summary>📋 Interfaces de Rede (clique para expandir)</summary>";
    echo "<pre>" . htmlspecialchars($ifconfig) . "</pre>";
    echo "</details>";
}

echo "<br>";

$nameservers = @shell_exec('cat /etc/resolv.conf 2>&1');
if ($nameservers) {
    echo "<strong>Servidores DNS:</strong><br>";
    echo "<pre>" . htmlspecialchars($nameservers) . "</pre>";
}

echo "</div>";

// 8. Recomendações
echo "<div class='section'>";
echo "<h2>8️⃣ 💡 Próximos Passos</h2>";

echo "<ol>";
echo "<li>Se o <strong>DNS</strong> falhou: Adicione DNS ao docker-compose.yml (8.8.8.8)</li>";
echo "<li>Se a <strong>conexão TCP</strong> falhou: Verifique firewall e rede do Docker</li>";
echo "<li>Se o <strong>cURL funcionou</strong>: O problema está no SoapClient/certificado</li>";
echo "<li>Se <strong>nada funcionou</strong>: O Docker não tem acesso à internet</li>";
echo "</ol>";

echo "</div>";

echo "</body></html>";
?>

