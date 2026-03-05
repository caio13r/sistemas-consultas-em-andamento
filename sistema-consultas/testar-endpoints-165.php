<?php
/**
 * Script PHP para testar endpoints do servidor 165 e verificar se retornam data_criacao
 * Uso: php testar-endpoints-165.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$API_BASE_URL = $_ENV['API_IDENTITY_URL'] ?? 'http://192.168.161.165:8082';
$API_TOKEN = $_ENV['API_TOKEN'] ?? null;

if (!$API_TOKEN) {
    die("❌ ERRO: API_TOKEN não configurado no arquivo .env!\n");
}

echo "==========================================\n";
echo "🔍 TESTE DE ENDPOINTS - SERVIDOR 165\n";
echo "==========================================\n";
echo "\n";
echo "📍 Servidor: $API_BASE_URL\n";
echo "🔑 Token: " . substr($API_TOKEN, 0, 10) . "... (primeiros 10 caracteres)\n";
echo "\n";

// Teste 1: Consulta por CPF
echo "==========================================\n";
echo "1️⃣ TESTE: Consulta por CPF\n";
echo "==========================================\n";
echo "\n";

$cpfTeste = "06672679642";
echo "CPF de teste: $cpfTeste\n";
echo "\n";

$url = "$API_BASE_URL/api/consulta/identidade/cpf?token=" . urlencode($API_TOKEN) . "&cpf=$cpfTeste";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "📊 Status HTTP: $httpCode\n";
echo "\n";

if ($httpCode == 200 && !$curlError) {
    echo "✅ Requisição bem-sucedida!\n";
    echo "\n";
    
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "❌ Erro ao decodificar JSON\n";
        echo "Resposta bruta (primeiros 500 chars):\n";
        echo substr($response, 0, 500) . "\n";
    } else {
        // Identificar estrutura
        $item = null;
        if (isset($data['identidade'])) {
            $item = $data['identidade'];
            echo "📦 Estrutura: objeto 'identidade'\n";
        } elseif (isset($data['list']) && is_array($data['list']) && !empty($data['list'])) {
            $item = $data['list'][0];
            echo "📦 Estrutura: array 'list' (primeiro item)\n";
        } else {
            echo "⚠️ Estrutura não reconhecida\n";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $item = null;
        }
        
        if ($item) {
            echo "\n";
            echo "📊 Campos disponíveis:\n";
            foreach (array_keys($item) as $key) {
                $value = $item[$key];
                if (is_string($value) && strlen($value) > 50) {
                    $valuePreview = substr($value, 0, 50) . "...";
                } elseif (is_array($value)) {
                    $valuePreview = "array[" . count($value) . "]";
                } elseif (is_object($value)) {
                    $valuePreview = "object{}";
                } else {
                    $valuePreview = (string)$value;
                }
                printf("  • %-30s = %s\n", $key, $valuePreview);
            }
            
            echo "\n";
            echo "🔍 Verificando data_criacao:\n";
            
            // Verificar na raiz
            if (isset($item['data_criacao']) && !empty($item['data_criacao'])) {
                echo "  ✅ data_criacao encontrado na raiz: " . $item['data_criacao'] . "\n";
            } else {
                echo "  ❌ data_criacao NÃO encontrado na raiz\n";
            }
            
            // Verificar dentro de foto (se for objeto)
            if (isset($item['foto']) && is_array($item['foto'])) {
                if (isset($item['foto']['data_criacao']) && !empty($item['foto']['data_criacao'])) {
                    echo "  ✅ data_criacao encontrado dentro de 'foto': " . $item['foto']['data_criacao'] . "\n";
                } else {
                    echo "  ❌ data_criacao NÃO encontrado dentro de 'foto'\n";
                    if (is_array($item['foto'])) {
                        echo "     Campos dentro de 'foto': " . implode(', ', array_keys($item['foto'])) . "\n";
                    }
                }
            }
            
            // Verificar outros campos relacionados a data
            echo "\n";
            echo "🔍 Campos relacionados a data encontrados:\n";
            $camposData = [];
            foreach (array_keys($item) as $key) {
                $keyLower = strtolower($key);
                if (strpos($keyLower, 'data') !== false || 
                    strpos($keyLower, 'criacao') !== false || 
                    strpos($keyLower, 'created') !== false || 
                    strpos($keyLower, 'date') !== false || 
                    strpos($keyLower, 'upload') !== false ||
                    strpos($keyLower, 'insercao') !== false) {
                    $camposData[] = [$key, $item[$key]];
                }
            }
            
            if (!empty($camposData)) {
                foreach ($camposData as list($campo, $valor)) {
                    echo "  ✅ $campo: $valor\n";
                }
            } else {
                echo "  ❌ Nenhum campo relacionado a data encontrado\n";
            }
            
            echo "\n";
            echo "🖼️ Informações sobre foto:\n";
            if (isset($item['foto'])) {
                $foto = $item['foto'];
                if (is_string($foto)) {
                    echo "  ✅ Campo 'foto' existe (string, tamanho: " . strlen($foto) . " chars)\n";
                } elseif (is_array($foto)) {
                    echo "  ✅ Campo 'foto' existe (array/objeto)\n";
                    echo "     Campos: " . implode(', ', array_keys($foto)) . "\n";
                } else {
                    echo "  ⚠️ Campo 'foto' existe mas tipo desconhecido: " . gettype($foto) . "\n";
                }
            } else {
                echo "  ❌ Campo 'foto' não encontrado\n";
            }
        }
    }
} else {
    echo "❌ Erro na requisição!\n";
    if ($curlError) {
        echo "Erro cURL: $curlError\n";
    }
    echo "\n";
    echo "Resposta:\n";
    echo substr($response, 0, 500) . "\n";
}

echo "\n";
echo "\n";

// Teste 2: Consulta por Nome
echo "==========================================\n";
echo "2️⃣ TESTE: Consulta por Nome\n";
echo "==========================================\n";
echo "\n";

$nomeTeste = "HUMBERTO FRANCO BUENO";
echo "Nome de teste: $nomeTeste\n";
echo "\n";

$url = "$API_BASE_URL/api/consulta/identidade/nome?token=" . urlencode($API_TOKEN) . "&name=" . urlencode($nomeTeste) . "&page_number=1&page_amount=1";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "📊 Status HTTP: $httpCode\n";
echo "\n";

if ($httpCode == 200 && !$curlError) {
    echo "✅ Requisição bem-sucedida!\n";
    echo "\n";
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['list']) && is_array($data['list']) && !empty($data['list'])) {
        $item = $data['list'][0];
        echo "📦 Estrutura: array 'list' (primeiro item)\n";
        echo "\n";
        echo "🔍 Verificando data_criacao:\n";
        if (isset($item['data_criacao']) && !empty($item['data_criacao'])) {
            echo "  ✅ data_criacao encontrado: " . $item['data_criacao'] . "\n";
        } else {
            echo "  ❌ data_criacao NÃO encontrado\n";
            echo "\n";
            echo "  Campos disponíveis:\n";
            foreach (array_keys($item) as $key) {
                echo "    - $key\n";
            }
        }
    } else {
        echo "⚠️ Nenhum resultado encontrado ou estrutura diferente\n";
        if ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
} else {
    echo "❌ Erro na requisição!\n";
    if ($curlError) {
        echo "Erro cURL: $curlError\n";
    }
}

echo "\n";
echo "==========================================\n";
echo "✅ TESTES CONCLUÍDOS\n";
echo "==========================================\n";
echo "\n";
echo "📝 Resumo:\n";
echo "1. Verifique se o servidor 165 retorna data_criacao\n";
echo "2. Se não retornar, o sistema fará chamada adicional ao servidor de foto\n";
echo "3. O servidor de foto (128) retorna data_criacao conforme documentado\n";
echo "\n";
